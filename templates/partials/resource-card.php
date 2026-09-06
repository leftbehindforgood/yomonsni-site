<?php
// One resource (book, video, academic paper, article, ...) on a domain's
// full resources page — deliberately "large cards" (two per row, not
// three like the testimonial/event grids) since this page is meant to
// share real material with anyone curious about the subject, not just
// people ready to book a session. Each is tagged with which coach
// recommends it — a personal touch, not just a bare bibliography.
// Expects: $entry, $prefix (for the optional cover image), $coaches
// (global, unfiltered), $domain_slug, $card_class/$button_class
// (optional — see gen-site.php's $domain_design).
$title  = field($entry, 'title');
$type   = field($entry, 'type');
$url    = field($entry, 'url');
$image  = field($entry, 'image');
$coach_id = field($entry, 'coach');
$extra        = (isset($card_class) && $card_class !== '') ? " $card_class" : '';
$button_extra = (isset($button_class) && $button_class !== '') ? " $button_class" : '';

$coach_html = '';
if ($coach_id !== '') {
    // First name only ("Recommended by Sam") — reads more personal here
    // than the full-name links testimonials/events/whispers use, but
    // it's the same coach_has_profile() fallback: a link to that coach's
    // profile in this domain when they have one there, plain text
    // otherwise.
    $first = htmlspecialchars(first_name(resolve_coach_name($coaches, $coach_id, $domain_slug)));
    $coach_html = coach_has_profile($coaches, $coach_id, $domain_slug)
        ? '<a href="coach-' . htmlspecialchars($coach_id) . '.html">' . $first . '</a>'
        : $first;
}

// A type-specific button label reads better than one generic "View" for
// every kind of resource; anything not in this list still gets a
// sensible fallback rather than being left without a label.
$type_labels = array(
    'book'    => 'View Book',
    'video'   => 'Watch Video',
    'paper'   => 'Read Paper',
    'article' => 'Read Article',
    'podcast' => 'Listen',
);
$type_key = strtolower($type);
$button_label = isset($type_labels[$type_key]) ? $type_labels[$type_key] : 'View Resource';
?>
  <div class="col-12 col-md-6 mb-4">
    <div class="card card-glass<?php echo $extra; ?> h-100">
<?php if ($image !== ''): ?>
      <img class="resource-cover" src="<?php echo asset_url($prefix, "img/$image"); ?>" alt="<?php echo htmlspecialchars($title); ?>">
<?php endif; ?>
      <div class="card-body d-flex flex-column">
        <h5 class="card-title"><?php echo htmlspecialchars($title); ?></h5>
        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars(ucfirst($type)); ?><?php if ($coach_html !== ''): ?> &middot; Recommended by <?php echo $coach_html; ?><?php endif; ?></h6>
        <?php echo entry_html($entry); ?>
<?php if ($url !== ''): ?>
        <a class="btn btn-primary mt-auto align-self-start<?php echo $button_extra; ?>" href="<?php echo htmlspecialchars($url); ?>" rel="noopener" target="_blank"><?php echo htmlspecialchars($button_label); ?></a>
<?php endif; ?>
      </div>
    </div>
  </div>
