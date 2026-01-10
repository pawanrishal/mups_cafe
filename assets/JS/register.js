// register.js - handles toggling between register and login panels with animation

function showLoginPanel() {
  const wrapper = document.querySelector(".auth-panels");
  if (!wrapper) return;
  wrapper.classList.add("show-login");
}

function showRegisterPanel() {
  const wrapper = document.querySelector(".auth-panels");
  if (!wrapper) return;
  wrapper.classList.remove("show-login");
}

// Optional: allow clicking the login button to animate smoothly before navigation
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".login-button").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      showLoginPanel();
      const href = btn.getAttribute("href");
      setTimeout(() => {
        window.location.href = href;
      }, 600); // match CSS transition duration
    });
  });
});
