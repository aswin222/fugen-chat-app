<!DOCTYPE html>
<html>

<head>
    <title>Chat</title>

    <!-- Bootstrap & FontAwesome -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"
        integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css"
        integrity="sha384-B4dIYHKNBt8Bc12p+WXckhzcICo0wtJAoU8YZTY5qE0Id1GSseTk6S+L3BlXeVIU" crossorigin="anonymous">

    <!-- jQuery & Custom Scrollbar -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.js">
    </script>

    <!-- Custom CSS -->
    <link href="{{ asset('chat-css.css') }}" rel="stylesheet" id="chat-css">

    <!-- Bootstrap JS -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.6.2/js/bootstrap.min.js"></script>
</head>

<body>
    <div class="container-fluid h-100">
        <div class="row justify-content-center h-100">
            <div class="col-md-4 col-xl-4 chat">
                <div class="card mb-sm-3 mb-md-0 contacts_card">
                    <div class="card-header">
                        <div class="input-group">
                            <input type="text" placeholder="Search..." class="form-control search">
                            <div class="input-group-prepend">
                                <span class="input-group-text search_btn"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body contacts_body">
                        <ul class="contacts" id="userList">
                        </ul>
                    </div>
                    <div class="card-footer"></div>
                </div>
            </div>

            <div class="col-md-4 col-xl-4 chat" id="messsageBox" style="display: none;">
                <div class="card">
                    <div class="card-header msg_head">
                        <div class="d-flex bd-highlight">
                            <div class="img_cont">
                                <img src="https://static.turbosquid.com/Preview/001292/481/WV/_D.jpg"
                                    class="rounded-circle user_img">
                                <span class="online_icon"></span>
                            </div>
                            <div class="user_info">
                                <span>Chat</span>

                            </div>
                        </div>
                        <span id="action_menu_btn"><i class="fas fa-ellipsis-v"></i></span>
                        <div class="action_menu">

                        </div>
                    </div>

                    <div class="card-body msg_card_body">
                        <div class="d-flex justify-content-start mb-4">
                            <div class="img_cont_msg">
                                <img src="https://static.turbosquid.com/Preview/001292/481/WV/_D.jpg"
                                    class="rounded-circle user_img_msg">
                            </div>
                            <div class="msg_cotainer">

                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="input-group">
                            <input type="text" id="messageInput" class="form-control type_msg"
                                placeholder="Type your message...">
                            <div class="input-group-append">
                                <input type="file" id="fileInput" style="display: none;">

                                <button id="pinDocumentButton" class="form-control-text pin_btn" title="Pin Document">
                                    <i class="fas fa-paperclip"></i>
                                </button>

                                <button id="sendButton" class="input-group-text send_btn">
                                    <i class="fas fa-location-arrow"></i>
                                </button>
                            </div>
                        </div>

                        <div id="fileNameContainer" style="margin-top: 5px; display: none;">
                            <span id="fileName" style="font-weight: bold;"></span>
                            <button id="removeFile"
                                style="border: none; background: transparent; color: red; cursor: pointer;">✖</button>
                        </div>

                    </div>


                </div>
            </div>

            <div class="col-md-4 col-xl-4" id="notificationContainer">
                <span id="notificationBadge" class="badge badge-danger" style="display:none;"></span>
            </div>
        </div>
    </div>
</body>

