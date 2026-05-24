Set-Location 'C:\xampp\htdocs\Digital_Library'
$files = (& git ls-files -z) -split "`0" | Where-Object { $_ -ne '' -and $_ -match '\.(php|html?|css|js|json|md|txt|sql|xml|ya?ml)$' }
$count = 0
foreach ($f in $files) {
    if (-not (Test-Path $f)) { Write-Output ("Skipping missing: $f"); continue }
    try { $raw = Get-Content -Raw -Encoding UTF8 -ErrorAction Stop $f } catch { $raw = Get-Content -Raw -Encoding Default $f }
    # Normalize to LF for processing
    $raw = $raw -replace "\r\n?","\n"
    # Remove leading blank lines
    $raw = $raw -replace "^\n+", ''
    # Remove trailing blank lines (leave single newline)
    $raw = $raw -replace "\n+$", "\n"
    $lines = $raw -split "\n"
    # Trim trailing whitespace from each line
    $lines = $lines | ForEach-Object { $_ -replace '[ \t]+$','' }
    $out = ($lines -join "`r`n") + "`r`n"
    Set-Content -Encoding UTF8 -Value $out -Path $f
    $count++
}
Write-Output "Processed $count files"
