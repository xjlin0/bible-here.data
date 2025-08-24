<?php

/**
 * The book management functionality of the plugin.
 *
 * @link       https://github.com/xjlin0
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 */

/**
 * The book management functionality of the plugin.
 *
 * Provides functionality for managing Bible books, including
 * book information, abbreviations, and ordering.
 *
 * @since      1.0.0
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 * @author     Jack Lin <xjlin0@gmail.com>
 */
class Bible_Here_Book_Manager {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string                 $version     The version of this plugin.
	 * @param    Bible_Here_Database    $database    The database instance.
	 */
	public function __construct($version, $database) {
		$this->version = $version;
		$this->database = $database;
	}

	/**
	 * Get all books for a specific language.
	 *
	 * @since    1.0.0
	 * @param    string    $language    Language code (default: 'en')
	 * @param    string    $testament   Testament filter ('old', 'new', 'all')
	 * @return   array                  Array of book objects
	 */
	public function get_books($language = 'en', $testament = 'all') {
		$books = $this->database->get_books($language);

		if ($testament !== 'all') {
			$books = array_filter($books, function($book) use ($testament) {
				if ($testament === 'old') {
					return $book->book_number <= 39;
				} elseif ($testament === 'new') {
					return $book->book_number >= 40;
				}
				return true;
			});
		}

		return $books;
	}

	/**
	 * Get book by number.
	 *
	 * @since    1.0.0
	 * @param    int       $book_number    Book number (1-66)
	 * @param    string    $language       Language code
	 * @return   object|null               Book object or null if not found
	 */
	public function get_book_by_number($book_number, $language = 'en') {
		return $this->database->get_book_by_number($book_number, $language);
	}

	/**
	 * Get book by abbreviation.
	 *
	 * @since    1.0.0
	 * @param    string    $abbreviation    Book abbreviation
	 * @param    string    $language        Language code
	 * @return   object|null                Book object or null if not found
	 */
	public function get_book_by_abbreviation($abbreviation, $language = 'en') {
		return $this->database->get_book_by_abbreviation($abbreviation, $language);
	}

	/**
	 * Get all abbreviations for a book.
	 *
	 * @since    1.0.0
	 * @param    int       $book_number    Book number
	 * @param    string    $language       Language code
	 * @return   array                     Array of abbreviation strings
	 */
	public function get_book_abbreviations($book_number, $language = 'en') {
		global $wpdb;

		$table_name = $this->database->get_table_name('abbreviations');

		$results = $wpdb->get_results($wpdb->prepare(
			"SELECT abbreviation FROM {$table_name} 
			 WHERE book_number = %d AND language = %s
			 ORDER BY LENGTH(abbreviation) DESC",
			$book_number,
			$language
		));

		$abbreviations = array();
		foreach ($results as $result) {
			$abbreviations[] = $result->abbreviation;
		}

		return $abbreviations;
	}

	/**
	 * Get books grouped by testament.
	 *
	 * @since    1.0.0
	 * @param    string    $language    Language code
	 * @return   array                  Array with 'old' and 'new' testament books
	 */
	public function get_books_by_testament($language = 'en') {
		$all_books = $this->get_books($language);

		$testaments = array(
			'old' => array(),
			'new' => array()
		);

		foreach ($all_books as $book) {
			if ($book->book_number <= 39) {
				$testaments['old'][] = $book;
			} else {
				$testaments['new'][] = $book;
			}
		}

		return $testaments;
	}

	/**
	 * Get books grouped by genre.
	 *
	 * @since    1.0.0
	 * @param    string    $language    Language code
	 * @return   array                  Array grouped by genre
	 */
	public function get_books_by_genre($language = 'en') {
		global $wpdb;

		$books_table = $this->database->get_table_name('books');
		$genres_table = $this->database->get_table_name('genres');

		$results = $wpdb->get_results($wpdb->prepare(
			"SELECT b.*, g.genre_name, g.genre_name_local
			 FROM {$books_table} b
			 LEFT JOIN {$genres_table} g ON b.genre_id = g.genre_id AND g.language = %s
			 WHERE b.language = %s
			 ORDER BY b.book_number",
			$language,
			$language
		));

		$grouped = array();
		foreach ($results as $book) {
			$genre_name = $book->genre_name_local ?: $book->genre_name ?: 'Unknown';
			if (!isset($grouped[$genre_name])) {
				$grouped[$genre_name] = array();
			}
			$grouped[$genre_name][] = $book;
		}

		return $grouped;
	}

