<?php
// One testimonial. Expects: $entry (a testimonial entry).
$client = field($entry, 'client');
$date   = field($entry, 'date');
?>
  <div class="col-sm mb-4">
    <div class="card h-100">
      <div class="card-body">
        <?php echo entry_html($entry); ?>
        <p class="card-text text-right mb-0">&mdash; <?php echo htmlspecialchars($client); ?>, <?php echo htmlspecialchars($date); ?></p>
      </div>
    </div>
  </div>
