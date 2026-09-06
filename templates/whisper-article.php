<?php
// Page shape 6: one whisper's full page. Generated once per domain it's
// tagged into (restructure plan §4.2) — same content, independent page
// instances, none linking to the others. Expects: $entry, $coaches,
// $domain_slug. Like an event's full page, the body can embed a
// venue/other photo via a raw <img class="content-photo content-photo-
// left|right"> tag (myfunk.css) — no engine support needed, Parsedown
// passes raw HTML in the body through untouched; see CONTENT-GUIDE.md.
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
      </div>
    </div>
  </div>
