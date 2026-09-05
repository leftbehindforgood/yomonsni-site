<?php
// The domain overview page's Resources teaser, as a card matching the
// coach/whisper/event cards. A couple of quick pointers (always visible),
// a chevron toggle revealing a bit more teaser text (content/js/
// toggle-expand.js — same mechanism as a coach card's "read more"), then
// a link through to the domain's full resources page.
// Expects: $resources_entry, $card_class (optional), $button_class
// (optional).
$pointers = entry_list(field($resources_entry, 'pointers'), '|');
$teaser   = field($resources_entry, 'teaser');
$card_extra   = (isset($card_class) && $card_class !== '') ? " $card_class" : '';
$button_extra = (isset($button_class) && $button_class !== '') ? " $button_class" : '';
?>
  <div class="col-12 mb-4">
    <div class="card card-glass<?php echo $card_extra; ?>">
      <div class="card-body">
<?php if (!empty($pointers)): ?>
        <ul class="resource-pointers">
<?php foreach ($pointers as $p): ?>
          <li><?php echo htmlspecialchars($p); ?></li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
<?php if ($teaser !== ''): ?>
        <button type="button" class="toggle-more" data-target="resources-more" aria-expanded="false" aria-controls="resources-more" aria-label="More about resources"><i class="fas fa-chevron-right"></i></button>
        <div id="resources-more" class="expand-content" hidden>
          <p><?php echo htmlspecialchars($teaser); ?></p>
        </div>
<?php endif; ?>
        <a class="btn btn-primary<?php echo $button_extra; ?> mt-3" href="resources.html">Explore resources</a>
      </div>
    </div>
  </div>
