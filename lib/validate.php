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

function validate_content_references($domain_slugs, $coaches, $testimonials, $whispers) {
    $problems = array();

    $coach_keys = array(); // "domain|coach_id" => true
    foreach ($coaches as $c) {
        $coach_keys[field($c, 'domain') . '|' . field($c, 'coach_id')] = true;
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

function print_problems($problems) {
    $errors = array_filter($problems, function ($p) { return $p['level'] === 'error'; });
    $warnings = array_filter($problems, function ($p) { return $p['level'] === 'warning'; });

    foreach ($warnings as $p) fwrite(STDERR, "WARNING: {$p['message']}\n");
    foreach ($errors as $p) fwrite(STDERR, "ERROR: {$p['message']}\n");

    fwrite(STDERR, sprintf("\nValidation: %d error(s), %d warning(s)\n", count($errors), count($warnings)));

    return count($errors);
}
