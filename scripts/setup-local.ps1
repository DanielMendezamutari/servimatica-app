$ErrorActionPreference = 'Stop'
$projectRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
if ($projectRoot -ne 'C:\xampp\htdocs\servimatica-app') { throw 'Ruta de proyecto inesperada.' }
$vhostPath = 'C:\xampp\apache\conf\extra\httpd-vhosts.conf'
$hostsPath = 'C:\Windows\System32\drivers\etc\hosts'
$vhostText = [IO.File]::ReadAllText($vhostPath)
if ($vhostText -notmatch 'ServerName\s+servimatica-app\.test') {
    Copy-Item -LiteralPath $vhostPath -Destination ($vhostPath + '.servimatica-backup') -ErrorAction Stop
    $block = @'

# Servimatica App
<VirtualHost *:80>
    ServerName servimatica-app.test
    DocumentRoot "C:/xampp/htdocs/servimatica-app/public"
    <Directory "C:/xampp/htdocs/servimatica-app/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
'@
    [IO.File]::AppendAllText($vhostPath, $block, [Text.Encoding]::ASCII)
}
$hostsText = [IO.File]::ReadAllText($hostsPath)
if ($hostsText -notmatch '(?m)^\s*127\.0\.0\.1\s+.*\bservimatica-app\.test\b') {
    [IO.File]::AppendAllText($hostsPath, "`r`n127.0.0.1 servimatica-app.test`r`n", [Text.Encoding]::ASCII)
}
& 'C:\xampp\apache\bin\httpd.exe' -t
if ($LASTEXITCODE -ne 0) { throw 'Configuración Apache inválida.' }
if (-not (Get-Process mysqld -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath 'C:\xampp\mysql\bin\mysqld.exe' -ArgumentList '--defaults-file=C:/xampp/mysql/bin/my.ini','--standalone' -WorkingDirectory 'C:\xampp' -WindowStyle Hidden
}
if (-not (Get-Process httpd -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath 'C:\xampp\apache\bin\httpd.exe' -WorkingDirectory 'C:\xampp\apache' -WindowStyle Hidden
} else {
    Write-Output 'Apache ya está activo: recargue su configuración desde XAMPP si se agregó el host.'
}
Write-Output 'Configuración local preparada para http://servimatica-app.test.'
