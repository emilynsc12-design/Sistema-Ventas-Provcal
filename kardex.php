<?php
ini_set('max_execution_time', 180); // 120 segundos
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

// ─────────── Definimos hoy para detectar “futuro” ───────────
$hoy = date('Y-m-d');
/* ─────────────── 2. Productos y estructura base ────────── */
$productos = json_decode(json_encode(
    select("SELECT id, codigo, nombre FROM productos ORDER BY nombre ASC")
), true);

$datos_kardex = [];

foreach ($productos as $producto) {
    $id = $producto['id'];
    $es_pan = str_starts_with(strtolower($producto['nombre']), 'pan');

    /* 2-A. Stock antes del mes */
    $fechaAntes = date('Y-m-d', strtotime("$fechaInicio -1 day"));
    $stock_anterior = select(
        "SELECT stock_resultante
           FROM kardex
          WHERE idProducto = ? AND DATE(fecha) <= ?
       ORDER BY fecha DESC LIMIT 1",
        [$id, $fechaAntes]
    );

    /* 2-B. Primer movimiento del mes (alta) */
    $primer_mov = select(
        "SELECT stock_resultante, DATE(fecha) AS dia_mov
           FROM kardex
          WHERE idProducto = ? AND DATE(fecha) BETWEEN ? AND ?
       ORDER BY fecha ASC LIMIT 1",
        [$id, $fechaInicio, $fechaFin]
    );

    /* 2-C. ¿Nació este producto este mes? */
    $nacio_este_mes = !$stock_anterior;

    /* 2-B bis. Para PAN creado este mes, guardo stock + fecha */
    $stock_creacion = 0;
    $dia_creacion = null;
    if ($nacio_este_mes && !empty($primer_mov)) {
        $stock_creacion = intval($primer_mov[0]->stock_resultante);
        $dia_creacion = $primer_mov[0]->dia_mov;  // "YYYY-MM-DD"
    }

    /* 2-D. Stock inicial del mes (no-pan) */
    $stock_inicial_mes = $stock_anterior
        ? intval($stock_anterior[0]->stock_resultante)
        : ($primer_mov ? intval($primer_mov[0]->stock_resultante) : 0);

    /* 2-E. Estructura base */
    $datos_kardex[$id] = [
        'codigo' => $producto['codigo'],
        'nombre' => $producto['nombre'],
        'stock_inicial' => $es_pan ? 0 : $stock_inicial_mes,
        'ingresos_mes' => 0,
        'salidas_mes' => 0,
        'stock_final' => $stock_inicial_mes,
        'dias' => []
    ];

    foreach ($fechas as $f) {
        $datos_kardex[$id]['dias'][$f] = [
            'stock_inicio' => null,
            'ajuste_inicial' => 0,
            'ingresos' => 0,
            'salidas' => 0,
            'stock_final' => null
        ];
    }

    /* ───────── 3. Cargo movimientos ───────── */
    $movimientos = json_decode(json_encode(select(
        "SELECT fecha, tipo_movimiento AS tipo, cantidad, observacion
           FROM kardex
          WHERE idProducto = ? AND DATE(fecha) BETWEEN ? AND ?",
        [$id, $fechaInicio, $fechaFin]
    )), true);

    foreach ($movimientos as $mov) {
        $dia = substr($mov['fecha'], 0, 10);
        if (!isset($datos_kardex[$id]['dias'][$dia]))
            continue;

        $tipo = $mov['tipo'];
        $cant = (int) $mov['cantidad'];
        $obs = strtolower($mov['observacion'] ?? '');

        if ($tipo === 'INGRESO') {
            $datos_kardex[$id]['dias'][$dia]['ingresos'] += $cant;
            $datos_kardex[$id]['ingresos_mes'] += $cant;

        } elseif ($tipo === 'SALIDA') {
            $datos_kardex[$id]['dias'][$dia]['salidas'] += $cant;
            $datos_kardex[$id]['salidas_mes'] += $cant;

        } elseif ($tipo === 'AJUSTE') {
            if (str_contains($obs, 'anulación')) {
                $datos_kardex[$id]['dias'][$dia]['salidas'] -= $cant;
                $datos_kardex[$id]['salidas_mes'] -= $cant;

            } elseif (str_contains($obs, 'stock inicial')) {
                // Sólo aplico el ajuste si EXISTÍA antes del mes
                if (!$nacio_este_mes) {
                    $datos_kardex[$id]['dias'][$dia]['ajuste_inicial'] += $cant;
                }
            } elseif (str_contains($obs, 'edición del producto')) {
                //$datos_kardex[$id]['dias'][$dia]['ajuste_inicial'] += -$cant;
                $datos_kardex[$id]['ingresos_mes'] -= $cant;
                $datos_kardex[$id]['dias'][$dia]['ingresos'] -= $cant;

            } else {
                $datos_kardex[$id]['dias'][$dia]['ajuste_inicial'] += $cant;
            }
        }
    }

    if ($es_pan && $nacio_este_mes) {
        $datos_kardex[$id]['ingresos_mes'] += $stock_creacion;
    }

    /* ───────── 4. Recorrido diario ───────── */
    // inicio de cálculo mensual
    $stock = $nacio_este_mes ? 0 : $stock_inicial_mes;

    /* ─────────────── 4. Recorrido diario ────────────── */
    foreach ($fechas as $f) {
        // Si NO es pan y la fecha es futura, mostrar todo a 0 y saltar
        if (!$es_pan && $f > $hoy) {
            $datos_kardex[$id]['dias'][$f]['stock_inicio'] = 0;
            $datos_kardex[$id]['dias'][$f]['ingresos'] = 0;
            $datos_kardex[$id]['dias'][$f]['salidas'] = 0;
            $datos_kardex[$id]['dias'][$f]['stock_final'] = 0;
            continue;
        }
        // A) Días antes de la creación → ceros
        if ($nacio_este_mes && $dia_creacion && $f < $dia_creacion) {
            // …  
            continue;
        }

        // B) Día de creación → arranca con stock de creación
        if ($nacio_este_mes && $dia_creacion && $f === $dia_creacion) {
            $stock_inicio = $stock_creacion;

            // C) **Pan: nunca arrastra stock. Si no hay movimiento, salto.**
        } elseif ($es_pan) {
            $ing = $datos_kardex[$id]['dias'][$f]['ingresos'];
            $sal = $datos_kardex[$id]['dias'][$f]['salidas'];
            $aj = $datos_kardex[$id]['dias'][$f]['ajuste_inicial'];

            // si es pan y no hubo ningún movimiento, dejamos todo en null (se renderiza como 0)
            if ($ing === 0 && $sal === 0 && $aj === 0) {
                continue;
            }

            // si hubo movimiento, tomo el stock_final del día anterior SOLO para este día
            $ayer = date('Y-m-d', strtotime("$f -1 day"));
            $stock_inicio = $datos_kardex[$id]['dias'][$ayer]['stock_final'];

            // D) Resto de productos (no-pan) → lógica normal de arrastre
        } else {
            $idx = array_search($f, $fechas);
            if ($idx === 0) {
                $stock_inicio = $stock_inicial_mes;
            } else {
                $ayer = $fechas[$idx - 1];
                $stock_inicio = $datos_kardex[$id]['dias'][$ayer]['stock_final'];
            }
        }

        // Asigno y calculo final
        $datos_kardex[$id]['dias'][$f]['stock_inicio'] = $stock_inicio;

        $aj = $datos_kardex[$id]['dias'][$f]['ajuste_inicial'];
        $ing = $datos_kardex[$id]['dias'][$f]['ingresos'];
        $sal = $datos_kardex[$id]['dias'][$f]['salidas'];
        $stock_fin = $stock_inicio + $aj + $ing - $sal;
        $datos_kardex[$id]['dias'][$f]['stock_final'] = $stock_fin;
        $stock = $stock_fin;
    }

    /* ─────────────── Resumen mensual ────────────── */
    if ($es_pan && $nacio_este_mes) {
        // stock_final = ingresos_mes (ya incluye stock_creacion) - salidas_mes
        $datos_kardex[$id]['stock_final'] =
            $datos_kardex[$id]['ingresos_mes']
            - $datos_kardex[$id]['salidas_mes'];
    } else {
        $datos_kardex[$id]['stock_final'] = $stock;
    }


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
        background: #fff;
        border-top: 5px solid #d3d6da;
        border-right: 5px solid #d3d6da;
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
        vertical-align: middle;
    }

    .table-responsive {
        overflow-x: auto;
        width: 100%;
    }
