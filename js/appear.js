document.addEventListener("DOMContentLoaded", () => { 
  const yearEls = document.querySelectorAll("#year, #year2");
  yearEls.forEach(el => el.textContent = new Date().getFullYear());

  const steps = document.querySelectorAll(".step");
  const progressBar = document.getElementById("progressBar");
  let currentStep = 0;

  function showStep(index) {
    steps.forEach((step, i) => {
      step.style.display = i === index ? "block" : "none";
    });
    if (progressBar) progressBar.style.width = `${((index + 1) / steps.length) * 100}%`;
  }

  const next1 = document.getElementById("next1");
  const next2 = document.getElementById("next2");
  const back2 = document.getElementById("back2");
  const back3 = document.getElementById("back3");
  const summary = document.getElementById("summary");
  const form = document.getElementById("requestForm");

  if (next1) next1.addEventListener("click", () => { currentStep = 1; showStep(currentStep); });
  if (next2) next2.addEventListener("click", () => {
    const requestType = form.requestType.value;
    const details = form.details.value;
    summary.innerHTML = `<p><strong>Request Type:</strong> ${requestType}</p>
                         <p><strong>Details:</strong> ${details}</p>`;
    currentStep = 2;
    showStep(currentStep);
  });
  if (back2) back2.addEventListener("click", () => { currentStep = 0; showStep(currentStep); });
  if (back3) back3.addEventListener("click", () => { currentStep = 1; showStep(currentStep); });

  const modals = document.querySelectorAll(".modal");
  const closeBtns = document.querySelectorAll(".close");

  closeBtns.forEach(btn => {
    btn.addEventListener("click", () => {
      btn.closest(".modal").style.display = "none";
    });
  });

  window.addEventListener("click", e => {
    modals.forEach(modal => {
      if (e.target === modal) modal.style.display = "none";
    });
  });

  const scrollCards = document.querySelector(".scroll-cards");
  if (scrollCards) {
    scrollCards.addEventListener("wheel", e => {
      e.preventDefault();
      scrollCards.scrollLeft += e.deltaY;
    });
  }

  const openHistoryBtn = document.getElementById("openHistory");
  const historyModal = document.getElementById("historyModal");
  const closeHistoryBtn = document.getElementById("closeHistory");

  if (openHistoryBtn && historyModal && closeHistoryBtn) {
    openHistoryBtn.addEventListener("click", () => {
      historyModal.style.display = "flex";
    });

    closeHistoryBtn.addEventListener("click", () => {
      historyModal.style.display = "none";
    });
  }

  showStep(0);
});
