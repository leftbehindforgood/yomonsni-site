<?php
// Page shape 2: a domain's own front door (content/<domain>/index.entry).
// Composite — it renders its own intro copy, then teaser sections pulled
// from other collections filtered to this domain (restructure plan §5.2).
// Expects: $entry, $domain_slug, $domain_title, $prefix, $coaches (already
// filtered to this domain), $resources_entry, $whispers (filtered, sorted
// newest-first), $workshops, $retreats (each filtered to this domain),
// $text_align ('left'/'right'/null — a per-domain layout choice, see
// gen-site.php's $domain_design), $card_class (extra class appended to
// this domain's cards, e.g. for a fully-transparent variant).
$intro_col = 'col-12';
if ($text_align === 'left') $intro_col = 'col-12 col-md-8';
if ($text_align === 'right') $intro_col = 'col-12 col-md-8 offset-md-4';
?>
  <header class="mastblank" style="background: none;">
    <div class="container d-flex h-100 align-items-center">
      <div class="mx-auto text-center">
        <h1 class="mx-auto my-0 text-uppercase"><?php echo htmlspecialchars($domain_title); ?></h1>
      </div>
    </div>
  </header>

  <div class="container-fluid p-3 tw">
    <div class="row">
      <div class="<?php echo $intro_col; ?>">
        <?php echo entry_html($entry); ?>
      </div>
    </div>

<?php if (!empty($coaches)): ?>
    <div class="row">
      <div class="col-12"><h2>Coaches</h2></div>
<?php foreach ($coaches as $c): ?>
<?php echo render_template(__DIR__ . '/partials/coach-card.php', array('entry' => $c, 'prefix' => $prefix, 'card_class' => $card_class)); ?>
<?php endforeach; ?>
    </div>
<?php endif; ?>

    <div class="row">
      <div class="col-12">
        <h2>Resources</h2>
        <p><?php echo htmlspecialchars(field($resources_entry, 'teaser')); ?></p>
        <a class="btn btn-primary" href="resources.html">Explore resources</a>
      </div>
    </div>

<?php if (!empty($whispers)): ?>
    <div class="row">
      <div class="col-12"><h2>Whispers</h2></div>
<?php foreach (array_slice($whispers, 0, 3) as $w): ?>
<?php echo render_template(__DIR__ . '/partials/whisper-teaser-card.php', array('entry' => $w, 'coaches' => $coaches, 'domain_slug' => $domain_slug, 'card_class' => $card_class)); ?>
<?php endforeach; ?>
      <div class="col-12"><a href="whispers.html">All whispers &rarr;</a></div>
    </div>
<?php endif; ?>

<?php if (!empty($workshops)): ?>
    <div class="row">
      <div class="col-12"><h2>Workshops</h2></div>
<?php foreach ($workshops as $w): ?>
<?php echo render_template(__DIR__ . '/partials/event-card.php', array('entry' => $w, 'card_class' => $card_class)); ?>
<?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($retreats)): ?>
    <div class="row">
      <div class="col-12"><h2>Retreats</h2></div>
<?php foreach ($retreats as $r): ?>
<?php echo render_template(__DIR__ . '/partials/event-card.php', array('entry' => $r, 'card_class' => $card_class)); ?>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </div>
