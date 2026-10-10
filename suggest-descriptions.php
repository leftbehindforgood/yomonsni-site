<?php

// Companion to gen-site.php's description warnings (check_descriptions()
// in lib/validate.php). A page's `description` front-matter field is the
// blurb a search engine shows under its title; this finds the entries
// that have none (and so run on meta_description()'s automatic fallback)
// or whose body has changed a lot since theirs was written.
//
//   php suggest-descriptions.php            list them, change nothing
//   php suggest-descriptions.php --write    draft a description for each
//                                           and write it into the .entry
//                                           file's front matter
//   php suggest-descriptions.php --write content/field-notes/foo.entry ...
//                                           same, only for the files named
//   php suggest-descriptions.php --accept   the stale ones are still fine
//                                           as written: stop warning
//
// --write drafts each one by running the `claude` command-line tool
// (Claude Code, already signed in on this machine — no API key lives in
// this repo) on that entry's own text. Nothing is committed or
// published: review the result with `git diff`, edit or revert anything
// that reads wrong, then run `php gen-site.php` as usual. That last part
// is not optional for coach cards and coach landing pages — the same
// "read back what you wrote" privacy rule applies to a description as to
// the bio it was drafted from (see CONTENT-GUIDE.md).

require_once __DIR__ . '/lib/Parsedown.php';
require_once __DIR__ . '/lib/entry.php';
require_once __DIR__ . '/lib/validate.php';

chdir(__DIR__);
$state_file = __DIR__ . '/description-baselines.json';
$args   = array_slice($argv, 1);
$write  = in_array('--write', $args, true);
$accept = in_array('--accept', $args, true);
$only   = array_values(array_filter($args, function ($a) { return strpos($a, '--') !== 0; }));

$entries = description_entries('content/');
$status  = description_status($entries, $state_file, $accept);
if ($accept) {
    echo "Accepted every current description as matching its page.\n";
    exit(0);
}

$todo = array();
foreach ($status['stale'] as $path => $pct) $todo[$path] = "stale (about $pct% of the body changed)";
foreach ($status['missing'] as $path) $todo[$path] = 'no description';
if (!empty($only)) $todo = array_intersect_key($todo, array_flip($only));

if (empty($todo)) {
    echo "Nothing to do: every page has a description that matches its content.\n";
    exit(0);
}

if (!$write) {
    foreach ($todo as $path => $why) echo "$path — $why\n";
    echo "\n" . count($todo) . " to review. Draft them with: php suggest-descriptions.php --write\n";
    exit(0);
}

// One or two plain sentences from the entry's own text, via `claude -p`.
function draft_description($entry) {
    $prompt = "Write the meta description (the blurb under the title in a search result) for the web page below, "
        . "from a coaching practice's site.\n"
        . "- One or two plain sentences, 110 to 155 characters in total.\n"
        . "- Say what the page is and who it is for, in the page's own voice. No hype, no call to action.\n"
        . "- Use only what the page itself says. Add no subject, service, or area of coaching it doesn't name.\n"
        . "- Reply with the description alone: one line, no quotation marks, no preamble.\n\n"
        . "PAGE TITLE: " . field($entry, 'title', field($entry, 'name')) . "\n\nPAGE TEXT:\n"
        . mb_substr(plain_text(entry_html($entry)), 0, 6000) . "\n";
    $proc = proc_open(array('claude', '-p'), array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
    if (!is_resource($proc)) return '';
    fwrite($pipes[0], $prompt);
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($proc) !== 0) return '';
    $text = trim(preg_replace('/\s+/u', ' ', $out), " \"'“”");
    return truncate_words($text, 160);
}

// Set (or replace) the description line inside the +++ front matter,
// leaving every other byte of the file alone.
function set_description($path, $description) {
    $lines = explode("\n", file_get_contents($path));
    $close = null;
    for ($i = 1; $i < count($lines); $i++) {
        if (trim($lines[$i]) === '+++') { $close = $i; break; }
        if (preg_match('/^description\s*:/i', $lines[$i])) { $lines[$i] = "description: $description"; $close = -1; break; }
    }
    if ($close === null || trim($lines[0]) !== '+++') return false;
    if ($close > 0) array_splice($lines, $close, 0, array("description: $description"));
    file_put_contents($path, implode("\n", $lines));
    return true;
}

$by_path = array();
foreach ($entries as $e) $by_path[$e['path']] = $e;
$done = 0;
foreach ($todo as $path => $why) {
    echo "$path ... ";
    $description = draft_description($by_path[$path]);
    if ($description === '' || !set_description($path, $description)) {
        echo "FAILED (left unchanged)\n";
        continue;
    }
    echo "\n  $description\n";
    $done++;
}
echo "\nWrote $done of " . count($todo) . ". Review with `git diff content/`, then run `php gen-site.php`.\n";
