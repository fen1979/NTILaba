// Отправка сообщения
function sendMessage() {
    let message = document.getElementById('msg').value;
    let receiverId = document.querySelector('input[name="user"]:checked').value;

    $.ajax({
        url: '/chat-handler',
        type: 'POST',
        data: {
            action: 'sendMessage',
            message: message,
            receiver_id: receiverId,
            user_id: dom.e("#uid").value
        },
        success: function (response) {
            let result = JSON.parse(response);
            if (result.status === 'success') {
                loadMessages(receiverId);
                document.getElementById('msg').value = ''; // Очистка текстового поля
                dom.hide("#loading");
            } else {
                alert(result.message);
            }
        }
    });
}

// Загрузка сообщений
function loadMessages(receiverId) {
    let uid = dom.e("#uid").value;
    $.ajax({
        url: '/chat-handler',
        type: 'POST',
        data: {
            action: 'getMessages',
            receiver_id: receiverId,
            user_id: uid
        },
        success: function (response) {
            let messages = JSON.parse(response);
            let msgArea = document.getElementById('msg_area');
            msgArea.innerHTML = ''; // Очистка области сообщений
            messages.forEach(msg => {
                msgArea.innerHTML += `
                    <p data-id="${msg.id}" class="chat-message" oncontextmenu="deleteMessage(${msg.id}); return false;">
                        <strong>${msg.sender_id === uid ? 'You' : msg.sender_id}:</strong>
                        ${msg.message}
                        <span class="text-muted fs-s">${msg.created_at}</span>
                    </p>`;
            });
        }
    });
}

// Удаление сообщения по ID
function deleteMessage(messageId) {
    if (confirm('Are you sure you want to delete this message?')) {
        $.ajax({
            url: '/chat-handler',
            type: 'POST',
            data: {
                action: 'deleteMessage',
                message_id: messageId,
                user_id: dom.e("#uid").value
            },
            success: function (response) {
                let result = JSON.parse(response);
                if (result.status === 'success') {
                    loadMessages(document.querySelector('input[name="user"]:checked').value); // Обновляем сообщения
                } else {
                    alert(result.message);
                }
            }
        });
    }
}

// Проверка новых сообщений (например, каждые 10 секунд)
setInterval(function () {
    $.ajax({
        url: '/chat-handler',
        type: 'POST',
        data: {
            action: 'checkNewMessages',
            user_id: dom.e("#uid").value
        },
        success: function (response) {
            console.log(response)
            let result = JSON.parse(response);
            if (result.count > 0) {
                dom.show("#msg_count");
                dom.e("#msg_count").textContent = result.count;
            }
        }
    });
}, 10000);
