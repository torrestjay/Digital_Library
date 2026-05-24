Set-Location 'C:\xampp\htdocs\Digital_Library'
$files = (& git ls-files -z) -split "`0" | Where-Object { $_ -ne '' -and $_ -match '\.(php|html?|css|js|json|md|txt|sql|xml|ya?ml)$' }
$count = 0
foreach ($f in $files) {
    if (-not (Test-Path $f)) { Write-Output ("Skipping missing: $f"); continue }
    try {
        $content = [System.IO.File]::ReadAllText($f)
    } catch {
        $content = Get-Content -Raw -Encoding UTF8 $f
    }
    # Remove trailing spaces/tabs before newlines
    $content = [System.Text.RegularExpressions.Regex]::Replace($content, '[ \t]+(?=\r?\n)', '')
    # Normalize line endings to CRLF
    $content = $content -replace '\r\n?|\n','\r\n'
    # Remove leading blank lines
    $content = $content -replace '^(\r\n)+',''
    # Ensure single newline at EOF
    $content = [System.Text.RegularExpressions.Regex]::Replace($content, '(\r\n)+$','\r\n')
    [System.IO.File]::WriteAllText($f, $content, [System.Text.Encoding]::UTF8)
    $count++
}
Write-Output "Stripped trailing whitespace from $count files"