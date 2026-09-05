<?php
// One testimonial. Expects: $entry (a testimonial entry), $card_class
// (optional extra class — see gen-site.php's $domain_design).
$client = field($entry, 'client');
$date   = field($entry, 'date');
$extra  = (isset($card_class) && $card_class !== '') ? " $card_class" : '';
?>
  <div class="col-sm mb-4">
    <div class="card card-glass<?php echo $extra; ?> h-100">
      <div class="card-body">
        <?php echo entry_html($entry); ?>
        <p class="card-text text-right mb-0">&mdash; <?php echo htmlspecialchars($client); ?>, <?php echo htmlspecialchars($date); ?></p>
      </div>
    </div>
  </div>
