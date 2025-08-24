<?php

/**
 * Provide a admin area view for the plugin overview
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

// Get plugin data
$plugin_data = get_plugin_data( plugin_dir_path( dirname( __FILE__ ) ) . '../bible-here.php' );

// Get database statistics
global $wpdb;
$table_name = $wpdb->prefix . 'bible_verses';
$versions_table = $wpdb->prefix . 'bible_versions';

$total_verses = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
$total_versions = $wpdb->get_var( "SELECT COUNT(*) FROM {$versions_table}" );
$active_versions = $wpdb->get_var( "SELECT COUNT(*) FROM {$versions_table} WHERE status = 'active'" );

?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<div class="bible-here-admin-overview">
		<!-- Plugin Info Card -->
		<div class="postbox">
			<h2 class="hndle"><span><?php _e( 'Plugin Information', 'bible-here' ); ?></span></h2>
			<div class="inside">
				<table class="form-table">
					<tr>
						<th scope="row"><?php _e( 'Version', 'bible-here' ); ?></th>
						<td><?php echo esc_html( $plugin_data['Version'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Author', 'bible-here' ); ?></th>
						<td><?php echo wp_kses_post( $plugin_data['Author'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Description', 'bible-here' ); ?></th>
						<td><?php echo wp_kses_post( $plugin_data['Description'] ); ?></td>
					</tr>
				</table>
			</div>
		</div>

		<!-- Statistics Card -->
		<div class="postbox">
			<h2 class="hndle"><span><?php _e( 'Database Statistics', 'bible-here' ); ?></span></h2>
			<div class="inside">
				<div class="bible-here-stats">
					<div class="stat-item">
						<div class="stat-number"><?php echo number_format( intval( $total_verses ) ); ?></div>
						<div class="stat-label"><?php _e( 'Total Verses', 'bible-here' ); ?></div>
					</div>
					<div class="stat-item">
						<div class="stat-number"><?php echo number_format( intval( $total_versions ) ); ?></div>
						<div class="stat-label"><?php _e( 'Bible Versions', 'bible-here' ); ?></div>
					</div>
					<div class="stat-item">
						<div class="stat-number"><?php echo number_format( intval( $active_versions ) ); ?></div>
						<div class="stat-label"><?php _e( 'Active Versions', 'bible-here' ); ?></div>
					</div>
				</div>
			</div>
		</div>

		<!-- Quick Actions Card -->
		<div class="postbox">
			<h2 class="hndle"><span><?php _e( 'Quick Actions', 'bible-here' ); ?></span></h2>
			<div class="inside">
				<div class="bible-here-quick-actions">
					<a href="<?php echo admin_url( 'admin.php?page=bible-here-versions' ); ?>" class="button button-primary">
						<span class="dashicons dashicons-book-alt"></span>
						<?php _e( 'Manage Bible Versions', 'bible-here' ); ?>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=bible-here-import' ); ?>" class="button button-secondary">
						<span class="dashicons dashicons-download"></span>
						<?php _e( 'Import Bible Data', 'bible-here' ); ?>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=bible-here-settings' ); ?>" class="button button-secondary">
						<span class="dashicons dashicons-admin-settings"></span>
						<?php _e( 'Plugin Settings', 'bible-here' ); ?>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=bible-here-status' ); ?>" class="button button-secondary">
						<span class="dashicons dashicons-admin-tools"></span>
						<?php _e( 'System Status', 'bible-here' ); ?>
					</a>
				</div>
			</div>
		</div>

		<!-- Usage Guide Card -->
		<div class="postbox">
			<h2 class="hndle"><span><?php _e( 'How to Use', 'bible-here' ); ?></span></h2>
			<div class="inside">
				<div class="bible-here-usage-guide">
					<h4><?php _e( 'Shortcodes', 'bible-here' ); ?></h4>
					<p><?php _e( 'Use these shortcodes in your posts and pages:', 'bible-here' ); ?></p>
					<ul>
						<li><code>[bible-here ref="John 3:16"]</code> - <?php _e( 'Display a single verse', 'bible-here' ); ?></li>
						<li><code>[bible-here ref="John 3:16-17" version="NIV"]</code> - <?php _e( 'Display multiple verses with specific version', 'bible-here' ); ?></li>
						<li><code>[bible-here-reader]</code> - <?php _e( 'Display the Bible reader interface', 'bible-here' ); ?></li>
						<li><code>[bible-here-search]</code> - <?php _e( 'Display the Bible search interface', 'bible-here' ); ?></li>
					</ul>

					<h4><?php _e( 'Auto-Detection', 'bible-here' ); ?></h4>
					<p><?php _e( 'When auto-detection is enabled, Bible references like "John 3:16" will automatically become clickable links.', 'bible-here' ); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
.bible-here-admin-overview {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 20px;
	margin-top: 20px;
}

.bible-here-admin-overview .postbox {
	margin-bottom: 0;
}

.bible-here-stats {
	display: flex;
	justify-content: space-around;
	text-align: center;
}

.stat-item {
	padding: 20px;
}

.stat-number {
	font-size: 2.5em;
	font-weight: bold;
	color: #0073aa;
	line-height: 1;
}

.stat-label {
	font-size: 0.9em;
	color: #666;
	margin-top: 5px;
}

.bible-here-quick-actions {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 10px;
}

.bible-here-quick-actions .button {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 10px 15px;
	height: auto;
	text-decoration: none;
}

.bible-here-quick-actions .button .dashicons {
	margin-right: 5px;
}

.bible-here-usage-guide h4 {
	margin-top: 20px;
	margin-bottom: 10px;
}

.bible-here-usage-guide ul {
	margin-left: 20px;
}

.bible-here-usage-guide code {
	background: #f1f1f1;
	padding: 2px 6px;
	border-radius: 3px;
	font-family: monospace;
}

@media (max-width: 782px) {
	.bible-here-admin-overview {
		grid-template-columns: 1fr;
	}
	
	.bible-here-quick-actions {
		grid-template-columns: 1fr;
	}
	
	.bible-here-stats {
		flex-direction: column;
	}
}
</style>