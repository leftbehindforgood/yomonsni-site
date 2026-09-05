<?php
// Page shape 3: one coach's profile within one domain. Same layout
// regardless of domain (restructure plan §5.3) — only the entry's own
// data (photo, bio, booking link) and this domain's testimonials for this
// coach differ. Expects: $entry, $testimonials (already filtered to this
// coach + domain), $prefix, $card_class/$button_class (optional, this
// domain's per-domain styling — see gen-site.php's $domain_design).
$name    = field($entry, 'name');
$photo   = field($entry, 'photo');
$booking = field($entry, 'booking_link');
$button_extra = (isset($button_class) && $button_class !== '') ? " $button_class" : '';
?>
  <div class="container-fluid p-3 tw">
    <div class="row">
<?php if ($photo !== ''): ?>
      <div class="col-md-4 text-center">
        <img class="img-fluid rounded" src="<?php echo asset_url($prefix, "img/$photo"); ?>" alt="<?php echo htmlspecialchars($name); ?>">
      </div>
<?php endif; ?>
      <div class="col-md-8">
        <h1><?php echo htmlspecialchars($name); ?></h1>
        <?php echo entry_html($entry); ?>
<?php if ($booking !== ''): ?>
        <a class="btn btn-primary<?php echo $button_extra; ?>" href="<?php echo htmlspecialchars($booking); ?>" rel="noopener" target="_blank">Book with <?php echo htmlspecialchars($name); ?></a>
<?php else: ?>
        <span class="btn btn-unavailable" aria-disabled="true">Currently Unavailable for new Bookings</span>
<?php endif; ?>
      </div>
    </div>

<?php if (!empty($testimonials)): ?>
    <div class="row">
      <div class="col-12"><h2>What clients say</h2></div>
<?php foreach ($testimonials as $t): ?>
<?php echo render_template(__DIR__ . '/partials/testimonial-card.php', array('entry' => $t, 'card_class' => $card_class)); ?>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </div>
