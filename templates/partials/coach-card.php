<?php
// Teaser card for a coach, shown on a domain overview page. Links only to
// that coach's profile *within this same domain* (a bare same-directory
// relative filename — never the domain prefix, since there's nothing to
// cross into).
//
// Layout: photo floated to one side (side set by the coach's own
// `photo_side` front-matter field, default 'left' — same field
// coach-profile.php reads for the full profile page's layout, so a
// coach's side choice is consistent between their teaser and their full
// page) so the name and summary text wrap around it — the name
// top-aligned with the image, text starting right under the name —
// rather than sitting in a centered row above the text. A small chevron
// toggle (content/js/toggle-expand.js) expands the coach's full bio
// inline without leaving the page. Two buttons at the bottom — Book
// (external booking_link, or a non-interactive "currently unavailable"
// stand-in if that field is empty) and Full profile.
//
// Expects: $entry (a coach entry), $prefix (for the photo, which lives
// in the shared img/ asset dir), $card_class (optional extra card class),
// $button_class (optional extra button class).
$coach_id = field($entry, 'coach_id');
$name     = field($entry, 'name');
$photo    = field($entry, 'photo');
$booking  = field($entry, 'booking_link');
$can_book = $booking !== '' && !field_bool($entry, 'fully_booked');
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
$photo_side   = (field($entry, 'photo_side') === 'right') ? 'coach-photo-right' : 'coach-photo-left';
$more_id      = 'coach-more-' . $coach_id;
?>
  <div class="col-12 mb-4">
    <div class="card card-glass<?php echo $card_extra; ?>">
      <div class="card-body">
        <div class="coach-card-wrap">
<?php if ($photo !== ''): ?>
          <img class="coach-photo rounded <?php echo $photo_side; ?>" src="<?php echo asset_url($prefix, "img/$photo"); ?>" alt="<?php echo htmlspecialchars($name); ?>">
<?php endif; ?>
          <h5 class="card-title mt-0 mb-1"><?php echo htmlspecialchars($name); ?></h5>
          <p class="card-text"><?php echo htmlspecialchars($summary); ?>
            <button type="button" class="toggle-more" data-target="<?php echo $more_id; ?>" aria-expanded="false" aria-controls="<?php echo $more_id; ?>" aria-label="Show more about <?php echo htmlspecialchars($name); ?>"><i class="fas fa-chevron-right"></i></button>
          </p>
        </div>
        <div class="clearfix"></div>
        <div id="<?php echo $more_id; ?>" class="expand-content" hidden>
          <?php echo entry_html($entry); ?>
        </div>

        <div class="d-flex justify-content-between mt-3">
<?php if ($can_book): ?>
          <a class="btn btn-primary<?php echo $button_extra; ?>" href="<?php echo htmlspecialchars($booking); ?>" rel="noopener" target="_blank">Book</a>
<?php else: ?>
          <span class="btn btn-unavailable" aria-disabled="true">Fully Booked</span>
<?php endif; ?>
          <a class="btn btn-primary<?php echo $button_extra; ?>" href="coach-<?php echo htmlspecialchars($coach_id); ?>.html">Full profile</a>
        </div>
      </div>
    </div>
  </div>