	/**
	 * Search books by name or abbreviation.
	 *
	 * @since    1.0.0
	 * @param    string    $search_term    Search term
	 * @param    string    $language       Language code
	 * @param    int       $limit          Maximum results to return
	 * @return   array                     Array of matching books
	 */
	public function search_books($search_term, $language = 'en', $limit = 10) {
		global $wpdb;

		$books_table = $this->database->get_table_name('books');
		$abbrev_table = $this->database->get_table_name('abbreviations');

		// Search in book names
		$book_results = $wpdb->get_results($wpdb->prepare(
			"SELECT *, 'book' as match_type, title_full as match_text
			 FROM {$books_table}
			 WHERE language = %s
			   AND (title_full LIKE %s OR title_short LIKE %s)
			 ORDER BY 
			   CASE 
			     WHEN title_full LIKE %s THEN 1
			     WHEN title_short LIKE %s THEN 2
			     ELSE 3
			   END,
			   book_number
			 LIMIT %d",
			$language,
			'%' . $wpdb->esc_like($search_term) . '%',
			'%' . $wpdb->esc_like($search_term) . '%',
			$wpdb->esc_like($search_term) . '%',
			$wpdb->esc_like($search_term) . '%',
			$limit
		));

		// Search in abbreviations
		$abbrev_results = $wpdb->get_results($wpdb->prepare(
			"SELECT b.*, 'abbreviation' as match_type, a.abbreviation as match_text
			 FROM {$books_table} b
			 JOIN {$abbrev_table} a ON b.book_number = a.book_number AND b.language = a.language
			 WHERE b.language = %s
			   AND a.abbreviation LIKE %s
			 ORDER BY 
			   CASE 
			     WHEN a.abbreviation = %s THEN 1
			     WHEN a.abbreviation LIKE %s THEN 2
			     ELSE 3
			   END,
			   LENGTH(a.abbreviation),
			   b.book_number
			 LIMIT %d",
			$language,
			'%' . $wpdb->esc_like($search_term) . '%',
			$search_term,
			$wpdb->esc_like($search_term) . '%',
			$limit
		));

		// Combine and deduplicate results
		$all_results = array_merge($book_results, $abbrev_results);
		$unique_results = array();
		$seen_books = array();

		foreach ($all_results as $result) {
			if (!in_array($result->book_number, $seen_books)) {
				$unique_results[] = $result;
				$seen_books[] = $result->book_number;
			}
		}

		return array_slice($unique_results, 0, $limit);
	}

	/**
	 * Get book statistics.
	 *
	 * @since    1.0.0
	 * @param    int       $book_number    Book number
	 * @param    string    $version        Version abbreviation
	 * @return   array|null                Book statistics or null if not found
	 */
	public function get_book_statistics($book_number, $version = 'kjv') {
		global $wpdb;

		$version_table = $this->database->get_table_name($version);

		// Check if version table exists
		if (!$this->database->table_exists($version_table)) {
			return null;
		}

		$stats = $wpdb->get_row($wpdb->prepare(
			"SELECT 
			   COUNT(DISTINCT chapter_number) as chapter_count,
			   COUNT(*) as verse_count,
			   MIN(chapter_number) as first_chapter,
			   MAX(chapter_number) as last_chapter,
			   AVG(LENGTH(verse)) as avg_verse_length,
			   SUM(LENGTH(verse)) as total_characters
			 FROM {$version_table}
			 WHERE book_number = %d",
			$book_number
		));

		if (!$stats) {
			return null;
		}

		// Get book information
		$book = $this->get_book_by_number($book_number);

		return array(
			'book_number' => $book_number,
			'book_name' => $book ? $book->title_full : 'Unknown',
			'version' => strtoupper($version),
			'chapter_count' => intval($stats->chapter_count),
			'verse_count' => intval($stats->verse_count),
			'first_chapter' => intval($stats->first_chapter),
			'last_chapter' => intval($stats->last_chapter),
			'avg_verse_length' => round(floatval($stats->avg_verse_length), 2),
			'total_characters' => intval($stats->total_characters)
		);
	}

