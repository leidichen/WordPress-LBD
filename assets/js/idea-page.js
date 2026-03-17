(function () {
  function toggleIdeaContent(element) {
    var cardContainer = element.closest('.idea-card-container');
    if (!cardContainer) return;
    var isExpanded = cardContainer.classList.contains('expanded');
    cardContainer.classList.toggle('expanded');
    element.textContent = isExpanded ? '显示更多' : '收起';
  }

  function initExpand() {
    var allCards = document.querySelectorAll('.idea-card-container');
    var container = document.querySelector('.idea-container');
    var clampEnabled = !container || container.getAttribute('data-clamp-enabled') !== '0';
    allCards.forEach(function (card) {
      var contentClamp = card.querySelector('.idea-content-clamp');
      var toggleBtn = card.querySelector('.idea-expand-toggle');
      if (!contentClamp || !toggleBtn) return;
      if (!clampEnabled) {
        toggleBtn.style.display = 'none';
        return;
      }
      if (contentClamp.scrollHeight > contentClamp.clientHeight) {
        toggleBtn.style.display = 'block';
      }
    });

    var expandButtons = document.querySelectorAll('.idea-expand-text');
    expandButtons.forEach(function (button) {
      button.addEventListener('click', function (e) {
        e.stopPropagation();
        toggleIdeaContent(this);
      });
    });
  }

  function parseGalleryData(str) {
    if (!str) return [];
    try {
      return JSON.parse(str);
    } catch (e) {
      try {
        var fixed = str.replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&amp;/g, '&');
        return JSON.parse(fixed);
      } catch (e2) {
        return [];
      }
    }
  }

  function createLightbox() {
    var box = document.createElement('div');
    box.className = 'idea-lightbox';
    box.innerHTML = '' +
      '<div class="idea-lightbox-backdrop"></div>' +
      '<div class="idea-lightbox-inner">' +
      '  <button class="idea-lightbox-close" aria-label="关闭">×</button>' +
      '  <button class="idea-lightbox-prev" aria-label="上一张">‹</button>' +
      '  <button class="idea-lightbox-next" aria-label="下一张">›</button>' +
      '  <img class="idea-lightbox-image" alt="">' +
      '  <div class="idea-lightbox-counter"></div>' +
      '</div>';
    document.body.appendChild(box);
    return box;
  }

  function initGallery() {
    var container = document.querySelector('.idea-container');
    if (!container) return;

    var lightbox = document.querySelector('.idea-lightbox') || createLightbox();
    var imgEl = lightbox.querySelector('.idea-lightbox-image');
    var closeBtn = lightbox.querySelector('.idea-lightbox-close');
    var prevBtn = lightbox.querySelector('.idea-lightbox-prev');
    var nextBtn = lightbox.querySelector('.idea-lightbox-next');
    var backdrop = lightbox.querySelector('.idea-lightbox-backdrop');
    var counterEl = lightbox.querySelector('.idea-lightbox-counter');

    var currentList = [];
    var currentIndex = 0;
    var visible = false;

    function show(index) {
      if (!currentList.length) return;
      currentIndex = ((index % currentList.length) + currentList.length) % currentList.length;
      var item = currentList[currentIndex];
      imgEl.src = item.src;
      imgEl.alt = item.alt || '';
      counterEl.textContent = (currentIndex + 1) + ' / ' + currentList.length;
      prevBtn.style.display = currentList.length > 1 ? 'block' : 'none';
      nextBtn.style.display = currentList.length > 1 ? 'block' : 'none';
      lightbox.style.display = 'flex';
      document.documentElement.classList.add('idea-lightbox-open');
      visible = true;
    }

    function hide() {
      lightbox.style.display = 'none';
      document.documentElement.classList.remove('idea-lightbox-open');
      visible = false;
    }

    closeBtn.addEventListener('click', hide);
    backdrop.addEventListener('click', hide);
    prevBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      show(currentIndex - 1);
    });
    nextBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      show(currentIndex + 1);
    });

    document.addEventListener('keydown', function (e) {
      if (!visible) return;
      if (e.key === 'Escape') hide();
      if (e.key === 'ArrowLeft') show(currentIndex - 1);
      if (e.key === 'ArrowRight') show(currentIndex + 1);
    });

    var startX = 0;
    var endX = 0;
    imgEl.addEventListener('touchstart', function (e) {
      if (!visible) return;
      startX = e.touches[0].clientX;
    }, { passive: true });
    imgEl.addEventListener('touchend', function (e) {
      if (!visible) return;
      endX = e.changedTouches[0].clientX;
      var delta = endX - startX;
      if (Math.abs(delta) > 50) {
        if (delta < 0) {
          show(currentIndex + 1);
        } else {
          show(currentIndex - 1);
        }
      }
    });

    container.addEventListener('click', function (e) {
      var target = e.target;
      if (!target || target.tagName.toLowerCase() !== 'img') return;
      var itemEl = target.closest('.image-item');
      var cardContent = target.closest('.idea-card-content');
      if (!cardContent) return;
      var dataStr = cardContent.getAttribute('data-gallery');
      var list = parseGalleryData(dataStr);
      if (!list.length) {
        currentList = [{ src: target.src, alt: target.alt || '' }];
        show(0);
        return;
      }
      currentList = list.map(function (it) {
        return { src: it.src, alt: it.alt || '' };
      });
      var index = 0;
      if (itemEl) {
        var siblings = Array.prototype.slice.call(cardContent.querySelectorAll('.image-gallery .image-item img'));
        index = Math.max(0, siblings.indexOf(target));
      }
      show(index);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (!document.querySelector('.idea-container')) return;
    initExpand();
    initGallery();

    (function initShare() {
      var container = document.querySelector('.idea-container');
      if (!container) return;
      container.addEventListener('click', function (e) {
        var t = e.target;
        var btn = t.closest && t.closest('.idea-action-btn');
        if (!btn) return;
        e.stopPropagation();
        var card = btn.closest('.idea-card-container');
        if (!card) return;
        if (btn.classList && btn.classList.contains('copy-rich')) {
          var clamp = card.querySelector('.idea-content-clamp');
          var html = clamp ? clamp.innerHTML : '';
          var plain = clamp ? clamp.textContent.trim() : '';
          var done = false;
          if (window.ClipboardItem && navigator.clipboard && html) {
            var item = new ClipboardItem({
              'text/html': new Blob([html], { type: 'text/html' }),
              'text/plain': new Blob([plain], { type: 'text/plain' })
            });
            navigator.clipboard.write([item]).then(function(){ showHint(btn); }).catch(function(){});
            done = true;
          }
          if (!done && document.execCommand && clamp) {
            var range = document.createRange();
            range.selectNodeContents(clamp);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
            try { document.execCommand('copy'); showHint(btn); } catch(e){}
            sel.removeAllRanges();
          }
          return;
        }
        if (btn.classList && btn.classList.contains('copy-md')) {
          var md = toMarkdown(card.querySelector('.idea-content-clamp'));
          if (!md) return;
          if (window.ClipboardItem && navigator.clipboard) {
            var item2 = new ClipboardItem({
              'text/plain': new Blob([md], { type: 'text/plain' }),
              'text/markdown': new Blob([md], { type: 'text/markdown' })
            });
            navigator.clipboard.write([item2]).then(function(){ showHint(btn); }).catch(function(){});
            return;
          }
          if (document.execCommand) {
            var ta = document.createElement('textarea');
            ta.value = md;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); showHint(btn); } catch(e){}
            document.body.removeChild(ta);
          }
          return;
        }
      });
      function showHint(el) {
        var hint = document.createElement('span');
        hint.className = 'idea-action-hint';
        hint.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        el.insertAdjacentElement('afterend', hint);
        setTimeout(function(){ if (hint && hint.parentNode) hint.parentNode.removeChild(hint); }, 1200);
      }
      function toMarkdown(root) {
        if (!root) return '';
        function mdInline(n) {
          var s = '';
          n.childNodes.forEach(function(c){
            if (c.nodeType === 3) { s += c.nodeValue; return; }
            var tag = c.nodeName.toLowerCase();
            if (tag === 'strong' || tag === 'b') { s += '**' + mdInline(c) + '**'; return; }
            if (tag === 'em' || tag === 'i') { s += '*' + mdInline(c) + '*'; return; }
            if (tag === 'code') { s += '`' + (c.textContent || '').replace(/`/g,'\\`') + '`'; return; }
            if (tag === 'a') {
              var txt = mdInline(c);
              var href = c.getAttribute('href') || '';
              s += '[' + txt + '](' + href + ')';
              return;
            }
            if (tag === 'br') { s += '\n'; return; }
            if (tag === 'img') {
              var alt = c.getAttribute('alt') || '';
              var src = c.getAttribute('src') || '';
              s += '![' + alt + '](' + src + ')';
              return;
            }
            s += mdInline(c);
          });
          return s;
        }
        function mdBlock(n) {
          var out = [];
          n.childNodes.forEach(function(c){
            if (c.nodeType === 3) {
              var t = c.nodeValue.trim();
              if (t) out.push(t);
              return;
            }
            var tag = c.nodeName.toLowerCase();
            if (tag === 'h1' || tag === 'h2' || tag === 'h3' || tag === 'h4' || tag === 'h5' || tag === 'h6') {
              var lvl = parseInt(tag.substr(1),10);
              out.push(Array(lvl+1).join('#') + ' ' + mdInline(c));
              out.push('');
              return;
            }
            if (tag === 'p') {
              out.push(mdInline(c));
              out.push('');
              return;
            }
            if (tag === 'blockquote') {
              var lines = mdInline(c).split(/\n/);
              lines.forEach(function(l){ out.push('> ' + l); });
              out.push('');
              return;
            }
            if (tag === 'pre') {
              var code = c.textContent || '';
              out.push('```');
              out.push(code.replace(/\n+$/,''));
              out.push('```');
              out.push('');
              return;
            }
            if (tag === 'ul') {
              c.querySelectorAll(':scope > li').forEach(function(li){
                var chk = li.querySelector('input[type="checkbox"]');
                var prefix = chk ? (chk.checked ? '- [x] ' : '- [ ] ') : '- ';
                out.push(prefix + mdInline(li));
              });
              out.push('');
              return;
            }
            if (tag === 'ol') {
              var i = 1;
              c.querySelectorAll(':scope > li').forEach(function(li){
                out.push(i + '. ' + mdInline(li));
                i++;
              });
              out.push('');
              return;
            }
            out.push(mdInline(c));
          });
          return out.join('\n').replace(/\n{3,}/g, '\n\n').trim();
        }
        return mdBlock(root);
      }
    })();
    (function initWeather() {
      var nodes = document.querySelectorAll('.idea-weather');
      if (!nodes.length) return;
      var today = new Date().toISOString().slice(0,10);
      var cacheKey = 'lbd_weather_'+today;
      try {
        var cached = localStorage.getItem(cacheKey);
        if (cached) {
          render(JSON.parse(cached));
          return;
        }
      } catch (e) {}
      fetch('/wp-json/lbd/v1/weather', { credentials: 'same-origin' })
        .then(function (res) { return res && res.ok ? res.json() : null; })
        .then(function (data) {
          if (!data) return;
          try { localStorage.setItem(cacheKey, JSON.stringify(data)); } catch (e) {}
          render(data);
        })
        .catch(function () {});

      function render(data) {
        var icon = (data && (data.iconDay || data.icon)) || '';
        var max = (data && (data.tempMax != null)) ? String(data.tempMax) : '';
        var min = (data && (data.tempMin != null)) ? String(data.tempMin) : '';
        var tempStr = (min !== '' && max !== '') ? (min + '–' + max + '℃') : '';
        var html = '';
        if (icon) html += '<i class="qi-' + icon + '-fill" aria-hidden="true"></i>';
        if (tempStr) html += ' <span class="idea-temp">' + tempStr + '</span>';
        nodes.forEach(function (el) {
          el.innerHTML = html;
          if (tempStr) el.setAttribute('aria-label', tempStr);
        });
      }
    })();
  });
})();
