<?php
defined('ABSPATH') || exit;

class GPF_Feed_Admin {

    public static function init(): void {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_init', [self::class, 'handle_actions']);
        add_action('admin_head', [self::class, 'inline_styles']);
    }

    // ── Menu ──────────────────────────────────────────────────────────────

    public static function add_menu(): void {
        add_submenu_page(
            'woocommerce',
            'Google Product Feed',
            'Google Feed',
            'manage_woocommerce',
            'google-product-feed',
            [self::class, 'render_page']
        );
    }

    // ── Handle form actions ───────────────────────────────────────────────

    public static function handle_actions(): void {
        if (!isset($_POST['gpf_action']) || !check_admin_referer('gpf_feed_nonce')) {
            return;
        }

        $redirect = admin_url('admin.php?page=google-product-feed');

        if ($_POST['gpf_action'] === 'generate') {
            $success = GPF_Feed_Generator::generate();
            wp_redirect($redirect . '&status=' . ($success ? 'generated' : 'error'));
            exit;
        }

        if ($_POST['gpf_action'] === 'clear_logs') {
            GPF_Feed_Generator::clear_logs();
            wp_redirect($redirect . '&status=logs_cleared');
            exit;
        }
    }

    // ── Minimal inline styles ─────────────────────────────────────────────

    public static function inline_styles(): void {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'woocommerce_page_google-product-feed') return;
        ?>
        <style>
            .gpf-log-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            .gpf-log-table th,
            .gpf-log-table td { padding: 8px 12px; border: 1px solid #ddd; font-size: 13px; }
            .gpf-log-table th { background: #f5f5f5; text-align: left; }
            .gpf-level-success { color: #0a6b0a; font-weight: 600; }
            .gpf-level-error   { color: #cc0000; font-weight: 600; }
            .gpf-level-warning { color: #996600; font-weight: 600; }
            .gpf-level-info    { color: #333; }
        </style>
        <?php
    }

    // ── Admin page ────────────────────────────────────────────────────────

    public static function render_page(): void {
        $last_generated = get_option('gpf_feed_last_generated', null);
        $next_scheduled = wp_next_scheduled('gpf_generate_feed');
        $feed_url       = GPF_Feed_Generator::get_feed_url();
        $logs           = GPF_Feed_Generator::get_logs();
        $status         = $_GET['status'] ?? null;
        ?>
        <div class="wrap">
            <h1>Google Product Feed</h1>

            <?php if ($status === 'generated'): ?>
                <div class="notice notice-success is-dismissible"><p>✅ Feed generated successfully.</p></div>
            <?php elseif ($status === 'error'): ?>
                <div class="notice notice-error is-dismissible"><p>❌ Feed generation failed. Check the log below for details.</p></div>
            <?php elseif ($status === 'logs_cleared'): ?>
                <div class="notice notice-info is-dismissible"><p>Log cleared.</p></div>
            <?php endif; ?>

            <h2>Feed Status</h2>
            <table class="form-table">
                <tr>
                    <th>Feed URL</th>
                    <td>
                        <a href="<?= esc_url($feed_url) ?>" target="_blank">
                            <?= esc_html($feed_url) ?>
                        </a>
                        <p class="description">Give this URL to Google Merchant Center.</p>
                    </td>
                </tr>
                <tr>
                    <th>Last Generated</th>
                    <td><?= $last_generated ? esc_html($last_generated) : '<em>Never</em>' ?></td>
                </tr>
                <tr>
                    <th>Next Scheduled Run</th>
                    <td>
                        <?php if ($next_scheduled): ?>
                            <?= esc_html(get_date_from_gmt(date('Y-m-d H:i:s', $next_scheduled))) ?>
                        <?php else: ?>
                            <span style="color:red;">⚠️ Not scheduled — try deactivating and reactivating the plugin.</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <h2>Manual Generation</h2>
            <p>Trigger a regeneration immediately without waiting for the scheduled run.</p>
            <form method="post">
                <?php wp_nonce_field('gpf_feed_nonce'); ?>
                <input type="hidden" name="gpf_action" value="generate">
                <?php submit_button('Generate Feed Now', 'primary'); ?>
            </form>

            <h2>Activity Log</h2>
            <?php if (empty($logs)): ?>
                <p>No activity yet. Generate the feed to see logs here.</p>
            <?php else: ?>
                <table class="gpf-log-table widefat">
                    <thead>
                        <tr>
                            <th style="width:180px;">Time</th>
                            <th style="width:80px;">Level</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $entry): ?>
                            <tr>
                                <td><?= esc_html($entry['time']) ?></td>
                                <td>
                                    <span class="gpf-level-<?= esc_attr($entry['level']) ?>">
                                        <?= esc_html(strtoupper($entry['level'])) ?>
                                    </span>
                                </td>
                                <td><?= esc_html($entry['message']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <form method="post" style="margin-top: 10px;">
                    <?php wp_nonce_field('gpf_feed_nonce'); ?>
                    <input type="hidden" name="gpf_action" value="clear_logs">
                    <?php submit_button('Clear Log', 'secondary small'); ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
}
