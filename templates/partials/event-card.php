<?php
// One workshop or retreat listing. Same shape for both (restructure plan
// §5) — registration is always an external link, never handled on-site.
// Expects: $entry.
$title    = field($entry, 'title');
$when     = field($entry, 'date');
$format   = field($entry, 'format');
$booking  = field($entry, 'booking_link');
$extra    = (isset($card_class) && $card_class !== '') ? " $card_class" : '';
?>
  <div class="col-sm mb-4">
    <div class="card card-glass<?php echo $extra; ?> h-100">
      <div class="card-body">
        <h5 class="card-title"><?php echo htmlspecialchars($title); ?></h5>
        <h6 class="card-subtitle mb-2 text-muted">
          <?php echo htmlspecialchars($when); ?><?php echo $format !== '' ? ' &middot; ' . htmlspecialchars($format) : ''; ?>
        </h6>
        <?php echo entry_html($entry); ?>
<?php if ($booking !== ''): ?>
        <a class="btn btn-primary" href="<?php echo htmlspecialchars($booking); ?>" rel="noopener" target="_blank">Register</a>
<?php endif; ?>
      </div>
    </div>
  </div>
