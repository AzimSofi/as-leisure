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

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'tools' => [
                [
                    'functionDeclarations' => [
                        [
                            'name' => 'getRoadtaxDetails',
                            'description' => 'Gets road tax details for a given vehicle number, including expiry date.',
                            'parameters' => [
                                'type' => 'object',
                                'properties' => [
                                    'vehicle_number' => [
                                        'type' => 'string',
                                        'description' => 'The vehicle registration number (e.g., ABC1234).'
                                    ]
                                ],
                                'required' => ['vehicle_number']
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::post($url, $payload);

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
            count($jsonResponse['candidates'][0]['content']['parts']) > 0
        ) {
            $part = $jsonResponse['candidates'][0]['content']['parts'][0];

            if (isset($part['functionCall'])) {
                $functionCall = $part['functionCall'];
                $functionName = $functionCall['name'];
                $args = (array) $functionCall['args']; // Cast to array for consistency

                Log::info('Gemini requested function call', ['function' => $functionName, 'args' => $args]);

                try {
                    $toolOutput = $this->handleFunctionCall($functionName, $args);
                    // Send the tool output back to Gemini for a final response
                    return $this->getAiResponseWithToolOutput($prompt, $toolOutput, $model);
                } catch (\Exception $e) {
                    Log::error('Error handling function call', ['error' => $e->getMessage()]);
                    return ['error' => 'Error executing tool: ' . $e->getMessage()];
                }
            } elseif (isset($part['text'])) {
                return $part['text'];
            }
        }

        Log::error('Unexpected Gemini API Response', [
            'response' => $jsonResponse,
        ]);
        return 'Error: Could not extract AI response.';
    }

    private function handleFunctionCall(string $functionName, array $args)
    {
        switch ($functionName) {
            case 'getRoadtaxDetails':
                if (!isset($args['vehicle_number'])) {
                    throw new \Exception('Missing vehicle_number for getRoadtaxDetails function.');
                }
                $vehicleNumber = $args['vehicle_number'];
                // Use direct API path instead of route() helper to avoid route definition issues
                $response = Http::get(url('/api/road-taxes',/* ['vehicle_number' => $vehicleNumber]*/));

                if ($response->successful()) {
                    return $response->json();
                } else {
                    Log::error('API call failed for roadtax details', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    throw new \Exception('Failed to fetch road tax details from API.');
                }
                break;
            default:
                throw new \Exception('Unknown function call: ' . $functionName);
        }
    }

    private function getAiResponseWithToolOutput(string $prompt, array $toolOutput, string $model)
    {
        $apiKey = env('AI_API_KEY');
        if (!$apiKey) {
            Log::error('AI_API_KEY not set in .env');
            return ['error' => 'AI_API_KEY not configured.'];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ],
                [
                    'role' => 'model',
                    'parts' => [
                        ['functionCall' => ['name' => 'getRoadtaxDetails', 'args' => ['vehicle_number' => $toolOutput['data'][0]['vehicle_number'] ?? '']]]
                    ]
                ],
                [
                    'role' => 'tool',
                    'parts' => [
                        ['functionResponse' => ['name' => 'getRoadtaxDetails', 'response' => $toolOutput]]
                    ]
                ]
            ],
            'tools' => [
                [
                    'functionDeclarations' => [
                        [
                            'name' => 'getRoadtaxDetails',
                            'description' => 'Gets road tax details for a given vehicle number, including expiry date.',
                            'parameters' => [
                                'type' => 'object',
                                'properties' => [
                                    'vehicle_number' => [
                                        'type' => 'string',
                                        'description' => 'The vehicle registration number (e.g., ABC1234).'
                                    ]
                                ],
                                'required' => ['vehicle_number']
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::post($url, $payload);

        if (!$response->successful()) {
            Log::error('Gemini API Error (tool output follow-up)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [
                'error' => 'Failed to get AI response after tool call',
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
            Log::error('Unexpected Gemini API Response (tool output follow-up)', [
                'response' => $jsonResponse,
            ]);
            return 'Error: Could not extract AI response after tool call.';
        }
    }

    public function render()
    {
        return view('livewire.chatbot');
    }
}
