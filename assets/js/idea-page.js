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
    allCards.forEach(function (card) {
      var contentClamp = card.querySelector('.idea-content-clamp');
      var toggleBtn = card.querySelector('.idea-expand-toggle');
      if (!contentClamp || !toggleBtn) return;
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
  });
})();
