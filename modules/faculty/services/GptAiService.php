<?php
/**
 * GptAiService
 * Wraps the Gemini text-generation API.
 * Uses the URL `?key=` auth method for maximum compatibility.
 */
require_once __DIR__ . '/../config/ai.php';

class GptAiService
{
    private string $apiKey;
    private string $model;
    private string $endpoint;

    /** User-facing error strings — never mention the upstream provider. */
    public const ERR_UNAVAILABLE = 'GPT 4.1 is temporarily unavailable. Please try again in a moment.';
    public const ERR_LIMIT       = 'GPT 4.1 has reached its request limit. Try again shortly.';
    public const ERR_NETWORK     = 'Could not reach GPT 4.1. Check your connection and retry.';
    public const ERR_EMPTY       = 'GPT 4.1 did not return a result. Try rephrasing or retrying.';

    public function __construct()
    {
        $this->apiKey = (string)(ai_config('GEMINI_API_KEY') ?? '');
        $this->model  = (string)(ai_config('GEMINI_MODEL', 'gemini-2.5-flash'));

        $this->endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/'
                        . $this->model . ':generateContent?key=' . urlencode($this->apiKey);
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * @return array{ok:bool, text?:string, error?:string}
     */
    public function generate(string $prompt, int $maxOutputTokens = 2048): array
    {
        if (!$this->isConfigured()) {
            $this->log('missing_api_key');
            return ['ok' => false, 'error' => self::ERR_UNAVAILABLE];
        }

        $payload = [
            'contents' => [[
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'temperature'      => 0.8,
                'maxOutputTokens'  => max($maxOutputTokens, 2048),
                'topP'             => 0.95,
                'thinkingConfig'   => [
                    'thinkingBudget' => 0, // disable internal reasoning so all tokens go to output
                ],
            ],
        ];

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlErr !== '') {
            $this->log('curl_error: ' . $curlErr);
            return ['ok' => false, 'error' => self::ERR_NETWORK];
        }

        if ($httpCode === 429) {
            $this->log('rate_limit: ' . substr($response, 0, 300));
            return ['ok' => false, 'error' => self::ERR_LIMIT];
        }
        if ($httpCode === 401 || $httpCode === 403) {
            $this->log('auth_error_' . $httpCode . ': ' . substr($response, 0, 300));
            return ['ok' => false, 'error' => self::ERR_UNAVAILABLE];
        }
        if ($httpCode >= 500) {
            $this->log('server_error_' . $httpCode . ': ' . substr($response, 0, 300));
            return ['ok' => false, 'error' => self::ERR_UNAVAILABLE];
        }
        if ($httpCode !== 200) {
            $this->log('unexpected_' . $httpCode . ': ' . substr($response, 0, 300));
            return ['ok' => false, 'error' => self::ERR_UNAVAILABLE];
        }

        $decoded = json_decode($response, true);

        // Skip past any thinking parts to find the actual text
        $text = '';
        foreach (($decoded['candidates'][0]['content']['parts'] ?? []) as $part) {
            if (!empty($part['text'])) {
                $text .= $part['text'];
            }
        }

        if (trim($text) === '') {
            $this->log('empty_response: ' . substr($response, 0, 300));
            return ['ok' => false, 'error' => self::ERR_EMPTY];
        }

        return ['ok' => true, 'text' => trim($text)];
    }

    private function log(string $msg): void
    {
        $dir = dirname(__DIR__, 3) . '/storage/logs';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        @file_put_contents(
            $dir . '/ai.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL,
            FILE_APPEND
        );
    }
}