<?php
// A coach card's booking buttons: online (Book, or a non-interactive
// Fully Booked notice when `fully_booked` is set or there's no
// `booking_link`), plus an independent in-person one that only appears at
// all when the coach has set `location`. Just the buttons — the caller
// supplies the wrapping flex row. Used by coach-profile.php and at the
// foot of a field note (field-note-article.php).
// Expects: $entry (a content/coaches/ card), $button_class (optional).
$booking = field($entry, 'booking_link');
$can_book_online = $booking !== '' && !field_bool($entry, 'fully_booked');

$offers_in_person = field($entry, 'location') !== '';
$in_person_booking = field($entry, 'in_person_booking_link');
$can_book_in_person = $offers_in_person && $in_person_booking !== '' && !field_bool($entry, 'in_person_fully_booked');

$button_extra = (isset($button_class) && $button_class !== '') ? " $button_class" : '';
?>
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
