<?php

/**
 * The database functionality of the plugin.
 *
 * @link       https://github.com/xjlin0
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 */

/**
 * The database functionality of the plugin.
 *
 * Defines the database operations, table management, and data access methods.
 *
 * @since      1.0.0
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 * @author     Jack Lin <xjlin0@gmail.com>
 */
class Bible_Here_Database {

	/**
	 * The database version.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of the database schema.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct($version) {
		$this->version = $version;
	}

	/**
	 * Get table name with WordPress prefix.
	 *
	 * @since    1.0.0
	 * @param    string    $table    Table name without prefix
	 * @return   string             Full table name with prefix
	 */
	public function get_table_name($table) {
		global $wpdb;
		return $wpdb->prefix . 'bible_here_' . $table;
	}

	/**
	 * Get all available Bible versions.
	 *
	 * @since    1.0.0
	 * @param    bool    $installed_only    Whether to return only installed versions
	 * @return   array                     Array of version objects
	 */
	public function get_versions($installed_only = true) {
		global $wpdb;

		$table_name = $this->get_table_name('versions');
		$where_clause = $installed_only ? 'WHERE installed = 1' : '';

		$results = $wpdb->get_results(
			"SELECT * FROM {$table_name} {$where_clause} ORDER BY language, name"
		);

		return $results ? $results : array();
	}

