<?php

/**
 * The cross reference display functionality of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/public
 */

/**
 * The cross reference display functionality of the plugin.
 *
 * Defines the plugin name, version, and functionality for displaying
 * cross references on the public-facing side of the site.
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/public
 * @author     Your Name <email@example.com>
 */
class Bible_Here_Cross_Reference_Display {

	/**
	 * The cross reference manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Bible_Here_Cross_Reference_Manager    $cross_reference_manager
	 */
	private $cross_reference_manager;

	/**
	 * The database instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Bible_Here_Database    $database
	 */
	private $database;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->cross_reference_manager = new Bible_Here_Cross_Reference_Manager();
		$this->database = new Bible_Here_Database();
	}

	/**
	 * Display cross references for a specific verse.
	 *
	 * @since    1.0.0
	 * @param    string    $verse_id    The verse ID
	 * @param    array     $options     Display options
	 * @return   string    HTML output
	 */
	public function display_verse_cross_references($verse_id, $options = array()) {
		$defaults = array(
			'show_type' => true,
			'show_strength' => false,
			'show_notes' => true,
			'show_source' => false,
			'limit' => 10,
			'reference_type' => '',
			'min_strength' => 3,
			'group_by_type' => false,
			'show_verse_text' => true
		);

		$options = wp_parse_args($options, $defaults);

		$cross_references = $this->cross_reference_manager->get_verse_cross_references($verse_id, array(
			'reference_type' => $options['reference_type'],
			'min_strength' => $options['min_strength'],
			'limit' => $options['limit']
		));

		if (empty($cross_references)) {
			return '<div class="bible-cross-references-empty">' . __('No cross references found.', 'bible-here') . '</div>';
		}

		ob_start();
		?>
		<div class="bible-cross-references" data-verse-id="<?php echo esc_attr($verse_id); ?>">
			<div class="cross-references-header">
				<h4 class="cross-references-title">
					<span class="cross-references-icon">⚡</span>
					<?php _e('Cross References', 'bible-here'); ?>
					<span class="cross-references-count">(<?php echo count($cross_references); ?>)</span>
				</h4>
			</div>

			<div class="cross-references-list">
				<?php
				if ($options['group_by_type']) {
					$this->render_grouped_cross_references($cross_references, $options);
				} else {
					$this->render_cross_references_list($cross_references, $options);
				}
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Display cross references for a chapter.
	 *
	 * @since    1.0.0
	 * @param    int       $book_number      The book number
	 * @param    int       $chapter_number   The chapter number
	 * @param    array     $options          Display options
	 * @return   string    HTML output
	 */
	public function display_chapter_cross_references($book_number, $chapter_number, $options = array()) {
		$defaults = array(
			'show_verse_numbers' => true,
			'show_type' => true,
			'show_notes' => false,
			'limit_per_verse' => 5,
			'min_strength' => 3
		);

		$options = wp_parse_args($options, $defaults);

		$cross_references = $this->cross_reference_manager->get_chapter_cross_references(
			$book_number, 
			$chapter_number, 
			array(
				'min_strength' => $options['min_strength'],
				'limit_per_verse' => $options['limit_per_verse']
			)
		);

		if (empty($cross_references)) {
			return '<div class="bible-chapter-cross-references-empty">' . __('No cross references found for this chapter.', 'bible-here') . '</div>';
		}

		ob_start();
		?>
		<div class="bible-chapter-cross-references" data-book="<?php echo esc_attr($book_number); ?>" data-chapter="<?php echo esc_attr($chapter_number); ?>">
			<div class="chapter-cross-references-header">
				<h3 class="chapter-cross-references-title">
					<?php _e('Chapter Cross References', 'bible-here'); ?>
				</h3>
			</div>

			<div class="chapter-cross-references-content">
				<?php foreach ($cross_references as $verse_number => $verse_refs): ?>
					<div class="verse-cross-references" data-verse="<?php echo esc_attr($verse_number); ?>">
						<?php if ($options['show_verse_numbers']): ?>
							<div class="verse-cross-references-header">
								<span class="verse-number"><?php echo esc_html($verse_number); ?></span>
							</div>
						<?php endif; ?>

						<div class="verse-cross-references-list">
							<?php $this->render_cross_references_list($verse_refs, $options); ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a list of cross references.
	 *
	 * @since    1.0.0
	 * @param    array    $cross_references    Array of cross references
	 * @param    array    $options             Display options
	 */
	private function render_cross_references_list($cross_references, $options) {
		foreach ($cross_references as $reference) {
			$this->render_single_cross_reference($reference, $options);
		}
	}

	/**
	 * Render cross references grouped by type.
	 *
	 * @since    1.0.0
	 * @param    array    $cross_references    Array of cross references
	 * @param    array    $options             Display options
	 */
	private function render_grouped_cross_references($cross_references, $options) {
		$grouped = array();
		foreach ($cross_references as $reference) {
			$type = $reference['reference_type'];
			if (!isset($grouped[$type])) {
				$grouped[$type] = array();
			}
			$grouped[$type][] = $reference;
		}

		$reference_types = $this->cross_reference_manager->get_reference_types();

		foreach ($grouped as $type => $references) {
			$type_label = isset($reference_types[$type]) ? $reference_types[$type] : ucfirst($type);
			?>
			<div class="cross-references-group" data-type="<?php echo esc_attr($type); ?>">
				<div class="cross-references-group-header">
					<h5 class="cross-references-group-title"><?php echo esc_html($type_label); ?></h5>
					<span class="cross-references-group-count">(<?php echo count($references); ?>)</span>
				</div>
				<div class="cross-references-group-list">
					<?php $this->render_cross_references_list($references, $options); ?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Render a single cross reference.
	 *
	 * @since    1.0.0
	 * @param    array    $reference    Cross reference data
	 * @param    array    $options      Display options
	 */
	private function render_single_cross_reference($reference, $options) {
		$reference_text = $this->format_reference_text($reference);
		$reference_types = $this->cross_reference_manager->get_reference_types();
		$type_label = isset($reference_types[$reference['reference_type']]) ? 
			$reference_types[$reference['reference_type']] : ucfirst($reference['reference_type']);

		?>
		<div class="cross-reference-item" data-id="<?php echo esc_attr($reference['id']); ?>" data-type="<?php echo esc_attr($reference['reference_type']); ?>">
			<div class="cross-reference-header">
				<span class="cross-reference-text">
					<a href="#" class="cross-reference-link" data-verse-id="<?php echo esc_attr($reference['ref_verse_id']); ?>">
						<?php echo esc_html($reference_text); ?>
					</a>
				</span>

				<?php if ($options['show_type']): ?>
					<span class="cross-reference-type" title="<?php echo esc_attr($type_label); ?>">
						<?php echo esc_html($type_label); ?>
					</span>
				<?php endif; ?>

				<?php if ($options['show_strength']): ?>
					<span class="cross-reference-strength" title="<?php _e('Reference Strength', 'bible-here'); ?>">
						<?php echo str_repeat('★', intval($reference['strength'])); ?>
					</span>
				<?php endif; ?>

				<?php if ($options['show_source'] && !empty($reference['source'])): ?>
					<span class="cross-reference-source">
						<?php echo esc_html($reference['source']); ?>
					</span>
				<?php endif; ?>
			</div>

			<?php if ($options['show_notes'] && !empty($reference['notes'])): ?>
				<div class="cross-reference-notes">
					<?php echo wp_kses_post(wpautop($reference['notes'])); ?>
				</div>
			<?php endif; ?>

			<?php if ($options['show_verse_text']): ?>
				<div class="cross-reference-verse-preview" data-verse-id="<?php echo esc_attr($reference['ref_verse_id']); ?>">
					<div class="verse-preview-loading"><?php _e('Loading...', 'bible-here'); ?></div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Format reference text for display.
	 *
	 * @since    1.0.0
	 * @param    array    $reference    Cross reference data
	 * @return   string   Formatted reference text
	 */
	private function format_reference_text($reference) {
		$book_name = !empty($reference['ref_book_abbr']) ? $reference['ref_book_abbr'] : $reference['ref_book_name'];
		$chapter = $reference['ref_chapter_number'];
		$verse_start = $reference['ref_verse_number'];
		$verse_end = $reference['ref_verse_end'];

		if (!empty($verse_end) && $verse_end != $verse_start) {
			return sprintf('%s %d:%d-%d', $book_name, $chapter, $verse_start, $verse_end);
		} else {
			return sprintf('%s %d:%d', $book_name, $chapter, $verse_start);
		}
	}

	/**
	 * Get cross reference popup content.
	 *
	 * @since    1.0.0
	 * @param    string    $verse_id    The verse ID
	 * @param    array     $options     Display options
	 * @return   string    HTML content for popup
	 */
	public function get_cross_reference_popup_content($verse_id, $options = array()) {
		$defaults = array(
			'show_type' => true,
			'show_notes' => true,
			'show_verse_text' => true,
			'limit' => 5
		);

		$options = wp_parse_args($options, $defaults);

		return $this->display_verse_cross_references($verse_id, $options);
	}

	/**
	 * Cross reference shortcode handler.
	 * [bible-cross-references ref="GEN.1.1" type="" limit="10" show_type="true" show_notes="true"]
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes
	 * @return   string   HTML output
	 */
	public function cross_reference_shortcode($atts) {
		$atts = shortcode_atts(array(
			'ref' => '',
			'book' => '',
			'chapter' => '',
			'verse' => '',
			'type' => '',
			'limit' => '10',
			'show_type' => 'true',
			'show_notes' => 'true',
			'show_source' => 'false',
			'show_strength' => 'false',
			'show_verse_text' => 'true',
			'group_by_type' => 'false',
			'min_strength' => '3'
		), $atts, 'bible-cross-references');

		// Determine verse ID
		$verse_id = '';
		if (!empty($atts['ref'])) {
			$verse_id = $atts['ref'];
		} elseif (!empty($atts['book']) && !empty($atts['chapter']) && !empty($atts['verse'])) {
			// Convert book name to book number if needed
			$book_number = $this->database->get_book_number_by_name($atts['book']);
			if ($book_number) {
				$verse_id = sprintf('%s.%d.%d', 
					strtoupper($atts['book']), 
					intval($atts['chapter']), 
					intval($atts['verse'])
				);
			}
		}

		if (empty($verse_id)) {
			return '<div class="bible-cross-references-error">' . __('Please specify a valid Bible reference.', 'bible-here') . '</div>';
		}

		$options = array(
			'reference_type' => $atts['type'],
			'limit' => intval($atts['limit']),
			'show_type' => ($atts['show_type'] === 'true'),
			'show_notes' => ($atts['show_notes'] === 'true'),
			'show_source' => ($atts['show_source'] === 'true'),
			'show_strength' => ($atts['show_strength'] === 'true'),
			'show_verse_text' => ($atts['show_verse_text'] === 'true'),
			'group_by_type' => ($atts['group_by_type'] === 'true'),
			'min_strength' => intval($atts['min_strength'])
		);

		return $this->display_verse_cross_references($verse_id, $options);
	}
}