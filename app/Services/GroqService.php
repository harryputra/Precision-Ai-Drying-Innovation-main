<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key');
        $this->model  = config('services.groq.model', 'llama-3.1-8b-instant');
    }

    /**
     * Kirim pesan ke Groq — format OpenAI compatible.
     */
    public function chat(string $systemPrompt, array $messages): array
    {
        $payload = [
            'model'    => $this->model,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $messages
            ),
            'temperature' => 0.7,
            'max_tokens'  => 1024,
        ];

        $response = Http::timeout(30)
            ->withToken($this->apiKey)
            ->post($this->baseUrl, $payload);

        if ($response->failed()) {
            Log::error('Groq API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Groq API error: ' . $response->status());
        }

        $data       = $response->json();
        $replyText  = $data['choices'][0]['message']['content'] ?? 'Maaf, tidak dapat memproses permintaan.';
        $tokensUsed = ($data['usage']['prompt_tokens'] ?? 0)
                    + ($data['usage']['completion_tokens'] ?? 0);

        return [
            'message'     => $replyText,
            'tokens_used' => $tokensUsed,
            'model'       => $this->model,
        ];
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
