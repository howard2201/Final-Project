(function() {
  const tbody = document.getElementById('requestsTbody');
  const logoutBtn = document.getElementById('logoutAdmin');

  const isAdmin = localStorage.getItem('admin');
  if (!isAdmin) {
    alert('Access denied. Please log in as Admin.');
    window.location.href = 'login.html';
    return;
  }

  function renderRequests() {
    const reqs = JSON.parse(localStorage.getItem('sb_requests') || '[]');
    if (!reqs.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="muted">No requests found.</td></tr>';
      return;
    }

    tbody.innerHTML = reqs.map(r => `
      <tr>
        <td>${r.id}</td>
        <td>${r.name}</td>
        <td>${r.type}</td>
        <td>${r.status}</td>
        <td>
          <button data-id="${r.id}" class="btn approve">Approve</button>
          <button data-id="${r.id}" class="btn outline reject">Reject</button>
        </td>
      </tr>
    `).join('');

    document.querySelectorAll('.approve').forEach(btn =>
      btn.addEventListener('click', () => updateStatus(btn.dataset.id, 'Approved'))
    );
    document.querySelectorAll('.reject').forEach(btn =>
      btn.addEventListener('click', () => updateStatus(btn.dataset.id, 'Rejected'))
    );
  }

  function updateStatus(id, status) {
    const reqs = JSON.parse(localStorage.getItem('sb_requests') || '[]');
    const index = reqs.findIndex(r => String(r.id) === String(id));
    if (index > -1) {
      reqs[index].status = status;
      localStorage.setItem('sb_requests', JSON.stringify(reqs));
      alert('Request #' + id + ' marked as ' + status);
      renderRequests();
    }
  }

  logoutBtn.addEventListener('click', () => {
    const confirmLogout = confirm('Are you sure you want to logout?');
    if (confirmLogout) {
      localStorage.removeItem('admin');
      alert('Logged out successfully.');
      window.location.href = 'login.html';
    }
  });

  renderRequests();
})();
