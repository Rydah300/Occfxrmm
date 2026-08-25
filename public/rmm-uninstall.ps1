# ============================================================
# RMM UNINSTALLER — Cipher Anon v3.0
# ============================================================

$ErrorActionPreference = "SilentlyContinue"

$RMM_CLIENT_DIR = "$env:PROGRAMDATA\CipherAnonRMM"
$TASK_NAME = "CipherAnonRMM"
$UNINSTALL_LOG = "$env:TEMP\rmm_uninstall_log.txt"

function Write-UninstallLog {
    param($Msg, $Color = "White")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$timestamp] $Msg"
    Add-Content -Path $UNINSTALL_LOG -Value $line
    if ($Color -eq "Green") { Write-Host "✅ $Msg" -ForegroundColor Green }
    elseif ($Color -eq "Red") { Write-Host "❌ $Msg" -ForegroundColor Red }
    elseif ($Color -eq "Yellow") { Write-Host "⚠️ $Msg" -ForegroundColor Yellow }
    else { Write-Host "ℹ️ $Msg" -ForegroundColor Gray }
}

Write-UninstallLog "============================================" -Color "White"
Write-UninstallLog "CIPHER ANON RMM UNINSTALLER v3.0" -Color "White"
Write-UninstallLog "============================================" -Color "White"

# 1. STOP PROCESSES
Write-UninstallLog "Stopping RMM processes..." -Color "Yellow"
$stopped = 0
try {
    $processes = Get-Process -Name "powershell" -ErrorAction SilentlyContinue | Where-Object {
        $_.CommandLine -like "*CipherAnonRMM*"
    }
    foreach ($p in $processes) {
        try { $p.Kill(); $stopped++ } catch {}
    }
} catch {}
if ($stopped -gt 0) { Write-UninstallLog "Stopped $stopped process(es)" -Color "Green" }
else { Write-UninstallLog "No RMM processes running" -Color "Gray" }
Start-Sleep -Seconds 2

# 2. REMOVE SCHEDULED TASK
Write-UninstallLog "Removing scheduled task: $TASK_NAME" -Color "Yellow"
try {
    $task = Get-ScheduledTask -TaskName $TASK_NAME -ErrorAction SilentlyContinue
    if ($task) {
        Unregister-ScheduledTask -TaskName $TASK_NAME -Confirm:$false
        Write-UninstallLog "Scheduled task removed" -Color "Green"
    } else {
        Write-UninstallLog "Scheduled task not found" -Color "Gray"
    }
} catch { Write-UninstallLog "Failed to remove scheduled task" -Color "Red" }

# 3. DELETE CLIENT DIRECTORY
Write-UninstallLog "Deleting RMM client directory..." -Color "Yellow"
if (Test-Path $RMM_CLIENT_DIR) {
    try {
        Remove-Item $RMM_CLIENT_DIR -Recurse -Force
        Write-UninstallLog "RMM client directory deleted" -Color "Green"
    } catch {
        Write-UninstallLog "Failed to delete directory" -Color "Red"
    }
} else {
    Write-UninstallLog "RMM client directory not found" -Color "Gray"
}

# 4. CLEANUP REGISTRY
Write-UninstallLog "Cleaning up registry..." -Color "Yellow"
$registryPaths = @("HKCU:\Software\CipherAnonRMM", "HKLM:\Software\CipherAnonRMM")
foreach ($regPath in $registryPaths) {
    try {
        if (Test-Path $regPath) {
            Remove-Item -Path $regPath -Recurse -Force
            Write-UninstallLog "Removed registry key: $regPath" -Color "Green"
        }
    } catch {}
}

# 5. CLEANUP TEMP FILES
Write-UninstallLog "Cleaning up temp files..." -Color "Yellow"
try {
    $tempFiles = Get-ChildItem -Path "$env:TEMP" -Filter "*CipherAnon*" -ErrorAction SilentlyContinue
    foreach ($f in $tempFiles) {
        try { Remove-Item $f.FullName -Force } catch {}
    }
} catch {}

# 6. SUMMARY
Write-UninstallLog ""
Write-UninstallLog "============================================" -Color "White"
Write-UninstallLog "✅ RMM AGENT COMPLETELY REMOVED" -Color "Green"
Write-UninstallLog "============================================" -Color "White"

exit 0
