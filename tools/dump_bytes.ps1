param([string]$path)
$bytes = [System.IO.File]::ReadAllBytes($path)
$len = [Math]::Min($bytes.Length,64)
for($i=0;$i -lt $len; $i++){
    Write-Host ($bytes[$i].ToString('X2')) -NoNewline; Write-Host ' '
}
Write-Host "`nTotal bytes: $($bytes.Length)"