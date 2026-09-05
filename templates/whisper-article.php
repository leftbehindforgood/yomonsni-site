<?php
// Page shape 6: one whisper's full page. Generated once per domain it's
// tagged into (restructure plan §4.2) — same content, independent page
// instances, none linking to the others. Expects: $entry, $coaches,
// $domain_slug.
$title  = field($entry, 'title');
$date   = field($entry, 'date');
$author = resolve_coach_name($coaches, field($entry, 'coach'), $domain_slug);
?>
  <div class="container-fluid p-3 tw">
    <div class="row">
      <div class="col-12 col-md-8 offset-md-2">
        <h1><?php echo htmlspecialchars($title); ?></h1>
        <p class="text-muted"><?php echo htmlspecialchars($author); ?> &middot; <?php echo htmlspecialchars($date); ?></p>
        <?php echo entry_html($entry); ?>
      </div>
    </div>
  </div>
