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

  document.addEventListener('DOMContentLoaded', function () {
    if (!document.querySelector('.idea-container')) return;
    initExpand();
  });
})();
