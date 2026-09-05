<?php
// Teaser card for a coach, shown on a domain overview page. Links only to
// that coach's profile *within this same domain* (a bare same-directory
// relative filename — never the domain prefix, since there's nothing to
// cross into).
//
// Layout: photo beside the name (side set by $image_side, alternated per
// coach by the caller), a short summary flowing full-width beneath both,
// a JS toggle (content/js/coach-card-toggle.js) revealing the coach's
// full bio inline without leaving the page, and two buttons at the
// bottom — Book (external booking_link, or a non-interactive "currently
// unavailable" stand-in if that field is empty) and Full profile.
//
// Expects: $entry (a coach entry), $prefix (for the photo, which lives
// in the shared img/ asset dir), $card_class (optional extra card class),
// $button_class (optional extra button class), $image_side ('left' or
// 'right', default 'left').
$coach_id = field($entry, 'coach_id');
$name     = field($entry, 'name');
$photo    = field($entry, 'photo');
$booking  = field($entry, 'booking_link');
$summary  = field($entry, 'summary');
if ($summary === '') {
    // No explicit summary: fall back to the first sentence of the bio.
    if (preg_match('/^.*?[.!?](?=\s|$)/s', trim($entry['body_md']), $m)) {
        $summary = trim($m[0]);
    } else {
        $summary = trim($entry['body_md']);
    }
}
$card_extra   = (isset($card_class) && $card_class !== '') ? " $card_class" : '';
$button_extra = (isset($button_class) && $button_class !== '') ? " $button_class" : '';
$reversed     = (isset($image_side) && $image_side === 'right');
$more_id      = 'coach-more-' . $coach_id;
?>
  <div class="col-12 mb-4">
    <div class="card card-glass<?php echo $card_extra; ?>">
      <div class="card-body">
        <div class="d-flex align-items-center coach-card-head<?php echo $reversed ? ' flex-row-reverse' : ''; ?>">
<?php if ($photo !== ''): ?>
          <img class="coach-photo rounded" src="<?php echo asset_url($prefix, "img/$photo"); ?>" alt="<?php echo htmlspecialchars($name); ?>">
<?php endif; ?>
          <h5 class="card-title mb-0"><?php echo htmlspecialchars($name); ?></h5>
        </div>

        <p class="card-text"><?php echo htmlspecialchars($summary); ?></p>
        <button type="button" class="btn btn-sm btn-link coach-toggle-more p-0" data-target="<?php echo $more_id; ?>">Read more</button>
        <div id="<?php echo $more_id; ?>" class="coach-more" hidden>
          <?php echo entry_html($entry); ?>
        </div>

        <div class="d-flex justify-content-between mt-3">
<?php if ($booking !== ''): ?>
          <a class="btn btn-primary<?php echo $button_extra; ?>" href="<?php echo htmlspecialchars($booking); ?>" rel="noopener" target="_blank">Book</a>
<?php else: ?>
          <span class="btn btn-unavailable" aria-disabled="true">Currently unavailable</span>
<?php endif; ?>
          <a class="btn btn-primary<?php echo $button_extra; ?>" href="coach-<?php echo htmlspecialchars($coach_id); ?>.html">Full profile</a>
        </div>
      </div>
    </div>
  </div>
