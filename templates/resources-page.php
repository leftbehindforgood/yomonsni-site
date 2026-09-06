<?php
// Page shape 8: a domain's full resources page. Composite, like
// domain-overview.php: the domain's own resources.entry intro copy (the
// framing for this page — it's meant for anyone curious about the
// subject, not just people ready to book a session), then a grid of
// large resource cards (books, videos, papers, ...) pulled from
// content/resource-items/, filtered to this domain. Expects: $entry
// (resources.entry), $title, $resource_items (already filtered to this
// domain), $coaches (global, unfiltered — passed through to
// resource-card.php's own coach-link resolution), $domain_slug, $prefix,
// $card_class/$button_class (optional, this domain's per-domain styling
// — see gen-site.php's $domain_design).
?>
  <div class="container-fluid p-3 tw nav-clearance">
    <div class="row">
      <div class="col-12">
        <h1><?php echo htmlspecialchars($title); ?></h1>
        <?php echo entry_html($entry); ?>
      </div>
    </div>
<?php if (!empty($resource_items)): ?>
    <div class="row mt-3">
<?php foreach ($resource_items as $r): ?>
<?php echo render_template(__DIR__ . '/partials/resource-card.php', array('entry' => $r, 'prefix' => $prefix, 'coaches' => $coaches, 'domain_slug' => $domain_slug, 'card_class' => $card_class, 'button_class' => $button_class)); ?>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </div>
