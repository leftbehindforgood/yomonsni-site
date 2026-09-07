<?php
// Guardrails from the restructure plan (§7, §9), made concrete. Two passes:
//
//   validate_content_references() — runs on the loaded collections, before
//   any HTML is written. Catches authoring mistakes (typo'd domain slug,
//   testimonial pointing at a coach who doesn't exist) that would otherwise
//   silently drop content with no error.
//
//   validate_output() — runs after generation, by walking the actual
//   generated HTML. Catches dead links/images, absolute internal links,
//   cross-domain leaks, and (with a weekly cache) dead external links.
//
// Both return an array of ['level' => 'error'|'warning', 'message' => ...].

function validate_content_references($domain_slugs, $coaches, $testimonials, $whispers, $events = array(), $resource_items = array(), $coach_landing = array()) {
    $problems = array();

    $coach_keys = array(); // "domain|coach_id" => true
    $coach_photos_by_id = array(); // coach_id => [photo, photo, ...] across all that coach's domain cards
    foreach ($coaches as $c) {
        $coach_keys[field($c, 'domain') . '|' . field($c, 'coach_id')] = true;
        $coach_photos_by_id[field($c, 'coach_id')][] = field($c, 'photo');
    }

    foreach ($testimonials as $t) {
        $domain = field($t, 'domain');
        $coach  = field($t, 'coach');
        if (!in_array($domain, $domain_slugs, true)) {
            $problems[] = array('level' => 'error', 'message' =>
                "{$t['path']}: domain \"$domain\" is not a known domain slug");
            continue;
        }
        if (!isset($coach_keys["$domain|$coach"])) {
            $problems[] = array('level' => 'error', 'message' =>
                "{$t['path']}: coach \"$coach\" has no coach card in domain \"$domain\"");
        }
    }

    foreach ($whispers as $w) {
        foreach (entry_list(field($w, 'domains')) as $domain) {
            if (!in_array($domain, $domain_slugs, true)) {
                $problems[] = array('level' => 'error', 'message' =>
                    "{$w['path']}: domain \"$domain\" is not a known domain slug");
            }
        }
    }

    // A workshop/retreat's `coaches` is optional (not every event is run
    // by a named coach) and, unlike a testimonial's single `coach`, a
    // comma-separated list — an event can have more than one coach
    // running it. Each one named has to resolve the same way a
    // testimonial's does.
    foreach ($events as $e) {
        $domain = field($e, 'domain');
        if (!in_array($domain, $domain_slugs, true)) {
            $problems[] = array('level' => 'error', 'message' =>
                "{$e['path']}: domain \"$domain\" is not a known domain slug");
            continue;
        }
        foreach (entry_list(field($e, 'coaches')) as $coach) {
            if (!isset($coach_keys["$domain|$coach"])) {
                $problems[] = array('level' => 'error', 'message' =>
                    "{$e['path']}: coach \"$coach\" has no coach card in domain \"$domain\"");
            }
        }
    }

    // A resource's `coach` (who recommends it) is optional and, like a
    // testimonial's, a single coach_id.
    foreach ($resource_items as $r) {
        $domain = field($r, 'domain');
        if (!in_array($domain, $domain_slugs, true)) {
            $problems[] = array('level' => 'error', 'message' =>
                "{$r['path']}: domain \"$domain\" is not a known domain slug");
            continue;
        }
        $coach = field($r, 'coach');
        if ($coach !== '' && !isset($coach_keys["$domain|$coach"])) {
            $problems[] = array('level' => 'error', 'message' =>
                "{$r['path']}: coach \"$coach\" has no coach card in domain \"$domain\"");
        }
    }

    // Coach-privacy guardrail: the same photo file must never be used by a
    // coach's cards in more than one domain (restructure plan §3/§7).
    $photo_domains = array(); // coach_id|photo => [domains]
    foreach ($coaches as $c) {
        $key = field($c, 'coach_id') . '|' . field($c, 'photo');
        $photo_domains[$key][] = field($c, 'domain');
    }
    foreach ($photo_domains as $key => $domains_used) {
        if (count(array_unique($domains_used)) > 1) {
            list($coach_id, $photo) = explode('|', $key, 2);
            $problems[] = array('level' => 'error', 'message' =>
                "coach \"$coach_id\" reuses photo \"$photo\" across domains: " . implode(', ', $domains_used));
        }
    }

    // A coach landing page (content/coach-landing/, see gen-site.php) is
    // meant to be handed out without revealing which domain(s) that coach
    // works in — its `coach_id` must belong to a real coach (at least one
    // domain card), and its own `photo` must be genuinely new, not one
    // already seen on any of that coach's domain cards. Reusing one would
    // let anyone who's seen that domain card recognize the same face on
    // this supposedly domain-neutral page and infer the connection
    // backwards — exactly what this whole design exists to prevent.
    foreach ($coach_landing as $cl) {
        $coach_id = field($cl, 'coach_id');
        if (!isset($coach_photos_by_id[$coach_id])) {
            $problems[] = array('level' => 'error', 'message' =>
                "{$cl['path']}: coach_id \"$coach_id\" has no coach card in any domain");
            continue;
        }
        $photo = field($cl, 'photo');
        if ($photo !== '' && in_array($photo, $coach_photos_by_id[$coach_id], true)) {
            $problems[] = array('level' => 'error', 'message' =>
                "{$cl['path']}: photo \"$photo\" is already used on one of coach \"$coach_id\"'s domain cards — a landing page photo must be different");
        }
    }

    return $problems;
}

