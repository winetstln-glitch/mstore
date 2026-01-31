$lines = Get-Content tmp.b64
$b64 = ""
foreach ($line in $lines) {
    if ($line -notmatch "-----") {
        $b64 += $line
    }
}
$svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><image href='data:image/png;base64,$b64' x='0' y='0' width='512' height='512' /></svg>"
Set-Content -Path public/favicon.svg -Value $svg
