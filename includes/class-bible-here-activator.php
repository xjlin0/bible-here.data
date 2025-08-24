<?php

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/xjlin0
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 * @author     Jack Lin <xjlin0@gmail.com>
 */
class Bible_Here_Activator {

	/**
	 * Plugin activation handler.
	 *
	 * Creates database tables and initializes default data.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		self::create_database_tables();
		self::initialize_default_data();
		self::set_default_options();
	}

	/**
	 * Create all required database tables.
	 *
	 * @since    1.0.0
	 */
	private static function create_database_tables() {
		global $wpdb;

		// Set charset and collation
		$charset_collate = $wpdb->get_charset_collate();
		if (empty($charset_collate)) {
			$charset_collate = 'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
		}

		// Include WordPress database upgrade functions
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		// Create books table
		self::create_books_table($charset_collate);

		// Create genres table
		self::create_genres_table($charset_collate);

		// Create abbreviations table
		self::create_abbreviations_table($charset_collate);

		// Create versions table
		self::create_versions_table($charset_collate);

		// Create commentaries table
		self::create_commentaries_table($charset_collate);

		// Create cross references table
		self::create_cross_references_table($charset_collate);

		// Create KJV verses table
		self::create_kjv_verses_table($charset_collate);

		// Create search index table
		self::create_search_index_table($charset_collate);

		// Create Strong Number tables
		self::create_strong_numbers_table($charset_collate);
		self::create_strong_definitions_table($charset_collate);
		self::create_verse_strong_numbers_table($charset_collate);
	}

