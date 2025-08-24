<?php

/**
 * The verse management functionality of the plugin.
 *
 * @link       https://github.com/xjlin0
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 */

/**
 * The verse management functionality of the plugin.
 *
 * Provides functionality for managing Bible verses, including
 * verse retrieval, caching, and formatting.
 *
 * @since      1.0.0
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 * @author     Jack Lin <xjlin0@gmail.com>
 */
class Bible_Here_Verse_Manager {

	/**
	 * The database instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Bible_Here_Database    $database    The database instance.
	 */
	private $database;

	/**
	 * The plugin version.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of the plugin.
	 */
	private $version;

	/**
	 * Cache duration in seconds.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $cache_duration    Cache duration.
	 */
	private $cache_duration;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string                 $version     The version of this plugin.
	 * @param    Bible_Here_Database    $database    The database instance.
	 */
	public function __construct($version, $database) {
		$this->version = $version;
		$this->database = $database;
		$this->cache_duration = get_option('bible_here_cache_duration', 3600); // 1 hour default
	}

	/**
	 * Get a single verse.
	 *
	 * @since    1.0.0
	 * @param    string    $version         Version abbreviation
	 * @param    int       $book_number     Book number
	 * @param    int       $chapter_number  Chapter number
	 * @param    int       $verse_number    Verse number
	 * @param    bool      $use_cache       Whether to use cache
	 * @return   object|null                Verse object or null if not found
	 */
	public function get_verse($version, $book_number, $chapter_number, $verse_number, $use_cache = true) {
		$cache_key = "bible_here_verse_{$version}_{$book_number}_{$chapter_number}_{$verse_number}";

		if ($use_cache) {
			$cached_verse = get_transient($cache_key);
			if ($cached_verse !== false) {
				return $cached_verse;
			}
		}

		$verse = $this->database->get_verse($version, $book_number, $chapter_number, $verse_number);

		if ($verse && $use_cache) {
			set_transient($cache_key, $verse, $this->cache_duration);
		}

		return $verse;
	}

	/**
	 * Get multiple verses.
	 *
	 * @since    1.0.0
	 * @param    string    $version         Version abbreviation
	 * @param    int       $book_number     Book number
	 * @param    int       $chapter_number  Chapter number
	 * @param    int       $verse_start     Starting verse number
	 * @param    int       $verse_end       Ending verse number (optional)
	 * @param    bool      $use_cache       Whether to use cache
	 * @return   array                      Array of verse objects
	 */
	public function get_verses($version, $book_number, $chapter_number, $verse_start, $verse_end = null, $use_cache = true) {
		if ($verse_end === null) {
			$verse_end = $verse_start;
		}

		$cache_key = "bible_here_verses_{$version}_{$book_number}_{$chapter_number}_{$verse_start}_{$verse_end}";

		if ($use_cache) {
			$cached_verses = get_transient($cache_key);
			if ($cached_verses !== false) {
				return $cached_verses;
			}
		}

		$verses = $this->database->get_verses($version, $book_number, $chapter_number, $verse_start, $verse_end);

		if (!empty($verses) && $use_cache) {
			set_transient($cache_key, $verses, $this->cache_duration);
		}

		return $verses;
	}

