<?php

// yomonsni site generator — v2, the coaching-site engine.
// See docs/coaching-site-restructure-plan.md for the design this
// implements. Replaces the earlier .con/.skel single-author templating
// system entirely (that system's own history is still visible in git log
// prior to this rewrite, on the coaching-rebuild branch).

require_once __DIR__ . '/lib/Parsedown.php';
require_once __DIR__ . '/lib/entry.php';
require_once __DIR__ . '/lib/render.php';
require_once __DIR__ . '/lib/validate.php';

$readprefix  = "content/";
$writeprefix = "working/";
$noworkdir   = array("css", "js", "scss", "vendor", "img", "doc");
$site_domain = "yomonsni.com";
$templates   = __DIR__ . '/templates/';

// The seven coaching domains. Order here is nav/hub display order.
$domain_titles = array(
    "leadership"  => "Leadership",
    "followership" => "Followership",
    "creativity"  => "Creativity",
    "change"      => "Change & Transitions",
    "performance" => "Performance",
    "intimate"    => "Intimate",
    "discovery"   => "Discovery",
);
$domain_slugs = array_keys($domain_titles);

// Per-domain visual tuning. The domain overview page's *layout* (intro
// beside a Coaches/Resources/Field Notes/Workshops stack, centered section
// headings, rounded buttons, the coach card design) is the same for every
// domain — it's not something this config turns on, and it isn't
// creativity-specific. What's actually meant to vary here is narrower:
// which side the text lands on, the background photo, and color/theme
// choices (button color, card transparency). Anything not set for a
// given domain falls back to the shared default: placeholder
// "<slug>-bg.jpg" background, text anchored left, and the standard (not
// fully transparent) card-glass treatment.
$domain_design = array(
    "creativity" => array(
        'bg'           => 'yo-IMG_42290-5D3-raw-shaped-flattened.jpg',
        'text_align'   => 'left', // matches the shared default; explicit for clarity
        'card_class'   => 'card-glass-transparent',
        'button_class' => 'btn-creativity', // dark green
    ),
    "leadership" => array(
        'bg'           => 'yo-IMG_00053-5DSR-raw16-shaped-flattened-kaleidoscope.jpg',
        'button_class' => 'btn-leadership', // dark slate blue-grey
    ),
    "followership" => array(
        'bg'           => 'yo-IMG_18604-5D-raw16-rawtherapee-shaped.jpg', // stand-in — not yet picked by hand like the others
        'button_class' => 'btn-followership', // dark indigo
    ),
    "change" => array(
        'bg'           => 'yo-IMG_08577-5DSR-raw16-rawtherapee-shaped.jpg',
        'button_class' => 'btn-change', // dark amber/bronze
    ),
    "performance" => array(
        'bg'           => 'yo-IMG_22696-5D-raw32-rawtherapee.jpg',
        'button_class' => 'btn-performance', // dark crimson/burgundy
    ),
    "intimate" => array(
        'bg'           => 'yo-IMG_20216-5DSR-raw16-rawtherapee-natural.jpg',
        'button_class' => 'btn-intimate', // dark plum/wine
    ),
    "discovery" => array(
        'bg'           => 'yo-IMG_73894-5D3-raw32-rawtherapee-shaped.jpg',
        'button_class' => 'btn-discovery', // dark teal
    ),
);

function domain_design($domain_design, $slug, $key, $default) {
    return isset($domain_design[$slug][$key]) ? $domain_design[$slug][$key] : $default;
}

// Placeholder headshot colors, one muted tone per domain — used only when
// a coach card's own `photo` field is blank (see ensure_coach_photo()
// below). Independent from $domain_design's button_class colors above
// (those are much darker, chosen for button contrast, not a 400x400
// portrait swatch) but the same one-hue-per-domain idea.
$coach_placeholder_colors = array(
    "leadership"  => "#3b506b", // slate blue
    "followership" => "#4a3f6b", // indigo
    "creativity"  => "#596b3b", // olive green
    "change"      => "#6b5a3b", // amber/bronze
    "performance" => "#6b453c", // crimson/burgundy
    "intimate"    => "#6b3b51", // plum/wine
    "discovery"   => "#3b6b64", // teal
);

