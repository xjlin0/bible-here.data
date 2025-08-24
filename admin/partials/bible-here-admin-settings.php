<?php

/**
 * Provide a admin area view for plugin settings
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

// Get current settings
$default_version = get_option( 'bible_here_default_version', '' );
$auto_detect = get_option( 'bible_here_auto_detect', '1' );
$popup_style = get_option( 'bible_here_popup_style', 'modern' );
$popup_width = get_option( 'bible_here_popup_width', '400' );
$popup_height = get_option( 'bible_here_popup_height', '300' );
$search_results_per_page = get_option( 'bible_here_search_results_per_page', '10' );
$enable_search_cache = get_option( 'bible_here_enable_search_cache', '1' );
$cache_expiry_hours = get_option( 'bible_here_cache_expiry_hours', '24' );
$enable_search_history = get_option( 'bible_here_enable_search_history', '1' );
$max_history_items = get_option( 'bible_here_max_history_items', '20' );
$enable_verse_context = get_option( 'bible_here_enable_verse_context', '1' );
$context_verses_before = get_option( 'bible_here_context_verses_before', '2' );
$context_verses_after = get_option( 'bible_here_context_verses_after', '2' );
$enable_copy_feature = get_option( 'bible_here_enable_copy_feature', '1' );
$copy_format = get_option( 'bible_here_copy_format', 'verse_reference' );
$custom_css = get_option( 'bible_here_custom_css', '' );
$debug_mode = get_option( 'bible_here_debug_mode', '0' );

// Get available versions
global $wpdb;
$versions_table = $wpdb->prefix . 'bible_versions';
$available_versions = $wpdb->get_results( "SELECT * FROM {$versions_table} WHERE status = 'active' ORDER BY name" );

?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<form method="post" action="options.php">
		<?php
		settings_fields( 'bible_here_settings' );
		do_settings_sections( 'bible_here_settings' );
		?>
		
		<div class="bible-here-settings-admin">
			<!-- General Settings -->
			<div class="postbox">
				<h2 class="hndle"><span><?php _e( 'General Settings', 'bible-here' ); ?></span></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><label for="bible_here_default_version"><?php _e( 'Default Bible Version', 'bible-here' ); ?></label></th>
							<td>
								<select id="bible_here_default_version" name="bible_here_default_version">
									<option value=""><?php _e( 'Select a version...', 'bible-here' ); ?></option>
									<?php foreach ( $available_versions as $version ) : ?>
										<option value="<?php echo esc_attr( $version->abbreviation ); ?>" <?php selected( $default_version, $version->abbreviation ); ?>>
											<?php echo esc_html( $version->name . ' (' . $version->abbreviation . ')' ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php _e( 'This version will be used when no specific version is requested.', 'bible-here' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'Auto-detect Bible References', 'bible-here' ); ?></th>
							<td>
								<fieldset>
									<label for="bible_here_auto_detect">
										<input type="checkbox" id="bible_here_auto_detect" name="bible_here_auto_detect" value="1" <?php checked( $auto_detect, '1' ); ?>>
										<?php _e( 'Enable automatic detection of Bible references in content', 'bible-here' ); ?>
									</label>
									<p class="description"><?php _e( 'When enabled, Bible references like "John 3:16" will be automatically detected and made clickable.', 'bible-here' ); ?></p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'Debug Mode', 'bible-here' ); ?></th>
							<td>
								<fieldset>
									<label for="bible_here_debug_mode">
										<input type="checkbox" id="bible_here_debug_mode" name="bible_here_debug_mode" value="1" <?php checked( $debug_mode, '1' ); ?>>
										<?php _e( 'Enable debug mode', 'bible-here' ); ?>
									</label>
									<p class="description"><?php _e( 'When enabled, additional debugging information will be logged.', 'bible-here' ); ?></p>
								</fieldset>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- Popup Settings -->
			<div class="postbox">
				<h2 class="hndle"><span><?php _e( 'Popup Settings', 'bible-here' ); ?></span></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><label for="bible_here_popup_style"><?php _e( 'Popup Style', 'bible-here' ); ?></label></th>
							<td>
								<select id="bible_here_popup_style" name="bible_here_popup_style">
									<option value="modern" <?php selected( $popup_style, 'modern' ); ?>><?php _e( 'Modern', 'bible-here' ); ?></option>
									<option value="classic" <?php selected( $popup_style, 'classic' ); ?>><?php _e( 'Classic', 'bible-here' ); ?></option>
									<option value="minimal" <?php selected( $popup_style, 'minimal' ); ?>><?php _e( 'Minimal', 'bible-here' ); ?></option>
								</select>
								<p class="description"><?php _e( 'Choose the visual style for Bible verse popups.', 'bible-here' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="bible_here_popup_width"><?php _e( 'Popup Width', 'bible-here' ); ?></label></th>
							<td>
								<input type="number" id="bible_here_popup_width" name="bible_here_popup_width" value="<?php echo esc_attr( $popup_width ); ?>" min="200" max="800" step="10">
								<span class="description"><?php _e( 'pixels', 'bible-here' ); ?></span>
								<p class="description"><?php _e( 'Maximum width of the popup window.', 'bible-here' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="bible_here_popup_height"><?php _e( 'Popup Height', 'bible-here' ); ?></label></th>
							<td>
								<input type="number" id="bible_here_popup_height" name="bible_here_popup_height" value="<?php echo esc_attr( $popup_height ); ?>" min="150" max="600" step="10">
								<span class="description"><?php _e( 'pixels', 'bible-here' ); ?></span>
								<p class="description"><?php _e( 'Maximum height of the popup window.', 'bible-here' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- Search Settings -->
			<div class="postbox">
				<h2 class="hndle"><span><?php _e( 'Search Settings', 'bible-here' ); ?></span></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><label for="bible_here_search_results_per_page"><?php _e( 'Results Per Page', 'bible-here' ); ?></label></th>
							<td>
								<input type="number" id="bible_here_search_results_per_page" name="bible_here_search_results_per_page" value="<?php echo esc_attr( $search_results_per_page ); ?>" min="5" max="50" step="5">
								<p class="description"><?php _e( 'Number of search results to display per page.', 'bible-here' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'Search Cache', 'bible-here' ); ?></th>
							<td>
								<fieldset>
									<label for="bible_here_enable_search_cache">
										<input type="checkbox" id="bible_here_enable_search_cache" name="bible_here_enable_search_cache" value="1" <?php checked( $enable_search_cache, '1' ); ?>>
										<?php _e( 'Enable search result caching', 'bible-here' ); ?>
									</label>
									<p class="description"><?php _e( 'Cache search results to improve performance.', 'bible-here' ); ?></p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="bible_here_cache_expiry_hours"><?php _e( 'Cache Expiry', 'bible-here' ); ?></label></th>
							<td>
								<input type="number" id="bible_here_cache_expiry_hours" name="bible_here_cache_expiry_hours" value="<?php echo esc_attr( $cache_expiry_hours ); ?>" min="1" max="168" step="1">
								<span class="description"><?php _e( 'hours', 'bible-here' ); ?></span>
								<p class="description"><?php _e( 'How long to keep cached search results.', 'bible-here' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'Search History', 'bible-here' ); ?></th>
							<td>
								<fieldset>
									<label for="bible_here_enable_search_history">
										<input type="checkbox" id="bible_here_enable_search_history" name="bible_here_enable_search_history" value="1" <?php checked( $enable_search_history, '1' ); ?>>
										<?php _e( 'Enable search history', 'bible-here' ); ?>
									</label>
									<p class="description"><?php _e( 'Keep track of recent searches for quick access.', 'bible-here' ); ?></p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="bible_here_max_history_items"><?php _e( 'Max History Items', 'bible-here' ); ?></label></th>
							<td>
								<input type="number" id="bible_here_max_history_items" name="bible_here_max_history_items" value="<?php echo esc_attr( $max_history_items ); ?>" min="5" max="100" step="5">
								<p class="description"><?php _e( 'Maximum number of search history items to keep.', 'bible-here' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- Verse Context Settings -->
			<div class="postbox">
				<h2 class="hndle"><span><?php _e( 'Verse Context Settings', 'bible-here' ); ?></span></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php _e( 'Enable Verse Context', 'bible-here' ); ?></th>
							<td>
								<fieldset>
									<label for="bible_here_enable_verse_context">
										<input type="checkbox" id="bible_here_enable_verse_context" name="bible_here_enable_verse_context" value="1" <?php checked( $enable_verse_context, '1' ); ?>>
										<?php _e( 'Show verse context feature', 'bible-here' ); ?>
									</label>
									<p class="description"><?php _e( 'Allow users to view surrounding verses for context.', 'bible-here' ); ?></p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="bible_here_context_verses_before"><?php _e( 'Verses Before', 'bible-here' ); ?></label></th>
							<td>
								<input type="number" id="bible_here_context_verses_before" name="bible_here_context_verses_before" value="<?php echo esc_attr( $context_verses_before ); ?>" min="0" max="10" step="1">
								<p class="description"><?php _e( 'Number of verses to show before the target verse.', 'bible-here' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="bible_here_context_verses_after"><?php _e( 'Verses After', 'bible-here' ); ?></label></th>
							<td>
								<input type="number" id="bible_here_context_verses_after" name="bible_here_context_verses_after" value="<?php echo esc_attr( $context_verses_after ); ?>" min="0" max="10" step="1">
								<p class="description"><?php _e( 'Number of verses to show after the target verse.', 'bible-here' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- Copy Feature Settings -->
			<div class="postbox">
				<h2 class="hndle"><span><?php _e( 'Copy Feature Settings', 'bible-here' ); ?></span></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php _e( 'Enable Copy Feature', 'bible-here' ); ?></th>
							<td>
								<fieldset>
									<label for="bible_here_enable_copy_feature">
										<input type="checkbox" id="bible_here_enable_copy_feature" name="bible_here_enable_copy_feature" value="1" <?php checked( $enable_copy_feature, '1' ); ?>>
										<?php _e( 'Show copy to clipboard feature', 'bible-here' ); ?>
									</label>
									<p class="description"><?php _e( 'Allow users to copy verses to clipboard.', 'bible-here' ); ?></p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="bible_here_copy_format"><?php _e( 'Copy Format', 'bible-here' ); ?></label></th>
							<td>
								<select id="bible_here_copy_format" name="bible_here_copy_format">
									<option value="verse_only" <?php selected( $copy_format, 'verse_only' ); ?>><?php _e( 'Verse text only', 'bible-here' ); ?></option>
									<option value="verse_reference" <?php selected( $copy_format, 'verse_reference' ); ?>><?php _e( 'Verse text + reference', 'bible-here' ); ?></option>
									<option value="reference_verse" <?php selected( $copy_format, 'reference_verse' ); ?>><?php _e( 'Reference + verse text', 'bible-here' ); ?></option>
								</select>
								<p class="description"><?php _e( 'Format for copied verse text.', 'bible-here' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- Custom CSS -->
			<div class="postbox">
				<h2 class="hndle"><span><?php _e( 'Custom Styling', 'bible-here' ); ?></span></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><label for="bible_here_custom_css"><?php _e( 'Custom CSS', 'bible-here' ); ?></label></th>
							<td>
								<textarea id="bible_here_custom_css" name="bible_here_custom_css" rows="10" cols="50" class="large-text code"><?php echo esc_textarea( $custom_css ); ?></textarea>
								<p class="description"><?php _e( 'Add custom CSS to style the Bible Here elements. Use with caution.', 'bible-here' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		
		<?php submit_button(); ?>
	</form>
</div>

<style>
.bible-here-settings-admin .postbox {
	margin-bottom: 20px;
}

.bible-here-settings-admin .form-table th {
	width: 200px;
}

.bible-here-settings-admin .form-table td {
	vertical-align: top;
}

.bible-here-settings-admin .description {
	margin-top: 5px;
	color: #666;
	font-style: italic;
}

.bible-here-settings-admin input[type="number"] {
	width: 80px;
}

.bible-here-settings-admin select {
	min-width: 200px;
}

.bible-here-settings-admin textarea.code {
	font-family: Consolas, Monaco, monospace;
	font-size: 12px;
	line-height: 1.4;
}

@media (max-width: 782px) {
	.bible-here-settings-admin .form-table th,
	.bible-here-settings-admin .form-table td {
		display: block;
		width: 100%;
	}
	
	.bible-here-settings-admin .form-table th {
		padding-bottom: 5px;
	}
	
	.bible-here-settings-admin select {
		min-width: 100%;
	}
}
</style>