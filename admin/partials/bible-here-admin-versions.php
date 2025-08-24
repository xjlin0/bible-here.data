<?php

/**
 * Provide a admin area view for Bible versions management
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

// Handle form submissions
if ( isset( $_POST['action'] ) && wp_verify_nonce( $_POST['bible_here_nonce'], 'bible_here_versions' ) ) {
	switch ( $_POST['action'] ) {
		case 'toggle_status':
			$version_id = intval( $_POST['version_id'] );
			$new_status = sanitize_text_field( $_POST['new_status'] );
			
			global $wpdb;
			$table_name = $wpdb->prefix . 'bible_versions';
			
			$result = $wpdb->update(
				$table_name,
				array( 'status' => $new_status ),
				array( 'id' => $version_id ),
				array( '%s' ),
				array( '%d' )
			);
			
			if ( $result !== false ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Version status updated successfully.', 'bible-here' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Failed to update version status.', 'bible-here' ) . '</p></div>';
			}
			break;
			
		case 'delete_version':
			$version_id = intval( $_POST['version_id'] );
			
			global $wpdb;
			$versions_table = $wpdb->prefix . 'bible_versions';
			$verses_table = $wpdb->prefix . 'bible_verses';
			
			// Delete verses first
			$wpdb->delete( $verses_table, array( 'version_id' => $version_id ), array( '%d' ) );
			
			// Delete version
			$result = $wpdb->delete( $versions_table, array( 'id' => $version_id ), array( '%d' ) );
			
			if ( $result !== false ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Version deleted successfully.', 'bible-here' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Failed to delete version.', 'bible-here' ) . '</p></div>';
			}
			break;
	}
}

// Get all Bible versions
global $wpdb;
$table_name = $wpdb->prefix . 'bible_versions';
$versions = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY name ASC" );

?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<div class="bible-here-versions-admin">
		<!-- Add New Version Card -->
		<div class="postbox">
			<h2 class="hndle"><span><?php _e( 'Add New Bible Version', 'bible-here' ); ?></span></h2>
			<div class="inside">
				<p><?php _e( 'To add a new Bible version, use the Import Data page to download and import Zefania XML files.', 'bible-here' ); ?></p>
				<a href="<?php echo admin_url( 'admin.php?page=bible-here-import' ); ?>" class="button button-primary">
					<span class="dashicons dashicons-download"></span>
					<?php _e( 'Go to Import Data', 'bible-here' ); ?>
				</a>
			</div>
		</div>

		<!-- Existing Versions -->
		<div class="postbox">
			<h2 class="hndle"><span><?php _e( 'Installed Bible Versions', 'bible-here' ); ?></span></h2>
			<div class="inside">
				<?php if ( empty( $versions ) ) : ?>
					<p><?php _e( 'No Bible versions installed yet. Please import some Bible data first.', 'bible-here' ); ?></p>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th scope="col" class="manage-column column-name"><?php _e( 'Name', 'bible-here' ); ?></th>
								<th scope="col" class="manage-column column-abbreviation"><?php _e( 'Abbreviation', 'bible-here' ); ?></th>
								<th scope="col" class="manage-column column-language"><?php _e( 'Language', 'bible-here' ); ?></th>
								<th scope="col" class="manage-column column-status"><?php _e( 'Status', 'bible-here' ); ?></th>
								<th scope="col" class="manage-column column-verses"><?php _e( 'Verses', 'bible-here' ); ?></th>
								<th scope="col" class="manage-column column-actions"><?php _e( 'Actions', 'bible-here' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $versions as $version ) : 
								// Get verse count for this version
								$verses_table = $wpdb->prefix . 'bible_verses';
								$verse_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$verses_table} WHERE version_id = %d", $version->id ) );
							?>
								<tr>
									<td class="column-name">
										<strong><?php echo esc_html( $version->name ); ?></strong>
										<?php if ( ! empty( $version->description ) ) : ?>
											<br><small><?php echo esc_html( $version->description ); ?></small>
										<?php endif; ?>
									</td>
									<td class="column-abbreviation">
										<code><?php echo esc_html( strtoupper( $version->abbreviation ) ); ?></code>
									</td>
									<td class="column-language">
										<?php echo esc_html( $version->language ); ?>
									</td>
									<td class="column-status">
										<?php if ( $version->status === 'active' ) : ?>
											<span class="status-active"><?php _e( 'Active', 'bible-here' ); ?></span>
										<?php else : ?>
											<span class="status-inactive"><?php _e( 'Inactive', 'bible-here' ); ?></span>
										<?php endif; ?>
									</td>
									<td class="column-verses">
										<?php echo number_format( intval( $verse_count ) ); ?>
									</td>
									<td class="column-actions">
										<div class="row-actions">
											<?php if ( $version->status === 'active' ) : ?>
												<form method="post" style="display: inline;">
													<?php wp_nonce_field( 'bible_here_versions', 'bible_here_nonce' ); ?>
													<input type="hidden" name="action" value="toggle_status">
													<input type="hidden" name="version_id" value="<?php echo esc_attr( $version->id ); ?>">
													<input type="hidden" name="new_status" value="inactive">
													<button type="submit" class="button-link" onclick="return confirm('<?php _e( 'Are you sure you want to deactivate this version?', 'bible-here' ); ?>')">
														<?php _e( 'Deactivate', 'bible-here' ); ?>
													</button>
												</form>
											<?php else : ?>
												<form method="post" style="display: inline;">
													<?php wp_nonce_field( 'bible_here_versions', 'bible_here_nonce' ); ?>
													<input type="hidden" name="action" value="toggle_status">
													<input type="hidden" name="version_id" value="<?php echo esc_attr( $version->id ); ?>">
													<input type="hidden" name="new_status" value="active">
													<button type="submit" class="button-link">
														<?php _e( 'Activate', 'bible-here' ); ?>
													</button>
												</form>
											<?php endif; ?>
											
											<span class="separator"> | </span>
											
											<form method="post" style="display: inline;">
												<?php wp_nonce_field( 'bible_here_versions', 'bible_here_nonce' ); ?>
												<input type="hidden" name="action" value="delete_version">
												<input type="hidden" name="version_id" value="<?php echo esc_attr( $version->id ); ?>">
												<button type="submit" class="button-link delete-link" onclick="return confirm('<?php _e( 'Are you sure you want to delete this version? This will also delete all associated verses and cannot be undone.', 'bible-here' ); ?>')">
													<?php _e( 'Delete', 'bible-here' ); ?>
												</button>
											</form>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<style>
.bible-here-versions-admin .postbox {
	margin-bottom: 20px;
}

.bible-here-versions-admin .button {
	display: inline-flex;
	align-items: center;
	gap: 5px;
}

.status-active {
	color: #46b450;
	font-weight: bold;
}

.status-inactive {
	color: #dc3232;
	font-weight: bold;
}

.column-name {
	width: 25%;
}

.column-abbreviation {
	width: 15%;
}

.column-language {
	width: 15%;
}

.column-status {
	width: 10%;
}

.column-verses {
	width: 15%;
}

.column-actions {
	width: 20%;
}

.delete-link {
	color: #dc3232 !important;
}

.delete-link:hover {
	color: #a00 !important;
}

.row-actions {
	visibility: hidden;
}

tr:hover .row-actions {
	visibility: visible;
}

@media (max-width: 782px) {
	.wp-list-table td {
		display: block;
		width: 100% !important;
		text-align: left !important;
	}
	
	.wp-list-table th {
		display: none;
	}
	
	.wp-list-table td:before {
		content: attr(data-label) ": ";
		font-weight: bold;
		display: inline-block;
		width: 100px;
	}
}
</style>