<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Chatbot extends Component
{
    public $message = '';
    public $chatHistory = [];
    public $hasMessages = false;

    public function mount()
    {
        $this->updateHasMessages();
        $this->chatHistory = [
            [
                'sender' => 'user',
                'text' => 'こんにちは',
                'time' => now()->format('H:i')
            ],
            [
                'sender' => 'bot',
                'text' => 'こんにちは！どのようなご用件でしょうか？',
                'time' => now()->subMinutes(5)->format('H:i')
            ]
        ];
        $this->chatHistory = [];
    }

    public function sendMessage()
    {
        if (empty($this->message)) {
            return;
        }

        $userMessage = $this->message;
        $this->chatHistory[] = ['sender' => 'user', 'text' => $userMessage, 'time' => now()->format('H:i')];
        $this->message = '';

        $aiResponse = $this->getAiResponse($userMessage);

        if (is_array($aiResponse) && isset($aiResponse['error'])) {
            $this->chatHistory[] = ['sender' => 'bot', 'text' => 'Error: ' . $aiResponse['error'] . (isset($aiResponse['error_details']['error']['message']) ? ' - ' . $aiResponse['error_details']['error']['message'] : ''), 'time' => now()->format('H:i')];
        } else {
            $this->chatHistory[] = ['sender' => 'bot', 'text' => $aiResponse, 'time' => now()->format('H:i')];
        }

        $this->updateHasMessages();
    }

    private function updateHasMessages()
    {
        $this->hasMessages = count($this->chatHistory) > 0;
    }

    public function getAiResponse(string $prompt, string $model = 'gemini-2.5-flash-lite')
    {
        $apiKey = env('AI_API_KEY');
        if (!$apiKey) {
            Log::error('AI_API_KEY not set in .env');
            return ['error' => 'AI_API_KEY not configured.'];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        if (!$response->successful()) {
            Log::error('Gemini API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [
                'error' => 'Failed to get AI response',
                'status_code' => $response->status(),
                'error_details' => $response->json(),
            ];
        }

        $jsonResponse = $response->json();

        if (
            isset($jsonResponse['candidates']) &&
            is_array($jsonResponse['candidates']) &&
            count($jsonResponse['candidates']) > 0 &&
            isset($jsonResponse['candidates'][0]['content']['parts']) &&
            is_array($jsonResponse['candidates'][0]['content']['parts']) &&
            count($jsonResponse['candidates'][0]['content']['parts']) > 0 &&
            isset($jsonResponse['candidates'][0]['content']['parts'][0]['text'])
        ) {
            return $jsonResponse['candidates'][0]['content']['parts'][0]['text'];
        } else {
            Log::error('Unexpected Gemini API Response', [
                'response' => $jsonResponse,
            ]);
            return 'Error: Could not extract AI response.';
        }
    }

    public function render()
    {
        return view('livewire.chatbot');
    }
}
