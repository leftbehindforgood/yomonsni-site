<?php
// Root-only nav. Deliberately does NOT list the six domains — those are
// handled on the hub page itself (templates/hub.php), not in navigation.
// The brand slot doesn't link "home" here (matching the old design, whose
// navbar-brand never did either) — it's the mission link instead.
// Expects: $prefix ("./" here).
?>
  <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
    <div class="container-fluid">
      <a class="navbar-brand" href="<?php echo asset_url($prefix, 'mission.html'); ?>">Yomonsni Mission</a>
      <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        Menu
        <i class="fas fa-bars"></i>
      </button>
      <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav ml-auto">
          <li class="nav-item">
            <a class="nav-link" href="<?php echo asset_url($prefix, 'coaching.html'); ?>">Our view on coaching</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
