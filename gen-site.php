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
$workshops    = load_collection("{$readprefix}workshops");
$retreats     = load_collection("{$readprefix}retreats");
$footer_entry = parse_entry_file("{$readprefix}footer.entry");

// ---------------------------------------------------------------------
// 2a. Validate cross-references before writing anything — a typo'd domain
// or coach id should fail loudly, not silently drop content (plan §9).
// ---------------------------------------------------------------------
$problems = validate_content_references($domain_slugs, $coaches, $testimonials, $whispers);
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

$hub_nav_html = render_template($templates . 'partials/hub-nav.php', array('prefix' => './'));

write_page("{$writeprefix}index.html", array(
    'prefix'          => './',
    'title'           => field($hub_entry, 'title', 'Yomonsni'),
    // The hero photo lives on the body (fixed/cover, from myfunk.css's
    // body{} rule), not the masthead — so it renders behind the whole
    // page, not just the header. templates/hub.php's masthead keeps only
    // its own gradient (no image) so the body's photo shows through there
    // too, continuously, instead of the masthead hiding it in that area.
    'bgimage'         => 'yo-IMG_56547-5DII-raw16-rawtherapee-shaped.jpg',
    'nav_html'        => $hub_nav_html,
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
foreach (array('mission', 'coaching', 'legal/terms', 'legal/privacy') as $path) {
    $slug = basename($path);
    $entry = parse_entry_file("{$readprefix}{$path}.entry");
    write_page("{$writeprefix}{$slug}.html", array(
        'prefix'          => './',
        'title'           => field($entry, 'title', ucfirst($slug)),
        'bgimage'         => '',
        'nav_html'        => $hub_nav_html,
        'footer_html'     => footer_html_for('./', $footer_entry, $templates),
        'content_template' => 'simple-content.php',
        'entry'           => $entry,
    ), $templates);
}

// ---------------------------------------------------------------------
// 5. Each domain: overview, resources, coach profiles, testimonials,
//    whispers (+ individual whisper pages), workshops/retreats if any.
// ---------------------------------------------------------------------
foreach ($domain_slugs as $slug) {
    $prefix = '../';
    $title  = $domain_titles[$slug];

    $d_coaches      = filter_by_domain($coaches, $slug, 'domain');
    $d_testimonials = filter_by_domain($testimonials, $slug, 'domain');
    $d_whispers     = filter_by_domain($whispers, $slug, 'domains');
    $d_workshops    = filter_by_domain($workshops, $slug, 'domain');
    $d_retreats     = filter_by_domain($retreats, $slug, 'domain');

    $nav_html = render_template($templates . 'partials/domain-nav.php', array(
        'prefix' => $prefix, 'domain_slug' => $slug, 'domain_title' => $title,
        'has_workshops' => !empty($d_workshops), 'has_retreats' => !empty($d_retreats),
    ));
    $footer_html = footer_html_for($prefix, $footer_entry, $templates);
    $common = array('prefix' => $prefix, 'nav_html' => $nav_html, 'footer_html' => $footer_html);

    // -- overview / index.html --
    $resources_entry = parse_entry_file("{$readprefix}{$slug}/resources.entry");
    write_page("{$writeprefix}{$slug}/index.html", $common + array(
        'title' => $title, 'bgimage' => "{$slug}-bg.jpg",
        'content_template' => 'domain-overview.php',
        'entry' => $domain_entries[$slug], 'domain_slug' => $slug, 'domain_title' => $title,
        'coaches' => $d_coaches, 'resources_entry' => $resources_entry,
        'whispers' => $d_whispers, 'workshops' => $d_workshops, 'retreats' => $d_retreats,
    ), $templates);

    // -- resources.html --
    write_page("{$writeprefix}{$slug}/resources.html", $common + array(
        'title' => field($resources_entry, 'title', "$title resources"), 'bgimage' => '',
        'content_template' => 'simple-content.php', 'entry' => $resources_entry,
    ), $templates);

    // -- coach-<id>.html, one per coach card in this domain --
    foreach ($d_coaches as $coach) {
        $coach_id = field($coach, 'coach_id');
        $coach_testimonials = entries_for_coach($testimonials, $coach_id, $slug);
        write_page("{$writeprefix}{$slug}/coach-{$coach_id}.html", $common + array(
            'title' => field($coach, 'name', $coach_id) . " — $title", 'bgimage' => '',
            'content_template' => 'coach-profile.php', 'entry' => $coach, 'testimonials' => $coach_testimonials,
        ), $templates, false); // excluded from sitemap.xml — see write_page()
    }

    // -- testimonials.html --
    $testimonial_cards = array();
    foreach ($d_testimonials as $t) {
        $testimonial_cards[] = render_template($templates . 'partials/testimonial-card.php', array('entry' => $t));
    }
    write_page("{$writeprefix}{$slug}/testimonials.html", $common + array(
        'title' => "$title testimonials", 'bgimage' => '',
        'content_template' => 'card-list.php', 'cards' => $testimonial_cards,
        'empty_message' => 'No testimonials published for this domain yet.',
    ), $templates);

    // -- whispers.html (teasers) + one page per whisper --
    $whisper_cards = array();
    foreach ($d_whispers as $w) {
        $whisper_cards[] = render_template($templates . 'partials/whisper-teaser-card.php', array(
            'entry' => $w, 'coaches' => $d_coaches, 'domain_slug' => $slug,
        ));
        write_page("{$writeprefix}{$slug}/whisper-{$w['slug']}.html", $common + array(
            'title' => field($w, 'title'), 'bgimage' => '',
            'content_template' => 'whisper-article.php', 'entry' => $w,
            'coaches' => $d_coaches, 'domain_slug' => $slug,
        ), $templates);
    }
    write_page("{$writeprefix}{$slug}/whispers.html", $common + array(
        'title' => "$title whispers", 'bgimage' => '',
        'content_template' => 'card-list.php', 'cards' => $whisper_cards,
        'empty_message' => 'No whispers published for this domain yet.',
    ), $templates);

    // -- workshops.html / retreats.html, only if this domain has any --
    if (!empty($d_workshops)) {
        $cards = array();
        foreach ($d_workshops as $w) $cards[] = render_template($templates . 'partials/event-card.php', array('entry' => $w));
        write_page("{$writeprefix}{$slug}/workshops.html", $common + array(
            'title' => "$title workshops", 'bgimage' => '',
            'content_template' => 'card-list.php', 'cards' => $cards, 'empty_message' => '',
        ), $templates);
    }
    if (!empty($d_retreats)) {
        $cards = array();
        foreach ($d_retreats as $r) $cards[] = render_template($templates . 'partials/event-card.php', array('entry' => $r));
        write_page("{$writeprefix}{$slug}/retreats.html", $common + array(
            'title' => "$title retreats", 'bgimage' => '',
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
