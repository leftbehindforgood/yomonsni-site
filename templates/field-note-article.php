<?php
// Page shape 6: one field note's full page. Generated once per domain it's
// tagged into (restructure plan §4.2) — same content, independent page
// instances, none linking to the others. Expects: $entry, $coaches,
// $domain_slug. Like an event's full page, the body can embed a
// venue/other photo via a raw <img class="content-photo content-photo-
// left|right"> tag (myfunk.css) — no engine support needed, Parsedown
// passes raw HTML in the body through untouched; see CONTENT-GUIDE.md.
//
// Also used for the note's central copy (field-notes/<slug>.html), which
// passes $domain_slug = '' (so the byline is plain text — there is no
// one domain whose coach profile it could link to) and $applies_to
// (slug => ['title', 'button_class']): the domains the note is tagged
// into, rendered as buttons through to each one's overview page. A
// domain's own copy never gets $applies_to — it would be a cross-domain
// link, which validate_output() rejects.
//
// $booking_coach (optional — the author's coach card, see
// coach_booking_card() in lib/entry.php) puts that coach's booking
// details after the body: location if they take in-person clients, and
// the same booking buttons as their profile page. Left out when the
// author has no card to take them from. $button_class (optional) is the
// domain's own, on a domain's copy.
$title  = field($entry, 'title');
$date   = field($entry, 'date');
$author_html = coach_links_html($coaches, array(field($entry, 'coach')), $domain_slug);
?>
  <!-- .nav-clearance (see myfunk.css): no masthead/mastblank hero here to
       reserve room under the fixed #mainNav, same as card-list.php and
       coach-profile.php. -->
  <div class="container-fluid p-3 tw nav-clearance">
    <div class="row">
      <div class="col-12 col-md-8 offset-md-2">
        <h1><?php echo htmlspecialchars($title); ?></h1>
        <p class="text-muted"><?php echo $author_html; ?> &middot; <?php echo htmlspecialchars($date); ?></p>
        <?php echo entry_html($entry); ?>
        <div class="clearfix"></div>
<?php if (!empty($booking_coach)): ?>
        <hr>
        <p class="mb-2">Coaching with <?php echo $author_html; ?><?php if (field($booking_coach, 'location') !== ''): ?> <span class="coach-location">&middot; <?php echo htmlspecialchars(field($booking_coach, 'location')); ?></span><?php endif; ?></p>
        <div class="d-flex flex-wrap align-items-center">
<?php echo render_template(__DIR__ . '/partials/coach-booking-buttons.php', array('entry' => $booking_coach, 'button_class' => isset($button_class) ? $button_class : '')); ?>
        </div>
<?php endif; ?>
<?php if (!empty($applies_to)): ?>
        <hr>
        <p>Coaching where this applies:</p>
        <p>
<?php foreach ($applies_to as $d_slug => $d): ?>
          <a class="btn btn-primary mr-2 mb-2<?php echo $d['button_class'] !== '' ? ' ' . htmlspecialchars($d['button_class']) : ''; ?>" href="<?php echo asset_url($prefix, "$d_slug/index.html"); ?>"><?php echo htmlspecialchars($d['title']); ?></a>
<?php endforeach; ?>
        </p>
<?php endif; ?>
      </div>
    </div>
  </div>
