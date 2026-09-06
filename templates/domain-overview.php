<?php
// Page shape 2: a domain's own front door (content/<domain>/index.entry).
// Composite — it renders its own intro copy, then teaser sections pulled
// from other collections filtered to this domain (restructure plan §5.2).
// Expects: $entry, $domain_slug, $domain_title, $prefix, $coaches (already
// filtered to this domain), $all_coaches (the *global*, unfiltered
// collection — needed for whisper byline resolution; see the comment
// below), $resources_entry, $whispers (filtered, sorted newest-first),
// $events (workshops and retreats together, filtered to this domain),
// $text_align ('left'/'right'/null — a per-domain layout choice, see
// gen-site.php's $domain_design), $card_class (extra class appended to
// this domain's cards, e.g. for a fully-transparent variant).
//
// When $text_align is set, the intro text and the coaches/resources/
// whispers/events stack sit side by side in one row (~2/3 +
// ~1/3) instead of the stack running full-width below the whole intro —
// the point is getting a visitor to "Coaches" (deliberately first in the
// stack, ahead of resources/whispers) without scrolling past a long
// intro first. order-md-first/last keeps the intro first in the actual
// markup (so it's still read/found first) while controlling which side
// it lands on visually. With no $text_align, both columns are col-12,
// which just stacks them full-width in document order — today's
// no-side-chosen-yet default for every domain except this one.
$intro_col = 'col-12';
$aside_col = 'col-12';
$intro_order = '';
$aside_order = '';
if ($text_align === 'left') {
    $intro_col = 'col-12 col-md-8';
    $aside_col = 'col-12 col-md-4';
    $intro_order = 'order-md-first';
    $aside_order = 'order-md-last';
} elseif ($text_align === 'right') {
    $intro_col = 'col-12 col-md-8';
    $aside_col = 'col-12 col-md-4';
    $intro_order = 'order-md-last';
    $aside_order = 'order-md-first';
}
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
      <div class="<?php echo $intro_col . ' ' . $intro_order; ?>">
        <?php echo entry_html($entry); ?>
      </div>

      <div class="<?php echo $aside_col . ' ' . $aside_order; ?>">
<?php if (!empty($coaches)): ?>
        <h2 class="text-center">Coaches</h2>
        <div class="row">
<?php foreach ($coaches as $c): ?>
<?php echo render_template(__DIR__ . '/partials/coach-card.php', array('entry' => $c, 'prefix' => $prefix, 'card_class' => $card_class, 'button_class' => $button_class)); ?>
<?php endforeach; ?>
        </div>
<?php endif; ?>

        <h2 class="text-center">Resources</h2>
        <div class="row">
<?php echo render_template(__DIR__ . '/partials/resources-card.php', array('resources_entry' => $resources_entry, 'card_class' => $card_class, 'button_class' => $button_class)); ?>
        </div>

<?php if (!empty($whispers)): ?>
        <h2 class="text-center">Whispers</h2>
        <div class="row">
<?php foreach (array_slice($whispers, 0, 3) as $w): ?>
<?php // 'coaches' here is $all_coaches (global), not the domain-filtered
     // $coaches — see this file's header comment. ?>
<?php echo render_template(__DIR__ . '/partials/whisper-teaser-card.php', array('entry' => $w, 'coaches' => $all_coaches, 'domain_slug' => $domain_slug, 'card_class' => $card_class, 'button_class' => $button_class)); ?>
<?php endforeach; ?>
        </div>
        <a href="whispers.html" class="d-block mb-5">All whispers &rarr;</a>
<?php endif; ?>

<?php if (!empty($events)): ?>
        <h2 class="text-center">Events</h2>
        <div class="row">
<?php foreach (array_slice($events, 0, 3) as $e): ?>
<?php // 'coaches' here is $all_coaches (global), same reason as the
     // whisper loop above — an event's coach may not have a card in
     // this domain at all. ?>
<?php echo render_template(__DIR__ . '/partials/event-card.php', array('entry' => $e, 'coaches' => $all_coaches, 'domain_slug' => $domain_slug, 'card_class' => $card_class, 'button_class' => $button_class)); ?>
<?php endforeach; ?>
        </div>
        <a href="events.html" class="d-block mb-5">All events &rarr;</a>
<?php endif; ?>
      </div>
    </div>
  </div>
