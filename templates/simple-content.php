<?php
// Page shape 4: plain prose, no collection data — terms, privacy, a
// domain's full resources page. Expects: $entry, $title.
//
// Carries `.nav-clearance` (see myfunk.css) for the same reason
// card-list.php and coach-profile.php do — no masthead/mastblank hero
// here to reserve room under the fixed #mainNav.
?>
  <div class="container-fluid p-3 tw nav-clearance">
    <div class="row">
      <div class="col-12">
        <h1><?php echo htmlspecialchars($title); ?></h1>
        <?php echo entry_html($entry); ?>
      </div>
    </div>
  </div>
