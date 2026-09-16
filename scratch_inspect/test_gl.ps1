$accdb = "d:\Project Laravel\h-rims\docs\ลืออำนาจ\GL2569 รพ.ลืออำนาจ.accdb"
$connStr = "Provider=Microsoft.ACE.OLEDB.12.0;Data Source=$accdb;Persist Security Info=False;"
$conn = New-Object -ComObject ADODB.Connection
$conn.Open($connStr)
Write-Host "Connected successfully to GL2569 accdb!"

$rs = $conn.Execute("SELECT COUNT(*) as c FROM DataTbl")
Write-Host ("DataTbl count: " + $rs.Fields.Item("c").Value)

$rs1 = $conn.Execute("SELECT COUNT(*) as c FROM DataTbl1")
Write-Host ("DataTbl1 count: " + $rs1.Fields.Item("c").Value)

# Check sub-accounts in DataTbl1
$sql = "SELECT DISTINCT AccCode1 FROM DataTbl1 WHERE AccCode1 LIKE '%21601%' OR AccCode1 LIKE '%40101%' OR AccCode1 LIKE '%80101%' OR AccCode1 LIKE '%30901%'"
$rsSub = $conn.Execute($sql)
Write-Host "`nSub-accounts in DataTbl1:"
while (!$rsSub.EOF) {
    Write-Host (" - " + $rsSub.Fields.Item("AccCode1").Value)
    $rsSub.MoveNext()
}

# Check Dates and Opening balance vouchers in DataTbl
$sqlOB = "SELECT d.ID, d.AccDate, d.DocID, d.Note FROM DataTbl d WHERE d.Note LIKE '%ยอดยกมา%' OR d.DocID LIKE '%ยกมา%'"
$rsOB = $conn.Execute($sqlOB)
Write-Host "`nOpening balance vouchers in DataTbl:"
while (!$rsOB.EOF) {
    $d = $rsOB.Fields.Item("AccDate").Value
    $doc = $rsOB.Fields.Item("DocID").Value
    $note = $rsOB.Fields.Item("Note").Value
    Write-Host ("ID: " + $rsOB.Fields.Item("ID").Value + " | Date: " + $d + " | Doc: " + $doc + " | Note: " + $note)
    $rsOB.MoveNext()
}

# Check Date range in DataTbl
$sqlDates = "SELECT MIN(AccDate) as minDate, MAX(AccDate) as maxDate FROM DataTbl"
$rsDates = $conn.Execute($sqlDates)
Write-Host ("`nDate range in DataTbl: " + $rsDates.Fields.Item("minDate").Value + " to " + $rsDates.Fields.Item("maxDate").Value)

$conn.Close()