// A coach card with a blank `photo` field hasn't gotten a real headshot
// yet. Rather than special-case that in validate_content_references()'s
// cross-domain photo-reuse check (two blank photos would otherwise look
// like the same reused photo), generate a real, if placeholder, photo
// file for it — the same solid-color-plus-name style a prior hand-run
// ImageMagick pass used (git log: "Add Norm's performance coach card +
// placeholder photos for both of Norm's cards") — and wire the entry's
// `photo` field to it in memory, so every downstream consumer (the
// validator, the templates) sees a normal, non-blank photo like any
// other coach's. Written into content/img/ (not working/img/), so it's
// picked up by the verbatim content/img -> working/img rsync below like
// any other asset, and becomes a real committed file from then on — a
// generated placeholder today, replaceable with a real photo later,
// exactly like every other coach's still-placeholder headshot. Idempotent:
// re-running gen-site.php never regenerates a file that's already there.
function ensure_coach_photo(&$coach, $readprefix, $coach_placeholder_colors) {
    if (field($coach, 'photo') !== '') return;

    $coach_id = field($coach, 'coach_id');
    $domain   = field($coach, 'domain');
    $name     = field($coach, 'name');
    $filename = "coach-{$coach_id}-{$domain}.jpg";
    $path     = "{$readprefix}img/{$filename}";

    if (!file_exists($path)) {
        $color = isset($coach_placeholder_colors[$domain]) ? $coach_placeholder_colors[$domain] : '#4a4a4a';
        $cmd = sprintf(
            'magick -size 400x400 xc:%s -gravity center -fill white ' .
            '-pointsize 36 -annotate +0-20 %s -pointsize 22 -annotate +0+30 %s %s 2>&1',
            escapeshellarg($color),
            escapeshellarg($name),
            escapeshellarg("($domain)"),
            escapeshellarg($path)
        );
        exec($cmd, $output, $exit_code);
        if ($exit_code !== 0) {
            fwrite(STDERR, "WARNING: couldn't generate placeholder photo for coach \"$coach_id\" ($domain): " . implode("\n", $output) . "\n");
            return; // leave photo blank — the reuse check tolerates that
        }
    }

    $coach['data']['photo'] = $filename;
}

// ---------------------------------------------------------------------
// 1. Clean the output directory and stray editor backups in content.
// ---------------------------------------------------------------------
system("rm -rf {$writeprefix}*");
@mkdir($writeprefix, 0755, true);
system("find $readprefix -name '*~' -delete");

// ---------------------------------------------------------------------
// 2. Load every collection once, up front.
// ---------------------------------------------------------------------
$coaches      = load_collection("{$readprefix}coaches");
$testimonials = load_collection("{$readprefix}testimonials");
$field_notes     = sort_by_date_desc(load_collection("{$readprefix}field-notes"));
$events       = load_collection("{$readprefix}events");
$resource_items = load_collection("{$readprefix}resource-items");
$coach_landing = load_collection("{$readprefix}coach-landing");
$footer_entry = parse_entry_file("{$readprefix}footer.entry");
// The "Join the Collective" page (collective.html), linked from the footer
// beside Terms/Privacy; its `title` is also that link's label.
$collective_entry = parse_entry_file("{$readprefix}collective.entry");
// Landing copy for the central field-notes section (working/field-notes/)
// — sits beside the collection folder of the same name, not inside it,
// since load_collection() would otherwise load it as a field note.
$field_notes_entry = parse_entry_file("{$readprefix}field-notes.entry");
$hub_entry      = parse_entry_file("{$readprefix}index.entry");
$mission_entry  = parse_entry_file("{$readprefix}mission.entry");
$coaching_entry = parse_entry_file("{$readprefix}coaching.entry");
// Every root-level page's own entry, keyed by path under content/ — each
// picks its background photo with its own `bgimage` field.
$root_entries = array(
    'index'         => $hub_entry,
    'mission'       => $mission_entry,
    'coaching'      => $coaching_entry,
    'legal/terms'   => parse_entry_file("{$readprefix}legal/terms.entry"),
    'legal/privacy' => parse_entry_file("{$readprefix}legal/privacy.entry"),
    'collective'    => $collective_entry,
    'field-notes'   => $field_notes_entry,
);

