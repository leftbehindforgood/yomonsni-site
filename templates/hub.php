<?php
// Page shape 1: the root landing page. One-off — nothing else uses this
// shape. Expects: $entry (content/index.entry), $domains (slug => title),
// $domain_entries (slug => that domain's own index.entry, for its blurb).
?>
  <!-- Gradient only here, deliberately no image: the hero photo is set on
       <body> instead (see gen-site.php's write_page call for this page),
       so it renders fixed behind the whole page, not just this header.
       background-attachment: scroll is also deliberate and load-bearing:
       .masthead's own CSS rule sets `background-attachment: fixed`, and
       having two *separate* elements (this header, and <body>) each
       independently peg their own background to the viewport is what was
       causing the visible seam/scroll mismatch around the card grid —
       each element's "fixed" background is calculated against the
       viewport on its own, so as this header scrolls out of view its
       gradient and the body's fixed photo drift apart. There must be
       exactly one fixed-attachment layer on the page (the body's), so
       this header's own background just scrolls normally with it. -->
  <header class="masthead" style="background-image: linear-gradient(to bottom, rgba(22, 22, 22, 0.3) 0%, rgba(22, 22, 22, 0.7) 75%, #161616 100%); background-attachment: scroll;">
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
        <div class="card card-glass h-100">
          <div class="card-body text-center">
            <h5 class="card-title"><?php echo htmlspecialchars($title); ?></h5>
            <p class="card-text"><?php echo htmlspecialchars($blurb); ?></p>
            <a class="btn btn-primary btn-explore" href="<?php echo asset_url($prefix, "$slug/index.html"); ?>">Explore</a>
          </div>
        </div>
      </div>
<?php endforeach; ?>
    </div>
  </div>
