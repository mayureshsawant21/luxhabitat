/* LuxHabitat Mumbai — front-end interactions (vanilla JS, no build step) */
(function () {
  'use strict';

  var doc = document.documentElement;
  var body = document.body;
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var lenis = null;

  /* ---------- Smooth scrolling ---------- */
  if (window.Lenis && !reduced) {
    lenis = new window.Lenis({ duration: 1.15, easing: function (t) { return Math.min(1, 1.001 - Math.pow(2, -10 * t)); }, smoothWheel: true });
    var raf = function (time) { lenis.raf(time); requestAnimationFrame(raf); };
    requestAnimationFrame(raf);
  }
  function scrollToEl(el) {
    var header = $('#siteHeader');
    var subnav = $('#subnav');
    var offset = (subnav ? subnav.offsetHeight : 0) + (header ? 70 : 0) - 1;
    if (lenis) lenis.scrollTo(el, { offset: -offset });
    else window.scrollTo({ top: el.getBoundingClientRect().top + window.pageYOffset - offset, behavior: reduced ? 'auto' : 'smooth' });
  }
  function lockScroll(lock) {
    if (lenis) { lock ? lenis.stop() : lenis.start(); }
    body.style.overflow = lock ? 'hidden' : '';
  }

  /* ---------- Preloader ---------- */
  var seen = false;
  try { seen = !!sessionStorage.getItem('lxSeen'); sessionStorage.setItem('lxSeen', '1'); } catch (e) {}
  var loadedAt = Date.now();
  function reveal() {
    var wait = seen ? 0 : Math.max(0, 1500 - (Date.now() - loadedAt));
    setTimeout(function () {
      body.classList.add('is-loaded');
      $$('[data-split]').forEach(function (el) {
        if (el.getBoundingClientRect().top < window.innerHeight) el.classList.add('is-in');
      });
    }, wait);
  }
  if (document.readyState === 'complete') reveal(); else window.addEventListener('load', reveal);
  setTimeout(function () { body.classList.add('is-loaded'); }, 4000); // safety net

  /* ---------- Header: solid on scroll, hide on scroll-down ---------- */
  var header = $('#siteHeader');
  var lastY = window.pageYOffset;
  function onScroll() {
    var y = window.pageYOffset;
    if (!header) return;
    header.classList.toggle('is-scrolled', y > 40);
    if (!body.classList.contains('menu-open')) {
      var hide = y > lastY && y > 300;
      header.classList.toggle('is-hidden', hide);
      body.classList.toggle('header-visible', !hide && y > 300);
    }
    lastY = y;
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ---------- Fullscreen menu ---------- */
  var toggle = $('#menuToggle');
  var menu = $('#siteMenu');
  function setMenu(open) {
    body.classList.toggle('menu-open', open);
    toggle.setAttribute('aria-expanded', open);
    toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    menu.setAttribute('aria-hidden', !open);
    lockScroll(open);
  }
  if (toggle && menu) {
    toggle.addEventListener('click', function () { setMenu(!body.classList.contains('menu-open')); });
    $$('a', menu).forEach(function (a) { a.addEventListener('click', function () { setMenu(false); }); });
  }

  /* ---------- Sliders (hero + project banners) ---------- */
  $$('[data-slider]').forEach(function (slider) {
    var slides = $$('.hero__slide', slider);
    var section = slider.parentElement;
    var dotsWrap = $('[data-slider-dots]', section);
    var counter = $('[data-slide-current]', section);
    var interval = parseInt(slider.getAttribute('data-interval'), 10) || 6000;
    var idx = 0, timer = null;
    if (slides.length < 2) { if (dotsWrap) dotsWrap.style.display = 'none'; return; }

    var dots = slides.map(function (_, i) {
      var b = document.createElement('button');
      b.type = 'button';
      b.setAttribute('aria-label', 'Show slide ' + (i + 1));
      b.style.setProperty('--interval', interval + 'ms');
      b.addEventListener('click', function () { go(i, true); });
      if (dotsWrap) dotsWrap.appendChild(b);
      return b;
    });

    function go(n, user) {
      slides[idx].classList.remove('is-active');
      idx = (n + slides.length) % slides.length;
      slides[idx].classList.add('is-active');
      dots.forEach(function (d, i) {
        d.classList.remove('is-active');
        d.classList.toggle('is-done', i < idx);
      });
      void dots[idx].offsetWidth; // restart progress animation
      dots[idx].classList.add('is-active');
      if (counter) counter.textContent = (idx + 1 < 10 ? '0' : '') + (idx + 1);
      if (user) restart();
    }
    function restart() { clearInterval(timer); timer = setInterval(function () { go(idx + 1); }, interval); }
    go(0); restart();
    document.addEventListener('visibilitychange', function () { document.hidden ? clearInterval(timer) : restart(); });

    // swipe
    var sx = null;
    section.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
    section.addEventListener('touchend', function (e) {
      if (sx === null) return;
      var dx = e.changedTouches[0].clientX - sx;
      if (Math.abs(dx) > 50) go(idx + (dx < 0 ? 1 : -1), true);
      sx = null;
    });
  });

  /* ---------- Static (GitHub Pages) thank-you page ---------- */
  var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return '&#' + c.charCodeAt(0) + ';'; }); };
  if (window.LX.static && body.classList.contains('page-thanks') && $('.thanks__actions')) {
    var tq = new URLSearchParams(location.search);
    var tp = (window.LX.projects || {})[tq.get('p')];
    var tsrc = tq.get('src') || '';
    var tname = '';
    try { tname = (sessionStorage.getItem('lxName') || '').split(' ')[0]; } catch (e) {}
    var canDl = tp && tq.get('dl') && tp.brochure;
    var h1 = $('.thanks h1');
    if (h1 && $('.thanks__text')) {
      h1.textContent = canDl ? 'Your brochure is ready' : tsrc === 'site-visit' ? 'Your site visit is being arranged' : 'Thank you' + (tname ? ', ' + tname : '');
      if (canDl) $('.thanks__text').textContent = 'Your download of the ' + tp.name + ' e-brochure should begin automatically. If it doesn’t, use the button below.';
      else if (tp) $('.thanks__text').innerHTML = 'Our advisor will call you shortly with details on <strong>' + esc(tp.name) + '</strong>.';
      var acts = '';
      if (canDl) acts += '<a class="btn btn--gold" href="' + esc(tp.brochure) + '" download data-autodl>Download brochure</a>';
      if (tp) acts += '<a class="btn btn--line" href="' + esc(tp.url) + '"><span>Back to ' + esc(tp.name) + '</span></a>';
      acts += '<a class="btn btn--' + (tp ? 'line' : 'dark') + '" href="' + esc(window.LX.base + '/projects/') + '"><span>Explore more projects</span></a>';
      $('.thanks__actions').innerHTML = acts;
    }
  }

  /* ---------- Split headings into animated words ---------- */
  $$('[data-split]').forEach(function (el) {
    var words = el.textContent.trim().split(/\s+/);
    el.innerHTML = words.map(function (w, i) {
      return '<span class="split-line" style="--i:' + i + '"><span>' + w.replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</span></span>';
    }).join(' ');
    el.classList.add('is-split');
  });

  /* ---------- Scroll reveals ---------- */
  var io = 'IntersectionObserver' in window ? new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); }
    });
  }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 }) : null;

  function observeReveals(ctx) {
    $$('[data-reveal], [data-split]', ctx).forEach(function (el) {
      if (el.hasAttribute('data-split') && !body.classList.contains('is-loaded')) {
        // hero titles animate after the preloader; others on scroll
        if (el.getBoundingClientRect().top < window.innerHeight) return;
      }
      if (io && !reduced) io.observe(el); else el.classList.add('is-in');
    });
  }
  observeReveals();

  /* ---------- Count-up stats ---------- */
  $$('[data-count]').forEach(function (el) {
    var raw = el.getAttribute('data-count');
    var m = raw.match(/^([^\d]*)([\d,.]+)(.*)$/);
    if (!m || reduced || !io) return;
    var target = parseFloat(m[2].replace(/,/g, ''));
    var useComma = m[2].indexOf(',') > -1;
    el.textContent = m[1] + '0' + m[3];
    var cio = new IntersectionObserver(function (ents) {
      if (!ents[0].isIntersecting) return;
      cio.disconnect();
      var start = null;
      var step = function (t) {
        if (!start) start = t;
        var p = Math.min(1, (t - start) / 1800);
        var v = Math.round(target * (1 - Math.pow(1 - p, 4)));
        el.textContent = m[1] + (useComma ? v.toLocaleString('en-IN') : v) + m[3];
        if (p < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
    }, { threshold: 0.5 });
    cio.observe(el);
  });

  /* ---------- Custom cursor ---------- */
  var cursor = $('.cursor');
  if (cursor && window.matchMedia('(hover: hover) and (pointer: fine)').matches && !reduced) {
    doc.classList.add('has-cursor');
    var cx = -100, cy = -100, tx = -100, ty = -100;
    window.addEventListener('mousemove', function (e) { tx = e.clientX; ty = e.clientY; }, { passive: true });
    (function loop() {
      cx += (tx - cx) * 0.18; cy += (ty - cy) * 0.18;
      cursor.style.transform = 'translate3d(' + cx + 'px,' + cy + 'px,0)';
      requestAnimationFrame(loop);
    })();
    document.addEventListener('mouseover', function (e) {
      cursor.classList.toggle('is-hover', !!e.target.closest('a, button, select, label.pill, .gal__item'));
    });
  }

  /* ---------- Lead forms ---------- */
  var utm = '';
  try {
    var qs = new URLSearchParams(location.search);
    var u = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid']
      .filter(function (k) { return qs.get(k); }).map(function (k) { return k + '=' + qs.get(k); }).join('&');
    if (u) sessionStorage.setItem('lxUtm', u);
    utm = sessionStorage.getItem('lxUtm') || '';
  } catch (e) {}

  $$('.lead-form').forEach(function (form) {
    var phone = $('input[name="phone"]', form);
    if (phone) phone.addEventListener('input', function () {
      var v = phone.value.replace(/\D/g, '');
      if (v.length > 10 && v.indexOf('91') === 0) v = v.slice(2);
      phone.value = v.slice(0, 10);
    });
    form.addEventListener('submit', function (e) {
      form.querySelector('[name="page_url"]').value = location.href;
      form.querySelector('[name="utm"]').value = utm;
      var ok = true;
      var name = $('input[name="name"]', form);
      var email = $('input[name="email"]', form);
      [[name, name.value.trim().length >= 2], [phone, /^\d{10}$/.test(phone.value)], [email, !email.value || email.checkValidity()]]
        .forEach(function (pair) {
          pair[0].closest('.field').classList.toggle('is-invalid', !pair[1]);
          if (!pair[1]) ok = false;
        });
      var consent = $('input[name="consent"]', form);
      if (consent && !consent.checked) ok = false;
      if (!ok) { e.preventDefault(); var bad = $('.is-invalid input', form); if (bad) bad.focus(); return; }
      var btn = $('button[type="submit"]', form);
      btn.classList.add('is-loading');
      btn.querySelector('span').textContent = 'Submitting…';
      if (window.LX.static) {
        // No server on GitHub Pages: optionally send the lead to a form service, then show the thank-you page
        e.preventDefault();
        var data = {};
        new FormData(form).forEach(function (v, k) { if (k !== 'website') data[k] = v; });
        try { sessionStorage.setItem('lxName', data.name || ''); } catch (err) {}
        var src = data.source || 'enquiry';
        var next = window.LX.thanks + '?src=' + encodeURIComponent(src) +
          (+data.project_id ? '&p=' + data.project_id : '') + (src === 'brochure' ? '&dl=1' : '');
        var endpoint = (window.LX_CONFIG || {}).leadEndpoint;
        var go = function () { location.href = next; };
        if (endpoint) {
          fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(data), keepalive: true })
            .then(go, go);
          setTimeout(go, 4000);
        } else go();
      }
    });
  });

  /* ---------- Enquiry modal ---------- */
  var modal = $('#leadModal');
  var lastFocus = null;
  if (modal) {
    var mForm = $('.lead-form', modal);
    var defaults = { title: $('#leadModalTitle').textContent, text: $('#leadModalText').textContent, button: $('button[type="submit"] span', mForm).textContent };
    var media = $('#leadModalMedia');
    var heroImg = $('.hero__slide img');

    var openModal = function (trigger) {
      lastFocus = trigger;
      var d = trigger.dataset;
      var proj = window.LX.project || null;
      $('#leadModalTitle').textContent = d.title || (proj ? 'Enquire about ' + proj.name : defaults.title);
      $('#leadModalText').textContent = d.text || defaults.text;
      $('#leadModalEyebrow').textContent = proj ? proj.name : 'Private advisory';
      $('button[type="submit"] span', mForm).textContent = d.button || defaults.button;
      mForm.querySelector('[name="source"]').value = d.source || 'popup';
      mForm.querySelector('[name="project_id"]').value = proj ? proj.id : 0;
      mForm.querySelector('[name="project"]').value = proj ? proj.name : '';
      var src = proj ? proj.image : (heroImg ? heroImg.currentSrc || heroImg.src : '');
      media.innerHTML = src ? '<img alt="" src="' + src + '" onerror="this.remove()">' : '';
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      lockScroll(true);
      setTimeout(function () { var f = $('input[name="name"]', mForm); if (f) f.focus({ preventScroll: true }); }, 350);
    };
    var closeModal = function () {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      lockScroll(false);
      if (lastFocus) lastFocus.focus({ preventScroll: true });
    };
    document.addEventListener('click', function (e) {
      var t = e.target.closest('[data-enquire]');
      if (t) { e.preventDefault(); if (body.classList.contains('menu-open')) setMenu(false); openModal(t); }
      if (e.target.closest('[data-close]')) closeModal();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      if (modal.classList.contains('is-open')) closeModal();
      else if (body.classList.contains('menu-open')) setMenu(false);
      else if (body.classList.contains('filters-open')) body.classList.remove('filters-open');
    });
    modal.addEventListener('keydown', function (e) {
      if (e.key !== 'Tab') return;
      var f = $$('button, input, select, textarea, a[href]', modal).filter(function (x) { return !x.disabled && x.offsetParent !== null && x.tabIndex >= 0; });
      if (!f.length) return;
      if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
      else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
    });
    // Optional: gentle auto pop-up once per session on project pages after 25s
    if (window.LX.project) {
      try {
        if (!sessionStorage.getItem('lxAuto')) {
          setTimeout(function () {
            if (!modal.classList.contains('is-open') && !body.classList.contains('menu-open') && !document.activeElement.closest('form')) {
              sessionStorage.setItem('lxAuto', '1');
              var fake = document.createElement('button');
              fake.dataset.source = 'auto-popup';
              fake.dataset.title = 'Get the best offer on ' + window.LX.project.name;
              fake.dataset.text = 'Unlock the latest price sheet, inventory and exclusive channel-partner offers.';
              openModal(fake);
            }
          }, 25000);
        }
      } catch (e) {}
    }
  }

  /* ---------- Hero search: keep the URL clean ---------- */
  $$('form.search').forEach(function (f) {
    f.addEventListener('submit', function () {
      $$('select', f).forEach(function (s) { if (!s.value) s.disabled = true; });
      setTimeout(function () { $$('select', f).forEach(function (s) { s.disabled = false; }); }, 50);
    });
  });

  /* ---------- Listing filters (live, no page reload) ---------- */
  var filters = $('#filters');
  if (filters) {
    var results = $('#results');
    var debounce;
    var update = function (closeDrawer) {
      var params = new URLSearchParams();
      new FormData(filters).forEach(function (v, k) { if (v !== '' && !(k === 'sort' && v === 'featured')) params.append(k, v); });
      var url = filters.getAttribute('action') + (params.toString() ? '?' + params.toString() : '');
      results.classList.add('is-loading');
      fetch(url, { headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.text(); })
        .then(function (html) {
          var next = new DOMParser().parseFromString(html, 'text/html').getElementById('results');
          if (!next) { location.href = url; return; }
          results.innerHTML = next.innerHTML;
          results.classList.remove('is-loading');
          history.replaceState(null, '', url);
          observeReveals(results);
          if (closeDrawer) body.classList.remove('filters-open');
          if (results.getBoundingClientRect().top < 0) scrollToEl(results);
        })
        .catch(function () { location.href = url; });
    };
    if (window.LX.static) update = staticFilter(filters, results);
    filters.addEventListener('change', function () { update(false); });
    document.addEventListener('change', function (e) { if (e.target.matches('[data-sort]')) update(false); });
    var q = $('input[name="q"]', filters);
    if (q) q.addEventListener('input', function () { clearTimeout(debounce); debounce = setTimeout(function () { update(false); }, 450); });
    filters.addEventListener('submit', function (e) { e.preventDefault(); update(true); });
    document.addEventListener('click', function (e) {
      if (e.target.closest('[data-filters-open]')) { body.classList.add('filters-open'); }
      if (e.target.closest('[data-filters-close]')) { body.classList.remove('filters-open'); }
    });
  }

  /* Client-side filtering for the GitHub Pages build (same rules as the PHP version) */
  function staticFilter(form, results) {
    var cards = $$('.card', results);
    var grid = $('.cards', results);
    var BUDGET = { '0-1': [0, 1], '1-2': [1, 2], '2-5': [2, 5], '5-10': [5, 10], '10-0': [10, 0] };
    var CARPET = { '0-500': [0, 500], '500-800': [500, 800], '800-1200': [800, 1200], '1200-2000': [1200, 2000], '2000-0': [2000, 0] };
    var sortSel = $('select[data-sort]');

    // Pre-fill the form from the URL (e.g. coming from the home-page search)
    var qs = new URLSearchParams(location.search);
    $$('input', form).forEach(function (i) {
      var vals = qs.getAll(i.name);
      if (i.type === 'checkbox') i.checked = vals.indexOf(i.value) > -1;
      else if (i.type === 'radio') i.checked = (qs.get(i.name) || '') === i.value;
      else if (i.name === 'q') i.value = qs.get('q') || '';
    });
    if (sortSel && qs.get('sort')) sortSel.value = qs.get('sort');

    function apply(closeDrawer) {
      var fd = new FormData(form);
      var locs = fd.getAll('location[]'), cfgs = fd.getAll('config[]'), sts = fd.getAll('status[]');
      var budget = fd.get('budget') || '', carpet = fd.get('carpet') || '', q = (fd.get('q') || '').trim().toLowerCase();
      var sort = sortSel ? sortSel.value : 'featured';
      var shown = cards.filter(function (c) {
        var d = c.dataset, ok = true;
        if (locs.length && locs.indexOf(d.loc) < 0) ok = false;
        if (ok && sts.length && sts.indexOf(d.status) < 0) ok = false;
        if (ok && cfgs.length) { var mine = d.cfg.split(' '); ok = cfgs.some(function (x) { return mine.indexOf(x) > -1; }); }
        if (ok && BUDGET[budget]) {
          var p = +d.price, b = BUDGET[budget];
          ok = p > 0 && p >= b[0] * 1e7 && (!b[1] || p <= b[1] * 1e7);
        }
        if (ok && CARPET[carpet]) {
          var r = CARPET[carpet];
          ok = d.carpets.split(',').some(function (pair) {
            var ab = pair.split('-'), a = +ab[0], z = +ab[1] || a;
            return (a || z) && z >= r[0] && (!r[1] || a <= r[1]);
          });
        }
        if (ok && q) ok = d.text.indexOf(q) > -1;
        c.hidden = !ok;
        if (ok) c.classList.add('is-in');
        return ok;
      });
      var maxArea = function (c) { return Math.max.apply(null, c.dataset.carpets.split(/[,-]/).map(Number).concat(0)); };
      shown.sort(function (a, b) {
        var A = a.dataset, B = b.dataset;
        if (sort === 'price_asc') return (+A.price || 9e15) - (+B.price || 9e15);
        if (sort === 'price_desc') return +B.price - +A.price;
        if (sort === 'area_desc') return maxArea(b) - maxArea(a);
        if (sort === 'newest') return +B.id - +A.id;
        return (+B.featured - +A.featured) || (+A.order - +B.order);
      }).forEach(function (c) { grid.appendChild(c); });

      $('.results__count', results).innerHTML = '<b>' + shown.length + '</b> project' + (shown.length === 1 ? '' : 's') + ' found';
      $('.empty', results).hidden = shown.length > 0;

      // Active filter chips
      var chips = $('.chips', results), html = '';
      $$('input:checked', form).forEach(function (i) {
        if (!i.value) return;
        html += '<button type="button" class="chip" data-chip-name="' + esc(i.name) + '" data-chip-value="' + esc(i.value) + '">' + esc(i.parentElement.querySelector('span').textContent) + ' <span aria-hidden="true">&times;</span></button>';
      });
      if (q) html += '<button type="button" class="chip" data-chip-name="q">“' + esc(q) + '” <span aria-hidden="true">&times;</span></button>';
      chips.innerHTML = html;
      chips.hidden = !html;
      var n = $$('.chip', chips).length;
      var fb = $('.results__filter-btn', results);
      if (fb) fb.lastChild.textContent = ' Filters' + (n ? ' (' + n + ')' : '');

      // Keep a shareable URL
      var params = new URLSearchParams();
      fd.forEach(function (v, k) { if (v !== '' && k !== 'sort') params.append(k, v); });
      if (sort !== 'featured') params.set('sort', sort);
      history.replaceState(null, '', location.pathname + (params.toString() ? '?' + params : ''));
      if (closeDrawer) body.classList.remove('filters-open');
      if (lenis) lenis.resize();
    }

    results.addEventListener('click', function (e) {
      var chip = e.target.closest('[data-chip-name]');
      if (!chip) return;
      var name = chip.dataset.chipName;
      if (name === 'q') $('input[name="q"]', form).value = '';
      else $$('input', form).forEach(function (i) {
        if (i.name !== name || i.value !== chip.dataset.chipValue) return;
        if (i.type === 'radio') { var any = $('input[name="' + name + '"][value=""]', form); if (any) any.checked = true; }
        else i.checked = false;
      });
      apply(false);
    });
    var clear = $('.filters__reset', form);
    if (clear) clear.addEventListener('click', function (e) { e.preventDefault(); form.reset(); $$('input[type="checkbox"]', form).forEach(function (i) { i.checked = false; }); $$('input[type="radio"][value=""]', form).forEach(function (i) { i.checked = true; }); $('input[name="q"]', form).value = ''; apply(false); });
    var emptyClear = $('.empty a', results);
    if (emptyClear) emptyClear.addEventListener('click', function (e) { e.preventDefault(); clear && clear.click(); });
    apply(false);
    return apply;
  }

  /* ---------- Project page: sub-nav, amenities, lightbox, EMI ---------- */
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href^="#"]');
    if (!a || a.getAttribute('href').length < 2) return;
    var target = document.getElementById(a.getAttribute('href').slice(1));
    if (target) { e.preventDefault(); scrollToEl(target); }
  });

  var subnavLinks = $$('#subnav a');
  if (subnavLinks.length && io) {
    var spy = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        subnavLinks.forEach(function (l) { l.classList.toggle('is-active', l.getAttribute('href') === '#' + en.target.id); });
      });
    }, { rootMargin: '-45% 0px -50% 0px' });
    subnavLinks.forEach(function (l) { var s = document.getElementById(l.getAttribute('href').slice(1)); if (s) spy.observe(s); });
  }

  var amenBtn = $('[data-amen-toggle]');
  if (amenBtn) amenBtn.addEventListener('click', function () {
    var g = $('#amenGrid');
    if (!amenBtn.dataset.label) amenBtn.dataset.label = amenBtn.textContent;
    var collapsed = g.classList.toggle('is-collapsed');
    amenBtn.textContent = collapsed ? amenBtn.dataset.label : 'Show fewer';
    $$('li', g).forEach(function (li) { li.classList.add('is-in'); });
    if (lenis) lenis.resize();
  });

  // Lightbox
  var groups = {};
  $$('[data-lightbox]').forEach(function (a) {
    var g = a.getAttribute('data-lightbox');
    (groups[g] = groups[g] || []).push(a);
  });
  if (Object.keys(groups).length) {
    var lb = document.createElement('div');
    lb.className = 'lightbox';
    lb.setAttribute('role', 'dialog');
    lb.setAttribute('aria-modal', 'true');
    lb.innerHTML = '<img alt=""><button class="lightbox__close" aria-label="Close">&times;</button><button class="lightbox__prev" aria-label="Previous">&larr;</button><button class="lightbox__next" aria-label="Next">&rarr;</button><span class="lightbox__count"></span>';
    body.appendChild(lb);
    var lbImg = $('img', lb), lbCount = $('.lightbox__count', lb), cur = [], ci = 0;
    var show = function (i) {
      ci = (i + cur.length) % cur.length;
      lbImg.style.opacity = 0;
      setTimeout(function () { lbImg.src = cur[ci].href; lbImg.style.opacity = 1; }, 150);
      lbCount.textContent = (ci + 1) + ' / ' + cur.length;
    };
    var closeLb = function () { lb.classList.remove('is-open'); lockScroll(false); };
    Object.keys(groups).forEach(function (g) {
      groups[g].forEach(function (a, i) {
        a.addEventListener('click', function (e) { e.preventDefault(); cur = groups[g]; show(i); lb.classList.add('is-open'); lockScroll(true); });
      });
    });
    $('.lightbox__close', lb).addEventListener('click', closeLb);
    $('.lightbox__prev', lb).addEventListener('click', function () { show(ci - 1); });
    $('.lightbox__next', lb).addEventListener('click', function () { show(ci + 1); });
    lb.addEventListener('click', function (e) { if (e.target === lb) closeLb(); });
    document.addEventListener('keydown', function (e) {
      if (!lb.classList.contains('is-open')) return;
      if (e.key === 'Escape') closeLb();
      if (e.key === 'ArrowLeft') show(ci - 1);
      if (e.key === 'ArrowRight') show(ci + 1);
    });
    var lsx = null;
    lb.addEventListener('touchstart', function (e) { lsx = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', function (e) { if (lsx === null) return; var dx = e.changedTouches[0].clientX - lsx; if (Math.abs(dx) > 50) show(ci + (dx < 0 ? 1 : -1)); lsx = null; });
  }

  // EMI calculator
  var emi = $('[data-emi]');
  if (emi) {
    var fmt = function (n) {
      if (n >= 1e7) return '₹ ' + (n / 1e7).toFixed(2).replace(/\.?0+$/, '') + ' Cr';
      if (n >= 1e5) return '₹ ' + (n / 1e5).toFixed(2).replace(/\.?0+$/, '') + ' L';
      return '₹ ' + Math.round(n).toLocaleString('en-IN');
    };
    var calc = function () {
      var P = +emi.amt.value, r = +emi.rate.value / 1200, n = +emi.yrs.value * 12;
      var E = r ? P * r * Math.pow(1 + r, n) / (Math.pow(1 + r, n) - 1) : P / n;
      $('[data-out="amt"]', emi).textContent = fmt(P);
      $('[data-out="rate"]', emi).textContent = (+emi.rate.value).toFixed(2) + ' %';
      $('[data-out="yrs"]', emi).textContent = emi.yrs.value + ' years';
      $('[data-out="emi"]', emi).textContent = '₹ ' + Math.round(E).toLocaleString('en-IN');
    };
    emi.addEventListener('input', calc);
    calc();
  }

  // Thank-you page: start brochure download automatically
  var auto = $('[data-autodl]');
  if (auto) setTimeout(function () { window.location.href = auto.href; }, 1400);
})();