	/**
	 * Get version by abbreviation.
	 *
	 * @since    1.0.0
	 * @param    string    $abbreviation    Version abbreviation
	 * @param    string    $language        Language code
	 * @return   object|null                Version object or null
	 */
	public function get_version_by_abbreviation($abbreviation, $language = 'en') {
		global $wpdb;

		$table_name = $this->get_table_name('versions');

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE abbreviation = %s AND language = %s",
				$abbreviation,
				$language
			)
		);

		return $result;
	}

	/**
	 * Get all books for a language.
	 *
	 * @since    1.0.0
	 * @param    string    $language    Language code
	 * @return   array                Array of book objects
	 */
	public function get_books($language = 'en') {
		global $wpdb;

		$table_name = $this->get_table_name('books');

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE language = %s ORDER BY book_number",
				$language
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Get book by number.
	 *
	 * @since    1.0.0
	 * @param    int       $book_number    Book number
	 * @param    string    $language       Language code
	 * @return   object|null               Book object or null
	 */
	public function get_book_by_number($book_number, $language = 'en') {
		global $wpdb;

		$table_name = $this->get_table_name('books');

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE book_number = %d AND language = %s",
				$book_number,
				$language
			)
		);

		return $result;
	}

	/**
	 * Get book by abbreviation.
	 *
	 * @since    1.0.0
	 * @param    string    $abbreviation    Book abbreviation
	 * @param    string    $language        Language code
	 * @return   object|null                Book object or null
	 */
	public function get_book_by_abbreviation($abbreviation, $language = 'en') {
		global $wpdb;

		$abbrev_table = $this->get_table_name('abbreviations');
		$books_table = $this->get_table_name('books');

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT b.* FROM {$books_table} b 
				 INNER JOIN {$abbrev_table} a ON b.book_number = a.book_number AND b.language = a.language
				 WHERE a.abbreviation = %s AND a.language = %s",
				$abbreviation,
				$language
			)
		);

		return $result;
	}

	/**
	 * Get verses from a specific version.
	 *
	 * @since    1.0.0
	 * @param    string    $version         Version abbreviation
	 * @param    int       $book_number     Book number
	 * @param    int       $chapter_number  Chapter number
	 * @param    int       $verse_start     Starting verse number (optional)
	 * @param    int       $verse_end       Ending verse number (optional)
	 * @return   array                      Array of verse objects
	 */
	public function get_verses($version, $book_number, $chapter_number, $verse_start = null, $verse_end = null) {
		global $wpdb;

		// Get version info to determine table name
		$version_info = $this->get_version_by_abbreviation($version);
		if (!$version_info || !$version_info->installed) {
			return array();
		}

		$table_name = $version_info->table_name;

		// Build WHERE clause
		$where_conditions = array(
			$wpdb->prepare('book_number = %d', $book_number),
			$wpdb->prepare('chapter_number = %d', $chapter_number)
		);

		if ($verse_start !== null) {
			$where_conditions[] = $wpdb->prepare('verse_number >= %d', $verse_start);
		}

		if ($verse_end !== null) {
			$where_conditions[] = $wpdb->prepare('verse_number <= %d', $verse_end);
		}

		$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

		$results = $wpdb->get_results(
			"SELECT * FROM {$table_name} {$where_clause} ORDER BY verse_number"
		);

		return $results ? $results : array();
	}

	/**
	 * Get a single verse.
	 *
	 * @since    1.0.0
	 * @param    string    $version         Version abbreviation
	 * @param    int       $book_number     Book number
	 * @param    int       $chapter_number  Chapter number
	 * @param    int       $verse_number    Verse number
	 * @return   object|null                Verse object or null
	 */
	public function get_verse($version, $book_number, $chapter_number, $verse_number) {
		global $wpdb;

		// Get version info to determine table name
		$version_info = $this->get_version_by_abbreviation($version);
		if (!$version_info || !$version_info->installed) {
			return null;
		}

		$table_name = $version_info->table_name;

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE book_number = %d AND chapter_number = %d AND verse_number = %d",
				$book_number,
				$chapter_number,
				$verse_number
			)
		);

		return $result;
	}

	/**
	 * Search verses by text with advanced options.
	 *
	 * @since    1.0.0
	 * @param    string    $search_text     Text to search for
	 * @param    array     $options         Search options
	 * @return   array                      Array of verse objects with relevance scores
	 */
	public function search_verses($search_text, $options = array()) {
		global $wpdb;

		// Default options
		$defaults = array(
			'versions' => array('kjv'),
			'books' => array(), // Empty means all books
			'limit' => 50,
			'offset' => 0,
			'search_mode' => 'natural', // natural, boolean, ngram
			'sort_by' => 'relevance', // relevance, reference
			'include_context' => false
		);

		$options = array_merge($defaults, $options);

		// Ensure versions is an array
		if (!is_array($options['versions'])) {
			$options['versions'] = array($options['versions']);
		}

		$all_results = array();

		foreach ($options['versions'] as $version) {
			// Get version info to determine table name
			$version_info = $this->get_version_by_abbreviation($version);
			if (!$version_info || !$version_info->installed) {
				continue;
			}

			$table_name = $version_info->table_name;
			$results = $this->perform_search_query($search_text, $table_name, $version, $options);
			
			// Add version info to results
			foreach ($results as $result) {
				$result->version = $version;
				$result->version_name = $version_info->name;
				$all_results[] = $result;
			}
		}

		// Sort combined results
		if ($options['sort_by'] === 'relevance') {
			usort($all_results, function($a, $b) {
				return $b->relevance <=> $a->relevance;
			});
		} else {
			usort($all_results, function($a, $b) {
				if ($a->book_number !== $b->book_number) {
					return $a->book_number <=> $b->book_number;
				}
				if ($a->chapter_number !== $b->chapter_number) {
					return $a->chapter_number <=> $b->chapter_number;
				}
				return $a->verse_number <=> $b->verse_number;
			});
		}

		// Apply pagination to combined results
		$total_results = count($all_results);
		$paginated_results = array_slice($all_results, $options['offset'], $options['limit']);

		return array(
			'results' => $paginated_results,
			'total' => $total_results,
			'has_more' => ($options['offset'] + $options['limit']) < $total_results
		);
	}

	/**
	 * Perform the actual search query on a specific table.
	 *
	 * @since    1.0.0
	 * @param    string    $search_text    Text to search for
	 * @param    string    $table_name     Table name to search in
	 * @param    string    $version        Version abbreviation
	 * @param    array     $options        Search options
	 * @return   array                     Array of verse objects
	 */
	private function perform_search_query($search_text, $table_name, $version, $options) {
		global $wpdb;

		$where_conditions = array();
		$search_clause = '';

		// Build book filter if specified
		if (!empty($options['books'])) {
			$book_numbers = array();
			foreach ($options['books'] as $book) {
				if (is_numeric($book)) {
					$book_numbers[] = intval($book);
				} else {
					// Convert book name/abbreviation to number
					$book_info = $this->get_book_by_abbreviation($book);
					if ($book_info) {
						$book_numbers[] = $book_info->book_number;
					}
				}
			}
			if (!empty($book_numbers)) {
				$book_placeholders = implode(',', array_fill(0, count($book_numbers), '%d'));
				$where_conditions[] = $wpdb->prepare("book_number IN ({$book_placeholders})", $book_numbers);
			}
		}

		// Build search clause based on mode
		switch ($options['search_mode']) {
			case 'boolean':
				$search_clause = $wpdb->prepare(
					"MATCH(verse) AGAINST(%s IN BOOLEAN MODE) as relevance",
					$search_text
				);
				$where_conditions[] = $wpdb->prepare(
					"MATCH(verse) AGAINST(%s IN BOOLEAN MODE)",
					$search_text
				);
				break;

			case 'ngram':
				// Use ngram parser for better CJK (Chinese, Japanese, Korean) support
				$search_clause = $wpdb->prepare(
					"MATCH(verse) AGAINST(%s WITH QUERY EXPANSION) as relevance",
					$search_text
				);
				$where_conditions[] = $wpdb->prepare(
					"MATCH(verse) AGAINST(%s WITH QUERY EXPANSION)",
					$search_text
				);
				break;

			case 'natural':
			default:
				$search_clause = $wpdb->prepare(
					"MATCH(verse) AGAINST(%s IN NATURAL LANGUAGE MODE) as relevance",
					$search_text
				);
				$where_conditions[] = $wpdb->prepare(
					"MATCH(verse) AGAINST(%s IN NATURAL LANGUAGE MODE)",
					$search_text
				);
				break;
		}

		// Build WHERE clause
		$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

		// Build ORDER BY clause
		$order_clause = ($options['sort_by'] === 'relevance') 
			? 'ORDER BY relevance DESC, book_number, chapter_number, verse_number'
			: 'ORDER BY book_number, chapter_number, verse_number';

		// Execute search query
		$search_query = "SELECT *, {$search_clause} FROM {$table_name} {$where_clause} {$order_clause}";
		$results = $wpdb->get_results($search_query);

		// Fallback to LIKE search if FULLTEXT returns no results
		if (empty($results)) {
			$like_conditions = array();
			$like_conditions[] = $wpdb->prepare('verse LIKE %s', '%' . $wpdb->esc_like($search_text) . '%');
			
			// Add book filter for LIKE search too
			if (!empty($options['books'])) {
				$book_numbers = array();
				foreach ($options['books'] as $book) {
					if (is_numeric($book)) {
						$book_numbers[] = intval($book);
					} else {
						$book_info = $this->get_book_by_abbreviation($book);
						if ($book_info) {
							$book_numbers[] = $book_info->book_number;
						}
					}
				}
				if (!empty($book_numbers)) {
					$book_placeholders = implode(',', array_fill(0, count($book_numbers), '%d'));
					$like_conditions[] = $wpdb->prepare("book_number IN ({$book_placeholders})", $book_numbers);
				}
			}

			$like_where = 'WHERE ' . implode(' AND ', $like_conditions);
			$like_order = ($options['sort_by'] === 'relevance') 
				? 'ORDER BY book_number, chapter_number, verse_number'
				: 'ORDER BY book_number, chapter_number, verse_number';

			$like_query = "SELECT *, 1 as relevance FROM {$table_name} {$like_where} {$like_order}";
			$results = $wpdb->get_results($like_query);
		}

		return $results ? $results : array();
	}

	/**
	 * Get search suggestions based on partial input.
	 *
	 * @since    1.0.0
	 * @param    string    $partial_text    Partial search text
	 * @param    string    $version         Version abbreviation
	 * @param    int       $limit           Maximum suggestions
	 * @return   array                      Array of suggestion strings
	 */
	public function get_search_suggestions($partial_text, $version = 'kjv', $limit = 10) {
		global $wpdb;

		$version_info = $this->get_version_by_abbreviation($version);
		if (!$version_info || !$version_info->installed) {
			return array();
		}

		$table_name = $version_info->table_name;

		// Extract unique words that start with the partial text
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(verse, ' ', numbers.n), ' ', -1) as word
				 FROM {$table_name}
				 CROSS JOIN (
					 SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
					 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
				 ) numbers
				 WHERE CHAR_LENGTH(verse) - CHAR_LENGTH(REPLACE(verse, ' ', '')) >= numbers.n - 1
				 AND LOWER(SUBSTRING_INDEX(SUBSTRING_INDEX(verse, ' ', numbers.n), ' ', -1)) LIKE %s
				 ORDER BY word
				 LIMIT %d",
				strtolower($partial_text) . '%',
				$limit
			)
		);

		$suggestions = array();
		foreach ($results as $result) {
			$word = trim($result->word, '.,!?;:"()[]{}');
			if (strlen($word) > 2 && !in_array($word, $suggestions)) {
				$suggestions[] = $word;
			}
		}

		return $suggestions;
	}

	/**
	 * Get cross references for a verse.
	 *
	 * @since    1.0.0
	 * @param    string    $verse_id    Verse ID in format "book:chapter:verse"
	 * @return   array                  Array of cross reference objects
	 */
	public function get_cross_references($verse_id) {
		global $wpdb;

		$table_name = $this->get_table_name('cross_references');

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE verse_id = %s ORDER BY rank",
				$verse_id
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Get commentaries for a verse.
	 *
	 * @since    1.0.0
	 * @param    string    $verse_id    The verse ID
	 * @param    array     $options     Query options
	 * @return   array                  Array of commentaries
	 */
	public function get_commentaries($verse_id, $options = array()) {
		global $wpdb;

		$table_name = $this->get_table_name('commentaries');
		$authors_table = $this->get_table_name('commentary_authors');
		$sources_table = $this->get_table_name('commentary_sources');
		
		$where_conditions = array('c.verse_id = %s', 'c.is_active = 1');
		$where_values = array($verse_id);
		
		// Add optional filters
		if (!empty($options['author_id'])) {
			$where_conditions[] = 'c.author_id = %d';
			$where_values[] = $options['author_id'];
		}
		
		if (!empty($options['source_id'])) {
			$where_conditions[] = 'c.source_id = %d';
			$where_values[] = $options['source_id'];
		}
		
		if (!empty($options['commentary_type'])) {
			$where_conditions[] = 'c.commentary_type = %s';
			$where_values[] = $options['commentary_type'];
		}
		
		if (!empty($options['language'])) {
			$where_conditions[] = 'c.language = %s';
			$where_values[] = $options['language'];
		}
		
		$where_clause = implode(' AND ', $where_conditions);
		
		$sql = $wpdb->prepare(
			"SELECT c.*, 
					a.name as author_name, a.full_name as author_full_name,
					s.name as source_name, s.abbreviation as source_abbreviation
			 FROM {$table_name} c
			 LEFT JOIN {$authors_table} a ON c.author_id = a.id
			 LEFT JOIN {$sources_table} s ON c.source_id = s.id
			 WHERE {$where_clause}
			 ORDER BY c.rank ASC, c.created_at ASC",
			...$where_values
		);
		
		$results = $wpdb->get_results($sql);
		return $results ? $results : array();
	}
	
	/**
	 * Get commentaries for a chapter.
	 *
	 * @since    1.0.0
	 * @param    int       $book_number     The book number
	 * @param    int       $chapter_number  The chapter number
	 * @param    array     $options         Query options
	 * @return   array                      Array of commentaries
	 */
	public function get_chapter_commentaries($book_number, $chapter_number, $options = array()) {
		global $wpdb;

		$table_name = $this->get_table_name('commentaries');
		$authors_table = $this->get_table_name('commentary_authors');
		$sources_table = $this->get_table_name('commentary_sources');
		
		$where_conditions = array(
			'c.book_number = %d',
			'c.chapter_number = %d',
			'c.is_active = 1'
		);
		$where_values = array($book_number, $chapter_number);
		
		// Add optional filters
		if (!empty($options['commentary_type'])) {
			$where_conditions[] = 'c.commentary_type IN (%s)';
			$where_values[] = is_array($options['commentary_type']) 
				? implode(',', array_map(function($type) { return "'$type'"; }, $options['commentary_type']))
				: $options['commentary_type'];
		} else {
			$where_conditions[] = "c.commentary_type IN ('verse', 'chapter')";
		}
		
		if (!empty($options['language'])) {
			$where_conditions[] = 'c.language = %s';
			$where_values[] = $options['language'];
		}
		
		$where_clause = implode(' AND ', $where_conditions);
		
		$sql = $wpdb->prepare(
			"SELECT c.*, 
					a.name as author_name, a.full_name as author_full_name,
					s.name as source_name, s.abbreviation as source_abbreviation
			 FROM {$table_name} c
			 LEFT JOIN {$authors_table} a ON c.author_id = a.id
			 LEFT JOIN {$sources_table} s ON c.source_id = s.id
			 WHERE {$where_clause}
			 ORDER BY c.verse_number ASC, c.rank ASC, c.created_at ASC",
			...$where_values
		);
		
		$results = $wpdb->get_results($sql);
		return $results ? $results : array();
	}
	
	/**
	 * Get commentary authors.
	 *
	 * @since    1.0.0
	 * @param    array     $options    Query options
	 * @return   array               Array of authors
	 */
	public function get_commentary_authors($options = array()) {
		global $wpdb;

		$table_name = $this->get_table_name('commentary_authors');
		
		$where_conditions = array('is_active = 1');
		$where_values = array();
		
		if (!empty($options['nationality'])) {
			$where_conditions[] = 'nationality = %s';
			$where_values[] = $options['nationality'];
		}
		
		if (!empty($options['denomination'])) {
			$where_conditions[] = 'denomination = %s';
			$where_values[] = $options['denomination'];
		}
		
		$where_clause = implode(' AND ', $where_conditions);
		$order_by = !empty($options['order_by']) ? $options['order_by'] : 'name ASC';
		
		if (empty($where_values)) {
			$sql = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY {$order_by}";
			$results = $wpdb->get_results($sql);
		} else {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY {$order_by}",
				...$where_values
			);
			$results = $wpdb->get_results($sql);
		}
		
		return $results ? $results : array();
	}
	
	/**
	 * Get commentary sources.
	 *
	 * @since    1.0.0
	 * @param    array     $options    Query options
	 * @return   array               Array of sources
	 */
	public function get_commentary_sources($options = array()) {
		global $wpdb;

		$table_name = $this->get_table_name('commentary_sources');
		
		$where_conditions = array('is_active = 1');
		$where_values = array();
		
		if (!empty($options['language'])) {
			$where_conditions[] = 'language = %s';
			$where_values[] = $options['language'];
		}
		
		if (!empty($options['source_type'])) {
			$where_conditions[] = 'source_type = %s';
			$where_values[] = $options['source_type'];
		}
		
		if (!empty($options['publisher'])) {
			$where_conditions[] = 'publisher = %s';
			$where_values[] = $options['publisher'];
		}
		
		$where_clause = implode(' AND ', $where_conditions);
		$order_by = !empty($options['order_by']) ? $options['order_by'] : 'name ASC';
		
		if (empty($where_values)) {
			$sql = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY {$order_by}";
			$results = $wpdb->get_results($sql);
		} else {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY {$order_by}",
				...$where_values
			);
			$results = $wpdb->get_results($sql);
		}
		
		return $results ? $results : array();
	}

	/**
	 * Insert or update a Bible version.
	 *
	 * @since    1.0.0
	 * @param    array    $version_data    Version data array
	 * @return   int|false               Version ID or false on failure
	 */
	public function insert_version($version_data) {
		global $wpdb;

		$table_name = $this->get_table_name('versions');

		$result = $wpdb->insert(
			$table_name,
			$version_data,
			array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update version installation status.
	 *
	 * @since    1.0.0
	 * @param    int     $version_id    Version ID
	 * @param    bool    $installed     Installation status
	 * @return   bool                   Success status
	 */
	public function update_version_status($version_id, $installed) {
		global $wpdb;

		$table_name = $this->get_table_name('versions');

		$result = $wpdb->update(
			$table_name,
			array('installed' => $installed ? 1 : 0),
			array('id' => $version_id),
			array('%d'),
			array('%d')
		);

		return $result !== false;
	}

	/**
	 * Get database statistics.
	 *
	 * @since    1.0.0
	 * @return   array    Array of statistics
	 */
	public function get_statistics() {
		global $wpdb;

		$stats = array();

		// Count installed versions
		$versions_table = $this->get_table_name('versions');
		$stats['installed_versions'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$versions_table} WHERE installed = 1"
		);

		// Count total books
		$books_table = $this->get_table_name('books');
		$stats['total_books'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$books_table}"
		);

		// Count verses for each installed version
		$versions = $this->get_versions(true);
		$stats['verses_by_version'] = array();

		foreach ($versions as $version) {
			$verse_count = $wpdb->get_var(
				"SELECT COUNT(*) FROM {$version->table_name}"
			);
			$stats['verses_by_version'][$version->abbreviation] = $verse_count;
		}

		return $stats;
	}

	/**
	 * Check if database tables exist.
	 *
	 * @since    1.0.0
	 * @return   bool    True if all tables exist
	 */
	public function tables_exist() {
		global $wpdb;

		$required_tables = array(
			'books',
			'genres',
			'abbreviations',
			'versions',
			'commentaries',
			'cross_references',
			'search_index'
		);

		foreach ($required_tables as $table) {
			$table_name = $this->get_table_name($table);
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SHOW TABLES LIKE %s",
					$table_name
				)
			);

			if (!$exists) {
				return false;
			}
		}

		return true;
	}

}