// ---------------------------------------------------------------------
// 2a. Force a placeholder photo for any coach card that doesn't have a
// real one yet — see ensure_coach_photo() above.
// ---------------------------------------------------------------------
foreach ($coaches as &$c) {
    ensure_coach_photo($c, $readprefix, $coach_placeholder_colors);
}
unset($c);

// ---------------------------------------------------------------------
// 2b. Validate cross-references before writing anything — a typo'd domain
// or coach id should fail loudly, not silently drop content (plan §9).
// ---------------------------------------------------------------------
$problems = validate_content_references($domain_slugs, $coaches, $testimonials, $field_notes, $events, $resource_items, $coach_landing);
$problems = array_merge($problems, validate_bgimages(array_merge(array_values($root_entries), $coach_landing), "{$readprefix}img/"));
$hard_errors = print_problems($problems);
if ($hard_errors > 0) {
    fwrite(STDERR, "\nAborting: fix the content errors above before generating.\n");
    exit(1);
}

function footer_html_for($prefix, $footer_entry, $templates, $show_legal_links = true) {
    global $collective_entry;
    return render_template($templates . 'partials/site-footer.php', array(
        'prefix'           => $prefix,
        'collective_label' => field($collective_entry, 'title', 'Join the Collective'),
        'footer_body_html' => entry_html($footer_entry),
        'show_legal_links' => $show_legal_links,
    ));
}

// $in_sitemap defaults to true; coach-profile pages pass false. See
// "the sitemap.xml decision" (plan §7/§10): a coach keeps the same
// identifier across every domain they work in, so a sitemap listing (say)
// leadership/coach-jane.html next to intimate/coach-jane.html would hand
// over the exact connection on-site browsing was built to avoid — no
// clicking around required, just opening /sitemap.xml. Every other page
// type doesn't have that problem (a field note spanning domains is already a
// deliberately-accepted exposure, not a new one — see the plan), so only
// coach profiles are excluded.
function write_page($path, $shell_vars, $templates, $in_sitemap = true) {
    global $writeprefix, $sitemap_urls;
    $shell_vars['content_html'] = render_template($templates . $shell_vars['content_template'], $shell_vars);
    $html = render_template($templates . 'partials/shell.php', $shell_vars);
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, $html);
    if ($in_sitemap) {
        $sitemap_urls[] = ltrim(substr($path, strlen($writeprefix)), '/');
    }
}

$sitemap_urls = array();

// ---------------------------------------------------------------------
// 3. The hub (root landing page).
// ---------------------------------------------------------------------
$domain_entries = array();
foreach ($domain_slugs as $slug) {
    $domain_entries[$slug] = parse_entry_file("{$readprefix}{$slug}/index.entry");
}

// The nav's two labels are each pulled from that page's own `title`
// field rather than hardcoded separately in the nav partial, so the link
// text and the destination page's own heading can never drift apart.
// Nav HTML is rendered per root page rather than once and reused — each
// render gets told which page it's for ($current_slug) so that page can
// omit its own nav-item (no point linking somewhere you already are),
// same reasoning domain-nav.php never links a domain to itself.
function hub_nav_for($current_slug, $hub_entry, $mission_entry, $coaching_entry, $templates, $prefix = './') {
    global $field_notes_entry;
    return render_template($templates . 'partials/hub-nav.php', array(
        'prefix'         => $prefix,
        'field_notes_title' => field($field_notes_entry, 'title', 'Field Notes'),
        'current_slug'   => $current_slug,
        'site_title'     => field($hub_entry, 'title', 'Yomonsni'),
        'mission_title'  => field($mission_entry, 'title', 'Mission'),
        'coaching_title' => field($coaching_entry, 'title', 'Coaching'),
    ));
}

write_page("{$writeprefix}index.html", array(
    'prefix'          => './',
    'title'           => field($hub_entry, 'title', 'Yomonsni'),
    'description'     => meta_description($hub_entry),
    // The hero photo lives on the body (fixed/cover, from myfunk.css's
    // body{} rule), not the masthead — so it renders behind the whole
    // page, not just the header. templates/hub.php's masthead keeps only
    // its own gradient (no image) so the body's photo shows through there
    // too, continuously, instead of the masthead hiding it in that area.
    // Picked in content/index.entry's own `bgimage` field.
    'bgimage'         => field($hub_entry, 'bgimage'),
    'nav_html'        => hub_nav_for('hub', $hub_entry, $mission_entry, $coaching_entry, $templates),
    'footer_html'     => footer_html_for('./', $footer_entry, $templates),
    'content_template' => 'hub.php',
    'entry'           => $hub_entry,
    'domains'         => $domain_titles,
    'domain_entries'  => $domain_entries,
), $templates);

