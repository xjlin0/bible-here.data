<?php

/**
 * The commentary management functionality of the plugin.
 *
 * @link       https://github.com/xjlin0
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 */

/**
 * The commentary management functionality of the plugin.
 *
 * Defines the commentary operations, data access methods, and business logic.
 *
 * @since      1.0.0
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 * @author     Jack Lin <xjlin0@gmail.com>
 */
class Bible_Here_Commentary_Manager {

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
	 * @param    Bible_Here_Database    $database    The database instance.
	 */
	public function __construct($database) {
		$this->database = $database;
	}

	/**
	 * Get commentaries for a specific verse.
	 *
	 * @since    1.0.0
	 * @param    string    $verse_id    Verse ID in format "book:chapter:verse"
	 * @param    array     $options     Query options
	 * @return   array                  Array of commentary objects
	 */
	public function get_verse_commentaries($verse_id, $options = array()) {
		global $wpdb;

		$defaults = array(
			'include_authors' => true,
			'include_sources' => true,
			'limit' => 10,
			'status' => 'published'
		);

		$options = array_merge($defaults, $options);

		// Set default options
		$default_options = array(
			'author_id' => null,
			'source_id' => null,
			'commentary_type' => 'verse',
			'language' => 'en'
		);
		
		$options = array_merge($default_options, $options);
		
		return $this->database->get_commentaries($verse_id, $options);
	}

	/**
	 * Get commentaries for a chapter.
	 *
	 * @since    1.0.0
	 * @param    int       $book_number     Book number
	 * @param    int       $chapter_number  Chapter number
	 * @param    array     $options         Query options
	 * @return   array                      Array of commentary objects
	 */
	public function get_chapter_commentaries($book_number, $chapter_number, $options = array()) {
		// Set default options
		$default_options = array(
			'commentary_type' => array('verse', 'chapter'),
			'language' => 'en'
		);
		
		$options = array_merge($default_options, $options);
		
		return $this->database->get_chapter_commentaries($book_number, $chapter_number, $options);
	}

	/**
	 * Add a new commentary.
	 *
	 * @since    1.0.0
	 * @param    array     $commentary_data    Commentary data
	 * @return   int|false                   Commentary ID on success, false on failure
	 */
	public function add_commentary($commentary_data) {
		// Validate required fields
		if (empty($commentary_data['verse_id']) || empty($commentary_data['commentary'])) {
			return false;
		}
		
		// Parse verse_id to get book, chapter, verse numbers
		$verse_parts = explode(':', $commentary_data['verse_id']);
		if (count($verse_parts) !== 3) {
			return false;
		}
		
		// Set default values
		$defaults = array(
			'book_number' => intval($verse_parts[0]),
			'chapter_number' => intval($verse_parts[1]),
			'verse_number' => intval($verse_parts[2]),
			'author_id' => null,
			'source_id' => null,
			'commentary_type' => 'verse',
			'language' => 'en',
			'rank' => 1,
			'is_active' => 1
		);
		
		$commentary_data = array_merge($defaults, $commentary_data);
		
		// Insert into database
		$table_name = $this->database->get_table_name('commentaries');
		
		$result = $this->database->wpdb->insert(
			$table_name,
			$commentary_data,
			array(
				'%s', // verse_id
				'%d', // book_number
				'%d', // chapter_number
				'%d', // verse_number
				'%d', // author_id
				'%d', // source_id
				'%s', // commentary
				'%s', // commentary_type
				'%s', // language
				'%d', // rank
				'%d'  // is_active
			)
		);
		
		return $result ? $this->database->wpdb->insert_id : false;
	}

	/**
	 * Update an existing commentary.
	 *
	 * @since    1.0.0
	 * @param    int       $commentary_id      Commentary ID
	 * @param    array     $commentary_data    Updated commentary data
	 * @return   bool                          True on success, false on failure
	 */
	public function update_commentary($commentary_id, $commentary_data) {
		if (empty($commentary_id)) {
			return false;
		}
		
		// If verse_id is being updated, parse it to update book/chapter/verse numbers
		if (!empty($commentary_data['verse_id'])) {
			$verse_parts = explode(':', $commentary_data['verse_id']);
			if (count($verse_parts) === 3) {
				$commentary_data['book_number'] = intval($verse_parts[0]);
				$commentary_data['chapter_number'] = intval($verse_parts[1]);
				$commentary_data['verse_number'] = intval($verse_parts[2]);
			}
		}
		
		// Add updated timestamp
		$commentary_data['updated_at'] = current_time('mysql');
		
		$table_name = $this->database->get_table_name('commentaries');
		
		$result = $this->database->wpdb->update(
			$table_name,
			$commentary_data,
			array('id' => $commentary_id),
			null, // format for data
			array('%d') // format for where
		);
		
		return $result !== false;
	}

