<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class PlaywrightHelper
{
    /**
     * Cached node/npm executable paths
     */
    protected static $cachedNode = false;
    protected static $cachedNpm = false;

    /**
     * Find working Node.js executable path.
     */
    public static function findNodeExecutable(): ?string
    {
        if (static::$cachedNode !== false) {
            return static::$cachedNode;
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        $candidates = [];
        if ($isWindows) {
            // 0. Project Embedded / Portable Node.js (Highest priority for zero-setup)
            $embeddedPaths = [
                __DIR__ . '/Node/bin/node.exe',
                __DIR__ . '/Node/node.exe',
                base_path('node/node.exe'),
            ];
            foreach ($embeddedPaths as $emb) {
                if (file_exists($emb)) {
                    $candidates[] = str_replace('/', '\\', $emb);
                }
            }

            $candidates[] = 'node';
            $candidates[] = 'node.exe';

            $drives = ['C:', 'D:', 'E:', 'F:'];
            foreach ($drives as $d) {
                $candidates[] = "{$d}\\Program Files\\nodejs\\node.exe";
                $candidates[] = "{$d}\\Program Files (x86)\\nodejs\\node.exe";
                $candidates[] = "{$d}\\nodejs\\node.exe";
                $candidates[] = "{$d}\\xampp\\nodejs\\node.exe";
            }

            $localAppData = getenv('LOCALAPPDATA');
            if ($localAppData) {
                $candidates[] = "{$localAppData}\\Programs\\node\\node.exe";
                $candidates[] = "{$localAppData}\\Programs\\nodejs\\node.exe";
            }

            $userProfile = getenv('USERPROFILE');
            if ($userProfile) {
                $candidates[] = "{$userProfile}\\AppData\\Roaming\\nvm\\current\\node.exe";
                $candidates[] = "{$userProfile}\\scoop\\shims\\node.exe";
            }

            $userGlob = @glob('C:/Users/*/AppData/Roaming/nvm/*/node.exe');
            if (is_array($userGlob)) {
                foreach ($userGlob as $found) {
                    $candidates[] = str_replace('/', '\\', $found);
                }
            }
        } else {
            $candidates[] = '/usr/bin/node';
            $candidates[] = '/usr/local/bin/node';
            $candidates[] = '/opt/homebrew/bin/node';
            $candidates[] = 'node';
        }

        foreach (array_unique($candidates) as $cmd) {
            if (empty($cmd)) continue;

            if (strpos($cmd, '\\') !== false || strpos($cmd, '/') !== false) {
                $cleaned = trim($cmd, '"\'');
                if (!file_exists($cleaned)) continue;
            }

            $formatted = (strpos($cmd, ' ') !== false && strpos($cmd, '"') === false) ? "\"{$cmd}\"" : $cmd;
            $testCmd = "{$formatted} -v 2>&1";
            $output = [];
            $returnVar = 1;
            @exec($testCmd, $output, $returnVar);

            if ($returnVar === 0 && !empty($output) && strpos(trim($output[0]), 'v') === 0) {
                static::$cachedNode = $formatted;
                return $formatted;
            }
        }

        static::$cachedNode = null;
        return null;
    }

    /**
     * Find working NPM / NPX executable.
     */
    public static function findNpmExecutable(): ?string
    {
        if (static::$cachedNpm !== false) {
            return static::$cachedNpm;
        }

        $nodeExe = static::findNodeExecutable();
        if (!$nodeExe) {
            static::$cachedNpm = null;
            return null;
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $candidates = [];

        if ($isWindows) {
            $candidates[] = 'npm.cmd';
            $candidates[] = 'npm';

            $nodeDir = dirname(trim($nodeExe, '"\''));
            if ($nodeDir && file_exists("{$nodeDir}\\npm.cmd")) {
                $candidates[] = "{$nodeDir}\\npm.cmd";
            }

            $candidates[] = 'C:\\Program Files\\nodejs\\npm.cmd';
            $candidates[] = 'C:\\Program Files (x86)\\nodejs\\npm.cmd';
        } else {
            $candidates[] = '/usr/bin/npm';
            $candidates[] = '/usr/local/bin/npm';
            $candidates[] = 'npm';
        }

        foreach (array_unique($candidates) as $cmd) {
            if (empty($cmd)) continue;
            $formatted = (strpos($cmd, ' ') !== false && strpos($cmd, '"') === false) ? "\"{$cmd}\"" : $cmd;
            $testCmd = "{$formatted} -v 2>&1";
            $output = [];
            $returnVar = 1;
            @exec($testCmd, $output, $returnVar);

            if ($returnVar === 0 && !empty($output)) {
                static::$cachedNpm = $formatted;
                return $formatted;
            }
        }

        static::$cachedNpm = null;
        return null;
    }

    /**
     * Get dedicated writable browsers path in project storage.
     */
    public static function getCustomBrowsersPath(): string
    {
        $path = storage_path('app/playwright_browsers');
        if (!file_exists($path)) {
            @mkdir($path, 0755, true);
        }
        return $path;
    }

    /**
     * Check if Playwright module & Chromium browser are installed.
     */
    public static function checkStatus(): array
    {
        $nodeExe = static::findNodeExecutable();
        $isNodeAvailable = !empty($nodeExe);
        $nodeVersion = null;

        if ($isNodeAvailable) {
            $out = [];
            @exec("{$nodeExe} -v 2>&1", $out);
            $nodeVersion = trim(implode('', $out));
        }

        $hasPlaywrightPackage = false;
        $hasChromium = false;

        $projectRoot = base_path();
        $nodeModulesPlaywright = $projectRoot . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . 'playwright';
        
        if (file_exists($nodeModulesPlaywright)) {
            $hasPlaywrightPackage = true;
        } else if ($isNodeAvailable) {
            $out = [];
            $code = 1;
            @exec("{$nodeExe} -e \"require('playwright'); console.log('OK');\" 2>&1", $out, $code);
            if ($code === 0 && trim(end($out) ?: '') === 'OK') {
                $hasPlaywrightPackage = true;
            }
        }

        // Test if Chromium can be launched in headless mode
        $launchError = null;
        if ($hasPlaywrightPackage && $isNodeAvailable) {
            $storageBrowsers = addslashes(str_replace('/', DIRECTORY_SEPARATOR, static::getCustomBrowsersPath()));
            $testScript = "const fs = require('fs'); const path = require('path'); const { chromium } = require('playwright'); function findExe() { try { const dir = '{$storageBrowsers}'; if (fs.existsSync(dir)) { const rec = (d, target) => { for (const f of fs.readdirSync(d)) { const fp = path.join(d, f); if (fs.statSync(fp).isDirectory()) { const r = rec(fp, target); if (r) return r; } else if (f === target) { return fp; } } return null; }; const hTarget = process.platform === 'win32' ? 'chrome-headless-shell.exe' : 'chrome-headless-shell'; const cTarget = process.platform === 'win32' ? 'chrome.exe' : 'chrome'; const fH = rec(dir, hTarget); if (fH) return fH; const fC = rec(dir, cTarget); if (fC) return fC; } } catch(e) {} try { const def = chromium.executablePath(); if (def && fs.existsSync(def)) return def; } catch(e) {} const sysList = process.platform === 'win32' ? ['C:\\\\Program Files\\\\Google\\\\Chrome\\\\Application\\\\chrome.exe', 'C:\\\\Program Files (x86)\\\\Google\\\\Chrome\\\\Application\\\\chrome.exe', 'C:\\\\Program Files\\\\Microsoft\\\\Edge\\\\Application\\\\msedge.exe'] : ['/usr/bin/google-chrome', '/usr/bin/google-chrome-stable', '/usr/bin/chromium', '/usr/bin/chromium-browser']; for (const s of sysList) { if (fs.existsSync(s)) return s; } return null; } (async () => { const exe = findExe(); const opts = { headless: true, args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage', '--disable-gpu'] }; if (exe) opts.executablePath = exe; const browser = await chromium.launch(opts); await browser.close(); console.log('CHROMIUM_OK'); })().catch(e => console.log('FAIL:' + e.message));";
            $escapedScript = str_replace('"', '\"', $testScript);

            $res = static::runSyncCommand("{$nodeExe} -e \"{$escapedScript}\"", $projectRoot);
            $fullOut = $res['output'];
            if (strpos($fullOut, 'CHROMIUM_OK') !== false) {
                $hasChromium = true;
            } else {
                $rawErr = trim($fullOut);
                if (preg_match('/error while loading shared libraries:\s*([^\s:]+)/i', $rawErr, $mLib)) {
                    $missingLib = $mLib[1];
                    $launchError = "ขาด System Library บนเซิร์ฟเวอร์ Linux ({$missingLib}): แนะนำให้ Admin รันคำสั่ง 'sudo dnf install -y chromium' (AlmaLinux/CentOS/RHEL) หรือ 'sudo apt-get install -y chromium-browser' (Ubuntu/Debian) เพียงครั้งเดียว";
                } else {
                    $launchError = $rawErr;
                }
            }
        }

        return [
            'available' => ($isNodeAvailable && $hasPlaywrightPackage && $hasChromium),
            'node_available' => $isNodeAvailable,
            'node_version' => $nodeVersion,
            'has_playwright' => $hasPlaywrightPackage,
            'has_chromium' => $hasChromium,
            'launch_error' => $launchError,
        ];
    }

    /**
     * Run command synchronously in working directory and capture output.
     */
    public static function runSyncCommand(string $cmd, ?string $cwd = null, array $extraEnv = []): array
    {
        $cwd = $cwd ?: base_path();
        $env = array_merge($_SERVER, $_ENV, $extraEnv);
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $process = @proc_open($cmd, $descriptors, $pipes, $cwd, $env);
        if (!is_resource($process)) {
            $out = [];
            $code = 1;
            @exec("{$cmd} 2>&1", $out, $code);
            return [
                'code' => $code,
                'output' => trim(implode("\n", $out))
            ];
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $code = proc_close($process);
        $fullOutput = trim($stdout . "\n" . $stderr);

        return [
            'code' => $code,
            'output' => $fullOutput
        ];
    }

    /**
     * Auto install Playwright & Chromium browser.
     */
    public static function autoInstall(): array
    {
        $nodeExe = static::findNodeExecutable();
        $npmExe = static::findNpmExecutable();

        if (!$nodeExe || !$npmExe) {
            return [
                'success' => false,
                'message' => 'ไม่พบ Node.js หรือ NPM บนเครื่องเซิร์ฟเวอร์ กรุณาติดตั้ง Node.js ก่อนใช้งาน Playwright'
            ];
        }

        $projectRoot = base_path();
        $logs = [];
        $customBrowsersPath = static::getCustomBrowsersPath();
        $extraEnv = ['PLAYWRIGHT_BROWSERS_PATH' => $customBrowsersPath, 'HOME' => '/tmp'];

        // 1. Check if playwright package is already present
        $hasPlaywrightPkg = file_exists($projectRoot . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . 'playwright');
        if (!$hasPlaywrightPkg) {
            // Install playwright & adm-zip package locally with fetch timeout
            $res1 = static::runSyncCommand("{$npmExe} install playwright adm-zip --save --fetch-timeout=20000", $projectRoot);
            $logs[] = "npm install: " . trim(substr($res1['output'], -150));
            
            // If failed to download package, return early without hanging
            if (!file_exists($projectRoot . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . 'playwright')) {
                return [
                    'success' => false,
                    'message' => 'NPM ไม่สามารถดาวน์โหลดแพ็กเกจ Playwright ได้ (เซิร์ฟเวอร์อาจไม่มีเน็ต หรือติด Firewall ของโรงพยาบาล)',
                    'logs' => $logs
                ];
            }
        }

        // 2. Install Chromium browser binaries (try storage path first)
        $playwrightCliJs = $projectRoot . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . 'playwright' . DIRECTORY_SEPARATOR . 'cli.js';
        $binPlaywright = $projectRoot . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . '.bin' . DIRECTORY_SEPARATOR . (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'playwright.cmd' : 'playwright');

        if (file_exists($playwrightCliJs)) {
            $cmd = "{$nodeExe} \"{$playwrightCliJs}\" install chromium";
        } elseif (file_exists($binPlaywright)) {
            $cmd = "\"{$binPlaywright}\" install chromium";
        } else {
            $npxExe = str_replace('npm', 'npx', $npmExe);
            $cmd = "{$npxExe} playwright install chromium";
        }

        // Install to custom storage path (guaranteed writable by web server on Linux)
        $resStorage = static::runSyncCommand($cmd, $projectRoot, $extraEnv);
        $logs[] = "playwright install (storage): " . trim(substr($resStorage['output'], -150));

        // Check if ready
        $status = static::checkStatus();
        if ($status['available']) {
            return [
                'success' => true,
                'message' => 'ติดตั้ง Playwright และ Chromium สำเร็จพร้อมใช้งาน',
                'logs' => $logs
            ];
        }

        // Try default install on Windows only if storage didn't succeed
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $resDefault = static::runSyncCommand($cmd, $projectRoot);
            $logs[] = "playwright install (default): " . trim(substr($resDefault['output'], -150));
            $status = static::checkStatus();
            if ($status['available']) {
                return [
                    'success' => true,
                    'message' => 'ติดตั้ง Playwright และ Chromium สำเร็จพร้อมใช้งาน',
                    'logs' => $logs
                ];
            }
        }

        $errDetail = $status['launch_error'] ?: implode(' | ', $logs);

        return [
            'success' => false,
            'message' => 'ติดตั้ง Playwright ไม่สำเร็จ (อาจติด Firewall หรือการจำกัดสิทธิ์ของเซิร์ฟเวอร์): ' . $errDetail,
            'status' => $status
        ];
    }

    /**
     * Run KTB Crawler script.
     *
     * @param array $params ['company_id', 'user_id', 'password', 'from_date', 'to_date', 'output_dir']
     * @return array
     */
    public static function runKtbCrawler(array $params): array
    {
        $nodeExe = static::findNodeExecutable();
        if (!$nodeExe) {
            return [
                'success' => false,
                'message' => 'ไม่พบ Node.js บนเซิร์ฟเวอร์'
            ];
        }

        // Auto install if not ready
        $status = static::checkStatus();
        if (!$status['available']) {
            $installRes = static::autoInstall();
            if (!$installRes['success']) {
                return $installRes;
            }
        }

        $scriptPath = base_path('app/Helpers/Scripts/ktb_edc_crawler.js');
        if (!file_exists($scriptPath)) {
            return [
                'success' => false,
                'message' => 'ไม่พบไฟล์สคริปต์ KTB Crawler ที่ ' . $scriptPath
            ];
        }

        $outputDir = $params['output_dir'] ?? storage_path('app/tmp_edc_import/' . uniqid('ktb_'));
        if (!file_exists($outputDir)) {
            @mkdir($outputDir, 0755, true);
        }

        // Prepare JSON arguments file to avoid CLI escaping issues with passwords
        $configToken = uniqid('crawler_cfg_');
        $configFile = storage_path('app/' . $configToken . '.json');
        
        $configData = [
            'company_id' => (string)($params['company_id'] ?? ''),
            'user_id' => (string)($params['user_id'] ?? ''),
            'password' => (string)($params['password'] ?? ''),
            'from_date' => (string)($params['from_date'] ?? ''), // e.g. 21-08-2026
            'to_date' => (string)($params['to_date'] ?? ''),     // e.g. 28-08-2026
            'output_dir' => $outputDir,
            'headless' => $params['headless'] ?? true,
            'timeout' => $params['timeout'] ?? 60000,
        ];

        file_put_contents($configFile, json_encode($configData, JSON_UNESCAPED_UNICODE));

        $customPath = static::getCustomBrowsersPath();
        $extraEnv = ['PLAYWRIGHT_BROWSERS_PATH' => $customPath, 'HOME' => '/tmp'];
        $cmd = "{$nodeExe} \"" . str_replace('/', DIRECTORY_SEPARATOR, $scriptPath) . "\" --config \"" . str_replace('/', DIRECTORY_SEPARATOR, $configFile) . "\"";

        $res = static::runSyncCommand($cmd, base_path(), $extraEnv);

        // Remove temp config file
        @unlink($configFile);

        $outputStr = $res['output'];
        
        // Find JSON result inside output
        $jsonData = null;
        if (preg_match('/<<<JSON_START>>>(.*?)<<<JSON_END>>>/s', $outputStr, $m)) {
            $jsonData = json_decode(trim($m[1]), true);
        } else {
            // Try direct JSON decode
            $jsonData = json_decode($outputStr, true);
        }

        if (is_array($jsonData)) {
            return $jsonData;
        }

        Log::error("KTB Crawler failed: " . $outputStr);

        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลจาก KTB: ' . ($outputStr ?: 'Unknown error'),
            'raw_output' => $outputStr
        ];
    }

    /**
     * Start ThaiD Login Background Worker
     */
    public static function startThaidLoginProcess(string $sessionId): bool
    {
        $nodeExe = static::findNodeExecutable();
        if (!$nodeExe) {
            return false;
        }

        $scriptPath = base_path('app/Helpers/Scripts/eclaim_thaid_login.js');
        if (!file_exists($scriptPath)) {
            return false;
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $scriptEscaped = '"' . str_replace('/', DIRECTORY_SEPARATOR, $scriptPath) . '"';

        if ($isWindows) {
            $cmd = "start /B \"\" {$nodeExe} {$scriptEscaped} --sessionId={$sessionId} > NUL 2>&1";
            pclose(popen($cmd, "r"));
        } else {
            $logFile = storage_path('logs/thaid_bot_' . $sessionId . '.log');
            $customPath = static::getCustomBrowsersPath();
            $cmd = "nohup env PLAYWRIGHT_BROWSERS_PATH=\"{$customPath}\" HOME=/tmp {$nodeExe} {$scriptEscaped} --sessionId={$sessionId} </dev/null > \"{$logFile}\" 2>&1 &";
            exec($cmd);
        }

        return true;
    }
}

