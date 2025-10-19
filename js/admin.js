(function() {
  const tbody = document.getElementById('requestsTbody');

  function render() {
    const reqs = JSON.parse(localStorage.getItem('sb_requests') || '[]');
    if (!tbody) return;
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

    tbody.querySelectorAll('.approve').forEach(btn =>
      btn.addEventListener('click', () => updateStatus(btn.dataset.id, 'Approved'))
    );
    tbody.querySelectorAll('.reject').forEach(btn =>
      btn.addEventListener('click', () => updateStatus(btn.dataset.id, 'Rejected'))
    );
  }

  function updateStatus(id, status) {
    const reqs = JSON.parse(localStorage.getItem('sb_requests') || '[]');
    const idx = reqs.findIndex(r => String(r.id) === String(id));
    if (idx > -1) {
      reqs[idx].status = status;
      localStorage.setItem('sb_requests', JSON.stringify(reqs));
      render();
      alert('Status updated');
    }
  }

  render();
})();
