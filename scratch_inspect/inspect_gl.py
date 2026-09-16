import win32com.client

conn = win32com.client.Dispatch('ADODB.Connection')
accdb = r'd:\Project Laravel\h-rims\docs\ลืออำนาจ\GL2569 รพ.ลืออำนาจ.accdb'
connStr = f'Provider=Microsoft.ACE.OLEDB.12.0;Data Source={accdb};Persist Security Info=False;'
conn.Open(connStr)
print('Connected via win32com!')

rs = conn.Execute('SELECT COUNT(*) as c FROM DataTbl')
print('DataTbl count:', rs.Fields('c').Value)
rs1 = conn.Execute('SELECT COUNT(*) as c FROM DataTbl1')
print('DataTbl1 count:', rs1.Fields('c').Value)

# Sub accounts
rsSub = conn.Execute("SELECT DISTINCT AccCode1 FROM DataTbl1 WHERE AccCode1 LIKE '%21601%' OR AccCode1 LIKE '%40101%' OR AccCode1 LIKE '%80101%' OR AccCode1 LIKE '%30901%'")
sub_list = []
while not rsSub.EOF:
    sub_list.append(str(rsSub.Fields('AccCode1').Value))
    rsSub.MoveNext()
print('Sub accounts found in DataTbl1:', sub_list)

# Dates
rsDates = conn.Execute('SELECT MIN(AccDate) as minDate, MAX(AccDate) as maxDate FROM DataTbl')
print('AccDate range:', rsDates.Fields('minDate').Value, 'to', rsDates.Fields('maxDate').Value)

# Check Opening Balances in DataTbl
rsOB = conn.Execute("SELECT d.ID, d.AccDate, d.DocID, d.Note, d.ApAr, d1.AccCode1, d1.Dr, d1.Cr FROM DataTbl d INNER JOIN DataTbl1 d1 ON d.ID = d1.ID WHERE d.Note LIKE '%ยอดยกมา%' OR d.DocID LIKE '%ยกมา%'")
ob_list = []
while not rsOB.EOF:
    ob_list.append((rsOB.Fields('ID').Value, str(rsOB.Fields('AccDate').Value), rsOB.Fields('DocID').Value, rsOB.Fields('Note').Value, rsOB.Fields('AccCode1').Value, rsOB.Fields('Dr').Value, rsOB.Fields('Cr').Value))
    rsOB.MoveNext()
print('Opening balance items count:', len(ob_list))
for item in ob_list[:20]:
    print('  ', item)

conn.Close()
