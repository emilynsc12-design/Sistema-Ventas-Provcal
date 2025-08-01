<?php
include_once "encabezado.php";
include_once "navbar.php";
include_once "funciones.php";
session_start();
if (empty($_SESSION['usuario']))
    header("location: login.php");

$_SESSION['lista'] = (isset($_SESSION['lista'])) ? $_SESSION['lista'] : [];
$total = calcularTotalLista($_SESSION['lista']);
?>

<style>
    .modal-dialog-centered {
        animation: fadeInDown 0.3s;
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-10%);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
<div class="container mt-3">
    <form action="agregar_producto_venta.php" method="post" class="row">
        <div class="col-6">
            <input class="form-control form-control-lg" name="codigo" autofocus id="codigo" pattern="\d*" maxlength="12"
                oninput="this.value = this.value.replace(/[^0-9]/g, '')" type="text"
                placeholder="Código de barras del producto">
        </div>
        <div class="col-3">
            <input class="form-control form-control-lg" name="cantidad" type="number" min="1" value="1"
                placeholder="Cantidad">
        </div>
        <div class="col-3">
            <input type="submit" value="Agregar" name="agregar" class="btn btn-success mt-2 w-100">
        </div>
    </form>

    <?php if (isset($_SESSION['mensaje_error'])): ?>
        <div class="alert alert-danger mt-3" role="alert">
            <?= $_SESSION['mensaje_error'];
            unset($_SESSION['mensaje_error']); ?>
        </div>
    <?php endif; ?>

    <?php if ($_SESSION['lista']) { ?>
        <div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th>Quitar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['lista'] as $lista) { ?>
                        <tr>
                            <td><?= $lista->codigo; ?></td>
                            <td><?= $lista->nombre; ?></td>
                            <td>S/.<?= $lista->venta; ?></td>
                            <td>
                                <form action="actualizar_cantidad.php" method="post" class="d-flex">
                                    <input type="hidden" name="id" value="<?= $lista->id ?>">
                                    <input type="number" name="cantidad" value="<?= $lista->cantidad ?>" min="1"
                                        class="form-control form-control-sm" style="width: 70px;">
                                    <button type="submit" class="btn btn-sm btn-primary ms-1"><i
                                            class="fa fa-sync"></i></button>
                                </form>
                            </td>

                            <td>S/.<?= floatval($lista->cantidad * $lista->venta); ?></td>
                            <td>
                                <a href="quitar_producto_venta.php?id=<?= $lista->id ?>" class="btn btn-danger">
                                    <i class="fa fa-times"></i>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <div class="text-center mt-3">
                <h1>Total: S/.<?= $total; ?></h1>
                <button class="btn btn-primary btn-lg" onclick="abrirModalConfirmacion()">
                    <i class="fa fa-check"></i>
                    Terminar venta
                </button>
                <a class="btn btn-danger btn-lg" href="cancelar_venta.php">
                    <i class="fa fa-times"></i>
                    Cancelar
                </a>
            </div>
        </div>
    <?php } ?>

    <!-- Modal de confirmación -->
    <div class="modal fade" id="ventaModal" tabindex="-1" aria-labelledby="ventaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header">
                    <h5 class="modal-title w-100" id="ventaModalLabel">Confirmar venta</h5>
                </div>
                <div class="modal-body">
                    ¿Estás seguro de que deseas realizar la venta y generar el ticket?
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="confirmarVenta()"
                        id="btnConfirmarVenta">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
    <iframe id="ticketFrame"
        style="position: absolute; left: -9999px; top: -9999px; width: 1px; height: 1px; border: none;"></iframe>


</div>

<script>
    function abrirModalConfirmacion() {
        const modal = new bootstrap.Modal(document.getElementById('ventaModal'));
        modal.show();
    }

    function confirmarVenta() {
        const btn = document.getElementById("btnConfirmarVenta");
        btn.disabled = true;

        fetch('registrar_venta.php', {
            method: 'POST'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const id = data.id_venta;

                    // Cerrar el modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('ventaModal'));
                    modal.hide();

                    // Generar, imprimir y descargar el PDF
                    const frame = document.getElementById("ticketFrame");
                    frame.src = "generar_ticket_pdf.php?id=" + id;

                    frame.onload = function () {
                        frame.contentWindow.focus();
                        frame.contentWindow.print();

                        const a = document.createElement("a");
                        a.href = frame.src;
                        a.download = "ticket_" + id + ".pdf";
                        document.body.appendChild(a);
                        a.click();
                        a.remove();

                        // Luego de impresión/descarga, limpiar interfaz
                        setTimeout(() => {
                            window.location.href = "vender.php"; // limpia la vista
                        }, 8000); // espera breve para que imprima antes de recargar
                    };
                } else {
                    alert("Error al registrar la venta.");
                }
                btn.disabled = false;
            })
            .catch(error => {
                console.error("Error:", error);
                alert("Error inesperado.");
                btn.disabled = false;
            });
    }
</script>