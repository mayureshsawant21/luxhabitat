/* LuxHabitat — admin panel interactions */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  /* ---------- Repeaters ---------- */
  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-rep-add]');
    if (add) {
      var tpl = document.getElementById(add.dataset.repAdd);
      var target = document.getElementById(add.dataset.repTarget);
      target.appendChild(tpl.content.cloneNode(true));
      var inputs = $$('input', target);
      var last = target.lastElementChild && $('input, textarea', target.lastElementChild);
      if (last) last.focus();
    }
    var rm = e.target.closest('[data-rep-remove]');
    if (rm) rm.closest('.rep__row').remove();
    var tr = e.target.closest('[data-tile-remove]');
    if (tr) tr.closest('.tile').remove();
  });

  /* ---------- Sortable tiles (drag & drop) ---------- */
  $$('[data-sortable]').forEach(function (box) {
    var dragging = null;
    box.addEventListener('dragstart', function (e) {
      dragging = e.target.closest('.tile');
      if (dragging) { dragging.classList.add('is-dragging'); e.dataTransfer.effectAllowed = 'move'; }
    });
    box.addEventListener('dragend', function () { if (dragging) dragging.classList.remove('is-dragging'); dragging = null; });
    box.addEventListener('dragover', function (e) {
      if (!dragging) return;
      e.preventDefault();
      var over = e.target.closest('.tile');
      if (!over || over === dragging) return;
      var r = over.getBoundingClientRect();
      var after = (e.clientX - r.left) > r.width / 2;
      box.insertBefore(dragging, after ? over.nextSibling : over);
    });
  });

  /* ---------- Image previews ---------- */
  document.addEventListener('change', function (e) {
    var inp = e.target;
    if (inp.matches('[data-preview-input]') && inp.files[0]) {
      var scope = inp.closest('form, .media-grid > div') || document;
      var img = $('img[data-preview="' + inp.name + '"]', inp.closest('.media-grid > div, .banner, form'));
      if (img) img.src = URL.createObjectURL(inp.files[0]);
    }
    if (inp.matches('[data-multi-preview]')) {
      var holder = inp.parentElement.querySelector('.new-previews');
      if (!holder) { holder = document.createElement('div'); holder.className = 'tiles new-previews mt'; inp.parentElement.appendChild(holder); }
      holder.innerHTML = '';
      Array.prototype.forEach.call(inp.files, function (f) {
        var t = document.createElement('div');
        t.className = 'tile tile--new';
        t.innerHTML = '<img alt="">';
        t.firstChild.src = URL.createObjectURL(f);
        holder.appendChild(t);
      });
    }
  });

  /* ---------- Amenities ---------- */
  var amenBox = $('#amenBox');
  if (amenBox) {
    var count = function () { $('#amenCount').textContent = $$('input:checked', amenBox).length + ' selected'; };
    amenBox.addEventListener('change', count);
    $$('[data-amen-all]').forEach(function (b) {
      b.addEventListener('click', function () {
        $$('.cb', amenBox).forEach(function (l) { if (l.style.display !== 'none') $('input', l).checked = b.dataset.amenAll === '1'; });
        count();
      });
    });
    amenBox.addEventListener('click', function (e) {
      var t = e.target.closest('[data-amen-cat]');
      if (!t) return;
      var boxes = $$('input', t.closest('fieldset'));
      var allOn = boxes.every(function (x) { return x.checked; });
      boxes.forEach(function (x) { x.checked = !allOn; });
      count();
    });
    $('#amenSearch').addEventListener('input', function () {
      var q = this.value.trim().toLowerCase();
      $$('.cb', amenBox).forEach(function (l) { l.style.display = !q || l.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none'; });
      $$('fieldset', amenBox).forEach(function (fs) { fs.style.display = $$('.cb', fs).some(function (l) { return l.style.display !== 'none'; }) ? '' : 'none'; });
    });
  }

  /* ---------- Smart fill from e-brochure ---------- */
  var scan = $('#brochureScan');
  if (!scan) return;
  var drop = $('#brochureDrop');
  var statusBox = $('#smartStatus'), bar = $('#smartBar'), msg = $('#smartMsg');
  var form = $('#projectForm');

  ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); }); });
  ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.remove('is-over'); }); });
  drop.addEventListener('drop', function (e) {
    e.preventDefault();
    if (e.dataTransfer.files[0]) handle(e.dataTransfer.files[0]);
  });
  scan.addEventListener('change', function () { if (scan.files[0]) handle(scan.files[0]); });

  function setStatus(text, pct, busy) {
    statusBox.hidden = false;
    msg.textContent = text;
    bar.parentElement.classList.toggle('is-busy', !!busy);
    if (pct != null) bar.style.width = pct + '%';
  }

  function extractText(file) {
    if (!window.pdfjsLib) return Promise.resolve('');
    window.pdfjsLib.GlobalWorkerOptions.workerSrc = window.ADMIN.base + '/assets/vendor/pdf.worker.min.js';
    return file.arrayBuffer().then(function (buf) {
      return window.pdfjsLib.getDocument({ data: buf }).promise;
    }).then(function (pdf) {
      var pages = Math.min(pdf.numPages, 80);
      var chain = Promise.resolve(), out = [];
      for (var i = 1; i <= pages; i++) {
        (function (n) {
          chain = chain.then(function () {
            setStatus('Reading page ' + n + ' of ' + pages + '…', Math.round(n / pages * 45));
            return pdf.getPage(n).then(function (pg) { return pg.getTextContent(); }).then(function (tc) {
              out.push(tc.items.map(function (it) { return it.str + (it.hasEOL ? '\n' : ' '); }).join(''));
            });
          });
        })(i);
      }
      return chain.then(function () { return out.join('\n\n'); });
    }).catch(function () { return ''; });
  }

  function handle(file) {
    if (!/\.pdf$/i.test(file.name) && file.type !== 'application/pdf') { alert('Please choose a PDF brochure.'); return; }
    setStatus('Opening brochure…', 2);
    extractText(file).then(function (text) {
      setStatus('Uploading & analysing the brochure — AI reading can take up to a minute…', 55, true);
      var fd = new FormData();
      fd.append('pdf', file);
      fd.append('text', text);
      return fetch(window.ADMIN.base + '/admin/brochure-extract.php', {
        method: 'POST', body: fd, headers: { 'X-CSRF': window.ADMIN.csrf, 'X-Requested-With': 'fetch' }
      }).then(function (r) {
        return r.text().then(function (t) {
          try { return JSON.parse(t); } catch (e) { throw new Error(r.status === 413 ? 'The PDF is larger than the server upload limit.' : 'Server error (' + r.status + ').'); }
        });
      });
    }).then(function (res) {
      if (!res.ok) throw new Error(res.error || 'Could not read the brochure.');
      var hasData = $('[name="name"]', form).value.trim() !== '';
      var overwrite = !hasData || confirm('Replace the existing details with what was found in the brochure?\n\nOK = replace · Cancel = only fill empty fields');
      var n = fill(res.data, overwrite);
      $('#brochureUploaded').value = res.brochure;
      var note = $('#brochureNote');
      note.hidden = false;
      note.textContent = '📄 ' + file.name + ' will be attached as the e-brochure when you save.';
      setStatus('Done — ' + n + ' fields filled' + (res.engine === 'ai' ? ' with AI' : '') + '. Review the highlighted fields, add images, then save.' + (res.note ? ' (' + res.note + ')' : ''), 100, false);
      var first = $('.autofilled');
      if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }).catch(function (err) {
      setStatus('⚠ ' + err.message, 0, false);
    });
  }

  function mark(el) { el.classList.add('autofilled'); setTimeout(function () { el.classList.remove('autofilled'); }, 15000); }

  function setField(name, value, overwrite) {
    var el = $('[name="' + name + '"]', form);
    if (!el || value === undefined || value === null || value === '' || value === 0) return 0;
    if (!overwrite && el.value.trim() !== '') return 0;
    if (el.tagName === 'SELECT') {
      var opt = $$('option', el).filter(function (o) { return o.value === String(value) || o.textContent === String(value); })[0];
      if (!opt) return 0;
      el.value = opt.value;
    } else {
      el.value = value;
    }
    mark(el);
    return 1;
  }

  function rebuild(repId, tplId, rows, setRow) {
    var rep = document.getElementById(repId);
    $$('.rep__row', rep).forEach(function (r) { r.remove(); });
    rows.forEach(function (row) {
      rep.appendChild(document.getElementById(tplId).content.cloneNode(true));
      setRow(rep.lastElementChild, row);
    });
    mark(rep);
  }

  function repIsEmpty(repId) {
    return $$('#' + repId + ' .rep__row input').every(function (i) { return !i.value.trim() || i.name === 'conn_cat[]'; });
  }

  function fill(d, overwrite) {
    var n = 0;
    ['name', 'developer', 'tagline', 'location', 'city', 'address', 'status', 'possession', 'rera', 'land_parcel', 'towers', 'floors',
      'total_units', 'short_desc', 'overview', 'map_query'].forEach(function (k) { n += setField(k, d[k], overwrite); });
    if (d.highlights && d.highlights.length) n += setField('highlights', d.highlights.join('\n'), overwrite);
    if (d.starting_price_inr > 0) {
      var cr = d.starting_price_inr >= 1e7;
      var amt = cr ? d.starting_price_inr / 1e7 : d.starting_price_inr / 1e5;
      if (setField('price_amount', String(Math.round(amt * 100) / 100), overwrite)) { $('[name="price_unit"]', form).value = cr ? 'cr' : 'l'; n++; }
    }
    if (d.configurations && d.configurations.length && (overwrite || repIsEmpty('cfgRep'))) {
      rebuild('cfgRep', 'cfgTpl', d.configurations, function (row, c) {
        var i = $$('input', row);
        i[0].value = c.type || ''; i[1].value = c.carpet_min || ''; i[2].value = c.carpet_max || '';
      });
      n++;
    }
    if (d.connectivity && d.connectivity.length && (overwrite || repIsEmpty('connRep'))) {
      rebuild('connRep', 'connTpl', d.connectivity, function (row, c) {
        var i = $$('input', row);
        i[0].value = c.category || 'Nearby'; i[1].value = c.place || ''; i[2].value = c.distance || '';
      });
      n++;
    }
    if (d.amenities && d.amenities.length) {
      var want = d.amenities.map(function (a) { return a.toLowerCase(); });
      $$('#amenBox input[type="checkbox"]').forEach(function (cb) {
        if (want.indexOf(cb.value.toLowerCase()) > -1 && !cb.checked) { cb.checked = true; mark(cb.parentElement); }
      });
      $('#amenCount').textContent = $$('#amenBox input:checked').length + ' selected';
      n++;
    }
    if (d.other_amenities && d.other_amenities.length) {
      var ta = $('#amenCustom');
      ta.value = (ta.value ? ta.value + '\n' : '') + d.other_amenities.join('\n');
      mark(ta);
    }
    return n;
  }
})();
