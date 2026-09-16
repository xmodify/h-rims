$accdb = "d:\Project Laravel\h-rims\docs\ลืออำนาจ\GL2569 รพ.ลืออำนาจ.accdb"
$connStr = "Provider=Microsoft.ACE.OLEDB.12.0;Data Source=$accdb;Persist Security Info=False;"
$conn = New-Object -ComObject ADODB.Connection
$conn.Open($connStr)
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

Write-Host "Connected successfully to GL2569 accdb!"

$rs = $conn.Execute("SELECT COUNT(*) as c FROM DataTbl")
Write-Host ("DataTbl count: " + $rs.Fields.Item("c").Value)

$rs1 = $conn.Execute("SELECT COUNT(*) as c FROM DataTbl1")
Write-Host ("DataTbl1 count: " + $rs1.Fields.Item("c").Value)

# Sub accounts
$sql = "SELECT DISTINCT AccCode1 FROM DataTbl1 WHERE AccCode1 LIKE '%21601%' OR AccCode1 LIKE '%40101%' OR AccCode1 LIKE '%80101%' OR AccCode1 LIKE '%30901%'"
$rsSub = $conn.Execute($sql)
Write-Host "`nSub-accounts in DataTbl1:"
while (-not $rsSub.EOF) {
    Write-Host (" - " + $rsSub.Fields.Item("AccCode1").Value)
    $rsSub.MoveNext()
}

# Date range
$sqlDates = "SELECT MIN(AccDate) as minDate, MAX(AccDate) as maxDate FROM DataTbl"
$rsDates = $conn.Execute($sqlDates)
Write-Host ("`nDate range: " + $rsDates.Fields.Item("minDate").Value + " to " + $rsDates.Fields.Item("maxDate").Value)

# Check all Vouchers around Sep 2025 / Oct 2025
$sqlV = "SELECT TOP 30 d.ID, d.AccDate, d.DocID, d.Note, d.ApAr, d1.AccCode1, d1.Dr, d1.Cr FROM DataTbl d INNER JOIN DataTbl1 d1 ON d.ID = d1.ID WHERE d1.AccCode1 LIKE '1102%' ORDER BY d.AccDate ASC"
$rsV = $conn.Execute($sqlV)
Write-Host "`nFirst 30 Debtor journal entries in GL2569:"
while (-not $rsV.EOF) {
    $dt = $rsV.Fields.Item("AccDate").Value
    $doc = $rsV.Fields.Item("DocID").Value
    $acc = $rsV.Fields.Item("AccCode1").Value
    $dr = $rsV.Fields.Item("Dr").Value
    $cr = $rsV.Fields.Item("Cr").Value
    $note = $rsV.Fields.Item("Note").Value
    Write-Host ("Date: " + $dt + " | Doc: " + $doc + " | " + $acc + " | Dr: " + $dr + " | Cr: " + $cr + " | " + $note)
    $rsV.MoveNext()
}

$conn.Close()
