(function () {
  const toggle = document.querySelector('[data-site-nav-toggle]');
  const drawer = document.querySelector('[data-site-nav-drawer]');
  const mask = document.querySelector('[data-site-nav-mask]');

  if (!toggle || !drawer || !mask) {
    return;
  }

  function closeNav() {
    toggle.classList.remove('is-open');
    drawer.classList.remove('is-open');
    mask.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  function openNav() {
    toggle.classList.add('is-open');
    drawer.classList.add('is-open');
    mask.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }

  toggle.addEventListener('click', function () {
    if (drawer.classList.contains('is-open')) {
      closeNav();
    } else {
      openNav();
    }
  });

  mask.addEventListener('click', closeNav);

  drawer.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', closeNav);
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth > 768) {
      closeNav();
    }
  });
})();