// ---------------------------------------------------------------------
// 4. Sitewide pages: mission, what-coaching-is, and legal (also
// root-level, sharing the hub's nav/footer).
// ---------------------------------------------------------------------
// Each page's background photo is its own entry's `bgimage` field.
foreach (array('mission', 'coaching', 'legal/terms', 'legal/privacy', 'collective') as $path) {
    $slug = basename($path);
    $entry = $root_entries[$path];
    write_page("{$writeprefix}{$slug}.html", array(
        'prefix'          => './',
        'title'           => field($entry, 'title', ucfirst($slug)),
        'description'     => meta_description($entry),
        'bgimage'         => field($entry, 'bgimage'),
        'nav_html'        => hub_nav_for($slug, $hub_entry, $mission_entry, $coaching_entry, $templates),
        'footer_html'     => footer_html_for('./', $footer_entry, $templates),
        'content_template' => 'simple-content.php',
        'entry'           => $entry,
    ), $templates);
}

// ---------------------------------------------------------------------
// 5. Coach landing pages — one per real coach (not per domain), from
// content/coach-landing/, for handing out privately (e.g. a business
// card) rather than linking to from anywhere on the site. Deliberately
// never mentions which domain(s) that coach actually works in: no
// booking link, no domain-scoped bio, and validate_coach_landing_pages()
// (run later, on the generated output) enforces that the only outbound
// links it ends up with are Home/Mission/Coaching — see CLAUDE.md's "The
// coach-privacy design". $show_legal_links=false on the footer for the
// same reason: Terms/Privacy aren't on that allowlist.
// ---------------------------------------------------------------------
foreach ($coach_landing as $cl) {
    $coach_id = field($cl, 'coach_id');
    write_page("{$writeprefix}coach-{$coach_id}.html", array(
        'prefix'          => './',
        'title'           => field($cl, 'name', $coach_id),
        'description'     => meta_description($cl),
        'bgimage'         => field($cl, 'bgimage'),
        'nav_html'        => hub_nav_for("coach-{$coach_id}", $hub_entry, $mission_entry, $coaching_entry, $templates),
        'footer_html'     => footer_html_for('./', $footer_entry, $templates, false),
        'content_template' => 'coach-landing.php',
        'entry'           => $cl,
    ), $templates, false); // excluded from sitemap.xml — not linked from anywhere, so it shouldn't be discoverable there either
}

