<?php
// Page shape 1: the root landing page. One-off — nothing else uses this
// shape. Expects: $entry (content/index.entry), $domains (slug => title),
// $domain_entries (slug => that domain's own index.entry, for its hook).
// Domain cards carry no title/label — just hook/hook2, two "mom test"
// style questions (simple, personal, no jargon), meant to pull a visitor
// in by resonance rather than by describing the service.
//
// The masthead's header has no background of its own: the hero photo
// lives on <body> (set by the caller), so it reads as one continuous
// image behind the whole page rather than a separate hero graphic.
//
// card-body is a flex column with the button pinned to the bottom
// (mt-auto): the six questions wrap to different numbers of lines, and
// without this the button's distance from the card's bottom edge would
// vary card to card. align-items-center keeps the button sized to its own
// content instead of the flex column's default of stretching it full width.
$site_name = field($entry, 'title');
?>
  <header class="masthead" style="background: none;">
    <div class="container d-flex h-100 align-items-center">
      <div class="mx-auto text-center">
        <h1 class="mx-auto my-0 text-uppercase"><?php echo htmlspecialchars($site_name); ?></h1>
        <?php echo entry_html($entry); ?>
      </div>
    </div>
  </header>

  <div class="container-fluid p-3 tw">
    <div class="row">
<?php foreach ($domains as $slug => $title):
        $d = $domain_entries[$slug];
        $hook = field($d, 'hook');
        $hook2 = field($d, 'hook2');
?>
      <div class="col-12 col-sm-6 col-md-4 mb-4">
        <div class="card card-glass h-100">
          <div class="card-body text-center d-flex flex-column align-items-center">
            <p class="card-text"><?php echo htmlspecialchars($hook); ?></p>
            <p class="card-text card-text-secondary"><?php echo htmlspecialchars($hook2); ?></p>
            <a class="btn btn-primary btn-explore mt-auto" href="<?php echo asset_url($prefix, "$slug/index.html"); ?>">Explore</a>
          </div>
        </div>
      </div>
<?php endforeach; ?>
    </div>
  </div>
