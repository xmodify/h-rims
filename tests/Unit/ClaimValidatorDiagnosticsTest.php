<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ClaimValidator;

class ClaimValidatorDiagnosticsTest extends TestCase
{
    protected ClaimValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ClaimValidator();
    }

    /**
     * ทดสอบเคส C-240 สำหรับรายการ 15001 เมื่อ HOSxP มีเพียง K081 และ Z298
     */
    public function test_c240_fluoride_reports_missing_dental_risk_diagnosis(): void
    {
        $visit = (object) [
            'hn'             => '500005217',
            'pdx'            => 'K081',
            'sdx'            => 'Z298',
            'icd9'           => '9654,9997',
            'sex'            => '2',
            'age_y'          => 59,
            'cid'            => '3341800288290',
            'rep_error_code' => '240',
            'receive_total'  => 0,
        ];

        $items = [
            (object) [
                'nhso_adp_code' => '15001',
                'name'          => 'ค่าบริการเคลือบฟลูออไรด์ (กลุ่มเสี่ยง)',
                'sum_price'     => 100.0,
                'ppfs'          => 'Y',
            ],
        ];

        $result = $this->validator->validatePpfs($visit, $items);

        $this->assertFalse(empty($result['errors']));
        $this->assertTrue(collect($result['errors'])->contains(function ($err) {
            return str_contains($err, 'สิ่งที่ขาดใน HOSxP')
                && str_contains($err, '240')
                && str_contains($err, 'ขาดรหัสโรคกลุ่มเสี่ยงทางทันตกรรม')
                && str_contains($err, 'K081');
        }));
    }

    /**
     * ทดสอบ Pre-Audit ของรายการ 15001 เมื่อยังไม่มี REP (รอส่ง)
     */
    public function test_pre_audit_fluoride_reports_missing_dental_risk_and_warns_c240(): void
    {
        $visit = (object) [
            'hn'            => '500005217',
            'pdx'           => 'K081',
            'sdx'           => 'Z298',
            'icd9'          => '9654',
            'sex'           => 'F',
            'age_y'         => 59,
            'cid'           => '3341800288290',
            'receive_total' => 0,
        ];

        $items = [
            (object) [
                'nhso_adp_code' => '15001',
                'name'          => 'ค่าบริการเคลือบฟลูออไรด์ (กลุ่มเสี่ยง)',
                'sum_price'     => 100.0,
                'ppfs'          => 'Y',
            ],
        ];

        $result = $this->validator->validatePpfs($visit, $items);

        $this->assertTrue(collect($result['errors'])->contains(function ($err) {
            return str_contains($err, 'สิ่งที่ขาดใน HOSxP')
                && str_contains($err, 'ขาดรหัสโรคกลุ่มเสี่ยงทางทันตกรรม')
                && str_contains($err, 'อาจทำให้ติด C-240');
        }));
    }

    /**
     * ทดสอบเคส C-217 สำหรับรายการ FP002_2 เมื่อขาดรหัส 8605
     */
    public function test_c217_implant_removal_reports_missing_procedure_8605(): void
    {
        $visit = (object) [
            'hn'             => '530068800',
            'pdx'            => 'Z308',
            'sdx'            => '',
            'icd9'           => '',
            'sex'            => 'F',
            'age_y'          => 28,
            'rep_error_code' => '217',
            'receive_total'  => 0,
        ];

        $items = [
            (object) [
                'nhso_adp_code' => 'FP002_2',
                'name'          => 'ค่าถอดยาฝังคุมกำเนิด',
                'sum_price'     => 350.0,
                'ppfs'          => 'Y',
            ],
        ];

        $result = $this->validator->validatePpfs($visit, $items);

        $this->assertTrue(collect($result['errors'])->contains(function ($err) {
            return str_contains($err, 'สิ่งที่ขาดใน HOSxP')
                && str_contains($err, '217')
                && str_contains($err, '8605');
        }));
    }

    /**
     * ทดสอบ Pre-Audit ของ FP002_2 เมื่อยังไม่มี REP (รอส่ง)
     */
    public function test_pre_audit_implant_removal_warns_c217(): void
    {
        $visit = (object) [
            'hn'            => '530068800',
            'pdx'           => 'Z308',
            'sdx'           => '',
            'icd9'          => '',
            'sex'           => 'F',
            'age_y'         => 28,
            'receive_total' => 0,
        ];

        $items = [
            (object) [
                'nhso_adp_code' => 'FP002_2',
                'name'          => 'ค่าถอดยาฝังคุมกำเนิด',
                'sum_price'     => 350.0,
                'ppfs'          => 'Y',
            ],
        ];

        $result = $this->validator->validatePpfs($visit, $items);

        $this->assertTrue(collect($result['errors'])->contains(function ($err) {
            return str_contains($err, 'สิ่งที่ขาดใน HOSxP')
                && str_contains($err, 'ขาดรหัสหัตถการ ICD-9 8605')
                && str_contains($err, 'อาจทำให้ติด C-217');
        }));
    }

    /**
     * ทดสอบเคสที่มีรหัสกลุ่มเสี่ยงครบถ้วนและได้รับชดเชยแล้ว จะต้องไม่เกิด error
     */
    public function test_valid_case_with_risk_diagnosis_and_compensation_passes(): void
    {
        $visit = (object) [
            'hn'            => '500005217',
            'pdx'           => 'K020', // ฟันผุ (กลุ่มเสี่ยง)
            'sdx'           => 'Z298',
            'icd9'          => '9654',
            'sex'           => 'F',
            'age_y'         => 35,
            'receive_total' => 100.0,
            'rep_error_code'=> '',
        ];

        $items = [
            (object) [
                'nhso_adp_code' => '15001',
                'name'          => 'ค่าบริการเคลือบฟลูออไรด์ (กลุ่มเสี่ยง)',
                'sum_price'     => 100.0,
                'ppfs'          => 'Y',
            ],
        ];

        $result = $this->validator->validatePpfs($visit, $items);

        $this->assertEmpty($result['errors']);
    }
}
