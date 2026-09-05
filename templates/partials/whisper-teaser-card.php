<?php
// One whisper's teaser on a domain's whispers listing. The "domains" field
// is generation-time-only metadata (it decides which domain page(s) this
// renders on) and must never appear in the rendered output — see
// restructure plan §4.2. Expects: $entry, $coaches, $domain_slug.
$title  = field($entry, 'title');
$teaser = field($entry, 'teaser');
if ($teaser === '') {
    // No explicit teaser field: fall back to the first paragraph of the body.
    $parts  = preg_split('/\n\s*\n/', trim($entry['body_md']), 2);
    $teaser = $parts[0];
}
$author = resolve_coach_name($coaches, field($entry, 'coach'), $domain_slug);
$md = new Parsedown();
$extra        = (isset($card_class) && $card_class !== '') ? " $card_class" : '';
$button_extra = (isset($button_class) && $button_class !== '') ? " $button_class" : '';
?>
  <div class="col-sm mb-4">
    <div class="card card-glass<?php echo $extra; ?> h-100">
      <div class="card-body">
        <h5 class="card-title"><?php echo htmlspecialchars($title); ?></h5>
        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($author); ?></h6>
        <?php echo $md->text($teaser); ?>
        <a class="btn btn-primary<?php echo $button_extra; ?>" href="whisper-<?php echo htmlspecialchars($entry['slug']); ?>.html">Read more</a>
      </div>
    </div>
  </div>
