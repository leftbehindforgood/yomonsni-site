<?php
// Page shape 1: the root landing page. One-off — nothing else uses this
// shape. Expects: $entry (content/index.entry), $domains (slug => title),
// $domain_entries (slug => that domain's own index.entry, for its hook).
// The domain cards deliberately have no title/label — just a "mom test"
// style question (simple, personal, no jargon) meant to pull a visitor
// toward that domain by resonance rather than by describing the service.
?>
  <!-- No background of its own at all, deliberately: the hero photo lives
       on <body> only (see gen-site.php's write_page call for this page),
       and it's meant to read as one plain, untinted, continuous image for
       the whole page — no gradient here, no fade in the card section
       below, no tint in the footer. This element's own .masthead CSS
       rule carries a gradient+image background; "background: none"
       cancels that entirely so body's photo shows straight through with
       nothing overlaid on top of it, same as everywhere else on the page. -->
  <header class="masthead" style="background: none;">
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
        $hook = field($d, 'hook');
?>
      <div class="col-12 col-sm-6 col-md-4 mb-4">
        <div class="card card-glass h-100">
          <!-- d-flex flex-column + mt-auto on the button: the six hook
               questions wrap to different numbers of lines, and without
               this the button would sit right after the text at a
               different height in every card, leaving an inconsistent
               gap to the card's bottom edge. This pins it to the bottom
               of the (already-equal-height, via h-100) card instead, so
               that gap is the same everywhere regardless of text length.
               align-items-center is load-bearing too: a flex column's
               default cross-axis behavior is to stretch items to fill
               the container's width, which is why the button was
               spanning edge-to-edge — this makes it size to its own
               content instead, centered, like a normal button. -->
          <div class="card-body text-center d-flex flex-column align-items-center">
            <p class="card-text"><?php echo htmlspecialchars($hook); ?></p>
            <a class="btn btn-primary btn-explore mt-auto" href="<?php echo asset_url($prefix, "$slug/index.html"); ?>">Explore</a>
          </div>
        </div>
      </div>
<?php endforeach; ?>
    </div>
  </div>
