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

// The six coaching domains. Order here is nav/hub display order.
$domain_titles = array(
    "leadership"  => "Leadership",
    "creativity"  => "Creativity",
    "change"      => "Change & Transitions",
    "performance" => "Performance",
    "intimate"    => "Intimate",
    "discovery"   => "Discovery",
);
$domain_slugs = array_keys($domain_titles);

// Per-domain visual tuning. The domain overview page's *layout* (intro
// beside a Coaches/Resources/Whispers/Workshops stack, centered section
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
    "change" => array(
        'bg'           => 'yo-IMG_08577-5DSR-raw16-rawtherapee-shaped.jpg',
        'button_class' => 'btn-change', // dark amber/bronze
    ),
    "performance" => array(
        'bg'           => 'yo-IMG_22696-5D-raw32-rawtherapee.jpg',
        'button_class' => 'btn-performance', // dark crimson/burgundy
    ),
    "intimate" => array(
        'bg'           => 'yo-IMG_43079-5D-raw16-rawtherapee-shaped-flattened.jpg',
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
$whispers     = sort_by_date_desc(load_collection("{$readprefix}whispers"));
$events       = load_collection("{$readprefix}events");
$resource_items = load_collection("{$readprefix}resource-items");
$footer_entry = parse_entry_file("{$readprefix}footer.entry");

// ---------------------------------------------------------------------
// 2a. Validate cross-references before writing anything — a typo'd domain
// or coach id should fail loudly, not silently drop content (plan §9).
// ---------------------------------------------------------------------
$problems = validate_content_references($domain_slugs, $coaches, $testimonials, $whispers, $events, $resource_items);
$hard_errors = print_problems($problems);
if ($hard_errors > 0) {
    fwrite(STDERR, "\nAborting: fix the content errors above before generating.\n");
    exit(1);
}

function footer_html_for($prefix, $footer_entry, $templates) {
    return render_template($templates . 'partials/site-footer.php', array(
        'prefix'           => $prefix,
        'footer_body_html' => entry_html($footer_entry),
    ));
}

// $in_sitemap defaults to true; coach-profile pages pass false. See
// "the sitemap.xml decision" (plan §7/§10): a coach keeps the same
// identifier across every domain they work in, so a sitemap listing (say)
// leadership/coach-jane.html next to intimate/coach-jane.html would hand
// over the exact connection on-site browsing was built to avoid — no
// clicking around required, just opening /sitemap.xml. Every other page
// type doesn't have that problem (a whisper spanning domains is already a
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
$hub_entry = parse_entry_file("{$readprefix}index.entry");
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
$mission_entry = parse_entry_file("{$readprefix}mission.entry");
$coaching_entry = parse_entry_file("{$readprefix}coaching.entry");
function hub_nav_for($current_slug, $hub_entry, $mission_entry, $coaching_entry, $templates) {
    return render_template($templates . 'partials/hub-nav.php', array(
        'prefix'         => './',
        'current_slug'   => $current_slug,
        'site_title'     => field($hub_entry, 'title', 'Yomonsni'),
        'mission_title'  => field($mission_entry, 'title', 'Mission'),
        'coaching_title' => field($coaching_entry, 'title', 'Coaching'),
    ));
}

write_page("{$writeprefix}index.html", array(
    'prefix'          => './',
    'title'           => field($hub_entry, 'title', 'Yomonsni'),
    // The hero photo lives on the body (fixed/cover, from myfunk.css's
    // body{} rule), not the masthead — so it renders behind the whole
    // page, not just the header. templates/hub.php's masthead keeps only
    // its own gradient (no image) so the body's photo shows through there
    // too, continuously, instead of the masthead hiding it in that area.
    'bgimage'         => 'yo-IMG_56547-5DII-raw16-rawtherapee-shaped.jpg',
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
// Background photos for these, picked per page same as $domain_design —
// still empty by default (see CLAUDE.md's "Known gaps"), filled in one
// page at a time rather than all at once.
$root_page_bgimages = array(
    'mission' => 'yo-IMG_57108-5DII-raw32-rawtherapee-shaped.jpg',
);
$already_parsed = array('mission' => $mission_entry, 'coaching' => $coaching_entry);
foreach (array('mission', 'coaching', 'legal/terms', 'legal/privacy') as $path) {
    $slug = basename($path);
    $entry = isset($already_parsed[$path]) ? $already_parsed[$path] : parse_entry_file("{$readprefix}{$path}.entry");
    write_page("{$writeprefix}{$slug}.html", array(
        'prefix'          => './',
        'title'           => field($entry, 'title', ucfirst($slug)),
        'bgimage'         => isset($root_page_bgimages[$slug]) ? $root_page_bgimages[$slug] : '',
        'nav_html'        => hub_nav_for($slug, $hub_entry, $mission_entry, $coaching_entry, $templates),
        'footer_html'     => footer_html_for('./', $footer_entry, $templates),
        'content_template' => 'simple-content.php',
        'entry'           => $entry,
    ), $templates);
}

// ---------------------------------------------------------------------
// 5. Each domain: overview, resources, coach profiles, testimonials,
//    whispers (+ individual whisper pages), events if any.
// ---------------------------------------------------------------------
foreach ($domain_slugs as $slug) {
    $prefix = '../';
    $title  = $domain_titles[$slug];

    $d_coaches      = sort_by_order(filter_by_domain($coaches, $slug, 'domain'));
    $d_testimonials = filter_by_domain($testimonials, $slug, 'domain');
    $d_whispers     = filter_by_domain($whispers, $slug, 'domains');
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
        'content_template' => 'domain-overview.php',
        'entry' => $domain_entries[$slug], 'domain_slug' => $slug, 'domain_title' => $title,
        'coaches' => $d_coaches, 'all_coaches' => $coaches, 'resources_entry' => $resources_entry,
        'whispers' => $d_whispers, 'events' => $d_events,
        'text_align' => $text_align, 'card_class' => $card_class, 'button_class' => $button_class,
    ), $templates);

    // -- resources.html --
    write_page("{$writeprefix}{$slug}/resources.html", $common + array(
        'title' => field($resources_entry, 'title', "$title resources"), 'bgimage' => $bgimage,
        'content_template' => 'resources-page.php', 'entry' => $resources_entry,
        'resource_items' => $d_resource_items, 'coaches' => $coaches, 'domain_slug' => $slug,
        'card_class' => $card_class, 'button_class' => $button_class,
    ), $templates);

    // -- coach-<id>.html, one per coach card in this domain --
    foreach ($d_coaches as $coach) {
        $coach_id = field($coach, 'coach_id');
        $coach_testimonials = entries_for_coach($testimonials, $coach_id, $slug);
        // $d_whispers is already filtered to this domain (and sorted
        // newest-first) — narrow further to just this coach's own. Can't
        // reuse entries_for_coach() here: it checks a single 'domain'
        // field, but a whisper's domain membership lives in 'domains'
        // (comma-separated, already resolved into $d_whispers).
        $coach_whispers = array_values(array_filter($d_whispers, function ($w) use ($coach_id) {
            return field($w, 'coach') === $coach_id;
        }));
        write_page("{$writeprefix}{$slug}/coach-{$coach_id}.html", $common + array(
            'title' => field($coach, 'name', $coach_id) . " — $title", 'bgimage' => $bgimage,
            'content_template' => 'coach-profile.php', 'entry' => $coach, 'testimonials' => $coach_testimonials,
            'coach_whispers' => $coach_whispers, 'coaches' => $coaches, 'domain_slug' => $slug,
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
        'content_template' => 'card-list.php', 'cards' => $testimonial_cards,
        'empty_message' => 'No testimonials published for this domain yet.',
    ), $templates);

    // -- whispers.html (teasers) + one page per whisper --
    $whisper_cards = array();
    foreach ($d_whispers as $w) {
        // 'coaches' here is deliberately the *global*, un-filtered
        // collection, not $d_coaches — resolve_coach_name() needs to see
        // a coach's cards in domains other than this one to fall back
        // correctly when a whisper is tagged into a domain its author
        // has no card in at all (a real case: whispers can span domains
        // a coach doesn't formally coach in). Passing the domain-filtered
        // list here silently broke that fallback (rendered the bare
        // coach_id instead of a real name) until this was traced.
        $whisper_cards[] = render_template($templates . 'partials/whisper-teaser-card.php', array(
            'entry' => $w, 'coaches' => $coaches, 'domain_slug' => $slug, 'card_class' => $card_class, 'button_class' => $button_class,
        ));
        write_page("{$writeprefix}{$slug}/whisper-{$w['slug']}.html", $common + array(
            'title' => field($w, 'title'), 'bgimage' => $bgimage,
            'content_template' => 'whisper-article.php', 'entry' => $w,
            'coaches' => $coaches, 'domain_slug' => $slug,
        ), $templates);
    }
    write_page("{$writeprefix}{$slug}/whispers.html", $common + array(
        'title' => "$title whispers", 'bgimage' => $bgimage,
        'content_template' => 'card-list.php', 'cards' => $whisper_cards,
        'empty_message' => 'No whispers published for this domain yet.',
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
                'content_template' => 'event-article.php', 'entry' => $e,
                'coaches' => $coaches, 'domain_slug' => $slug, 'button_class' => $button_class,
            ), $templates);
        }
        write_page("{$writeprefix}{$slug}/events.html", $common + array(
            'title' => "$title events", 'bgimage' => $bgimage,
            'content_template' => 'card-list.php', 'cards' => $cards, 'empty_message' => '',
        ), $templates);
    }
}

// ---------------------------------------------------------------------
// 6. sitemap.xml — everything write_page() registered except coach
// profiles (see the comment on write_page() above).
// ---------------------------------------------------------------------
$sitemap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$sitemap .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($sitemap_urls as $rel) {
    $sitemap .= "  <url><loc>https://{$site_domain}/{$rel}</loc></url>\n";
}
$sitemap .= "</urlset>\n";
file_put_contents("{$writeprefix}sitemap.xml", $sitemap);

// ---------------------------------------------------------------------
// 7. Non-templated asset directories, copied verbatim.
// ---------------------------------------------------------------------
foreach ($noworkdir as $which) {
    system("rsync -a --delete {$readprefix}{$which} {$writeprefix}");
}

// ---------------------------------------------------------------------
// 8. Validate the generated output (dead/absolute/cross-domain links,
//    missing images, external link liveness, sitemap exclusions — §9).
// ---------------------------------------------------------------------
$output_problems = validate_output($writeprefix, $domain_slugs, $site_domain);
$output_problems = array_merge($output_problems, validate_sitemap($writeprefix));
$hard_errors = print_problems($output_problems);

// ---------------------------------------------------------------------
// 9. Mirror to /tmp/foo — the local dry-run/preview step. Not debugging
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
