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
    public $roadtaxDetails = [];

    public function mount()
    {
        $this->updateHasMessages();

        // Load chat history from session if available
        if (session()->has('chatHistory')) {
            $this->chatHistory = session('chatHistory');
            session()->forget('chatHistory'); // Clear it after loading
        } else {
            // Initial messages if no history
            /*$this->chatHistory = [
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
            ];*/
        }

        // Load roadtax details from session if available
        if (session()->has('roadtaxDetails')) {
            $this->roadtaxDetails = session('roadtaxDetails');
            session()->forget('roadtaxDetails'); // Clear it after loading
        }
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
                            'description' => 'Retrieves all road tax records from the database without requiring any specific vehicle number.',
                            'parameters' => [
                                'type' => 'object',
                                'properties' => new \stdClass(), // No properties needed as it will fetch all data
                                'required' => []     // No required parameters
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
                    // If a redirect was initiated, we don't need to send tool output back to Gemini
                    // as the page will refresh. Return an empty string to signify completion.
                    if ($toolOutput === 'REDIRECT_INITIATED') {
                        return '';
                    }
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
                // The AI requested road tax details. Redirect to the main page
                // which now fetches all road tax data via RoadtaxController::showRoadtaxes.
                // No need to fetch specific data here or pass via session, as the
                // web route will handle fetching all data.
                session()->flash('chatHistory', $this->chatHistory); // Preserve chat history
                $this->redirect('/?show_roadtax=true'); // Redirect with flag to show road tax
                return 'REDIRECT_INITIATED'; // Indicate that a redirect was initiated
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
                        ['functionCall' => ['name' => 'getRoadtaxDetails', 'args' => new \stdClass()]] // Pass an empty object for args
                    ]
                ],
                [
                    'role' => 'tool',
                    'parts' => [
                        ['functionResponse' => ['name' => 'getRoadtaxDetails', 'response' => (array)$toolOutput]]
                    ]
                ]
            ],
            'tools' => [
                [
                    'functionDeclarations' => [
                        [
                            'name' => 'getRoadtaxDetails',
                            'description' => 'Retrieves all road tax records from the database without requiring any specific vehicle number.',
                            'parameters' => [
                                'type' => 'object',
                                'properties' => new \stdClass(), // No properties needed
                                'required' => []     // No required parameters
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

// The extractArgsFromPrompt method is no longer needed and has been removed.
