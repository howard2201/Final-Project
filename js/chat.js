document.addEventListener("DOMContentLoaded", () => {
  const chatToggle = document.getElementById("chatToggle");
  const chatBox = document.getElementById("chatBox");
  const closeChat = document.getElementById("closeChat");
  const chatBody = document.querySelector(".chat-body");
  const chatInput = document.getElementById("chatInput");
  const sendButton = document.getElementById("sendChat");
  const conversationsList = document.querySelector(".conversations-list");
  const chatMessages = document.querySelector(".chat-messages");
  const chatUserInfo = document.getElementById("chatUserInfo");

  let currentRecipientId = null;
  let currentRecipientName = null;
  let messageRefreshInterval = null;
  let currentUserName = null;

  // Determine the correct API path based on current location
  function getApiPath() {
    const path = window.location.pathname;
    if (path.includes('/admin/')) {
      return 'api_chat.php';
    } else {
      return '../admin/api_chat.php';
    }
  }

  const apiPath = getApiPath();

  // Fetch and display current user identity
  async function loadUserIdentity() {
    try {
      const response = await fetch(apiPath + "?action=user_info");
      const data = await response.json();
      if (data.success) {
        currentUserName = data.user_name;
        if (chatUserInfo) {
          chatUserInfo.textContent = `Logged in as: ${data.user_name}`;
        }
      }
    } catch (error) {
      console.error("Load user info error:", error);
    }
  }

  // Load user identity when page loads
  loadUserIdentity();

  // Initialize chat toggle
  if (chatToggle && chatBox && closeChat) {
    chatToggle.addEventListener("click", () => {
      chatBox.classList.toggle("active");
      if (chatBox.classList.contains("active")) {
        loadConversations();
      }
    });

    closeChat.addEventListener("click", () => {
      chatBox.classList.remove("active");
    });

    document.addEventListener("click", (e) => {
      if (!chatBox.contains(e.target) && e.target !== chatToggle) {
        chatBox.classList.remove("active");
      }
    });
  }

  // Send message
  if (sendButton && chatInput) {
    sendButton.addEventListener("click", sendMessage);
    chatInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });
  }

  async function sendMessage() {
    if (!currentRecipientId) {
      alert("Please select a conversation");
      return;
    }

    const content = chatInput.value.trim();
    if (!content) return;

    try {
      const response = await fetch(apiPath + "?action=send", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          recipient_id: currentRecipientId,
          content: content,
          reply_to_id: null,
        }),
      });

      if (response.ok) {
        chatInput.value = "";
        loadMessages();
      } else {
        const error = await response.json();
        alert("Error: " + error.message);
      }
    } catch (error) {
      console.error("Send message error:", error);
      alert("Failed to send message");
    }
  }

  async function loadConversations() {
    try {
      const response = await fetch(apiPath + "?action=conversations");
      const data = await response.json();

      if (data.success && conversationsList) {
        conversationsList.innerHTML = "";

        if (data.conversations.length === 0) {
          conversationsList.innerHTML =
            '<p class="no-conversations">No conversations yet</p>';
          return;
        }

        data.conversations.forEach((conv) => {
          const div = document.createElement("div");
          div.className = "conversation-item";
          if (currentRecipientId === conv.id) {
            div.classList.add("active");
          }

          const lastMsg =
            conv.last_message && conv.last_message.length > 50
              ? conv.last_message.substring(0, 50) + "..."
              : conv.last_message || "No messages yet";

          div.innerHTML = `
            <div class="conv-header">
              <strong>${escapeHtml(conv.full_name)}</strong>
              ${conv.unread_count > 0 ? `<span class="badge">${conv.unread_count}</span>` : ""}
            </div>
            <div class="conv-preview">${escapeHtml(lastMsg)}</div>
          `;

          div.addEventListener("click", () => {
            selectConversation(conv.id, conv.full_name);
          });

          conversationsList.appendChild(div);
        });
      }
    } catch (error) {
      console.error("Load conversations error:", error);
    }
  }

  async function selectConversation(recipientId, recipientName) {
    currentRecipientId = recipientId;
    currentRecipientName = recipientName;

    // Update active conversation
    document.querySelectorAll(".conversation-item").forEach((item) => {
      item.classList.remove("active");
    });
    event.currentTarget.classList.add("active");

    // Update chat header
    const header = document.querySelector(".chat-header-title");
    if (header) {
      header.textContent = "Chat with " + recipientName;
    }

    // Load messages
    loadMessages();

    // Set up auto-refresh
    if (messageRefreshInterval) {
      clearInterval(messageRefreshInterval);
    }
    messageRefreshInterval = setInterval(loadMessages, 3000);
  }

  async function loadMessages() {
    if (!currentRecipientId) return;

    try {
      const response = await fetch(
        apiPath + `?action=get&recipient_id=${currentRecipientId}`
      );
      const data = await response.json();

      if (data.success && chatMessages) {
        chatMessages.innerHTML = "";

        data.messages.forEach((msg) => {
          const msgDiv = document.createElement("div");
          msgDiv.className = "message";
          msgDiv.classList.add(msg.sender_type);
          msgDiv.dataset.messageId = msg.id;

          const timestamp = new Date(msg.created_at).toLocaleString();
          const editedIndicator = msg.is_edited
            ? `<span class="edited-indicator">(edited)</span>`
            : "";

          let replyHTML = "";
          if (msg.reply_to_id) {
            replyHTML = `
              <div class="reply-to">
                <strong>Replying to ${escapeHtml(msg.reply_to_sender_name)}:</strong>
                <p>${escapeHtml(msg.reply_to_content)}</p>
              </div>
            `;
          }

          msgDiv.innerHTML = `
            <div class="message-content">
              ${replyHTML}
              <div class="message-text">${escapeHtml(msg.content)}</div>
              <div class="message-footer">
                <span class="timestamp">${timestamp}</span>
                ${editedIndicator}
              </div>
            </div>
            <div class="message-actions">
              <button class="btn-reply" data-reply-to="${msg.id}">Reply</button>
              <button class="btn-edit" data-edit-id="${msg.id}">Edit</button>
              <button class="btn-delete" data-delete-id="${msg.id}">Delete</button>
            </div>
          `;

          // Attach event listeners
          msgDiv
            .querySelector(".btn-reply")
            .addEventListener("click", () =>
              replyToMessage(msg.id, msg.sender_name)
            );
          msgDiv
            .querySelector(".btn-edit")
            .addEventListener("click", () => editMessage(msg.id, msg.content));
          msgDiv
            .querySelector(".btn-delete")
            .addEventListener("click", () => deleteMessage(msg.id));

          chatMessages.appendChild(msgDiv);
        });

        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
      }
    } catch (error) {
      console.error("Load messages error:", error);
    }
  }

  async function editMessage(messageId, currentContent) {
    const newContent = prompt("Edit your message:", currentContent);
    if (newContent === null || newContent.trim() === "") return;

    try {
      const response = await fetch(apiPath + "?action=edit", {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          message_id: messageId,
          content: newContent.trim(),
        }),
      });

      if (response.ok) {
        loadMessages();
      } else {
        const error = await response.json();
        alert("Error: " + error.message);
      }
    } catch (error) {
      console.error("Edit message error:", error);
      alert("Failed to edit message");
    }
  }

  async function deleteMessage(messageId) {
    if (!confirm("Are you sure you want to delete this message?")) return;

    try {
      const response = await fetch(apiPath + "?action=delete", {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          message_id: messageId,
        }),
      });

      if (response.ok) {
        loadMessages();
      } else {
        const error = await response.json();
        alert("Error: " + error.message);
      }
    } catch (error) {
      console.error("Delete message error:", error);
      alert("Failed to delete message");
    }
  }

  function replyToMessage(messageId, senderName) {
    const currentText = chatInput.value;
    chatInput.value =
      (currentText ? currentText + "\n" : "") +
      `@${senderName} (replying to message #${messageId}): `;
    chatInput.focus();
  }

  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }
});
