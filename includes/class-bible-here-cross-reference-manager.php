<?php

/**
 * The cross reference management functionality of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 */

/**
 * The cross reference management functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 * @author     Your Name <email@example.com>
 */
class Bible_Here_Cross_Reference_Manager {

	/**
	 * The database instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Bible_Here_Database    $database    The database instance.
	 */
	private $database;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->database = new Bible_Here_Database();
	}

	/**
	 * Get cross references for a specific verse.
	 *
	 * @since    1.0.0
	 * @param    string    $verse_id    The verse ID (e.g., "GEN.1.1")
	 * @param    array     $options     Query options
	 * @return   array     Array of cross references
	 */
	public function get_verse_cross_references($verse_id, $options = array()) {
		global $wpdb;

		$defaults = array(
			'reference_type' => '',
			'min_strength' => 1,
			'source' => '',
			'limit' => 50,
			'order_by' => 'strength',
			'order' => 'DESC'
		);

		$options = wp_parse_args($options, $defaults);

		$table_name = $wpdb->prefix . 'bible_here_cross_references';
		$books_table = $wpdb->prefix . 'bible_here_books';

		$where_conditions = array(
			"cr.verse_id = %s",
			"cr.is_active = 1",
			"cr.strength >= %d"
		);
		$where_values = array($verse_id, $options['min_strength']);

		if (!empty($options['reference_type'])) {
			$where_conditions[] = "cr.reference_type = %s";
			$where_values[] = $options['reference_type'];
		}

		if (!empty($options['source'])) {
			$where_conditions[] = "cr.source = %s";
			$where_values[] = $options['source'];
		}

		$where_clause = implode(' AND ', $where_conditions);
		$order_clause = sprintf('ORDER BY cr.%s %s', $options['order_by'], $options['order']);
		$limit_clause = sprintf('LIMIT %d', $options['limit']);

		$sql = "
			SELECT 
				cr.*,
				b.name as ref_book_name,
				b.abbreviation as ref_book_abbr
			FROM {$table_name} cr
			LEFT JOIN {$books_table} b ON cr.ref_book_number = b.book_number
			WHERE {$where_clause}
			{$order_clause}
			{$limit_clause}
		";

		$prepared_sql = $wpdb->prepare($sql, $where_values);
		$results = $wpdb->get_results($prepared_sql, ARRAY_A);

		return $results ? $results : array();
	}

	/**
	 * Get cross references for multiple verses in a chapter.
	 *
	 * @since    1.0.0
	 * @param    int       $book_number      The book number
	 * @param    int       $chapter_number   The chapter number
	 * @param    array     $options          Query options
	 * @return   array     Array of cross references grouped by verse
	 */
	public function get_chapter_cross_references($book_number, $chapter_number, $options = array()) {
		global $wpdb;

		$defaults = array(
			'reference_type' => '',
			'min_strength' => 1,
			'source' => '',
			'limit_per_verse' => 10
		);

		$options = wp_parse_args($options, $defaults);

		$table_name = $wpdb->prefix . 'bible_here_cross_references';
		$books_table = $wpdb->prefix . 'bible_here_books';

		$where_conditions = array(
			"cr.book_number = %d",
			"cr.chapter_number = %d",
			"cr.is_active = 1",
			"cr.strength >= %d"
		);
		$where_values = array($book_number, $chapter_number, $options['min_strength']);

		if (!empty($options['reference_type'])) {
			$where_conditions[] = "cr.reference_type = %s";
			$where_values[] = $options['reference_type'];
		}

		if (!empty($options['source'])) {
			$where_conditions[] = "cr.source = %s";
			$where_values[] = $options['source'];
		}

		$where_clause = implode(' AND ', $where_conditions);

		$sql = "
			SELECT 
				cr.*,
				b.name as ref_book_name,
				b.abbreviation as ref_book_abbr
			FROM {$table_name} cr
			LEFT JOIN {$books_table} b ON cr.ref_book_number = b.book_number
			WHERE {$where_clause}
			ORDER BY cr.verse_number ASC, cr.strength DESC
		";

		$prepared_sql = $wpdb->prepare($sql, $where_values);
		$results = $wpdb->get_results($prepared_sql, ARRAY_A);

		// Group by verse number
		$grouped_results = array();
		foreach ($results as $reference) {
			$verse_num = $reference['verse_number'];
			if (!isset($grouped_results[$verse_num])) {
				$grouped_results[$verse_num] = array();
			}
			
			// Limit references per verse
			if (count($grouped_results[$verse_num]) < $options['limit_per_verse']) {
				$grouped_results[$verse_num][] = $reference;
			}
		}

		return $grouped_results;
	}

	/**
	 * Add a new cross reference.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Cross reference data
	 * @return   int|false    The cross reference ID on success, false on failure
	 */
	public function add_cross_reference($data) {
		global $wpdb;

		$required_fields = array('verse_id', 'book_number', 'chapter_number', 'verse_number', 
								'ref_verse_id', 'ref_book_number', 'ref_chapter_number', 'ref_verse_number');

		// Validate required fields
		foreach ($required_fields as $field) {
			if (empty($data[$field])) {
				return false;
			}
		}

		$table_name = $wpdb->prefix . 'bible_here_cross_references';

		$insert_data = array(
			'verse_id' => sanitize_text_field($data['verse_id']),
			'book_number' => intval($data['book_number']),
			'chapter_number' => intval($data['chapter_number']),
			'verse_number' => intval($data['verse_number']),
			'ref_verse_id' => sanitize_text_field($data['ref_verse_id']),
			'ref_book_number' => intval($data['ref_book_number']),
			'ref_chapter_number' => intval($data['ref_chapter_number']),
			'ref_verse_number' => intval($data['ref_verse_number']),
			'ref_verse_end' => isset($data['ref_verse_end']) ? intval($data['ref_verse_end']) : null,
			'reference_type' => isset($data['reference_type']) ? sanitize_text_field($data['reference_type']) : 'parallel',
			'strength' => isset($data['strength']) ? intval($data['strength']) : 5,
			'notes' => isset($data['notes']) ? sanitize_textarea_field($data['notes']) : '',
			'source' => isset($data['source']) ? sanitize_text_field($data['source']) : '',
			'rank' => isset($data['rank']) ? intval($data['rank']) : 1,
			'is_active' => isset($data['is_active']) ? intval($data['is_active']) : 1
		);

		$result = $wpdb->insert($table_name, $insert_data);

		if ($result !== false) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Update an existing cross reference.
	 *
	 * @since    1.0.0
	 * @param    int      $id      Cross reference ID
	 * @param    array    $data    Updated cross reference data
	 * @return   bool     True on success, false on failure
	 */
	public function update_cross_reference($id, $data) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_cross_references';

		$update_data = array();

		$allowed_fields = array(
			'verse_id', 'book_number', 'chapter_number', 'verse_number',
			'ref_verse_id', 'ref_book_number', 'ref_chapter_number', 'ref_verse_number', 'ref_verse_end',
			'reference_type', 'strength', 'notes', 'source', 'rank', 'is_active'
		);

		foreach ($allowed_fields as $field) {
			if (isset($data[$field])) {
				switch ($field) {
					case 'verse_id':
					case 'ref_verse_id':
					case 'reference_type':
					case 'source':
						$update_data[$field] = sanitize_text_field($data[$field]);
						break;
					case 'notes':
						$update_data[$field] = sanitize_textarea_field($data[$field]);
						break;
					default:
						$update_data[$field] = intval($data[$field]);
						break;
				}
			}
		}

		if (empty($update_data)) {
			return false;
		}

		$result = $wpdb->update(
			$table_name,
			$update_data,
			array('id' => intval($id)),
			array('%s', '%d', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%d', '%d'),
			array('%d')
		);

		return $result !== false;
	}

	/**
	 * Delete a cross reference (soft delete).
	 *
	 * @since    1.0.0
	 * @param    int    $id    Cross reference ID
	 * @return   bool   True on success, false on failure
	 */
	public function delete_cross_reference($id) {
		return $this->update_cross_reference($id, array('is_active' => 0));
	}

	/**
	 * Get cross reference by ID.
	 *
	 * @since    1.0.0
	 * @param    int    $id    Cross reference ID
	 * @return   array|null    Cross reference data or null if not found
	 */
	public function get_cross_reference_by_id($id) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_cross_references';
		$books_table = $wpdb->prefix . 'bible_here_books';

		$sql = "
			SELECT 
				cr.*,
				b1.name as book_name,
				b1.abbreviation as book_abbr,
				b2.name as ref_book_name,
				b2.abbreviation as ref_book_abbr
			FROM {$table_name} cr
			LEFT JOIN {$books_table} b1 ON cr.book_number = b1.book_number
			LEFT JOIN {$books_table} b2 ON cr.ref_book_number = b2.book_number
			WHERE cr.id = %d
		";

		$result = $wpdb->get_row($wpdb->prepare($sql, $id), ARRAY_A);

		return $result;
	}

	/**
	 * Get all cross reference types.
	 *
	 * @since    1.0.0
	 * @return   array    Array of reference types
	 */
	public function get_reference_types() {
		return array(
			'parallel' => __('Parallel Passage', 'bible-here'),
			'theme' => __('Similar Theme', 'bible-here'),
			'word' => __('Word Study', 'bible-here'),
			'concept' => __('Concept Link', 'bible-here'),
			'prophecy' => __('Prophecy', 'bible-here'),
			'fulfillment' => __('Fulfillment', 'bible-here'),
			'contrast' => __('Contrast', 'bible-here'),
			'illustration' => __('Illustration', 'bible-here')
		);
	}

	/**
	 * Get cross reference sources.
	 *
	 * @since    1.0.0
	 * @return   array    Array of unique sources
	 */
	public function get_cross_reference_sources() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_cross_references';

		$sql = "
			SELECT DISTINCT source
			FROM {$table_name}
			WHERE source IS NOT NULL AND source != ''
			ORDER BY source ASC
		";

		$results = $wpdb->get_col($sql);

		return $results ? $results : array();
	}

	/**
	 * Import cross references from array data.
	 *
	 * @since    1.0.0
	 * @param    array    $references    Array of cross reference data
	 * @param    string   $source        Source identifier
	 * @return   array    Import results
	 */
	public function import_cross_references($references, $source = '') {
		$results = array(
			'success' => 0,
			'failed' => 0,
			'errors' => array()
		);

		foreach ($references as $reference) {
			if (!empty($source)) {
				$reference['source'] = $source;
			}

			$result = $this->add_cross_reference($reference);

			if ($result !== false) {
				$results['success']++;
			} else {
				$results['failed']++;
				$results['errors'][] = sprintf(
					__('Failed to import cross reference for %s', 'bible-here'),
					$reference['verse_id'] ?? 'unknown'
				);
			}
		}

		return $results;
	}
}