	/**
	 * Delete a commentary (soft delete by setting is_active to 0).
	 *
	 * @since    1.0.0
	 * @param    int       $commentary_id    Commentary ID
	 * @param    bool      $hard_delete      Whether to permanently delete (default: false)
	 * @return   bool                        True on success, false on failure
	 */
	public function delete_commentary($commentary_id, $hard_delete = false) {
		if (empty($commentary_id)) {
			return false;
		}
		
		$table_name = $this->database->get_table_name('commentaries');
		
		if ($hard_delete) {
			// Permanently delete from database
			$result = $this->database->wpdb->delete(
				$table_name,
				array('id' => $commentary_id),
				array('%d')
			);
		} else {
			// Soft delete by setting is_active to 0
			$result = $this->database->wpdb->update(
				$table_name,
				array(
					'is_active' => 0,
					'updated_at' => current_time('mysql')
				),
				array('id' => $commentary_id),
				array('%d', '%s'),
				array('%d')
			);
		}
		
		return $result !== false;
	}

	/**
	 * Get commentary authors.
	 *
	 * @since    1.0.0
	 * @param    array     $options    Query options
	 * @return   array               Array of author objects
	 */
	public function get_commentary_authors($options = array()) {
		return $this->database->get_commentary_authors($options);
	}

