<?php
// One event teaser — a workshop or a retreat, both living in the single
// content/events/ collection and sharing this card (restructure plan §5
// originally split these into two collections; merged into one "Events"
// listing/nav item since a visitor doesn't care which bucket a given date
// came from). Shows only a short $summary, not the full description —
// links through to that event's own full page (event-<slug>.html, see
// templates/event-article.php) for the rest (which may embed venue/
// facility photos — see myfunk.css's .content-photo-left/-right) and the
// actual Register button. Same teaser/full-page relationship
// coach-card.php has to coach-profile.php. Expects: $entry, $coaches
// (global, unfiltered — same reason as whisper-teaser-card.php: an
// event's coach may not have a card in this domain), $domain_slug.
$title    = field($entry, 'title');
$when     = field($entry, 'date');
$format   = field($entry, 'format');
$location = field($entry, 'location');
$type     = field($entry, 'type');
$coach_ids = entry_list(field($entry, 'coaches'));
$booking  = field($entry, 'booking_link');
$summary  = field($entry, 'summary');
if ($summary === '') {
    // No explicit summary: fall back to the first sentence of the body,
    // same convention coach-card.php uses. An event whose body embeds a
    // raw <img> (see myfunk.css's .content-photo-left/-right) should
    // always set an explicit summary instead of relying on this — the
    // fallback works on the raw Markdown source and won't strip a
    // leading image tag out for you.
    if (preg_match('/^.*?[.!?](?=\s|$)/s', trim($entry['body_md']), $m)) {
        $summary = trim($m[0]);
    } else {
        $summary = trim($entry['body_md']);
    }
}
$extra        = (isset($card_class) && $card_class !== '') ? " $card_class" : '';
$button_extra = (isset($button_class) && $button_class !== '') ? " $button_class" : '';
$subtitle_parts = array();
if ($type !== '') $subtitle_parts[] = ucfirst($type);
$subtitle_parts[] = $when;
if ($format !== '') $subtitle_parts[] = $format;
if ($location !== '') $subtitle_parts[] = $location;
$event_url = 'event-' . $entry['slug'] . '.html';
?>
  <div class="col-sm mb-4">
    <div class="card card-glass<?php echo $extra; ?> h-100">
      <div class="card-body d-flex flex-column">
        <h5 class="card-title"><?php echo htmlspecialchars($title); ?></h5>
        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars(implode(' · ', $subtitle_parts)); ?></h6>
<?php if (!empty($coach_ids)): ?>
        <p class="card-text text-muted mb-2">With <?php echo coach_links_html($coaches, $coach_ids, $domain_slug); ?></p>
<?php endif; ?>
        <p class="card-text"><?php echo htmlspecialchars($summary); ?></p>
        <div class="d-flex flex-wrap mt-auto pt-3">
<?php if ($booking !== ''): ?>
          <a class="btn btn-primary mr-3 mb-2<?php echo $button_extra; ?>" href="<?php echo htmlspecialchars($booking); ?>" rel="noopener" target="_blank">Register</a>
<?php endif; ?>
          <a class="btn btn-primary mb-2<?php echo $button_extra; ?>" href="<?php echo htmlspecialchars($event_url); ?>">Learn More</a>
        </div>
      </div>
    </div>
  </div>