// ---------------------------------------------------------------------
// 5a. The central field-notes section (field-notes/): one listing of every
// field note, newest first, under landing copy from content/
// field-notes.entry, plus one page per note. This is each note's one
// canonical home — the per-domain copies written in section 6 point here
// with rel=canonical. It is also the one place a note's `domains` are
// shown to a visitor: as buttons at the foot of the note, through to each
// tagged domain's overview page, so a reader who came for the writing has
// a way on to the coaching. The byline here is plain text, never a link —
// a coach's profile is always a specific domain's page.
// ---------------------------------------------------------------------
$site_name = field($hub_entry, 'title', 'Yomonsni');
$fn_title  = field($field_notes_entry, 'title', 'Field Notes');
$fn_bg     = field($field_notes_entry, 'bgimage');
$fn_footer = footer_html_for('../', $footer_entry, $templates);
$central_cards = array();
foreach ($field_notes as $w) {
    $applies_to = array();
    foreach ($domain_slugs as $d) { // site display order, not the order typed in the entry
        if (in_array($d, entry_list(field($w, 'domains')), true)) {
            $applies_to[$d] = array(
                'title'        => $domain_titles[$d],
                'button_class' => domain_design($domain_design, $d, 'button_class', ''),
            );
        }
    }
    $central_cards[] = render_template($templates . 'partials/field-note-teaser-card.php', array(
        'entry' => $w, 'coaches' => $coaches, 'domain_slug' => '',
        'href' => "{$w['slug']}.html", 'show_date' => true, 'col_class' => 'col-12 col-md-6 col-lg-4',
    ));
    write_page("{$writeprefix}field-notes/{$w['slug']}.html", array(
        'prefix'          => '../',
        'title'           => field($w, 'title') . " — $fn_title | $site_name",
        'description'     => meta_description($w, array('teaser')),
        'bgimage'         => $fn_bg,
        'nav_html'        => hub_nav_for('field-note', $hub_entry, $mission_entry, $coaching_entry, $templates, '../'),
        'footer_html'     => $fn_footer,
        'content_template' => 'field-note-article.php',
        'entry' => $w, 'coaches' => $coaches, 'domain_slug' => '', 'applies_to' => $applies_to,
        'booking_coach' => coach_booking_card($coaches, field($w, 'coach'), array_keys($applies_to)),
    ), $templates);
}
write_page("{$writeprefix}field-notes/index.html", array(
    'prefix'          => '../',
    'title'           => $fn_title,
    'description'     => meta_description($field_notes_entry),
    'bgimage'         => $fn_bg,
    'nav_html'        => hub_nav_for('field-notes', $hub_entry, $mission_entry, $coaching_entry, $templates, '../'),
    'footer_html'     => $fn_footer,
    'content_template' => 'card-list.php',
    'cards' => $central_cards, 'intro_html' => entry_html($field_notes_entry),
    'subtitle' => field($field_notes_entry, 'subtitle'),
    'empty_message' => 'No field notes published yet.',
), $templates);

