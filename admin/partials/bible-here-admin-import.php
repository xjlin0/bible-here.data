<?php

/**
 * Provide a admin area view for Bible data import
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
if ( isset( $_POST['action'] ) && wp_verify_nonce( $_POST['bible_here_nonce'], 'bible_here_import' ) ) {
	switch ( $_POST['action'] ) {
		case 'download_and_import':
			$download_url = sanitize_url( $_POST['download_url'] );
			$version_name = sanitize_text_field( $_POST['version_name'] );
			$version_abbr = sanitize_text_field( $_POST['version_abbr'] );
			$version_lang = sanitize_text_field( $_POST['version_lang'] );
			
			if ( empty( $download_url ) || empty( $version_name ) || empty( $version_abbr ) ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Please fill in all required fields.', 'bible-here' ) . '</p></div>';
			} else {
				// Start import process
				echo '<div class="notice notice-info"><p>' . __( 'Starting download and import process...', 'bible-here' ) . '</p></div>';
				
				// Download file
				$temp_file = download_url( $download_url );
				
				if ( is_wp_error( $temp_file ) ) {
					echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Failed to download file: ', 'bible-here' ) . $temp_file->get_error_message() . '</p></div>';
				} else {
					// Process the downloaded file
					$result = bible_here_import_zefania_xml( $temp_file, $version_name, $version_abbr, $version_lang );
					
					// Clean up temp file
					unlink( $temp_file );
					
					if ( $result['success'] ) {
						echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Successfully imported %d verses from %s', 'bible-here' ), $result['verses_imported'], $version_name ) . '</p></div>';
					} else {
						echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Import failed: ', 'bible-here' ) . $result['error'] . '</p></div>';
					}
				}
			}
			break;
			
		case 'upload_and_import':
			if ( ! empty( $_FILES['xml_file']['tmp_name'] ) ) {
				$version_name = sanitize_text_field( $_POST['upload_version_name'] );
				$version_abbr = sanitize_text_field( $_POST['upload_version_abbr'] );
				$version_lang = sanitize_text_field( $_POST['upload_version_lang'] );
				
				if ( empty( $version_name ) || empty( $version_abbr ) ) {
					echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Please fill in all required fields.', 'bible-here' ) . '</p></div>';
				} else {
					$uploaded_file = $_FILES['xml_file']['tmp_name'];
					
					// Validate file type
					if ( pathinfo( $_FILES['xml_file']['name'], PATHINFO_EXTENSION ) !== 'xml' ) {
						echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Please upload a valid XML file.', 'bible-here' ) . '</p></div>';
					} else {
						$result = bible_here_import_zefania_xml( $uploaded_file, $version_name, $version_abbr, $version_lang );
						
						if ( $result['success'] ) {
							echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Successfully imported %d verses from %s', 'bible-here' ), $result['verses_imported'], $version_name ) . '</p></div>';
						} else {
							echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Import failed: ', 'bible-here' ) . $result['error'] . '</p></div>';
						}
					}
				}
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Please select a file to upload.', 'bible-here' ) . '</p></div>';
			}
			break;
	}
}

// Popular Bible versions available for download
$popular_versions = array(
	'kjv' => array(
		'name' => 'King James Version',
		'abbreviation' => 'KJV',
		'language' => 'English',
		'url' => 'https://raw.githubusercontent.com/christos-c/bible-corpus/master/bibles/English-KJV.xml'
	),
	'asv' => array(
		'name' => 'American Standard Version',
		'abbreviation' => 'ASV',
		'language' => 'English',
		'url' => 'https://raw.githubusercontent.com/christos-c/bible-corpus/master/bibles/English-ASV.xml'
	),
	'web' => array(
		'name' => 'World English Bible',
		'abbreviation' => 'WEB',
		'language' => 'English',
		'url' => 'https://raw.githubusercontent.com/christos-c/bible-corpus/master/bibles/English-WEB.xml'
	),
	'cunp' => array(
		'name' => '和合本',
		'abbreviation' => 'CUNP',
		'language' => 'Chinese',
		'url' => 'https://raw.githubusercontent.com/christos-c/bible-corpus/master/bibles/Chinese-CUNP.xml'
	)
);

?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<div class="bible-here-import-admin">
		<!-- Popular Versions -->
		<div class="postbox">
			<h2 class="hndle"><span><?php _e( 'Popular Bible Versions', 'bible-here' ); ?></span></h2>
			<div class="inside">
				<p><?php _e( 'Click on any version below to quickly download and import it:', 'bible-here' ); ?></p>
				
				<div class="popular-versions-grid">
					<?php foreach ( $popular_versions as $key => $version ) : ?>
						<div class="version-card">
							<h4><?php echo esc_html( $version['name'] ); ?></h4>
							<p>
								<strong><?php _e( 'Abbreviation:', 'bible-here' ); ?></strong> <?php echo esc_html( $version['abbreviation'] ); ?><br>
								<strong><?php _e( 'Language:', 'bible-here' ); ?></strong> <?php echo esc_html( $version['language'] ); ?>
							</p>
							<form method="post" class="quick-import-form">
								<?php wp_nonce_field( 'bible_here_import', 'bible_here_nonce' ); ?>
								<input type="hidden" name="action" value="download_and_import">
								<input type="hidden" name="download_url" value="<?php echo esc_url( $version['url'] ); ?>">
								<input type="hidden" name="version_name" value="<?php echo esc_attr( $version['name'] ); ?>">
								<input type="hidden" name="version_abbr" value="<?php echo esc_attr( $version['abbreviation'] ); ?>">
								<input type="hidden" name="version_lang" value="<?php echo esc_attr( $version['language'] ); ?>">
								<button type="submit" class="button button-primary" onclick="return confirm('<?php _e( 'This will download and import the Bible version. This may take several minutes. Continue?', 'bible-here' ); ?>')">
									<span class="dashicons dashicons-download"></span>
									<?php _e( 'Import', 'bible-here' ); ?>
								</button>
							</form>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<!-- Custom Download -->
		<div class="postbox">
			<h2 class="hndle"><span><?php _e( 'Download from URL', 'bible-here' ); ?></span></h2>
			<div class="inside">
				<p><?php _e( 'Enter a direct URL to a Zefania XML Bible file to download and import:', 'bible-here' ); ?></p>
				
				<form method="post" class="custom-download-form">
					<?php wp_nonce_field( 'bible_here_import', 'bible_here_nonce' ); ?>
					<input type="hidden" name="action" value="download_and_import">
					
					<table class="form-table">
						<tr>
							<th scope="row"><label for="download_url"><?php _e( 'Download URL', 'bible-here' ); ?> *</label></th>
							<td><input type="url" id="download_url" name="download_url" class="regular-text" required placeholder="https://example.com/bible.xml"></td>
						</tr>
						<tr>
							<th scope="row"><label for="version_name"><?php _e( 'Version Name', 'bible-here' ); ?> *</label></th>
							<td><input type="text" id="version_name" name="version_name" class="regular-text" required placeholder="<?php _e( 'e.g., New International Version', 'bible-here' ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="version_abbr"><?php _e( 'Abbreviation', 'bible-here' ); ?> *</label></th>
							<td><input type="text" id="version_abbr" name="version_abbr" class="small-text" required placeholder="<?php _e( 'e.g., NIV', 'bible-here' ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="version_lang"><?php _e( 'Language', 'bible-here' ); ?></label></th>
							<td><input type="text" id="version_lang" name="version_lang" class="regular-text" placeholder="<?php _e( 'e.g., English', 'bible-here' ); ?>"></td>
						</tr>
					</table>
					
					<p class="submit">
						<button type="submit" class="button button-primary" onclick="return confirm('<?php _e( 'This will download and import the Bible version. This may take several minutes. Continue?', 'bible-here' ); ?>')">
							<span class="dashicons dashicons-download"></span>
							<?php _e( 'Download and Import', 'bible-here' ); ?>
						</button>
					</p>
				</form>
			</div>
		</div>

		<!-- File Upload -->
		<div class="postbox">
			<h2 class="hndle"><span><?php _e( 'Upload XML File', 'bible-here' ); ?></span></h2>
			<div class="inside">
				<p><?php _e( 'Upload a Zefania XML Bible file from your computer:', 'bible-here' ); ?></p>
				
				<form method="post" enctype="multipart/form-data" class="file-upload-form">
					<?php wp_nonce_field( 'bible_here_import', 'bible_here_nonce' ); ?>
					<input type="hidden" name="action" value="upload_and_import">
					
					<table class="form-table">
						<tr>
							<th scope="row"><label for="xml_file"><?php _e( 'XML File', 'bible-here' ); ?> *</label></th>
							<td><input type="file" id="xml_file" name="xml_file" accept=".xml" required></td>
						</tr>
						<tr>
							<th scope="row"><label for="upload_version_name"><?php _e( 'Version Name', 'bible-here' ); ?> *</label></th>
							<td><input type="text" id="upload_version_name" name="upload_version_name" class="regular-text" required placeholder="<?php _e( 'e.g., New International Version', 'bible-here' ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="upload_version_abbr"><?php _e( 'Abbreviation', 'bible-here' ); ?> *</label></th>
							<td><input type="text" id="upload_version_abbr" name="upload_version_abbr" class="small-text" required placeholder="<?php _e( 'e.g., NIV', 'bible-here' ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="upload_version_lang"><?php _e( 'Language', 'bible-here' ); ?></label></th>
							<td><input type="text" id="upload_version_lang" name="upload_version_lang" class="regular-text" placeholder="<?php _e( 'e.g., English', 'bible-here' ); ?>"></td>
						</tr>
					</table>
					
					<p class="submit">
						<button type="submit" class="button button-primary" onclick="return confirm('<?php _e( 'This will import the Bible version. This may take several minutes. Continue?', 'bible-here' ); ?>')">
							<span class="dashicons dashicons-upload"></span>
							<?php _e( 'Upload and Import', 'bible-here' ); ?>
						</button>
					</p>
				</form>
			</div>
		</div>

		<!-- Import Instructions -->
		<div class="postbox">
			<h2 class="hndle"><span><?php _e( 'Import Instructions', 'bible-here' ); ?></span></h2>
			<div class="inside">
				<h4><?php _e( 'Supported Format', 'bible-here' ); ?></h4>
				<p><?php _e( 'This plugin supports Zefania XML format Bible files. You can find many free Bible versions in this format from:', 'bible-here' ); ?></p>
				<ul>
					<li><a href="https://github.com/christos-c/bible-corpus" target="_blank">Bible Corpus on GitHub</a></li>
					<li><a href="https://www.zefania.com/" target="_blank">Zefania.com</a></li>
					<li><a href="https://sourceforge.net/projects/zefania-sharp/" target="_blank">Zefania Sharp Project</a></li>
				</ul>
				
				<h4><?php _e( 'Import Process', 'bible-here' ); ?></h4>
				<ol>
					<li><?php _e( 'The import process may take several minutes depending on the file size.', 'bible-here' ); ?></li>
					<li><?php _e( 'Do not close this page or navigate away during the import.', 'bible-here' ); ?></li>
					<li><?php _e( 'After successful import, the new version will be available in the Bible Versions page.', 'bible-here' ); ?></li>
					<li><?php _e( 'You can activate/deactivate versions as needed.', 'bible-here' ); ?></li>
				</ol>
				
				<h4><?php _e( 'Troubleshooting', 'bible-here' ); ?></h4>
				<ul>
					<li><?php _e( 'If import fails, check that the XML file is valid Zefania format.', 'bible-here' ); ?></li>
					<li><?php _e( 'Large files may timeout - consider increasing PHP max_execution_time.', 'bible-here' ); ?></li>
					<li><?php _e( 'Check the System Status page for any configuration issues.', 'bible-here' ); ?></li>
				</ul>
			</div>
		</div>
	</div>
</div>

<style>
.bible-here-import-admin .postbox {
	margin-bottom: 20px;
}

.popular-versions-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 20px;
	margin-top: 15px;
}

.version-card {
	border: 1px solid #ddd;
	padding: 15px;
	border-radius: 5px;
	background: #f9f9f9;
}

.version-card h4 {
	margin-top: 0;
	margin-bottom: 10px;
	color: #0073aa;
}

.version-card p {
	margin-bottom: 15px;
	font-size: 13px;
}

.quick-import-form .button {
	display: inline-flex;
	align-items: center;
	gap: 5px;
}

.form-table th {
	width: 150px;
}

.bible-here-import-admin .button {
	display: inline-flex;
	align-items: center;
	gap: 5px;
}

.bible-here-import-admin ul {
	margin-left: 20px;
}

.bible-here-import-admin ol {
	margin-left: 20px;
}

@media (max-width: 782px) {
	.popular-versions-grid {
		grid-template-columns: 1fr;
	}
	
	.form-table th,
	.form-table td {
		display: block;
		width: 100%;
	}
	
	.form-table th {
		padding-bottom: 5px;
	}
}
</style>

<?php

/**
 * Import Zefania XML Bible file
 *
 * @param string $file_path Path to the XML file
 * @param string $version_name Name of the Bible version
 * @param string $version_abbr Abbreviation of the Bible version
 * @param string $version_lang Language of the Bible version
 * @return array Result array with success status and details
 */
