<?php
defined('ABSPATH') || exit;

class GPF_Feed_Scheduler {

    private const HOOK     = 'gpf_generate_feed';
    private const INTERVAL = 'every_two_days';

    // ── Register interval and hook ────────────────────────────────────────

    public static function init(): void {
        add_filter('cron_schedules', [self::class, 'add_interval']);
        add_action(self::HOOK, [self::class, 'run']);
    }

    public static function add_interval(array $schedules): array {
        $schedules[self::INTERVAL] = [
            'interval' => 2 * DAY_IN_SECONDS,
            'display'  => 'Every Two Days',
        ];
        return $schedules;
    }

    // ── Schedule on activation ────────────────────────────────────────────

    public static function schedule(): void {
        if (!wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time(), self::INTERVAL, self::HOOK);
        }
    }

    // ── Unschedule on deactivation ────────────────────────────────────────

    public static function unschedule(): void {
        $timestamp = wp_next_scheduled(self::HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::HOOK);
        }
    }

    // ── What runs on each cron tick ───────────────────────────────────────

    public static function run(): void {
        GPF_Feed_Generator::generate();
    }
}
