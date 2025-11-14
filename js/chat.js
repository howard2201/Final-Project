document.addEventListener("DOMContentLoaded", () => {
  const chatToggle = document.getElementById("chatToggle");
  const chatBox = document.getElementById("chatBox");
  const closeChat = document.getElementById("closeChat");

  if (chatToggle && chatBox && closeChat) {

    chatToggle.addEventListener("click", () => {
      chatBox.classList.toggle("active");
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
});
