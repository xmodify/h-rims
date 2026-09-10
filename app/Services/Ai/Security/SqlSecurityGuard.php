<?php

namespace App\Services\Ai\Security;

use Illuminate\Support\Facades\Log;

class SqlSecurityGuard
{
    /**
     * Default and maximum row limit allowed for AI generated queries
     */
    public const DEFAULT_LIMIT = 100;
    public const MAX_LIMIT = 200;

    /**
     * Forbidden SQL Keywords (Mutating, Administrative, or Dangerous statements)
     */
    protected const FORBIDDEN_KEYWORDS = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE', 'TRUNCATE',
        'RENAME', 'REPLACE', 'GRANT', 'REVOKE', 'EXECUTE', 'LOCK', 'UNLOCK',
        'CALL', 'DO', 'HANDLER', 'LOAD', 'FLUSH', 'RESET', 'SHUTDOWN', 'KILL',
        'SAVEPOINT', 'ROLLBACK', 'COMMIT', 'START TRANSACTION', 'BEGIN'
    ];

    /**
     * Forbidden SQL Functions / Substrings (File system access, DoS, arbitrary execution)
     */
    protected const FORBIDDEN_CLAUSES = [
        'INTO OUTFILE',
        'INTO DUMPFILE',
        'LOAD_FILE',
        'BENCHMARK(',
        'SLEEP(',
        'GET_LOCK(',
        'RELEASE_LOCK(',
        'IS_FREE_LOCK(',
        'IS_USED_LOCK(',
        'SYS_EXEC(',
        'SYS_EVAL(',
    ];

    /**
     * Validate and sanitize an AI-generated SQL query
     *
     * @param string $rawSql
     * @return array ['is_valid' => bool, 'sanitized_sql' => string, 'error' => string|null]
     */
    public function validateAndSanitize(string $rawSql, string $targetDb = 'auto'): array
    {
        $clean = trim($rawSql);

        // 1. Strip markdown code block fences if AI wrapped it in ```sql ... ```
        $clean = preg_replace('/^```(?:sql)?\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/', '', $clean);
        $clean = trim($clean);

        if (empty($clean)) {
            return [
                'is_valid' => false,
                'sanitized_sql' => '',
                'error' => 'คำสั่ง SQL ว่างเปล่า'
            ];
        }

        // 2. Reject multi-statement queries (e.g. SELECT 1; DROP TABLE ...)
        // Remove trailing semicolon first
        $clean = rtrim($clean, ';');
        if (str_contains($clean, ';')) {
            $this->logWarning("SqlSecurityGuard: Multi-statement query rejected: {$clean}");
            return [
                'is_valid' => false,
                'sanitized_sql' => '',
                'error' => 'ระบบความปลอดภัย: ไม่อนุญาตให้รันหลายคำสั่งต่อกัน (Multi-statement blocked)'
            ];
        }

        // 3. Strip SQL comments to inspect underlying tokens
        $sqlWithoutComments = preg_replace('!/\*.*?\*/!s', '', $clean);
        $sqlWithoutComments = preg_replace('/--[^\n\r]*/', '', $sqlWithoutComments);
        $sqlWithoutComments = preg_replace('/#[^\n\r]*/', '', $sqlWithoutComments);
        $sqlWithoutComments = trim($sqlWithoutComments);

        // 4. Must begin with SELECT or WITH (CTE)
        if (!preg_match('/^(?:SELECT|WITH\s+[a-zA-Z0-9_]+\s+AS\s*\()/i', $sqlWithoutComments)) {
            $this->logWarning("SqlSecurityGuard: Non-SELECT root statement rejected: {$clean}");
            return [
                'is_valid' => false,
                'sanitized_sql' => '',
                'error' => 'ระบบความปลอดภัยเข้มงวด: อนุญาตเฉพาะคำสั่ง SELECT เพื่อเรียกดูข้อมูลเท่านั้น'
            ];
        }

        // 5. Check for Forbidden keywords as distinct words
        foreach (self::FORBIDDEN_KEYWORDS as $kw) {
            $pattern = '/\b' . preg_quote($kw, '/') . '\b/i';
            if (preg_match($pattern, $sqlWithoutComments)) {
                $this->logWarning("SqlSecurityGuard: Forbidden keyword '{$kw}' detected in: {$clean}");
                return [
                    'is_valid' => false,
                    'sanitized_sql' => '',
                    'error' => "ระบบความปลอดภัย: ตรวจพบคำสั่งที่ไม่ได้รับอนุญาต ('{$kw}') ไม่อนุญาตให้แก้ไข ดัดแปลง หรือเข้าถึงสิทธิ์ระดับระบบ"
                ];
            }
        }

        // 6. Check for dangerous clauses / functions
        $upperSql = strtoupper($sqlWithoutComments);
        foreach (self::FORBIDDEN_CLAUSES as $clause) {
            if (str_contains($upperSql, $clause)) {
                $this->logWarning("SqlSecurityGuard: Dangerous clause '{$clause}' detected in: {$clean}");
                return [
                    'is_valid' => false,
                    'sanitized_sql' => '',
                    'error' => "ระบบความปลอดภัย: ตรวจพบฟังก์ชันที่มีความเสี่ยงต่อระบบ ('{$clause}')"
                ];
            }
        }

        // 7. Enforce Table Scope Restrictions based on target database
        $tableCheck = $this->validateTableScope($clean, $targetDb);
        if (!$tableCheck['is_valid']) {
            $this->logWarning("SqlSecurityGuard: Unauthorized table access blocked: {$tableCheck['error']}");
            return [
                'is_valid' => false,
                'sanitized_sql' => '',
                'error' => $tableCheck['error']
            ];
        }

        // 8. Enforce LIMIT guardrail
        $sanitizedSql = $this->enforceLimit($clean);

        return [
            'is_valid' => true,
            'sanitized_sql' => $sanitizedSql,
            'error' => null
        ];
    }

    /**
     * Verify that SQL query only touches permitted tables for the given database target
     */
    public function validateTableScope(string $sql, string $targetDb = 'auto'): array
    {
        if ($targetDb === 'auto') {
            return ['is_valid' => true, 'error' => null];
        }

        // Extract table names following FROM or JOIN
        preg_match_all('/\b(?:FROM|JOIN)\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $matches);
        $tables = array_unique(array_map('strtolower', $matches[1] ?? []));

        if ($targetDb === 'hrims') {
            foreach ($tables as $tbl) {
                // HosFin queries must only touch tables starting with hosfin_
                if (!str_starts_with($tbl, 'hosfin_')) {
                    if ($tbl === 'information_schema' || str_contains($tbl, 'schema')) {
                        return [
                            'is_valid' => false,
                            'error' => "ท่านสามารถพิมพ์คำถามเจาะจงที่ต้องการดูข้อมูลได้เลยครับ เช่น 'ขอยอดเจ้าหนี้การค้าแยกตามบริษัท', 'ยอดหนี้ค้างจ่าย อภ.', หรือ 'ขอยอดงบทดลองเดือนล่าสุด' โดยระบบจะค้นหาจากฐานข้อมูล HosFin ให้ทันทีครับ"
                        ];
                    }
                    return [
                        'is_valid' => false,
                        'error' => "ระบบความปลอดภัย: ขอบเขต HosFin อนุญาตให้สืบค้นเฉพาะข้อมูลระบบการเงิน HosFin เท่านั้น (พบการเรียกดู '{$tbl}')"
                    ];
                }
            }
        } elseif ($targetDb === 'hosxp') {
            $allowedHosxp = ['nondrugitems', 'pttype', 'doctor'];
            foreach ($tables as $tbl) {
                if (!in_array($tbl, $allowedHosxp, true)) {
                    if ($tbl === 'information_schema' || str_contains($tbl, 'schema')) {
                        return [
                            'is_valid' => false,
                            'error' => "ท่านสามารถพิมพ์คำถามเจาะจงที่ต้องการตรวจสอบได้เลยครับ เช่น 'ตรวจรายการค่าบริการที่ยังไม่ผูก ADP', 'ตรวจสิทธิ pttype', หรือ 'รายชื่อแพทย์ที่ไม่มีเลข ว.' โดยระบบจะตรวจสอบจากฐานข้อมูล HOSxP Setting ให้ทันทีครับ"
                        ];
                    }
                    return [
                        'is_valid' => false,
                        'error' => "ระบบความปลอดภัย: ขอบเขต HOSxP Setting อนุญาตให้ตรวจสอบเฉพาะข้อมูลพื้นฐาน nondrugitems, pttype, doctor เท่านั้น (พบการเรียกดู '{$tbl}')"
                    ];
                }
            }
        }

        return ['is_valid' => true, 'error' => null];
    }

    /**
     * Enforce a strict LIMIT on queries to protect DB resources
     */
    protected function enforceLimit(string $sql): string
    {
        // Check if LIMIT exists at the end of the query
        if (preg_match('/\bLIMIT\s+(\d+)(?:\s*,\s*(\d+)|\s+OFFSET\s+(\d+))?\s*$/i', $sql, $matches)) {
            $count = isset($matches[2]) && $matches[2] !== '' ? (int)$matches[2] : (int)$matches[1];
            if ($count > self::MAX_LIMIT) {
                // Cap the limit to max allowed
                $replacement = isset($matches[2]) && $matches[2] !== ''
                    ? "LIMIT {$matches[1]}, " . self::MAX_LIMIT
                    : "LIMIT " . self::MAX_LIMIT;
                $sql = preg_replace('/\bLIMIT\s+\d+(?:\s*,\s*\d+|\s+OFFSET\s+\d+)?\s*$/i', $replacement, $sql);
            }
            return $sql;
        }

        // No LIMIT found, append DEFAULT_LIMIT
        return rtrim($sql) . ' LIMIT ' . self::DEFAULT_LIMIT;
    }

    /**
     * Mask sensitive PDPA data (e.g. Thai National ID 13 digits) in query results
     *
     * @param array $rows
     * @param bool $allowFullData Whether the current user is permitted to see unmasked data
     * @return array
     */
    public function maskSensitiveData(array $rows, bool $allowFullData = false): array
    {
        if (empty($rows) || $allowFullData) {
            return $rows;
        }

        $sensitiveColPatterns = ['cid', 'idcard', 'card_no', 'citizen_id', 'personal_id'];

        return array_map(function ($row) use ($sensitiveColPatterns) {
            $isObj = is_object($row);
            $arr = (array)$row;

            foreach ($arr as $key => $val) {
                if ($val === null || !is_string($val)) {
                    continue;
                }

                $lowerKey = strtolower($key);
                $isSensitiveCol = false;
                foreach ($sensitiveColPatterns as $pattern) {
                    if (str_contains($lowerKey, $pattern)) {
                        $isSensitiveCol = true;
                        break;
                    }
                }

                // Mask 13-digit Thai CID (e.g. 1234567890123 -> 1-xxxx-xxxxx-xx-3)
                if ($isSensitiveCol && preg_match('/^\d{13}$/', trim($val))) {
                    $c = trim($val);
                    $arr[$key] = substr($c, 0, 1) . '-xxxx-xxxxx-xx-' . substr($c, -1);
                } elseif (preg_match('/^\b(\d{1})(\d{4})(\d{5})(\d{2})(\d{1})\b/', $val, $m)) {
                    $arr[$key] = $m[1] . '-xxxx-xxxxx-xx-' . $m[5];
                }
            }

            return $isObj ? (object)$arr : $arr;
        }, $rows);
    }

    protected function logWarning(string $msg): void
    {
        try {
            if (class_exists(Log::class)) {
                Log::warning($msg);
            }
        } catch (\Throwable $e) {
            // Ignored in raw/CLI testing
        }
    }
}
