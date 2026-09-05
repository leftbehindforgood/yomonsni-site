<?php
// Page shape 1: the root landing page. One-off — nothing else uses this
// shape. Expects: $entry (content/index.entry), $domains (slug => title),
// $domain_entries (slug => that domain's own index.entry, for its blurb).
?>
  <!-- No inline background-image here, deliberately: content/css/myfunk.css's
       own .masthead rule already carries the site's real hero photo
       (yo-IMG_56547-...jpg — the same one the live pre-rebuild site used),
       so this just lets that CSS default show through, the same way the
       original design did. -->
  <header class="masthead">
    <div class="container d-flex h-100 align-items-center">
      <div class="mx-auto text-center">
        <h1 class="mx-auto my-0 text-uppercase">Yomonsni</h1>
        <?php echo entry_html($entry); ?>
      </div>
    </div>
  </header>

  <div class="container-fluid p-3 tw">
    <div class="row">
<?php foreach ($domains as $slug => $title):
        $d = $domain_entries[$slug];
        $blurb = field($d, 'blurb');
?>
      <div class="col-sm mb-4">
        <div class="card h-100">
          <div class="card-body text-center">
            <h5 class="card-title"><?php echo htmlspecialchars($title); ?></h5>
            <p class="card-text"><?php echo htmlspecialchars($blurb); ?></p>
            <a class="btn btn-primary" href="<?php echo asset_url($prefix, "$slug/index.html"); ?>">Explore</a>
          </div>
        </div>
      </div>
<?php endforeach; ?>
    </div>
  </div>
