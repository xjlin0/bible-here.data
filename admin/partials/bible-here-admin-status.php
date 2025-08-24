<?php

/**
 * Provide a admin area view for system status and maintenance
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/xjlin0
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/admin/partials
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle maintenance actions
if ( isset( $_POST['action'] ) && wp_verify_nonce( $_POST['bible_here_nonce'], 'bible_here_maintenance' ) ) {
	switch ( $_POST['action'] ) {
		case 'clear_cache':
			$cache_cleared = bible_here_clear_search_cache();
			if ( $cache_cleared ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Search cache cleared successfully.', 'bible-here' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Failed to clear search cache.', 'bible-here' ) . '</p></div>';
			}
			break;
			
		case 'clear_history':
			$history_cleared = bible_here_clear_search_history();
			if ( $history_cleared ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Search history cleared successfully.', 'bible-here' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Failed to clear search history.', 'bible-here' ) . '</p></div>';
			}
			break;
			
		case 'optimize_database':
			$optimized = bible_here_optimize_database();
			if ( $optimized ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Database optimized successfully.', 'bible-here' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Failed to optimize database.', 'bible-here' ) . '</p></div>';
			}
			break;
			
		case 'clear_logs':
			$logs_cleared = bible_here_clear_error_logs();
			if ( $logs_cleared ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Error logs cleared successfully.', 'bible-here' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Failed to clear error logs.', 'bible-here' ) . '</p></div>';
			}
			break;
			
		case 'reindex_search':
			$reindexed = bible_here_reindex_search();
			if ( $reindexed ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Search index rebuilt successfully.', 'bible-here' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Failed to rebuild search index.', 'bible-here' ) . '</p></div>';
			}
			break;
	}
}

// Get system information
$system_info = bible_here_get_system_info();
$database_info = bible_here_get_database_info();
$cache_info = bible_here_get_cache_info();
$error_logs = bible_here_get_recent_error_logs();

?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<div class="bible-here-status-admin">
		<!-- System Information -->
		<div class="postbox">
			<h2 class="hndle"><span><?php _e( 'System Information', 'bible-here' ); ?></span></h2>
			<div class="inside">
				<table class="widefat striped">
					<tbody>
						<tr>
							<td><strong><?php _e( 'Plugin Version', 'bible-here' ); ?></strong></td>
							<td><?php echo esc_html( $system_info['plugin_version'] ); ?></td>
							<td>
								<?php if ( $system_info['plugin_version'] === $system_info['latest_version'] ) : ?>
									<span class="status-good"><span class="dashicons dashicons-yes"></span> <?php _e( 'Up to date', 'bible-here' ); ?></span>
								<?php else : ?>
									<span class="status-warning"><span class="dashicons dashicons-warning"></span> <?php _e( 'Update available', 'bible-here' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong><?php _e( 'WordPress Version', 'bible-here' ); ?></strong></td>
							<td><?php echo esc_html( $system_info['wp_version'] ); ?></td>
							<td>
								<?php if ( version_compare( $system_info['wp_version'], '5.0', '>=' ) ) : ?>
									<span class="status-good"><span class="dashicons dashicons-yes"></span> <?php _e( 'Compatible', 'bible-here' ); ?></span>
								<?php else : ?>
									<span class="status-error"><span class="dashicons dashicons-no"></span> <?php _e( 'Incompatible', 'bible-here' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong><?php _e( 'PHP Version', 'bible-here' ); ?></strong></td>
							<td><?php echo esc_html( $system_info['php_version'] ); ?></td>
							<td>
								<?php if ( version_compare( $system_info['php_version'], '7.4', '>=' ) ) : ?>
									<span class="status-good"><span class="dashicons dashicons-yes"></span> <?php _e( 'Compatible', 'bible-here' ); ?></span>
								<?php else : ?>
									<span class="status-warning"><span class="dashicons dashicons-warning"></span> <?php _e( 'Upgrade recommended', 'bible-here' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong><?php _e( 'MySQL Version', 'bible-here' ); ?></strong></td>
							<td><?php echo esc_html( $system_info['mysql_version'] ); ?></td>
							<td>
								<?php if ( version_compare( $system_info['mysql_version'], '5.6', '>=' ) ) : ?>
									<span class="status-good"><span class="dashicons dashicons-yes"></span> <?php _e( 'Compatible', 'bible-here' ); ?></span>
								<?php else : ?>
									<span class="status-warning"><span class="dashicons dashicons-warning"></span> <?php _e( 'Upgrade recommended', 'bible-here' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong><?php _e( 'Memory Limit', 'bible-here' ); ?></strong></td>
							<td><?php echo esc_html( $system_info['memory_limit'] ); ?></td>
							<td>
								<?php if ( $system_info['memory_limit_bytes'] >= 134217728 ) : // 128MB ?>
									<span class="status-good"><span class="dashicons dashicons-yes"></span> <?php _e( 'Sufficient', 'bible-here' ); ?></span>
								<?php else : ?>
									<span class="status-warning"><span class="dashicons dashicons-warning"></span> <?php _e( 'Low', 'bible-here' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong><?php _e( 'Max Execution Time', 'bible-here' ); ?></strong></td>
							<td><?php echo esc_html( $system_info['max_execution_time'] . 's' ); ?></td>
							<td>
								<?php if ( $system_info['max_execution_time'] >= 30 ) : ?>
									<span class="status-good"><span class="dashicons dashicons-yes"></span> <?php _e( 'Sufficient', 'bible-here' ); ?></span>
								<?php else : ?>
									<span class="status-warning"><span class="dashicons dashicons-warning"></span> <?php _e( 'Low', 'bible-here' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Database Information -->
		<div class="postbox">
			<h2 class="hndle"><span><?php _e( 'Database Information', 'bible-here' ); ?></span></h2>
			<div class="inside">
				<table class="widefat striped">
					<tbody>
						<tr>
							<td><strong><?php _e( 'Bible Versions', 'bible-here' ); ?></strong></td>
							<td><?php echo esc_html( $database_info['total_versions'] ); ?></td>
							<td><?php echo esc_html( sprintf( __( '%d active', 'bible-here' ), $database_info['active_versions'] ) ); ?></td>
						</tr>
						<tr>
							<td><strong><?php _e( 'Total Verses', 'bible-here' ); ?></strong></td>
							<td><?php echo esc_html( number_format( $database_info['total_verses'] ) ); ?></td>
							<td><?php echo esc_html( $database_info['verses_size'] ); ?></td>
						</tr>
						<tr>
							<td><strong><?php _e( 'Search Cache Entries', 'bible-here' ); ?></strong></td>
							<td><?php echo esc_html( number_format( $database_info['cache_entries'] ) ); ?></td>
							<td><?php echo esc_html( $database_info['cache_size'] ); ?></td>
						</tr>
						<tr>
							<td><strong><?php _e( 'Search History Entries', 'bible-here' ); ?></strong></td>
							<td><?php echo esc_html( number_format( $database_info['history_entries'] ) ); ?></td>
							<td><?php echo esc_html( $database_info['history_size'] ); ?></td>
						</tr>
						<tr>
							<td><strong><?php _e( 'Database Size', 'bible-here' ); ?></strong></td>
							<td><?php echo esc_html( $database_info['total_size'] ); ?></td>
							<td>
								<?php if ( $database_info['needs_optimization'] ) : ?>
									<span class="status-warning"><span class="dashicons dashicons-warning"></span> <?php _e( 'Optimization recommended', 'bible-here' ); ?></span>
								<?php else : ?>
									<span class="status-good"><span class="dashicons dashicons-yes"></span> <?php _e( 'Optimized', 'bible-here' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Cache Information -->
		<div class="postbox">
			<h2 class="hndle"><span><?php _e( 'Cache Information', 'bible-here' ); ?></span></h2>
			<div class="inside">
				<table class="widefat striped">
					<tbody>
						<tr>
							<td><strong><?php _e( 'Search Cache Status', 'bible-here' ); ?></strong></td>
							<td>
								<?php if ( $cache_info['search_cache_enabled'] ) : ?>
									<span class="status-good"><span class="dashicons dashicons-yes"></span> <?php _e( 'Enabled', 'bible-here' ); ?></span>
								<?php else : ?>
									<span class="status-neutral"><span class="dashicons dashicons-minus"></span> <?php _e( 'Disabled', 'bible-here' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( sprintf( __( 'Expires in %d hours', 'bible-here' ), $cache_info['cache_expiry_hours'] ) ); ?></td>
						</tr>
						<tr>
							<td><strong><?php _e( 'Cache Hit Rate', 'bible-here' ); ?></strong></td>
							<td><?php echo esc_html( $cache_info['hit_rate'] . '%' ); ?></td>
							<td>
								<?php if ( $cache_info['hit_rate'] >= 70 ) : ?>
									<span class="status-good"><span class="dashicons dashicons-yes"></span> <?php _e( 'Good', 'bible-here' ); ?></span>
								<?php elseif ( $cache_info['hit_rate'] >= 40 ) : ?>
									<span class="status-warning"><span class="dashicons dashicons-warning"></span> <?php _e( 'Fair', 'bible-here' ); ?></span>
								<?php else : ?>
									<span class="status-error"><span class="dashicons dashicons-no"></span> <?php _e( 'Poor', 'bible-here' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong><?php _e( 'Expired Cache Entries', 'bible-here' ); ?></strong></td>
							<td><?php echo esc_html( number_format( $cache_info['expired_entries'] ) ); ?></td>
							<td>
								<?php if ( $cache_info['expired_entries'] > 100 ) : ?>
									<span class="status-warning"><span class="dashicons dashicons-warning"></span> <?php _e( 'Cleanup recommended', 'bible-here' ); ?></span>
								<?php else : ?>
									<span class="status-good"><span class="dashicons dashicons-yes"></span> <?php _e( 'Clean', 'bible-here' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Maintenance Tools -->
		<div class="postbox">
			<h2 class="hndle"><span><?php _e( 'Maintenance Tools', 'bible-here' ); ?></span></h2>
			<div class="inside">
				<div class="maintenance-tools-grid">
					<div class="tool-card">
						<h4><span class="dashicons dashicons-trash"></span> <?php _e( 'Clear Search Cache', 'bible-here' ); ?></h4>
						<p><?php _e( 'Remove all cached search results to free up space and force fresh searches.', 'bible-here' ); ?></p>
						<form method="post" style="display: inline;">
							<?php wp_nonce_field( 'bible_here_maintenance', 'bible_here_nonce' ); ?>
							<input type="hidden" name="action" value="clear_cache">
							<button type="submit" class="button" onclick="return confirm('<?php _e( 'Are you sure you want to clear all search cache?', 'bible-here' ); ?>')">
								<?php _e( 'Clear Cache', 'bible-here' ); ?>
							</button>
						</form>
					</div>

					<div class="tool-card">
						<h4><span class="dashicons dashicons-clock"></span> <?php _e( 'Clear Search History', 'bible-here' ); ?></h4>
						<p><?php _e( 'Remove all search history entries to protect user privacy.', 'bible-here' ); ?></p>
						<form method="post" style="display: inline;">
							<?php wp_nonce_field( 'bible_here_maintenance', 'bible_here_nonce' ); ?>
							<input type="hidden" name="action" value="clear_history">
							<button type="submit" class="button" onclick="return confirm('<?php _e( 'Are you sure you want to clear all search history?', 'bible-here' ); ?>')">
								<?php _e( 'Clear History', 'bible-here' ); ?>
							</button>
						</form>
					</div>

					<div class="tool-card">
						<h4><span class="dashicons dashicons-performance"></span> <?php _e( 'Optimize Database', 'bible-here' ); ?></h4>
						<p><?php _e( 'Optimize database tables to improve performance and reduce storage space.', 'bible-here' ); ?></p>
						<form method="post" style="display: inline;">
							<?php wp_nonce_field( 'bible_here_maintenance', 'bible_here_nonce' ); ?>
							<input type="hidden" name="action" value="optimize_database">
							<button type="submit" class="button" onclick="return confirm('<?php _e( 'This may take several minutes. Continue?', 'bible-here' ); ?>')">
								<?php _e( 'Optimize Database', 'bible-here' ); ?>
							</button>
						</form>
					</div>

					<div class="tool-card">
						<h4><span class="dashicons dashicons-search"></span> <?php _e( 'Rebuild Search Index', 'bible-here' ); ?></h4>
						<p><?php _e( 'Rebuild the search index to improve search accuracy and performance.', 'bible-here' ); ?></p>
						<form method="post" style="display: inline;">
							<?php wp_nonce_field( 'bible_here_maintenance', 'bible_here_nonce' ); ?>
							<input type="hidden" name="action" value="reindex_search">
							<button type="submit" class="button" onclick="return confirm('<?php _e( 'This may take several minutes. Continue?', 'bible-here' ); ?>')">
								<?php _e( 'Rebuild Index', 'bible-here' ); ?>
							</button>
						</form>
					</div>

					<div class="tool-card">
						<h4><span class="dashicons dashicons-warning"></span> <?php _e( 'Clear Error Logs', 'bible-here' ); ?></h4>
						<p><?php _e( 'Remove all error log entries to free up space.', 'bible-here' ); ?></p>
						<form method="post" style="display: inline;">
							<?php wp_nonce_field( 'bible_here_maintenance', 'bible_here_nonce' ); ?>
							<input type="hidden" name="action" value="clear_logs">
							<button type="submit" class="button" onclick="return confirm('<?php _e( 'Are you sure you want to clear all error logs?', 'bible-here' ); ?>')">
								<?php _e( 'Clear Logs', 'bible-here' ); ?>
							</button>
						</form>
					</div>
				</div>
			</div>
		</div>

		<!-- Recent Error Logs -->
		<?php if ( ! empty( $error_logs ) ) : ?>
		<div class="postbox">
			<h2 class="hndle"><span><?php _e( 'Recent Error Logs', 'bible-here' ); ?></span></h2>
			<div class="inside">
				<div class="error-logs-container">
					<?php foreach ( $error_logs as $log ) : ?>
						<div class="error-log-entry <?php echo esc_attr( $log['level'] ); ?>">
							<div class="log-header">
								<span class="log-level"><?php echo esc_html( strtoupper( $log['level'] ) ); ?></span>
								<span class="log-time"><?php echo esc_html( $log['timestamp'] ); ?></span>
							</div>
							<div class="log-message"><?php echo esc_html( $log['message'] ); ?></div>
							<?php if ( ! empty( $log['context'] ) ) : ?>
								<div class="log-context">
									<strong><?php _e( 'Context:', 'bible-here' ); ?></strong>
									<pre><?php echo esc_html( json_encode( $log['context'], JSON_PRETTY_PRINT ) ); ?></pre>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
				<p class="description"><?php _e( 'Showing the 20 most recent error log entries.', 'bible-here' ); ?></p>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>

<style>
.bible-here-status-admin .postbox {
	margin-bottom: 20px;
}

.bible-here-status-admin .widefat td {
	padding: 12px;
	vertical-align: middle;
}

.status-good {
	color: #46b450;
	font-weight: 600;
}

.status-warning {
	color: #ffb900;
	font-weight: 600;
}

.status-error {
	color: #dc3232;
	font-weight: 600;
}

.status-neutral {
	color: #666;
	font-weight: 600;
}

.maintenance-tools-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 20px;
	margin-top: 15px;
}

.tool-card {
	border: 1px solid #ddd;
	padding: 20px;
	border-radius: 5px;
	background: #f9f9f9;
}

.tool-card h4 {
	margin-top: 0;
	margin-bottom: 10px;
	color: #0073aa;
	display: flex;
	align-items: center;
	gap: 8px;
}

.tool-card p {
	margin-bottom: 15px;
	font-size: 13px;
	color: #666;
}

.error-logs-container {
	max-height: 400px;
	overflow-y: auto;
	border: 1px solid #ddd;
	background: #fff;
}

.error-log-entry {
	padding: 15px;
	border-bottom: 1px solid #eee;
}

.error-log-entry:last-child {
	border-bottom: none;
}

.error-log-entry.error {
	border-left: 4px solid #dc3232;
}

.error-log-entry.warning {
	border-left: 4px solid #ffb900;
}

.error-log-entry.info {
	border-left: 4px solid #0073aa;
}

.log-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 8px;
}

.log-level {
	font-weight: 600;
	font-size: 12px;
	padding: 2px 8px;
	border-radius: 3px;
	background: #f0f0f0;
}

.log-time {
	font-size: 12px;
	color: #666;
}

.log-message {
	font-family: monospace;
	font-size: 13px;
	line-height: 1.4;
	margin-bottom: 8px;
}

.log-context {
	margin-top: 10px;
	padding: 10px;
	background: #f5f5f5;
	border-radius: 3px;
}

.log-context pre {
	font-size: 11px;
	margin: 5px 0 0 0;
	white-space: pre-wrap;
	word-wrap: break-word;
}

@media (max-width: 782px) {
	.maintenance-tools-grid {
		grid-template-columns: 1fr;
	}
	
	.log-header {
		flex-direction: column;
		align-items: flex-start;
		gap: 5px;
	}
}
</style>

<?php

/**
 * Get system information
 */
