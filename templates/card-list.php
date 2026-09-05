<?php
// Page shape 5: one reusable "list of short cards" shape, reused for a
// domain's testimonials, whisper teasers, workshops, and retreats — the
// caller pre-renders each item with the appropriate card partial and just
// hands this template the finished HTML fragments (restructure plan §5.5).
// Expects: $title, $cards (array of already-rendered HTML strings),
// $empty_message (shown when $cards is empty).
?>
  <div class="container-fluid p-3 tw">
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
