<?php

/**
 * The commentary display functionality of the plugin.
 *
 * @link       https://github.com/jacklinquan/bible-here
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/public
 */

/**
 * The commentary display functionality of the plugin.
 *
 * Defines the plugin name, version, and handles the display of commentaries
 * in the public-facing side of the site.
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/public
 * @author     Jack Lin <jacklinquan@gmail.com>
 */
class Bible_Here_Commentary_Display {

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
	 * The commentary manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Bible_Here_Commentary_Manager    $commentary_manager    The commentary manager.
	 */
	private $commentary_manager;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->commentary_manager = new Bible_Here_Commentary_Manager();

	}

	/**
	 * Display commentaries for a specific verse.
	 *
	 * @since    1.0.0
	 * @param    string    $verse_id    The verse ID (e.g., "Gen.1.1")
	 * @param    array     $options     Display options
	 * @return   string               HTML output for commentaries
	 */
	public function display_verse_commentaries($verse_id, $options = array()) {
		$defaults = array(
			'show_author' => true,
			'show_source' => true,
			'show_type' => false,
			'limit' => 5,
			'language' => 'en',
			'commentary_type' => 'all',
			'css_class' => 'bible-here-commentaries'
		);
		
		$options = array_merge($defaults, $options);
		
		// Get commentaries from manager
		$commentaries = $this->commentary_manager->get_verse_commentaries($verse_id, array(
			'limit' => $options['limit'],
			'language' => $options['language'],
			'commentary_type' => $options['commentary_type'],
			'status' => 'active'
		));
		
		if (empty($commentaries)) {
			return '';
		}
		
		$output = '<div class="' . esc_attr($options['css_class']) . '">';
		$output .= '<h4 class="commentary-title">' . __('Commentaries', 'bible-here') . '</h4>';
		
		foreach ($commentaries as $commentary) {
			$output .= $this->render_single_commentary($commentary, $options);
		}
		
		$output .= '</div>';
		
		return $output;
	}

	/**
	 * Display commentaries for a specific chapter.
	 *
	 * @since    1.0.0
	 * @param    int       $book_number     Book number
	 * @param    int       $chapter_number  Chapter number
	 * @param    array     $options         Display options
	 * @return   string                     HTML output for commentaries
	 */
	public function display_chapter_commentaries($book_number, $chapter_number, $options = array()) {
		$defaults = array(
			'show_author' => true,
			'show_source' => true,
			'show_verse' => true,
			'group_by_verse' => true,
			'limit' => 20,
			'language' => 'en',
			'commentary_type' => 'all',
			'css_class' => 'bible-here-chapter-commentaries'
		);
		
		$options = array_merge($defaults, $options);
		
		// Get commentaries from manager
		$commentaries = $this->commentary_manager->get_chapter_commentaries($book_number, $chapter_number, array(
			'limit' => $options['limit'],
			'language' => $options['language'],
			'commentary_type' => $options['commentary_type'],
			'status' => 'active'
		));
		
		if (empty($commentaries)) {
			return '';
		}
		
		$output = '<div class="' . esc_attr($options['css_class']) . '">';
		$output .= '<h3 class="chapter-commentary-title">' . __('Chapter Commentaries', 'bible-here') . '</h3>';
		
		if ($options['group_by_verse']) {
			$grouped_commentaries = $this->group_commentaries_by_verse($commentaries);
			foreach ($grouped_commentaries as $verse_number => $verse_commentaries) {
				$output .= '<div class="verse-commentaries-group">';
				$output .= '<h5 class="verse-number">' . sprintf(__('Verse %d', 'bible-here'), $verse_number) . '</h5>';
				foreach ($verse_commentaries as $commentary) {
					$output .= $this->render_single_commentary($commentary, $options);
				}
				$output .= '</div>';
			}
		} else {
			foreach ($commentaries as $commentary) {
				$output .= $this->render_single_commentary($commentary, $options);
			}
		}
		
		$output .= '</div>';
		
		return $output;
	}

	/**
	 * Render a single commentary.
	 *
	 * @since    1.0.0
	 * @param    object    $commentary    Commentary object
	 * @param    array     $options       Display options
	 * @return   string                   HTML output for single commentary
	 */
	private function render_single_commentary($commentary, $options) {
		$output = '<div class="single-commentary" data-commentary-id="' . esc_attr($commentary->id) . '">';
		
		// Commentary header
		if ($options['show_author'] || $options['show_source'] || $options['show_type']) {
			$output .= '<div class="commentary-header">';
			
			if ($options['show_verse'] && isset($commentary->verse_number)) {
				$output .= '<span class="commentary-verse">' . sprintf(__('v.%d', 'bible-here'), $commentary->verse_number) . '</span>';
			}
			
			if ($options['show_author'] && !empty($commentary->author_name)) {
				$output .= '<span class="commentary-author">' . esc_html($commentary->author_name) . '</span>';
			}
			
			if ($options['show_source'] && !empty($commentary->source_name)) {
				$output .= '<span class="commentary-source">' . esc_html($commentary->source_name) . '</span>';
			}
			
			if ($options['show_type'] && !empty($commentary->commentary_type)) {
				$output .= '<span class="commentary-type">' . esc_html(ucfirst($commentary->commentary_type)) . '</span>';
			}
			
			$output .= '</div>';
		}
		
		// Commentary content
		$output .= '<div class="commentary-content">';
		$output .= wp_kses_post($commentary->commentary);
		$output .= '</div>';
		
		$output .= '</div>';
		
		return $output;
	}

	/**
	 * Group commentaries by verse number.
	 *
	 * @since    1.0.0
	 * @param    array     $commentaries    Array of commentary objects
	 * @return   array                      Grouped commentaries
	 */
	private function group_commentaries_by_verse($commentaries) {
		$grouped = array();
		
		foreach ($commentaries as $commentary) {
			$verse_number = $commentary->verse_number;
			if (!isset($grouped[$verse_number])) {
				$grouped[$verse_number] = array();
			}
			$grouped[$verse_number][] = $commentary;
		}
		
		// Sort by verse number
		ksort($grouped);
		
		return $grouped;
	}

	/**
	 * Get commentary popup content for AJAX requests.
	 *
	 * @since    1.0.0
	 * @param    string    $verse_id    The verse ID
	 * @param    array     $options     Display options
	 * @return   array                  Response data
	 */
	public function get_commentary_popup_content($verse_id, $options = array()) {
		$defaults = array(
			'show_author' => true,
			'show_source' => true,
			'limit' => 3,
			'language' => 'en'
		);
		
		$options = array_merge($defaults, $options);
		
		$commentaries = $this->commentary_manager->get_verse_commentaries($verse_id, array(
			'limit' => $options['limit'],
			'language' => $options['language'],
			'status' => 'active'
		));
		
		if (empty($commentaries)) {
			return array(
				'success' => false,
				'message' => __('No commentaries found for this verse.', 'bible-here')
			);
		}
		
		$content = '';
		foreach ($commentaries as $commentary) {
			$content .= $this->render_single_commentary($commentary, $options);
		}
		
		return array(
			'success' => true,
			'content' => $content,
			'count' => count($commentaries)
		);
	}

	/**
	 * Register shortcode for displaying commentaries.
	 *
	 * @since    1.0.0
	 */
	public function register_commentary_shortcode() {
		add_shortcode('bible-commentary', array($this, 'commentary_shortcode'));
	}

	/**
	 * Handle the [bible-commentary] shortcode.
	 *
	 * @since    1.0.0
	 * @param    array     $atts    Shortcode attributes
	 * @return   string             HTML output
	 */
	public function commentary_shortcode($atts) {
		$atts = shortcode_atts(array(
			'ref' => '',
			'book' => '',
			'chapter' => '',
			'verse' => '',
			'limit' => 5,
			'show_author' => 'true',
			'show_source' => 'true',
			'show_type' => 'false',
			'language' => 'en',
			'type' => 'all'
		), $atts);
		
		// Convert string booleans to actual booleans
		$options = array(
			'show_author' => $atts['show_author'] === 'true',
			'show_source' => $atts['show_source'] === 'true',
			'show_type' => $atts['show_type'] === 'true',
			'limit' => intval($atts['limit']),
			'language' => $atts['language'],
			'commentary_type' => $atts['type']
		);
		
		// Handle different reference formats
		if (!empty($atts['ref'])) {
			// Parse reference like "Gen.1.1" or "Genesis 1:1"
			$verse_id = $this->parse_verse_reference($atts['ref']);
			if ($verse_id) {
				return $this->display_verse_commentaries($verse_id, $options);
			}
		} elseif (!empty($atts['book']) && !empty($atts['chapter'])) {
			$book_number = $this->get_book_number($atts['book']);
			if ($book_number) {
				if (!empty($atts['verse'])) {
					// Display verse commentary
					$verse_id = $atts['book'] . '.' . $atts['chapter'] . '.' . $atts['verse'];
					return $this->display_verse_commentaries($verse_id, $options);
				} else {
					// Display chapter commentaries
					return $this->display_chapter_commentaries($book_number, intval($atts['chapter']), $options);
				}
			}
		}
		
		return '<p class="bible-here-error">' . __('Invalid commentary reference.', 'bible-here') . '</p>';
	}

	/**
	 * Parse verse reference string.
	 *
	 * @since    1.0.0
	 * @param    string    $reference    Reference string
	 * @return   string|false           Parsed verse ID or false
	 */
	private function parse_verse_reference($reference) {
		// This is a simplified parser - you might want to use the existing
		// reference parsing logic from the main plugin
		$reference = trim($reference);
		
		// Handle formats like "Gen.1.1"
		if (preg_match('/^([A-Za-z]+)\.?(\d+)\.(\d+)$/', $reference, $matches)) {
			return $matches[1] . '.' . $matches[2] . '.' . $matches[3];
		}
		
		// Handle formats like "Genesis 1:1"
		if (preg_match('/^([A-Za-z\s]+)\s+(\d+):(\d+)$/', $reference, $matches)) {
			$book_abbr = $this->get_book_abbreviation(trim($matches[1]));
			if ($book_abbr) {
				return $book_abbr . '.' . $matches[2] . '.' . $matches[3];
			}
		}
		
		return false;
	}

	/**
	 * Get book number from book name or abbreviation.
	 *
	 * @since    1.0.0
	 * @param    string    $book    Book name or abbreviation
	 * @return   int|false         Book number or false
	 */
	private function get_book_number($book) {
		// This should use the existing book lookup logic
		// For now, return a placeholder
		return 1; // Genesis
	}

	/**
	 * Get book abbreviation from book name.
	 *
	 * @since    1.0.0
	 * @param    string    $book_name    Book name
	 * @return   string|false           Book abbreviation or false
	 */
	private function get_book_abbreviation($book_name) {
		// This should use the existing abbreviation lookup logic
		// For now, return a placeholder
		return 'Gen'; // Genesis
	}

}