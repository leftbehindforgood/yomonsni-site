<?php
// Page shape 7: one event's (workshop or retreat) full page, linked from
// its teaser card's "Learn More" button. Unlike a whisper, an event
// lives in exactly one domain, so this is a single generated page per
// event, not one per tagged domain. Expects: $entry, $coaches (global,
// unfiltered — see event-card.php's own comment), $domain_slug.
$title    = field($entry, 'title');
$when     = field($entry, 'date');
$format   = field($entry, 'format');
$location = field($entry, 'location');
$type     = field($entry, 'type');
$coach_ids = entry_list(field($entry, 'coaches'));
$booking  = field($entry, 'booking_link');
$button_extra = (isset($button_class) && $button_class !== '') ? " $button_class" : '';
$meta_parts = array();
if ($type !== '') $meta_parts[] = ucfirst($type);
$meta_parts[] = $when;
if ($format !== '') $meta_parts[] = $format;
if ($location !== '') $meta_parts[] = $location;
?>
  <!-- .nav-clearance (see myfunk.css): no masthead/mastblank hero here to
       reserve room under the fixed #mainNav, same as card-list.php and
       coach-profile.php. -->
  <div class="container-fluid p-3 tw nav-clearance">
    <div class="row">
      <div class="col-12 col-md-8 offset-md-2">
        <h1><?php echo htmlspecialchars($title); ?></h1>
        <p class="text-muted mb-2"><?php echo htmlspecialchars(implode(' · ', $meta_parts)); ?></p>
<?php if (!empty($coach_ids)): ?>
        <p class="text-muted">With <?php echo coach_links_html($coaches, $coach_ids, $domain_slug); ?></p>
<?php endif; ?>
        <?php echo entry_html($entry); ?>
        <div class="clearfix"></div>
<?php if ($booking !== ''): ?>
        <a class="btn btn-primary mt-3<?php echo $button_extra; ?>" href="<?php echo htmlspecialchars($booking); ?>" rel="noopener" target="_blank">Register</a>
<?php endif; ?>
      </div>
    </div>
  </div>
