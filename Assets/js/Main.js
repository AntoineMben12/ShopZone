

document.addEventListener('DOMContentLoaded', () => {

  // Auto-dismiss alerts after 5s
  document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 5000);
  });

  // Quantity buttons (+ / −)
  document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const input  = document.querySelector(btn.dataset.target);
      if (!input) return;
      const min    = parseInt(input.min) || 1;
      const max    = parseInt(input.max) || 999;
      let   val    = parseInt(input.value) || 1;
      val = btn.dataset.dir === 'up' ? Math.min(val + 1, max) : Math.max(val - 1, min);
      input.value = val;
    });
  });

  // Active nav link highlight
  const currentPath = window.location.pathname.split('/').pop();
  document.querySelectorAll('.navbar .nav-link').forEach(link => {
    if (link.getAttribute('href') && link.getAttribute('href').includes(currentPath)) {
      link.classList.add('active');
    }
  });

  // Confirm delete dialogs
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

  // Scroll-reveal animation
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.animationPlayState = 'running';
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.fade-up, .fade-up-2, .fade-up-3').forEach(el => {
    el.style.animationPlayState = 'paused';
    observer.observe(el);
  });
});