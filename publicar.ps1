<#
.SYNOPSIS
    Sube tus cambios a GitHub.

.DESCRIPTION
    1. Muestra que archivos cambiaron.
    2. Los agrega todos al commit.
    3. Los sube a https://github.com/emilynsc12-design/Sistema-Ventas-Provcal

.USO
    Opcion A - Doble clic en "Subir a GitHub.bat" (usa un mensaje predeterminado)
    Opcion B - Desde PowerShell con tu propio mensaje:
        .\publicar.ps1 -Mensaje "Agregue reporte de ventas"
#>
param(
    [string]$Mensaje = "Actualizacion del sistema"
)

$rutaProyecto = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $rutaProyecto

Write-Host ""
Write-Host "==============================================" -ForegroundColor Green
Write-Host "   SUBIENDO CAMBIOS A GITHUB" -ForegroundColor Green
Write-Host "==============================================" -ForegroundColor Green
Write-Host ""

# ---------- Comprobar que es un repositorio Git ----------
if (-not (Test-Path "$rutaProyecto\.git")) {
    Write-Host "[ERROR] Esta carpeta no es un repositorio de Git." -ForegroundColor Red
    Read-Host "Pulsa Enter para salir"
    exit 1
}

# ---------- 1. Ver que hay cambiado ----------
Write-Host "[1/4] Revisando cambios..." -ForegroundColor Cyan
$estado = git status --short
if (-not $estado) {
    Write-Host ""
    Write-Host "[OK] No hay cambios que subir. Todo ya esta en GitHub." -ForegroundColor Green
    Write-Host ""
    Read-Host "Pulsa Enter para cerrar"
    exit 0
}

Write-Host "Archivos que se van a subir:" -ForegroundColor White
$estado | ForEach-Object { Write-Host "   $_" -ForegroundColor DarkGray }

# ---------- Aviso de seguridad: archivos sensibles ----------
Write-Host ""
Write-Host "[2/4] Comprobando que no haya datos sensibles..." -ForegroundColor Cyan

$sensibles = git status --short | ForEach-Object {
    $ruta = $_.Substring(3).Trim()
    if ($ruta -match '^\.env$|mariadb-data|\.pem$|\.key$|my\.cnf$|php-error\.log$') { $ruta }
}

if ($sensibles) {
    Write-Host ""
    Write-Host "[CUIDADO] Estos archivos podrian contener contrasenas:" -ForegroundColor Red
    $sensibles | ForEach-Object { Write-Host "   $_" -ForegroundColor Red }
    Write-Host ""
    $respuesta = Read-Host "Seguro que quieres subirlos? (s/N)"
    if ($respuesta -notmatch '^[sS]$') {
        Write-Host "Cancelado. Nada se subio." -ForegroundColor Yellow
        Read-Host "Pulsa Enter para cerrar"
        exit 1
    }
} else {
    Write-Host "[OK] No hay archivos sensibles." -ForegroundColor Green
}

# ---------- 3. Agregar y commitear ----------
Write-Host ""
Write-Host "[3/4] Guardando cambios..." -ForegroundColor Cyan
git add -A

# Si tras agregar no hay nada, no tiene sentido commitear
if (-not (git status --short)) {
    Write-Host "[OK] No hay cambios reales que subir." -ForegroundColor Green
    Read-Host "Pulsa Enter para cerrar"
    exit 0
}

git commit -m $Mensaje
if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Fallo el commit." -ForegroundColor Red
    Read-Host "Pulsa Enter para cerrar"
    exit 1
}

# ---------- 4. Subir ----------
Write-Host ""
Write-Host "[4/4] Subiendo a GitHub..." -ForegroundColor Cyan
git push origin main

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "==============================================" -ForegroundColor Green
    Write-Host "   LISTO. Cambios subidos a GitHub" -ForegroundColor Green
    Write-Host "   https://github.com/emilynsc12-design/Sistema-Ventas-Provcal" -ForegroundColor White
    Write-Host "==============================================" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "[ERROR] No se pudo subir. Puede que falte tu acceso a GitHub." -ForegroundColor Red
}

Write-Host ""
Read-Host "Pulsa Enter para cerrar"
