<?php
require 'vendor/autoload.php';
include "funciones.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$fechaInicio = $_GET['inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fin'] ?? date('Y-m-t');

$fechas = [];
$actual = new DateTime($fechaInicio);
$fin = new DateTime($fechaFin);
while ($actual <= $fin) {
    $fechas[] = $actual->format('Y-m-d');
    $actual->modify('+1 day');
}

// Obtener productos
$productos = select("SELECT id, codigo, nombre, existencia FROM productos ORDER BY nombre ASC");

// Inicializar datos
$datos_kardex = [];
foreach ($productos as $producto) {
    $id = $producto->id;
    $datos_kardex[$id] = [
        'codigo' => $producto->codigo,
        'nombre' => $producto->nombre,
        'stock_inicial' => $producto->existencia,
        'ingresos_mes' => 0,
        'salidas_mes' => 0,
        'stock_final' => $producto->existencia,
        'dias' => []
    ];

    foreach ($fechas as $fecha) {
        $datos_kardex[$id]['dias'][$fecha] = [
            'stock_inicio' => null,
            'ingresos' => 0,
            'salidas' => 0,
            'stock_final' => null
        ];
    }

    $movimientos = select(
        "SELECT fecha, tipo_movimiento AS tipo, cantidad FROM kardex WHERE idProducto = ? AND DATE(fecha) BETWEEN ? AND ?",
        [$id, $fechaInicio, $fechaFin]
    );

    foreach ($movimientos as $mov) {
        $fecha = substr($mov->fecha, 0, 10);
        $tipo = $mov->tipo;
        $cantidad = intval($mov->cantidad);

        if (!isset($datos_kardex[$id]['dias'][$fecha]))
            continue;

        if ($tipo === 'INGRESO') {
            $datos_kardex[$id]['dias'][$fecha]['ingresos'] += $cantidad;
            $datos_kardex[$id]['ingresos_mes'] += $cantidad;
        } elseif ($tipo === 'SALIDA') {
            $datos_kardex[$id]['dias'][$fecha]['salidas'] += $cantidad;
            $datos_kardex[$id]['salidas_mes'] += $cantidad;
        }
    }

    $stock = $producto->existencia;
    foreach ($fechas as $i => $fecha) {
        if ($i === 0) {
            $datos_kardex[$id]['dias'][$fecha]['stock_inicio'] = $stock;
        } else {
            $anterior = $fechas[$i - 1];
            $stock = $datos_kardex[$id]['dias'][$anterior]['stock_final'];
            $datos_kardex[$id]['dias'][$fecha]['stock_inicio'] = $stock;
        }

        $ing = $datos_kardex[$id]['dias'][$fecha]['ingresos'];
        $sal = $datos_kardex[$id]['dias'][$fecha]['salidas'];
        $stock = $stock + $ing - $sal;
        $datos_kardex[$id]['dias'][$fecha]['stock_final'] = $stock;
    }

    $datos_kardex[$id]['stock_final'] = $stock;
}

// Generar Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Encabezados fijos
$sheet->setCellValue('A1', 'Código');
$sheet->setCellValue('B1', 'Producto');
$sheet->setCellValue('C1', 'Stock Inicial');
$sheet->setCellValue('D1', 'Ingresos Mes');
$sheet->setCellValue('E1', 'Salidas Mes');
$sheet->setCellValue('F1', 'Stock Final');

// Encabezados por día
$colIndex = 7; // columna G
foreach ($fechas as $fecha) {
    $fechaStr = date('d-M', strtotime($fecha));

    $colLetraInicio = Coordinate::stringFromColumnIndex($colIndex);
    $colLetraFin = Coordinate::stringFromColumnIndex($colIndex + 3);

    // Fila 1: Encabezado con merge
    $sheet->setCellValue($colLetraInicio . '1', $fechaStr);
    $sheet->mergeCells($colLetraInicio . '1:' . $colLetraFin . '1');
    $sheet->getStyle($colLetraInicio . '1')->getAlignment()->setHorizontal('center');

    // Fila 2: Subcolumnas
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex) . '2', 'Inicio');
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex + 1) . '2', 'Ingresos');
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex + 2) . '2', 'Salidas');
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex + 3) . '2', 'Final');

    $colIndex += 4;
}

// Merge celdas para encabezados fijos
$sheet->mergeCells("A1:A2");
$sheet->mergeCells("B1:B2");
$sheet->mergeCells("C1:C2");
$sheet->mergeCells("D1:D2");
$sheet->mergeCells("E1:E2");
$sheet->mergeCells("F1:F2");

$lastCol = Coordinate::stringFromColumnIndex($colIndex - 1);
$sheet->getStyle("A1:{$lastCol}2")->getFont()->setBold(true);
$sheet->getStyle("A1:{$lastCol}2")->getAlignment()->setHorizontal('center');
$sheet->getStyle("A1:{$lastCol}2")->getAlignment()->setVertical('center');

// Llenar datos por producto
$fila = 3;
foreach ($datos_kardex as $producto) {
    $colIndex = 1;
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++) . $fila, $producto['codigo']);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++) . $fila, $producto['nombre']);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++) . $fila, $producto['stock_inicial']);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++) . $fila, $producto['ingresos_mes']);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++) . $fila, $producto['salidas_mes']);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++) . $fila, $producto['stock_final']);

    foreach ($producto['dias'] as $dia) {
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++) . $fila, $dia['stock_inicio'] ?? 0);
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++) . $fila, $dia['ingresos']);
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++) . $fila, $dia['salidas']);
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++) . $fila, $dia['stock_final'] ?? 0);
    }

    $fila++;
}

// Descargar Excel
$filename = "Kardex_" . date('Ymd_His') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
