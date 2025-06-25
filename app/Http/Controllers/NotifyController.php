<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotifyController extends Controller
{
    // CKD รายงานสถานะการณ์สรุปผู้ป่วย CKD  เวรเช้า รัน 16.00 น.
    public function notify_summary()
    {
        $service = DB::connection('hosxp')->select('
                SELECT IFNULL(COUNT(DISTINCT o1.vn),0) AS patient_all 
                FROM opd_dep_queue o1, ovst o2 WHERE o1.depcode IN ("031") AND o1.vn = o2.vn  
                AND o2.vstdate = DATE(NOW()) AND o2.vsttime BETWEEN "00:00:00" AND "15:59:59"');         

        foreach ($service as $row){
                $patient_all=$row->patient_all;
                $url=route('nurse_productivity_ckd_morning');
        }    

    //แจ้งเตือน Telegram

        $message = "ผู้ป่วย CKD" ."\n"
        ."วันที่ ". DateThai(date("Y-m-d")) ."\n"
        ."ณ เวลา 16.00 น.(เวรเช้า)" ."\n"
        ."ผู้ป่วยในเวร " .$patient_all ." ราย" ."\n" 
        ."บันทึก Productivity " ."\n"
        .$url ."\n";
    
        $token = "7878226178:AAGNIxtdhgi2C607l0lsKmgVXshgzmUp-p0"; //HTP_Notify_Bot
        $chat_ids = ["-4729376994"]; //Test_Notify_Group2
    
        foreach ($chat_ids as $chat_id) {
                $url = "https://api.telegram.org/bot$token/sendMessage";
    
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => $message
                ];
    
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);
                sleep(1);
        }
    
        return response()->json(['success' => 'success'], 200);  
    }   
}
