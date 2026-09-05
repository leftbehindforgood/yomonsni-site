<?php
// Page shape 4: plain prose, no collection data — terms, privacy, a
// domain's full resources page. Expects: $entry, $title.
?>
  <div class="container-fluid p-3 tw">
    <div class="row">
      <div class="col-12">
        <h1><?php echo htmlspecialchars($title); ?></h1>
        <?php echo entry_html($entry); ?>
      </div>
    </div>
  </div>
