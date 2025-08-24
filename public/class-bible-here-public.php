<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/xjlin0
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/public
 * @author     Jack Lin <xjlin0@gmail.com>
 */
class Bible_Here_Public {

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
	 * The Bible service instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Bible_Here_Bible_Service    $bible_service    The Bible service instance.
	 */
	private $bible_service;

	/**
	 * The commentary display instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Bible_Here_Commentary_Display    $commentary_display    The commentary display instance.
	 */
	private $commentary_display;

	/**
	 * The cross reference display instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Bible_Here_Cross_Reference_Display    $cross_reference_display    The cross reference display instance.
	 */
	private $cross_reference_display;

	/**
	 * The Strong Number display instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Bible_Here_Strong_Number_Display    $strong_number_display    The Strong Number display instance.
	 */
	private $strong_number_display;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->bible_service = new Bible_Here_Bible_Service();

		// Register shortcodes
		add_shortcode('bible-here-reader', array($this, 'bible_reader_shortcode'));
		add_shortcode('bible-here', array($this, 'bible_reference_shortcode'));
		add_shortcode('bible-here-search', array($this, 'bible_search_shortcode'));
		
		// Initialize commentary display
		$this->commentary_display = new Bible_Here_Commentary_Display();
		
		// Initialize cross reference display
		$this->cross_reference_display = new Bible_Here_Cross_Reference_Display();
		
		// Initialize Strong Number display
		$this->strong_number_display = new Bible_Here_Strong_Number_Display();
		
		// Register commentary shortcode
		add_shortcode('bible-commentary', array($this->commentary_display, 'commentary_shortcode'));
		
		// Register cross reference shortcode
		add_shortcode('bible-cross-references', array($this->cross_reference_display, 'cross_reference_shortcode'));
		
		// Register Strong Number shortcodes
		add_shortcode('bible-strong-search', array($this->strong_number_display, 'strong_search_shortcode'));
		add_shortcode('bible-strong-info', array($this->strong_number_display, 'strong_info_shortcode'));

		// Register AJAX handlers
		add_action('wp_ajax_bible_here_get_verses', array($this, 'ajax_get_verses'));
		add_action('wp_ajax_nopriv_bible_here_get_verses', array($this, 'ajax_get_verses'));
		add_action('wp_ajax_bible_here_search', array($this, 'ajax_search_verses'));
		add_action('wp_ajax_nopriv_bible_here_search', array($this, 'ajax_search_verses'));
		add_action('wp_ajax_bible_here_get_versions', array($this, 'ajax_get_versions'));
		add_action('wp_ajax_nopriv_bible_here_get_versions', array($this, 'ajax_get_versions'));
		
		add_action('wp_ajax_bible_here_search_suggestions', array($this, 'ajax_search_suggestions'));
		add_action('wp_ajax_nopriv_bible_here_search_suggestions', array($this, 'ajax_search_suggestions'));
		
		add_action('wp_ajax_bible_here_get_verse_context', array($this, 'ajax_get_verse_context'));
		add_action('wp_ajax_nopriv_bible_here_get_verse_context', array($this, 'ajax_get_verse_context'));
		
		add_action('wp_ajax_bible_here_get_search_history', array($this, 'ajax_get_search_history'));
		add_action('wp_ajax_nopriv_bible_here_get_search_history', array($this, 'ajax_get_search_history'));
		
		add_action('wp_ajax_bible_here_clear_search_history', array($this, 'ajax_clear_search_history'));
		add_action('wp_ajax_nopriv_bible_here_clear_search_history', array($this, 'ajax_clear_search_history'));

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/bible-here-public.css', array(), $this->version, 'all' );
		
		// Enqueue commentary styles
		wp_enqueue_style( $this->plugin_name . '-commentary', plugin_dir_url( __FILE__ ) . 'css/bible-here-commentary.css', array(), $this->version, 'all' );
		
		// Enqueue cross references styles
		wp_enqueue_style(
			'bible-here-cross-references',
			plugin_dir_url(__FILE__) . 'css/bible-here-cross-references.css',
			array(),
			$this->version,
			'all'
		);
		
