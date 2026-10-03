<?php
/** Staging-only age fixture: changes timestamps of one private test entry. */
if (parse_url(home_url(), PHP_URL_HOST) !== 'wordpress-218158-6702910.cloudwaysapps.com') {
    throw new RuntimeException('Staging only');
}
$key = $args[0] ?? '';
if (!preg_match('/^iu_frag_[a-f0-9]{64}$/', $key)) throw new RuntimeException('Invalid key');
$entry = get_transient($key);
if (!is_array($entry) || empty($entry['html']) || !isset($entry['fresh_until'], $entry['stale_until'])) {
    throw new RuntimeException('Missing tested entry');
}
$entry['fresh_until'] = time() - 1;
$entry['stale_until'] = time() + 180;
if (!set_transient($key, $entry, 180)) throw new RuntimeException('Could not age fixture');
echo "Aged one test entry; HTML and key preserved.\n";