function validate_output($writeprefix, $domain_slugs, $site_domain) {
    $problems = array();
    $writeprefix = rtrim($writeprefix, '/');

    $files = glob("$writeprefix/*.html");
    foreach ($domain_slugs as $slug) {
        $files = array_merge($files, glob("$writeprefix/$slug/*.html"));
    }

    $external_urls = array();

    foreach ($files as $file) {
        $html = file_get_contents($file);
        $page_domain = null; // which domain directory this page lives in, if any
        foreach ($domain_slugs as $slug) {
            if (strpos($file, "$writeprefix/$slug/") === 0) { $page_domain = $slug; break; }
        }
        $page_dir = dirname($file);

        // hrefs
        if (preg_match_all('/href="([^"]+)"/', $html, $m)) {
            foreach ($m[1] as $href) {
                if ($href === '' || $href[0] === '#') continue;

                if (preg_match('#^https?://#i', $href)) {
                    $host = parse_url($href, PHP_URL_HOST);
                    if ($host !== null && strcasecmp($host, $site_domain) === 0) {
                        $problems[] = array('level' => 'error', 'message' =>
                            "$file: internal link uses an absolute URL (\"$href\") — internal links must be relative");
                    } else {
                        $external_urls[$href] = true;
                    }
                    continue;
                }

                if ($href[0] === '/') {
                    $problems[] = array('level' => 'error', 'message' =>
                        "$file: root-relative link (\"$href\") — internal links must be relative, e.g. \"..$href\"");
                    continue;
                }

                // relative link: resolve and check it exists
                $target = realpath($page_dir . '/' . $href);
                if ($target === false || !is_file($target)) {
                    $problems[] = array('level' => 'error', 'message' =>
                        "$file: link to \"$href\" does not resolve to a generated file");
                    continue;
                }

                // cross-domain leak check: fine within the same domain, fine
                // back to the root hub (working/index.html), never fine into
                // a *different* domain's directory.
                if ($page_domain !== null) {
                    foreach ($domain_slugs as $slug) {
                        if ($slug === $page_domain) continue;
                        if (strpos($target, "$writeprefix/$slug/") === 0) {
                            $problems[] = array('level' => 'error', 'message' =>
                                "$file: links into domain \"$slug\" from domain \"$page_domain\" (\"$href\") — domains must not cross-link");
                        }
                    }
                }
            }
        }

        // images
        if (preg_match_all('/src="([^"]+)"/', $html, $m)) {
            foreach ($m[1] as $src) {
                if (preg_match('#^https?://#i', $src)) continue;
                $target = realpath($page_dir . '/' . $src);
                if ($target === false || !is_file($target)) {
                    $problems[] = array('level' => 'error', 'message' =>
                        "$file: image \"$src\" does not resolve to a generated file");
                }
            }
        }
    }

    $problems = array_merge($problems, check_external_links(array_keys($external_urls)));

    return $problems;
}

