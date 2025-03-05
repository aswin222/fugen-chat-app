<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://unpkg.com/tailwindcss@1.9.6/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="h-screen overflow-hidden flex items-center justify-center" style="background: #edf2f7;">
    <div class="flex h-screen antialiased text-gray-800">
        <div class="flex flex-row h-full w-full overflow-x-hidden">
            <div class="flex flex-col py-8 pl-6 pr-2 w-64 bg-white flex-shrink-0">
                <div class="flex flex-row items-center justify-center h-12 w-full">
                    <div class="flex items-center justify-center rounded-2xl text-indigo-700 bg-indigo-100 h-10 w-10">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-2 font-bold text-2xl">Fugen Chat</div>
                </div>

                <div class="flex flex-col mt-8" id="chatContainer">
                    <div class="flex flex-row items-center justify-between text-xs">
                        <span class="font-bold">Active Conversations</span>
                        <span class="flex items-center justify-center bg-gray-300 h-4 w-4 rounded-full"
                            id="messageCount">0</span>
                        <div id="userList"></div>
                    </div>
                </div>

            </div>
            <div class="flex flex-col flex-auto h-full p-6">
                <div class="flex flex-col flex-auto flex-shrink-0 rounded-2xl bg-gray-100 h-full p-4">
                    <div class="flex flex-col h-full overflow-x-auto mb-4">
                        <div class="flex flex-col h-full">
                            <div class="grid grid-cols-12 gap-y-2">
                                <div id="chatContainer"></div>

                            </div>
                        </div>
                    </div>
                    <div class="flex flex-row items-center h-16 rounded-xl bg-white w-full px-4">
                        <div>
                            <button class="flex items-center justify-center text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13">
                                    </path>
                                </svg>
                            </button>
                        </div>
                        <div class="flex-grow ml-4">
                            <div class="relative w-full">
                                <input type="text" id="messageInput"
                                    class="flex w-full border rounded-xl focus:outline-none focus:border-indigo-300 pl-4 h-10" />
                                <button id="sendMessageBtn"
                                    class="absolute flex items-center justify-center h-full w-12 right-0 top-0 text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="ml-4">
                            <button id="sendButton"
                                class="flex items-center justify-center bg-indigo-500 hover:bg-indigo-600 rounded-xl text-white px-4 py-1 flex-shrink-0">
                                <span>Send</span>
                                <span class="ml-2">
                                    <svg class="w-4 h-4 transform rotate-45 -mt-px" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.socket.io/4.0.1/socket.io.min.js"></script>
    <script>
        const socket = io("http://localhost:3000");

        function sendMessage() {
            let message = document.getElementById('messageInput').value;
            socket.emit('sendMessage', {
                message: message
            });
            document.getElementById('messageInput').value = '';
        }

        socket.on('receiveMessage', (data) => {
            let messagesDiv = document.getElementById('messages');
            let newMessage = document.createElement('p');
            newMessage.textContent = data.message;
            messagesDiv.appendChild(newMessage);
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            fetchChatUsers();
        });

        let selectedUserId = null;

        function fetchChatUsers() {
            const authToken = localStorage.getItem('authToken');

            fetch('api/chat-users', {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${authToken}`
                    }
                })
                .then(response => response.json())
                .then(users => {
                    displayChatUsers(users);
                })
                .catch(error => console.error('Error fetching users:', error));
        }

        function displayChatUsers(users) {
            let userList = document.getElementById("userList");
            userList.innerHTML = "";

            users.forEach(user => {
                let userElement = document.createElement("div");
                userElement.classList.add("p-2", "cursor-pointer", "border-b");
                userElement.innerHTML = `<strong>${user.name}</strong>`;

                userElement.onclick = function() {
                    selectedUserId = user.id;
                    fetchMessages(user.id);
                };

                userList.appendChild(userElement);
            });
        }

        function fetchMessages(receiverId) {
            const authToken = localStorage.getItem('authToken');

            fetch('api/messages', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${authToken}`
                    },
                    body: JSON.stringify({
                        receiver_id: receiverId
                    })
                })
                .then(response => response.json())
                .then(messages => {
                    displayMessages(messages);
                })
                .catch(error => console.error('Error fetching messages:', error));
        }

        function displayMessages(messages) {
            let chatContainer = document.getElementById("chatContainer");
            chatContainer.innerHTML = "";

            messages.forEach(message => {
                let alignment = message.sender_id === selectedUserId ? "text-left" : "text-right";
                let bgColor = message.sender_id === selectedUserId ? "bg-gray-200" : "bg-blue-500 text-white";

                let chatMessage = `
            <div class="${alignment} p-2 my-2 ${bgColor} rounded w-max">
                ${message.message}
            </div>
        `;
                chatContainer.innerHTML += chatMessage;
            });
        }

        function sendMessage() {
            const authToken = localStorage.getItem('authToken');
            let messageInput = document.getElementById("messageInput").value;

            if (!selectedUserId || !messageInput) return;

            fetch('api/messages', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${authToken}`
                    },
                    body: JSON.stringify({
                        receiver_id: selectedUserId,
                        message: messageInput
                    })
                })
                .then(response => response.json())
                .then(() => {
                    fetchMessages(selectedUserId);
                    document.getElementById("messageInput").value = "";
                })
                .catch(error => console.error('Error sending message:', error));
        }
    </script>

    <script>
        document.getElementById("sendButton").addEventListener("click", function() {
            let message = document.getElementById("messageInput").value;
            let token = localStorage.getItem("authToken");

            if (!token) {
                alert("User is not authenticated");
                return;
            }

            if (message.trim() === "") {
                alert("Message cannot be empty");
                return;
            }

            fetch("/api/store-messages", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Authorization": "Bearer " + token,
                    },
                    body: JSON.stringify({
                        message: message,
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        console.log("Message sent:", data.message);
                        document.getElementById("messageInput").value = "";
                    } else {
                        alert("Failed to send message");
                    }
                })
                .catch(error => console.error("Error:", error));
        });
    </script>
</body>

</html>
