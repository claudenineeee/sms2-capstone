<?php
/**
 * AI config — loads .env values for the AI service.
 * Never expose the key to the frontend.
 */
if (file_exists(__DIR__ . '/ai.php')) {
    require_once __DIR__ . '/ai.php';
}
if (!function_exists('ai_load_env')) {
    function ai_load_env(): array {
        static $env = null;
        if ($env !== null) return $env;

        $env = [];
        // ai.php lives at: modules/faculty/config/ai.php
        // dirname(__DIR__, 3) climbs: config -> faculty -> modules -> sms2-capstone
        $envPath = dirname(__DIR__, 3) . '/.env';

        if (is_file($envPath)) {
            foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                if (!str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
            }
        }

        return $env;
    }
}

if (!function_exists('ai_config')) {
    function ai_config(string $key, ?string $default = null): ?string {
        $env = ai_load_env();
        return $env[$key] ?? $default;
    }
}