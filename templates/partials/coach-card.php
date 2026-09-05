<?php
// Teaser card for a coach, shown on a domain overview page. Links only to
// that coach's profile *within this same domain* (a bare same-directory
// relative filename — never the domain prefix, since there's nothing to
// cross into). Expects: $entry (a coach entry), $prefix (for the photo,
// which lives in the shared img/ asset dir).
$coach_id = field($entry, 'coach_id');
$name     = field($entry, 'name');
$photo    = field($entry, 'photo');
?>
  <div class="col-sm mb-4">
    <div class="card card-glass h-100">
<?php if ($photo !== ''): ?>
      <img class="coach-photo rounded mx-auto mt-3" src="<?php echo asset_url($prefix, "img/$photo"); ?>" alt="<?php echo htmlspecialchars($name); ?>">
<?php endif; ?>
      <div class="card-body text-center">
        <h5 class="card-title"><?php echo htmlspecialchars($name); ?></h5>
        <a class="btn btn-primary" href="coach-<?php echo htmlspecialchars($coach_id); ?>.html">View profile</a>
      </div>
    </div>
  </div>
