<#
.SYNOPSIS
    Inicia el Sistema de Ventas PROVCAL (base de datos + servidor web).

.DESCRIPTION
    Arranca MariaDB (base de datos local) y el servidor web PHP.
    No hace falta instalar nada: busca los programas automaticamente.

.USO
    Doble clic en "Iniciar sistema.bat"
    o desde PowerShell:  .\iniciar.ps1
#>
param(
    # Evita la pausa final (usado cuando lo llama otro script)
    [switch]$SinPausa
)
# ---------- Rutas del proyecto (relativas a donde esta este script) ----------
$rutaProyecto = Split-Path -Parent $MyInvocation.MyCommand.Path

# ---------- Buscar MariaDB ----------
$rutasMariaDB = @(
    "C:\Program Files\MariaDB 13.0\bin",
    "C:\Program Files\MariaDB 12.0\bin",
    "C:\Program Files\MariaDB 11.4\bin",
    "C:\Program Files\MariaDB 10.11\bin"
)
$binMaria = $rutasMariaDB | Where-Object { Test-Path "$_\mariadbd.exe" } | Select-Object -First 1

# ---------- Buscar PHP ----------
$rutasPHP = @(
    "C:\Users\$env:USERNAME\AppData\Local\Microsoft\WinGet\Packages",
    "C:\php",
    "C:\xampp\php",
    "C:\laragon\bin\php"
)
$phpExe = $null
foreach ($base in $rutasPHP) {
    if (Test-Path $base) {
        $candidato = Get-ChildItem -Path $base -Filter "php.exe" -Recurse -Depth 4 -ErrorAction SilentlyContinue |
                     Select-Object -First 1 -ExpandProperty FullName
        if ($candidato) { $phpExe = $candidato; break }
    }
}

Write-Host ""
Write-Host "==============================================" -ForegroundColor Green
Write-Host "   SISTEMA DE VENTAS PROVCAL - Inicio" -ForegroundColor Green
Write-Host "==============================================" -ForegroundColor Green
Write-Host ""

# ---------- Comprobar que exista .env ----------
if (-not (Test-Path "$rutaProyecto\.env")) {
    Write-Host "[ERROR] No se encontro el archivo .env" -ForegroundColor Red
    Write-Host "        Copia .env.example a .env y completa los datos de la base de datos." -ForegroundColor Yellow
    Read-Host "Pulsa Enter para salir"
    exit 1
}

# ---------- 1. Arrancar MariaDB ----------
if (-not $binMaria) {
    Write-Host "[ERROR] No se encontro MariaDB instalado." -ForegroundColor Red
    Read-Host "Pulsa Enter para salir"
    exit 1
}

$puertoDB = 3306
$yaCorriendo = Get-NetTCPConnection -LocalPort $puertoDB -State Listen -ErrorAction SilentlyContinue

if ($yaCorriendo) {
    Write-Host "[OK] Base de datos ya estaba corriendo (puerto $puertoDB)" -ForegroundColor Green
} else {
    Write-Host "[...] Arrancando base de datos MariaDB..." -ForegroundColor Cyan
    Start-Process -FilePath "$binMaria\mariadbd.exe" `
        -ArgumentList "--datadir=`"$rutaProyecto\mariadb-data`"", "--port=$puertoDB", "--bind-address=127.0.0.1" `
        -WindowStyle Hidden

    # Esperar a que este lista (max 30 segundos)
    $lista = $false
    for ($i = 0; $i -lt 30; $i++) {
        Start-Sleep -Seconds 1
        if (Get-NetTCPConnection -LocalPort $puertoDB -State Listen -ErrorAction SilentlyContinue) {
            $lista = $true; break
        }
    }
    if ($lista) { Write-Host "[OK] Base de datos lista (puerto $puertoDB)" -ForegroundColor Green }
    else        { Write-Host "[ERROR] La base de datos no arranco." -ForegroundColor Red }
}

# ---------- 2. Arrancar servidor web PHP ----------
$puertoWeb = 8000
$yaWeb = Get-NetTCPConnection -LocalPort $puertoWeb -State Listen -ErrorAction SilentlyContinue

if ($yaWeb) {
    Write-Host "[OK] Servidor web ya estaba corriendo (puerto $puertoWeb)" -ForegroundColor Green
} else {
    if (-not $phpExe) {
        Write-Host "[ERROR] No se encontro php.exe instalado." -ForegroundColor Red
        Read-Host "Pulsa Enter para salir"
        exit 1
    }
    Write-Host "[...] Arrancando servidor web PHP..." -ForegroundColor Cyan
    Start-Process -FilePath $phpExe `
        -ArgumentList "-S", "127.0.0.1:$puertoWeb", "-t", "`"$rutaProyecto`"" `
        -WorkingDirectory $rutaProyecto -WindowStyle Hidden

    Start-Sleep -Seconds 3
    if (Get-NetTCPConnection -LocalPort $puertoWeb -State Listen -ErrorAction SilentlyContinue) {
        Write-Host "[OK] Servidor web listo (puerto $puertoWeb)" -ForegroundColor Green
    } else {
        Write-Host "[ERROR] El servidor web no arranco." -ForegroundColor Red
    }
}

# ---------- Resumen ----------
Write-Host ""
Write-Host "==============================================" -ForegroundColor Yellow
Write-Host "   LISTO. Abre en tu navegador:" -ForegroundColor Yellow
Write-Host ""
Write-Host "   http://127.0.0.1:$puertoWeb" -ForegroundColor White
Write-Host "==============================================" -ForegroundColor Yellow
Write-Host ""
Write-Host "Para verlo desde internet, ejecuta: Iniciar con internet.bat" -ForegroundColor DarkGray
Write-Host "Para apagar todo, ejecuta:          Detener sistema.bat" -ForegroundColor DarkGray
Write-Host ""
if (-not $SinPausa) {
    Read-Host "Pulsa Enter para cerrar esta ventana"
}
