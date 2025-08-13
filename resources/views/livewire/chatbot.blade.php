<div class="chat-container @if($hasMessages) has-messages @endif">
    <div class="chat-messages">
        @foreach ($chatHistory as $chat)
            <div class="message {{ $chat['sender'] }}">
                {{ $chat['text'] }}
                {{-- @isset($chat['time'])
                    <span class="message-time">{{ $chat['time'] }}</span>
                @endisset --}}
            </div>
        @endforeach
    </div>

    <form wire:submit.prevent="sendMessage" class="chat-input-form">
        @csrf
        <div class="chat-input-wrapper">
            <button class="icon-button">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"
                    xmlns="http://www.w3.org/2000/svg" class="icon">
                    <path
                        d="M9.33496 16.5V10.665H3.5C3.13273 10.665 2.83496 10.3673 2.83496 10C2.83496 9.63273 3.13273 9.33496 3.5 9.33496H9.33496V3.5C9.33496 3.13273 9.63273 2.83496 10 2.83496C10.3673 2.83496 10.665 3.13273 10.665 3.5V9.33496H16.5L16.6338 9.34863C16.9369 9.41057 17.165 9.67857 17.165 10C17.165 10.3214 16.9369 10.5894 16.6338 10.6514L16.5 10.665H10.665V16.5C10.665 16.8673 10.3673 17.165 10 17.165C9.63273 17.165 9.33496 16.8673 9.33496 16.5Z">
                    </path>
                </svg>
            </button>
            <input type="text" wire:model.live="message" id="chat-input" placeholder="@if($hasMessages) Ask me anything @else Where should we begin? @endif"
                class="chat-input">
            <button type="submit" id="send-button" class="icon-button send-button">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"
                    xmlns="http://www.w3.org/2000/svg" class="icon">
                    <path
                        d="M8.99992 16V6.41407L5.70696 9.70704C5.31643 10.0976 4.68342 10.0976 4.29289 9.70704C3.90237 9.31652 3.90237 8.6835 4.29289 8.29298L9.29289 3.29298L9.36907 3.22462C9.76184 2.90427 10.3408 2.92686 10.707 3.29298L15.707 8.29298L15.7753 8.36915C16.0957 8.76192 16.0731 9.34092 15.707 9.70704C15.3408 10.0732 14.7618 10.0958 14.3691 9.7754L14.2929 9.70704L10.9999 6.41407V16C10.9999 16.5523 10.5522 17 9.99992 17C9.44764 17 8.99992 16.5523 8.99992 16Z">
                    </path>
                </svg>
            </button>
        </div>
    </form>
</div>
