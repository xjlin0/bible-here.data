<?php

/**
 * The data import functionality of the plugin.
 *
 * @link       https://github.com/xjlin0
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 */

/**
 * The data import functionality of the plugin.
 *
 * Provides functionality for importing Bible data, including
 * KJV baseline data and other Bible versions.
 *
 * @since      1.0.0
 * @package    Bible_Here
 * @subpackage Bible_Here/includes
 * @author     Jack Lin <xjlin0@gmail.com>
 */
class Bible_Here_Data_Importer {

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
	 * Import KJV baseline data.
	 *
	 * @since    1.0.0
	 * @return   array    Import results with success/error counts
	 */
	public function import_kjv_baseline() {
		$results = array(
			'success' => 0,
			'errors' => 0,
			'messages' => array()
		);

		try {
			// Import sample KJV verses for demonstration
			$sample_verses = $this->get_kjv_sample_data();

			global $wpdb;
			$table_name = $this->database->get_table_name('en_kjv');

			foreach ($sample_verses as $verse_data) {
				$result = $wpdb->insert(
					$table_name,
					array(
						'book_number' => $verse_data['book_number'],
						'chapter_number' => $verse_data['chapter_number'],
						'verse_number' => $verse_data['verse_number'],
						'verse' => $verse_data['verse']
					),
					array('%d', '%d', '%d', '%s')
				);

				if ($result !== false) {
					$results['success']++;
				} else {
					$results['errors']++;
					$results['messages'][] = "Failed to import verse: {$verse_data['book_number']}:{$verse_data['chapter_number']}:{$verse_data['verse_number']}";
				}
			}

			// Update version status
			if ($results['success'] > 0) {
				$this->database->update_version_status('kjv', true);
				$results['messages'][] = "Successfully imported {$results['success']} KJV verses";
			}

		} catch (Exception $e) {
			$results['errors']++;
			$results['messages'][] = 'Import failed: ' . $e->getMessage();
		}

		return $results;
	}

	/**
	 * Get KJV sample data for initial import.
	 *
	 * @since    1.0.0
	 * @return   array    Array of sample verse data
	 */
	private function get_kjv_sample_data() {
		return array(
			// Genesis 1:1-3
			array(
				'book_number' => 1,
				'chapter_number' => 1,
				'verse_number' => 1,
				'verse' => 'In the beginning God created the heaven and the earth.'
			),
			array(
				'book_number' => 1,
				'chapter_number' => 1,
				'verse_number' => 2,
				'verse' => 'And the earth was without form, and void; and darkness was upon the face of the deep. And the Spirit of God moved upon the face of the waters.'
			),
			array(
				'book_number' => 1,
				'chapter_number' => 1,
				'verse_number' => 3,
				'verse' => 'And God said, Let there be light: and there was light.'
			),
			// John 3:16
			array(
				'book_number' => 43,
				'chapter_number' => 3,
				'verse_number' => 16,
				'verse' => 'For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life.'
			),
			// Psalm 23:1-4
			array(
				'book_number' => 19,
				'chapter_number' => 23,
				'verse_number' => 1,
				'verse' => 'The LORD is my shepherd; I shall not want.'
			),
			array(
				'book_number' => 19,
				'chapter_number' => 23,
				'verse_number' => 2,
				'verse' => 'He maketh me to lie down in green pastures: he leadeth me beside the still waters.'
			),
			array(
				'book_number' => 19,
				'chapter_number' => 23,
				'verse_number' => 3,
				'verse' => 'He restoreth my soul: he leadeth me in the paths of righteousness for his name\'s sake.'
			),
			array(
				'book_number' => 19,
				'chapter_number' => 23,
				'verse_number' => 4,
				'verse' => 'Yea, though I walk through the valley of the shadow of death, I will fear no evil: for thou art with me; thy rod and thy staff they comfort me.'
			),
			// Romans 8:28
			array(
				'book_number' => 45,
				'chapter_number' => 8,
				'verse_number' => 28,
				'verse' => 'And we know that all things work together for good to them that love God, to them who are the called according to his purpose.'
			),
			// Philippians 4:13
			array(
				'book_number' => 50,
				'chapter_number' => 4,
				'verse_number' => 13,
				'verse' => 'I can do all things through Christ which strengtheneth me.'
			),
			// Matthew 28:19-20
			array(
				'book_number' => 40,
				'chapter_number' => 28,
				'verse_number' => 19,
				'verse' => 'Go ye therefore, and teach all nations, baptizing them in the name of the Father, and of the Son, and of the Holy Ghost:'
			),
			array(
				'book_number' => 40,
				'chapter_number' => 28,
				'verse_number' => 20,
				'verse' => 'Teaching them to observe all things whatsoever I have commanded you: and, lo, I am with you alway, even unto the end of the world. Amen.'
			),
			// 1 Corinthians 13:4-7
			array(
				'book_number' => 46,
				'chapter_number' => 13,
				'verse_number' => 4,
				'verse' => 'Charity suffereth long, and is kind; charity envieth not; charity vaunteth not itself, is not puffed up,'
			),
			array(
				'book_number' => 46,
				'chapter_number' => 13,
				'verse_number' => 5,
				'verse' => 'Doth not behave itself unseemly, seeketh not her own, is not easily provoked, thinketh no evil;'
			),
			array(
				'book_number' => 46,
				'chapter_number' => 13,
				'verse_number' => 6,
				'verse' => 'Rejoiceth not in iniquity, but rejoiceth in the truth;'
			),
			array(
				'book_number' => 46,
				'chapter_number' => 13,
				'verse_number' => 7,
				'verse' => 'Beareth all things, believeth all things, hopeth all things, endureth all things.'
			)
		);
	}

