<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Chatbot Interface</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        <style>
            body {
                font-family: 'Inter', sans-serif;
            }
            .chat-input-container {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                display: flex;
                justify-content: center;
                padding: 20px;
                background-color: white;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            }
            .chat-input-wrapper {
                width: 100%;
                max-width: 768px;
                display: flex;
                align-items: center;
                border: 1px solid #e0e0e0;
                border-radius: 30px;
                padding: 8px 16px;
                background-color: #f7f7f7;
            }
            .chat-input {
                flex-grow: 1;
                border: none;
                outline: none;
                padding: 8px 0;
                font-size: 16px;
                background-color: transparent;
            }
            .chat-input::placeholder {
                color: #a0a0a0;
            }
            .icon-button {
                background: none;
                border: none;
                cursor: pointer;
                padding: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: black;
            }
            .send-button {
                background-color: black;
                color: white;
                border-radius: 50%;
                transition: background-color 0.3s ease, color 0.3s ease;
            }

            #mic-menu-button:hover {
                background: #e0e0e0;
                border-radius: 50%;
            }
        </style>
    </head>
    <body class="bg-white text-gray-800 flex flex-col min-h-screen">
        <header class="flex justify-between items-center p-4 border-b border-gray-200">
            <div class="flex items-center space-x-2">
                <span class="font-semibold text-lg">AS Leisure</span>
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </div>
            <div class="flex items-center space-x-4 text-sm text-gray-500">
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Still in development
                </span>
                <a href="#" class="flex items-center text-purple-600 hover:underline">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path></svg>
                    To add or edit file
                </a>
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </div>
        </header>

        <main class="flex-grow flex flex-col items-center text-center p-4">
            <h1 class="text-3xl font-light text-gray-700 mb-8 header-message mt-55">Ask anything</h1>
            <div class="w-full">
              @livewire('chatbot')
            </div>
        </main>
        @livewireScripts
    </body>
</html>