function bible_here_get_system_info() {
	global $wp_version;
	
	$memory_limit = ini_get( 'memory_limit' );
	$memory_limit_bytes = wp_convert_hr_to_bytes( $memory_limit );
	
	return array(
		'plugin_version' => BIBLE_HERE_VERSION,
		'latest_version' => BIBLE_HERE_VERSION, // TODO: Check for updates
		'wp_version' => $wp_version,
		'php_version' => PHP_VERSION,
		'mysql_version' => $GLOBALS['wpdb']->db_version(),
		'memory_limit' => $memory_limit,
		'memory_limit_bytes' => $memory_limit_bytes,
		'max_execution_time' => ini_get( 'max_execution_time' )
	);
}

/**
 * Get database information
 */
function bible_here_get_database_info() {
	global $wpdb;
	
	$versions_table = $wpdb->prefix . 'bible_versions';
	$verses_table = $wpdb->prefix . 'bible_verses';
	$cache_table = $wpdb->prefix . 'bible_search_cache';
	$history_table = $wpdb->prefix . 'bible_search_history';
	
	// Get version counts
	$total_versions = $wpdb->get_var( "SELECT COUNT(*) FROM {$versions_table}" );
	$active_versions = $wpdb->get_var( "SELECT COUNT(*) FROM {$versions_table} WHERE status = 'active'" );
	
	// Get verse count
	$total_verses = $wpdb->get_var( "SELECT COUNT(*) FROM {$verses_table}" );
	
	// Get cache and history counts
	$cache_entries = $wpdb->get_var( "SELECT COUNT(*) FROM {$cache_table}" ) ?: 0;
	$history_entries = $wpdb->get_var( "SELECT COUNT(*) FROM {$history_table}" ) ?: 0;
	
	// Get table sizes
	$verses_size = bible_here_get_table_size( $verses_table );
	$cache_size = bible_here_get_table_size( $cache_table );
	$history_size = bible_here_get_table_size( $history_table );
	$total_size = bible_here_format_bytes( 
		bible_here_parse_size( $verses_size ) + 
		bible_here_parse_size( $cache_size ) + 
		bible_here_parse_size( $history_size )
	);
	
	// Check if optimization is needed
	$needs_optimization = ( $cache_entries > 1000 ) || ( bible_here_parse_size( $total_size ) > 50 * 1024 * 1024 ); // 50MB
	
	return array(
		'total_versions' => $total_versions ?: 0,
		'active_versions' => $active_versions ?: 0,
		'total_verses' => $total_verses ?: 0,
		'cache_entries' => $cache_entries,
		'history_entries' => $history_entries,
		'verses_size' => $verses_size,
		'cache_size' => $cache_size,
		'history_size' => $history_size,
		'total_size' => $total_size,
		'needs_optimization' => $needs_optimization
	);
}

