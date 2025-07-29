<?php
include "funciones.php";
include "encabezado.php";
include "navbar.php";
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

/* ───────────────── 1. Rango de fechas ─────────────────── */
$fechaInicio = $_GET['inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fin'] ?? date('Y-m-t');

$fechas = [];
for ($d = new DateTime($fechaInicio); $d <= new DateTime($fechaFin); $d->modify('+1 day')) {
    $fechas[] = $d->format('Y-m-d');
}

/* ─────────────── 2. Productos y estructura base ────────── */
$productos = json_decode(json_encode(
    select("SELECT id, codigo, nombre FROM productos ORDER BY nombre ASC")
), true);

$datos_kardex = [];

foreach ($productos as $producto) {
    $id = $producto['id'];

    /* 2-A.  Stock antes de iniciar el mes */
    $fechaAntes = date('Y-m-d', strtotime("$fechaInicio -1 day"));

    $stock_anterior = select(
        "SELECT stock_resultante
           FROM kardex
          WHERE idProducto = ? AND DATE(fecha) <= ?
       ORDER BY fecha DESC LIMIT 1",
        [$id, $fechaAntes]
    );

    /* 2-B.  Primer movimiento dentro del mes (alta de producto) */
    $primer_mov = select(
        "SELECT stock_resultante, DATE(fecha) AS dia_mov
           FROM kardex
          WHERE idProducto = ? AND DATE(fecha) BETWEEN ? AND ?
       ORDER BY fecha ASC LIMIT 1",
        [$id, $fechaInicio, $fechaFin]
    );

    /* 2-C.  Cantidad Inicial que se mostrará en la tabla */
    $stock_inicial_mes = $stock_anterior
        ? intval($stock_anterior[0]->stock_resultante)
        : ($primer_mov ? intval($primer_mov[0]->stock_resultante) : 0);

    /* 2-D.  ¿Nació este producto dentro del mes? */
    $nacio_este_mes = !$stock_anterior;          // true si no existía antes

    /* 2-E.  Crear estructura */
    $datos_kardex[$id] = [
        'codigo' => $producto['codigo'],
        'nombre' => $producto['nombre'],
        'stock_inicial' => $stock_inicial_mes,  // para la columna principal
        'ingresos_mes' => 0,
        'salidas_mes' => 0,
        'stock_final' => $stock_inicial_mes,  // se recalculará
        'dias' => []
    ];

    foreach ($fechas as $fecha) {
        $datos_kardex[$id]['dias'][$fecha] = [
            'stock_inicio' => null,
            'ajuste_inicial' => 0,   // ← NUEVO
            'ingresos' => 0,
            'salidas' => 0,
            'stock_final' => null
        ];
    }

    /* ─────────────── 3. Cargar movimientos del mes ────────────── */
    $movimientos = json_decode(json_encode(select(
        "SELECT fecha, tipo_movimiento AS tipo, cantidad, observacion
           FROM kardex
          WHERE idProducto = ? AND DATE(fecha) BETWEEN ? AND ?",
        [$id, $fechaInicio, $fechaFin]
    )), true);

    foreach ($movimientos as $mov) {
        $dia = substr($mov['fecha'], 0, 10);
        $tipo = $mov['tipo'];
        $cant = (int) $mov['cantidad'];

        if (!isset($datos_kardex[$id]['dias'][$dia]))
            continue;

        if ($tipo === 'INGRESO') {
            $datos_kardex[$id]['dias'][$dia]['ingresos'] += $cant;
            $datos_kardex[$id]['ingresos_mes'] += $cant;

        } elseif ($tipo === 'SALIDA') {
            $datos_kardex[$id]['dias'][$dia]['salidas'] += $cant;
            $datos_kardex[$id]['salidas_mes'] += $cant;

        } elseif ($tipo === 'AJUSTE') {
            $obs = strtolower($mov['observacion'] ?? '');

            if (str_contains($obs, 'anulación')) {
                $datos_kardex[$id]['dias'][$dia]['salidas'] -= $cant;
                $datos_kardex[$id]['salidas_mes'] -= $cant;

            } elseif (str_contains($obs, 'stock inicial')) {
                /*  Alta de producto: afecta saldo pero NO se cuenta
                    como ingreso ni salida */
                $datos_kardex[$id]['dias'][$dia]['ajuste_inicial'] += $cant;
            } else {
                $datos_kardex[$id]['dias'][$dia]['ingresos'] += $cant;
                $datos_kardex[$id]['ingresos_mes'] += $cant;
            }
        }
    }

    /* ─────────────── 4. Recorrido diario ────────────── */
    $stock = $nacio_este_mes ? 0 : $stock_inicial_mes;

    foreach ($fechas as $i => $fecha) {
        /* Inicio */
        if ($i === 0) {
            $datos_kardex[$id]['dias'][$fecha]['stock_inicio'] = $stock;
        } else {
            $ayer = $fechas[$i - 1];
            $stock = $datos_kardex[$id]['dias'][$ayer]['stock_final'];
            $datos_kardex[$id]['dias'][$fecha]['stock_inicio'] = $stock;
        }

        /* Movimiento del día */
        $aj = $datos_kardex[$id]['dias'][$fecha]['ajuste_inicial'];
        $ing = $datos_kardex[$id]['dias'][$fecha]['ingresos'];
        $sal = $datos_kardex[$id]['dias'][$fecha]['salidas'];

        $stock = $stock + $aj + $ing - $sal;

        $datos_kardex[$id]['dias'][$fecha]['stock_final'] = $stock;
    }

    $datos_kardex[$id]['stock_final'] = $stock;
}  
?>


