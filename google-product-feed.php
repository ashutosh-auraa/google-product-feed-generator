<?php
/**
 * Plugin Name: Google Product Feed
 * Description: Automatically generates a Google Shopping product feed every 2 days.
 * Version: 1.1.0
 * Author: Auraa Design
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 */

defined('ABSPATH') || exit;

// ── PHP Version Check ─────────────────────────────────────────────────────

if (version_compare(PHP_VERSION, '8.0', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Google Product Feed:</strong> This plugin requires PHP 8.0 or higher. ';
        echo 'Your server is running PHP ' . PHP_VERSION . '.';
        echo '</p></div>';
    });
    return;
}

// ── WooCommerce Check ─────────────────────────────────────────────────────

if (!function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if (!is_plugin_active('woocommerce/woocommerce.php')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Google Product Feed:</strong> WooCommerce must be installed and active.';
        echo '</p></div>';
    });
    return;
}

// ── Constants ─────────────────────────────────────────────────────────────

define('GPF_FEED_DIR',     plugin_dir_path(__FILE__));
define('GPF_FEED_VERSION', '1.1.0');

// ── Includes ──────────────────────────────────────────────────────────────

require_once GPF_FEED_DIR . 'includes/class-feed-generator.php';
require_once GPF_FEED_DIR . 'includes/class-feed-scheduler.php';
require_once GPF_FEED_DIR . 'admin/class-feed-admin.php';

// ── Activation / Deactivation ─────────────────────────────────────────────

register_activation_hook(__FILE__, function () {
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Google Product Feed requires PHP 8.0 or higher.');
    }
    GPF_Feed_Scheduler::schedule();
});

register_deactivation_hook(__FILE__, function () {
    GPF_Feed_Scheduler::unschedule();
});

// ── Boot ──────────────────────────────────────────────────────────────────

add_action('plugins_loaded', function () {
    GPF_Feed_Scheduler::init();
    GPF_Feed_Admin::init();
});