/**
 * Get cache information
 */
function bible_here_get_cache_info() {
	global $wpdb;
	
	$cache_table = $wpdb->prefix . 'bible_search_cache';
	$search_cache_enabled = get_option( 'bible_here_enable_search_cache', '1' ) === '1';
	$cache_expiry_hours = (int) get_option( 'bible_here_cache_expiry_hours', '24' );
	
	// Calculate hit rate (simplified)
	$total_searches = get_option( 'bible_here_total_searches', 0 );
	$cache_hits = get_option( 'bible_here_cache_hits', 0 );
	$hit_rate = $total_searches > 0 ? round( ( $cache_hits / $total_searches ) * 100 ) : 0;
	
	// Get expired entries count
	$expired_entries = 0;
	if ( $search_cache_enabled ) {
		$expired_entries = $wpdb->get_var( $wpdb->prepare( 
			"SELECT COUNT(*) FROM {$cache_table} WHERE created_at < %s",
			date( 'Y-m-d H:i:s', strtotime( "-{$cache_expiry_hours} hours" ) )
		) ) ?: 0;
	}
	
	return array(
		'search_cache_enabled' => $search_cache_enabled,
		'cache_expiry_hours' => $cache_expiry_hours,
		'hit_rate' => $hit_rate,
		'expired_entries' => $expired_entries
	);
}

