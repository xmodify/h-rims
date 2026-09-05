<?php

namespace App\Services\Ai;

use App\Models\MainSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    /**
     * Check if RiMS Copilot is enabled globally
     */
    public static function isActive(): bool
    {
        $val = self::getSetting('ai_active', 'Y');
        return strtoupper($val) !== 'N' && $val !== '0';
    }

    /**
     * Get configured AI Provider ('gemini', 'ollama', 'openai_compatible')
     */
    public static function getProvider()
    {
        return self::getSetting('ai_provider', env('AI_PROVIDER', 'gemini'));
    }

    /**
     * Get API Key
     */
    public static function getApiKey()
    {
        return self::getSetting('ai_api_key', env('GEMINI_API_KEY', ''));
    }

    /**
     * Get Base URL (for Ollama or custom local server)
     */
    public static function getApiUrl()
    {
        $provider = self::getProvider();
        $defaultUrl = ($provider === 'gemini') ? 'https://generativelanguage.googleapis.com' : 'http://localhost:11434';
        $url = self::getSetting('ai_api_url', env('AI_API_URL', $defaultUrl));

        if ($provider === 'gemini' && (empty($url) || strpos($url, 'localhost:11434') !== false)) {
            return 'https://generativelanguage.googleapis.com';
        }

        return rtrim($url, '/');
    }

    /**
     * Get Chat Model Name (per-page or default)
     */
    public static function getModelName(?string $pageContext = null)
    {
        $provider = self::getProvider();

        if ($pageContext && str_contains(strtolower($pageContext), 'hosfin')) {
            $hosfinModel = self::getSetting('ai_model_hosfin');
            if (!empty($hosfinModel)) {
                // If provider is Ollama, protect against using cloud Gemini model name
                if ($provider === 'ollama' && str_contains(strtolower($hosfinModel), 'gemini')) {
                    $generalModel = self::getSetting('ai_model_name', 'gemma4:e4b');
                    return (!str_contains(strtolower($generalModel), 'gemini')) ? $generalModel : 'gemma4:e4b';
                }
                return $hosfinModel;
            }
            return ($provider === 'ollama') ? 'gemma4:e4b' : (($provider === 'openai_compatible') ? 'deepseek-chat' : 'gemini-3.7-flash');
        }

        $generalModel = self::getSetting('ai_model_name');
        if (!empty($generalModel)) {
            if ($provider === 'ollama' && str_contains(strtolower($generalModel), 'gemini')) {
                return 'gemma4:e4b';
            }
            return $generalModel;
        }

        return ($provider === 'ollama') ? 'gemma4:e4b' : (($provider === 'openai_compatible') ? 'deepseek-chat' : 'gemini-3.7-flash');
    }

    /**
     * Get HosFin Specific Model Name
     */
    public static function getHosfinModelName()
    {
        $provider = self::getProvider();
        $hosfinModel = self::getSetting('ai_model_hosfin');
        if (!empty($hosfinModel)) {
            if ($provider === 'ollama' && str_contains(strtolower($hosfinModel), 'gemini')) {
                return 'gemma4:e4b';
            }
            return $hosfinModel;
        }
        return ($provider === 'ollama') ? 'gemma4:e4b' : 'gemini-3.7-flash';
    }

    /**
     * Get Embedding Model Name
     */
    public static function getEmbedModel()
    {
        return self::getSetting('ai_embed_model', env('AI_EMBED_MODEL', 'text-embedding-004'));
    }

    /**
     * Helper to get setting from DB or fallback
     */
    protected static function getSetting($name, $default = '')
    {
        try {
            $val = MainSetting::where('name', $name)->value('value');
            if (!is_null($val) && trim($val) !== '') {
                return trim($val, '" \'');
            }
        } catch (\Throwable $e) {
            // DB not ready or column missing
        }
        return $default;
    }

    /**
     * Test connection to the configured AI service
     */
    public function testConnection(?string $pageContext = null)
    {
        $provider = self::getProvider();
        $model = self::getModelName($pageContext);

        try {
            $testPrompt = "กรุณาตอบว่า 'การเชื่อมต่อระบบ AI สำเร็จ' สั้นๆ 1 ประโยค";
            $response = $this->generateChat($testPrompt, null, $pageContext);

            return [
                'success' => true,
                'provider' => $provider,
                'model' => $model,
                'response' => $response,
                'message' => "เชื่อมต่อสำเร็จ! โมเดล [{$model}] ตอบกลับเรียบร้อยแล้ว"
            ];
        } catch (\Throwable $e) {
            Log::error("AI Connection Test Error ({$provider}): " . $e->getMessage());
            return [
                'success' => false,
                'provider' => $provider,
                'model' => $model,
                'error' => $e->getMessage(),
                'message' => "เชื่อมต่อไม่สำเร็จ: " . $e->getMessage()
            ];
        }
    }

    /**
     * Fetch available models from the provider (Google Gemini, Ollama, etc.)
     */
    public function fetchAvailableModels(?string $provider = null, ?string $apiKey = null, ?string $apiUrl = null): array
    {
        $provider = $provider ?: self::getProvider();
        $apiKey = $apiKey !== null ? trim($apiKey) : self::getApiKey();
        $apiUrl = $apiUrl !== null ? trim($apiUrl) : self::getApiUrl();

        if ($provider === 'gemini') {
            if (empty($apiKey)) {
                return [
                    'success' => false,
                    'message' => 'กรุณาระบุ API Key ของ Google Gemini ก่อนเพื่อค้นหาโมเดล'
                ];
            }

            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";
                $res = Http::withoutVerifying()->timeout(15)->get($url);

                if (!$res->successful()) {
                    $err = $res->json()['error']['message'] ?? $res->body();
                    return [
                        'success' => false,
                        'message' => 'Gemini API แจ้งข้อผิดพลาด: ' . $err
                    ];
                }

                $data = $res->json();
                $models = $data['models'] ?? [];

                $chatModels = [];
                $embedModels = [];

                foreach ($models as $m) {
                    $name = str_replace('models/', '', $m['name']);
                    $methods = $m['supportedGenerationMethods'] ?? [];

                    // Embedding models
                    if (in_array('embedContent', $methods) || str_contains($name, 'embedding')) {
                        $embedModels[] = [
                            'name' => $name,
                            'display_name' => $m['displayName'] ?? $name,
                            'recommended' => ($name === 'gemini-embedding-001')
                        ];
                    }

                    // Chat generation models
                    if (in_array('generateContent', $methods)) {
                        // Exclude specialized non-chat / robotics / vision-only / transcribe models
                        if (preg_match('/(tts|robotics|computer-use|transcribe|preview-customtools|image)/i', $name)) {
                            continue;
                        }

                        $chatModels[] = [
                            'name' => $name,
                            'display_name' => $m['displayName'] ?? $name,
                            'recommended' => in_array($name, ['gemini-3.7-flash', 'gemini-3.5-flash-lite', 'gemini-flash-latest'])
                        ];
                    }
                }

                // Sort: recommended priority
                usort($chatModels, function($a, $b) {
                    $order = [
                        'gemini-3.7-flash' => 1,
                        'gemini-3.5-flash-lite' => 2,
                        'gemini-flash-latest' => 3,
                        'gemini-flash-lite-latest' => 4,
                        'gemini-3.8-flash' => 5,
                        'gemini-3.6-flash' => 6,
                        'gemini-pro-latest' => 7,
                    ];
                    $rankA = $order[$a['name']] ?? 99;
                    $rankB = $order[$b['name']] ?? 99;
                    return $rankA <=> $rankB;
                });

                return [
                    'success' => true,
                    'provider' => 'gemini',
                    'chat_models' => array_values($chatModels),
                    'embed_models' => array_values($embedModels),
                    'recommended_chat' => 'gemini-3.7-flash',
                    'recommended_embed' => 'gemini-embedding-001',
                    'message' => 'ค้นพบ ' . count($chatModels) . ' โมเดลตอบคำถาม และ ' . count($embedModels) . ' โมเดลเวกเตอร์ จาก Gemini API'
                ];
            } catch (\Throwable $e) {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถติดต่อ Gemini API ได้: ' . $e->getMessage()
                ];
            }
        } elseif ($provider === 'ollama') {
            $baseUrl = !empty($apiUrl) ? rtrim($apiUrl, '/') : 'http://localhost:11434';
            try {
                $res = Http::withoutVerifying()->timeout(10)->get("{$baseUrl}/api/tags");
                if (!$res->successful()) {
                    return [
                        'success' => false,
                        'message' => 'ไม่สามารถดึงโมเดลจาก Ollama ได้: ' . $res->body()
                    ];
                }

                $data = $res->json();
                $models = $data['models'] ?? [];
                $chatModels = [];
                $embedModels = [];

                foreach ($models as $m) {
                    $name = $m['name'] ?? '';
                    if (str_contains($name, 'embed') || str_contains($name, 'bge')) {
                        $embedModels[] = ['name' => $name, 'display_name' => $name];
                    } else {
                        $chatModels[] = ['name' => $name, 'display_name' => $name];
                    }
                }

                return [
                    'success' => true,
                    'provider' => 'ollama',
                    'chat_models' => $chatModels,
                    'embed_models' => $embedModels,
                    'recommended_chat' => $chatModels[0]['name'] ?? 'gemma4:e4b',
                    'recommended_embed' => $embedModels[0]['name'] ?? 'nomic-embed-text',
                    'message' => 'ดึงโมเดลที่ติดตั้งใน Ollama สำเร็จ (' . count($models) . ' โมเดล)'
                ];
            } catch (\Throwable $e) {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถติดต่อเซิร์ฟเวอร์ Ollama ได้: ' . $e->getMessage()
                ];
            }
        } else {
            // OpenAI-Compatible (DeepSeek, OpenAI, LocalAI, LM Studio, vLLM)
            $baseUrl = !empty($apiUrl) ? rtrim($apiUrl, '/') : 'https://api.deepseek.com/v1';

            // Try dynamic fetch from the specified URL endpoint if provided
            if (!empty($baseUrl)) {
                try {
                    $endpoint = str_ends_with($baseUrl, '/models') ? $baseUrl : "{$baseUrl}/models";
                    $req = Http::withoutVerifying()->timeout(6);
                    if (!empty($apiKey)) {
                        $req = $req->withToken($apiKey);
                    }
                    $res = $req->get($endpoint);

                    if ($res->successful()) {
                        $data = $res->json();
                        $modelsList = $data['data'] ?? [];
                        if (!empty($modelsList) && is_array($modelsList)) {
                            $chatModels = [];
                            $embedModels = [];

                            foreach ($modelsList as $m) {
                                $id = $m['id'] ?? '';
                                if (empty($id)) continue;
                                if (str_contains(strtolower($id), 'embed') || str_contains(strtolower($id), 'bge')) {
                                    $embedModels[] = ['name' => $id, 'display_name' => $id];
                                } else {
                                    $chatModels[] = ['name' => $id, 'display_name' => $id];
                                }
                            }

                            if (!empty($chatModels)) {
                                return [
                                    'success' => true,
                                    'provider' => 'openai_compatible',
                                    'chat_models' => $chatModels,
                                    'embed_models' => !empty($embedModels) ? $embedModels : [
                                        ['name' => 'bge-m3', 'display_name' => 'BGE-M3 (Multilingual)', 'recommended' => true]
                                    ],
                                    'recommended_chat' => $chatModels[0]['name'],
                                    'recommended_embed' => !empty($embedModels) ? $embedModels[0]['name'] : 'bge-m3',
                                    'message' => 'ดึงโมเดลจากเซิร์ฟเวอร์ ' . parse_url($baseUrl, PHP_URL_HOST) . ' สำเร็จ (' . count($chatModels) . ' โมเดล)'
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // Endpoint unreachable or timeout: fall back smoothly to presets below
                }
            }

            // Fallback standard presets for OpenAI-compatible
            return [
                'success' => true,
                'provider' => 'openai_compatible',
                'chat_models' => [
                    ['name' => 'deepseek-chat', 'display_name' => 'DeepSeek Chat (V3)', 'recommended' => true],
                    ['name' => 'deepseek-reasoner', 'display_name' => 'DeepSeek Reasoner (R1)'],
                    ['name' => 'gpt-4o-mini', 'display_name' => 'GPT-4o Mini'],
                    ['name' => 'gpt-4o', 'display_name' => 'GPT-4o']
                ],
                'embed_models' => [
                    ['name' => 'bge-m3', 'display_name' => 'BGE-M3 (Multilingual)', 'recommended' => true],
                    ['name' => 'text-embedding-3-small', 'display_name' => 'OpenAI text-embedding-3-small']
                ],
                'recommended_chat' => 'deepseek-chat',
                'recommended_embed' => 'bge-m3',
                'message' => 'รายการโมเดลมาตรฐานสำหรับ OpenAI-Compatible (DeepSeek / GPT)'
            ];
        }
    }

    /**
     * Generate embedding vector from text
     *
     * @param string $text
     * @return array Vector array of floats
     */
    public function getEmbedding(string $text): array
    {
        $provider = self::getProvider();
        $cleanText = mb_substr(trim(preg_replace('/\s+/', ' ', $text)), 0, 3000);

        if (empty($cleanText)) {
            return [];
        }

        if ($provider === 'gemini') {
            return $this->getGeminiEmbedding($cleanText);
        } elseif ($provider === 'ollama') {
            return $this->getOllamaEmbedding($cleanText);
        } else {
            return $this->getOpenAiEmbedding($cleanText);
        }
    }

    /**
     * Generate response from LLM
     *
     * @param string $prompt
     * @param string|null $systemPrompt
     * @param string|null $pageContext
     * @return string
     */
    public function generateChat(string $prompt, ?string $systemPrompt = null, ?string $pageContext = null): string
    {
        $provider = self::getProvider();

        if ($provider === 'gemini') {
            return $this->generateGeminiChat($prompt, $systemPrompt, $pageContext);
        } elseif ($provider === 'ollama') {
            return $this->generateOllamaChat($prompt, $systemPrompt, $pageContext);
        } else {
            return $this->generateOpenAiChat($prompt, $systemPrompt, $pageContext);
        }
    }

    // ==========================================
    // Google Gemini API Handlers
    // ==========================================

    protected function getGeminiEmbedding(string $text): array
    {
        $apiKey = self::getApiKey();
        if (empty($apiKey)) {
            throw new \Exception("ยังไม่ได้ระบุ Gemini API Key สำหรับโมเดล Google Gemini (กรุณากรอกในหน้าตั้งค่า AI & LLM Connection หรือสลับไปใช้ Ollama)");
        }

        $embedModel = self::getEmbedModel();
        if (empty($embedModel) || $embedModel === 'text-embedding-004') {
            $embedModel = 'gemini-embedding-001';
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$embedModel}:embedContent?key={$apiKey}";

        $res = Http::withoutVerifying()
            ->timeout(20)
            ->withHeaders([
                'x-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json'
            ])
            ->post($url, [
                'content' => [
                    'parts' => [
                        ['text' => $text]
                    ]
                ]
            ]);

        if (!$res->successful()) {
            $err = $res->json()['error']['message'] ?? $res->body();
            throw new \Exception("Gemini Embedding Error: " . $err);
        }

        $data = $res->json();
        return $data['embedding']['values'] ?? [];
    }

    protected function generateGeminiChat(string $prompt, ?string $systemPrompt = null, ?string $pageContext = null): string
    {
        $apiKey = self::getApiKey();
        if (empty($apiKey)) {
            throw new \Exception("ยังไม่ได้ระบุ Gemini API Key สำหรับ Google Gemini (กรุณาไปที่หน้า 'ตั้งค่า AI & LLM Connection' เพื่อบันทึก Key หรือสลับไปใช้ผู้ให้บริการอื่น เช่น Ollama / OpenAI-Compatible)");
        }

        $model = self::getModelName($pageContext);
        if (empty($model) || in_array($model, ['gemini-1.5-flash', 'gemini-2.5-flash', 'gemini-2.0-flash'], true)) {
            $model = ($pageContext && str_contains(strtolower($pageContext), 'hosfin')) ? 'gemini-3.6-flash' : 'gemini-flash-latest';
        }

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        if ($systemPrompt) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemPrompt]
                ]
            ];
        }

        // Resilient fallback order if primary model experiences high demand spike (503/429) or is deprecated (404)
        $modelsToTry = array_values(array_unique([$model, 'gemini-3.7-flash', 'gemini-3.5-flash-lite', 'gemini-flash-lite-latest', 'gemini-3.8-flash', 'gemini-flash-latest']));
        $lastError = null;

        foreach ($modelsToTry as $currentModel) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:generateContent?key={$apiKey}";

            try {
                $res = Http::withoutVerifying()
                    ->timeout(50)
                    ->withHeaders([
                        'x-goog-api-key' => $apiKey,
                        'Content-Type' => 'application/json'
                    ])
                    ->post($url, $payload);

                if ($res->successful()) {
                    $data = $res->json();
                    return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'ไม่พบคำตอบจาก AI';
                }

                $err = $res->json()['error']['message'] ?? $res->body();
                $lastError = $err;

                // If high demand spike, quota limit (429), or deprecated model (404), try next fallback model
                if ($res->status() === 503 || $res->status() === 429 || $res->status() === 404 || str_contains($err, 'demand') || str_contains($err, 'RESOURCE_EXHAUSTED') || str_contains($err, 'no longer available')) {
                    Log::warning("Gemini model {$currentModel} status {$res->status()}: {$err}. Trying next fallback...");
                    usleep(300000);
                    continue;
                }

                throw new \Exception("Gemini Chat Error: " . $err);
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                if (str_contains($lastError, 'timed out') || str_contains($lastError, 'demand')) {
                    Log::warning("Gemini model {$currentModel} timed out or busy. Trying next fallback...");
                    continue;
                }
                throw $e;
            }
        }

        throw new \Exception("Gemini Chat Error: " . ($lastError ?? 'เกิดข้อผิดพลาดในการเชื่อมต่อกับ Gemini API'));
    }

    // ==========================================
    // Ollama (Local) Handlers
    // ==========================================

    protected function getOllamaEmbedding(string $text): array
    {
        $baseUrl = self::getApiUrl();
        $model = self::getEmbedModel();
        if (empty($model) || strpos($model, 'text-embedding') !== false) {
            $model = 'nomic-embed-text';
        }

        // Try /api/embeddings first
        $res = Http::withoutVerifying()
            ->timeout(30)
            ->post("{$baseUrl}/api/embeddings", [
                'model' => $model,
                'prompt' => $text
            ]);

        if (!$res->successful()) {
            // Try /api/embed (newer Ollama version)
            $res = Http::withoutVerifying()
                ->timeout(30)
                ->post("{$baseUrl}/api/embed", [
                    'model' => $model,
                    'input' => $text
                ]);
            if ($res->successful()) {
                $embeddings = $res->json()['embeddings'] ?? [];
                return $embeddings[0] ?? [];
            }
            throw new \Exception("Ollama Embedding Error: " . $res->body());
        }

        return $res->json()['embedding'] ?? [];
    }

    protected function generateOllamaChat(string $prompt, ?string $systemPrompt = null, ?string $pageContext = null): string
    {
        $baseUrl = self::getApiUrl();
        $model = self::getModelName($pageContext);

        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'num_predict' => 800,
                'temperature' => 0.4
            ]
        ];

        if ($systemPrompt) {
            $payload['system'] = $systemPrompt;
        }

        try {
            $res = Http::withoutVerifying()
                ->timeout(180)
                ->post("{$baseUrl}/api/generate", $payload);
        } catch (\Throwable $e) {
            $errMsg = $e->getMessage();
            if (str_contains($errMsg, 'timed out') || str_contains($errMsg, 'cURL error 28')) {
                throw new \Exception("โมเดล Ollama ({$model}) ประมวลผลนานเกิน 3 นาที (Timeout) เนื่องจากโมเดลมีขนาดใหญ่ (9.4GB) และรันบน CPU คอมพิวเตอร์ (ไม่มี GPU แยก) ทำให้คำนวณบทวิเคราะห์ยาวไม่ทัน แนะนำให้กดปุ่ม '⚙️ ตั้งค่า AI' สลับไปใช้ Google Gemini ซึ่งประมวลผลเสร็จใน 2-3 วินาทีครับ");
            }
            throw new \Exception("ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ Ollama ที่ [{$baseUrl}] ได้ ({$errMsg}) กรุณาตรวจสอบว่าเซิร์ฟเวอร์ Ollama กำลังทำงานอยู่");
        }

        if (!$res->successful()) {
            throw new \Exception("Ollama (โมเดล {$model}) ตอบกลับข้อผิดพลาด: " . ($res->json()['error'] ?? $res->body()));
        }

        return $res->json()['response'] ?? '';
    }

    // ==========================================
    // OpenAI-Compatible Handlers (DeepSeek, etc.)
    // ==========================================

    protected function getOpenAiEmbedding(string $text): array
    {
        $baseUrl = self::getApiUrl();
        $apiKey = self::getApiKey();
        $model = self::getEmbedModel();

        $res = Http::withoutVerifying()
            ->withToken($apiKey)
            ->timeout(20)
            ->post("{$baseUrl}/embeddings", [
                'model' => $model,
                'input' => $text
            ]);

        if (!$res->successful()) {
            throw new \Exception("OpenAI Embedding Error: " . $res->body());
        }

        return $res->json()['data'][0]['embedding'] ?? [];
    }

    protected function generateOpenAiChat(string $prompt, ?string $systemPrompt = null, ?string $pageContext = null): string
    {
        $baseUrl = self::getApiUrl();
        $apiKey = self::getApiKey();
        $model = self::getModelName($pageContext);

        if (empty($apiKey)) {
            throw new \Exception("ยังไม่ได้ระบุ API Key สำหรับ OpenAI-Compatible ในหน้าตั้งค่า AI & LLM Connection");
        }

        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $res = Http::withoutVerifying()
            ->withToken($apiKey)
            ->timeout(60)
            ->post("{$baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => $messages
            ]);

        if (!$res->successful()) {
            throw new \Exception("OpenAI Chat Error: " . $res->body());
        }

        return $res->json()['choices'][0]['message']['content'] ?? '';
    }
}
