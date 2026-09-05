<?php
// Root-only nav. Deliberately does NOT list the six domains — those are
// handled on the hub page itself (templates/hub.php), not in navigation.
// The brand slot doesn't link "home" here (matching the old design, whose
// navbar-brand never did either) — it's the mission link instead.
// mission_title/coaching_title come from content/mission.entry and
// content/coaching.entry's own `title` fields (see gen-site.php), so the
// nav label always matches the destination page's own heading.
// Expects: $prefix ("./" here), $mission_title, $coaching_title.
?>
  <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
    <div class="container-fluid">
      <a class="navbar-brand" href="<?php echo asset_url($prefix, 'mission.html'); ?>"><?php echo htmlspecialchars($mission_title); ?></a>
      <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        Menu
        <i class="fas fa-bars"></i>
      </button>
      <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav ml-auto">
          <li class="nav-item">
            <a class="nav-link" href="<?php echo asset_url($prefix, 'coaching.html'); ?>"><?php echo htmlspecialchars($coaching_title); ?></a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