function bible_here_import_zefania_xml( $file_path, $version_name, $version_abbr, $version_lang = 'English' ) {
	global $wpdb;
	
	// Check if file exists
	if ( ! file_exists( $file_path ) ) {
		return array( 'success' => false, 'error' => 'File not found' );
	}
	
	// Load XML
	libxml_use_internal_errors( true );
	$xml = simplexml_load_file( $file_path );
	
	if ( $xml === false ) {
		$errors = libxml_get_errors();
		$error_message = 'Invalid XML file';
		if ( ! empty( $errors ) ) {
			$error_message .= ': ' . $errors[0]->message;
		}
		return array( 'success' => false, 'error' => $error_message );
	}
	
	// Check if version already exists
	$versions_table = $wpdb->prefix . 'bible_versions';
	$existing_version = $wpdb->get_var( $wpdb->prepare( 
		"SELECT id FROM {$versions_table} WHERE abbreviation = %s", 
		$version_abbr 
	) );
	
	if ( $existing_version ) {
		return array( 'success' => false, 'error' => 'Version with this abbreviation already exists' );
	}
	
	// Insert version record
	$version_result = $wpdb->insert(
		$versions_table,
		array(
			'name' => $version_name,
			'abbreviation' => $version_abbr,
			'language' => $version_lang,
			'status' => 'active',
			'created_at' => current_time( 'mysql' )
		),
		array( '%s', '%s', '%s', '%s', '%s' )
	);
	
	if ( $version_result === false ) {
		return array( 'success' => false, 'error' => 'Failed to create version record' );
	}
	
	$version_id = $wpdb->insert_id;
	$verses_table = $wpdb->prefix . 'bible_verses';
	$verses_imported = 0;
	
	// Parse XML and import verses
	try {
		// Zefania XML structure: XMLBIBLE > BIBLEBOOK > CHAPTER > VERS
		foreach ( $xml->BIBLEBOOK as $book ) {
			$book_name = (string) $book['bname'];
			$book_number = (int) $book['bnumber'];
			
			foreach ( $book->CHAPTER as $chapter ) {
				$chapter_number = (int) $chapter['cnumber'];
				
				foreach ( $chapter->VERS as $verse ) {
					$verse_number = (int) $verse['vnumber'];
					$verse_text = (string) $verse;
					
					// Insert verse
					$verse_result = $wpdb->insert(
						$verses_table,
						array(
							'version_id' => $version_id,
							'book_name' => $book_name,
							'book_number' => $book_number,
							'chapter' => $chapter_number,
							'verse' => $verse_number,
							'text' => $verse_text
						),
						array( '%d', '%s', '%d', '%d', '%d', '%s' )
					);
					
					if ( $verse_result !== false ) {
						$verses_imported++;
					}
				}
			}
		}
		
		return array( 
			'success' => true, 
			'verses_imported' => $verses_imported,
			'version_id' => $version_id
		);
		
	} catch ( Exception $e ) {
		// Clean up on error
		$wpdb->delete( $versions_table, array( 'id' => $version_id ), array( '%d' ) );
		$wpdb->delete( $verses_table, array( 'version_id' => $version_id ), array( '%d' ) );
		
		return array( 'success' => false, 'error' => $e->getMessage() );
	}
}

?>