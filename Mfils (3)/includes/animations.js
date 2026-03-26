/**
 * ══════════════════════════════════════════════════════════════
 *  MFILLS — ANIMATION ENGINE  v1.0
 *  Drop-in: <script src="/includes/animations.js" defer></script>
 *  Requires: animations.css
 * ══════════════════════════════════════════════════════════════
 */

(function () {
  'use strict';

  /* ── Reduced-motion guard ── */
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ══════════════════════════════════════
     1. SCROLL REVEAL — .rv elements
  ══════════════════════════════════════ */
  function initScrollReveal() {
    var els = document.querySelectorAll('.rv, .rv-stagger-grid');
    if (!els.length) return;

    if (reduced) {
      els.forEach(function (el) {
        el.classList.add('on');
        el.style.opacity = '1';
        el.style.transform = 'none';
      });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('on');
        observer.unobserve(entry.target);
      });
    }, {
      threshold: 0.06,
      rootMargin: '0px 0px -40px 0px'
    });

    els.forEach(function (el) { observer.observe(el); });
  }

  /* ══════════════════════════════════════
     2. NAVBAR SCROLL SHRINK
     Adds .nav-scrolled after 40px scroll
  ══════════════════════════════════════ */
  function initNavbarShrink() {
    var navs = document.querySelectorAll('.nav-landing, .nav-dashboard');
    if (!navs.length) return;

    function tick() {
      var scrolled = window.scrollY > 40;
      navs.forEach(function (nav) {
        nav.classList.toggle('nav-scrolled', scrolled);
      });
    }

    window.addEventListener('scroll', tick, { passive: true });
    tick();
  }

  /* ══════════════════════════════════════
     3. COUNTER TICK-UP
     <span data-count="40" data-suffix="%">0</span>
     Triggers when element scrolls into view
  ══════════════════════════════════════ */
  function initCounters() {
    var counters = document.querySelectorAll('[data-count]');
    if (!counters.length) return;

    if (reduced) {
      counters.forEach(function (el) {
        el.textContent = el.dataset.count + (el.dataset.suffix || '');
      });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        observer.unobserve(entry.target);
        animateCounter(entry.target);
      });
    }, { threshold: 0.5 });

    counters.forEach(function (el) { observer.observe(el); });
  }

  function animateCounter(el) {
    var target   = parseFloat(el.dataset.count);
    var suffix   = el.dataset.suffix  || '';
    var prefix   = el.dataset.prefix  || '';
    var duration = parseInt(el.dataset.duration || 1400);
    var decimals = (el.dataset.count.indexOf('.') !== -1)
      ? el.dataset.count.split('.')[1].length : 0;
    var start    = null;

    function step(ts) {
      if (!start) start = ts;
      var progress = Math.min((ts - start) / duration, 1);
      /* ease out quart */
      var eased = 1 - Math.pow(1 - progress, 4);
      var current = eased * target;
      el.textContent = prefix + current.toFixed(decimals) + suffix;
      if (progress < 1) requestAnimationFrame(step);
    }

    requestAnimationFrame(step);
  }

  /* ══════════════════════════════════════
     4. HERO PARALLAX on scroll
     Shifts hero ::before dot-grid layer at 30% scroll rate
  ══════════════════════════════════════ */
  function initParallax() {
    var heroes = document.querySelectorAll(
      '.about-hero, .blog-hero, .grv-hero, .ct-hero, .page-header, .bd-hero'
    );
    if (!heroes.length || reduced) return;

    function tick() {
      heroes.forEach(function (hero) {
        var rect   = hero.getBoundingClientRect();
        var inView = rect.bottom > 0 && rect.top < window.innerHeight;
        if (!inView) return;
        var offset = -rect.top * 0.28;
        hero.style.setProperty('--parallax-y', offset + 'px');
      });
    }

    /* Apply CSS variable to the texture layer */
    var style = document.createElement('style');
    style.textContent = [
      '.about-hero::before,',
      '.blog-hero::before,',
      '.grv-hero::before,',
      '.ct-hero::before,',
      '.page-header::before {',
      '  transform: translateY(var(--parallax-y, 0));',
      '}'
    ].join('\n');
    document.head.appendChild(style);

    window.addEventListener('scroll', tick, { passive: true });
    tick();
  }

  /* ══════════════════════════════════════
     5. PAGE TRANSITION — fade out on link click
  ══════════════════════════════════════ */
  function initPageTransitions() {
    if (reduced) return;

    document.addEventListener('click', function (e) {
      var link = e.target.closest('a');
      if (!link) return;

      var href = link.getAttribute('href');
      if (!href) return;

      /* Skip: external, anchor-only, target=_blank, js: */
      if (
        href.startsWith('http')   ||
        href.startsWith('//')     ||
        href.startsWith('#')      ||
        href.startsWith('mailto') ||
        href.startsWith('tel')    ||
        href.startsWith('javascript') ||
        link.target === '_blank'  ||
        e.ctrlKey || e.metaKey || e.shiftKey
      ) return;

      e.preventDefault();
      document.body.style.cssText += 'opacity:0;transform:translateY(-6px);transition:opacity 180ms ease,transform 180ms ease;';
      setTimeout(function () {
        window.location.href = href;
      }, 190);
    });
  }

  /* ══════════════════════════════════════
     6. STAT TILES — auto-detect and wire up counters
     Looks for .stat-tile .value or .about-stat-val
     and reads numeric content → converts to data-count
  ══════════════════════════════════════ */
  function initStatTiles() {
    var tiles = document.querySelectorAll(
      '.stat-tile .value, .about-stat-val, .grv-tl-days'
    );

    tiles.forEach(function (el) {
      /* Already manually set? Skip */
      if (el.dataset.count) return;

      var raw    = el.textContent.trim();
      var prefix = raw.match(/^[₹$£€+]/) ? raw.match(/^[₹$£€+]/)[0] : '';
      var suffix = raw.match(/[%+kmKM]$/) ? raw.match(/[%+kmKM]$/)[0] : '';
      var num    = parseFloat(raw.replace(/[^0-9.]/g, ''));

      if (isNaN(num) || num === 0) return;

      el.dataset.count  = num;
      el.dataset.prefix = prefix;
      el.dataset.suffix = suffix;
      el.textContent    = prefix + '0' + suffix;
    });

    /* Re-run counter init to pick up newly set data-count attrs */
    initCounters();
  }

  /* ══════════════════════════════════════
     7. HOVER TILT on product cards
     Subtle 3D perspective tilt on mousemove
  ══════════════════════════════════════ */
  function initCardTilt() {
    if (reduced) return;

    var cards = document.querySelectorAll('.product-card, .about-value-card, .vmp-card');

    cards.forEach(function (card) {
      card.style.transformStyle = 'preserve-3d';
      card.style.perspective    = '800px';

      card.addEventListener('mousemove', function (e) {
        var rect   = card.getBoundingClientRect();
        var cx     = rect.left + rect.width  / 2;
        var cy     = rect.top  + rect.height / 2;
        var dx     = (e.clientX - cx) / (rect.width  / 2);
        var dy     = (e.clientY - cy) / (rect.height / 2);
        var rotX   = (-dy * 4).toFixed(2);
        var rotY   = ( dx * 4).toFixed(2);
        card.style.transform = 'translateY(-5px) rotateX(' + rotX + 'deg) rotateY(' + rotY + 'deg)';
        card.style.transition = 'transform 60ms linear, box-shadow 200ms ease';
      });

      card.addEventListener('mouseleave', function () {
        card.style.transform  = '';
        card.style.transition = 'transform 420ms cubic-bezier(.34,1.56,.64,1), box-shadow 420ms ease';
      });
    });
  }

  /* ══════════════════════════════════════
     8. BLOG READING PROGRESS BAR
     Already in blog-detail.php but unified here
  ══════════════════════════════════════ */
  function initReadingProgress() {
    var bar = document.getElementById('bdProgress');
    if (!bar) return;

    var art = document.getElementById('bdArticle');
    if (!art) return;

    function update() {
      var artTop    = art.getBoundingClientRect().top + window.scrollY;
      var artBottom = artTop + art.offsetHeight;
      var scrolled  = window.scrollY - artTop;
      var total     = artBottom - artTop - window.innerHeight;
      var pct       = Math.min(100, Math.max(0, (scrolled / total) * 100));
      bar.style.width = pct.toFixed(1) + '%';
    }

    window.addEventListener('scroll', update, { passive: true });
    update();
  }

  /* ══════════════════════════════════════
     9. ACTIVE SIDENAV LINKS (shared utility)
     Replaces inline scroll listeners on about/grievance/contact
  ══════════════════════════════════════ */
  function initSidenavHighlight() {
    var sidenavs = document.querySelectorAll(
      '#aboutSidenav, #grvSidenav, #ctSidenav, #blogSidenav'
    );

    sidenavs.forEach(function (nav) {
      var links = nav.querySelectorAll('a[href^="#"]');
      if (!links.length) return;

      var ids = [];
      links.forEach(function (a) {
        ids.push(a.getAttribute('href').slice(1));
      });

      function setActive() {
        var OFF = 110;
        var cur = ids[0];
        ids.forEach(function (id) {
          var el = document.getElementById(id);
          if (el && window.scrollY >= el.getBoundingClientRect().top + window.scrollY - OFF) {
            cur = id;
          }
        });
        links.forEach(function (a) {
          a.classList.toggle('active', a.getAttribute('href') === '#' + cur);
        });
      }

      window.addEventListener('scroll', setActive, { passive: true });
      setActive();
    });
  }

  /* ══════════════════════════════════════
     10. TICKET INPUT — auto uppercase
  ══════════════════════════════════════ */
  function initTicketInput() {
    var input = document.querySelector('.grv-track-input');
    if (input) {
      input.addEventListener('input', function () {
        var pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
      });
    }
  }

  /* ══════════════════════════════════════
     11. SCROLL-TO-TOP BUTTON
  ══════════════════════════════════════ */
  function initScrollTop() {
    var btn = document.getElementById('mfScrollTop') || document.getElementById('bmScrollTop');
    if (!btn) return;
    window.addEventListener('scroll', function () {
      btn.classList.toggle('visible', window.scrollY > 320);
    }, { passive: true });
  }

  /* ══════════════════════════════════════
     INIT ALL
  ══════════════════════════════════════ */
  function init() {
    initScrollReveal();
    initNavbarShrink();
    initStatTiles();   /* must run before initCounters */
    initParallax();
    initPageTransitions();
    initCardTilt();
    initReadingProgress();
    initSidenavHighlight();
    initTicketInput();
    initScrollTop();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();