	/**
	 * Create books table.
	 *
	 * @since    1.0.0
	 * @param    string    $charset_collate    Database charset and collation
	 */
	private static function create_books_table($charset_collate) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_books';

		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			language varchar(10) NOT NULL,
			genre_number int(11) NOT NULL,
			book_number int(11) NOT NULL,
			title_short varchar(50) NOT NULL,
			title_full varchar(100) NOT NULL,
			chapters int(11) NOT NULL,
			created_at timestamp DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_book (language, book_number),
			KEY idx_books_language (language),
			KEY idx_books_genre (genre_number)
		) $charset_collate;";

		dbDelta($sql);
	}

	/**
	 * Create genres table.
	 *
	 * @since    1.0.0
	 * @param    string    $charset_collate    Database charset and collation
	 */
	private static function create_genres_table($charset_collate) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_genres';

		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			language varchar(10) NOT NULL,
			type varchar(20) NOT NULL,
			genre_number int(11) NOT NULL,
			name varchar(50) NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY unique_genre (language, genre_number)
		) $charset_collate;";

		dbDelta($sql);
	}

	/**
	 * Create abbreviations table.
	 *
	 * @since    1.0.0
	 * @param    string    $charset_collate    Database charset and collation
	 */
	private static function create_abbreviations_table($charset_collate) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_abbreviations';

		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			language varchar(10) NOT NULL,
			abbreviation varchar(20) NOT NULL,
			book_number int(11) NOT NULL,
			is_primary tinyint(1) DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY unique_abbrev (language, abbreviation),
			KEY idx_abbrev_language (language),
			KEY idx_abbrev_book (book_number)
		) $charset_collate;";

		dbDelta($sql);
	}

	/**
	 * Create versions table.
	 *
	 * @since    1.0.0
	 * @param    string    $charset_collate    Database charset and collation
	 */
	private static function create_versions_table($charset_collate) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_versions';

		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			installed tinyint(1) DEFAULT 0,
			table_name varchar(100) NOT NULL,
			language varchar(10) NOT NULL,
			abbreviation varchar(20) NOT NULL,
			name varchar(100) NOT NULL,
			info_text text,
			info_url varchar(255),
			publisher varchar(100),
			copyright text,
			download_url varchar(255),
			created_at timestamp DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_version (language, abbreviation)
		) $charset_collate;";

		dbDelta($sql);
	}

	/**
	 * Create commentaries table.
	 *
	 * @since    1.0.0
	 * @param    string    $charset_collate    Database charset and collation
	 */
	private static function create_commentaries_table($charset_collate) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_commentaries';

		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			verse_id varchar(20) NOT NULL,
			book_number int(11) NOT NULL,
			chapter_number int(11) NOT NULL,
			verse_number int(11) NOT NULL,
			author_id int(11),
			source_id int(11),
			commentary text NOT NULL,
			commentary_type enum('verse','chapter','book','topical') DEFAULT 'verse',
			language varchar(10) DEFAULT 'en',
			rank int(11) NOT NULL DEFAULT 1,
			is_active tinyint(1) DEFAULT 1,
			created_at timestamp DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_verse_id (verse_id),
			KEY idx_book_chapter_verse (book_number, chapter_number, verse_number),
			KEY idx_author (author_id),
			KEY idx_source (source_id),
			KEY idx_type (commentary_type),
			KEY idx_language (language),
			FULLTEXT KEY idx_commentary_text (commentary)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql);

		// Create commentary authors table
		self::create_commentary_authors_table($charset_collate);
		
		// Create commentary sources table
		self::create_commentary_sources_table($charset_collate);
	}

	/**
	 * Create commentary authors table.
	 *
	 * @since    1.0.0
	 * @param    string    $charset_collate    Database charset and collation
	 */
	private static function create_commentary_authors_table($charset_collate) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_commentary_authors';

		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			full_name varchar(500),
			bio text,
			birth_year int(11),
			death_year int(11),
			nationality varchar(100),
			denomination varchar(100),
			website varchar(255),
			is_active tinyint(1) DEFAULT 1,
			created_at timestamp DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_name (name),
			KEY idx_nationality (nationality),
			KEY idx_denomination (denomination)
		) $charset_collate;";

		dbDelta($sql);
	}

	/**
	 * Create commentary sources table.
	 *
	 * @since    1.0.0
	 * @param    string    $charset_collate    Database charset and collation
	 */
	private static function create_commentary_sources_table($charset_collate) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_commentary_sources';

		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			full_name varchar(500),
			abbreviation varchar(50),
			description text,
			publisher varchar(255),
			publication_year int(11),
			isbn varchar(20),
			url varchar(255),
			copyright text,
			language varchar(10) DEFAULT 'en',
			source_type enum('book','commentary','study_bible','devotional','sermon','article') DEFAULT 'commentary',
			is_active tinyint(1) DEFAULT 1,
			created_at timestamp DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_name (name),
			KEY idx_abbreviation (abbreviation),
			KEY idx_language (language),
			KEY idx_source_type (source_type),
			KEY idx_publisher (publisher)
		) $charset_collate;";

		dbDelta($sql);
	}

	/**
	 * Create cross references table.
	 *
	 * @since    1.0.0
	 * @param    string    $charset_collate    Database charset and collation
	 */
	private static function create_cross_references_table($charset_collate) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_cross_references';

		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			verse_id varchar(20) NOT NULL,
			book_number int(11) NOT NULL,
			chapter_number int(11) NOT NULL,
			verse_number int(11) NOT NULL,
			ref_verse_id varchar(20) NOT NULL,
			ref_book_number int(11) NOT NULL,
			ref_chapter_number int(11) NOT NULL,
			ref_verse_number int(11) NOT NULL,
			ref_verse_end int(11),
			reference_type enum('parallel','theme','word','concept','prophecy','fulfillment','contrast','illustration') DEFAULT 'parallel',
			strength int(11) DEFAULT 5,
			notes text,
			source varchar(100),
			rank int(11) NOT NULL DEFAULT 1,
			is_active tinyint(1) DEFAULT 1,
			created_at timestamp DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_verse_id (verse_id),
			KEY idx_book_chapter_verse (book_number, chapter_number, verse_number),
			KEY idx_ref_verse_id (ref_verse_id),
			KEY idx_ref_book_chapter_verse (ref_book_number, ref_chapter_number, ref_verse_number),
			KEY idx_reference_type (reference_type),
			KEY idx_strength (strength),
			KEY idx_source (source),
			KEY idx_rank (rank)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql);
	}

	/**
	 * Create KJV verses table.
	 *
	 * @since    1.0.0
	 * @param    string    $charset_collate    Database charset and collation
	 */
	private static function create_kjv_verses_table($charset_collate) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_en_kjv';

		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			language varchar(10) NOT NULL DEFAULT 'en',
			book_number int(11) NOT NULL,
			chapter_number int(11) NOT NULL,
			verse_number int(11) NOT NULL,
			verse text NOT NULL,
			strong_verse text,
			created_at timestamp DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_verse (book_number, chapter_number, verse_number),
			KEY idx_verse_book_chapter (book_number, chapter_number),
			FULLTEXT KEY idx_verse_fulltext (verse)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql);
	}

	/**
	 * Create search index table.
	 *
	 * @since    1.0.0
	 * @param    string    $charset_collate    Database charset and collation
	 */
	private static function create_search_index_table($charset_collate) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_search_index';

		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			version varchar(20) NOT NULL,
			book_number int(11) NOT NULL,
			chapter_number int(11) NOT NULL,
			verse_number int(11) NOT NULL,
			content text NOT NULL,
			last_updated timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_search_verse (version, book_number, chapter_number, verse_number),
			FULLTEXT KEY idx_search_content (content)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql);
	}

	/**
	 * Create Strong Numbers table.
	 *
	 * @since    1.0.0
	 * @param    string    $charset_collate    Database charset and collation
	 */
	private static function create_strong_numbers_table($charset_collate) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_strong_numbers';

		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			strong_number varchar(10) NOT NULL,
			language enum('hebrew','greek') NOT NULL,
			original_word varchar(100) NOT NULL,
			transliteration varchar(100),
			pronunciation varchar(100),
			part_of_speech varchar(50),
			root_word varchar(100),
			is_active tinyint(1) DEFAULT 1,
			created_at timestamp DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_strong_number (strong_number),
			KEY idx_strong_language (language),
			KEY idx_strong_active (is_active),
			KEY idx_strong_original (original_word),
			KEY idx_strong_transliteration (transliteration)
		) $charset_collate;";

		dbDelta($sql);
	}

	/**
	 * Create Strong Definitions table.
	 *
	 * @since    1.0.0
	 * @param    string    $charset_collate    Database charset and collation
	 */
	private static function create_strong_definitions_table($charset_collate) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_strong_definitions';

		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			strong_number varchar(10) NOT NULL,
			definition_type enum('brief','detailed','etymology') NOT NULL DEFAULT 'brief',
			definition text NOT NULL,
			usage_notes text,
			related_words text,
			example_verses text,
			source varchar(100),
			is_active tinyint(1) DEFAULT 1,
			created_at timestamp DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_strong_def_number (strong_number),
			KEY idx_strong_def_type (definition_type),
			KEY idx_strong_def_active (is_active),
			FULLTEXT KEY idx_strong_def_content (definition, usage_notes)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql);
	}

	/**
	 * Create Verse Strong Numbers table.
	 *
	 * @since    1.0.0
	 * @param    string    $charset_collate    Database charset and collation
	 */
	private static function create_verse_strong_numbers_table($charset_collate) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_verse_strong_numbers';

		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			version varchar(20) NOT NULL DEFAULT 'kjv',
			book_number int(11) NOT NULL,
			chapter_number int(11) NOT NULL,
			verse_number int(11) NOT NULL,
			word_position int(11) NOT NULL,
			word_text varchar(100) NOT NULL,
			strong_number varchar(10) NOT NULL,
			morph_code varchar(20),
			is_active tinyint(1) DEFAULT 1,
			created_at timestamp DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_verse_strong_verse (version, book_number, chapter_number, verse_number),
			KEY idx_verse_strong_number (strong_number),
			KEY idx_verse_strong_word (word_text),
			KEY idx_verse_strong_position (word_position),
			KEY idx_verse_strong_active (is_active)
		) $charset_collate;";

		dbDelta($sql);
	}

	/**
	 * Initialize default data.
	 *
	 * @since    1.0.0
	 */
	private static function initialize_default_data() {
		self::insert_default_genres();
		self::insert_default_books();
		self::insert_default_abbreviations();
		self::insert_kjv_version_info();
		self::insert_sample_kjv_verses();
	}

	/**
	 * Insert default genres.
	 *
	 * @since    1.0.0
	 */
	private static function insert_default_genres() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_genres';

		$genres = array(
			array('en', 'ot', 1, 'Law'),
			array('en', 'ot', 2, 'History'),
			array('en', 'ot', 3, 'Wisdom'),
			array('en', 'ot', 4, 'Prophets'),
			array('en', 'nt', 5, 'Gospel'),
			array('en', 'nt', 6, 'History'),
			array('en', 'nt', 7, 'Epistles'),
			array('en', 'nt', 8, 'Prophecy')
		);

		foreach ($genres as $genre) {
			$wpdb->insert(
				$table_name,
				array(
					'language' => $genre[0],
					'type' => $genre[1],
					'genre_number' => $genre[2],
					'name' => $genre[3]
				)
			);
		}
	}

	/**
	 * Insert default books.
	 *
	 * @since    1.0.0
	 */
	private static function insert_default_books() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_books';

		// Sample books (first few books of the Bible)
		$books = array(
			array('en', 1, 1, 'Gen', 'Genesis', 50),
			array('en', 1, 2, 'Exo', 'Exodus', 40),
			array('en', 1, 3, 'Lev', 'Leviticus', 27),
			array('en', 1, 4, 'Num', 'Numbers', 36),
			array('en', 1, 5, 'Deu', 'Deuteronomy', 34),
			array('en', 5, 40, 'Mat', 'Matthew', 28),
			array('en', 5, 41, 'Mar', 'Mark', 16),
			array('en', 5, 42, 'Luk', 'Luke', 24),
			array('en', 5, 43, 'Joh', 'John', 21)
		);

		foreach ($books as $book) {
			$wpdb->insert(
				$table_name,
				array(
					'language' => $book[0],
					'genre_number' => $book[1],
					'book_number' => $book[2],
					'title_short' => $book[3],
					'title_full' => $book[4],
					'chapters' => $book[5]
				)
			);
		}
	}

	/**
	 * Insert default abbreviations.
	 *
	 * @since    1.0.0
	 */
	private static function insert_default_abbreviations() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_abbreviations';

		$abbreviations = array(
			array('en', 'Gen', 1, 1),
			array('en', 'Genesis', 1, 0),
			array('en', 'Exo', 2, 1),
			array('en', 'Exodus', 2, 0),
			array('en', 'Lev', 3, 1),
			array('en', 'Leviticus', 3, 0),
			array('en', 'Mat', 40, 1),
			array('en', 'Matthew', 40, 0),
			array('en', 'Joh', 43, 1),
			array('en', 'John', 43, 0)
		);

		foreach ($abbreviations as $abbrev) {
			$wpdb->insert(
				$table_name,
				array(
					'language' => $abbrev[0],
					'abbreviation' => $abbrev[1],
					'book_number' => $abbrev[2],
					'is_primary' => $abbrev[3]
				)
			);
		}
	}

	/**
	 * Insert KJV version info.
	 *
	 * @since    1.0.0
	 */
	private static function insert_kjv_version_info() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_versions';

		$wpdb->insert(
			$table_name,
			array(
				'installed' => 1,
				'table_name' => $wpdb->prefix . 'bible_here_en_kjv',
				'language' => 'en',
				'abbreviation' => 'kjv',
				'name' => 'King James Version',
				'info_text' => 'The King James Version (KJV) is an English translation of the Christian Bible for the Church of England.',
				'publisher' => 'Public Domain',
				'copyright' => 'Public Domain',
				'download_url' => 'https://github.com/biblenerd/Zefania-XML-Preservation/raw/main/bibles/kjv.xml'
			)
		);
	}

	/**
	 * Insert sample KJV verses.
	 *
	 * @since    1.0.0
	 */
	private static function insert_sample_kjv_verses() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bible_here_en_kjv';

		$verses = array(
			array(1, 1, 1, 'In the beginning God created the heaven and the earth.'),
			array(1, 1, 2, 'And the earth was without form, and void; and darkness was upon the face of the deep. And the Spirit of God moved upon the face of the waters.'),
			array(1, 1, 3, 'And God said, Let there be light: and there was light.'),
			array(43, 3, 16, 'For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life.'),
			array(43, 1, 1, 'In the beginning was the Word, and the Word was with God, and the Word was God.')
		);

		foreach ($verses as $verse) {
			$wpdb->insert(
				$table_name,
				array(
					'book_number' => $verse[0],
					'chapter_number' => $verse[1],
					'verse_number' => $verse[2],
					'verse' => $verse[3]
				)
			);
		}
	}

	/**
	 * Set default plugin options.
	 *
	 * @since    1.0.0
	 */
	private static function set_default_options() {
		add_option('bible_here_default_version', 'kjv');
		add_option('bible_here_auto_detect', 1);
		add_option('bible_here_show_verse_numbers', 1);
		add_option('bible_here_parallel_display', 1);
		add_option('bible_here_search_enabled', 1);
		add_option('bible_here_cache_duration', 3600);
	}

}