	/**
	 * Get a specific commentary author.
	 *
	 * @since    1.0.0
	 * @param    int       $author_id    Author ID
	 * @return   object|null             Author object or null
	 */
	public function get_commentary_author($author_id) {
		global $wpdb;

		$table_name = $this->database->get_table_name('commentary_authors');

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$author_id
			)
		);

		return $result;
	}

	/**
	 * Get commentary sources.
	 *
	 * @since    1.0.0
	 * @param    array     $options    Query options
	 * @return   array               Array of source objects
	 */
	public function get_commentary_sources($options = array()) {
		return $this->database->get_commentary_sources($options);
	}
	
	/**
	 * Add a new commentary author.
	 *
	 * @since    1.0.0
	 * @param    array     $author_data    Author data
	 * @return   int|false             Author ID on success, false on failure
	 */
	public function add_commentary_author($author_data) {
		// Validate required fields
		if (empty($author_data['name'])) {
			return false;
		}
		
		// Set default values
		$defaults = array(
			'full_name' => null,
			'bio' => null,
			'birth_year' => null,
			'death_year' => null,
			'nationality' => null,
			'denomination' => null,
			'website' => null,
			'is_active' => 1
		);
		
		$author_data = array_merge($defaults, $author_data);
		
		// Insert into database
		$table_name = $this->database->get_table_name('commentary_authors');
		
		$result = $this->database->wpdb->insert(
			$table_name,
			$author_data
		);
		
		return $result ? $this->database->wpdb->insert_id : false;
	}
	
	/**
	 * Add a new commentary source.
	 *
	 * @since    1.0.0
	 * @param    array     $source_data    Source data
	 * @return   int|false             Source ID on success, false on failure
	 */
	public function add_commentary_source($source_data) {
		// Validate required fields
		if (empty($source_data['name'])) {
			return false;
		}
		
		// Set default values
		$defaults = array(
			'full_name' => null,
			'abbreviation' => null,
			'description' => null,
			'publisher' => null,
			'publication_year' => null,
			'isbn' => null,
			'url' => null,
			'copyright' => null,
			'language' => 'en',
			'source_type' => 'commentary',
			'is_active' => 1
		);
		
		$source_data = array_merge($defaults, $source_data);
		
		// Insert into database
		$table_name = $this->database->get_table_name('commentary_sources');
		
		$result = $this->database->wpdb->insert(
			$table_name,
			$source_data
		);
		
		return $result ? $this->database->wpdb->insert_id : false;
	}
	
	/**
	 * Update a commentary author.
	 *
	 * @since    1.0.0
	 * @param    int       $author_id      Author ID
	 * @param    array     $author_data    Updated author data
	 * @return   bool                      True on success, false on failure
	 */
	public function update_commentary_author($author_id, $author_data) {
		if (empty($author_id)) {
			return false;
		}
		
		// Add updated timestamp
		$author_data['updated_at'] = current_time('mysql');
		
		$table_name = $this->database->get_table_name('commentary_authors');
		
		$result = $this->database->wpdb->update(
			$table_name,
			$author_data,
			array('id' => $author_id),
			null,
			array('%d')
		);
		
		return $result !== false;
	}
	
	/**
	 * Update a commentary source.
	 *
	 * @since    1.0.0
	 * @param    int       $source_id      Source ID
	 * @param    array     $source_data    Updated source data
	 * @return   bool                      True on success, false on failure
	 */
	public function update_commentary_source($source_id, $source_data) {
		if (empty($source_id)) {
			return false;
		}
		
		// Add updated timestamp
		$source_data['updated_at'] = current_time('mysql');
		
		$table_name = $this->database->get_table_name('commentary_sources');
		
		$result = $this->database->wpdb->update(
			$table_name,
			$source_data,
			array('id' => $source_id),
			null,
			array('%d')
		);
		
		return $result !== false;
	}

	/**
	 * Get a specific commentary source.
	 *
	 * @since    1.0.0
	 * @param    int       $source_id    Source ID
	 * @return   object|null             Source object or null
	 */
	public function get_commentary_source($source_id) {
		global $wpdb;

		$table_name = $this->database->get_table_name('commentary_sources');

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$source_id
			)
		);

		return $result;
	}

	/**
	 * Get the next available rank for a verse's commentaries.
	 *
	 * @since    1.0.0
	 * @param    string    $verse_id    Verse ID
	 * @return   int                    Next available rank
	 */
	private function get_next_commentary_rank($verse_id) {
		global $wpdb;

		$table_name = $this->database->get_table_name('commentaries');

		$max_rank = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(rank) FROM {$table_name} WHERE verse_id = %s",
				$verse_id
			)
		);

		return ($max_rank ? intval($max_rank) : 0) + 1;
	}

	/**
	 * Get commentary by ID
	 *
	 * @param int $commentary_id Commentary ID
	 * @return object|null Commentary object or null if not found
	 */
	public function get_commentary_by_id( $commentary_id ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'bible_here_commentaries';
		
		$sql = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE id = %d AND is_active = 1",
			$commentary_id
		);
		
		return $wpdb->get_row( $sql );
	}
	
	/**
	 * Search commentaries by text.
	 *
	 * @since    1.0.0
	 * @param    string    $search_text    Text to search for
	 * @param    array     $options        Search options
	 * @return   array                     Array of commentary objects
	 */
	public function search_commentaries($search_text, $options = array()) {
		global $wpdb;

		$defaults = array(
			'limit' => 20,
			'offset' => 0,
			'status' => 'published',
			'include_authors' => true,
			'include_sources' => true
		);

		$options = array_merge($defaults, $options);

		$table_name = $this->database->get_table_name('commentaries');

		$where_conditions = array(
			$wpdb->prepare('commentary LIKE %s', '%' . $wpdb->esc_like($search_text) . '%')
		);

		if ($options['status'] !== 'all') {
			$where_conditions[] = $wpdb->prepare('status = %s', $options['status']);
		}

		$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
		$limit_clause = $wpdb->prepare('LIMIT %d OFFSET %d', $options['limit'], $options['offset']);

		$results = $wpdb->get_results(
			"SELECT * FROM {$table_name} {$where_clause} ORDER BY verse_id, rank ASC {$limit_clause}"
		);

		if ($results && ($options['include_authors'] || $options['include_sources'])) {
			foreach ($results as $commentary) {
				if ($options['include_authors'] && !empty($commentary->author_id)) {
					$commentary->author = $this->get_commentary_author($commentary->author_id);
				}
				if ($options['include_sources'] && !empty($commentary->source_id)) {
					$commentary->source = $this->get_commentary_source($commentary->source_id);
				}
			}
		}

		return $results ? $results : array();
	}

	/**
	 * Get commentary statistics.
	 *
	 * @since    1.0.0
	 * @return   array    Array of statistics
	 */
	public function get_commentary_statistics() {
		global $wpdb;

		$table_name = $this->database->get_table_name('commentaries');

		$stats = array();

		// Total commentaries
		$stats['total_commentaries'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_name}"
		);

		// Published commentaries
		$stats['published_commentaries'] = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE status = %s",
				'published'
			)
		);

		// Commentaries by type
		$type_stats = $wpdb->get_results(
			"SELECT type, COUNT(*) as count FROM {$table_name} GROUP BY type"
		);

		$stats['by_type'] = array();
		foreach ($type_stats as $type_stat) {
			$stats['by_type'][$type_stat->type] = $type_stat->count;
		}

		return $stats;
	}

}