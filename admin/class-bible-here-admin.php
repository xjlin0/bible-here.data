<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/xjlin0
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/admin
 * @author     Jack Lin <xjlin0@gmail.com>
 */
class Bible_Here_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Add editor integration hooks
		add_action( 'init', array( $this, 'init_editor_integration' ) );
		add_action( 'wp_ajax_bible_here_preview_shortcode', array( $this, 'ajax_preview_shortcode' ) );

		// Add admin menu hooks
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Bible_Here_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Bible_Here_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/bible-here-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * Enqueue admin scripts with proper dependencies
		 */

		// Enqueue jQuery UI Dialog for shortcode dialog
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );

		// Enqueue main admin script
		wp_enqueue_script( 
			$this->plugin_name, 
			plugin_dir_url( __FILE__ ) . 'js/bible-here-admin.js', 
			array( 'jquery', 'jquery-ui-dialog' ), 
			$this->version, 
			false 
		);

		// Localize script for AJAX
		wp_localize_script( $this->plugin_name, 'bible_here_admin', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'bible_here_admin_nonce' )
		));

	}

	/**
	 * Initialize editor integration
	 *
	 * @since    1.0.0
	 */
	public function init_editor_integration() {
		// Add TinyMCE button
		add_filter( 'mce_buttons', array( $this, 'add_tinymce_button' ) );
		add_filter( 'mce_external_plugins', array( $this, 'add_tinymce_plugin' ) );

		// Register Gutenberg block
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Add TinyMCE button
	 *
	 * @since    1.0.0
	 * @param    array    $buttons    Existing buttons
	 * @return   array    Modified buttons
	 */
	public function add_tinymce_button( $buttons ) {
		array_push( $buttons, 'bible_here_button' );
		return $buttons;
	}

	/**
	 * Add TinyMCE plugin
	 *
	 * @since    1.0.0
	 * @param    array    $plugins    Existing plugins
	 * @return   array    Modified plugins
	 */
	public function add_tinymce_plugin( $plugins ) {
		$plugins['bible_here_button'] = plugin_dir_url( __FILE__ ) . 'js/bible-here-admin.js';
		return $plugins;
	}

	/**
	 * Enqueue block editor assets
	 *
	 * @since    1.0.0
	 */
	public function enqueue_block_editor_assets() {
		// Enqueue block editor script
		wp_enqueue_script(
			$this->plugin_name . '-block-editor',
			plugin_dir_url( __FILE__ ) . 'js/bible-here-admin.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor' ),
			$this->version,
			true
		);
	}

	/**
	 * AJAX handler for shortcode preview
	 *
	 * @since    1.0.0
	 */
	public function ajax_preview_shortcode() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'bible_here_admin_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$shortcode = sanitize_text_field( $_POST['shortcode'] );
		
		if ( empty( $shortcode ) ) {
			wp_send_json_error( 'No shortcode provided' );
		}

		// Process shortcode and return preview
		$preview_content = do_shortcode( $shortcode );
		
		if ( empty( $preview_content ) || $preview_content === $shortcode ) {
			// If shortcode wasn't processed, create a basic preview
			$preview_content = $this->generate_basic_preview( $shortcode );
		}

		wp_send_json_success( $preview_content );
	}

	/**
	 * Generate basic preview for shortcode
	 *
	 * @since    1.0.0
	 * @param    string    $shortcode    The shortcode to preview
	 * @return   string    Preview HTML
	 */
	private function generate_basic_preview( $shortcode ) {
		// Parse shortcode attributes
		preg_match( '/\[bible-here\s+([^\]]+)\]/', $shortcode, $matches );
		
		if ( empty( $matches[1] ) ) {
			return '<div class="bible-shortcode-error">Invalid shortcode format</div>';
		}

		// Parse attributes
		$atts_string = $matches[1];
		$atts = array();
		
		// Extract ref attribute
		if ( preg_match( '/ref=["\']([^"\']*)["\']/i', $atts_string, $ref_match ) ) {
			$atts['ref'] = $ref_match[1];
		}
		
		// Extract version attribute
		if ( preg_match( '/version=["\']([^"\']*)["\']/i', $atts_string, $version_match ) ) {
			$atts['version'] = $version_match[1];
		}
		
		// Extract style attribute
		if ( preg_match( '/style=["\']([^"\']*)["\']/i', $atts_string, $style_match ) ) {
			$atts['style'] = $style_match[1];
		}
		
		// Extract class attribute
		if ( preg_match( '/class=["\']([^"\']*)["\']/i', $atts_string, $class_match ) ) {
			$atts['class'] = $class_match[1];
		}

		if ( empty( $atts['ref'] ) ) {
			return '<div class="bible-shortcode-error">No reference specified</div>';
		}

		// Generate preview HTML
		$version_text = ! empty( $atts['version'] ) ? ' (' . strtoupper( $atts['version'] ) . ')' : '';
		$class_attr = ! empty( $atts['class'] ) ? ' class="bible-reference ' . esc_attr( $atts['class'] ) . '"' : ' class="bible-reference"';
		
		$preview_html = '<div class="bible-shortcode-preview">';
		$preview_html .= '<a href="#"' . $class_attr . ' data-ref="' . esc_attr( $atts['ref'] ) . '">';
		$preview_html .= esc_html( $atts['ref'] ) . $version_text;
		$preview_html .= '</a>';
		$preview_html .= '</div>';

		return $preview_html;
	}

	/**
	 * Add admin menu
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		// Add main menu page
		add_menu_page(
			__( 'Bible Here', 'bible-here' ),
			__( 'Bible Here', 'bible-here' ),
			'manage_options',
			'bible-here',
			array( $this, 'admin_page_overview' ),
			'dashicons-book-alt',
			30
		);

		// Add submenu pages
		add_submenu_page(
			'bible-here',
			__( 'Overview', 'bible-here' ),
			__( 'Overview', 'bible-here' ),
			'manage_options',
			'bible-here',
			array( $this, 'admin_page_overview' )
		);

		add_submenu_page(
			'bible-here',
			__( 'Bible Versions', 'bible-here' ),
			__( 'Bible Versions', 'bible-here' ),
			'manage_options',
			'bible-here-versions',
			array( $this, 'admin_page_versions' )
		);

		add_submenu_page(
			'bible-here',
			__( 'Commentaries', 'bible-here' ),
			__( 'Commentaries', 'bible-here' ),
			'manage_options',
			'bible-here-commentaries',
			array( $this, 'admin_page_commentaries' )
		);

		add_submenu_page(
			'bible-here',
			__( 'Cross References', 'bible-here' ),
			__( 'Cross References', 'bible-here' ),
			'manage_options',
			'bible-here-cross-references',
			array( $this, 'admin_page_cross_references' )
		);

		add_submenu_page(
			'bible-here',
			__( 'Import Data', 'bible-here' ),
			__( 'Import Data', 'bible-here' ),
			'manage_options',
			'bible-here-import',
			array( $this, 'admin_page_import' )
		);

		add_submenu_page(
			'bible-here',
			__( 'Settings', 'bible-here' ),
			__( 'Settings', 'bible-here' ),
			'manage_options',
			'bible-here-settings',
			array( $this, 'admin_page_settings' )
		);

		add_submenu_page(
			'bible-here',
			__( 'System Status', 'bible-here' ),
			__( 'System Status', 'bible-here' ),
			'manage_options',
			'bible-here-status',
			array( $this, 'admin_page_status' )
		);
	}

	/**
	 * Initialize admin settings
	 *
	 * @since    1.0.0
	 */
	public function admin_init() {
		// Register settings
		register_setting( 'bible_here_settings', 'bible_here_options' );

		// Add settings sections and fields
		add_settings_section(
			'bible_here_general_section',
			__( 'General Settings', 'bible-here' ),
			array( $this, 'general_section_callback' ),
			'bible_here_settings'
		);

		add_settings_field(
			'default_version',
			__( 'Default Bible Version', 'bible-here' ),
			array( $this, 'default_version_callback' ),
			'bible_here_settings',
			'bible_here_general_section'
		);

		add_settings_field(
			'auto_detect',
			__( 'Auto-detect Bible References', 'bible-here' ),
			array( $this, 'auto_detect_callback' ),
			'bible_here_settings',
			'bible_here_general_section'
		);

		add_settings_field(
			'popup_style',
			__( 'Popup Style', 'bible-here' ),
			array( $this, 'popup_style_callback' ),
			'bible_here_settings',
			'bible_here_general_section'
		);
	}

	/**
	 * Overview admin page
	 *
	 * @since    1.0.0
	 */
	public function admin_page_overview() {
		include_once plugin_dir_path( __FILE__ ) . 'partials/bible-here-admin-overview.php';
	}

	/**
	 * Bible versions admin page
	 *
	 * @since    1.0.0
	 */
	public function admin_page_versions() {
		include_once plugin_dir_path( __FILE__ ) . 'partials/bible-here-admin-versions.php';
	}

	/**
	 * Commentaries admin page
	 *
	 * @since    1.0.0
	 */
	public function admin_page_commentaries() {
		include_once plugin_dir_path( __FILE__ ) . 'partials/bible-here-admin-commentaries.php';
	}

	/**
	 * Cross references admin page
	 *
	 * @since    1.0.0
	 */
	public function admin_page_cross_references() {
		include_once plugin_dir_path( __FILE__ ) . 'partials/bible-here-admin-cross-references.php';
	}

	/**
	 * Import data admin page
	 *
	 * @since    1.0.0
	 */
	public function admin_page_import() {
		include_once plugin_dir_path( __FILE__ ) . 'partials/bible-here-admin-import.php';
	}

	/**
	 * Settings admin page
	 *
	 * @since    1.0.0
	 */
	public function admin_page_settings() {
		include_once plugin_dir_path( __FILE__ ) . 'partials/bible-here-admin-settings.php';
	}

	/**
	 * System status admin page
	 *
	 * @since    1.0.0
	 */
	public function admin_page_status() {
		include_once plugin_dir_path( __FILE__ ) . 'partials/bible-here-admin-status.php';
	}

	/**
	 * General settings section callback
	 *
	 * @since    1.0.0
	 */
	public function general_section_callback() {
		echo '<p>' . __( 'Configure general settings for Bible Here plugin.', 'bible-here' ) . '</p>';
	}

	/**
	 * Default version field callback
	 *
	 * @since    1.0.0
	 */
	public function default_version_callback() {
		$options = get_option( 'bible_here_options' );
		$value = isset( $options['default_version'] ) ? $options['default_version'] : 'kjv';
		
		echo '<select name="bible_here_options[default_version]">';
		echo '<option value="kjv"' . selected( $value, 'kjv', false ) . '>King James Version (KJV)</option>';
		echo '<option value="niv"' . selected( $value, 'niv', false ) . '>New International Version (NIV)</option>';
		echo '<option value="esv"' . selected( $value, 'esv', false ) . '>English Standard Version (ESV)</option>';
		echo '<option value="nlt"' . selected( $value, 'nlt', false ) . '>New Living Translation (NLT)</option>';
		echo '</select>';
	}

	/**
	 * Auto detect field callback
	 *
	 * @since    1.0.0
	 */
	public function auto_detect_callback() {
		$options = get_option( 'bible_here_options' );
		$value = isset( $options['auto_detect'] ) ? $options['auto_detect'] : '1';
		
		echo '<input type="checkbox" name="bible_here_options[auto_detect]" value="1"' . checked( $value, '1', false ) . ' />';
		echo '<label for="bible_here_options[auto_detect]">' . __( 'Automatically detect and convert Bible references to clickable links', 'bible-here' ) . '</label>';
	}

	/**
	 * Popup style field callback
	 *
	 * @since    1.0.0
	 */
	public function popup_style_callback() {
		$options = get_option( 'bible_here_options' );
		$value = isset( $options['popup_style'] ) ? $options['popup_style'] : 'modern';
		
		echo '<select name="bible_here_options[popup_style]">';
		echo '<option value="modern"' . selected( $value, 'modern', false ) . '>' . __( 'Modern', 'bible-here' ) . '</option>';
		echo '<option value="classic"' . selected( $value, 'classic', false ) . '>' . __( 'Classic', 'bible-here' ) . '</option>';
		echo '<option value="minimal"' . selected( $value, 'minimal', false ) . '>' . __( 'Minimal', 'bible-here' ) . '</option>';
		echo '</select>';
	}

}
