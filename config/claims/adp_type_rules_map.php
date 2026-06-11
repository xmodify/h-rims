<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/*
 * Mapping: nhso_adp_type_id => config/claims filename (without .php)
 * ใช้สำหรับโหลด rules file ตาม ADP Type
 */
// (This file is just a helper reference - actual logic is in CheckController.php)

return [
    2  => 'ins_rules',
    3  => 'other_service_rules',
    4  => 'pp_special_rules',
    5  => 'project_code_rules',
    8  => 'op_refer_rules',
    9  => 'special_diag_rules',
    10 => 'room_board_rules',
    11 => 'medical_supply_rules',
    12 => 'dental_rules',
    13 => 'acupuncture_rules',
    14 => 'blood_rules',
    15 => 'lab_rules',
    16 => 'xray_rules',
    17 => 'nursing_rules',
    18 => 'medical_device_rules',
    19 => 'procedure_rules',
    20 => 'physical_therapy_rules',
];
