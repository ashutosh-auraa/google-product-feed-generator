<?php
// Only runs when plugin is deleted from WordPress admin
defined('WP_UNINSTALL_PLUGIN') || exit;

// Remove all plugin options from the database
delete_option('gpf_feed_last_generated');
delete_option('gpf_feed_log');

// Remove the scheduled cron event
$timestamp = wp_next_scheduled('gpf_generate_feed');
if ($timestamp) {
    wp_unschedule_event($timestamp, 'gpf_generate_feed');
}

// Remove the generated feed file
$upload   = wp_upload_dir();
$feed_dir = trailingslashit($upload['basedir']) . 'feeds';
$feed_file = trailingslashit($feed_dir) . 'google-products.xml';

if (file_exists($feed_file)) {
    @unlink($feed_file);
}