/**
 * Get recent error logs
 */
function bible_here_get_recent_error_logs() {
	global $wpdb;
	
	$logs_table = $wpdb->prefix . 'bible_error_logs';
	
	// Check if logs table exists
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$logs_table}'" ) !== $logs_table ) {
		return array();
	}
	
	$logs = $wpdb->get_results( 
		"SELECT * FROM {$logs_table} ORDER BY created_at DESC LIMIT 20",
		ARRAY_A
	);
	
	foreach ( $logs as &$log ) {
		if ( ! empty( $log['context'] ) ) {
			$log['context'] = json_decode( $log['context'], true );
		}
	}
	
	return $logs ?: array();
}

/**
 * Get table size in human readable format
 */
function bible_here_get_table_size( $table_name ) {
	global $wpdb;
	
	$size = $wpdb->get_var( $wpdb->prepare( 
		"SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb' 
		 FROM information_schema.TABLES 
		 WHERE table_schema = %s AND table_name = %s",
		DB_NAME,
		$table_name
	) );
	
	return $size ? $size . ' MB' : '0 MB';
}

/**
 * Parse size string to bytes
 */
function bible_here_parse_size( $size_string ) {
	if ( preg_match( '/([0-9.]+)\s*(MB|KB|GB)/i', $size_string, $matches ) ) {
		$value = floatval( $matches[1] );
		$unit = strtoupper( $matches[2] );
		
		switch ( $unit ) {
			case 'GB':
				return $value * 1024 * 1024 * 1024;
			case 'MB':
				return $value * 1024 * 1024;
			case 'KB':
				return $value * 1024;
		}
	}
	return 0;
}

