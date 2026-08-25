<?php

namespace App\Helpers;

class PythonHelper
{
    /**
     * Cached python command/path for the current request
     * @var string|null|false
     */
    protected static $cachedExecutable = false;

    /**
     * Find a working Python 3 executable command/path on the system.
     *
     * @return string|null The executable command/path or null if not found
     */
    public static function findExecutable(): ?string
    {
        if (static::$cachedExecutable !== false) {
            return static::$cachedExecutable;
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            // 0. Project Embedded / Portable Python (Highest priority for zero-setup on Windows)
            $embeddedPaths = [
                __DIR__ . '/Python/bin/windows/python.exe',
                __DIR__ . '/Python/embedded/python.exe',
                __DIR__ . '/Python/bin/python.exe'
            ];
            foreach ($embeddedPaths as $emb) {
                if (file_exists($emb)) {
                    $candidates[] = str_replace('/', '\\', $emb);
                }
            }

            // 1. Common command names in PATH
            $candidates[] = 'python';
            $candidates[] = 'python3';
            $candidates[] = 'py -3';
            $candidates[] = 'py';

            // 2. Windows Drive Roots & Standard Installation Folders
            $drives = ['C:', 'D:', 'E:', 'F:'];
            $pyVersions = ['314', '313', '312', '311', '310', '39', '38', '37', '3'];

            foreach ($drives as $drive) {
                // e.g. C:\xampp\python\python.exe
                $candidates[] = "{$drive}\\xampp\\python\\python.exe";

                foreach ($pyVersions as $v) {
                    $candidates[] = "{$drive}\\Python{$v}\\python.exe";
                    $candidates[] = "{$drive}\\Program Files\\Python{$v}\\python.exe";
                    $candidates[] = "{$drive}\\Program Files (x86)\\Python{$v}\\python.exe";
                }
            }

            // 3. User AppData / LocalAppData
            $localAppData = getenv('LOCALAPPDATA');
            if ($localAppData) {
                foreach ($pyVersions as $v) {
                    $candidates[] = "{$localAppData}\\Programs\\Python\\Python{$v}\\python.exe";
                }
            }

            $userProfile = getenv('USERPROFILE');
            if ($userProfile) {
                foreach ($pyVersions as $v) {
                    $candidates[] = "{$userProfile}\\AppData\\Local\\Programs\\Python\\Python{$v}\\python.exe";
                }
                $candidates[] = "{$userProfile}\\miniconda3\\python.exe";
                $candidates[] = "{$userProfile}\\anaconda3\\python.exe";
                $candidates[] = "{$userProfile}\\.pyenv\\pyenv-win\\shims\\python.exe";
                $candidates[] = "{$userProfile}\\scoop\\shims\\python.exe";
            }

            // 4. Glob search in C:\Users\*\AppData\Local\Programs\Python\Python*\python.exe
            $userGlob = @glob('C:/Users/*/AppData/Local/Programs/Python/Python*/python.exe');
            if (is_array($userGlob)) {
                foreach ($userGlob as $foundPy) {
                    $candidates[] = str_replace('/', '\\', $foundPy);
                }
            }

            // 5. Chocolatey / Conda system-wide
            $candidates[] = 'C:\\ProgramData\\chocolatey\\bin\\python.exe';
            $candidates[] = 'C:\\ProgramData\\miniconda3\\python.exe';
            $candidates[] = 'C:\\ProgramData\\anaconda3\\python.exe';
        } else {
            // Linux / macOS standard paths
            $candidates[] = '/usr/bin/python3';
            $candidates[] = '/usr/local/bin/python3';
            $candidates[] = '/usr/bin/python';
            $candidates[] = '/opt/homebrew/bin/python3';
            $candidates[] = '/opt/conda/bin/python3';
        }

        // Test each candidate
        foreach (array_unique($candidates) as $cmd) {
            if (empty($cmd)) {
                continue;
            }

            // If it looks like a direct file path, verify file existence first
            if (strpos($cmd, '\\') !== false || strpos($cmd, '/') !== false) {
                $cleanedPath = trim($cmd, '"\'');
                if (!file_exists($cleanedPath)) {
                    continue;
                }
            }

            // Format command for execution
            $formattedCmd = (strpos($cmd, ' ') !== false && strpos($cmd, '"') === false) ? "\"{$cmd}\"" : $cmd;
            
            $testCmd = "{$formattedCmd} -c \"import sys; print(sys.version_info[0])\" 2>&1";
            $output = [];
            $returnVar = 1;
            @exec($testCmd, $output, $returnVar);

            if ($returnVar === 0 && !empty($output)) {
                $firstLine = trim($output[0]);
                if ($firstLine === '3' || strpos($firstLine, '3') === 0) {
                    static::$cachedExecutable = $formattedCmd;
                    return $formattedCmd;
                }
            }
        }

        static::$cachedExecutable = null;
        return null;
    }

    /**
     * Get Python version string from executable.
     */
    public static function getVersion(?string $executable = null): ?string
    {
        $exe = $executable ?? static::findExecutable();
        if (!$exe) {
            return null;
        }

        $output = [];
        $returnVar = 1;
        @exec("{$exe} --version 2>&1", $output, $returnVar);
        if ($returnVar === 0 && !empty($output)) {
            return trim(implode(' ', $output));
        }

        return null;
    }

