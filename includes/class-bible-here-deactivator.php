<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/xjlin0
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 * @author     Jack Lin <xjlin0@gmail.com>
 */
class Bible_Here_Deactivator {

	/**
	 * Plugin deactivation handler.
	 *
	 * Cleans up temporary data and caches when plugin is deactivated.
	 * Note: Database tables are preserved for data integrity.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		self::clear_caches();
		self::clear_scheduled_events();
		self::clear_transients();
		self::flush_rewrite_rules();
	}

	/**
	 * Clear all plugin-related caches.
	 *
	 * @since    1.0.0
	 */
	private static function clear_caches() {
		// Clear WordPress object cache for our plugin
		wp_cache_flush();

		// Clear any plugin-specific cache directories
		self::clear_cache_directory();
	}

	/**
	 * Clear plugin cache directory.
	 *
	 * @since    1.0.0
	 */
	private static function clear_cache_directory() {
		$cache_dir = WP_CONTENT_DIR . '/cache/bible-here/';
		
		if (is_dir($cache_dir)) {
			self::delete_directory_contents($cache_dir);
		}
	}

	/**
	 * Recursively delete directory contents.
	 *
	 * @since    1.0.0
	 * @param    string    $dir    Directory path
	 */
	private static function delete_directory_contents($dir) {
		if (!is_dir($dir)) {
			return;
		}

		$files = array_diff(scandir($dir), array('.', '..'));
		
		foreach ($files as $file) {
			$file_path = $dir . DIRECTORY_SEPARATOR . $file;
			
			if (is_dir($file_path)) {
				self::delete_directory_contents($file_path);
				rmdir($file_path);
			} else {
				unlink($file_path);
			}
		}
	}

	/**
	 * Clear scheduled WordPress events.
	 *
	 * @since    1.0.0
	 */
	private static function clear_scheduled_events() {
		// Clear any scheduled cron jobs for the plugin
		wp_clear_scheduled_hook('bible_here_daily_cleanup');
		wp_clear_scheduled_hook('bible_here_update_search_index');
		wp_clear_scheduled_hook('bible_here_check_version_updates');
	}

	/**
	 * Clear plugin-related transients.
	 *
	 * @since    1.0.0
	 */
	private static function clear_transients() {
		global $wpdb;

		// Delete all transients that start with 'bible_here_'
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_bible_here_%',
				'_transient_timeout_bible_here_%'
			)
		);

		// Clear specific transients
		delete_transient('bible_here_versions_list');
		delete_transient('bible_here_books_cache');
		delete_transient('bible_here_search_results');
		delete_transient('bible_here_popular_verses');
	}

	/**
	 * Flush rewrite rules.
	 *
	 * @since    1.0.0
	 */
	private static function flush_rewrite_rules() {
		// Flush rewrite rules to remove any custom endpoints
		flush_rewrite_rules();
	}

	/**
	 * Clean up user meta data (optional, for complete cleanup).
	 *
	 * @since    1.0.0
	 */
	private static function clear_user_meta() {
		global $wpdb;

		// Remove user preferences for the plugin
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
				'bible_here_%'
			)
		);
	}

}