</style>

<div class="container-fluid mt-4">
    <div class="kardex-container">
        <h3 class="mb-3">Kardex Mensual</h3>
        <form method="get">
            <div class="row align-items-end justify-content-between mb-3">
                <div class="col-md-auto d-flex flex-wrap gap-3 align-items-end">
                    <div>
                        <label for="inicio"><strong>Fecha Inicio</strong></label>
                        <input type="date" name="inicio" id="inicio" class="form-control" value="<?= $fechaInicio ?>">
                    </div>
                    <div>
                        <label for="fin"><strong>Fecha Fin</strong></label>
                        <input type="date" name="fin" id="fin" class="form-control" value="<?= $fechaFin ?>">
                    </div>
                    <div><button type="submit" class="btn btn-success">Mostrar</button></div>
                </div>
                <div class="col-md-auto d-flex flex-wrap align-items-end gap-3">
                    <a href="exportar_kardex_excel.php?inicio=<?= $fechaInicio ?>&fin=<?= $fechaFin ?>"
                        class="btn btn-success" target="_blank">Exportar a Excel</a>
                    <div>
                        <label for="buscadorPersonalizado" class="fw-bold">Buscar:</label>
                        <input type="text" id="buscadorPersonalizado" class="form-control">
                    </div>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table id="tablaKardex"
                class="table table-bordered table-striped table-sm text-center align-middle w-100 kardex-table">
                <thead>
                    <tr>
                        <th rowspan="2">Código</th>
                        <th rowspan="2">Producto</th>
                        <th rowspan="2">Cantidad Inicial del Mes</th>
                        <th colspan="3">Total del Mes</th>
                        <?php foreach ($fechas as $f): ?>
                            <th colspan="4"><?= date('d-M', strtotime($f)) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <th>Ingresos</th>
                        <th>Salidas</th>
                        <th>Stock Final</th>
                        <?php foreach ($fechas as $f): ?>
                            <th>Inicio</th>
                            <th>Ingresos</th>
                            <th>Salidas</th>
                            <th>Stock Final</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos_kardex as $p): ?>
                        <tr>
                            <td><?= $p['codigo'] ?></td>
                            <td><?= $p['nombre'] ?></td>
                            <td><?= $p['stock_inicial'] ?></td>
                            <td><?= $p['ingresos_mes'] ?></td>
                            <td><?= $p['salidas_mes'] ?></td>
                            <td><?= $p['stock_final'] ?></td>
                            <?php foreach ($p['dias'] as $d): ?>
                                <td><?= $d['stock_inicio'] ?? 0 ?></td>
                                <td><?= $d['ingresos'] ?? 0 ?></td>
                                <td><?= $d['salidas'] ?? 0 ?></td>
                                <td><?= $d['stock_final'] ?? 0 ?></td>
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
        $('#buscadorPersonalizado').on('keyup', function () {
            const texto = $(this).val().toLowerCase();
            $('#tablaKardex tbody tr').each(function () {
                const cod = $(this).find('td:eq(0)').text().toLowerCase();
                const nom = $(this).find('td:eq(1)').text().toLowerCase();
                $(this).toggle(cod.includes(texto) || nom.includes(texto));
            });
        });
    });
</script>