    /**
     * Get path to bundled python libraries.
     */
    public static function getLibsPath(): string
    {
        if (function_exists('base_path')) {
            try {
                return str_replace('\\', '/', base_path('app/Helpers/Python/libs'));
            } catch (\Throwable $e) {
                // Ignore container not booted
            }
        }
        return str_replace('\\', '/', __DIR__ . '/Python/libs');
    }

    /**
     * Check if access_parser module is available (bundled or pip-installed).
     */
    public static function checkAccessParser(?string $executable = null): bool
    {
        $exe = $executable ?? static::findExecutable();
        if (!$exe) {
            return false;
        }

        $libsPath = static::getLibsPath();
        $testScript = "import sys; sys.path.insert(0, '{$libsPath}'); import access_parser; print('OK')";
        $cmd = "{$exe} -c \"{$testScript}\" 2>&1";

        $output = [];
        $returnVar = 1;
        @exec($cmd, $output, $returnVar);

        if ($returnVar === 0) {
            $lastLine = trim(end($output) ?: '');
            if ($lastLine === 'OK') {
                return true;
            }
        }

        // If bundled import failed, try installing via pip using detected executable
        $installCmd = "{$exe} -m pip install access-parser 2>&1";
        $installOutput = [];
        $installReturn = 1;
        @exec($installCmd, $installOutput, $installReturn);

        if ($installReturn === 0) {
            return true;
        }

        return false;
    }

    /**
     * Get full status report of Python environment.
     */
    public static function checkStatus(): array
    {
        $exe = static::findExecutable();
        $isAvailable = !empty($exe);
        $version = $isAvailable ? static::getVersion($exe) : null;
        $hasAccessParser = $isAvailable ? static::checkAccessParser($exe) : false;

        $guide = [
            'title' => 'คำแนะนำการติดตั้ง Python สำหรับระบบ XAMPP / Windows Server',
            'summary' => 'ระบบ XAMPP บน Windows ไม่มี Python มาให้ในตัว จึงต้องติดตั้ง Python 3 เพิ่มเติมเพื่อให้ระบบสามารถอ่านและนำเข้าไฟล์งบกระทรวง (.mdb) ได้',
            'steps' => [
                '1. ดาวน์โหลด Python 3 สำหรับ Windows จากเว็บไซต์ทางการ: https://www.python.org/downloads/ (แนะนำ Python 3.10, 3.11 หรือ 3.12)',
                '2. ดับเบิลคลิกไฟล์ติดตั้ง และจุดสำคัญที่สุด: ให้ทำเครื่องหมายถูกที่ช่อง ☑ "Add python.exe to PATH" ที่หน้าแรกของโปรแกรมติดตั้งก่อนกด Install Now (หรือเลือก Customize installation แล้วติ๊ก "Install for all users")',
                '3. รอจนกระทั่งติดตั้งเสร็จสมบูรณ์',
                '4. เปิดโปรแกรม XAMPP Control Panel กดปุ่ม "Stop" ที่ Apache แล้วกด "Start" ใหม่อีกครั้ง เพื่อให้ Apache โหลดค่า PATH ใหม่',
                '5. กลับมาใช้งานระบบ H-RIMS หรือกดอัปเกรดโครงสร้าง/นำเข้างบกระทรวงได้ทันที'
            ],
            'download_url' => 'https://www.python.org/downloads/'
        ];

        return [
            'available' => $isAvailable,
            'executable' => $exe,
            'version' => $version,
            'has_access_parser' => $hasAccessParser,
            'guide' => $guide
        ];
    }

    /**
     * Run a Python script safely with arguments.
     *
     * @param string $scriptPath Absolute path to .py script
     * @param array $args Arguments to pass to script
     * @return array ['success' => bool, 'output' => string, 'return_var' => int]
     */
    public static function runScript(string $scriptPath, array $args = []): array
    {
        $exe = static::findExecutable();
        if (!$exe) {
            return [
                'success' => false,
                'output' => 'ไม่พบโปรแกรม Python บนเซิร์ฟเวอร์ กรุณาติดตั้ง Python 3 บนระบบ',
                'return_var' => 127
            ];
        }

        $escapedArgs = array_map(function ($arg) {
            return '"' . addcslashes($arg, '"\\') . '"';
        }, $args);

        $scriptEscaped = '"' . str_replace('/', DIRECTORY_SEPARATOR, $scriptPath) . '"';
        $fullCommand = "{$exe} {$scriptEscaped} " . implode(' ', $escapedArgs) . ' 2>&1';

        $output = [];
        $returnVar = 1;
        @exec($fullCommand, $output, $returnVar);

        $outputStr = implode("\n", $output);
        if (!mb_check_encoding($outputStr, 'UTF-8')) {
            $outputStr = @iconv('TIS-620', 'UTF-8//IGNORE', $outputStr) ?: $outputStr;
        }

        return [
            'success' => ($returnVar === 0),
            'output' => $outputStr,
            'return_var' => $returnVar
        ];
    }
}
