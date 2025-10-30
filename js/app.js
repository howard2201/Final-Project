document.querySelectorAll('#year, #year2').forEach(el => {
  if (el) el.textContent = new Date().getFullYear();
});

if (!localStorage.getItem('sb_announcements')) {
  const ann = [
    { id: 1, title: 'Brgy Clean-up Drive', body: 'Join us this Saturday for a clean up drive at Poblacion.' },
    { id: 2, title: 'Vaccination Schedule', body: 'Free vaccination at the covered court, bring your ID.' }
  ];
  localStorage.setItem('sb_announcements', JSON.stringify(ann));
}

if (!localStorage.getItem('sb_requests')) {
  const reqs = [
    { id: 1001, name: 'Juan Dela Cruz', type: 'Barangay Clearance', status: 'Pending' },
    { id: 1002, name: 'Maria Santos', type: 'Indigency Certificate', status: 'Approved' }
  ];
  localStorage.setItem('sb_requests', JSON.stringify(reqs));
}

const annList = document.getElementById('announcementsList');
if (annList) {
  const anns = JSON.parse(localStorage.getItem('sb_announcements') || '[]');
  annList.innerHTML = anns.map(a => `
    <div class="card">
      <h4>${a.title}</h4>
      <p>${a.body}</p>
    </div>
  `).join('');
}

if (document.getElementById('welcomeUser')) {
  const resident = JSON.parse(localStorage.getItem('resident') || '{}');
  document.getElementById('welcomeUser').textContent = 'Welcome, ' + (resident.name || 'Guest');

  const reqs = JSON.parse(localStorage.getItem('sb_requests') || '[]');
  document.getElementById('statPending').textContent = reqs.filter(r => r.status === 'Pending').length;
  document.getElementById('statApproved').textContent = reqs.filter(r => r.status === 'Approved').length;
  document.getElementById('statRejected').textContent = reqs.filter(r => r.status === 'Rejected').length;

  const list = document.getElementById('requestsList');
  if (list) {
    const all = reqs.map(r => `
      <div class="card">
        <strong>${r.type}</strong>
        <p>${r.name} — <em>${r.status}</em></p>
      </div>
    `).join('');
    list.innerHTML = all || '<p class="muted">You have not submitted any requests.</p>';
  }
}

const requestForm = document.getElementById('requestForm');
if (requestForm) {
  let step = 1;
  const steps = Array.from(document.querySelectorAll('.step'));
  const progressBar = document.getElementById('progressBar');

  function showStep(n) {
    steps.forEach(s => s.style.display = s.dataset.step == n ? 'block' : 'none');
    progressBar.style.width = (n / steps.length * 100) + '%';
  }

  showStep(step);

  document.getElementById('next1').addEventListener('click', () => { step = 2; showStep(step); });
  document.getElementById('back2').addEventListener('click', () => { step = 1; showStep(step); });
  document.getElementById('next2').addEventListener('click', () => {
    const summary = document.getElementById('summary');
    summary.innerHTML = `
      <p><strong>Name:</strong> ${document.getElementById('fullName').value}</p>
      <p><strong>Request:</strong> ${document.getElementById('requestType').value}</p>
      <p><strong>Details:</strong> ${document.getElementById('details').value}</p>
      <p><strong>Uploaded ID:</strong> ${document.getElementById('uploadId').value.split('\\').pop()}</p>
      <p><strong>Residency Certificate:</strong> ${document.getElementById('uploadResidency').value.split('\\').pop()}</p>
    `;
    step = 3;
    showStep(step);
  });
  document.getElementById('back3').addEventListener('click', () => { step = 2; showStep(step); });

  requestForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const reqs = JSON.parse(localStorage.getItem('sb_requests') || '[]');
    const id = Math.floor(10000 + Math.random() * 90000);
    const newReq = {
      id,
      name: document.getElementById('fullName').value,
      type: document.getElementById('requestType').value,
      status: 'Pending',
      details: document.getElementById('details').value,
      idFile: document.getElementById('uploadId').value.split('\\').pop(),
      residencyFile: document.getElementById('uploadResidency').value.split('\\').pop()
    };
    reqs.push(newReq);
    localStorage.setItem('sb_requests', JSON.stringify(reqs));
    alert('Request submitted! Reference ID: ' + id);
    window.location.href = 'dashboard.html';
  });
}
