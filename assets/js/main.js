

(function () {
  'use strict';

  const navbar = document.getElementById('mainNavbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 50);
    }, { passive: true });
  }

  const sections = document.querySelectorAll('section[id], header[id]');
  const navLinks = document.querySelectorAll('.nav-link[href^="#"]');

  function setActiveNav() {
    let current = '';
    sections.forEach(section => {
      const top = section.offsetTop - 100;
      if (window.scrollY >= top) current = section.id;
    });
    navLinks.forEach(link => {
      link.classList.toggle('active', link.getAttribute('href') === '#' + current);
    });
  }
  window.addEventListener('scroll', setActiveNav, { passive: true });

  const revealElements = document.querySelectorAll('.reveal');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -30px 0px' });

  revealElements.forEach(el => observer.observe(el));

  const filterTabs = document.querySelectorAll('.satker-filter-tab');
  const satkerItems = document.querySelectorAll('.satker-item');
  const satkerSearch = document.getElementById('satkerSearch');

  function filterSatker() {
    const activeFilter = document.querySelector('.satker-filter-tab.active')?.dataset.filter || 'all';
    const searchQuery = satkerSearch ? satkerSearch.value.toLowerCase().trim() : '';

    let visibleCount = 0;
    satkerItems.forEach(item => {
      const typeMatch = activeFilter === 'all' || item.dataset.type === activeFilter;
      const nameMatch = !searchQuery || item.dataset.name.includes(searchQuery);
      const visible = typeMatch && nameMatch;
      item.style.display = visible ? '' : 'none';
      if (visible) visibleCount++;
    });

    const container = document.getElementById('satkerContainer');
    let emptyState = document.getElementById('satkerEmpty');
    if (visibleCount === 0) {
      if (!emptyState) {
        emptyState = document.createElement('div');
        emptyState.id = 'satkerEmpty';
        emptyState.className = 'w-100 text-center py-5 text-muted';
        emptyState.innerHTML = '<i class="bi bi-search fs-2 d-block mb-2 opacity-25"></i><p>Tidak ada satker yang cocok dengan pencarian.</p>';
        container.appendChild(emptyState);
      }
      emptyState.style.display = '';
    } else if (emptyState) {
      emptyState.style.display = 'none';
    }
  }

  filterTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      filterTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      filterSatker();
    });
  });

  if (satkerSearch) {
    satkerSearch.addEventListener('input', filterSatker);
  }

  window.openLightbox = function (src, caption) {
    const modal = document.getElementById('lightboxModal');
    const img = document.getElementById('lightboxImg');
    const cap = document.getElementById('lightboxCaption');
    if (modal && img) {
      img.src = src;
      img.alt = caption || '';
      if (cap) cap.textContent = caption || '';
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }
  };

  window.closeLightbox = function () {
    const modal = document.getElementById('lightboxModal');
    if (modal) {
      modal.style.display = 'none';
      document.body.style.overflow = '';
    }
  };

  const lightboxModal = document.getElementById('lightboxModal');
  if (lightboxModal) {
    lightboxModal.addEventListener('click', function (e) {
      if (e.target === this) window.closeLightbox();
    });
  }

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') window.closeLightbox();
  });

  const mobileNavLinks = document.querySelectorAll('.mobile-bottom-nav a');
  mobileNavLinks.forEach(link => {
    link.addEventListener('click', () => {
      mobileNavLinks.forEach(l => l.classList.remove('active'));
      link.classList.add('active');
    });
  });

  function animateCounter(el, target, duration = 1500) {
    const isDecimal = target.toString().includes('.');
    const isAlpha = /[a-zA-Z+]/.test(target);
    if (isAlpha) return;

    const numTarget = parseFloat(target.toString().replace(/[^0-9.]/g, ''));
    const suffix = target.toString().replace(/[0-9.]/g, '');
    let start = null;

    function step(timestamp) {
      if (!start) start = timestamp;
      const progress = Math.min((timestamp - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      const current = Math.floor(eased * numTarget);
      el.textContent = current + suffix;
      if (progress < 1) requestAnimationFrame(step);
      else el.textContent = numTarget + suffix;
    }
    requestAnimationFrame(step);
  }

  const statNums = document.querySelectorAll('.hero-stat .num');
  let statsAnimated = false;
  const heroStatObserver = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting && !statsAnimated) {
      statsAnimated = true;
      statNums.forEach(el => animateCounter(el, el.textContent.trim()));
    }
  }, { threshold: 0.5 });
  if (statNums.length) heroStatObserver.observe(statNums[0]);

  document.querySelectorAll('.kapolda-photo').forEach(img => {
    img.addEventListener('contextmenu', e => e.preventDefault());
  });

  console.log('%c🛡️ Polda Bengkulu', 'font-size:18px;font-weight:bold;color:#1a3a6b');
  console.log('%cWebsite Resmi Kepolisian Daerah Bengkulu', 'color:#64748b');
  console.log('%cJika Anda menemukan kerentanan keamanan, harap laporkan ke: humas@poldabengkulu.go.id', 'color:#d4af37');

})();