// External link liveness, cached for a week per URL so routine local
// generation doesn't hammer third-party sites on every run (restructure
// plan §9). Dead external links are warnings, not errors.
function check_external_links($urls) {
    $problems = array();
    if (empty($urls)) return $problems;

    $cache_file = __DIR__ . '/../var/link-cache.json';
    $cache = file_exists($cache_file) ? json_decode(file_get_contents($cache_file), true) : array();
    if (!is_array($cache)) $cache = array();

    $now = time();
    $week = 7 * 24 * 60 * 60;

    foreach ($urls as $url) {
        $entry = isset($cache[$url]) ? $cache[$url] : null;
        if ($entry !== null && ($now - $entry['checked_at']) < $week) {
            $ok = $entry['ok'];
        } else {
            $ok = url_is_reachable($url);
            $cache[$url] = array('checked_at' => $now, 'ok' => $ok);
        }
        if (!$ok) {
            $problems[] = array('level' => 'warning', 'message' => "external link appears dead: $url");
        }
    }

    @mkdir(dirname($cache_file), 0755, true);
    file_put_contents($cache_file, json_encode($cache, JSON_PRETTY_PRINT));

    return $problems;
}

function url_is_reachable($url) {
    if (!function_exists('curl_init')) return true; // don't fail the build over a missing extension
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'yomonsni-link-check/1.0',
    ));
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_errno($ch);
    curl_close($ch);
    return $err === 0 && $code > 0 && $code < 400;
}

// Regression guard for "the sitemap.xml decision" (plan §7/§10): a coach
// profile URL must never appear in sitemap.xml, since the same coach_id
// shows up across their domain cards by design, and a sitemap listing two
// of a coach's domain profiles side by side hands over exactly the
// connection on-site browsing was built to avoid. This exists so a future
// change to gen-site.php that starts including coach pages in the sitemap
// again fails loudly instead of silently reintroducing the leak.
function validate_sitemap($writeprefix) {
    $problems = array();
    $path = rtrim($writeprefix, '/') . '/sitemap.xml';
    if (!file_exists($path)) return $problems;

    if (preg_match_all('#<loc>([^<]*)</loc>#', file_get_contents($path), $m)) {
        foreach ($m[1] as $url) {
            if (preg_match('#/coach-[^/]+\.html$#', $url)) {
                $problems[] = array('level' => 'error', 'message' =>
                    "sitemap.xml includes a coach-profile URL, which must be excluded: $url");
            }
        }
    }
    return $problems;
}

// A coach landing page (coach-<id>.html, at the site root — see
// gen-site.php's write_page() calls for content/coach-landing/) is meant
// to be handed out privately (a business card) without ever pointing a
// visitor toward any specific domain, so unlike every other page on the
// site, its only permitted outbound links are Home, Mission, and
// Coaching. This checks that promise directly against the generated
// HTML rather than trusting the template was written correctly — same
// philosophy as this file's other checks. Only <a> tags are considered
// (a <link>/<script> tag's href/src for CSS/JS is a different thing
// entirely, not a link a visitor could click through to another page).
function validate_coach_landing_pages($writeprefix) {
    $problems = array();
    $writeprefix = rtrim($writeprefix, '/');
    // Root-level pages always render their links with a "./" prefix
    // (asset_url()'s $prefix for anything at the site root), so that's
    // what actually shows up in the generated href, not a bare filename.
    $allowed = array('./index.html', './mission.html', './coaching.html');

    // glob() doesn't recurse, so this only ever matches root-level
    // coach-*.html files — a domain's own coach-<id>.html profile pages
    // live one directory down and are untouched by this check.
    foreach (glob("$writeprefix/coach-*.html") as $file) {
        $html = file_get_contents($file);
        if (preg_match_all('/<a\b[^>]*\bhref="([^"]+)"/i', $html, $m)) {
            foreach ($m[1] as $href) {
                if (!in_array($href, $allowed, true)) {
                    $problems[] = array('level' => 'error', 'message' =>
                        "$file: a coach landing page may only link to index.html, mission.html, or coaching.html (found \"$href\")");
                }
            }
        }
    }
    return $problems;
}

function print_problems($problems) {
    $errors = array_filter($problems, function ($p) { return $p['level'] === 'error'; });
    $warnings = array_filter($problems, function ($p) { return $p['level'] === 'warning'; });

    foreach ($warnings as $p) fwrite(STDERR, "WARNING: {$p['message']}\n");
    foreach ($errors as $p) fwrite(STDERR, "ERROR: {$p['message']}\n");

    fwrite(STDERR, sprintf("\nValidation: %d error(s), %d warning(s)\n", count($errors), count($warnings)));

    return count($errors);
}