<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>



<style>
    .kardex-container {
        position: relative;
        border-radius: 5px;
        background: #ffffffff;
        border-top: 5px solid #d3d6daff;
        border-right: 5px solid #d3d6daff;
        margin-bottom: 20px;
        width: 100%;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }

    .kardex-table {
        border-collapse: collapse !important;
        width: 100%;
    }

    .kardex-table th,
    .kardex-table td {
        font-size: 14px;
        padding: 10px;
        white-space: nowrap;
        min-width: 60px;
        border: 1px solid rgba(0, 0, 0, 0.1);
        text-align: center;
        /* Por si DataTables lo sobreescribe */
        vertical-align: middle;
        /* Asegura alineación vertical */
    }

    /* Línea negra gruesa solo a las celdas de la primera fila que NO tengan rowspan */
    .kardex-table thead tr:first-child th[colspan] {
        border-bottom: none solid #000 !important;
        border-top: none solid #fafafa !important
    }

    /* Celdas con rowspan (Código, Producto, Stock Inicial) borde más delgado */
    .kardex-table thead tr:first-child th[rowspan] {
        border-bottom: 2px solid #000 !important;
    }

    /* Segunda fila sin borde */
    .kardex-table thead tr:nth-child(2) th {
        border-top: none !important;
        border-bottom: none !important;
    }

    /* Todos los th con estilos generales */
    .kardex-table thead th {
        background-color: #fff !important;
        color: #000 !important;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
        border-top: none !important;
    }

    .kardex-table tbody tr:nth-of-type(odd) {
        background-color: #fafafa;
    }

    .table-responsive {
        overflow-x: auto;
        width: 100%;
    }

    .fecha-input {
        width: 250px;
        height: 32px;
        font-size: 14px;
    }
</style>



<div class="container-fluid mt-4">
    <div class="kardex-container">
        <h3 class="mb-3">Kardex Mensual</h3>

        <form method="get">
            <div class="row align-items-end justify-content-between mb-3">
                <!-- Filtros a la izquierda -->
                <div class="col-md-auto d-flex flex-wrap gap-3 align-items-end">
                    <div>
                        <label for="inicio" class="form-label mb-1"><strong>Fecha Inicio</strong></label>
                        <input type="date" name="inicio" id="inicio" class="form-control" style="min-width: 180px;"
                            value="<?= $fechaInicio ?>">
                    </div>

                    <div>
                        <label for="fin" class="form-label mb-1"><strong>Fecha Fin</strong></label>
                        <input type="date" name="fin" id="fin" class="form-control" style="min-width: 180px;"
                            value="<?= $fechaFin ?>">
                    </div>

                    <div>
                        <button type="submit" class="btn btn-success mt-4">Mostrar</button>
                    </div>
                </div>

                <!-- Exportar y Buscar a la derecha -->
                <div class="col-md-auto d-flex flex-wrap align-items-end gap-3 justify-content-end">
                    <div class="col-auto">
                        <!-- BOTÓN EXPORTAR A EXCEL PERSONALIZADO -->
                        <a href="exportar_kardex_excel.php?inicio=<?= $fechaInicio ?>&fin=<?= $fechaFin ?>"
                            class="btn btn-success" target="_blank">
                            Exportar a Excel
                        </a>
                    </div>
                    <div>
                        <label for="buscadorPersonalizado" class="form-label mb-1 fw-bold">Buscar:</label>
                        <input type="text" id="buscadorPersonalizado" class="form-control" style="min-width: 220px;">
                    </div>
                </div>
            </div>
        </form>

        <div class="table-responsive" style="text-align-last: center;">
            <table id="tablaKardex"
                class="table table-bordered table-striped table-sm text-center align-middle w-100 kardex-table">
                <thead>
                    <tr>
                        <th rowspan="2">Código</th>
                        <th rowspan="2">Producto</th>
                        <th rowspan="2">Cantidad Inicial del Mes</th>
                        <th colspan="3">Total del Mes</th>
                        <?php foreach ($fechas as $fecha): ?>
                            <th colspan="4"><?= date('d-M', strtotime($fecha)) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <th>Ingresos</th>
                        <th>Salidas</th>
                        <th>Stock Final</th>
                        <?php foreach ($fechas as $fecha): ?>
                            <th>Inicio</th>
                            <th>Ingresos</th>
                            <th>Salidas</th>
                            <th>Stock Final</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos_kardex as $producto): ?>
                        <tr>
                            <td><?= $producto['codigo'] ?></td>
                            <td><?= $producto['nombre'] ?></td>
                            <td><?= $producto['stock_inicial'] ?></td>
                            <td><?= $producto['ingresos_mes'] ?></td>
                            <td><?= $producto['salidas_mes'] ?></td>
                            <td><?= $producto['stock_final'] ?></td>
                            <?php foreach ($producto['dias'] as $dia): ?>
                                <td><?= $dia['stock_inicio'] ?? 0 ?></td>
                                <td><?= $dia['ingresos'] ?></td>
                                <td><?= $dia['salidas'] ?></td>
                                <td><?= $dia['stock_final'] ?? 0 ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Función de filtro manual para columnas 0 (código) y 1 (producto)
        $('#buscadorPersonalizado').on('keyup', function () {
            const texto = $(this).val().toLowerCase();

            $('#tablaKardex tbody tr').each(function () {
                const codigo = $(this).find('td:eq(0)').text().toLowerCase();
                const producto = $(this).find('td:eq(1)').text().toLowerCase();

                if (codigo.includes(texto) || producto.includes(texto)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    });
</script>