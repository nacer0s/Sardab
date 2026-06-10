(function () {
  'use strict';

  var navBar = document.getElementById('nav-bar');
  if (navBar) {
    var onScroll = function () {
      navBar.classList.toggle('is-scrolled', window.scrollY > 20);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  var revealEls = document.querySelectorAll('.reveal, .reveal-up, .stagger-children');
  if (revealEls.length && 'IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -60px 0px' });
    for (var i = 0; i < revealEls.length; i++) {
      observer.observe(revealEls[i]);
    }
  }

  document.querySelectorAll('.faq-question').forEach(function (btn) {
    btn.addEventListener('click', function () {
      btn.parentElement.classList.toggle('is-open');
    });
  });

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-copy]');
    if (!btn) return;
    var text = btn.dataset.copy;
    if (!text) return;

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
        btn.disabled = true;
        setTimeout(function () { btn.innerHTML = orig; btn.disabled = false; }, 2000);
      });
    } else {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.style.position = 'fixed';
      ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); } catch (e) {}
      document.body.removeChild(ta);
      var orig = btn.innerHTML;
      btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
      setTimeout(function () { btn.innerHTML = orig; }, 2000);
    }
  });

  document.querySelectorAll('.p2p-mode-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var parent = btn.closest('.p2p-mode-selector');
      if (parent) {
        parent.querySelectorAll('.p2p-mode-btn').forEach(function (b) {
          b.classList.remove('active');
          b.setAttribute('aria-selected', 'false');
        });
      }
      btn.classList.add('active');
      btn.setAttribute('aria-selected', 'true');

      var mode = btn.dataset.p2pMode;
      var sendPanel = document.getElementById('p2p-panel-send');
      var recvPanel = document.getElementById('p2p-panel-recv');
      if (mode === 'send') {
        if (sendPanel) sendPanel.classList.remove('p2p-panel-hidden');
        if (recvPanel) recvPanel.classList.add('p2p-panel-hidden');
      } else {
        if (sendPanel) sendPanel.classList.add('p2p-panel-hidden');
        if (recvPanel) recvPanel.classList.remove('p2p-panel-hidden');
      }
    });
  });

  window.showToast = function (message, type) {
    type = type || 'info';
    var container = document.querySelector('.toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    var toast = document.createElement('div');
    toast.className = 'toast is-' + type;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(function () {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      toast.style.transition = 'all 0.3s ease-out';
      setTimeout(function () { toast.remove(); }, 300);
    }, 3000);
  };

})();