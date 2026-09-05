// Generic expand/collapse: any button with class "toggle-more" and a
// data-target pointing at an element's id shows/hides that element and
// flips the button's chevron. Used by coach cards (full bio) and the
// resources card (a bit more teaser text) — plain JS, no framework, since
// the site loads no JS bundle otherwise and this one interaction doesn't
// justify pulling in jQuery/Bootstrap's JS for it.
(function () {
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.toggle-more');
    if (!btn) return;
    var target = document.getElementById(btn.getAttribute('data-target'));
    if (!target) return;
    var isHidden = target.hasAttribute('hidden');
    if (isHidden) {
      target.removeAttribute('hidden');
      btn.setAttribute('aria-expanded', 'true');
      btn.classList.add('is-open');
    } else {
      target.setAttribute('hidden', '');
      btn.setAttribute('aria-expanded', 'false');
      btn.classList.remove('is-open');
    }
  });
})();
