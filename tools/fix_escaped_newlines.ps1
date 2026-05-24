Set-Location 'C:\xampp\htdocs\Digital_Library'
$raw = & git -C . diff --name-only --diff-filter=ACMRT --cached
$files = $raw -split "\r?\n" | Where-Object { $_ -and $_ -match '\.(php|html?|css|js|json|md|txt|sql|xml|ya?ml)$' }
$fixed = 0
foreach ($f in $files) {
    $path = Join-Path (Get-Location) $f
    if (-not (Test-Path $path)) { Write-Output "Skipping missing: $f"; continue }
    $text = Get-Content -Raw -Encoding UTF8 $path
    # Remove BOM
    if ($text.Length -gt 0 -and $text[0] -eq [char]0xFEFF) { $text = $text.Substring(1) }
    if ($text -match '\\\r\\n' -or $text -match '\\n' -or $text -match '\\r') {
        # Replace literal escaped CRLF first
        $text = $text -replace '\\\r\\n', "`r`n"
        # Then replace literal escaped newline and carriage returns
        $text = $text -replace '\\n', "`r`n"
        $text = $text -replace '\\r', "`r`n"
        # Normalize multiple consecutive newlines
        $text = [System.Text.RegularExpressions.Regex]::Replace($text, '(\r\n)+', "`r`n")
        [System.IO.File]::WriteAllText($path, $text, [System.Text.Encoding]::UTF8)
        Write-Output "Fixed: $f"
        $fixed++
    }
}
Write-Output "Total fixed files: $fixed"