	/**
	 * Import Bible version from CSV file.
	 *
	 * @since    1.0.0
	 * @param    string    $file_path       Path to CSV file
	 * @param    string    $version_abbrev  Version abbreviation
	 * @param    array     $options         Import options
	 * @return   array                      Import results
	 */
	public function import_from_csv($file_path, $version_abbrev, $options = array()) {
		$results = array(
			'success' => 0,
			'errors' => 0,
			'messages' => array()
		);

		if (!file_exists($file_path)) {
			$results['errors']++;
			$results['messages'][] = 'CSV file not found: ' . $file_path;
			return $results;
		}

		$default_options = array(
			'delimiter' => ',',
			'enclosure' => '"',
			'escape' => '\\',
			'skip_header' => true,
			'batch_size' => 1000
		);

		$options = array_merge($default_options, $options);

		try {
			$handle = fopen($file_path, 'r');
			if (!$handle) {
				$results['errors']++;
				$results['messages'][] = 'Cannot open CSV file: ' . $file_path;
				return $results;
			}

			// Skip header if requested
			if ($options['skip_header']) {
				fgetcsv($handle, 0, $options['delimiter'], $options['enclosure'], $options['escape']);
			}

			global $wpdb;
			$table_name = $this->database->get_table_name($version_abbrev);

			$batch_data = array();
			$row_count = 0;

			while (($data = fgetcsv($handle, 0, $options['delimiter'], $options['enclosure'], $options['escape'])) !== false) {
				if (count($data) < 4) {
					$results['errors']++;
					continue;
				}

				$batch_data[] = array(
					'book_number' => intval($data[0]),
					'chapter_number' => intval($data[1]),
					'verse_number' => intval($data[2]),
					'verse' => $data[3]
				);

				$row_count++;

				// Process batch
				if (count($batch_data) >= $options['batch_size']) {
					$batch_results = $this->insert_verse_batch($table_name, $batch_data);
					$results['success'] += $batch_results['success'];
					$results['errors'] += $batch_results['errors'];
					$batch_data = array();
				}
			}

			// Process remaining data
			if (!empty($batch_data)) {
				$batch_results = $this->insert_verse_batch($table_name, $batch_data);
				$results['success'] += $batch_results['success'];
				$results['errors'] += $batch_results['errors'];
			}

			fclose($handle);

			// Update version status
			if ($results['success'] > 0) {
				$this->database->update_version_status($version_abbrev, true);
				$results['messages'][] = "Successfully imported {$results['success']} verses for {$version_abbrev}";
			}

		} catch (Exception $e) {
			$results['errors']++;
			$results['messages'][] = 'Import failed: ' . $e->getMessage();
		}

		return $results;
	}

	/**
	 * Import Bible version from Zefania XML format.
	 *
	 * @since    1.0.0
	 * @param    string    $file_path       Path to XML file
	 * @param    string    $version_abbrev  Version abbreviation
	 * @param    array     $options         Import options
	 * @return   array                      Import results
	 */
	public function import_from_zefania_xml($file_path, $version_abbrev, $options = array()) {
		$results = array(
			'success' => 0,
			'errors' => 0,
			'messages' => array()
		);

		if (!file_exists($file_path)) {
			$results['errors']++;
			$results['messages'][] = 'XML file not found: ' . $file_path;
			return $results;
		}

		try {
			$xml = simplexml_load_file($file_path);
			if (!$xml) {
				$results['errors']++;
				$results['messages'][] = 'Invalid XML file: ' . $file_path;
				return $results;
			}

			global $wpdb;
			$table_name = $this->database->get_table_name($version_abbrev);

			// Parse Zefania XML structure
			foreach ($xml->BIBLEBOOK as $book) {
				$book_number = intval($book['bnumber']);

				foreach ($book->CHAPTER as $chapter) {
					$chapter_number = intval($chapter['cnumber']);

					foreach ($chapter->VERS as $verse) {
						$verse_number = intval($verse['vnumber']);
						$verse_text = (string) $verse;

						$result = $wpdb->insert(
							$table_name,
							array(
								'book_number' => $book_number,
								'chapter_number' => $chapter_number,
								'verse_number' => $verse_number,
								'verse' => $verse_text
							),
							array('%d', '%d', '%d', '%s')
						);

						if ($result !== false) {
							$results['success']++;
						} else {
							$results['errors']++;
						}
					}
				}
			}

			// Update version status
			if ($results['success'] > 0) {
				$this->database->update_version_status($version_abbrev, true);
				$results['messages'][] = "Successfully imported {$results['success']} verses from Zefania XML";
			}

		} catch (Exception $e) {
			$results['errors']++;
			$results['messages'][] = 'XML import failed: ' . $e->getMessage();
		}

		return $results;
	}

