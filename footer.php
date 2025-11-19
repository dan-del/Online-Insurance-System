</div> 

    <button id="chat-button" onclick="toggleChat()" 
    style="position: fixed; bottom: 30px; right: 30px; z-index: 9999;">
    Chat with Us
    </button>

    <div id="chat-window" style="display:none;">
        <div id="chat-header">Online Insurance Chatbot <span id="close-chat" onclick="toggleChat()">X</span></div>
        <div id="chat-body">
            <div class="message bot">Welcome! I can answer questions about our policies and your account status (if logged in).</div>
        </div>
        <input type="text" id="chat-input" placeholder="Ask a question..." onkeydown="if(event.keyCode===13) sendMessage()">
    </div>

    <script>
        const chatWindow = document.getElementById('chat-window');
        const chatBody = document.getElementById('chat-body');
        const chatInput = document.getElementById('chat-input');

        function toggleChat() {
            chatWindow.style.display = chatWindow.style.display === 'none' ? 'flex' : 'none';
            if (chatWindow.style.display === 'flex') {
                chatInput.focus();
            }
        }

        function addMessage(text, sender) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message ' + sender;
            messageDiv.textContent = text;
            chatBody.appendChild(messageDiv);
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        function sendMessage() {
            const message = chatInput.value.trim();
            if (message === '') return;

            addMessage(message, 'user');
            chatInput.value = '';
            
            chatInput.disabled = true;

            fetch('chatbot_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=' + encodeURIComponent(message)
            })
            .then(response => response.json())
            .then(data => {
                addMessage(data.answer, 'bot');
            })
            .catch(error => {
                console.error('Error fetching chatbot response:', error);
                addMessage("Error connecting to the chat service.", 'bot');
            })
            .finally(() => {
                chatInput.disabled = false;
                chatInput.focus();
            });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>