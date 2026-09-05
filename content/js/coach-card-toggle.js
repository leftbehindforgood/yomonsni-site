// Expands/collapses a coach card's full bio below its summary. Plain JS,
// no framework — the site loads no JS bundle otherwise, and one toggle
// behavior doesn't justify pulling in jQuery/Bootstrap's JS for it.
(function () {
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.coach-toggle-more');
    if (!btn) return;
    var target = document.getElementById(btn.getAttribute('data-target'));
    if (!target) return;
    var isHidden = target.hasAttribute('hidden');
    if (isHidden) {
      target.removeAttribute('hidden');
      btn.textContent = 'Read less';
    } else {
      target.setAttribute('hidden', '');
      btn.textContent = 'Read more';
    }
  });
})();
