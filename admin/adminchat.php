
<div class="chat-container">
  <button class="chat-btn" id="chatToggle">💬 Messages</button>

  <div class="chat-box" id="chatBox">
    <div class="chat-header">
      <div class="chat-header-info">
        <h3 class="chat-header-title">Resident Messages</h3>
        <p class="chat-user-info" id="chatUserInfo">Loading...</p>
      </div>
      <button id="closeChat" class="close-btn">&times;</button>
    </div>

    <div class="chat-body">
      <!-- Conversations list on the left -->
      <div class="conversations-panel">
        <div class="conversations-header">
          <h4>Residents</h4>
        </div>
        <div class="conversations-list">
          <p class="loading">Loading conversations...</p>
        </div>
      </div>

      <!-- Messages area on the right -->
      <div class="messages-panel">
        <div class="chat-messages"></div>
      </div>
    </div>

    <div class="chat-footer">
      <textarea 
        id="chatInput" 
        placeholder="Type your message... (Shift+Enter for new line)" 
        class="chat-input"
        rows="2"
      ></textarea>
      <button id="sendChat" class="send-btn">Send</button>
    </div>
  </div>
</div>

<link rel="stylesheet" href="../css/chats.css">
<script src="../js/chat.js"></script>

