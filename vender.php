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
<div class="container mt-3">
    <form action="agregar_producto_venta.php" method="post" class="row">
        <div class="col-6">
            <input class="form-control form-control-lg" name="codigo" autofocus id="codigo" pattern="\d*" maxlength="6"
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
                <a class="btn btn-primary btn-lg" href="registrar_venta.php">
                    <i class="fa fa-check"></i>
                    Terminar venta
                </a>
                <a class="btn btn-danger btn-lg" href="cancelar_venta.php">
                    <i class="fa fa-times"></i>
                    Cancelar
                </a>
            </div>
        </div>
    <?php } ?>
</div>