/**
 * Format bytes to human readable format
 */
function bible_here_format_bytes( $bytes ) {
	if ( $bytes >= 1024 * 1024 * 1024 ) {
		return round( $bytes / ( 1024 * 1024 * 1024 ), 2 ) . ' GB';
	} elseif ( $bytes >= 1024 * 1024 ) {
		return round( $bytes / ( 1024 * 1024 ), 2 ) . ' MB';
	} elseif ( $bytes >= 1024 ) {
		return round( $bytes / 1024, 2 ) . ' KB';
	} else {
		return $bytes . ' B';
	}
}

/**
 * Clear search cache
 */
function bible_here_clear_search_cache() {
	global $wpdb;
	$cache_table = $wpdb->prefix . 'bible_search_cache';
	return $wpdb->query( "TRUNCATE TABLE {$cache_table}" ) !== false;
}

/**
 * Clear search history
 */
function bible_here_clear_search_history() {
	global $wpdb;
	$history_table = $wpdb->prefix . 'bible_search_history';
	return $wpdb->query( "TRUNCATE TABLE {$history_table}" ) !== false;
}

/**
 * Optimize database
 */
function bible_here_optimize_database() {
	global $wpdb;
	
	$tables = array(
		$wpdb->prefix . 'bible_versions',
		$wpdb->prefix . 'bible_verses',
		$wpdb->prefix . 'bible_search_cache',
		$wpdb->prefix . 'bible_search_history'
	);
	
	$success = true;
	foreach ( $tables as $table ) {
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
			$result = $wpdb->query( "OPTIMIZE TABLE {$table}" );
			if ( $result === false ) {
				$success = false;
			}
		}
	}
	
	return $success;
}

/**
 * Clear error logs
 */
function bible_here_clear_error_logs() {
	global $wpdb;
	$logs_table = $wpdb->prefix . 'bible_error_logs';
	
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$logs_table}'" ) === $logs_table ) {
		return $wpdb->query( "TRUNCATE TABLE {$logs_table}" ) !== false;
	}
	
	return true;
}

/**
 * Rebuild search index
 */
function bible_here_reindex_search() {
	global $wpdb;
	
	$verses_table = $wpdb->prefix . 'bible_verses';
	
	// Drop and recreate fulltext indexes
	$wpdb->query( "ALTER TABLE {$verses_table} DROP INDEX IF EXISTS idx_fulltext_search" );
	$result = $wpdb->query( "ALTER TABLE {$verses_table} ADD FULLTEXT idx_fulltext_search (text, book_name)" );
	
	return $result !== false;
}

?>