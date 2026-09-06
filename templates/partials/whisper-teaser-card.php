<?php
// One whisper's teaser on a domain's whispers listing (or, nested, on a
// coach's own profile page). The "domains" field is generation-time-only
// metadata (it decides which domain page(s) this renders on) and must
// never appear in the rendered output — see restructure plan §4.2.
// Expects: $entry, $coaches (global, unfiltered), $domain_slug,
// $card_class/$button_class (optional — see gen-site.php's
// $domain_design; $card_class ignored when $nested is true), $nested
// (optional, default false).
//
// $nested renders this as a plain full-width card with no .col-sm grid
// wrapper and a lighter .card-glass-nested background instead of the
// domain's own $card_class — meant to sit inside a scrollable
// .card-scroll-box on a coach's own profile page (coach-profile.php),
// stacked with that coach's other whispers in this domain rather than
// laid out in a grid ("a card within a card"). The default (false) is
// the original grid-of-cards layout a domain's whispers.html page (and
// domain-overview.php's teaser stack) use.
$title  = field($entry, 'title');
$teaser = field($entry, 'teaser');
if ($teaser === '') {
    // No explicit teaser field: fall back to the first paragraph of the body.
    $parts  = preg_split('/\n\s*\n/', trim($entry['body_md']), 2);
    $teaser = $parts[0];
}
$coach_id = field($entry, 'coach');
// Same coach_links_html() helper testimonial-card.php/event-card.php use
// — a whisper always has exactly one coach, but the link-or-plain-text
// fallback logic (does this coach have a profile in *this* domain?) is
// identical either way.
$author_html = coach_links_html($coaches, array($coach_id), $domain_slug);
$md = new Parsedown();
$extra        = (isset($card_class) && $card_class !== '') ? " $card_class" : '';
$button_extra = (isset($button_class) && $button_class !== '') ? " $button_class" : '';
$nested       = !empty($nested);
if ($nested) {
    $outer_open  = '<div class="card card-glass-nested">';
    $outer_close = '</div>';
} else {
    $outer_open  = '<div class="col-sm mb-4"><div class="card card-glass' . $extra . ' h-100">';
    $outer_close = '</div></div>';
}
echo $outer_open;
?>
      <div class="card-body">
        <h5 class="card-title"><?php echo htmlspecialchars($title); ?></h5>
        <h6 class="card-subtitle mb-2 text-muted">By <?php echo $author_html; ?></h6>
        <?php echo $md->text($teaser); ?>
        <a class="btn btn-primary<?php echo $button_extra; ?>" href="whisper-<?php echo htmlspecialchars($entry['slug']); ?>.html">Read more</a>
      </div>
<?php echo $outer_close; ?>
