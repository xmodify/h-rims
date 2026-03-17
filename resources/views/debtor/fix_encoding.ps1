
$content = [IO.File]::ReadAllText('d:\Projec Laravel\h-rims\resources\views\debtor\1102050101_308.blade.php')
$bytes = [Text.Encoding]::GetEncoding('Windows-1252').GetBytes($content)
$fixed = [Text.Encoding]::GetEncoding('windows-874').GetString($bytes)
[IO.File]::WriteAllText('d:\Projec Laravel\h-rims\resources\views\debtor\1102050101_308.blade.php', $fixed, [Text.Encoding]::UTF8)
