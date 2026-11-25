

(function () {
  'use strict';

  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const hamburger = document.getElementById('hamburgerBtn');

  function openSidebar() {
    sidebar?.classList.add('open');
    overlay?.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('active');
    document.body.style.overflow = '';
  }

  hamburger?.addEventListener('click', openSidebar);
  overlay?.addEventListener('click', closeSidebar);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });

  document.querySelectorAll('.admin-alert.success').forEach(alert => {
    setTimeout(() => {
      alert.style.opacity = '0';
      alert.style.transition = 'opacity .4s';
      setTimeout(() => alert.remove(), 400);
    }, 5000);
  });

  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm || 'Yakin ingin melanjutkan?')) {
        e.preventDefault();
      }
    });
  });

  document.querySelectorAll('.toggle-input').forEach(input => {
    
    function updateThumb() {
      const thumb = input.parentElement.querySelector('.toggle-thumb');
      if (thumb) {
        thumb.style.left = input.checked ? '23px' : '3px';
      }
    }
    input.addEventListener('change', updateThumb);
    updateThumb();
  });

  document.querySelectorAll('tr[data-href]').forEach(row => {
    row.style.cursor = 'pointer';
    row.addEventListener('click', function () {
      window.location.href = this.dataset.href;
    });
  });

  const titleInput = document.getElementById('post-title');
  if (titleInput) {
    titleInput.addEventListener('input', function () {
      const slugField = document.getElementById('post-slug');
      if (slugField && !slugField.dataset.manual) {
        slugField.value = this.value.toLowerCase()
          .replace(/[^a-z0-9\s-]/g, '')
          .replace(/\s+/g, '-')
          .replace(/-+/g, '-')
          .trim();
      }
    });
    const slugField = document.getElementById('post-slug');
    if (slugField) {
      slugField.addEventListener('input', () => slugField.dataset.manual = '1');
    }
  }

  const tableSearch = document.getElementById('tableSearch');
  if (tableSearch) {
    const tbody = document.querySelector('.admin-table tbody');
    tableSearch.addEventListener('input', function () {
      const q = this.value.toLowerCase();
      tbody?.querySelectorAll('tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  const pwInput = document.querySelector('input[name="password"], input[name="new_password"]');
  if (pwInput) {
    const indicator = document.createElement('div');
    indicator.style.cssText = 'height:4px;border-radius:4px;margin-top:6px;transition:all .3s;background:#e2e8f0';
    pwInput.parentElement.appendChild(indicator);

    const hint = document.createElement('div');
    hint.className = 'admin-input-hint';
    hint.style.marginTop = '4px';
    pwInput.parentElement.appendChild(hint);

    pwInput.addEventListener('input', function () {
      const v = this.value;
      let score = 0;
      if (v.length >= 8) score++;
      if (/[A-Z]/.test(v)) score++;
      if (/[0-9]/.test(v)) score++;
      if (/[^a-zA-Z0-9]/.test(v)) score++;

      const colors = ['#e2e8f0', '#dc2626', '#f97316', '#d4af37', '#16a34a'];
      const labels = ['', 'Sangat Lemah', 'Lemah', 'Sedang', 'Kuat'];
      indicator.style.background = colors[score];
      indicator.style.width = (score * 25) + '%';
      hint.textContent = score > 0 ? 'Kekuatan: ' + labels[score] : '';
      hint.style.color = colors[score];
    });
  }

  if (window.jQuery && $.fn.DataTable) {
    $('.admin-table').each(function() {
      var $table = $(this);
      if (!$.fn.DataTable.isDataTable(this)) {
        $table.DataTable({
          language: {
            search: "Cari Data:",
            searchPlaceholder: "Ketik untuk mencari...",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Belum ada data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            zeroRecords: "Tidak ada data yang cocok",
            paginate: {
              first: "Pertama",
              last: "Terakhir",
              next: "Lanjut",
              previous: "Sebelum"
            }
          },
          pageLength: 10,
          lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
          order: [],
          columnDefs: [
            { orderable: false, targets: "no-sort" }
          ]
        });
      }
    });
  }

  console.log('%c🛡️ Polda Bengkulu Admin', 'font-size:14px;font-weight:bold;color:#1a3a6b');

})();
