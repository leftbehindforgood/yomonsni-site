<?php
// Page shape 9: a coach's landing page — one per real person, not per
// domain, generated from content/coach-landing/ (see gen-site.php).
// Meant to be handed out privately (e.g. printed on a business card),
// never linked to from anywhere else on the site, and deliberately never
// mentions which domain(s) this coach actually works in — no booking
// link, no domain-scoped bio, nothing pulled from that coach's per-domain
// content/coaches/ cards. See CLAUDE.md's "The coach-privacy design".
// validate_coach_landing_pages() (lib/validate.php) checks after
// generation that this page's only outbound links are Home/Mission/
// Coaching, so don't add a link to anything else here.
//
// `photo_side` (same field/values coach-profile.php and coach-card.php
// use, default 'left') picks which side the photo lands on, top-aligned
// with the name — same convention, so a coach who appears both here and
// on a domain card can keep a consistent layout if they want, though the
// two are otherwise entirely independent.
// Expects: $entry, $prefix.
$name  = field($entry, 'name');
$photo = field($entry, 'photo');

$photo_side = (field($entry, 'photo_side') === 'right') ? 'right' : 'left';
if ($photo_side === 'right') {
    $info_order  = 'order-md-first';
    $photo_order = 'order-md-last';
} else {
    $info_order  = 'order-md-last';
    $photo_order = 'order-md-first';
}
?>
  <div class="container-fluid p-3 tw nav-clearance">
    <div class="row">
      <div class="col-md-8 <?php echo $info_order; ?>">
        <h1 class="mb-3"><?php echo htmlspecialchars($name); ?></h1>
        <?php echo entry_html($entry); ?>
      </div>
<?php if ($photo !== ''): ?>
      <div class="col-md-4 text-center <?php echo $photo_order; ?>">
        <img class="img-fluid rounded" src="<?php echo asset_url($prefix, "img/$photo"); ?>" alt="<?php echo htmlspecialchars($name); ?>">
      </div>
<?php endif; ?>
    </div>
  </div>
