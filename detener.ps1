<#
.SYNOPSIS
    Apaga el Sistema de Ventas PROVCAL (servidor web, base de datos y tunel).

.DESCRIPTION
    Detiene los tres procesos que mantiene el sistema:
      - El tunel de internet (cloudflared)
      - El servidor web PHP (puerto 8000)
      - La base de datos MariaDB (puerto 3306)

    Tus datos NO se borran: siguen guardados en la carpeta mariadb-data.
#>

Write-Host ""
Write-Host "==============================================" -ForegroundColor Yellow
Write-Host "   APAGANDO SISTEMA DE VENTAS PROVCAL" -ForegroundColor Yellow
Write-Host "==============================================" -ForegroundColor Yellow
Write-Host ""

$puertos = @{
    "Servidor web (8000)" = 8000
    "Base de datos (3306)" = 3306
}

foreach ($nombre in $puertos.Keys) {
    $puerto = $puertos[$nombre]
    $conexiones = Get-NetTCPConnection -LocalPort $puerto -State Listen -ErrorAction SilentlyContinue

    if ($conexiones) {
        $procesos = $conexiones.OwningProcess | Select-Object -Unique
        foreach ($procId in $procesos) {
            try {
                $p = Get-Process -Id $procId -ErrorAction Stop
                Stop-Process -Id $procId -Force -ErrorAction SilentlyContinue
                Write-Host "[OK] Detenido: $($p.ProcessName) ($nombre)" -ForegroundColor Green
            } catch {
                Write-Host "[--] No se pudo detener el proceso $procId ($nombre)" -ForegroundColor DarkGray
            }
        }
    } else {
        Write-Host "[--] $nombre no estaba corriendo" -ForegroundColor DarkGray
    }
}

# Tunel de internet (cloudflared)
$tuneles = Get-Process -Name "cloudflared" -ErrorAction SilentlyContinue
if ($tuneles) {
    $tuneles | Stop-Process -Force -ErrorAction SilentlyContinue
    Write-Host "[OK] Detenido: tunel de internet (cloudflared)" -ForegroundColor Green
} else {
    Write-Host "[--] Tunel de internet no estaba corriendo" -ForegroundColor DarkGray
}

Write-Host ""
Write-Host "Sistema apagado. Tus datos siguen guardados." -ForegroundColor Cyan
Write-Host "Para volver a encenderlo:  Iniciar sistema.bat" -ForegroundColor DarkGray
Write-Host ""
Read-Host "Pulsa Enter para cerrar esta ventana"
