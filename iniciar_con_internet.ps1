<#
.SYNOPSIS
    Inicia el sistema Y lo publica en internet con un enlace temporario.

.DESCRIPTION
    Primero arranca base de datos + servidor web (igual que "Iniciar sistema"),
    y luego crea un tunel de Cloudflare para que puedas abrir la pagina
    desde cualquier lugar con un enlace https.

    IMPORTANTE: el enlace cambia cada vez que lo ejecutas.
#>

$rutaProyecto = Split-Path -Parent $MyInvocation.MyCommand.Path

# 1) Arrancar base de datos y servidor web
& "$rutaProyecto\iniciar.ps1" -SinPausa

# 2) Buscar cloudflared
$rutaCloudflared = @(
    "C:\Program Files (x86)\cloudflared\cloudflared.exe",
    "C:\Program Files\cloudflared\cloudflared.exe",
    "$env:LOCALAPPDATA\Microsoft\WinGet\Links\cloudflared.exe"
) | Where-Object { Test-Path $_ } | Select-Object -First 1

if (-not $rutaCloudflared) {
    Write-Host "[ERROR] No se encontro cloudflared (para el enlace internet)." -ForegroundColor Red
    Read-Host "Pulsa Enter para salir"
    exit 1
}

Write-Host ""
Write-Host "[...] Creando enlace publico de internet..." -ForegroundColor Cyan
Write-Host "    (deja esta ventana ABierta; al cerrarla se apaga el enlace)" -ForegroundColor DarkGray
Write-Host ""

# Ejecutar el tunel. Cloudflare imprime el enlace en la consola.
& $rutaCloudflared tunnel --url http://127.0.0.1:8000 --no-autoupdate