		// Enqueue Strong Numbers styles
		wp_enqueue_style(
			'bible-here-strong-numbers',
			plugin_dir_url(__FILE__) . 'css/bible-here-strong-numbers.css',
			array(),
			$this->version,
			'all'
		);

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/bible-here-public.js', array( 'jquery' ), $this->version, false );

		// Localize script for AJAX
		wp_localize_script( $this->plugin_name, 'bible_here_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'bible_here_nonce' )
		));

	}

	/**
	 * Bible Reader Shortcode Handler
	 * [bible-here-reader version="KJV" book="Genesis" chapter="1" parallel="" height="500px" show_navigation="true" show_search="true"]
	 */
	public function bible_reader_shortcode($atts) {
		$atts = shortcode_atts(array(
			'version' => 'KJV',
			'book' => 'Genesis',
			'chapter' => '1',
			'parallel' => '',
			'height' => '500px',
			'show_navigation' => 'true',
			'show_search' => 'true'
		), $atts, 'bible-here-reader');

		$reader_id = 'bible-reader-' . uniqid();
		$show_nav = ($atts['show_navigation'] === 'true');
		$show_search = ($atts['show_search'] === 'true');
		$parallel_versions = !empty($atts['parallel']) ? array_map('trim', explode(',', $atts['parallel'])) : array();
		$has_parallel = !empty($parallel_versions);

		ob_start();
		?>
		<div class="bible-here-reader" id="<?php echo esc_attr($reader_id); ?>" 
			 data-version="<?php echo esc_attr($atts['version']); ?>"
			 data-book="<?php echo esc_attr($atts['book']); ?>"
			 data-chapter="<?php echo esc_attr($atts['chapter']); ?>"
			 <?php if ($has_parallel): ?>data-parallel="<?php echo esc_attr($atts['parallel']); ?>"<?php endif; ?>
			 style="min-height: <?php echo esc_attr($atts['height']); ?>">
			
			<?php if ($show_nav): ?>
			<div class="bible-reader-content">
				<div class="bible-navigation">
					<div class="version-selector">
						<h3><?php _e('Version', 'bible-here'); ?></h3>
						<select class="version-select">
							<option value="<?php echo esc_attr($atts['version']); ?>"><?php echo esc_html($atts['version']); ?></option>
						</select>
					</div>

					<!-- Parallel version selector (only show if parallel is enabled) -->
					<?php if ($has_parallel): ?>
					<div class="bible-parallel-selector">
						<h4>Parallel Versions</h4>
						<div class="parallel-versions-list">
							<!-- Parallel versions will be loaded dynamically -->
						</div>
					</div>
					<?php endif; ?>

					<div class="book-selector">
						<h3><?php _e('Book', 'bible-here'); ?></h3>
						<select class="book-select">
							<option value="<?php echo esc_attr($atts['book']); ?>"><?php echo esc_html($atts['book']); ?></option>
						</select>
					</div>

					<div class="chapter-navigation">
						<h3><?php _e('Chapter', 'bible-here'); ?></h3>
						<div class="chapter-nav-buttons">
							<!-- Chapter buttons will be populated by JavaScript -->
						</div>
					</div>

					<?php if ($has_parallel): ?>
					<div class="parallel-selector">
						<h3><?php _e('Parallel Versions', 'bible-here'); ?></h3>
						<?php foreach ($parallel_versions as $index => $version): ?>
						<div class="parallel-version-item">
							<label>
								<input type="checkbox" class="parallel-version-checkbox" 
									   value="<?php echo esc_attr($version); ?>" checked>
								<?php echo esc_html($version); ?>
							</label>
						</div>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>

					<?php if ($show_search): ?>
					<div class="search-box">
						<h3><?php _e('Search', 'bible-here'); ?></h3>
						<input type="text" class="bible-search-input" placeholder="<?php _e('Search verses...', 'bible-here'); ?>">
						<button class="bible-search-button"><?php _e('Search', 'bible-here'); ?></button>
					</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

				<?php if ($has_parallel): ?>
				<!-- Parallel View -->
				<div class="bible-parallel-view">
					<!-- Main Version Column -->
					<div class="bible-parallel-column bible-main-column">
						<div class="bible-content">
							<div class="bible-content-header">
								<h2 class="bible-title"><?php echo esc_html($atts['book'] . ' ' . $atts['chapter']); ?></h2>
								<span class="bible-version-badge"><?php echo esc_html($atts['version']); ?></span>
							</div>
							<div class="bible-verses">
								<div class="bible-loading">
									<div class="loading-spinner"></div>
									<p><?php _e('Loading Bible content...', 'bible-here'); ?></p>
								</div>
							</div>
						</div>
					</div>

					<!-- Parallel Version Columns -->
					<?php foreach ($parallel_versions as $index => $version): ?>
					<div class="bible-parallel-column bible-parallel-<?php echo esc_attr($index); ?>" data-version="<?php echo esc_attr($version); ?>">
						<div class="bible-content">
							<div class="bible-content-header">
								<h2 class="bible-title"><?php echo esc_html($atts['book'] . ' ' . $atts['chapter']); ?></h2>
								<span class="bible-version-badge"><?php echo esc_html($version); ?></span>
							</div>
							<div class="bible-verses">
								<div class="bible-loading">
									<div class="loading-spinner"></div>
									<p><?php _e('Loading...', 'bible-here'); ?></p>
								</div>
							</div>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
				<?php else: ?>
				<!-- Single Version View -->
				<div class="bible-content">
					<div class="bible-content-header">
						<h2 class="bible-title"><?php echo esc_html($atts['book'] . ' ' . $atts['chapter']); ?></h2>
						<span class="bible-version-badge"><?php echo esc_html($atts['version']); ?></span>
					</div>
					<div class="bible-verses">
						<div class="bible-loading">
							<div class="loading-spinner"></div>
							<p><?php _e('Loading Bible content...', 'bible-here'); ?></p>
						</div>
					</div>
				</div>
				<?php endif; ?>

			<?php if ($show_nav): ?>
			</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Bible reference shortcode handler.
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes
	 * @return   string           HTML output
	 */
	public function bible_reference_shortcode($atts) {
		$atts = shortcode_atts(array(
			'ref' => '',
			'version' => 'kjv',
			'format' => 'popup',
			'show_reference' => 'true'
		), $atts);

		if (empty($atts['ref'])) {
			return '[Bible reference not specified]';
		}

		// Parse and get the verse
		$verse_data = $this->bible_service->get_passage($atts['ref'], $atts['version']);
		
		if (!$verse_data) {
			return '[Bible reference not found: ' . esc_html($atts['ref']) . ']';
		}

		$reference_id = 'bible-ref-' . uniqid();
		
		ob_start();
		?>
		<span class="bible-here-reference" 
			  id="<?php echo esc_attr($reference_id); ?>"
			  data-ref="<?php echo esc_attr($atts['ref']); ?>"
			  data-version="<?php echo esc_attr($atts['version']); ?>"
			  data-format="<?php echo esc_attr($atts['format']); ?>">
			<?php if ($atts['show_reference'] === 'true'): ?>
				<a href="#" class="bible-reference-link"><?php echo esc_html($atts['ref']); ?></a>
			<?php else: ?>
				<a href="#" class="bible-reference-link"><?php echo esc_html($verse_data['text']); ?></a>
			<?php endif; ?>
			
			<!-- Popup content (hidden by default) -->
			<div class="bible-popup-content" style="display: none;">
				<div class="bible-popup-header">
					<strong><?php echo esc_html($verse_data['reference']); ?></strong>
					<span class="bible-version">(<?php echo esc_html(strtoupper($atts['version'])); ?>)</span>
				</div>
				<div class="bible-popup-text">
					<?php echo esc_html($verse_data['text']); ?>
				</div>
			</div>
		</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Bible search shortcode
	 */
	public function bible_search_shortcode($atts) {
		$atts = shortcode_atts(array(
			'title' => 'Bible Search',
			'placeholder' => 'Search Bible verses...',
			'versions' => 'KJV,NIV,ESV',
			'books' => 'all',
			'search_modes' => 'natural,boolean,ngram',
			'sort_options' => 'relevance,reference',
			'results_per_page' => '10',
			'show_filters' => 'true',
			'show_history' => 'true',
			'show_suggestions' => 'true',
			'theme' => 'light',
			'height' => 'auto'
		), $atts, 'bible-here-search');

		// Generate unique ID for this search instance
		$search_id = 'bible-search-' . uniqid();
		
		// Parse versions and books
		$versions = array_map('trim', explode(',', $atts['versions']));
		$books = $atts['books'] === 'all' ? 'all' : array_map('trim', explode(',', $atts['books']));
		$search_modes = array_map('trim', explode(',', $atts['search_modes']));
		$sort_options = array_map('trim', explode(',', $atts['sort_options']));
		
		// Get available versions and books from database
		$bible_service = new Bible_Here_Bible_Service();
		$available_versions = $bible_service->get_versions();
		$available_books = $bible_service->get_books();
		
		ob_start();
		?>
		<div id="<?php echo esc_attr($search_id); ?>" class="bible-search-interface" 
			 data-title="<?php echo esc_attr($atts['title']); ?>"
			 data-placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
			 data-versions="<?php echo esc_attr(json_encode($versions)); ?>"
			 data-books="<?php echo esc_attr(json_encode($books)); ?>"
			 data-search-modes="<?php echo esc_attr(json_encode($search_modes)); ?>"
			 data-sort-options="<?php echo esc_attr(json_encode($sort_options)); ?>"
			 data-results-per-page="<?php echo esc_attr($atts['results_per_page']); ?>"
			 data-show-filters="<?php echo esc_attr($atts['show_filters']); ?>"
			 data-show-history="<?php echo esc_attr($atts['show_history']); ?>"
			 data-show-suggestions="<?php echo esc_attr($atts['show_suggestions']); ?>"
			 data-theme="<?php echo esc_attr($atts['theme']); ?>"
			 style="<?php echo $atts['height'] !== 'auto' ? 'height: ' . esc_attr($atts['height']) : ''; ?>">
			
			<!-- Search Header -->
			<div class="bible-search-header">
				<h3 class="search-title"><?php echo esc_html($atts['title']); ?></h3>
				<div class="search-stats"></div>
			</div>
			
			<!-- Search Form -->
			<div class="bible-search-form">
				<div class="search-input-container">
					<input type="text" class="bible-search-input" 
						   placeholder="<?php echo esc_attr($atts['placeholder']); ?>" 
						   autocomplete="off">
					<button type="button" class="bible-search-clear" title="Clear search">✕</button>
					<button type="button" class="bible-search-button">
						<span>🔍</span>
						<span>Search</span>
					</button>
				</div>
				
				<?php if ($atts['show_suggestions'] === 'true'): ?>
				<div class="search-suggestions" style="display: none;"></div>
				<?php endif; ?>
			</div>
			
			<!-- Advanced Search Filters -->
			<?php if ($atts['show_filters'] === 'true'): ?>
			<div class="bible-search-filters">
				<div class="filters-toggle">
					<button type="button" class="toggle-filters-btn">
						<span>Advanced Search Options</span>
						<span class="toggle-icon">▼</span>
					</button>
				</div>
				<div class="filters-content" style="display: none;">
					<div class="filter-row">
						<div class="filter-group">
							<label for="filter-version-<?php echo esc_attr($search_id); ?>">Bible Version</label>
							<select id="filter-version-<?php echo esc_attr($search_id); ?>" class="filter-version">
								<?php foreach ($available_versions as $version): ?>
									<?php if (in_array($version['abbreviation'], $versions)): ?>
										<option value="<?php echo esc_attr($version['abbreviation']); ?>" 
												<?php selected($version['abbreviation'], $versions[0]); ?>>
											<?php echo esc_html($version['name'] . ' (' . $version['abbreviation'] . ')'); ?>
										</option>
									<?php endif; ?>
								<?php endforeach; ?>
							</select>
						</div>
						
						<div class="filter-group">
							<label for="filter-books-<?php echo esc_attr($search_id); ?>">Book Range</label>
							<select id="filter-books-<?php echo esc_attr($search_id); ?>" class="filter-books">
								<option value="">All Books</option>
								<optgroup label="Old Testament">
									<?php foreach ($available_books as $book): ?>
										<?php if ($book['testament'] === 'Old'): ?>
											<option value="<?php echo esc_attr($book['abbreviation']); ?>">
												<?php echo esc_html($book['name']); ?>
											</option>
										<?php endif; ?>
									<?php endforeach; ?>
								</optgroup>
								<optgroup label="New Testament">
									<?php foreach ($available_books as $book): ?>
										<?php if ($book['testament'] === 'New'): ?>
											<option value="<?php echo esc_attr($book['abbreviation']); ?>">
												<?php echo esc_html($book['name']); ?>
											</option>
										<?php endif; ?>
									<?php endforeach; ?>
								</optgroup>
							</select>
						</div>
					</div>
					
					<div class="filter-row">
						<div class="filter-group">
							<label for="filter-search-mode-<?php echo esc_attr($search_id); ?>">Search Mode</label>
							<select id="filter-search-mode-<?php echo esc_attr($search_id); ?>" class="filter-search-mode">
								<?php if (in_array('natural', $search_modes)): ?>
									<option value="natural">Natural Language</option>
								<?php endif; ?>
								<?php if (in_array('boolean', $search_modes)): ?>
									<option value="boolean">Boolean Search</option>
								<?php endif; ?>
								<?php if (in_array('ngram', $search_modes)): ?>
									<option value="ngram">N-gram (Chinese)</option>
								<?php endif; ?>
								<option value="regex">Regular Expression</option>
							</select>
						</div>
						
						<div class="filter-group">
							<label for="filter-sort-<?php echo esc_attr($search_id); ?>">Sort By</label>
							<select id="filter-sort-<?php echo esc_attr($search_id); ?>" class="filter-sort">
								<?php if (in_array('relevance', $sort_options)): ?>
									<option value="relevance">Relevance</option>
								<?php endif; ?>
								<?php if (in_array('reference', $sort_options)): ?>
									<option value="reference">Bible Reference</option>
								<?php endif; ?>
							</select>
						</div>
					</div>
					
					<div class="filter-actions">
						<button type="button" class="apply-filters-btn">Apply Filters</button>
						<button type="button" class="reset-filters-btn">Reset</button>
					</div>
				</div>
			</div>
			<?php endif; ?>
			
			<!-- Search Results -->
			<div class="bible-search-results"></div>
			
			<!-- Search Pagination -->
			<div class="search-pagination" style="display: none;"></div>
			
			<!-- Search History -->
			<?php if ($atts['show_history'] === 'true'): ?>
			<div class="bible-search-history">
				<div class="history-header">
					<h4>Search History</h4>
					<button type="button" class="clear-history-btn">Clear All</button>
				</div>
				<div class="history-list"></div>
			</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX handler for getting verses.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_verses() {
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'bible_here_nonce')) {
			wp_die('Security check failed');
		}

		$version = sanitize_text_field($_POST['version'] ?? 'kjv');
		$book = intval($_POST['book'] ?? 1);
		$chapter = intval($_POST['chapter'] ?? 1);
		$start_verse = isset($_POST['start_verse']) ? intval($_POST['start_verse']) : null;
		$end_verse = isset($_POST['end_verse']) ? intval($_POST['end_verse']) : null;

		try {
			$verses = $this->bible_service->get_verses($version, $book, $chapter, $start_verse, $end_verse);
			$book_info = $this->bible_service->get_book_info($book);
			
			wp_send_json_success(array(
				'verses' => $verses,
				'book_info' => $book_info,
				'version' => strtoupper($version)
			));
		} catch (Exception $e) {
			wp_send_json_error(array(
				'message' => $e->getMessage()
			));
		}
	}

	/**
	 * Handle AJAX search verses request.
	 *
	 * @since    1.0.0
	 */
	public function ajax_search_verses() {
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'bible_here_nonce')) {
			wp_die('Security check failed');
		}

		$search_text = sanitize_text_field($_POST['search_text']);
		$version = sanitize_text_field($_POST['version'] ?? 'kjv');
		$page = intval($_POST['page'] ?? 1);
		$books = isset($_POST['books']) ? array_map('sanitize_text_field', $_POST['books']) : array();
		$search_mode = sanitize_text_field($_POST['search_mode'] ?? 'natural');
		$sort_by = sanitize_text_field($_POST['sort_by'] ?? 'relevance');
		$regex_search = isset($_POST['regex_search']) ? (bool)$_POST['regex_search'] : false;

		$options = array(
			'versions' => array($version),
			'books' => $books,
			'page' => $page,
			'limit' => 20,
			'search_mode' => $search_mode,
			'sort_by' => $sort_by,
			'highlight' => true,
			'include_context' => false,
			'include_book_info' => true,
			'regex_search' => $regex_search
		);

		$results = $this->bible_service->search_verses($search_text, $options);

		// Save to search history if results found
		if (!empty($results['results'])) {
			$this->bible_service->save_search_history($search_text, $options, $results['total']);
		}

		wp_send_json_success($results);
	}

	/**
	 * AJAX handler for getting search suggestions.
	 *
	 * @since    1.0.0
	 */
	public function ajax_search_suggestions() {
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'bible_here_nonce')) {
			wp_die('Security check failed');
		}

		$query = sanitize_text_field($_POST['query'] ?? '');
		$version = sanitize_text_field($_POST['version'] ?? 'kjv');
		$limit = intval($_POST['limit'] ?? 5);

		if (empty($query) || strlen($query) < 2) {
			wp_send_json_success(array());
		}

		try {
			$suggestions = $this->bible_service->get_search_suggestions($query, $version, $limit);
			
			wp_send_json_success($suggestions);
		} catch (Exception $e) {
			wp_send_json_error(array(
				'message' => 'Failed to get suggestions: ' . $e->getMessage()
			));
		}
	}

	/**
	 * Handle AJAX get search history request.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_search_history() {
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'bible_here_nonce')) {
			wp_die('Security check failed');
		}

		$limit = intval($_POST['limit'] ?? 20);
		$history = $this->bible_service->get_search_history($limit);

		wp_send_json_success($history);
	}

	/**
	 * Handle AJAX clear search history request.
	 *
	 * @since    1.0.0
	 */
	public function ajax_clear_search_history() {
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'bible_here_nonce')) {
			wp_die('Security check failed');
		}

		$this->bible_service->clear_search_history();

		wp_send_json_success(array('message' => 'Search history cleared'));
	}

	/**
	 * AJAX handler for getting verse context.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_verse_context() {
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'bible_here_nonce')) {
			wp_die('Security check failed');
		}

		$version = sanitize_text_field($_POST['version'] ?? '');
		$book = sanitize_text_field($_POST['book'] ?? '');
		$chapter = intval($_POST['chapter'] ?? 0);
		$verse = intval($_POST['verse'] ?? 0);
		$context_verses = intval($_POST['context_verses'] ?? 2);

		if (empty($version) || empty($book) || $chapter < 1 || $verse < 1) {
			wp_send_json_error(array('message' => 'Invalid parameters'));
		}

		try {
			$context = $this->bible_service->get_verse_context($version, $book, $chapter, $verse, $context_verses);
			
			wp_send_json_success($context);
		} catch (Exception $e) {
			wp_send_json_error(array(
				'message' => 'Failed to get context: ' . $e->getMessage()
			));
		}
	}

	/**
	 * AJAX handler for getting available versions.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_versions() {
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'bible_here_nonce')) {
			wp_die('Security check failed');
		}

		try {
			$versions = $this->bible_service->get_available_versions(true);
			$books = $this->bible_service->get_books_list();
			
			wp_send_json_success(array(
				'versions' => $versions,
				'books' => $books
			));
		} catch (Exception $e) {
			wp_send_json_error(array(
				'message' => $e->getMessage()
			));
		}
	}

}
