<?php
// Root-only nav, shared by the hub and the four other root-level pages
// (mission, coaching, terms, privacy). Deliberately does NOT list the six
// domains — those are handled on the hub page itself (templates/hub.php),
// not in navigation.
//
// The brand slot links home (index.html) and shows the site's own name —
// same convention a domain's nav uses for its own brand (always links
// back to that domain's own overview, wherever you are within it).
// Previously the brand linked to mission.html instead (matching the old
// pre-rebuild site, whose navbar-brand never linked home either) — flipped
// because that made the mission page's own top-left link to itself,
// with no way back to the hub from the nav at all. Mission and Coaching
// are both now plain nav-items, sourced from those two pages' (and the
// hub's) own `title` fields (see gen-site.php) so no nav label can ever
// drift from the destination page's own heading.
//
// $current_slug ('hub', 'mission', 'coaching', 'terms', or 'privacy')
// says which page this particular render is for, so that page's own
// nav-item can be omitted entirely rather than linking to itself —
// there's no point helping someone navigate to where they already are,
// same reasoning domain-nav.php never links a domain to itself. Since
// each root page needs its own nav render for this (not one shared
// `$hub_nav_html` reused everywhere), gen-site.php calls
// `hub_nav_for()` once per page instead.
// Expects: $prefix ("./" here), $current_slug, $site_title,
// $mission_title, $coaching_title.
?>
  <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
    <div class="container-fluid">
      <a class="navbar-brand" href="<?php echo asset_url($prefix, 'index.html'); ?>"><?php echo htmlspecialchars($site_title); ?></a>
      <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        Menu
        <i class="fas fa-bars"></i>
      </button>
      <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav ml-auto">
<?php if ($current_slug !== 'mission'): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo asset_url($prefix, 'mission.html'); ?>"><?php echo htmlspecialchars($mission_title); ?></a>
          </li>
<?php endif; ?>
<?php if ($current_slug !== 'coaching'): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo asset_url($prefix, 'coaching.html'); ?>"><?php echo htmlspecialchars($coaching_title); ?></a>
          </li>
<?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
