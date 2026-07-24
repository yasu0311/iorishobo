(function () {
  const root = document.querySelector('[data-hero-slideshow]');

  if (!root) {
    return;
  }

  const slides = Array.from(root.querySelectorAll('[data-hero-slide]'));
  const dots = Array.from(root.querySelectorAll('[data-hero-dot]'));

  if (slides.length < 2) {
    return;
  }

  const intervalMs = 5000;
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let currentIndex = slides.findIndex(function (slide) {
    return slide.classList.contains('is-active');
  });
  let timerId = null;

  if (currentIndex < 0) {
    currentIndex = 0;
  }

  function goTo(index) {
    const nextIndex = (index + slides.length) % slides.length;

    slides.forEach(function (slide, i) {
      const isActive = i === nextIndex;
      slide.classList.toggle('is-active', isActive);
      slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
    });

    dots.forEach(function (dot, i) {
      const isActive = i === nextIndex;
      dot.classList.toggle('is-active', isActive);
      dot.setAttribute('aria-current', isActive ? 'true' : 'false');
    });

    currentIndex = nextIndex;
  }

  function stop() {
    if (timerId !== null) {
      window.clearInterval(timerId);
      timerId = null;
    }
  }

  function start() {
    if (reduceMotion) {
      return;
    }

    stop();
    timerId = window.setInterval(function () {
      goTo(currentIndex + 1);
    }, intervalMs);
  }

  dots.forEach(function (dot, i) {
    dot.addEventListener('click', function () {
      goTo(i);
      start();
    });
  });

  root.addEventListener('mouseenter', stop);
  root.addEventListener('mouseleave', start);
  root.addEventListener('focusin', stop);
  root.addEventListener('focusout', function (event) {
    if (!root.contains(event.relatedTarget)) {
      start();
    }
  });

  goTo(currentIndex);
  start();
})();
