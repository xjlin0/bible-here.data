<?php

/**
 * Strong Number Display Class
 *
 * @link       https://github.com/xjlin0
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 */

/**
 * Strong Number Display Class.
 *
 * This class defines all code necessary to display Strong Numbers on the frontend.
 *
 * @since      1.0.0
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 * @author     Jack Lin <xjlin0@gmail.com>
 */
class Bible_Here_Strong_Number_Display {

	/**
	 * Strong Number Manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Bible_Here_Strong_Number_Manager    $manager    Strong Number Manager instance.
	 */
	private $manager;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->manager = new Bible_Here_Strong_Number_Manager();
	}

	/**
	 * Display Strong Numbers for a verse.
	 *
	 * @since    1.0.0
	 * @param    string    $version         Bible version
	 * @param    int       $book_number     Book number
	 * @param    int       $chapter_number  Chapter number
	 * @param    int       $verse_number    Verse number
	 * @param    array     $options         Display options
	 * @return   string    HTML output
	 */
	public function display_verse_strong_numbers($version, $book_number, $chapter_number, $verse_number, $options = array()) {
		$defaults = array(
			'show_numbers' => true,
			'show_original' => true,
			'show_transliteration' => true,
			'show_definitions' => true,
			'clickable' => true,
			'group_by_word' => true,
			'container_class' => 'bible-strong-numbers'
		);

		$options = wp_parse_args($options, $defaults);

		$strong_numbers = $this->manager->get_verse_strong_numbers($version, $book_number, $chapter_number, $verse_number);

		if (empty($strong_numbers)) {
			return '<div class="bible-strong-numbers-empty">' . __('No Strong Numbers available for this verse.', 'bible-here') . '</div>';
		}

		$output = '<div class="' . esc_attr($options['container_class']) . '" data-verse="' . esc_attr("$book_number:$chapter_number:$verse_number") . '">';

		if ($options['group_by_word']) {
			$output .= $this->render_grouped_strong_numbers($strong_numbers, $options);
		} else {
			$output .= $this->render_linear_strong_numbers($strong_numbers, $options);
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render grouped Strong Numbers (by word).
	 *
	 * @since    1.0.0
	 * @param    array    $strong_numbers    Strong Numbers data
	 * @param    array    $options          Display options
	 * @return   string   HTML output
	 */
	private function render_grouped_strong_numbers($strong_numbers, $options) {
		$output = '<div class="bible-strong-words">';

		$current_word = '';
		$word_strongs = array();

		foreach ($strong_numbers as $strong_data) {
			if ($current_word !== $strong_data->word_text) {
				if (!empty($word_strongs)) {
					$output .= $this->render_word_with_strongs($current_word, $word_strongs, $options);
				}
				$current_word = $strong_data->word_text;
				$word_strongs = array();
			}
			$word_strongs[] = $strong_data;
		}

		// Render the last word
		if (!empty($word_strongs)) {
			$output .= $this->render_word_with_strongs($current_word, $word_strongs, $options);
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render a word with its Strong Numbers.
	 *
	 * @since    1.0.0
	 * @param    string    $word_text       The word text
	 * @param    array     $word_strongs    Strong Numbers for this word
	 * @param    array     $options         Display options
	 * @return   string    HTML output
	 */
	private function render_word_with_strongs($word_text, $word_strongs, $options) {
		$output = '<span class="bible-strong-word">';
		$output .= '<span class="bible-word-text">' . esc_html($word_text) . '</span>';

		if ($options['show_numbers']) {
			$output .= '<span class="bible-strong-numbers-list">';
			foreach ($word_strongs as $strong_data) {
				$output .= $this->render_single_strong_number($strong_data, $options);
			}
			$output .= '</span>';
		}

		$output .= '</span> ';

		return $output;
	}

	/**
	 * Render linear Strong Numbers (not grouped by word).
	 *
	 * @since    1.0.0
	 * @param    array    $strong_numbers    Strong Numbers data
	 * @param    array    $options          Display options
	 * @return   string   HTML output
	 */
	private function render_linear_strong_numbers($strong_numbers, $options) {
		$output = '<div class="bible-strong-list">';

		foreach ($strong_numbers as $strong_data) {
			$output .= '<div class="bible-strong-item">';
			$output .= '<span class="bible-word-text">' . esc_html($strong_data->word_text) . '</span>';
			$output .= $this->render_single_strong_number($strong_data, $options);
			$output .= '</div>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render a single Strong Number.
	 *
	 * @since    1.0.0
	 * @param    object    $strong_data    Strong Number data
	 * @param    array     $options       Display options
	 * @return   string    HTML output
	 */
	private function render_single_strong_number($strong_data, $options) {
		$strong_info = $this->manager->get_strong_number($strong_data->strong_number);
		$definitions = $this->manager->get_strong_definitions($strong_data->strong_number, 'brief');

		$classes = array('bible-strong-number');
		if ($strong_info) {
			$classes[] = 'bible-strong-' . $strong_info->language;
		}
		if ($options['clickable']) {
			$classes[] = 'bible-strong-clickable';
		}

		$output = '<span class="' . implode(' ', $classes) . '" data-strong="' . esc_attr($strong_data->strong_number) . '"';

		if ($options['clickable']) {
			$popup_content = $this->get_strong_number_popup_content($strong_data->strong_number);
			$output .= ' data-popup="' . esc_attr($popup_content) . '"';
		}

		$output .= '>';

		// Strong Number
		if ($options['show_numbers']) {
			$output .= '<span class="bible-strong-num">' . esc_html($strong_data->strong_number) . '</span>';
		}

		// Original word
		if ($options['show_original'] && $strong_info && !empty($strong_info->original_word)) {
			$output .= '<span class="bible-strong-original">' . esc_html($strong_info->original_word) . '</span>';
		}

		// Transliteration
		if ($options['show_transliteration'] && $strong_info && !empty($strong_info->transliteration)) {
			$output .= '<span class="bible-strong-transliteration">' . esc_html($strong_info->transliteration) . '</span>';
		}

		// Brief definition
		if ($options['show_definitions'] && !empty($definitions)) {
			$brief_def = wp_trim_words($definitions[0]->definition, 5, '...');
			$output .= '<span class="bible-strong-definition">' . esc_html($brief_def) . '</span>';
		}

		$output .= '</span>';

		return $output;
	}

	/**
	 * Get Strong Number popup content.
	 *
	 * @since    1.0.0
	 * @param    string    $strong_number    The Strong Number
	 * @return   string    Popup content HTML
	 */
	public function get_strong_number_popup_content($strong_number) {
		$strong_info = $this->manager->get_strong_number($strong_number);
		$definitions = $this->manager->get_strong_definitions($strong_number, 'all');
		$usage_stats = $this->manager->get_strong_number_usage($strong_number);

		if (!$strong_info) {
			return '<div class="bible-strong-popup-error">' . __('Strong Number not found.', 'bible-here') . '</div>';
		}

		$output = '<div class="bible-strong-popup">';

		// Header
		$output .= '<div class="bible-strong-popup-header">';
		$output .= '<h3>' . esc_html($strong_number) . ' - ' . esc_html($strong_info->original_word) . '</h3>';
		if (!empty($strong_info->transliteration)) {
			$output .= '<p class="bible-strong-transliteration">' . esc_html($strong_info->transliteration) . '</p>';
		}
		if (!empty($strong_info->pronunciation)) {
			$output .= '<p class="bible-strong-pronunciation">[' . esc_html($strong_info->pronunciation) . ']</p>';
		}
		$output .= '</div>';

		// Basic info
		$output .= '<div class="bible-strong-popup-info">';
		if (!empty($strong_info->part_of_speech)) {
			$output .= '<p><strong>' . __('Part of Speech:', 'bible-here') . '</strong> ' . esc_html($strong_info->part_of_speech) . '</p>';
		}
		if (!empty($strong_info->root_word)) {
			$output .= '<p><strong>' . __('Root Word:', 'bible-here') . '</strong> ' . esc_html($strong_info->root_word) . '</p>';
		}
		$output .= '<p><strong>' . __('Language:', 'bible-here') . '</strong> ' . ucfirst($strong_info->language) . '</p>';
		$output .= '</div>';

		// Definitions
		if (!empty($definitions)) {
			$output .= '<div class="bible-strong-popup-definitions">';
			$output .= '<h4>' . __('Definitions:', 'bible-here') . '</h4>';
			foreach ($definitions as $definition) {
				$output .= '<div class="bible-strong-definition-item">';
				$output .= '<h5>' . ucfirst($definition->definition_type) . ':</h5>';
				$output .= '<p>' . esc_html($definition->definition) . '</p>';
				if (!empty($definition->usage_notes)) {
					$output .= '<p class="bible-strong-usage-notes"><em>' . esc_html($definition->usage_notes) . '</em></p>';
				}
				$output .= '</div>';
			}
			$output .= '</div>';
		}

		// Usage statistics
		if ($usage_stats && $usage_stats->total_occurrences > 0) {
			$output .= '<div class="bible-strong-popup-stats">';
			$output .= '<h4>' . __('Usage Statistics:', 'bible-here') . '</h4>';
			$output .= '<ul>';
			$output .= '<li>' . sprintf(__('Total occurrences: %d', 'bible-here'), $usage_stats->total_occurrences) . '</li>';
			$output .= '<li>' . sprintf(__('Unique verses: %d', 'bible-here'), $usage_stats->unique_verses) . '</li>';
			$output .= '<li>' . sprintf(__('Books: %d', 'bible-here'), $usage_stats->books_count) . '</li>';
			$output .= '</ul>';
			$output .= '</div>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Strong Number search shortcode.
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes
	 * @return   string   HTML output
	 */
	public function strong_search_shortcode($atts) {
		$atts = shortcode_atts(array(
			'term' => '',
			'language' => 'all',
			'limit' => 20,
			'show_form' => 'true',
			'container_class' => 'bible-strong-search'
		), $atts, 'bible-strong-search');

		$output = '<div class="' . esc_attr($atts['container_class']) . '">';

		// Search form
		if ($atts['show_form'] === 'true') {
			$output .= $this->render_search_form($atts);
		}

		// Search results
		if (!empty($atts['term'])) {
			$results = $this->manager->search_strong_numbers($atts['term'], $atts['language'], intval($atts['limit']));
			$output .= $this->render_search_results($results, $atts);
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render Strong Number search form.
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes
	 * @return   string   HTML output
	 */
	private function render_search_form($atts) {
		$output = '<form class="bible-strong-search-form" method="get">';
		$output .= '<div class="bible-strong-search-fields">';
		
		$output .= '<input type="text" name="strong_term" placeholder="' . esc_attr__('Search Strong Numbers...', 'bible-here') . '" value="' . esc_attr($atts['term']) . '" class="bible-strong-search-input" />';
		
		$output .= '<select name="strong_language" class="bible-strong-search-language">';
		$output .= '<option value="all"' . selected($atts['language'], 'all', false) . '>' . __('All Languages', 'bible-here') . '</option>';
		$output .= '<option value="hebrew"' . selected($atts['language'], 'hebrew', false) . '>' . __('Hebrew', 'bible-here') . '</option>';
		$output .= '<option value="greek"' . selected($atts['language'], 'greek', false) . '>' . __('Greek', 'bible-here') . '</option>';
		$output .= '</select>';
		
		$output .= '<button type="submit" class="bible-strong-search-submit">' . __('Search', 'bible-here') . '</button>';
		$output .= '</div>';
		$output .= '</form>';

		return $output;
	}

	/**
	 * Render Strong Number search results.
	 *
	 * @since    1.0.0
	 * @param    array    $results    Search results
	 * @param    array    $atts       Shortcode attributes
	 * @return   string   HTML output
	 */
	private function render_search_results($results, $atts) {
		if (empty($results)) {
			return '<div class="bible-strong-search-no-results">' . __('No Strong Numbers found.', 'bible-here') . '</div>';
		}

		$output = '<div class="bible-strong-search-results">';
		$output .= '<h3>' . sprintf(__('Found %d Strong Numbers:', 'bible-here'), count($results)) . '</h3>';
		$output .= '<div class="bible-strong-results-list">';

		foreach ($results as $result) {
			$output .= '<div class="bible-strong-result-item bible-strong-' . esc_attr($result->language) . '">';
			$output .= '<div class="bible-strong-result-header">';
			$output .= '<span class="bible-strong-number">' . esc_html($result->strong_number) . '</span>';
			$output .= '<span class="bible-strong-original">' . esc_html($result->original_word) . '</span>';
			if (!empty($result->transliteration)) {
				$output .= '<span class="bible-strong-transliteration">(' . esc_html($result->transliteration) . ')</span>';
			}
			$output .= '</div>';
			if (!empty($result->brief_definition)) {
				$output .= '<div class="bible-strong-result-definition">' . esc_html($result->brief_definition) . '</div>';
			}
			$output .= '<div class="bible-strong-result-meta">';
			$output .= '<span class="bible-strong-language">' . ucfirst($result->language) . '</span>';
			if (!empty($result->part_of_speech)) {
				$output .= '<span class="bible-strong-pos">' . esc_html($result->part_of_speech) . '</span>';
			}
			$output .= '</div>';
			$output .= '</div>';
		}

		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Strong Number info shortcode.
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes
	 * @return   string   HTML output
	 */
	public function strong_info_shortcode($atts) {
		$atts = shortcode_atts(array(
			'number' => '',
			'show_original' => 'true',
			'show_transliteration' => 'true',
			'show_definitions' => 'true',
			'show_usage' => 'false',
			'container_class' => 'bible-strong-info'
		), $atts, 'bible-strong-info');

		if (empty($atts['number'])) {
			return '<div class="bible-strong-error">' . __('Strong Number is required.', 'bible-here') . '</div>';
		}

		$strong_info = $this->manager->get_strong_number($atts['number']);
		if (!$strong_info) {
			return '<div class="bible-strong-error">' . __('Strong Number not found.', 'bible-here') . '</div>';
		}

		$output = '<div class="' . esc_attr($atts['container_class']) . ' bible-strong-' . esc_attr($strong_info->language) . '">';
		$output .= '<div class="bible-strong-header">';
		$output .= '<h3>' . esc_html($atts['number']);
		if ($atts['show_original'] === 'true') {
			$output .= ' - ' . esc_html($strong_info->original_word);
		}
		$output .= '</h3>';
		if ($atts['show_transliteration'] === 'true' && !empty($strong_info->transliteration)) {
			$output .= '<p class="bible-strong-transliteration">' . esc_html($strong_info->transliteration) . '</p>';
		}
		$output .= '</div>';

		if ($atts['show_definitions'] === 'true') {
			$definitions = $this->manager->get_strong_definitions($atts['number'], 'all');
			if (!empty($definitions)) {
				$output .= '<div class="bible-strong-definitions">';
				foreach ($definitions as $definition) {
					$output .= '<div class="bible-strong-definition-' . esc_attr($definition->definition_type) . '">';
					$output .= '<h4>' . ucfirst($definition->definition_type) . ':</h4>';
					$output .= '<p>' . esc_html($definition->definition) . '</p>';
					$output .= '</div>';
				}
				$output .= '</div>';
			}
		}

		if ($atts['show_usage'] === 'true') {
			$usage_stats = $this->manager->get_strong_number_usage($atts['number']);
			if ($usage_stats && $usage_stats->total_occurrences > 0) {
				$output .= '<div class="bible-strong-usage">';
				$output .= '<h4>' . __('Usage Statistics:', 'bible-here') . '</h4>';
				$output .= '<p>' . sprintf(__('Used %d times in %d verses across %d books.', 'bible-here'), 
					$usage_stats->total_occurrences, 
					$usage_stats->unique_verses, 
					$usage_stats->books_count
				) . '</p>';
				$output .= '</div>';
			}
		}

		$output .= '</div>';

		return $output;
	}

}