// ---------------------------------------------------------------------
// 6. Each domain: overview, resources, coach profiles, testimonials,
//    field notes (+ individual field note pages), events if any.
// ---------------------------------------------------------------------
foreach ($domain_slugs as $slug) {
    $prefix = '../';
    $title  = $domain_titles[$slug];

    $d_coaches      = sort_by_order(filter_by_domain($coaches, $slug, 'domain'));
    $d_testimonials = filter_by_domain($testimonials, $slug, 'domain');
    $d_field_notes     = filter_by_domain($field_notes, $slug, 'domains');
    $d_events       = filter_by_domain($events, $slug, 'domain');
    $d_resource_items = filter_by_domain($resource_items, $slug, 'domain');

    $nav_html = render_template($templates . 'partials/domain-nav.php', array(
        'prefix' => $prefix, 'domain_slug' => $slug, 'domain_title' => $title,
        'has_events' => !empty($d_events),
    ));
    $footer_html = footer_html_for($prefix, $footer_entry, $templates);
    $common = array('prefix' => $prefix, 'nav_html' => $nav_html, 'footer_html' => $footer_html);

    // -- overview / index.html --
    $resources_entry = parse_entry_file("{$readprefix}{$slug}/resources.entry");
    $bgimage      = domain_design($domain_design, $slug, 'bg', "{$slug}-bg.jpg");
    // 'left' is the shared default, not a creativity-only choice — the
    // intro-beside-coaches layout (see templates/domain-overview.php) is
    // meant to be how every domain's page works, with only which side
    // the text lands on left open per domain. Only the color/image
    // choices below (bg/card_class/button_class) are meant to vary.
    $text_align   = domain_design($domain_design, $slug, 'text_align', 'left');
    $card_class   = domain_design($domain_design, $slug, 'card_class', '');
    $button_class = domain_design($domain_design, $slug, 'button_class', '');
    write_page("{$writeprefix}{$slug}/index.html", $common + array(
        'title' => $title, 'bgimage' => $bgimage,
        'description' => meta_description($domain_entries[$slug]),
        'content_template' => 'domain-overview.php',
        'entry' => $domain_entries[$slug], 'domain_slug' => $slug, 'domain_title' => $title,
        'coaches' => $d_coaches, 'all_coaches' => $coaches, 'resources_entry' => $resources_entry,
        'field_notes' => $d_field_notes, 'events' => $d_events,
        'text_align' => $text_align, 'card_class' => $card_class, 'button_class' => $button_class,
    ), $templates);

    // -- resources.html --
    write_page("{$writeprefix}{$slug}/resources.html", $common + array(
        'title' => field($resources_entry, 'title', "$title resources"), 'bgimage' => $bgimage,
        'description' => meta_description($resources_entry, array('teaser')),
        'content_template' => 'resources-page.php', 'entry' => $resources_entry,
        'resource_items' => $d_resource_items, 'coaches' => $coaches, 'domain_slug' => $slug,
        'card_class' => $card_class, 'button_class' => $button_class,
    ), $templates);

    // -- coach-<id>.html, one per coach card in this domain --
    foreach ($d_coaches as $coach) {
        $coach_id = field($coach, 'coach_id');
        $coach_testimonials = entries_for_coach($testimonials, $coach_id, $slug);
        // $d_field_notes is already filtered to this domain (and sorted
        // newest-first) — narrow further to just this coach's own. Can't
        // reuse entries_for_coach() here: it checks a single 'domain'
        // field, but a field note's domain membership lives in 'domains'
        // (comma-separated, already resolved into $d_field_notes).
        $coach_field_notes = array_values(array_filter($d_field_notes, function ($w) use ($coach_id) {
            return field($w, 'coach') === $coach_id;
        }));
        write_page("{$writeprefix}{$slug}/coach-{$coach_id}.html", $common + array(
            'title' => field($coach, 'name', $coach_id) . " — $title", 'bgimage' => $bgimage,
            'description' => meta_description($coach, array('summary')),
            'content_template' => 'coach-profile.php', 'entry' => $coach, 'testimonials' => $coach_testimonials,
            'coach_field_notes' => $coach_field_notes, 'coaches' => $coaches, 'domain_slug' => $slug,
            'card_class' => $card_class, 'button_class' => $button_class,
        ), $templates, false); // excluded from sitemap.xml — see write_page()
    }

    // -- testimonials.html --
    $testimonial_cards = array();
    foreach ($d_testimonials as $t) {
        $testimonial_cards[] = render_template($templates . 'partials/testimonial-card.php', array('entry' => $t, 'coaches' => $coaches, 'domain_slug' => $slug, 'card_class' => $card_class));
    }
    write_page("{$writeprefix}{$slug}/testimonials.html", $common + array(
        'title' => "$title testimonials", 'bgimage' => $bgimage,
        'description' => "What clients have said about working with {$site_name}'s $title coaches.",
        'content_template' => 'card-list.php', 'cards' => $testimonial_cards,
        'empty_message' => 'No testimonials published for this domain yet.',
    ), $templates);

    // -- field-notes.html (teasers) + one page per field note --
    $field_note_cards = array();
    foreach ($d_field_notes as $w) {
        // 'coaches' here is deliberately the *global*, un-filtered
        // collection, not $d_coaches — resolve_coach_name() needs to see
        // a coach's cards in domains other than this one to fall back
        // correctly when a field note is tagged into a domain its author
        // has no card in at all (a real case: field notes can span domains
        // a coach doesn't formally coach in). Passing the domain-filtered
        // list here silently broke that fallback (rendered the bare
        // coach_id instead of a real name) until this was traced.
        $field_note_cards[] = render_template($templates . 'partials/field-note-teaser-card.php', array(
            'entry' => $w, 'coaches' => $coaches, 'domain_slug' => $slug, 'card_class' => $card_class, 'button_class' => $button_class,
        ));
        write_page("{$writeprefix}{$slug}/field-note-{$w['slug']}.html", $common + array(
            'title' => field($w, 'title'), 'bgimage' => $bgimage,
            'description' => meta_description($w, array('teaser')),
            // This domain's copy of the note exists so a reader stays
            // inside the domain's own nav; the one a search engine should
            // index is the central copy (section 5a), which is also why
            // this one stays out of sitemap.xml.
            'canonical' => "https://{$site_domain}/field-notes/{$w['slug']}.html",
            'content_template' => 'field-note-article.php', 'entry' => $w,
            'coaches' => $coaches, 'domain_slug' => $slug, 'button_class' => $button_class,
            'booking_coach' => coach_booking_card($coaches, field($w, 'coach'), array($slug)),
        ), $templates, false);
    }
    write_page("{$writeprefix}{$slug}/field-notes.html", $common + array(
        'title' => "$title Field Notes", 'bgimage' => $bgimage,
        'description' => "$title field notes from {$site_name}, drawn from our coaches' observations and experimentations in life.",
        'content_template' => 'card-list.php', 'cards' => $field_note_cards,
        'intro' => "Drawn from our coaches' observations and experimentations in life.",
        'empty_message' => 'No field notes published for this domain yet.',
    ), $templates);

    // -- events.html (teasers, workshops + retreats together) + one full
    // page per event, only if this domain has any --
    if (!empty($d_events)) {
        $cards = array();
        foreach ($d_events as $e) {
            $cards[] = render_template($templates . 'partials/event-card.php', array(
                'entry' => $e, 'coaches' => $coaches, 'domain_slug' => $slug,
                'card_class' => $card_class, 'button_class' => $button_class,
            ));
            write_page("{$writeprefix}{$slug}/event-{$e['slug']}.html", $common + array(
                'title' => field($e, 'title') . " — $title", 'bgimage' => $bgimage,
                'description' => meta_description($e, array('summary')),
                'content_template' => 'event-article.php', 'entry' => $e,
                'coaches' => $coaches, 'domain_slug' => $slug, 'button_class' => $button_class,
            ), $templates);
        }
        write_page("{$writeprefix}{$slug}/events.html", $common + array(
            'title' => "$title events", 'bgimage' => $bgimage,
            'description' => "Upcoming $title workshops and retreats from {$site_name}.",
            'content_template' => 'card-list.php', 'cards' => $cards, 'empty_message' => '',
        ), $templates);
    }
}

