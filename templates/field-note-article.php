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
