<?php
use Illuminate\Support\Facades\DB;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$start_date = '2026-02-16';
$end_date = '2026-02-16';
$pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');

echo "Setting raw: " . $pttype_sss_fund . "\n";
echo "Setting json: " . json_encode($pttype_sss_fund) . "\n";

$query = "
             SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, o.vstdate, o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(inc.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(ch.other_price,0) AS other, IFNULL(ch.ppfs_price,0)  AS ppfs,
                IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(ch.other_price,0)-IFNULL(ch.ppfs_price,0) AS debtor,
                ch.other_list,ch.ppfs_list,\"ยืนยันลูกหนี้\" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (SELECT op.vn,op.pttype,SUM(op.sum_price) AS income
                FROM opitemrece op
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype) inc ON inc.vn = o.vn AND inc.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM(r.bill_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno   
                FROM rcpt_print r
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno) 
                GROUP BY r.vn) rc ON rc.vn = o.vn
            LEFT JOIN (SELECT op.vn,SUM(CASE WHEN li.ems = \"Y\" OR li.kidney = \"Y\" THEN op.sum_price ELSE 0 END) AS other_price,
                SUM(CASE WHEN li.ppfs = \"Y\" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                GROUP_CONCAT(DISTINCT CASE WHEN li.ems = \"Y\" OR li.kidney = \"Y\" THEN sd.`name` END) AS other_list,
                GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = \"Y\" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn) ch ON ch.vn = o.vn
            WHERE (o.an IS NULL OR o.an = \"\")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(ch.other_price,0)) > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_307 WHERE vn IS NOT NULL)
            AND p.pttype IN ($pttype_sss_fund)
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue
";

try {
    $debtor_search = DB::connection('hosxp')->select($query, [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
    echo "Query Result Count: " . count($debtor_search) . "\n";
    if (count($debtor_search) > 0) {
        echo "First Row Logic Check:\n";
        print_r($debtor_search[0]);
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
