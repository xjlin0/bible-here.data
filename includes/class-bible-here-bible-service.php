<?php

/**
 * The Bible content service functionality of the plugin.
 *
 * @link       https://github.com/xjlin0
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 */

/**
 * The Bible content service functionality of the plugin.
 *
 * Provides high-level Bible content services including verse retrieval,
 * parallel reading, search functionality, and content formatting.
 *
 * @since      1.0.0
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 * @author     Jack Lin <xjlin0@gmail.com>
 */
class Bible_Here_Bible_Service {

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
	 * Parse Bible reference string.
	 *
	 * @since    1.0.0
	 * @param    string    $reference    Bible reference (e.g., "John 3:16", "Gen 1:1-3")
	 * @param    string    $language     Language code
	 * @return   array|false             Parsed reference array or false on failure
	 */
	public function parse_reference($reference, $language = 'en') {
		// Remove extra spaces and normalize
		$reference = trim(preg_replace('/\s+/', ' ', $reference));

		// Pattern to match various Bible reference formats
		$patterns = array(
			// Book Chapter:Verse-Verse (e.g., "John 3:16-18")
			'/^([a-zA-Z0-9\s]+)\s+(\d+):(\d+)-(\d+)$/',
			// Book Chapter:Verse (e.g., "John 3:16")
			'/^([a-zA-Z0-9\s]+)\s+(\d+):(\d+)$/',
			// Book Chapter (e.g., "John 3")
			'/^([a-zA-Z0-9\s]+)\s+(\d+)$/',
		);

		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $reference, $matches)) {
				$book_name = trim($matches[1]);
				$chapter = intval($matches[2]);
				$verse_start = isset($matches[3]) ? intval($matches[3]) : null;
				$verse_end = isset($matches[4]) ? intval($matches[4]) : $verse_start;

				// Get book information
				$book = $this->database->get_book_by_abbreviation($book_name, $language);
				if (!$book) {
					return false;
				}

				return array(
					'book_number' => $book->book_number,
					'book_name' => $book->title_full,
					'book_short' => $book->title_short,
					'chapter' => $chapter,
					'verse_start' => $verse_start,
					'verse_end' => $verse_end,
					'language' => $language
				);
			}
		}

		return false;
	}

	/**
	 * Get Bible passage with formatting.
	 *
	 * @since    1.0.0
	 * @param    string    $reference       Bible reference
	 * @param    string    $version         Version abbreviation
	 * @param    array     $options         Formatting options
	 * @return   array|false               Formatted passage data or false on failure
	 */
	public function get_passage($reference, $version = 'kjv', $options = array()) {
		$default_options = array(
			'show_verse_numbers' => true,
			'show_reference' => true,
			'format' => 'html', // html, text, json
			'language' => 'en'
		);

		$options = array_merge($default_options, $options);

		// Parse the reference
		$parsed = $this->parse_reference($reference, $options['language']);
		if (!$parsed) {
			return false;
		}

		// Get verses from database
		$verses = $this->database->get_verses(
			$version,
			$parsed['book_number'],
			$parsed['chapter'],
			$parsed['verse_start'],
			$parsed['verse_end']
		);

		if (empty($verses)) {
			return false;
		}

		// Format the passage
		$formatted_passage = $this->format_passage($verses, $parsed, $options);

		return array(
			'reference' => $this->format_reference($parsed),
			'version' => strtoupper($version),
			'verses' => $verses,
			'formatted' => $formatted_passage,
			'metadata' => $parsed
		);
	}

	/**
	 * Get parallel passages from multiple versions.
	 *
	 * @since    1.0.0
	 * @param    string    $reference    Bible reference
	 * @param    array     $versions     Array of version abbreviations
	 * @param    array     $options      Formatting options
	 * @return   array                   Array of parallel passages
	 */
	public function get_parallel_passages($reference, $versions = array('kjv'), $options = array()) {
		$passages = array();

		foreach ($versions as $version) {
			$passage = $this->get_passage($reference, $version, $options);
			if ($passage) {
				$passages[$version] = $passage;
			}
		}

		return $passages;
	}

	/**
	 * Search Bible text across versions with advanced options.
	 *
	 * @since    1.0.0
	 * @param    string    $search_text    Text to search for
	 * @param    array     $options        Search options
	 * @return   array                     Search results with metadata
	 */
	public function search($search_text, $options = array()) {
		$default_options = array(
			'versions' => array('kjv'),
			'books' => array(),
			'limit' => 50,
			'offset' => 0,
			'search_mode' => 'natural', // natural, boolean, ngram
			'sort_by' => 'relevance', // relevance, reference
			'highlight' => true,
			'include_context' => false,
			'include_book_info' => true,
			'use_cache' => true
		);

		$options = array_merge($default_options, $options);

		// Start timing for performance monitoring
		$start_time = microtime(true);
		
		// Optimize search query
		$optimized_query = $this->optimize_search_query($search_text);
		
		// Generate cache key for search results
		$cache_key = $this->generate_search_cache_key($optimized_query, $options);
		
		// Check cache first if enabled
		if ($options['use_cache']) {
			$cached_result = get_transient($cache_key);
			if ($cached_result !== false) {
				// Log cache hit statistics
				$search_time = microtime(true) - $start_time;
				$this->log_search_statistics($search_text, count($cached_result['results'] ?? array()), $search_time, $options);
				return $cached_result;
			}
		}

		// Use the enhanced database search method
		$search_result = $this->database->search_verses($optimized_query, $options);

		// Pre-process results for better performance
		$search_result['results'] = $this->preprocess_search_results($search_result['results'], $search_text);
		
		// Add book information to results if requested
		if ($options['include_book_info']) {
			foreach ($search_result['results'] as $result) {
				$book = $this->database->get_book($result->book_number);
				if ($book) {
					$result->book_name = $book->name;
					$result->book_abbreviation = $book->abbreviation;
				}

				// Highlight search terms if requested
				if ($options['highlight']) {
					$result->highlighted_verse = $this->highlight_search_terms(
						$result->verse,
						$search_text
					);
				}

				// Add context verses if requested
				if ($options['include_context']) {
					$result->context = $this->get_verse_context(
						$result->version,
						$result->book_number,
						$result->chapter_number,
						$result->verse_number
					);
				}
			}
		}

		$final_result = array(
			'results' => $search_result['results'],
			'total' => $search_result['total'],
			'has_more' => $search_result['has_more'],
			'search_options' => $options,
			'search_text' => $search_text,
			'cached' => false
		);

		// Cache the results if enabled (cache for 1 hour)
		if ($options['use_cache']) {
			set_transient($cache_key, $final_result, HOUR_IN_SECONDS);
		}
		
		// Log search statistics
		$search_time = microtime(true) - $start_time;
		$this->log_search_statistics($search_text, count($final_result['results'] ?? array()), $search_time, $options);

		return $final_result;
	}

	/**
	 * Get context verses around a specific verse.
	 *
	 * @since    1.0.0
	 * @param    string    $version         Version abbreviation
	 * @param    int       $book_number     Book number
	 * @param    int       $chapter_number  Chapter number
	 * @param    int       $verse_number    Verse number
	 * @param    int       $context_size    Number of verses before and after
	 * @return   array                      Context verses
	 */
	public function get_verse_context($version, $book_number, $chapter_number, $verse_number, $context_size = 2) {
		$context_verses = array();

		// Get verses before
		for ($i = $context_size; $i >= 1; $i--) {
			$prev_verse = $this->database->get_verse(
				$version,
				$book_number,
				$chapter_number,
				$verse_number - $i
			);
			if ($prev_verse) {
				$context_verses[] = $prev_verse;
			}
		}

		// Get verses after
		for ($i = 1; $i <= $context_size; $i++) {
			$next_verse = $this->database->get_verse(
				$version,
				$book_number,
				$chapter_number,
				$verse_number + $i
			);
			if ($next_verse) {
				$context_verses[] = $next_verse;
			}
		}

		return $context_verses;
	}

	/**
	 * Advanced search with regex support and performance optimization.
	 *
	 * @since    1.0.0
	 * @param    string    $search_text    Text to search for
	 * @param    array     $options        Search options
	 * @return   array                     Search results with metadata
	 */
	public function search_verses($search_text, $options = array()) {
		$default_options = array(
			'versions' => array('kjv'),
			'books' => array(),
			'limit' => 20,
			'offset' => 0,
			'page' => 1,
			'search_mode' => 'natural',
			'sort_by' => 'relevance',
			'highlight' => true,
			'use_cache' => true,
			'regex_search' => false
		);

		$options = array_merge($default_options, $options);
		
		// Calculate offset from page if provided
		if ($options['page'] > 1) {
			$options['offset'] = ($options['page'] - 1) * $options['limit'];
		}

		// Handle regex search
		if ($options['regex_search']) {
			return $this->regex_search($search_text, $options);
		}

		// Use the standard search method
		return $this->search($search_text, $options);
	}

	/**
	 * Perform regex search on Bible text.
	 *
	 * @since    1.0.0
	 * @param    string    $pattern     Regex pattern
	 * @param    array     $options     Search options
	 * @return   array                  Search results
	 */
	private function regex_search($pattern, $options) {
		// Validate regex pattern
		if (@preg_match($pattern, '') === false) {
			return array(
				'results' => array(),
				'total' => 0,
				'has_more' => false,
				'error' => 'Invalid regex pattern',
				'search_text' => $pattern
			);
		}

		// Use database to get all verses, then filter with regex
		$search_options = array_merge($options, array(
			'search_mode' => 'natural',
			'use_cache' => false // Don't cache regex searches
		));

		// Get a broader set of results to filter
		$broad_search = $this->database->search_verses('', $search_options);
		$filtered_results = array();

		foreach ($broad_search['results'] as $result) {
			if (preg_match($pattern, $result->verse)) {
				if ($options['highlight']) {
					$result->highlighted_verse = preg_replace(
						$pattern,
						'<mark class="search-highlight">$0</mark>',
						$result->verse
					);
				}
				$filtered_results[] = $result;
			}
		}

		// Apply pagination
		$total = count($filtered_results);
		$paginated = array_slice($filtered_results, $options['offset'], $options['limit']);

		return array(
			'results' => $paginated,
			'total' => $total,
			'has_more' => ($options['offset'] + $options['limit']) < $total,
			'search_text' => $pattern,
			'regex_search' => true
		);
	}

	/**
	 * Get search suggestions based on partial input.
	 *
	 * @since    1.0.0
	 * @param    string    $partial_text    Partial search text
	 * @param    int       $limit          Maximum number of suggestions
	 * @return   array                     Array of search suggestions
	 */
	public function get_search_suggestions($partial_text, $limit = 10) {
		if (empty($partial_text) || strlen($partial_text) < 2) {
			return array();
		}
		
		// Generate cache key for suggestions
		$cache_key = 'bible_here_suggestions_' . md5($partial_text . '_' . $limit);
		
		// Check cache first
		$cached_suggestions = get_transient($cache_key);
		if ($cached_suggestions !== false) {
			return $cached_suggestions;
		}
		
		// Get suggestions from database
		$suggestions = $this->database->get_search_suggestions($partial_text, $limit);
		
		// Cache suggestions for 30 minutes
		set_transient($cache_key, $suggestions, 30 * MINUTE_IN_SECONDS);
		
		return $suggestions;
	}

	/**
	 * Save search to history.
	 *
	 * @since    1.0.0
	 * @param    string    $search_text    Search text
	 * @param    array     $options        Search options
	 * @param    int       $result_count   Number of results found
	 */
	public function save_search_history($search_text, $options = array(), $result_count = 0) {
		if (empty($search_text)) {
			return;
		}

		$user_id = get_current_user_id();
		$session_id = session_id();
		
		// Use user ID if logged in, otherwise use session ID
		$identifier = $user_id ? 'user_' . $user_id : 'session_' . $session_id;
		
		$history_key = 'bible_here_search_history_' . $identifier;
		$history = get_transient($history_key);
		
		if (!is_array($history)) {
			$history = array();
		}

		// Create search entry
		$search_entry = array(
			'text' => $search_text,
			'options' => $options,
			'result_count' => $result_count,
			'timestamp' => current_time('timestamp'),
			'date' => current_time('Y-m-d H:i:s')
		);

		// Remove duplicate searches (same text and options)
		$history = array_filter($history, function($entry) use ($search_text, $options) {
			return !($entry['text'] === $search_text && 
					 $entry['options']['versions'] === $options['versions'] &&
					 $entry['options']['books'] === $options['books']);
		});

		// Add new search to beginning
		array_unshift($history, $search_entry);

		// Keep only last 50 searches
		$history = array_slice($history, 0, 50);

		// Save for 30 days
		set_transient($history_key, $history, 30 * DAY_IN_SECONDS);
	}

	/**
	 * Get search history.
	 *
	 * @since    1.0.0
	 * @param    int    $limit    Number of entries to return
	 * @return   array            Search history entries
	 */
	public function get_search_history($limit = 20) {
		$user_id = get_current_user_id();
		$session_id = session_id();
		
		// Use user ID if logged in, otherwise use session ID
		$identifier = $user_id ? 'user_' . $user_id : 'session_' . $session_id;
		
		$history_key = 'bible_here_search_history_' . $identifier;
		$history = get_transient($history_key);
		
		if (!is_array($history)) {
			return array();
		}

		// Return limited results
		return array_slice($history, 0, $limit);
	}

	/**
	 * Clear search history.
	 *
	 * @since    1.0.0
	 */
	public function clear_search_history() {
		$user_id = get_current_user_id();
		$session_id = session_id();
		
		// Use user ID if logged in, otherwise use session ID
		$identifier = $user_id ? 'user_' . $user_id : 'session_' . $session_id;
		
		$history_key = 'bible_here_search_history_' . $identifier;
		delete_transient($history_key);
	}

	/**
	 * Get verse of the day.
	 *
	 * @since    1.0.0
	 * @param    string    $version    Version abbreviation
	 * @param    string    $date       Date in Y-m-d format (optional)
	 * @return   array|false           Verse data or false on failure
	 */
	public function get_verse_of_the_day($version = 'kjv', $date = null) {
		if (!$date) {
			$date = current_time('Y-m-d');
		}

		// Check cache first
		$cache_key = "bible_here_votd_{$version}_{$date}";
		$cached_verse = get_transient($cache_key);

		if ($cached_verse !== false) {
			return $cached_verse;
		}

		// Popular verses for verse of the day
		$popular_references = array(
			'John 3:16',
			'Romans 8:28',
			'Philippians 4:13',
			'Jeremiah 29:11',
			'Psalm 23:1',
			'Isaiah 40:31',
			'Proverbs 3:5-6',
			'Matthew 28:20',
			'1 Corinthians 13:4-7',
			'Romans 12:2'
		);

		// Use date to select a verse (deterministic but appears random)
		$day_of_year = date('z', strtotime($date));
		$reference_index = $day_of_year % count($popular_references);
		$reference = $popular_references[$reference_index];

		$verse_data = $this->get_passage($reference, $version);

		if ($verse_data) {
			// Cache for 24 hours
			set_transient($cache_key, $verse_data, DAY_IN_SECONDS);
		}

		return $verse_data;
	}

	/**
	 * Format passage for display.
	 *
	 * @since    1.0.0
	 * @param    array    $verses     Array of verse objects
	 * @param    array    $parsed     Parsed reference data
	 * @param    array    $options    Formatting options
	 * @return   string               Formatted passage
	 */
	private function format_passage($verses, $parsed, $options) {
		if ($options['format'] === 'json') {
			return json_encode($verses);
		}

		$formatted = '';
		$is_html = ($options['format'] === 'html');

		foreach ($verses as $verse) {
			if ($options['show_verse_numbers'] && $parsed['verse_start'] !== null) {
				if ($is_html) {
					$formatted .= '<sup class="verse-number">' . $verse->verse_number . '</sup> ';
				} else {
					$formatted .= $verse->verse_number . ' ';
				}
			}

			$formatted .= $verse->verse;

			// Add space between verses if multiple verses
			if (count($verses) > 1) {
				$formatted .= ' ';
			}
		}

		if ($is_html) {
			$formatted = '<span class="bible-passage">' . $formatted . '</span>';
		}

		return trim($formatted);
	}

	/**
	 * Format reference string.
	 *
	 * @since    1.0.0
	 * @param    array    $parsed    Parsed reference data
	 * @return   string             Formatted reference
	 */
	private function format_reference($parsed) {
		$reference = $parsed['book_name'] . ' ' . $parsed['chapter'];

		if ($parsed['verse_start'] !== null) {
			$reference .= ':' . $parsed['verse_start'];

			if ($parsed['verse_end'] !== null && $parsed['verse_end'] !== $parsed['verse_start']) {
				$reference .= '-' . $parsed['verse_end'];
			}
		}

		return $reference;
	}

	/**
	 * Format verse reference for search results.
	 *
	 * @since    1.0.0
	 * @param    object    $book     Book object
	 * @param    object    $verse    Verse object
	 * @return   string              Formatted reference
	 */
	private function format_verse_reference($book, $verse) {
		if (!$book) {
			return "Unknown {$verse->chapter_number}:{$verse->verse_number}";
		}

		return "{$book->title_full} {$verse->chapter_number}:{$verse->verse_number}";
	}

	/**
	 * Generate cache key for search results.
	 *
	 * @since    1.0.0
	 * @param    string    $query      Search query
	 * @param    array     $options    Search options
	 * @return   string                Cache key
	 */
	private function generate_search_cache_key($query, $options) {
		$key_data = array(
			'query' => $query,
			'versions' => $options['versions'],
			'books' => $options['books'],
			'search_mode' => $options['search_mode'],
			'sort_by' => $options['sort_by'],
			'limit' => $options['limit'],
			'offset' => $options['offset']
		);
		
		return 'bible_search_' . md5(serialize($key_data));
	}

	/**
	 * Optimize search query for better performance.
	 *
	 * @since    1.0.0
	 * @param    string    $query    Original search query
	 * @return   string              Optimized query
	 */
	private function optimize_search_query($query) {
		// Remove extra whitespace
		$query = trim(preg_replace('/\s+/', ' ', $query));
		
		// Remove common stop words for better search performance
		$stop_words = array('the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'a', 'an');
		$words = explode(' ', strtolower($query));
		$filtered_words = array();
		
		foreach ($words as $word) {
			$word = trim($word, '.,!?;:"()[]{}');
			if (strlen($word) > 2 && !in_array($word, $stop_words)) {
				$filtered_words[] = $word;
			}
		}
		
		// If all words were filtered out, return original query
		if (empty($filtered_words)) {
			return $query;
		}
		
		return implode(' ', $filtered_words);
	}

	/**
	 * Pre-process search results for better performance.
	 *
	 * @since    1.0.0
	 * @param    array     $results    Raw search results
	 * @param    string    $query      Search query
	 * @return   array                 Processed results
	 */
	private function preprocess_search_results($results, $query) {
		if (empty($results)) {
			return $results;
		}
		
		// Add relevance scoring for non-fulltext searches
		foreach ($results as &$result) {
			if (!isset($result->relevance) || $result->relevance == 1) {
				$result->relevance = $this->calculate_relevance_score($result->verse, $query);
			}
			
			// Add verse reference for easier display
			$book = $this->database->get_book_by_number($result->book_number);
			$result->reference = ($book ? $book->title_short : 'Book ' . $result->book_number) . 
								' ' . $result->chapter_number . ':' . $result->verse_number;
		}
		
		// Sort by relevance if not already sorted
		if (!empty($results) && isset($results[0]->relevance)) {
			usort($results, function($a, $b) {
				return $b->relevance <=> $a->relevance;
			});
		}
		
		return $results;
	}

	/**
	 * Calculate relevance score for a verse.
	 *
	 * @since    1.0.0
	 * @param    string    $verse    Verse text
	 * @param    string    $query    Search query
	 * @return   float               Relevance score
	 */
	private function calculate_relevance_score($verse, $query) {
		$verse_lower = strtolower($verse);
		$query_lower = strtolower($query);
		$words = explode(' ', $query_lower);
		
		$score = 0;
		$verse_length = strlen($verse_lower);
		
		foreach ($words as $word) {
			$word = trim($word, '.,!?;:"()[]{}');
			if (strlen($word) < 3) continue;
			
			// Exact word match gets higher score
			$exact_matches = substr_count($verse_lower, ' ' . $word . ' ') + 
							substr_count($verse_lower, $word . ' ') + 
							substr_count($verse_lower, ' ' . $word);
			$score += $exact_matches * 10;
			
			// Partial matches get lower score
			$partial_matches = substr_count($verse_lower, $word) - $exact_matches;
			$score += $partial_matches * 3;
		}
		
		// Normalize by verse length (shorter verses with matches rank higher)
		if ($verse_length > 0) {
			$score = $score * (200 / $verse_length);
		}
		
		return round($score, 2);
	}

	/**
	 * Log search statistics for performance monitoring.
	 *
	 * @since    1.0.0
	 * @param    string    $query         Search query
	 * @param    int       $result_count  Number of results
	 * @param    float     $search_time   Search execution time
	 * @param    array     $options       Search options
	 */
	private function log_search_statistics($query, $result_count, $search_time, $options) {
		// Only log if statistics are enabled
		if (!get_option('bible_here_enable_search_stats', false)) {
			return;
		}
		
		$stats = array(
			'query' => substr($query, 0, 100), // Limit query length
			'result_count' => $result_count,
			'search_time' => round($search_time, 4),
			'versions' => implode(',', $options['versions']),
			'search_mode' => $options['search_mode'],
			'timestamp' => current_time('mysql'),
			'user_id' => get_current_user_id(),
			'ip_address' => $this->get_client_ip()
		);
		
		// Store in transient for batch processing
		$existing_stats = get_transient('bible_here_search_stats') ?: array();
		$existing_stats[] = $stats;
		
		// Keep only last 100 entries
		if (count($existing_stats) > 100) {
			$existing_stats = array_slice($existing_stats, -100);
		}
		
		set_transient('bible_here_search_stats', $existing_stats, DAY_IN_SECONDS);
	}

	/**
	 * Get client IP address.
	 *
	 * @since    1.0.0
	 * @return   string    Client IP address
	 */
	private function get_client_ip() {
		$ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
		
		foreach ($ip_keys as $key) {
			if (array_key_exists($key, $_SERVER) === true) {
				foreach (explode(',', $_SERVER[$key]) as $ip) {
					$ip = trim($ip);
					if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
						return $ip;
					}
				}
			}
		}
		
		return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
	}

	/**
	 * Get search performance statistics.
	 *
	 * @since    1.0.0
	 * @return   array    Performance statistics
	 */
	public function get_search_performance_stats() {
		$stats = get_transient('bible_here_search_stats') ?: array();
		
		if (empty($stats)) {
			return array(
				'total_searches' => 0,
				'average_time' => 0,
				'average_results' => 0,
				'popular_queries' => array(),
				'search_modes' => array()
			);
		}
		
		$total_searches = count($stats);
		$total_time = array_sum(array_column($stats, 'search_time'));
		$total_results = array_sum(array_column($stats, 'result_count'));
		
		// Count popular queries
		$query_counts = array();
		foreach ($stats as $stat) {
			$query = strtolower(trim($stat['query']));
			if (!empty($query)) {
				$query_counts[$query] = ($query_counts[$query] ?? 0) + 1;
			}
		}
		arsort($query_counts);
		
		// Count search modes
		$mode_counts = array();
		foreach ($stats as $stat) {
			$mode = $stat['search_mode'];
			$mode_counts[$mode] = ($mode_counts[$mode] ?? 0) + 1;
		}
		
		return array(
			'total_searches' => $total_searches,
			'average_time' => $total_searches > 0 ? round($total_time / $total_searches, 4) : 0,
			'average_results' => $total_searches > 0 ? round($total_results / $total_searches, 1) : 0,
			'popular_queries' => array_slice($query_counts, 0, 10, true),
			'search_modes' => $mode_counts
		);
	}

	/**
	 * Clear all search-related caches.
	 *
	 * @since    1.0.0
	 * @return   bool    True on success, false on failure
	 */
	public function clear_search_cache() {
		global $wpdb;
		
		// Clear search result caches
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_bible_here_search_%'
			)
		);
		
		// Clear suggestion caches
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_bible_here_suggestions_%'
			)
		);
		
		// Clear timeout entries
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_timeout_bible_here_search_%'
			)
		);
		
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_timeout_bible_here_suggestions_%'
			)
		);
		
		return true;
	}

	/**
	 * Get cache statistics.
	 *
	 * @since    1.0.0
	 * @return   array    Cache statistics
	 */
	public function get_cache_statistics() {
		global $wpdb;
		
		// Count search cache entries
		$search_cache_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_bible_here_search_%'
			)
		);
		
		// Count suggestion cache entries
		$suggestion_cache_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_bible_here_suggestions_%'
			)
		);
		
		// Calculate cache size (approximate)
		$cache_size = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_bible_here_search_%',
				'_transient_bible_here_suggestions_%'
			)
		);
		
		return array(
			'search_cache_entries' => (int) $search_cache_count,
			'suggestion_cache_entries' => (int) $suggestion_cache_count,
			'total_cache_entries' => (int) $search_cache_count + (int) $suggestion_cache_count,
			'cache_size_bytes' => (int) $cache_size,
			'cache_size_mb' => round((int) $cache_size / 1024 / 1024, 2)
		);
	}

	/**
	 * Highlight search terms in text.
	 *
	 * @since    1.0.0
	 * @param    string    $text          Original text
	 * @param    string    $search_text   Text to highlight
	 * @return   string                   Text with highlighted search terms
	 */
	public function highlight_search_terms($text, $search_text) {
		// Split search text into individual terms
		$terms = preg_split('/\s+/', trim($search_text));
		$highlighted = $text;
		
		foreach ($terms as $term) {
			$term = trim($term, '"\'+*-()[]{}');
			if (strlen($term) > 1) {
				$highlighted = preg_replace(
					'/(' . preg_quote($term, '/') . ')/iu',
					'<mark class="search-highlight">$1</mark>',
					$highlighted
				);
			}
		}

		return $highlighted;
	}

	/**
	 * Highlight search text in verse (legacy method).
	 *
	 * @since    1.0.0
	 * @param    string    $text          Original text
	 * @param    string    $search_text   Text to highlight
	 * @return   string                   Text with highlighted search terms
	 */
	private function highlight_search_text($text, $search_text) {
		return $this->highlight_search_terms($text, $search_text);
	}

	/**
	 * Get context verses around a search result.
	 *
	 * @since    1.0.0
	 * @param    string    $version    Version abbreviation
	 * @param    object    $verse      Verse object
	 * @return   array                 Context verses
	 */
	private function get_verse_context($version, $verse) {
		$context_verses = array();

		// Get previous verse
		if ($verse->verse_number > 1) {
			$prev_verse = $this->database->get_verse(
				$version,
				$verse->book_number,
				$verse->chapter_number,
				$verse->verse_number - 1
			);
			if ($prev_verse) {
				$context_verses['previous'] = $prev_verse;
			}
		}

		// Get next verse
		$next_verse = $this->database->get_verse(
			$version,
			$verse->book_number,
			$verse->chapter_number,
			$verse->verse_number + 1
		);
		if ($next_verse) {
			$context_verses['next'] = $next_verse;
		}

		return $context_verses;
	}

	/**
	 * Get available Bible versions.
	 *
	 * @since    1.0.0
	 * @param    bool    $installed_only    Whether to return only installed versions
	 * @return   array                      Array of version data
	 */
	public function get_available_versions($installed_only = true) {
		return $this->database->get_versions($installed_only);
	}

	/**
	 * Get books list for a language.
	 *
	 * @since    1.0.0
	 * @param    string    $language    Language code
	 * @return   array                  Array of book data
	 */
	public function get_books_list($language = 'en') {
		return $this->database->get_books($language);
	}

	/**
	 * Validate Bible reference.
	 *
	 * @since    1.0.0
	 * @param    string    $reference    Bible reference to validate
	 * @param    string    $language     Language code
	 * @return   bool                    True if valid, false otherwise
	 */
	public function validate_reference($reference, $language = 'en') {
		$parsed = $this->parse_reference($reference, $language);
		return $parsed !== false;
	}

}