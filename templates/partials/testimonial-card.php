<?php
// One testimonial. Expects: $entry (a testimonial entry), $coaches
// (global, unfiltered — same reason as whisper-teaser-card.php/
// event-card.php: resolve_coach_name()'s cross-domain fallback needs to
// see a coach's cards in domains other than this one), $domain_slug,
// $card_class (optional extra class — see gen-site.php's $domain_design;
// ignored when $nested is true), $nested (optional, default false).
//
// $nested renders this as a plain full-width card with no .col-sm grid
// wrapper and a lighter .card-glass-nested background instead of the
// domain's own $card_class — meant to sit inside a scrollable
// .card-scroll-box on a coach's own profile page (coach-profile.php),
// stacked with that coach's other testimonials rather than laid out in
// a grid ("a card within a card"). The default (false) is the original
// grid-of-cards layout a domain's testimonials.html page uses.
$client   = field($entry, 'client');
$date     = field($entry, 'date');
$coach_id = field($entry, 'coach');
$nested   = !empty($nested);
$extra    = (isset($card_class) && $card_class !== '') ? " $card_class" : '';
// Reuses coach_links_html() (built for an event's possibly-several
// coaches) with a single-element list — a testimonial always has exactly
// one coach (restructure plan §4.2), but the link-or-plain-text logic is
// identical either way.
$coach_html = ($coach_id !== '') ? coach_links_html($coaches, array($coach_id), $domain_slug) : '';
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
        <?php echo entry_html($entry); ?>
        <p class="card-text text-right mb-0">&mdash; <?php echo htmlspecialchars($client); ?>, <?php echo htmlspecialchars($date); ?></p>
<?php if ($coach_html !== ''): ?>
        <p class="card-text text-right mb-0"><small>Client of <?php echo $coach_html; ?></small></p>
<?php endif; ?>
      </div>
<?php echo $outer_close; ?>
