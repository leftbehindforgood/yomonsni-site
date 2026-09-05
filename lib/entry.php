<?php
// Content-entry parsing and collection loading for the coaching-site engine.
// See docs/coaching-site-restructure-plan.md §4 for the file format this
// implements: a "+++"-fenced front-matter block (never "---", since body
// content is Markdown and "---" collides with Markdown's own horizontal-rule
// syntax), followed by a blank line, followed by a Markdown body.

// Parse one *.entry file into ['path','slug','data','body_md'].
// $data is an associative array of the front-matter fields, lowercased keys.
function parse_entry_file($path) {
    if (!file_exists($path)) {
        fwrite(STDERR, "ERROR: missing content file: $path\n");
        return array('path' => $path, 'slug' => basename($path), 'data' => array(), 'body_md' => '', 'missing' => true);
    }

    $lines = explode("\n", str_replace("\r\n", "\n", file_get_contents($path)));
    $data = array();
    $body = '';

    if (isset($lines[0]) && trim($lines[0]) === '+++') {
        $close_at = null;
        for ($i = 1; $i < count($lines); $i++) {
            if (trim($lines[$i]) === '+++') { $close_at = $i; break; }
        }
        if ($close_at === null) {
            fwrite(STDERR, "ERROR: unclosed +++ front-matter block in $path\n");
        } else {
            // Front matter is every line strictly between the two fences —
            // may legitimately be empty (e.g. footer.entry has no fields).
            foreach (array_slice($lines, 1, $close_at - 1) as $line) {
                $line = rtrim($line);
                if ($line === '') continue;
                if (strpos($line, ':') === false) {
                    fwrite(STDERR, "WARNING: malformed front-matter line in $path: \"$line\"\n");
                    continue;
                }
                list($key, $val) = explode(':', $line, 2);
                $data[strtolower(trim($key))] = trim($val);
            }
            $body = ltrim(implode("\n", array_slice($lines, $close_at + 1)), "\n");
        }
    } else {
        fwrite(STDERR, "WARNING: no +++ front-matter block found in $path (treating whole file as body)\n");
        $body = implode("\n", $lines);
    }

    return array(
        'path'    => $path,
        'slug'    => preg_replace('/\.entry$/', '', basename($path)),
        'data'    => $data,
        'body_md' => $body,
    );
}

// Render an entry's Markdown body to HTML. Memoized per-entry via a static
// cache keyed on path, since the same entry sometimes gets rendered more
// than once (e.g. a whisper appearing on several domains' pages).
function entry_html($entry) {
    static $md = null;
    static $cache = array();
    if ($md === null) $md = new Parsedown();
    $key = $entry['path'];
    if (!isset($cache[$key])) {
        $cache[$key] = $md->text($entry['body_md']);
    }
    return $cache[$key];
}

// Front-matter values are plain strings; a handful of fields (like a
// whisper's "domains") are comma-separated lists. This splits and trims one.
// $delimiter defaults to a comma (used by e.g. a whisper's "domains").
// Pass '|' for a list whose individual items might contain commas of
// their own (e.g. resources.entry's "pointers": each one is a full
// sentence, which commas show up in constantly).
function entry_list($value, $delimiter = ',') {
    if ($value === null || $value === '') return array();
    return array_values(array_filter(array_map('trim', explode($delimiter, $value)), function ($v) {
        return $v !== '';
    }));
}

function field($entry, $key, $default = '') {
    return isset($entry['data'][$key]) ? $entry['data'][$key] : $default;
}

// For a yes/no front-matter field (e.g. a coach's "fully_booked") —
// "yes"/"true"/"1" (any case) are true, anything else (including unset)
// is false.
function field_bool($entry, $key) {
    return in_array(strtolower(trim(field($entry, $key))), array('yes', 'true', '1'), true);
}

// Load every *.entry file directly inside $dir (collections are flat, not
// nested — see the restructure plan §4.2).
function load_collection($dir) {
    $out = array();
    foreach (glob(rtrim($dir, '/') . '/*.entry') as $file) {
        $out[] = parse_entry_file($file);
    }
    return $out;
}

// $field is 'domain' for a single-domain record (coach, testimonial,
// workshop, retreat) or 'domains' for a whisper's comma-separated list.
function filter_by_domain($entries, $domain_slug, $field = 'domain') {
    $out = array();
    foreach ($entries as $e) {
        $val = field($e, $field);
        if ($field === 'domains') {
            if (in_array($domain_slug, entry_list($val), true)) $out[] = $e;
        } else {
            if (trim($val) === $domain_slug) $out[] = $e;
        }
    }
    return $out;
}

// Testimonials for one coach within one domain (used on a coach profile
// page) — always exactly one domain and one coach per testimonial, see
// the restructure plan §4.2.
function entries_for_coach($entries, $coach_id, $domain_slug) {
    $out = array();
    foreach ($entries as $e) {
        if (trim(field($e, 'domain')) === $domain_slug && trim(field($e, 'coach')) === $coach_id) {
            $out[] = $e;
        }
    }
    return $out;
}

// Resolve a coach_id to a display name. A coach's name is the same across
// every domain they work in (restructure plan §3), so prefer their card in
// $domain_slug but fall back to any of their cards if they don't have one
// there (e.g. a whisper credited to a coach in a domain they don't formally
// coach in), and finally the bare id if no card matches at all.
function resolve_coach_name($coaches, $coach_id, $domain_slug) {
    $any = null;
    foreach ($coaches as $c) {
        if (field($c, 'coach_id') !== $coach_id) continue;
        if (field($c, 'domain') === $domain_slug) return field($c, 'name');
        if ($any === null) $any = field($c, 'name');
    }
    return $any !== null ? $any : $coach_id;
}

function sort_by_date_desc($entries) {
    usort($entries, function ($a, $b) {
        return strcmp(field($b, 'date'), field($a, 'date'));
    });
    return $entries;
}

// Sorts by an explicit numeric "order" front-matter field (lower first).
// Without it, collections just come back in glob()'s filesystem order,
// which happens to be alphabetical by filename — fine by accident, but
// not something to rely on for "which coach appears first." Entries with
// no order field (or a non-numeric one) sort after every ordered entry,
// and ties fall back to name so the result is stable and predictable
// rather than depending on file order.
function sort_by_order($entries, $field = 'order') {
    usort($entries, function ($a, $b) use ($field) {
        $oa = field($a, $field);
        $ob = field($b, $field);
        $oa = ($oa === '' || !is_numeric($oa)) ? PHP_INT_MAX : (float) $oa;
        $ob = ($ob === '' || !is_numeric($ob)) ? PHP_INT_MAX : (float) $ob;
        if ($oa !== $ob) return ($oa < $ob) ? -1 : 1;
        return strcmp(field($a, 'name'), field($b, 'name'));
    });
    return $entries;
}
