<?php
// Sitewide generic footer: the ethical code / mission statement content
// (content/footer.entry) plus links to the top-level legal pages. Same on
// every page, hub or domain — this is the one place cross-site links are
// fine, since it never mentions a coach or names which domains exist.
// No background tint here, deliberately — the page's own fixed photo
// (whichever one that page uses) should read as one plain, continuous
// image straight through the footer too, not fade behind a third,
// differently-tinted overlay.
// Expects: $prefix, $footer_body_html, $show_legal_links (optional,
// default true — set false only for a page whose sole permitted outbound
// links are Home/Mission/Coaching, e.g. templates/coach-landing.php;
// Terms/Privacy don't belong on that allowlist).
$show_legal_links = isset($show_legal_links) ? $show_legal_links : true;
?>
  <footer class="py-5">
    <div class="container">
      <div class="row">
        <div class="col">
          <?php echo $footer_body_html; ?>
        </div>
      </div>
<?php if ($show_legal_links): ?>
      <div class="row">
        <div class="col-6 col-sm">
          <p class="mb-0"><a href="<?php echo asset_url($prefix, 'terms.html'); ?>">Terms</a></p>
        </div>
        <div class="col-6 col-sm">
          <p class="mb-0"><a href="<?php echo asset_url($prefix, 'privacy.html'); ?>">Privacy</a></p>
        </div>
      </div>
<?php endif; ?>
      <div class="row">
        <div class="col">
          <p class="mb-0">&copy; Yomonsni <?php echo date('Y'); ?></p>
        </div>
      </div>
    </div>
  </footer>
