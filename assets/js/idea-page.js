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

  function openIdeaLightbox(images, startIndex) {
    var overlay = document.createElement('div');
    overlay.className = 'idea-lightbox-overlay';

    var content = document.createElement('div');
    content.className = 'idea-lightbox-content';

    var imageContainer = document.createElement('div');
    imageContainer.className = 'idea-lightbox-image-container';

    var img = document.createElement('img');
    img.className = 'idea-lightbox-image';
    img.src = images[startIndex].src;
    img.alt = images[startIndex].alt || '';

    imageContainer.appendChild(img);
    content.appendChild(imageContainer);

    var closeBtn = document.createElement('div');
    closeBtn.className = 'idea-lightbox-close';
    closeBtn.innerHTML = '×';

    overlay.appendChild(content);
    overlay.appendChild(closeBtn);

    var handleKeydown = null;
    var currentIndex = startIndex;

    if (images.length > 1) {
      var counter = document.createElement('div');
      counter.className = 'idea-lightbox-counter';
      counter.innerHTML = (startIndex + 1) + ' / ' + images.length;
      content.appendChild(counter);

      var prevBtn = document.createElement('div');
      prevBtn.className = 'idea-lightbox-nav prev';
      prevBtn.innerHTML = '‹';

      var nextBtn = document.createElement('div');
      nextBtn.className = 'idea-lightbox-nav next';
      nextBtn.innerHTML = '›';

      overlay.appendChild(prevBtn);
      overlay.appendChild(nextBtn);

      var indicator = document.createElement('div');
      indicator.className = 'idea-lightbox-indicator';

      images.forEach(function (_, index) {
        var dot = document.createElement('div');
        dot.className = 'idea-lightbox-dot' + (index === startIndex ? ' active' : '');
        dot.onclick = function (e) {
          e.stopPropagation();
          showImage(index);
        };
        indicator.appendChild(dot);
      });

      overlay.appendChild(indicator);

      var showImage = function (index) {
        currentIndex = index;
        img.src = images[index].src;
        img.alt = images[index].alt || '';
        counter.innerHTML = (index + 1) + ' / ' + images.length;

        var dots = indicator.querySelectorAll('.idea-lightbox-dot');
        dots.forEach(function (dot, i) {
          dot.classList.toggle('active', i === index);
        });
      };

      var navigateGallery = function (direction) {
        var newIndex = currentIndex + direction;
        if (newIndex < 0) newIndex = images.length - 1;
        if (newIndex >= images.length) newIndex = 0;
        showImage(newIndex);
      };

      prevBtn.onclick = function (e) {
        e.stopPropagation();
        navigateGallery(-1);
      };

      nextBtn.onclick = function (e) {
        e.stopPropagation();
        navigateGallery(1);
      };

      handleKeydown = function (e) {
        if (e.key === 'Escape') {
          closeLightbox();
        } else if (e.key === 'ArrowLeft') {
          navigateGallery(-1);
        } else if (e.key === 'ArrowRight') {
          navigateGallery(1);
        }
      };

      document.addEventListener('keydown', handleKeydown);
    }

    var closeLightbox = function () {
      if (images.length > 1 && handleKeydown) {
        document.removeEventListener('keydown', handleKeydown);
      }
      overlay.remove();
    };

    closeBtn.onclick = closeLightbox;
    overlay.onclick = closeLightbox;
    img.onclick = function (e) {
      e.stopPropagation();
    };

    document.body.appendChild(overlay);
    setTimeout(function () {
      overlay.classList.add('active');
    }, 10);
  }

  function initImageLightbox() {
    var ideaImages = document.querySelectorAll('.idea-card-content .image-item img');

    ideaImages.forEach(function (img) {
      img.addEventListener('click', function () {
        var contentNode = this.closest('.idea-card-content');
        if (!contentNode) return;

        var galleryData = contentNode.dataset.gallery;
        if (galleryData) {
          try {
            var images = JSON.parse(galleryData);
            var currentIndex = Array.from(this.closest('.image-gallery').querySelectorAll('img')).indexOf(this);
            openIdeaLightbox(images, currentIndex < 0 ? 0 : currentIndex);
          } catch (e) {
            openIdeaLightbox([{ src: this.src, alt: this.alt }], 0);
          }
        } else {
          openIdeaLightbox([{ src: this.src, alt: this.alt }], 0);
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (!document.querySelector('.idea-container')) return;
    initExpand();
    initImageLightbox();
  });
})();
