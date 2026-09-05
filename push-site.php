<?php

// pushes the generated yomonsni site to production.
//
// Refuses to sync unless the most recent `php gen-site.php` run recorded a
// clean validation pass (restructure plan §9) — this is the "final gate"
// so a moment of forgetting to check /tmp/foo before pushing doesn't ship
// a broken or cross-domain-leaking page.

$status_file = __DIR__ . '/var/last-generation.json';

if (!file_exists($status_file)) {
    fwrite(STDERR, "Refusing to push: no record of a gen-site.php run. Run `php gen-site.php` first.\n");
    exit(1);
}

$status = json_decode(file_get_contents($status_file), true);
if (!is_array($status) || !isset($status['errors'])) {
    fwrite(STDERR, "Refusing to push: {$status_file} is unreadable. Run `php gen-site.php` again.\n");
    exit(1);
}

if ($status['errors'] > 0) {
    fwrite(STDERR, "Refusing to push: last generation had {$status['errors']} validation error(s). Fix and re-run `php gen-site.php`.\n");
    exit(1);
}

$age_minutes = round((time() - $status['generated_at']) / 60);
echo "howdy, pushing yomonsni (last generated {$age_minutes} minute(s) ago, validation clean)\n";

system("aws s3 sync working/ s3://yomonsni.com --acl public-read", $ret);
exit($ret);