	/**
	 * Get chapter information for a book.
	 *
	 * @since    1.0.0
	 * @param    int       $book_number    Book number
	 * @param    string    $version        Version abbreviation
	 * @return   array                     Array of chapter information
	 */
	public function get_book_chapters($book_number, $version = 'kjv') {
		global $wpdb;

		$version_table = $this->database->get_table_name($version);

		// Check if version table exists
		if (!$this->database->table_exists($version_table)) {
			return array();
		}

		$chapters = $wpdb->get_results($wpdb->prepare(
			"SELECT 
			   chapter_number,
			   COUNT(*) as verse_count,
			   MIN(verse_number) as first_verse,
			   MAX(verse_number) as last_verse
			 FROM {$version_table}
			 WHERE book_number = %d
			 GROUP BY chapter_number
			 ORDER BY chapter_number",
			$book_number
		));

		$chapter_info = array();
		foreach ($chapters as $chapter) {
			$chapter_info[] = array(
				'chapter_number' => intval($chapter->chapter_number),
				'verse_count' => intval($chapter->verse_count),
				'first_verse' => intval($chapter->first_verse),
				'last_verse' => intval($chapter->last_verse)
			);
		}

		return $chapter_info;
	}

	/**
	 * Validate book number.
	 *
	 * @since    1.0.0
	 * @param    int    $book_number    Book number to validate
	 * @return   bool                   True if valid, false otherwise
	 */
	public function is_valid_book_number($book_number) {
		return is_numeric($book_number) && $book_number >= 1 && $book_number <= 66;
	}

	/**
	 * Get testament for a book number.
	 *
	 * @since    1.0.0
	 * @param    int    $book_number    Book number
	 * @return   string                 'old' or 'new' testament
	 */
	public function get_book_testament($book_number) {
		return ($book_number <= 39) ? 'old' : 'new';
	}

	/**
	 * Get next book number.
	 *
	 * @since    1.0.0
	 * @param    int    $book_number    Current book number
	 * @return   int|null               Next book number or null if last book
	 */
	public function get_next_book($book_number) {
		if ($book_number < 66) {
			return $book_number + 1;
		}
		return null;
	}

	/**
	 * Get previous book number.
	 *
	 * @since    1.0.0
	 * @param    int    $book_number    Current book number
	 * @return   int|null               Previous book number or null if first book
	 */
	public function get_previous_book($book_number) {
		if ($book_number > 1) {
			return $book_number - 1;
		}
		return null;
	}

	/**
	 * Get popular books (most commonly referenced).
	 *
	 * @since    1.0.0
	 * @param    int       $limit       Number of books to return
	 * @param    string    $language    Language code
	 * @return   array                  Array of popular books
	 */
	public function get_popular_books($limit = 10, $language = 'en') {
		// Popular books based on common usage
		$popular_book_numbers = array(
			43, // John
			19, // Psalms
			40, // Matthew
			45, // Romans
			46, // 1 Corinthians
			1,  // Genesis
			23, // Isaiah
			20, // Proverbs
			50, // Philippians
			58, // Hebrews
			44, // Acts
			41, // Mark
			49, // Ephesians
			24, // Jeremiah
			42  // Luke
		);

		$popular_books = array();
		foreach (array_slice($popular_book_numbers, 0, $limit) as $book_number) {
			$book = $this->get_book_by_number($book_number, $language);
			if ($book) {
				$popular_books[] = $book;
			}
		}

		return $popular_books;
	}

}