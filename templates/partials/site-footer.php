<?php
// Sitewide generic footer: the ethical code / mission statement content
// (content/footer.entry) plus links to the top-level legal pages. Same on
// every page, hub or domain — this is the one place cross-site links are
// fine, since it never mentions a coach or names which domains exist.
// No background tint here, deliberately — the page's own fixed photo
// (whichever one that page uses) should read as one plain, continuous
// image straight through the footer too, not fade behind a third,
// differently-tinted overlay.
// Expects: $prefix, $footer_body_html.
?>
  <footer class="py-5">
    <div class="container">
      <div class="row">
        <div class="col">
          <?php echo $footer_body_html; ?>
        </div>
      </div>
      <div class="row">
        <div class="col-6 col-sm">
          <p class="mb-0"><a href="<?php echo asset_url($prefix, 'terms.html'); ?>">Terms</a></p>
        </div>
        <div class="col-6 col-sm">
          <p class="mb-0"><a href="<?php echo asset_url($prefix, 'privacy.html'); ?>">Privacy</a></p>
        </div>
      </div>
      <div class="row">
        <div class="col">
          <p class="mb-0">&copy; Yomonsni <?php echo date('Y'); ?></p>
        </div>
      </div>
    </div>
  </footer>