	/**
	 * Insert batch of verses into database.
	 *
	 * @since    1.0.0
	 * @param    string    $table_name    Table name
	 * @param    array     $verses        Array of verse data
	 * @return   array                    Batch insert results
	 */
	private function insert_verse_batch($table_name, $verses) {
		$results = array(
			'success' => 0,
			'errors' => 0
		);

		global $wpdb;

		// Build bulk insert query
		$values = array();
		$placeholders = array();

		foreach ($verses as $verse) {
			$values[] = $verse['book_number'];
			$values[] = $verse['chapter_number'];
			$values[] = $verse['verse_number'];
			$values[] = $verse['verse'];
			$placeholders[] = '(%d, %d, %d, %s)';
		}

		$query = "INSERT INTO {$table_name} (book_number, chapter_number, verse_number, verse) VALUES " . implode(', ', $placeholders);

		$result = $wpdb->query($wpdb->prepare($query, $values));

		if ($result !== false) {
			$results['success'] = count($verses);
		} else {
			$results['errors'] = count($verses);
		}

		return $results;
	}

	/**
	 * Validate import data.
	 *
	 * @since    1.0.0
	 * @param    array    $verse_data    Verse data to validate
	 * @return   bool                    True if valid, false otherwise
	 */
	private function validate_verse_data($verse_data) {
		// Check required fields
		if (!isset($verse_data['book_number']) || !isset($verse_data['chapter_number']) || 
		    !isset($verse_data['verse_number']) || !isset($verse_data['verse'])) {
			return false;
		}

		// Validate book number
		if (!is_numeric($verse_data['book_number']) || $verse_data['book_number'] < 1 || $verse_data['book_number'] > 66) {
			return false;
		}

		// Validate chapter and verse numbers
		if (!is_numeric($verse_data['chapter_number']) || $verse_data['chapter_number'] < 1) {
			return false;
		}

		if (!is_numeric($verse_data['verse_number']) || $verse_data['verse_number'] < 1) {
			return false;
		}

		// Validate verse text
		if (empty(trim($verse_data['verse']))) {
			return false;
		}

		return true;
	}

	/**
	 * Get import progress.
	 *
	 * @since    1.0.0
	 * @param    string    $version_abbrev    Version abbreviation
	 * @return   array                        Import progress data
	 */
	public function get_import_progress($version_abbrev) {
		global $wpdb;
		$table_name = $this->database->get_table_name($version_abbrev);

		$stats = $wpdb->get_row(
			"SELECT 
			   COUNT(*) as imported_verses,
			   COUNT(DISTINCT book_number) as imported_books,
			   COUNT(DISTINCT CONCAT(book_number, '-', chapter_number)) as imported_chapters
			 FROM {$table_name}"
		);

		if (!$stats) {
			return array(
				'imported_verses' => 0,
				'imported_books' => 0,
				'imported_chapters' => 0,
				'progress_percentage' => 0
			);
		}

		// Estimate total verses (approximately 31,000 verses in the Bible)
		$estimated_total = 31102;
		$progress_percentage = min(100, ($stats->imported_verses / $estimated_total) * 100);

		return array(
			'imported_verses' => intval($stats->imported_verses),
			'imported_books' => intval($stats->imported_books),
			'imported_chapters' => intval($stats->imported_chapters),
			'progress_percentage' => round($progress_percentage, 2)
		);
	}

	/**
	 * Clear imported data for a version.
	 *
	 * @since    1.0.0
	 * @param    string    $version_abbrev    Version abbreviation
	 * @return   bool                         True on success, false on failure
	 */
	public function clear_version_data($version_abbrev) {
		global $wpdb;
		$table_name = $this->database->get_table_name($version_abbrev);

		$result = $wpdb->query("TRUNCATE TABLE {$table_name}");

		if ($result !== false) {
			// Update version status
			$this->database->update_version_status($version_abbrev, false);
			return true;
		}

		return false;
	}

}