<script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.0.1/socket.io.js"></script>
<script>
    const socket = io("http://localhost:3000");
    const userData = localStorage.getItem("user");

    let userId = null;
    let selectedReceiverId = null;

    try {
        const user = userData ? JSON.parse(userData) : null;
        userId = user && user.id ? user.id : null;
    } catch (error) {
        console.error("Error parsing JSON:", error);
        localStorage.removeItem("user");
    }

    document.getElementById("pinDocumentButton").addEventListener("click", function() {
        document.getElementById("fileInput").click();
    });


    document.getElementById("fileInput").addEventListener("change", function(event) {
        let file = event.target.files[0];
        if (file) {
            document.getElementById("fileName").innerText = file.name;
            document.getElementById("fileNameContainer").style.display = "block";
        }
    });

    // Remove file
    document.getElementById("removeFile").addEventListener("click", function() {
        document.getElementById("fileInput").value = ""; // Clear the file input
        document.getElementById("fileNameContainer").style.display = "none";
    });


    $(document).ready(function() {
        if (!userId) {
            console.error("User ID is not available.");
            return;
        }

        socket.emit("join_room", userId);

        getUsers();
        fetchNotifications();

        $("#sendButton").click(function() {
            sendMessage();
        });

        $("#messageInput").keypress(function(event) {
            if (event.which === 13) {
                sendMessage();
            }
        });

        socket.on("receive_message", function(data) {

            if (data.receiver_id === userId) {
                if (selectedReceiverId === data.sender_id) {
                    appendMessage(data.message, "received", "unread");
                    markMessageAsRead(data.sender_id);
                } else {
                    appendMessage(data.message, "received", "unread");
                }
            }
        });

        socket.on("new_notification", function(notification) {

            if ("Notification" in window && Notification.permission === "granted") {
                new Notification(`New message from ${notification.sender_name}`, {
                    body: notification.message
                });
            }

            fetchNotifications();
        });

        socket.on('messages_read', (data) => {
            if (data.sender_id === userId && data.receiver_id === selectedReceiverId) {
                $('.msg_cotainer_send').addClass('read-message');
                $('.read-status').html('✓✓');
            }
        });
    });

    function fetchNotifications() {
        const authToken = localStorage.getItem('authToken');

        fetch(`/api/notifications?user_id=${userId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${authToken}`
                }
            })
            .then(response => response.json())
            .then(notifications => {
                const notificationContainer = $("#notificationContainer");
                notificationContainer.empty();

                if (notifications.length > 0) {
                    $("#notificationBadge").text(notifications.length).show();

                    notifications.forEach(notification => {
                        const notificationItem = $(`
                                <div class="notification-item" data-sender-id="${notification.sender_id}">
                                    <img src="${notification.sender_avatar ? notification.sender_avatar : 'https://static.turbosquid.com/Preview/001292/481/WV/_D.jpg'}" 
                                        class="notification-avatar" alt="User Avatar">
                                    <div class="notification-content">
                                        <strong>${notification.sender_name}</strong>
                                        <small>${new Date(notification.created_at).toLocaleString()}</small>
                                    </div>
                                </div>
                            `);

                        notificationItem.click(function() {
                            const senderId = $(this).data('sender-id');
                            $(".user-item").removeClass("active");
                            $(`.user-item[data-id="${senderId}"]`).addClass("active");

                            selectedReceiverId = senderId;
                            fetchMessages(senderId);

                            markNotificationsAsRead(senderId);
                        });

                        notificationContainer.append(notificationItem);
                    });
                } else {
                    $("#notificationBadge").hide();
                }
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    socket.on("notifications_marked_read", function(data) {
        fetchNotifications();
    });

    function markNotificationsAsRead(senderId) {
        socket.emit("mark_notifications_read", {
            user_id: userId,
            sender_id: senderId
        });
    }

    if ("Notification" in window) {
        Notification.requestPermission();
    }

    function sendMessage() {
        const messageInput = document.getElementById("messageInput");
        const fileInput = document.getElementById("fileInput");
        const message = messageInput.value.trim();

        if (!message && !fileInput.files.length) return;

        const formData = new FormData();
        formData.append("sender_id", userId);
        formData.append("receiver_id", selectedReceiverId);
        formData.append("message", message);

        if (fileInput.files.length > 0) {
            formData.append("file", fileInput.files[0]);
        }
        const authToken = localStorage.getItem('authToken');
        fetch("api/send-message", {
                method: "POST",
                headers: {
                    'Authorization': `Bearer ${authToken}`
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    socket.emit("send_message", {
                        sender_id: userId,
                        receiver_id: selectedReceiverId,
                        message: message,
                        message_id: data.new_message.id
                    });

                    appendMessage(message, "sent", "unread");

                    messageInput.value = "";
                    fileInput.value = "";
                } else {
                    console.error("Message sending failed:", data.message);
                }
            })
            .catch(error => {
                console.error("Error:", error);
            });

    }


    async function getUsers() {
        const authToken = localStorage.getItem('authToken');

        try {
            const response = await fetch('/api/users', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${authToken}`
                }
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch users: ${response.status} ${response.statusText}`);
            }

            const users = await response.json();
            const userList = $("#userList");
            userList.empty();

            users.forEach(user => {
                const userElement = $(`
                    <li class="user-item" data-id="${user.id}">
                        <div class="d-flex bd-highlight">
                            <div class="img_cont">
                               <img src="https://static.turbosquid.com/Preview/001292/481/WV/_D.jpg"
                                    class="rounded-circle user_img">
                                <span class="${user.is_online ? 'online_icon' : 'offline_icon'}"></span>
                            </div>
                            <div class="user_info">
                                <span>${user.name}</span>
                                <p>${user.is_online ? 'Online' : 'Offline'}</p>
                            </div>
                        </div>
                    </li>
                `);

                userElement.click(function() {
                    $(".user-item").removeClass("active");
                    $(this).addClass("active");

                    selectedReceiverId = $(this).data("id");
                    fetchMessages(selectedReceiverId);
                });

                userList.append(userElement);
            });
        } catch (error) {
            console.error('Error fetching users:', error);
        }
    }

    function fetchMessages(receiverId) {
        selectedReceiverId = receiverId;
        const authToken = localStorage.getItem('authToken');

        fetch('/api/messages', {
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
                $("#messsageBox").show();
                $(".msg_card_body").empty();
                messages.forEach(msg => {
                    const messageType = msg.sender_id === userId ? "sent" : "received";
                    const readStatus = msg.is_read ? "read" : "unread";
                    appendMessage(msg.message, messageType, readStatus, msg.file_url);
                });

                markMessageAsRead(receiverId);
            })
            .catch(error => console.error('Error fetching messages:', error));
    }


    function markMessageAsRead(senderId) {

        socket.emit('mark_messages_read', {
            sender_id: senderId,
            receiver_id: userId
        });
    }

    $(document).on('click', '.notification-item', function() {
        const senderId = $(this).data('sender-id');
        $(".user-item").removeClass("active");
        $(`.user-item[data-id="${senderId}"]`).addClass("active");

        selectedReceiverId = senderId;
        fetchMessages(senderId);

        markNotificationsAsRead(senderId);
    });

    function appendMessage(message, type, readStatus = 'unread', fileUrl = null) {
        let fileHtml = '';

        if (fileUrl) {
            if (fileUrl.match(/\.(jpeg|jpg|png|gif)$/)) {
                fileHtml = `<img src="${fileUrl}" alt="Image" class="chat-image">`;
            } else {
                fileHtml = `<a href="${fileUrl}" target="_blank" class="chat-file-link">Download File</a>`;
            }
        }

        const messageHtml = `
                <div class="d-flex justify-content-${type === "sent" ? "end" : "start"} mb-4">
                    <div class="msg_cotainer ${type === "sent" ? "msg_cotainer_send" : ""} ${readStatus === 'read' ? 'read-message' : 'unread-message'}">
                        ${message || ""}
                        ${fileHtml}
                        <span class="msg_time">
                            ${new Date().toLocaleTimeString()}
                            ${type === 'sent' ? 
                                `<span class="read-status">${readStatus === 'read' ? '✓✓' : '✓'}</span>` : 
                                ''
                            }
                        </span>
                    </div>
                </div>`;

        $(".msg_card_body").append(messageHtml);
        $(".msg_card_body").scrollTop($(".msg_card_body")[0].scrollHeight);
    }
</script>

</html>