	/**
	 * Get entire chapter.
	 *
	 * @since    1.0.0
	 * @param    string    $version         Version abbreviation
	 * @param    int       $book_number     Book number
	 * @param    int       $chapter_number  Chapter number
	 * @param    bool      $use_cache       Whether to use cache
	 * @return   array                      Array of verse objects
	 */
	public function get_chapter($version, $book_number, $chapter_number, $use_cache = true) {
		$cache_key = "bible_here_chapter_{$version}_{$book_number}_{$chapter_number}";

		if ($use_cache) {
			$cached_chapter = get_transient($cache_key);
			if ($cached_chapter !== false) {
				return $cached_chapter;
			}
		}

		global $wpdb;
		$version_table = $this->database->get_table_name($version);

		$verses = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$version_table}
			 WHERE book_number = %d AND chapter_number = %d
			 ORDER BY verse_number",
			$book_number,
			$chapter_number
		));

		if (!empty($verses) && $use_cache) {
			set_transient($cache_key, $verses, $this->cache_duration);
		}

		return $verses;
	}

	/**
	 * Get random verse.
	 *
	 * @since    1.0.0
	 * @param    string    $version      Version abbreviation
	 * @param    array     $filters      Optional filters (testament, book_numbers, etc.)
	 * @return   object|null             Random verse object or null
	 */
	public function get_random_verse($version = 'kjv', $filters = array()) {
		global $wpdb;
		$version_table = $this->database->get_table_name($version);

		$where_conditions = array();
		$where_values = array();

		// Apply filters
		if (isset($filters['testament'])) {
			if ($filters['testament'] === 'old') {
				$where_conditions[] = 'book_number <= 39';
			} elseif ($filters['testament'] === 'new') {
				$where_conditions[] = 'book_number >= 40';
			}
		}

		if (isset($filters['book_numbers']) && is_array($filters['book_numbers'])) {
			$placeholders = implode(',', array_fill(0, count($filters['book_numbers']), '%d'));
			$where_conditions[] = "book_number IN ({$placeholders})";
			$where_values = array_merge($where_values, $filters['book_numbers']);
		}

		if (isset($filters['min_length'])) {
			$where_conditions[] = 'LENGTH(verse) >= %d';
			$where_values[] = intval($filters['min_length']);
		}

		$where_clause = '';
		if (!empty($where_conditions)) {
			$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
		}

		$query = "SELECT * FROM {$version_table} {$where_clause} ORDER BY RAND() LIMIT 1";

		if (!empty($where_values)) {
			$verse = $wpdb->get_row($wpdb->prepare($query, $where_values));
		} else {
			$verse = $wpdb->get_row($query);
		}

		return $verse;
	}

	/**
	 * Get verse context (surrounding verses).
	 *
	 * @since    1.0.0
	 * @param    string    $version         Version abbreviation
	 * @param    int       $book_number     Book number
	 * @param    int       $chapter_number  Chapter number
	 * @param    int       $verse_number    Verse number
	 * @param    int       $context_size    Number of verses before and after
	 * @return   array                      Array with 'before', 'current', 'after' verses
	 */
	public function get_verse_context($version, $book_number, $chapter_number, $verse_number, $context_size = 2) {
		$context = array(
			'before' => array(),
			'current' => null,
			'after' => array()
		);

		// Get current verse
		$context['current'] = $this->get_verse($version, $book_number, $chapter_number, $verse_number);

		// Get verses before
		for ($i = $context_size; $i >= 1; $i--) {
			$before_verse_num = $verse_number - $i;
			if ($before_verse_num >= 1) {
				$before_verse = $this->get_verse($version, $book_number, $chapter_number, $before_verse_num);
				if ($before_verse) {
					$context['before'][] = $before_verse;
				}
			}
		}

		// Get verses after
		for ($i = 1; $i <= $context_size; $i++) {
			$after_verse_num = $verse_number + $i;
			$after_verse = $this->get_verse($version, $book_number, $chapter_number, $after_verse_num);
			if ($after_verse) {
				$context['after'][] = $after_verse;
			}
		}

		return $context;
	}

	/**
	 * Get cross references for a verse.
	 *
	 * @since    1.0.0
	 * @param    int       $book_number     Book number
	 * @param    int       $chapter_number  Chapter number
	 * @param    int       $verse_number    Verse number
	 * @param    string    $version         Version for reference text
	 * @return   array                      Array of cross reference objects
	 */
	public function get_cross_references($book_number, $chapter_number, $verse_number, $version = 'kjv') {
		return $this->database->get_cross_references($book_number, $chapter_number, $verse_number, $version);
	}

	/**
	 * Get commentaries for a verse.
	 *
	 * @since    1.0.0
	 * @param    int       $book_number     Book number
	 * @param    int       $chapter_number  Chapter number
	 * @param    int       $verse_number    Verse number
	 * @param    string    $language        Language code
	 * @return   array                      Array of commentary objects
	 */
	public function get_commentaries($book_number, $chapter_number, $verse_number, $language = 'en') {
		return $this->database->get_commentaries($book_number, $chapter_number, $verse_number, $language);
	}

	/**
	 * Format verse for display.
	 *
	 * @since    1.0.0
	 * @param    object    $verse           Verse object
	 * @param    array     $options         Formatting options
	 * @return   string                     Formatted verse
	 */
	public function format_verse($verse, $options = array()) {
		$default_options = array(
			'show_verse_number' => true,
			'show_reference' => false,
			'format' => 'html', // html, text
			'verse_number_format' => 'superscript' // superscript, inline, none
		);

		$options = array_merge($default_options, $options);

		$formatted = '';
		$is_html = ($options['format'] === 'html');

		// Add reference if requested
		if ($options['show_reference']) {
			$book = $this->database->get_book_by_number($verse->book_number);
			$book_name = $book ? $book->title_full : 'Unknown';
			$reference = "{$book_name} {$verse->chapter_number}:{$verse->verse_number}";

			if ($is_html) {
				$formatted .= '<span class="verse-reference">' . esc_html($reference) . '</span> ';
			} else {
				$formatted .= $reference . ' ';
			}
		}

		// Add verse number if requested
		if ($options['show_verse_number'] && $options['verse_number_format'] !== 'none') {
			if ($is_html) {
				if ($options['verse_number_format'] === 'superscript') {
					$formatted .= '<sup class="verse-number">' . $verse->verse_number . '</sup> ';
				} else {
					$formatted .= '<span class="verse-number">' . $verse->verse_number . '</span> ';
				}
			} else {
				$formatted .= $verse->verse_number . ' ';
			}
		}

		// Add verse text
		if ($is_html) {
			$formatted .= '<span class="verse-text">' . esc_html($verse->verse) . '</span>';
		} else {
			$formatted .= $verse->verse;
		}

		return $formatted;
	}

	/**
	 * Format multiple verses for display.
	 *
	 * @since    1.0.0
	 * @param    array     $verses          Array of verse objects
	 * @param    array     $options         Formatting options
	 * @return   string                     Formatted verses
	 */
	public function format_verses($verses, $options = array()) {
		if (empty($verses)) {
			return '';
		}

		$default_options = array(
			'show_verse_numbers' => true,
			'show_reference' => true,
			'format' => 'html',
			'verse_separator' => ' ',
			'reference_position' => 'before' // before, after
		);

		$options = array_merge($default_options, $options);
		$is_html = ($options['format'] === 'html');

		$formatted = '';

		// Add reference before if requested
		if ($options['show_reference'] && $options['reference_position'] === 'before') {
			$first_verse = $verses[0];
			$last_verse = end($verses);
			$book = $this->database->get_book_by_number($first_verse->book_number);
			$book_name = $book ? $book->title_full : 'Unknown';

			if ($first_verse->verse_number === $last_verse->verse_number) {
				$reference = "{$book_name} {$first_verse->chapter_number}:{$first_verse->verse_number}";
			} else {
				$reference = "{$book_name} {$first_verse->chapter_number}:{$first_verse->verse_number}-{$last_verse->verse_number}";
			}

			if ($is_html) {
				$formatted .= '<span class="passage-reference">' . esc_html($reference) . '</span> ';
			} else {
				$formatted .= $reference . ' ';
			}
		}

		// Format verses
		if ($is_html) {
			$formatted .= '<span class="bible-passage">';
		}

		foreach ($verses as $index => $verse) {
			if ($index > 0) {
				$formatted .= $options['verse_separator'];
			}

			// Add verse number if requested
			if ($options['show_verse_numbers']) {
				if ($is_html) {
					$formatted .= '<sup class="verse-number">' . $verse->verse_number . '</sup> ';
				} else {
					$formatted .= $verse->verse_number . ' ';
				}
			}

			// Add verse text
			if ($is_html) {
				$formatted .= esc_html($verse->verse);
			} else {
				$formatted .= $verse->verse;
			}
		}

		if ($is_html) {
			$formatted .= '</span>';
		}

		// Add reference after if requested
		if ($options['show_reference'] && $options['reference_position'] === 'after') {
			$first_verse = $verses[0];
			$last_verse = end($verses);
			$book = $this->database->get_book_by_number($first_verse->book_number);
			$book_name = $book ? $book->title_full : 'Unknown';

			if ($first_verse->verse_number === $last_verse->verse_number) {
				$reference = "{$book_name} {$first_verse->chapter_number}:{$first_verse->verse_number}";
			} else {
				$reference = "{$book_name} {$first_verse->chapter_number}:{$first_verse->verse_number}-{$last_verse->verse_number}";
			}

			if ($is_html) {
				$formatted .= ' <cite class="passage-reference">(' . esc_html($reference) . ')</cite>';
			} else {
				$formatted .= ' (' . $reference . ')';
			}
		}

		return $formatted;
	}

	/**
	 * Clear verse cache.
	 *
	 * @since    1.0.0
	 * @param    string    $version    Version abbreviation (optional)
	 * @return   bool                  True on success
	 */
	public function clear_cache($version = null) {
		global $wpdb;

		if ($version) {
			// Clear cache for specific version
			$wpdb->query($wpdb->prepare(
				"DELETE FROM {$wpdb->options} 
				 WHERE option_name LIKE %s",
				'_transient_bible_here_%' . $wpdb->esc_like($version) . '%'
			));
		} else {
			// Clear all Bible Here caches
			$wpdb->query(
				"DELETE FROM {$wpdb->options} 
				 WHERE option_name LIKE '_transient_bible_here_%'"
			);
		}

		return true;
	}

	/**
	 * Get verse statistics.
	 *
	 * @since    1.0.0
	 * @param    string    $version    Version abbreviation
	 * @return   array                 Verse statistics
	 */
	public function get_verse_statistics($version = 'kjv') {
		global $wpdb;
		$version_table = $this->database->get_table_name($version);

		$stats = $wpdb->get_row(
			"SELECT 
			   COUNT(*) as total_verses,
			   COUNT(DISTINCT book_number) as total_books,
			   COUNT(DISTINCT CONCAT(book_number, '-', chapter_number)) as total_chapters,
			   AVG(LENGTH(verse)) as avg_verse_length,
			   MIN(LENGTH(verse)) as min_verse_length,
			   MAX(LENGTH(verse)) as max_verse_length,
			   SUM(LENGTH(verse)) as total_characters
			 FROM {$version_table}"
		);

		if (!$stats) {
			return array();
		}

		return array(
			'version' => strtoupper($version),
			'total_verses' => intval($stats->total_verses),
			'total_books' => intval($stats->total_books),
			'total_chapters' => intval($stats->total_chapters),
			'avg_verse_length' => round(floatval($stats->avg_verse_length), 2),
			'min_verse_length' => intval($stats->min_verse_length),
			'max_verse_length' => intval($stats->max_verse_length),
			'total_characters' => intval($stats->total_characters)
		);
	}

	/**
	 * Validate verse reference.
	 *
	 * @since    1.0.0
	 * @param    int       $book_number     Book number
	 * @param    int       $chapter_number  Chapter number
	 * @param    int       $verse_number    Verse number
	 * @param    string    $version         Version abbreviation
	 * @return   bool                       True if valid, false otherwise
	 */
	public function validate_verse_reference($book_number, $chapter_number, $verse_number, $version = 'kjv') {
		// Basic validation
		if (!is_numeric($book_number) || !is_numeric($chapter_number) || !is_numeric($verse_number)) {
			return false;
		}

		if ($book_number < 1 || $book_number > 66) {
			return false;
		}

		if ($chapter_number < 1 || $verse_number < 1) {
			return false;
		}

		// Check if verse exists in database
		$verse = $this->get_verse($version, $book_number, $chapter_number, $verse_number, false);
		return $verse !== null;
	}

}