$php = 'C:\xampp\php\php.exe'
$out = 'C:\xampp\htdocs\Digital_Library\tools\php_lint_results.txt'
if (Test-Path $out) { Remove-Item $out }
$raw = & git -C c:\xampp\htdocs\Digital_Library diff --name-only --diff-filter=ACMRT --cached
$files = $raw -split "\r?\n" | Where-Object { $_ -and $_ -match '\.php$' }
if (-not $files) { Write-Output 'No staged PHP files found'; exit 0 }
foreach ($f in $files) {
    Add-Content $out ('---- ' + $f)
    $full = Join-Path 'C:\xampp\htdocs\Digital_Library' $f
    if (Test-Path $full) {
        & $php -l $full 2>&1 | ForEach-Object { Add-Content $out $_ }
    } else {
        Add-Content $out "MISSING: $full"
    }
}
Write-Output "WROTE: $out"