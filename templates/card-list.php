<?php
// Page shape 5: one reusable "list of short cards" shape, reused for a
// domain's testimonials, whisper teasers, and events — the
// caller pre-renders each item with the appropriate card partial and just
// hands this template the finished HTML fragments (restructure plan §5.5).
// Expects: $title, $cards (array of already-rendered HTML strings),
// $empty_message (shown when $cards is empty).
//
// Carries `.nav-clearance` (see myfunk.css) because this page shape has
// no masthead/mastblank hero reserving room under the fixed #mainNav —
// without it the title heading renders hidden underneath the navbar
// instead of below it (same fix as coach-profile.php).
?>
  <div class="container-fluid p-3 tw nav-clearance">
    <div class="row">
      <div class="col-12"><h1><?php echo htmlspecialchars($title); ?></h1></div>
    </div>
<?php if (empty($cards)): ?>
    <div class="row">
      <div class="col-12"><p><?php echo htmlspecialchars($empty_message); ?></p></div>
    </div>
<?php else: ?>
    <div class="row">
<?php foreach ($cards as $card_html): ?>
<?php echo $card_html; ?>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </div>
