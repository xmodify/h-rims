<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Stm_sss_kidney;

$zipPath = 'd:/Project Laravel/h-rims/docs/10703_SOCDSTM_20260501.ZIP';
if (!file_exists($zipPath)) {
    die("ZIP file not found at $zipPath\n");
}

$zip = new \ZipArchive;
if ($zip->open($zipPath) !== true) {
    die("Failed to open ZIP archive\n");
}

DB::beginTransaction();
try {
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $innerName = $stat['name'];

        if (strtolower(pathinfo($innerName, PATHINFO_EXTENSION)) !== 'xml') {
            continue;
        }

        $xmlString = $zip->getFromIndex($i);
        if (!$xmlString) {
            continue;
        }

        $xmlObject = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xmlObject === false) {
            continue;
        }

        $json = json_encode($xmlObject);
        $result = json_decode($json, true);

        $hcode = $result['hcode'] ?? null;
        $hname = $result['hname'] ?? null;
        $STMdoc = $result['STMdoc'] ?? $innerName;

        if (strpos(strtoupper($STMdoc), 'STM') === false) {
            continue;
        }

        echo "Importing STM doc: $STMdoc ($innerName)\n";
        
        // Delete existing records for this file to prevent duplicates
        Stm_sss_kidney::where('stm_filename', $innerName)->delete();

        $HDBills = $result['HDBills']['HDBill'] ?? [];
        if (!empty($HDBills) && array_keys($HDBills) !== range(0, count($HDBills) - 1)) {
            $HDBills = [$HDBills];
        }

        $insertedCount = 0;

        foreach ($HDBills as $bill) {
            $name = $bill['name'] ?? null;
            $cid = $bill['pid'] ?? null;
            $wkno = $bill['wkno'] ?? null;

            $TBills = $bill['TBill'] ?? [];
            if (!empty($TBills) && array_keys($TBills) !== range(0, count($TBills) - 1)) {
                $TBills = [$TBills];
            }

            foreach ($TBills as $row) {
                $hreg = $row['hreg'] ?? null;
                $station = $row['station'] ?? null;
                $invno = $row['invno'] ?? null;
                $hn = $row['hn'] ?? null;
                $amount = $row['amount'] ?? null;
                $paid = $row['paid'] ?? null;
                $rid = $row['rid'] ?? null;
                $HDflag = $row['HDflag'] ?? ($row['hdflag'] ?? null);
                $dttran = $row['dttran'] ?? null;

                $dttdate = null;
                $dtttime = null;
                if ($dttran && strpos($dttran, 'T') !== false) {
                    list($dttdate, $dtttime) = explode('T', $dttran, 2);
                }

                $epopay = $row['EPOs']['EPOpay'] ?? 0;
                $epoadm = $row['EPOs']['EPOadm'] ?? 0;

                if ($cid && $dttdate) {
                    $dataRow = [
                        'stm_filename' => $innerName,
                        'round_no' => $STMdoc,
                        'hcode' => $hcode,
                        'hname' => $hname,
                        'station' => $station,
                        'hreg' => $hreg,
                        'hn' => $hn,
                        'pt_name' => $name,
                        'cid' => $cid,
                        'invno' => $invno,
                        'dttran' => $dttran,
                        'vstdate' => $dttdate,
                        'vsttime' => $dtttime,
                        'amount' => $amount,
                        'epopay' => $epopay,
                        'epoadm' => $epoadm,
                        'paid' => $paid,
                        'rid' => $rid,
                        'hdflag' => $HDflag,
                    ];

                    Stm_sss_kidney::insert($dataRow);
                    $insertedCount++;
                }
            }
        }
        echo "Successfully inserted $insertedCount records from $innerName\n";
    }
    DB::commit();
} catch (\Throwable $e) {
    DB::rollBack();
    echo "Error during import: " . $e->getMessage() . "\n";
}
$zip->close();