// ---------------------------------------------------------------------
// 7. sitemap.xml — everything write_page() registered except coach
// profiles (see the comment on write_page() above).
// ---------------------------------------------------------------------
$sitemap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$sitemap .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($sitemap_urls as $rel) {
    $sitemap .= "  <url><loc>https://{$site_domain}/{$rel}</loc></url>\n";
}
$sitemap .= "</urlset>\n";
file_put_contents("{$writeprefix}sitemap.xml", $sitemap);

// robots.txt — everything is crawlable (including css/js/img, which a
// search engine needs in order to render a page the way a visitor sees
// it), plus a pointer at the sitemap. Deliberately has no Disallow lines
// for coach pages: robots.txt is itself a public file, so listing
// coach-<id>.html paths in it would publish exactly the per-coach
// manifest sitemap.xml is careful to leave out (see write_page() above).
file_put_contents("{$writeprefix}robots.txt", "User-agent: *\nAllow: /\n\nSitemap: https://{$site_domain}/sitemap.xml\n");

// ---------------------------------------------------------------------
// 8. Non-templated asset directories, copied verbatim.
// ---------------------------------------------------------------------
foreach ($noworkdir as $which) {
    system("rsync -a --delete {$readprefix}{$which} {$writeprefix}");
}

// ---------------------------------------------------------------------
// 9. Validate the generated output (dead/absolute/cross-domain links,
//    missing images, external link liveness, sitemap exclusions — §9).
// ---------------------------------------------------------------------
$output_problems = validate_output($writeprefix, $domain_slugs, $site_domain);
$output_problems = array_merge($output_problems, validate_sitemap($writeprefix));
$output_problems = array_merge($output_problems, validate_coach_landing_pages($writeprefix));

// A hand-written `description` can go stale when its page's body is
// rewritten — warn (never an error) when that looks to have happened, and
// when entries are still running on the automatic fallback. See
// check_descriptions() and suggest-descriptions.php.
$output_problems = array_merge($output_problems, check_descriptions(description_entries($readprefix), __DIR__ . '/description-baselines.json'));
$hard_errors = print_problems($output_problems);

// ---------------------------------------------------------------------
// 10. Mirror to /tmp/foo — the local dry-run/preview step. Not debugging
//    leftovers; this is what gets browsed before push-site.php ever runs.
// ---------------------------------------------------------------------
system("rsync -a $writeprefix /tmp/foo");

// Record the result so push-site.php can refuse to deploy a generation
// that failed validation (plan §9's final gate).
@mkdir(__DIR__ . '/var', 0755, true);
file_put_contents(__DIR__ . '/var/last-generation.json', json_encode(array(
    'generated_at' => time(),
    'errors'       => $hard_errors,
), JSON_PRETTY_PRINT));

if ($hard_errors > 0) {
    fwrite(STDERR, "\nSite generated with $hard_errors validation error(s) — review before pushing.\n");
    exit(1);
}
