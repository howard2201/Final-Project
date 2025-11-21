/**
 * User-Friendly Alert System
 * Shows beautiful alerts for success/error messages
 */

// Show success alert
function showSuccessAlert(message, title = '✓ Success!') {
  showAlert(message, title, 'success');
}

// Show error alert
function showErrorAlert(message, title = '⚠️ Error') {
  showAlert(message, title, 'error');
}

// Show info alert
function showInfoAlert(message, title = 'ℹ️ Information') {
  showAlert(message, title, 'info');
}

// Show warning alert
function showWarningAlert(message, title = '⚠️ Warning') {
  showAlert(message, title, 'warning');
}

// Generic alert function
function showAlert(message, title, type) {
  // Create alert element
  const alert = document.createElement('div');
  alert.className = `custom-alert custom-alert-${type}`;
  alert.innerHTML = `
    <div class="custom-alert-content">
      <div class="custom-alert-header">
        <strong>${title}</strong>
        <button class="custom-alert-close" onclick="this.parentElement.parentElement.parentElement.remove()">×</button>
      </div>
      <div class="custom-alert-body">${message}</div>
    </div>
  `;

  // Add to body
  document.body.appendChild(alert);

  // Animate in
  setTimeout(() => {
    alert.classList.add('show');
  }, 10);

  // Auto remove after 5 seconds
  setTimeout(() => {
    alert.classList.remove('show');
    setTimeout(() => {
      alert.remove();
    }, 300);
  }, 5000);
}

// Check for session messages on page load
document.addEventListener('DOMContentLoaded', function() {
  // Check for success message
  const successMessage = document.querySelector('[data-success-message]');
  if (successMessage) {
    const message = successMessage.getAttribute('data-success-message');
    showSuccessAlert(message);
  }

  // Check for error message
  const errorMessage = document.querySelector('[data-error-message]');
  if (errorMessage) {
    const message = errorMessage.getAttribute('data-error-message');
    showErrorAlert(message);
  }

  // Check for info message
  const infoMessage = document.querySelector('[data-info-message]');
  if (infoMessage) {
    const message = infoMessage.getAttribute('data-info-message');
    showInfoAlert(message);
  }

  // Check for login success
  const loginSuccess = document.querySelector('[data-login-success]');
  if (loginSuccess) {
    const userName = loginSuccess.getAttribute('data-user-name') || 'User';
    showSuccessAlert(`Welcome back, ${userName}! You have successfully logged in.`);
  }
});

// Confirmation dialog with custom styling
function confirmAction(message, onConfirm, title = 'Confirm Action') {
  if (confirm(message)) {
    if (typeof onConfirm === 'function') {
      onConfirm();
    }
    return true;
  }
  return false;
}

