import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const chatMessagesContainer = document.querySelector(".chat-messages");
    const headerMessage = document.querySelector(".header-message");
    const chatInput = document.getElementById("chat-input");
    const chatContainer = document.querySelector(".chat-container");

    if (chatMessagesContainer && headerMessage && chatInput && chatContainer) {
        const updateHeaderVisibility = () => {
            if (chatMessagesContainer.children.length > 0) {
                headerMessage.classList.add('hidden');
            } else {
                headerMessage.classList.remove('hidden');
            }
        };
        updateHeaderVisibility();

        const observer = new MutationObserver(mutations => {
            updateHeaderVisibility();
        });

        const config = { childList: true };
        observer.observe(chatMessagesContainer, config);
    }
});
