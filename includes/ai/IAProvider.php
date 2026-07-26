<?php
declare(strict_types=1);

final class IAProvider
{
    private string $provider;
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->provider = (string) env('AI_PROVIDER', 'mistral');
        $this->apiKey = (string) env('MISTRAL_API_KEY', '');
        $this->model = (string) env('MISTRAL_MODEL', 'mistral-small-latest');
    }

    public function complete(string $systemPrompt, string $userPrompt, array $options = []): ?string
    {
        $start = microtime(true);
        $response = null;
        $error = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = $this->request($systemPrompt, $userPrompt, $options);
                if ($response !== null && $response !== '') {
                    $this->logCall($start, true, null, $options, strlen($response));
                    return $response;
                }
            } catch (Throwable $throwable) {
                $error = $throwable->getMessage();
                if ($attempt === 2) {
                    $this->logCall($start, false, $error, $options, 0);
                    return null;
                }
            }
        }

        $this->logCall($start, false, $error, $options, 0);
        return null;
    }

    public function completeJson(string $systemPrompt, string $userPrompt, array $options = []): ?array
    {
        $options['response_format'] = ['type' => 'json_object'];
        $response = $this->complete($systemPrompt, $userPrompt, $options);

        if ($response === null) {
            return null;
        }

        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function request(string $systemPrompt, string $userPrompt, array $options): ?string
    {
        if ($this->provider !== 'mistral' || $this->apiKey === '' || !function_exists('curl_init')) {
            return null;
        }

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => $options['temperature'] ?? 0.2,
        ];

        if (isset($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        $curl = curl_init('https://api.mistral.ai/v1/chat/completions');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 30,
        ]);

        $rawResponse = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($rawResponse === false || $curlError !== '') {
            throw new RuntimeException($curlError !== '' ? $curlError : 'Unknown cURL error');
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException('Mistral HTTP ' . $httpCode);
        }

        $decoded = json_decode((string) $rawResponse, true);
        return $decoded['choices'][0]['message']['content'] ?? null;
    }

    private function logCall(float $start, bool $success, ?string $error, array $options, int $tokens): void
    {
        $line = json_encode([
            'timestamp' => date('c'),
            'provider' => $this->provider,
            'model' => $options['model'] ?? $this->model,
            'success' => $success,
            'error' => $error,
            'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            'tokens_estimate' => $tokens,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        file_put_contents(LOG_PATH . DIRECTORY_SEPARATOR . 'ai_calls.log', $line . PHP_EOL, FILE_APPEND);
    }
}