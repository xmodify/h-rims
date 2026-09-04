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
     * Get Chat Model Name
     */
    public static function getModelName()
    {
        return self::getSetting('ai_model_name', env('AI_MODEL_NAME', 'gemini-1.5-flash'));
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
    public function testConnection()
    {
        $provider = self::getProvider();
        $model = self::getModelName();

        try {
            $testPrompt = "กรุณาตอบว่า 'การเชื่อมต่อระบบ AI สำเร็จ' สั้นๆ 1 ประโยค";
            $response = $this->generateChat($testPrompt);

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
     * @return string
     */
    public function generateChat(string $prompt, ?string $systemPrompt = null): string
    {
        $provider = self::getProvider();

        if ($provider === 'gemini') {
            return $this->generateGeminiChat($prompt, $systemPrompt);
        } elseif ($provider === 'ollama') {
            return $this->generateOllamaChat($prompt, $systemPrompt);
        } else {
            return $this->generateOpenAiChat($prompt, $systemPrompt);
        }
    }

    // ==========================================
    // Google Gemini API Handlers
    // ==========================================

    protected function getGeminiEmbedding(string $text): array
    {
        $apiKey = self::getApiKey();
        if (empty($apiKey)) {
            throw new \Exception("กรุณากรอก GEMINI_API_KEY ในหน้าตั้งค่าหรือไฟล์ .env ก่อนใช้งาน");
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

    protected function generateGeminiChat(string $prompt, ?string $systemPrompt = null): string
    {
        $apiKey = self::getApiKey();
        if (empty($apiKey)) {
            throw new \Exception("กรุณากรอก GEMINI_API_KEY ในหน้าตั้งค่าหรือไฟล์ .env ก่อนใช้งาน");
        }

        $model = self::getModelName();
        if (empty($model) || in_array($model, ['gemini-1.5-flash', 'gemini-2.5-flash', 'gemini-2.0-flash'], true)) {
            $model = 'gemini-flash-latest';
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

        // Resilient fallback order if primary model experiences high demand spike (503/429)
        $modelsToTry = array_values(array_unique([$model, 'gemini-flash-latest', 'gemini-3.6-flash', 'gemini-3.5-flash']));
        $lastError = null;

        foreach ($modelsToTry as $currentModel) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:generateContent?key={$apiKey}";

            try {
                $res = Http::withoutVerifying()
                    ->timeout(30)
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

                // If high demand spike or 503/429, try next fallback model
                if ($res->status() === 503 || $res->status() === 429 || str_contains($err, 'demand') || str_contains($err, 'RESOURCE_EXHAUSTED')) {
                    Log::warning("Gemini model {$currentModel} busy: {$err}. Trying next fallback...");
                    usleep(500000);
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

    protected function generateOllamaChat(string $prompt, ?string $systemPrompt = null): string
    {
        $baseUrl = self::getApiUrl();
        $model = self::getModelName();

        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false
        ];

        if ($systemPrompt) {
            $payload['system'] = $systemPrompt;
        }

        $res = Http::withoutVerifying()
            ->timeout(90)
            ->post("{$baseUrl}/api/generate", $payload);

        if (!$res->successful()) {
            throw new \Exception("Ollama Generate Error: " . $res->body());
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

    protected function generateOpenAiChat(string $prompt, ?string $systemPrompt = null): string
    {
        $baseUrl = self::getApiUrl();
        $apiKey = self::getApiKey();
        $model = self::getModelName();

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
