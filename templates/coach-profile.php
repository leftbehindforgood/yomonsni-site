<?php
// Page shape 3: one coach's profile within one domain. Same layout for
// every domain — only the entry's own data (photo, bio, booking links,
// location, photo_side) and that domain's testimonials/whispers for this
// coach differ. Expects: $entry, $testimonials (already filtered to this
// coach + domain), $coach_whispers (likewise — this coach's whispers
// tagged into this domain), $coaches (global, unfiltered — passed
// through to testimonial-card.php's/whisper-teaser-card.php's own
// coach-link resolution), $domain_slug, $prefix, $card_class/
// $button_class (optional, this domain's per-domain styling — see
// gen-site.php's $domain_design).
//
// Testimonials and whispers each render inside their own outer
// .card-glass panel with a bounded-height, scrollable stack of nested
// cards (.card-scroll-box/.card-glass-nested, myfunk.css) rather than a
// grid that grows the page — "a card within a card" — so a coach with a
// lot of either doesn't turn their profile into an endless scroll.
//
// `photo_side` (front matter on the coach entry itself, 'left'/'right',
// default 'left') picks which side the photo lands on. Deliberately a
// per-coach-per-domain config field rather than an automatic alternation
// (which is what `coach-card.php`'s teaser used to do, by array index) —
// this is exactly the kind of left/right visual choice worth being able
// to set deliberately and compare (e.g. A/B testing which side books
// better for a given coach/domain), not leave to accident of file order.
// The info column (name/location/bio/buttons) stays first in the actual
// markup regardless of `photo_side` — order-md-first/last only changes
// which side it *looks* like it's on, same convention
// domain-overview.php uses for its intro/aside columns.
//
// The outer container carries `.nav-clearance` (see myfunk.css) because
// this page shape has no masthead/mastblank hero to reserve room under
// the fixed #mainNav the way domain-overview.php's title header does —
// without it, the name/photo render hidden underneath the navbar instead
// of below it.
$name     = field($entry, 'name');
$photo    = field($entry, 'photo');
$location = field($entry, 'location');
$offers_in_person = $location !== '';

$photo_side = (field($entry, 'photo_side') === 'right') ? 'right' : 'left';
if ($photo_side === 'right') {
    $info_order  = 'order-md-first';
    $photo_order = 'order-md-last';
} else {
    $info_order  = 'order-md-last';
    $photo_order = 'order-md-first';
}

$booking = field($entry, 'booking_link');
$can_book_online = $booking !== '' && !field_bool($entry, 'fully_booked');

$in_person_booking = field($entry, 'in_person_booking_link');
$can_book_in_person = $offers_in_person && $in_person_booking !== '' && !field_bool($entry, 'in_person_fully_booked');

$button_extra = (isset($button_class) && $button_class !== '') ? " $button_class" : '';
$card_extra   = (isset($card_class) && $card_class !== '') ? " $card_class" : '';
?>
  <div class="container-fluid p-3 tw nav-clearance">
    <div class="row">
      <div class="col-md-8 <?php echo $info_order; ?>">
        <h1 class="mb-0"><?php echo htmlspecialchars($name); ?></h1>
<?php if ($offers_in_person): ?>
        <p class="coach-location mb-3"><?php echo htmlspecialchars($location); ?></p>
<?php endif; ?>
        <?php echo entry_html($entry); ?>
        <div class="d-flex flex-wrap align-items-center mt-3">
<?php if ($can_book_online): ?>
          <a class="btn btn-primary mr-3 mb-2<?php echo $button_extra; ?>" href="<?php echo htmlspecialchars($booking); ?>" rel="noopener" target="_blank">Book Online Coaching</a>
<?php else: ?>
          <span class="btn btn-unavailable mr-3 mb-2" aria-disabled="true">Online Coaching Fully Booked</span>
<?php endif; ?>
<?php if ($offers_in_person): ?>
<?php if ($can_book_in_person): ?>
          <a class="btn btn-primary mr-3 mb-2<?php echo $button_extra; ?>" href="<?php echo htmlspecialchars($in_person_booking); ?>" rel="noopener" target="_blank">Book In Person Coaching</a>
<?php else: ?>
          <span class="btn btn-unavailable mr-3 mb-2" aria-disabled="true">In Person Coaching Fully Booked</span>
<?php endif; ?>
<?php endif; ?>
        </div>
      </div>
<?php if ($photo !== ''): ?>
      <div class="col-md-4 text-center <?php echo $photo_order; ?>">
        <img class="img-fluid rounded" src="<?php echo asset_url($prefix, "img/$photo"); ?>" alt="<?php echo htmlspecialchars($name); ?>">
      </div>
<?php endif; ?>
    </div>

<?php if (!empty($testimonials)): ?>
    <div class="row mt-5 pt-4">
      <div class="col-12">
        <div class="card card-glass<?php echo $card_extra; ?>">
          <div class="card-body">
            <h2>What clients have said about <?php echo htmlspecialchars($name); ?></h2>
            <div class="card-scroll-box testimonial-scroll-box">
<?php foreach ($testimonials as $t): ?>
<?php echo render_template(__DIR__ . '/partials/testimonial-card.php', array('entry' => $t, 'coaches' => $coaches, 'domain_slug' => $domain_slug, 'nested' => true)); ?>
<?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
<?php endif; ?>

<?php if (!empty($coach_whispers)): ?>
    <div class="row mt-4">
      <div class="col-12">
        <div class="card card-glass<?php echo $card_extra; ?>">
          <div class="card-body">
            <h2>What <?php echo htmlspecialchars($name); ?> has written</h2>
            <div class="card-scroll-box whisper-scroll-box">
<?php foreach ($coach_whispers as $w): ?>
<?php echo render_template(__DIR__ . '/partials/whisper-teaser-card.php', array('entry' => $w, 'coaches' => $coaches, 'domain_slug' => $domain_slug, 'nested' => true)); ?>
<?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
<?php endif; ?>
  </div>
