document.addEventListener("DOMContentLoaded", () => {
  const chatToggle = document.getElementById("chatToggle");
  const chatBox = document.getElementById("chatBox");
  const closeChat = document.getElementById("closeChat");

  if (chatToggle && chatBox && closeChat) {

    chatToggle.addEventListener("click", () => {
      chatBox.style.display = chatBox.style.display === "flex" ? "none" : "flex";
    });

    closeChat.addEventListener("click", () => {
      chatBox.style.display = "none";
    });

    document.addEventListener("click", (e) => {
      if (!chatBox.contains(e.target) && e.target !== chatToggle) {
        chatBox.style.display = "none";
      }
    });
  }
});
