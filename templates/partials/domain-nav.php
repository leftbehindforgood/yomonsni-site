<?php
// Domain-scoped nav. Deliberately links only within this one domain, plus a
// single "back to home" link — never to a sibling domain. See restructure
// plan §3/§7: this is what keeps a coach's cross-domain work non-obvious to
// a visitor browsing the site. Expects: $prefix ("../"), $domain_slug,
// $domain_title, $has_events.
?>
  <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
    <div class="container-fluid">
      <a class="navbar-brand" href="<?php echo asset_url($prefix, "$domain_slug/index.html"); ?>"><?php echo htmlspecialchars($domain_title); ?></a>
      <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        Menu
        <i class="fas fa-bars"></i>
      </button>
      <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav ml-auto">
          <li class="nav-item"><a class="nav-link" href="<?php echo asset_url($prefix, "$domain_slug/resources.html"); ?>">Resources</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo asset_url($prefix, "$domain_slug/testimonials.html"); ?>">Testimonials</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo asset_url($prefix, "$domain_slug/whispers.html"); ?>">Whispers</a></li>
<?php if (!empty($has_events)): ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo asset_url($prefix, "$domain_slug/events.html"); ?>">Events</a></li>
<?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo asset_url($prefix, 'index.html'); ?>">&larr; Home</a></li>
        </ul>
      </div>
    </div>
  </nav>
