<?php

/**
 * Strong Number Manager Class
 *
 * @link       https://github.com/xjlin0
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 */

/**
 * Strong Number Manager Class.
 *
 * This class defines all code necessary to manage Strong Numbers.
 *
 * @since      1.0.0
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 * @author     Jack Lin <xjlin0@gmail.com>
 */
class Bible_Here_Strong_Number_Manager {

	/**
	 * Get Strong Number by number.
	 *
	 * @since    1.0.0
	 * @param    string    $strong_number    The Strong Number (e.g., 'H1', 'G2316')
	 * @return   object|null    Strong Number data or null if not found
	 */
	public function get_strong_number($strong_number) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_strong_numbers';

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE strong_number = %s AND is_active = 1",
				$strong_number
			)
		);

		return $result;
	}

	/**
	 * Get Strong Number definitions.
	 *
	 * @since    1.0.0
	 * @param    string    $strong_number    The Strong Number
	 * @param    string    $type            Definition type (brief, detailed, etymology)
	 * @return   array     Array of definitions
	 */
	public function get_strong_definitions($strong_number, $type = 'brief') {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_strong_definitions';

		$where_clause = "strong_number = %s AND is_active = 1";
		$params = array($strong_number);

		if ($type !== 'all') {
			$where_clause .= " AND definition_type = %s";
			$params[] = $type;
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE $where_clause ORDER BY definition_type",
				$params
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Get verse Strong Numbers.
	 *
	 * @since    1.0.0
	 * @param    string    $version         Bible version
	 * @param    int       $book_number     Book number
	 * @param    int       $chapter_number  Chapter number
	 * @param    int       $verse_number    Verse number
	 * @return   array     Array of Strong Numbers for the verse
	 */
	public function get_verse_strong_numbers($version, $book_number, $chapter_number, $verse_number) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_verse_strong_numbers';

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name 
				 WHERE version = %s AND book_number = %d AND chapter_number = %d AND verse_number = %d AND is_active = 1
				 ORDER BY word_position",
				$version, $book_number, $chapter_number, $verse_number
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Search Strong Numbers.
	 *
	 * @since    1.0.0
	 * @param    string    $search_term     Search term
	 * @param    string    $language        Language filter (hebrew, greek, or all)
	 * @param    int       $limit           Number of results to return
	 * @return   array     Array of matching Strong Numbers
	 */
	public function search_strong_numbers($search_term, $language = 'all', $limit = 50) {
		global $wpdb;

		$strong_table = $wpdb->prefix . 'bible_here_strong_numbers';
		$def_table = $wpdb->prefix . 'bible_here_strong_definitions';

		$where_clause = "s.is_active = 1";
		$params = array();

		if ($language !== 'all') {
			$where_clause .= " AND s.language = %s";
			$params[] = $language;
		}

		// Search in Strong Number, original word, transliteration, and definitions
		$search_clause = "(
			s.strong_number LIKE %s OR 
			s.original_word LIKE %s OR 
			s.transliteration LIKE %s OR
			d.definition LIKE %s
		)";
		
		$search_term_wildcard = '%' . $wpdb->esc_like($search_term) . '%';
		$params = array_merge($params, array(
			$search_term_wildcard,
			$search_term_wildcard,
			$search_term_wildcard,
			$search_term_wildcard
		));

		$sql = "SELECT DISTINCT s.*, d.definition as brief_definition
				FROM $strong_table s
				LEFT JOIN $def_table d ON s.strong_number = d.strong_number AND d.definition_type = 'brief' AND d.is_active = 1
				WHERE $where_clause AND $search_clause
				ORDER BY s.strong_number
				LIMIT %d";

		$params[] = $limit;

		$results = $wpdb->get_results(
			$wpdb->prepare($sql, $params)
		);

		return $results ? $results : array();
	}

	/**
	 * Add Strong Number.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Strong Number data
	 * @return   int|false    Insert ID on success, false on failure
	 */
	public function add_strong_number($data) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_strong_numbers';

		$defaults = array(
			'transliteration' => '',
			'pronunciation' => '',
			'part_of_speech' => '',
			'root_word' => '',
			'is_active' => 1
		);

		$data = wp_parse_args($data, $defaults);

		$result = $wpdb->insert(
			$table_name,
			array(
				'strong_number' => $data['strong_number'],
				'language' => $data['language'],
				'original_word' => $data['original_word'],
				'transliteration' => $data['transliteration'],
				'pronunciation' => $data['pronunciation'],
				'part_of_speech' => $data['part_of_speech'],
				'root_word' => $data['root_word'],
				'is_active' => $data['is_active']
			),
			array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Add Strong Number definition.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Definition data
	 * @return   int|false    Insert ID on success, false on failure
	 */
	public function add_strong_definition($data) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_strong_definitions';

		$defaults = array(
			'definition_type' => 'brief',
			'usage_notes' => '',
			'related_words' => '',
			'example_verses' => '',
			'source' => '',
			'is_active' => 1
		);

		$data = wp_parse_args($data, $defaults);

		$result = $wpdb->insert(
			$table_name,
			array(
				'strong_number' => $data['strong_number'],
				'definition_type' => $data['definition_type'],
				'definition' => $data['definition'],
				'usage_notes' => $data['usage_notes'],
				'related_words' => $data['related_words'],
				'example_verses' => $data['example_verses'],
				'source' => $data['source'],
				'is_active' => $data['is_active']
			),
			array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Add verse Strong Number mapping.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Verse Strong Number data
	 * @return   int|false    Insert ID on success, false on failure
	 */
	public function add_verse_strong_number($data) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_verse_strong_numbers';

		$defaults = array(
			'version' => 'kjv',
			'morph_code' => '',
			'is_active' => 1
		);

		$data = wp_parse_args($data, $defaults);

		$result = $wpdb->insert(
			$table_name,
			array(
				'version' => $data['version'],
				'book_number' => $data['book_number'],
				'chapter_number' => $data['chapter_number'],
				'verse_number' => $data['verse_number'],
				'word_position' => $data['word_position'],
				'word_text' => $data['word_text'],
				'strong_number' => $data['strong_number'],
				'morph_code' => $data['morph_code'],
				'is_active' => $data['is_active']
			),
			array('%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d')
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get Strong Numbers by language.
	 *
	 * @since    1.0.0
	 * @param    string    $language    Language (hebrew or greek)
	 * @param    int       $limit       Number of results to return
	 * @param    int       $offset      Offset for pagination
	 * @return   array     Array of Strong Numbers
	 */
	public function get_strong_numbers_by_language($language, $limit = 50, $offset = 0) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_strong_numbers';

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name 
				 WHERE language = %s AND is_active = 1
				 ORDER BY strong_number
				 LIMIT %d OFFSET %d",
				$language, $limit, $offset
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Get Strong Number usage statistics.
	 *
	 * @since    1.0.0
	 * @param    string    $strong_number    The Strong Number
	 * @return   object    Usage statistics
	 */
	public function get_strong_number_usage($strong_number) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_verse_strong_numbers';

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					COUNT(*) as total_occurrences,
					COUNT(DISTINCT CONCAT(book_number, '-', chapter_number, '-', verse_number)) as unique_verses,
					COUNT(DISTINCT book_number) as books_count,
					COUNT(DISTINCT CONCAT(book_number, '-', chapter_number)) as chapters_count
				 FROM $table_name 
				 WHERE strong_number = %s AND is_active = 1",
				$strong_number
			)
		);

		return $result;
	}

	/**
	 * Import Strong Numbers from array data.
	 *
	 * @since    1.0.0
	 * @param    array    $strong_numbers    Array of Strong Number data
	 * @return   array    Import results
	 */
	public function import_strong_numbers($strong_numbers) {
		$results = array(
			'success' => 0,
			'failed' => 0,
			'errors' => array()
		);

		foreach ($strong_numbers as $strong_data) {
			$result = $this->add_strong_number($strong_data);
			
			if ($result) {
				$results['success']++;
			} else {
				$results['failed']++;
				$results['errors'][] = 'Failed to import Strong Number: ' . $strong_data['strong_number'];
			}
		}

		return $results;
	}

}