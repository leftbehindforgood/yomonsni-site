<?php

// pushes the generated yomonsni site to production.
//
// Refuses to sync unless the most recent `php gen-site.php` run recorded a
// clean validation pass (restructure plan §9) — this is the "final gate"
// so a moment of forgetting to check /tmp/foo before pushing doesn't ship
// a broken or cross-domain-leaking page.
//
// The site sits behind CloudFront (added for HTTPS, since S3's own website
// endpoint is HTTP-only) with no Cache-Control sent by the sync below, so
// it falls back to the distribution's default TTL (24h, CachingOptimized)
// — without an invalidation, a page just pushed to S3 can keep serving
// stale to visitors for up to a day.

$cloudfront_distribution_id = 'E2EATBQF9HJ2AR';

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
if ($ret !== 0) {
    exit($ret);
}

echo "sync done, invalidating CloudFront cache ({$cloudfront_distribution_id})\n";
system("aws cloudfront create-invalidation --distribution-id " . escapeshellarg($cloudfront_distribution_id) . " --paths '/*'", $invalidate_ret);
if ($invalidate_ret !== 0) {
    fwrite(STDERR, "Warning: site was pushed to S3 successfully, but the CloudFront invalidation request failed — visitors may see stale pages for up to 24h until the cache expires on its own. Check your AWS credentials/permissions and re-run the invalidation by hand if needed:\n  aws cloudfront create-invalidation --distribution-id {$cloudfront_distribution_id} --paths '/*'\n");
}

exit(0);
