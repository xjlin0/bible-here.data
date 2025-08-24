(function( $ ) {
	'use strict';

	/**
	 * Bible Here Plugin - Public JavaScript
	 * Handles Bible reading interface interactions
	 */

	// Global variables
	var bibleReader = {
		currentVersion: 'KJV',
		currentBook: 'Genesis',
		currentChapter: 1,
		versions: [],
		books: [],
		isLoading: false,
		searchTimeout: null
	};

	// Initialize when DOM is ready
	$(document).ready(function() {
		initializeBibleReader();
		initializeBibleSearch();
		bindEvents();
		
		// Initialize new systems
		if (window.bibleReferenceEngine) {
			window.bibleReferenceEngine.init();
		}
		
		if (window.bibleDOMScanner) {
			window.bibleDOMScanner.init();
		}
		
		if (window.biblePopupSystem) {
			window.biblePopupSystem.init();
		}
		
		if (window.bibleShortcodeProcessor) {
			window.bibleShortcodeProcessor.init();
		}
	});

	/**
	 * Initialize Bible Reader
	 */
	function initializeBibleReader() {
		// Load available versions and books
		loadVersionsAndBooks();
		
		// Initialize each Bible reader on the page
		$('.bible-here-reader').each(function() {
			var $reader = $(this);
			var version = $reader.data('version') || 'KJV';
			var book = $reader.data('book') || 'Genesis';
			var chapter = parseInt($reader.data('chapter')) || 1;
			
			// Set initial values
			$reader.find('.version-select').val(version);
			$reader.find('.book-select').val(book);
			
			// Load initial content
			loadBibleContent($reader, version, book, chapter);
		});
	}

	/**
	 * Initialize Bible Search Interface
	 */
	function initializeBibleSearch() {
		// Initialize each search interface on the page
		$('.bible-search-interface').each(function() {
			var $search = $(this);
			
			// Load search history if enabled
			if ($search.data('show-history') === 'true') {
				loadSearchHistory($search);
			}
			
			// Initialize search suggestions
			if ($search.data('show-suggestions') === 'true') {
				initializeSearchSuggestions($search);
			}
		});
	}

	/**
	 * Bind event handlers
	 */
	function bindEvents() {
		// Version selector change
		$(document).on('change', '.version-select', function() {
			var $reader = $(this).closest('.bible-here-reader');
			var version = $(this).val();
			var book = $reader.find('.book-select').val();
			var chapter = getCurrentChapter($reader);
			
			loadBibleContent($reader, version, book, chapter);
			
			// Update parallel version options to exclude the new main version
			var $parallelList = $reader.find('.parallel-versions-list');
			if ($parallelList.length > 0) {
				var readerId = $reader.attr('id') || 'reader-' + Math.random().toString(36).substr(2, 9);
				$parallelList.empty();
				$.each(bibleReader.versions, function(index, versionOption) {
					if (versionOption.abbreviation !== version) {
						var checkboxHtml = '<div class="parallel-version-option">' +
							'<input type="checkbox" class="parallel-version-checkbox" ' +
							'id="parallel-' + readerId + '-' + versionOption.abbreviation + '" ' +
							'value="' + versionOption.abbreviation + '">' +
							'<label for="parallel-' + readerId + '-' + versionOption.abbreviation + '" ' +
							'class="parallel-version-label">' +
							versionOption.name + ' (' + versionOption.abbreviation + ')' +
							'</label>' +
							'</div>';
						$parallelList.append(checkboxHtml);
					}
				});
			}
		});

		// Book selector change
		$(document).on('change', '.book-select', function() {
			var $reader = $(this).closest('.bible-here-reader');
			var version = $reader.find('.version-select').val();
			var book = $(this).val();
			
			// Reset to chapter 1 when book changes
			loadBibleContent($reader, version, book, 1);
			updateChapterNavigation($reader, book);
		});

		// Chapter navigation buttons
		$(document).on('click', '.chapter-nav-button', function() {
			var $reader = $(this).closest('.bible-here-reader');
			var version = $reader.find('.version-select').val();
			var book = $reader.find('.book-select').val();
			var chapter = parseInt($(this).data('chapter'));
			
			// Update active state
			$(this).siblings().removeClass('active');
			$(this).addClass('active');
			
			loadBibleContent($reader, version, book, chapter);
		});

		// Search functionality
		$(document).on('input', '.bible-search-input', function() {
			var $input = $(this);
			var $reader = $input.closest('.bible-here-reader');
			var query = $input.val().trim();
			
			// Clear previous timeout
			if (bibleReader.searchTimeout) {
				clearTimeout(bibleReader.searchTimeout);
			}
			
			// Debounce search
			if (query.length >= 3) {
				bibleReader.searchTimeout = setTimeout(function() {
					performSearch($reader, query);
				}, 500);
			} else {
				clearSearchResults($reader);
			}
		});

		// Search button click
		$(document).on('click', '.bible-search-button', function() {
			var $reader = $(this).closest('.bible-here-reader');
			var query = $reader.find('.bible-search-input').val().trim();
			
			if (query.length >= 3) {
				performSearch($reader, query);
			}
		});

		// Search functionality for bible-search-interface
		$('.search-button').on('click', function() {
			performAdvancedSearch($(this).closest('.bible-search-interface'));
		});

		$('.search-input').on('keypress', function(e) {
			if (e.which === 13) { // Enter key
				performAdvancedSearch($(this).closest('.bible-search-interface'));
			}
		});

		// Advanced search filters change events
		$('.version-filter, .book-filter, .search-mode, .sort-by').on('change', function() {
			var $search = $(this).closest('.bible-search-interface');
			var query = $search.find('.search-input').val().trim();
			if (query) {
				performAdvancedSearch($search);
			}
		});

		// Parallel version toggle functionality
		$(document).on('change', '.parallel-version-checkbox', function() {
			var $reader = $(this).closest('.bible-here-reader');
			var version = $(this).val();
			var isChecked = $(this).is(':checked');
			
			if (isChecked) {
				// Add version to parallel display
				var currentParallel = $reader.data('parallel') || '';
				var parallelVersions = currentParallel ? currentParallel.split(',').map(function(v) { return v.trim(); }) : [];
				if (parallelVersions.indexOf(version) === -1) {
					parallelVersions.push(version);
					$reader.data('parallel', parallelVersions.join(','));
				}
			} else {
				// Remove version from parallel display
				var currentParallel = $reader.data('parallel') || '';
				var parallelVersions = currentParallel ? currentParallel.split(',').map(function(v) { return v.trim(); }) : [];
				var index = parallelVersions.indexOf(version);
				if (index > -1) {
					parallelVersions.splice(index, 1);
					$reader.data('parallel', parallelVersions.join(','));
				}
			}
			
			toggleParallelVersion($reader, $(this));
		});

		// Search result click
		$(document).on('click', '.search-result-item', function(e) {
			// Don't trigger if clicking on action buttons
			if ($(e.target).closest('.search-result-actions').length > 0) {
				return;
			}
			
			var $reader = $(this).closest('.bible-here-reader');
			var version = $(this).data('version');
			var book = $(this).data('book');
			var chapter = parseInt($(this).data('chapter'));
			
			// Update selectors
			$reader.find('.version-select').val(version);
			$reader.find('.book-select').val(book);
			
			// Load content
			loadBibleContent($reader, version, book, chapter);
			updateChapterNavigation($reader, book, chapter);
			
			// Clear search
			clearSearchResults($reader);
			$reader.find('.bible-search-input').val('');
		});
		
		// View context button click
		$(document).on('click', '.view-context-btn', function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			var $item = $(this).closest('.search-result-item');
			var version = $item.data('version');
			var book = $item.data('book');
			var chapter = parseInt($item.data('chapter'));
			var verse = parseInt($item.data('verse'));
			
			// Show loading state
			$(this).html('⏳').prop('disabled', true);
			
			getVerseContext(version, book, chapter, verse, 3).then(function(context) {
				showContextModal(context, book + ' ' + chapter + ':' + verse + ' (' + version + ')');
			}).catch(function(error) {
				alert('Failed to load context: ' + error.message);
			}).finally(function() {
				$(this).html('📖').prop('disabled', false);
			}.bind(this));
		});
		
		// Copy verse button click
		$(document).on('click', '.copy-verse-btn', function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			var $item = $(this).closest('.search-result-item');
			var version = $item.data('version');
			var book = $item.data('book');
			var chapter = $item.data('chapter');
			var verse = $item.data('verse');
			var text = $item.find('.search-result-text').text();
			
			var reference = book + ' ' + chapter + ':' + verse + ' (' + version + ')';
			var fullText = '"' + text + '" - ' + reference;
			
			// Copy to clipboard
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(fullText).then(function() {
					showCopyFeedback($(this));
				}.bind(this));
			} else {
				// Fallback for older browsers
				var textArea = document.createElement('textarea');
				textArea.value = fullText;
				document.body.appendChild(textArea);
				textArea.select();
				try {
					document.execCommand('copy');
					showCopyFeedback($(this));
				} catch (err) {
					alert('Failed to copy verse');
				}
				document.body.removeChild(textArea);
			}
		});

		// Bible reference popup
		$(document).on('click', '.bible-reference-link', function(e) {
			e.preventDefault();
			var $link = $(this);
			var reference = $link.data('reference');
			var version = $link.data('version') || 'KJV';
			
			showReferencePopup($link, reference, version);
		});

		// Close popup when clicking outside
		$(document).on('click', function(e) {
			if (!$(e.target).closest('.bible-here-reference').length) {
				$('.bible-popup-content').remove();
				$('.bible-popup-overlay').remove();
			}
		});
	}

	/**
	 * Load versions and books data
	 */
	function loadVersionsAndBooks() {
		if (typeof bible_here_ajax === 'undefined') {
			console.error('Bible Here: AJAX configuration not found');
			return;
		}

		$.ajax({
			url: bible_here_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'bible_here_get_versions',
				nonce: bible_here_ajax.nonce
			},
			success: function(response) {
				if (response.success) {
					bibleReader.versions = response.data.versions || [];
					bibleReader.books = response.data.books || [];
					populateSelectors();
				} else {
					console.error('Bible Here: Failed to load versions and books');
				}
			},
			error: function(xhr, status, error) {
				console.error('Bible Here: AJAX error:', error);
			}
		});
	}

	/**
	 * Populate version and book selectors
	 */
	function populateSelectors() {
		// Populate version selectors
		$('.version-select').each(function() {
			var $select = $(this);
			var currentValue = $select.val();
			var $reader = $select.closest('.bible-here-reader');
			var readerId = $reader.attr('id') || 'reader-' + Math.random().toString(36).substr(2, 9);
			
			$select.empty();
			$.each(bibleReader.versions, function(index, version) {
				$select.append($('<option></option>')
					.attr('value', version.abbreviation)
					.text(version.name + ' (' + version.abbreviation + ')'));
			});
			
			if (currentValue) {
				$select.val(currentValue);
			}
			
			// Populate parallel version selector if it exists
			var $parallelList = $reader.find('.parallel-versions-list');
			if ($parallelList.length > 0) {
				$parallelList.empty();
				$.each(bibleReader.versions, function(index, version) {
					// Skip the current main version
					if (version.abbreviation !== currentValue) {
						var checkboxHtml = '<div class="parallel-version-option">' +
							'<input type="checkbox" class="parallel-version-checkbox" ' +
							'id="parallel-' + readerId + '-' + version.abbreviation + '" ' +
							'value="' + version.abbreviation + '">' +
							'<label for="parallel-' + readerId + '-' + version.abbreviation + '" ' +
							'class="parallel-version-label">' +
							version.name + ' (' + version.abbreviation + ')' +
							'</label>' +
							'</div>';
						$parallelList.append(checkboxHtml);
					}
				});
			}
		});

		// Populate book selectors
		$('.book-select').each(function() {
			var $select = $(this);
			var currentValue = $select.val();
			
			$select.empty();
			$.each(bibleReader.books, function(index, book) {
				$select.append($('<option></option>')
					.attr('value', book.name)
					.text(book.name));
			});
			
			if (currentValue) {
				$select.val(currentValue);
			}
		});
	}

	/**
	 * Load Bible content
	 */
	function loadBibleContent($reader, version, book, chapter, startVerse, endVerse) {
		if (bibleReader.isLoading) {
			return;
		}

		bibleReader.isLoading = true;
		showLoading($reader);

		// Get parallel versions from data attribute
		var parallelVersions = $reader.data('parallel') ? $reader.data('parallel').split(',').map(function(v) { return v.trim(); }) : [];
		var hasParallel = parallelVersions.length > 0;

		// Load main version
		loadVersionContent($reader, version, book, chapter, startVerse, endVerse, 'main');

		// Load parallel versions if any
		if (hasParallel) {
			var parallelIndex = 0;
			$.each(parallelVersions, function(index, parallelVersion) {
				if (parallelVersion && parallelVersion !== version) {
					loadVersionContent($reader, parallelVersion, book, chapter, startVerse, endVerse, 'parallel-' + parallelIndex);
					parallelIndex++;
				}
			});
		}
	}

	/**
	 * Load content for a specific version
	 */
	function loadVersionContent($reader, version, book, chapter, startVerse, endVerse, columnType) {
		$.ajax({
			url: bible_here_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'bible_here_get_verses',
				nonce: bible_here_ajax.nonce,
				version: version,
				book: book,
				chapter: chapter,
				start_verse: startVerse || 1,
				end_verse: endVerse || 999
			},
			success: function(response) {
				if (columnType === 'main') {
					bibleReader.isLoading = false;
					hideLoading($reader);
				}
				
				if (response.success && response.data.verses) {
					displayBibleContent($reader, response.data, version, book, chapter, columnType);
					if (columnType === 'main') {
						updateChapterNavigation($reader, book, chapter);
					}
				} else {
					showError($reader, response.data || 'Failed to load Bible content', columnType);
				}
			},
			error: function(xhr, status, error) {
				if (columnType === 'main') {
					bibleReader.isLoading = false;
					hideLoading($reader);
				}
				showError($reader, 'Network error: ' + error, columnType);
			}
		});
	}

	/**
	 * Display Bible content
	 */
	function displayBibleContent($reader, data, version, book, chapter, columnType) {
		var $content, $title, $badge, $verses;

		if (columnType === 'main') {
			// For main version or single version view
			if ($reader.find('.bible-parallel-view').length > 0) {
				// Parallel view - target main column
				var $mainColumn = $reader.find('.bible-main-column');
				$content = $mainColumn;
				$title = $mainColumn.find('.bible-title');
				$badge = $mainColumn.find('.bible-version-badge');
				$verses = $mainColumn.find('.bible-verses');
			} else {
				// Single version view
				$content = $reader.find('.bible-content');
				$title = $content.find('.bible-title');
				$badge = $content.find('.bible-version-badge');
				$verses = $content.find('.bible-verses');
			}
		} else {
			// For parallel versions - find by data attribute or create if needed
			var $parallelColumn = $reader.find('.bible-parallel-column[data-version="' + version + '"]');
			if ($parallelColumn.length === 0) {
				// Create parallel column if it doesn't exist
				var $parallelContainer = $reader.find('.bible-parallel-columns');
				if ($parallelContainer.length > 0) {
					$parallelColumn = $('<div class="bible-parallel-column" data-version="' + version + '">' +
						'<div class="bible-version-badge">' + version + '</div>' +
						'<div class="bible-title">' + book + ' ' + chapter + '</div>' +
						'<div class="bible-verses"></div>' +
						'</div>');
					$parallelContainer.append($parallelColumn);
				}
			}
			
			if ($parallelColumn.length > 0) {
				$content = $parallelColumn;
				$title = $parallelColumn.find('.bible-title');
				$badge = $parallelColumn.find('.bible-version-badge');
				$verses = $parallelColumn.find('.bible-verses');
			}
		}

		if (!$verses || $verses.length === 0) {
			return;
		}

		// Update title and version badge
		if ($title && $title.length > 0) {
			$title.text(book + ' ' + chapter);
		}
		if ($badge && $badge.length > 0) {
			$badge.text(version);
		}

		// Clear and populate verses
		$verses.empty();
		if (data.verses && data.verses.length > 0) {
			$.each(data.verses, function(index, verse) {
				var $verse = $('<div class="bible-verse"></div>');
				var $number = $('<span class="verse-number"></span>').text(verse.verse_number);
				var $text = $('<span class="verse-text"></span>').html(verse.verse_text);
				
				$verse.append($number).append($text);
				$verses.append($verse);
			});
		} else {
			$verses.html('<div class="bible-error">No verses found for this chapter.</div>');
		}
	}

	/**
	 * Update chapter navigation
	 */
	function updateChapterNavigation($reader, book, activeChapter) {
		var $navButtons = $reader.find('.chapter-nav-buttons');
		
		// Find book info
		var bookInfo = null;
		$.each(bibleReader.books, function(index, b) {
			if (b.name === book) {
				bookInfo = b;
				return false;
			}
		});

		if (!bookInfo) {
			return;
		}

		// Clear existing buttons
		$navButtons.empty();

		// Create chapter buttons
		for (var i = 1; i <= bookInfo.chapters; i++) {
			var $button = $('<button class="chapter-nav-button"></button>')
				.attr('data-chapter', i)
				.text(i);
			
			if (i === activeChapter) {
				$button.addClass('active');
			}
			
			$navButtons.append($button);
		}
	}

	/**
	 * Perform search
	 */
	var searchTimeout;
	var currentSearchPage = 1;
	var totalSearchResults = 0;
	var searchHistory = JSON.parse(localStorage.getItem('bible_search_history') || '[]');
	
	function performSearch($reader, query, options) {
		options = options || {};
		
		if (!query || query.trim() === '') {
			showSearchError($reader, 'Please enter a search query');
			return;
		}
		
		// Clear previous timeout
		if (searchTimeout) {
			clearTimeout(searchTimeout);
		}
		
		// Show loading state
		showSearchLoading($reader);
		
		// Set default options
		var searchOptions = {
			version: options.version || $reader.find('.version-select').val() || 'KJV',
			books: options.books || $reader.find('.filter-books').val() || [],
			search_mode: options.search_mode || $reader.find('.filter-search-mode').val() || 'natural',
			sort_by: options.sort_by || $reader.find('.filter-sort').val() || 'relevance',
			page: options.page || 1,
			limit: options.limit || 10
		};
		
		// Extend with additional options
		$.extend(searchOptions, options);

		// Perform search with timeout
		searchTimeout = setTimeout(function() {
			$.ajax({
				url: bible_here_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'bible_here_search_verses',
					query: query.trim(),
					version: searchOptions.version,
					books: searchOptions.books,
					search_mode: searchOptions.search_mode,
					sort_by: searchOptions.sort_by,
					page: searchOptions.page,
					limit: searchOptions.limit,
					nonce: bible_here_ajax.nonce
				},
				success: function(response) {
					if (response.success) {
						var results = response.data.results || response.data.verses || [];
						var total = response.data.total || 0;
						
						// Update current search state
						currentSearchPage = searchOptions.page;
						totalSearchResults = total;
						
						// Display results
						displaySearchResults($reader, results, query);
						
						// Update stats and pagination
						updateSearchStats($reader, query, total);
						updateSearchPagination($reader, total, searchOptions.page, searchOptions.limit);
						
						// Add to search history
						addToSearchHistory(query, total);
						
					} else {
						showSearchError($reader, response.data.message || 'Search failed');
					}
				},
				error: function(xhr, status, error) {
					showSearchError($reader, 'Network error: ' + error);
				}
			});
		}, 300); // Debounce search requests
	}

	function showSearchLoading($reader) {
		var $searchResults = $reader.find('.bible-search-results');
		if ($searchResults.length === 0) {
			$searchResults = $('<div class="bible-search-results"></div>');
			$reader.find('.search-box').after($searchResults);
		}
		$searchResults.html('<div class="search-loading"><div class="loading-spinner"></div><p>Searching Bible verses...</p></div>');
	}

	function showSearchError($reader, message) {
		var $searchResults = $reader.find('.bible-search-results');
		if ($searchResults.length === 0) {
			$searchResults = $('<div class="bible-search-results"></div>');
			$reader.find('.search-box').after($searchResults);
		}
		$searchResults.html('<div class="search-error"><p>' + escapeHtml(message) + '</p></div>');
	}

	/**
	 * Display search results
	 */
	function displaySearchResults($reader, results, query) {
		var $searchResults = $reader.find('.bible-search-results');
		
		if ($searchResults.length === 0) {
			$searchResults = $('<div class="bible-search-results"></div>');
			$reader.find('.search-box').after($searchResults);
		}

		$searchResults.empty();

		if (!results || results.length === 0) {
			$searchResults.html('<div class="search-empty-state"><div class="empty-icon">📖</div><h4>No verses found</h4><p>Try adjusting your search terms or filters</p></div>');
			return;
		}

		var html = '<div class="search-results-list">';
		$.each(results, function(index, verse) {
			var highlightedText = query ? highlightSearchTerm(verse.text || verse.verse_text, query) : (verse.text || verse.verse_text);
			html += '<div class="search-result-item" data-book="' + escapeHtml(verse.book_name) + '" data-chapter="' + verse.chapter + '" data-verse="' + verse.verse + '" data-version="' + escapeHtml(verse.version) + '">' +
				'<div class="search-result-header">' +
					'<div class="search-result-reference">' +
						'<span class="book-name">' + escapeHtml(verse.book_name) + '</span>' +
						'<span class="chapter-verse">' + verse.chapter + ':' + verse.verse + '</span>' +
						'<span class="version-badge">' + escapeHtml(verse.version) + '</span>' +
					'</div>' +
					'<div class="search-result-actions">' +
						'<button class="view-context-btn" title="View Context">📖</button>' +
						'<button class="copy-verse-btn" title="Copy Verse">📋</button>' +
					'</div>' +
				'</div>' +
				'<div class="search-result-text">' + highlightedText + '</div>' +
			'</div>';
		});
		html += '</div>';
		
		$searchResults.html(html);
	}

	function updateSearchStats($reader, query, total) {
		var $stats = $reader.find('.search-stats');
		if ($stats.length === 0) {
			$stats = $('<div class="search-stats"></div>');
			$reader.find('.bible-search-results').before($stats);
		}
		$stats.html('Found <span class="search-results-count">' + total + '</span> results for "' + escapeHtml(query) + '"');
	}

	function updateSearchPagination($reader, total, currentPage, perPage) {
		total = total || totalSearchResults;
		currentPage = currentPage || currentSearchPage;
		perPage = perPage || 10;
		
		var totalPages = Math.ceil(total / perPage);
		var $paginationContainer = $reader.find('.search-pagination');
		
		if (totalPages <= 1) {
			$paginationContainer.hide();
			return;
		}
		
		if ($paginationContainer.length === 0) {
			$paginationContainer = $('<div class="search-pagination"></div>');
			$reader.find('.bible-search-results').after($paginationContainer);
		}
		
		$paginationContainer.show();
		
		var startResult = ((currentPage - 1) * perPage) + 1;
		var endResult = Math.min(currentPage * perPage, total);
		
		var paginationInfo = '<div class="pagination-info">Showing ' + startResult + '-' + endResult + ' of ' + total + ' results</div>';
		
		// Generate pagination numbers
		var paginationNumbers = '';
		var maxVisible = 5;
		var startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
		var endPage = Math.min(totalPages, startPage + maxVisible - 1);
		
		if (endPage - startPage + 1 < maxVisible) {
			startPage = Math.max(1, endPage - maxVisible + 1);
		}
		
		for (var i = startPage; i <= endPage; i++) {
			paginationNumbers += '<button class="pagination-number ' + (i === currentPage ? 'active' : '') + '" data-page="' + i + '">' + i + '</button>';
		}
		
		var prevDisabled = currentPage === 1 ? 'disabled' : '';
		var nextDisabled = currentPage === totalPages ? 'disabled' : '';
		
		$paginationContainer.html(
			paginationInfo +
			'<div class="pagination-controls">' +
				'<button class="pagination-prev" data-page="' + (currentPage - 1) + '" ' + prevDisabled + '>Previous</button>' +
				'<div class="pagination-numbers">' + paginationNumbers + '</div>' +
				'<button class="pagination-next" data-page="' + (currentPage + 1) + '" ' + nextDisabled + '>Next</button>' +
			'</div>'
		);
		
		// Bind pagination click events
		$paginationContainer.find('.pagination-number, .pagination-prev, .pagination-next').off('click').on('click', function() {
			if ($(this).hasClass('disabled')) return;
			
			var page = parseInt($(this).data('page'));
			if (page && page !== currentPage) {
				var $searchInput = $reader.find('.bible-search-input');
				var query = $searchInput.val();
				if (query) {
					performSearch($reader, query, { page: page });
				}
			}
		});
	}

	function addToSearchHistory(query, resultCount) {
		var historyItem = {
			query: query,
			count: resultCount,
			date: new Date().toISOString()
		};
		
		// Remove existing entry if exists
		searchHistory = searchHistory.filter(function(item) {
			return item.query !== query;
		});
		
		// Add to beginning
		searchHistory.unshift(historyItem);
		
		// Keep only last 10 searches
		searchHistory = searchHistory.slice(0, 10);
		
		localStorage.setItem('bible_search_history', JSON.stringify(searchHistory));
		updateSearchHistoryDisplay();
	}

	function updateSearchHistoryDisplay() {
		var $historyContainer = $('.history-list');
		
		if (searchHistory.length === 0) {
			$historyContainer.html('<div class="no-history">No search history</div>');
			return;
		}
		
		var html = '';
		$.each(searchHistory, function(index, item) {
			var date = new Date(item.date).toLocaleDateString();
			html += '<div class="history-item" data-query="' + escapeHtml(item.query) + '">' +
				'<div class="history-query">' + escapeHtml(item.query) + '</div>' +
				'<div class="history-meta">' +
					'<span class="history-date">' + date + '</span>' +
					'<span class="history-count">' + item.count + ' results</span>' +
				'</div>' +
			'</div>';
		});
		
		$historyContainer.html(html);
	}

	/**
	 * Clear search results
	 */
	function clearSearchResults($reader) {
		$reader.find('.bible-search-results').empty();
		$reader.find('.bible-search-input').val('');
		$reader.find('.search-stats').empty();
		$reader.find('.search-pagination').hide();
		currentSearchPage = 1;
		totalSearchResults = 0;
	}

	function getSearchSuggestions($reader, query) {
		if (!query || query.length < 2) {
			$reader.find('.search-suggestions').hide();
			return;
		}
		
		$.ajax({
			url: bible_here_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'bible_here_search_suggestions',
				query: query,
				nonce: bible_here_ajax.nonce
			},
			success: function(response) {
				if (response.success && response.data.length > 0) {
					displaySearchSuggestions($reader, response.data);
				} else {
					$reader.find('.search-suggestions').hide();
				}
			}
		});
	}

	// Get verse context
	function getVerseContext(version, book, chapter, verse, contextVerses) {
		contextVerses = contextVerses || 2;
		
		return new Promise(function(resolve, reject) {
			$.ajax({
				url: bible_here_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'bible_here_get_verse_context',
					version: version,
					book: book,
					chapter: chapter,
					verse: verse,
					context_verses: contextVerses,
					nonce: bible_here_ajax.nonce
				},
				success: function(response) {
					if (response.success) {
						resolve(response.data);
					} else {
						reject(new Error(response.data.message || 'Failed to get context'));
					}
				},
				error: function(xhr, status, error) {
					reject(new Error('Network error: ' + error));
				}
			});
		});
	}

	function displaySearchSuggestions($reader, suggestions) {
		var html = '<ul class="suggestions-list">';
		$.each(suggestions, function(index, suggestion) {
			html += '<li data-suggestion="' + escapeHtml(suggestion) + '">' + escapeHtml(suggestion) + '</li>';
		});
		html += '</ul>';
		
		$reader.find('.search-suggestions').html(html).show();
	}
	
	// Show context modal
	function showContextModal(contextData, title) {
		// Remove existing modal
		$('.context-modal-overlay').remove();
		
		var html = '<div class="context-modal-overlay">' +
			'<div class="context-modal">' +
				'<div class="context-modal-header">' +
					'<h3>' + escapeHtml(title) + '</h3>' +
					'<button class="context-modal-close">&times;</button>' +
				'</div>' +
				'<div class="context-modal-content">';
		
		if (contextData && contextData.verses && contextData.verses.length > 0) {
			$.each(contextData.verses, function(index, verse) {
				var isTarget = verse.verse_number === contextData.target_verse;
				var verseClass = isTarget ? 'context-verse target-verse' : 'context-verse';
				html += '<div class="' + verseClass + '">' +
					'<span class="verse-number">' + verse.verse_number + '</span>' +
					'<span class="verse-text">' + verse.verse_text + '</span>' +
				'</div>';
			});
		} else {
			html += '<p>No context available</p>';
		}
		
		html += '</div></div></div>';
		
		$('body').append(html);
		
		// Bind close events
		$('.context-modal-close, .context-modal-overlay').on('click', function(e) {
			if (e.target === this) {
				$('.context-modal-overlay').remove();
			}
		});
		
		// Prevent modal content clicks from closing
		$('.context-modal').on('click', function(e) {
			e.stopPropagation();
		});
	}
	
	// Show copy feedback
	function showCopyFeedback($button) {
		var originalHtml = $button.html();
		$button.html('✓').addClass('copied');
		
		setTimeout(function() {
			$button.html(originalHtml).removeClass('copied');
		}, 2000);
	}

	/**
	 * Show reference popup
	 */
	function showReferencePopup($link, reference, version) {
		// Parse reference (e.g., "John 3:16" or "Genesis 1:1-3")
		var parts = reference.match(/^([\w\s]+)\s+(\d+):(\d+)(?:-(\d+))?$/);
		if (!parts) {
			return;
		}

		var book = parts[1].trim();
		var chapter = parseInt(parts[2]);
		var startVerse = parseInt(parts[3]);
		var endVerse = parts[4] ? parseInt(parts[4]) : startVerse;

		// Create popup
		var $popup = $('<div class="bible-popup-content"></div>');
		var $header = $('<div class="bible-popup-header"></div>')
			.html(reference + ' <span class="bible-version">(' + version + ')</span>');
		var $content = $('<div class="bible-popup-text">Loading...</div>');

		$popup.append($header).append($content);

		// Position and show popup
		if ($(window).width() <= 768) {
			// Mobile: show as modal
			var $overlay = $('<div class="bible-popup-overlay"></div>');
			$('body').append($overlay).append($popup);
		} else {
			// Desktop: show as tooltip
			$link.parent().append($popup);
		}

		// Load verse content
		$.ajax({
			url: bible_here_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'bible_here_get_verses',
				nonce: bible_here_ajax.nonce,
				version: version,
				book: book,
				chapter: chapter,
				start_verse: startVerse,
				end_verse: endVerse
			},
			success: function(response) {
				if (response.success && response.data.verses) {
					var text = '';
					$.each(response.data.verses, function(index, verse) {
						if (index > 0) text += ' ';
						text += '<sup>' + verse.verse_number + '</sup> ' + verse.verse_text;
					});
					$content.html(text);
				} else {
					$content.html('Verse not found.');
				}
			},
			error: function() {
				$content.html('Error loading verse.');
			}
		});
	}

	/**
	 * Utility functions
	 */
	function getCurrentChapter($reader) {
		var $active = $reader.find('.chapter-nav-button.active');
		return $active.length ? parseInt($active.data('chapter')) : 1;
	}

	function showLoading($reader) {
		var $content = $reader.find('.bible-content');
		var $loading = $('<div class="bible-loading-overlay"><div class="loading-spinner"></div><div>Loading...</div></div>');
		$content.css('position', 'relative').append($loading);
	}

	function hideLoading($reader) {
		$reader.find('.bible-loading-overlay').remove();
	}

	function showError($reader, message, columnType) {
		var $content, $verses;

		if (columnType === 'main') {
			// For main version or single version view
			if ($reader.find('.bible-parallel-view').length > 0) {
				// Parallel view - target main column
				var $mainColumn = $reader.find('.bible-main-column');
				$verses = $mainColumn.find('.bible-verses');
			} else {
				// Single version view
				$verses = $reader.find('.bible-verses');
			}
		} else {
			// For parallel versions - find by column type or data attribute
			var $parallelColumn = $reader.find('.bible-' + columnType);
			if ($parallelColumn.length === 0) {
				// Try finding by data-version attribute if columnType contains version info
				var versionMatch = columnType.match(/parallel-(\d+)/);
				if (versionMatch) {
					$parallelColumn = $reader.find('.bible-parallel-column').eq(parseInt(versionMatch[1]));
				}
			}
			if ($parallelColumn.length > 0) {
				$verses = $parallelColumn.find('.bible-verses');
			}
		}

		if ($verses && $verses.length > 0) {
			$verses.html('<div class="bible-error">' + escapeHtml(message) + '</div>');
		}
	}

	/**
	 * Toggle parallel version display
	 */
	function toggleParallelVersion($reader, $checkbox) {
		var version = $checkbox.val();
		var isChecked = $checkbox.is(':checked');
		var $parallelColumns = $reader.find('.bible-parallel-column');
		
		// Find the column for this version
		var $targetColumn = null;
		$parallelColumns.each(function() {
			if ($(this).data('version') === version) {
				$targetColumn = $(this);
				return false;
			}
		});

		if ($targetColumn) {
			if (isChecked) {
				// Show the column and load content
				$targetColumn.show();
				var book = $reader.find('.book-select').val();
				var chapter = getCurrentChapter($reader);
				var columnIndex = $parallelColumns.index($targetColumn);
				loadVersionContent($reader, version, book, chapter, 1, 999, 'parallel-' + columnIndex);
			} else {
				// Hide the column
				$targetColumn.hide();
			}
		}
	}

	function highlightSearchTerm(text, term) {
		if (!term) return text;
		
		// Handle multiple terms
		var terms = term.split(/\s+/).filter(function(t) { return t.length > 0; });
		var highlightedText = text;
		
		$.each(terms, function(index, t) {
			var regex = new RegExp('(' + escapeRegex(t) + ')', 'gi');
			highlightedText = highlightedText.replace(regex, '<span class="search-highlight">$1</span>');
		});
		
		return highlightedText;
	}

	function escapeHtml(text) {
		return text
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function escapeRegex(text) {
		return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

	/**
	 * Load search history
	 */
	function loadSearchHistory($search) {
		$.ajax({
			url: bible_here_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'bible_here_get_search_history',
				nonce: bible_here_ajax.nonce
			},
			success: function(response) {
				if (response.success && response.data.history) {
					displaySearchHistory($search, response.data.history);
				}
			}
		});
	}

	/**
	 * Display search history
	 */
	function displaySearchHistory($search, history) {
		var $historyContainer = $search.find('.search-history');
		if ($historyContainer.length === 0) return;

		if (history.length === 0) {
			$historyContainer.html('<p class="no-history">No search history found.</p>');
			return;
		}

		var html = '<h4>Recent Searches</h4><ul class="search-history-list">';
		$.each(history, function(index, item) {
			html += '<li><a href="#" class="search-history-item" data-query="' + escapeHtml(item.query) + '">' + escapeHtml(item.query) + '</a></li>';
		});
		html += '</ul><button type="button" class="clear-history-btn">Clear History</button>';
		$historyContainer.html(html);

		// Bind click events
		$historyContainer.find('.search-history-item').on('click', function(e) {
			e.preventDefault();
			var query = $(this).data('query');
			$search.find('.search-input').val(query);
			performSearch($search);
		});

		$historyContainer.find('.clear-history-btn').on('click', function() {
			clearSearchHistory($search);
		});
	}

	/**
	 * Clear search history
	 */
	function clearSearchHistory($search) {
		$.ajax({
			url: bible_here_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'bible_here_clear_search_history',
				nonce: bible_here_ajax.nonce
			},
			success: function(response) {
				if (response.success) {
					$search.find('.search-history').html('<p class="no-history">No search history found.</p>');
				}
			}
		});
	}

	/**
	 * Initialize search suggestions
	 */
	function initializeSearchSuggestions($search) {
		var $input = $search.find('.search-input');
		var $suggestions = $('<div class="search-suggestions"></div>');
		$input.after($suggestions);

		var suggestionTimeout;
		$input.on('input', function() {
			clearTimeout(suggestionTimeout);
			var query = $(this).val().trim();
			
			if (query.length < 2) {
				$suggestions.hide();
				return;
			}

			suggestionTimeout = setTimeout(function() {
				loadSearchSuggestions($search, query);
			}, 300);
		});

		// Hide suggestions when clicking outside
		$(document).on('click', function(e) {
			if (!$input.is(e.target) && !$suggestions.is(e.target) && $suggestions.has(e.target).length === 0) {
				$suggestions.hide();
			}
		});
	}

	/**
	 * Load search suggestions
	 */
	function loadSearchSuggestions($search, query) {
		var version = $search.find('.version-filter').val() || 'KJV';
		
		$.ajax({
			url: bible_here_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'bible_here_search_suggestions',
				nonce: bible_here_ajax.nonce,
				query: query,
				version: version
			},
			success: function(response) {
				if (response.success && response.data.suggestions) {
					displaySearchSuggestions($search, response.data.suggestions);
				}
			}
		});
	}

	/**
	 * Display search suggestions
	 */
	function displaySearchSuggestions($search, suggestions) {
		var $suggestions = $search.find('.search-suggestions');
		if (suggestions.length === 0) {
			$suggestions.hide();
			return;
		}

		var html = '<ul>';
		$.each(suggestions, function(index, suggestion) {
			html += '<li><a href="#" class="suggestion-item">' + escapeHtml(suggestion) + '</a></li>';
		});
		html += '</ul>';
		$suggestions.html(html).show();

		// Bind click events
		$suggestions.find('.suggestion-item').on('click', function(e) {
			e.preventDefault();
			var suggestion = $(this).text();
			$search.find('.search-input').val(suggestion);
			$suggestions.hide();
			performSearch($search);
		});
	}

	/**
	 * Enhanced search function with advanced options
	 */
	function performAdvancedSearch($search) {
		var query = $search.find('.search-input').val().trim();
		if (!query) return;

		// Get search parameters
		var version = $search.find('.version-filter').val() || 'KJV';
		var book = $search.find('.book-filter').val() || '';
		var searchMode = $search.find('.search-mode').val() || 'natural';
		var sortBy = $search.find('.sort-by').val() || 'relevance';
		var regexSearch = searchMode === 'regex';

		// Show loading
		var $results = $search.find('.search-results');
		$results.html('<div class="search-loading">Searching...</div>');

		// Perform search
		$.ajax({
			url: bible_here_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'bible_here_search_verses',
				nonce: bible_here_ajax.nonce,
				query: query,
				version: version,
				book: book,
				search_mode: searchMode,
				sort_by: sortBy,
				regex_search: regexSearch,
				highlight: true,
				include_context: false,
				include_book_info: true,
				limit: 20,
				offset: 0
			},
			success: function(response) {
				if (response.success) {
					displaySearchResults($search, response.data);
				} else {
					$results.html('<div class="search-error">' + (response.data || 'Search failed') + '</div>');
				}
			},
			error: function() {
				$results.html('<div class="search-error">Search request failed</div>');
			}
		});
	}

	/**
	 * Display enhanced search results
	 */
	function displaySearchResults($search, data) {
		var $results = $search.find('.search-results');
		
		if (!data.verses || data.verses.length === 0) {
			$results.html('<div class="no-results">No verses found matching your search.</div>');
			return;
		}

		var html = '<div class="search-results-header">';
		html += '<p class="results-count">Found ' + data.total + ' verses</p>';
		html += '</div>';
		html += '<div class="search-results-list">';

		$.each(data.verses, function(index, verse) {
			html += '<div class="search-result-item">';
			html += '<div class="verse-reference">';
			html += '<strong>' + verse.book_name + ' ' + verse.chapter + ':' + verse.verse_number + '</strong>';
			if (verse.version) {
				html += ' <span class="version-tag">(' + verse.version + ')</span>';
			}
			html += '</div>';
			html += '<div class="verse-text">' + verse.verse_text + '</div>';
			html += '</div>';
		});

		html += '</div>';

		// Add pagination if needed
		if (data.total > data.verses.length) {
			html += '<div class="search-pagination">';
			html += '<button type="button" class="load-more-btn" data-offset="' + data.verses.length + '">Load More Results</button>';
			html += '</div>';
		}

		$results.html(html);

		// Bind load more event
		$results.find('.load-more-btn').on('click', function() {
			loadMoreResults($search, $(this).data('offset'));
		});
	}

	/**
	 * Load more search results
	 */
	function loadMoreResults($search, offset) {
		var query = $search.find('.search-input').val().trim();
		var version = $search.find('.version-filter').val() || 'KJV';
		var book = $search.find('.book-filter').val() || '';
		var searchMode = $search.find('.search-mode').val() || 'natural';
		var sortBy = $search.find('.sort-by').val() || 'relevance';
		var regexSearch = searchMode === 'regex';

		var $loadMoreBtn = $search.find('.load-more-btn');
		$loadMoreBtn.text('Loading...');

		$.ajax({
			url: bible_here_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'bible_here_search_verses',
				nonce: bible_here_ajax.nonce,
				query: query,
				version: version,
				book: book,
				search_mode: searchMode,
				sort_by: sortBy,
				regex_search: regexSearch,
				highlight: true,
				include_context: false,
				include_book_info: true,
				limit: 20,
				offset: offset
			},
			success: function(response) {
				if (response.success && response.data.verses) {
					appendSearchResults($search, response.data, offset);
				} else {
					$loadMoreBtn.text('Error loading more results');
				}
			},
			error: function() {
				$loadMoreBtn.text('Error loading more results');
			}
		});
	}

	/**
	 * Append more search results
	 */
	function appendSearchResults($search, data, currentOffset) {
		var $resultsList = $search.find('.search-results-list');
		var html = '';

		$.each(data.verses, function(index, verse) {
			html += '<div class="search-result-item">';
			html += '<div class="verse-reference">';
			html += '<strong>' + verse.book_name + ' ' + verse.chapter + ':' + verse.verse_number + '</strong>';
			if (verse.version) {
				html += ' <span class="version-tag">(' + verse.version + ')</span>';
			}
			html += '</div>';
			html += '<div class="verse-text">' + verse.verse_text + '</div>';
			html += '</div>';
		});

		$resultsList.append(html);

		// Update or remove load more button
		var $loadMoreBtn = $search.find('.load-more-btn');
		var newOffset = currentOffset + data.verses.length;
		if (newOffset < data.total) {
			$loadMoreBtn.data('offset', newOffset).text('Load More Results');
		} else {
			$loadMoreBtn.parent().remove();
		}
	}

	/**
	 * Bible Reference Auto-Detection System
	 * Automatically detects and converts Bible references in text
	 */

	// Bible reference detection engine
	var bibleReferenceEngine = {
		// English book abbreviations
		englishBooks: {
			// Old Testament
			'Gen': 'Genesis', 'Genesis': 'Genesis',
			'Exod': 'Exodus', 'Ex': 'Exodus', 'Exodus': 'Exodus',
			'Lev': 'Leviticus', 'Leviticus': 'Leviticus',
			'Num': 'Numbers', 'Numbers': 'Numbers',
			'Deut': 'Deuteronomy', 'Dt': 'Deuteronomy', 'Deuteronomy': 'Deuteronomy',
			'Josh': 'Joshua', 'Joshua': 'Joshua',
			'Judg': 'Judges', 'Judges': 'Judges',
			'Ruth': 'Ruth',
			'1Sam': '1 Samuel', '1 Sam': '1 Samuel', '1 Samuel': '1 Samuel',
			'2Sam': '2 Samuel', '2 Sam': '2 Samuel', '2 Samuel': '2 Samuel',
			'1Kgs': '1 Kings', '1 Kings': '1 Kings', '1 Kgs': '1 Kings',
			'2Kgs': '2 Kings', '2 Kings': '2 Kings', '2 Kgs': '2 Kings',
			'1Chr': '1 Chronicles', '1 Chronicles': '1 Chronicles', '1 Chron': '1 Chronicles',
			'2Chr': '2 Chronicles', '2 Chronicles': '2 Chronicles', '2 Chron': '2 Chronicles',
			'Ezra': 'Ezra',
			'Neh': 'Nehemiah', 'Nehemiah': 'Nehemiah',
			'Esth': 'Esther', 'Esther': 'Esther',
			'Job': 'Job',
			'Ps': 'Psalms', 'Psalm': 'Psalms', 'Psalms': 'Psalms',
			'Prov': 'Proverbs', 'Proverbs': 'Proverbs',
			'Eccl': 'Ecclesiastes', 'Ecclesiastes': 'Ecclesiastes',
			'Song': 'Song of Solomon', 'SOS': 'Song of Solomon', 'Song of Solomon': 'Song of Solomon',
			'Isa': 'Isaiah', 'Isaiah': 'Isaiah',
			'Jer': 'Jeremiah', 'Jeremiah': 'Jeremiah',
			'Lam': 'Lamentations', 'Lamentations': 'Lamentations',
			'Ezek': 'Ezekiel', 'Ezekiel': 'Ezekiel',
			'Dan': 'Daniel', 'Daniel': 'Daniel',
			'Hos': 'Hosea', 'Hosea': 'Hosea',
			'Joel': 'Joel',
			'Amos': 'Amos',
			'Obad': 'Obadiah', 'Obadiah': 'Obadiah',
			'Jonah': 'Jonah',
			'Mic': 'Micah', 'Micah': 'Micah',
			'Nah': 'Nahum', 'Nahum': 'Nahum',
			'Hab': 'Habakkuk', 'Habakkuk': 'Habakkuk',
			'Zeph': 'Zephaniah', 'Zephaniah': 'Zephaniah',
			'Hag': 'Haggai', 'Haggai': 'Haggai',
			'Zech': 'Zechariah', 'Zechariah': 'Zechariah',
			'Mal': 'Malachi', 'Malachi': 'Malachi',
			// New Testament
			'Matt': 'Matthew', 'Mt': 'Matthew', 'Matthew': 'Matthew',
			'Mark': 'Mark', 'Mk': 'Mark',
			'Luke': 'Luke', 'Lk': 'Luke',
			'John': 'John', 'Jn': 'John',
			'Acts': 'Acts',
			'Rom': 'Romans', 'Romans': 'Romans',
			'1Cor': '1 Corinthians', '1 Cor': '1 Corinthians', '1 Corinthians': '1 Corinthians',
			'2Cor': '2 Corinthians', '2 Cor': '2 Corinthians', '2 Corinthians': '2 Corinthians',
			'Gal': 'Galatians', 'Galatians': 'Galatians',
			'Eph': 'Ephesians', 'Ephesians': 'Ephesians',
			'Phil': 'Philippians', 'Philippians': 'Philippians',
			'Col': 'Colossians', 'Colossians': 'Colossians',
			'1Thess': '1 Thessalonians', '1 Thess': '1 Thessalonians', '1 Thessalonians': '1 Thessalonians',
			'2Thess': '2 Thessalonians', '2 Thess': '2 Thessalonians', '2 Thessalonians': '2 Thessalonians',
			'1Tim': '1 Timothy', '1 Timothy': '1 Timothy',
			'2Tim': '2 Timothy', '2 Timothy': '2 Timothy',
			'Titus': 'Titus',
			'Phlm': 'Philemon', 'Philemon': 'Philemon',
			'Heb': 'Hebrews', 'Hebrews': 'Hebrews',
			'Jas': 'James', 'James': 'James',
			'1Pet': '1 Peter', '1 Peter': '1 Peter',
			'2Pet': '2 Peter', '2 Peter': '2 Peter',
			'1John': '1 John', '1 Jn': '1 John', '1 John': '1 John',
			'2John': '2 John', '2 Jn': '2 John', '2 John': '2 John',
			'3John': '3 John', '3 Jn': '3 John', '3 John': '3 John',
			'Jude': 'Jude',
			'Rev': 'Revelation', 'Revelation': 'Revelation'
		},

		// Chinese book names
		chineseBooks: {
			// 舊約
			'創': 'Genesis', '創世記': 'Genesis', '創世紀': 'Genesis',
			'出': 'Exodus', '出埃及記': 'Exodus',
			'利': 'Leviticus', '利未記': 'Leviticus',
			'民': 'Numbers', '民數記': 'Numbers',
			'申': 'Deuteronomy', '申命記': 'Deuteronomy',
			'書': 'Joshua', '約書亞記': 'Joshua',
			'士': 'Judges', '士師記': 'Judges',
			'得': 'Ruth', '路得記': 'Ruth',
			'撒上': '1 Samuel', '撒母耳記上': '1 Samuel',
			'撒下': '2 Samuel', '撒母耳記下': '2 Samuel',
			'王上': '1 Kings', '列王紀上': '1 Kings',
			'王下': '2 Kings', '列王紀下': '2 Kings',
			'代上': '1 Chronicles', '歷代志上': '1 Chronicles',
			'代下': '2 Chronicles', '歷代志下': '2 Chronicles',
			'拉': 'Ezra', '以斯拉記': 'Ezra',
			'尼': 'Nehemiah', '尼希米記': 'Nehemiah',
			'斯': 'Esther', '以斯帖記': 'Esther',
			'伯': 'Job', '約伯記': 'Job',
			'詩': 'Psalms', '詩篇': 'Psalms',
			'箴': 'Proverbs', '箴言': 'Proverbs',
			'傳': 'Ecclesiastes', '傳道書': 'Ecclesiastes',
			'歌': 'Song of Solomon', '雅歌': 'Song of Solomon',
			'賽': 'Isaiah', '以賽亞書': 'Isaiah',
			'耶': 'Jeremiah', '耶利米書': 'Jeremiah',
			'哀': 'Lamentations', '耶利米哀歌': 'Lamentations',
			'結': 'Ezekiel', '以西結書': 'Ezekiel',
			'但': 'Daniel', '但以理書': 'Daniel',
			'何': 'Hosea', '何西阿書': 'Hosea',
			'珥': 'Joel', '約珥書': 'Joel',
			'摩': 'Amos', '阿摩司書': 'Amos',
			'俄': 'Obadiah', '俄巴底亞書': 'Obadiah',
			'拿': 'Jonah', '約拿書': 'Jonah',
			'彌': 'Micah', '彌迦書': 'Micah',
			'鴻': 'Nahum', '那鴻書': 'Nahum',
			'哈': 'Habakkuk', '哈巴谷書': 'Habakkuk',
			'番': 'Zephaniah', '西番雅書': 'Zephaniah',
			'該': 'Haggai', '哈該書': 'Haggai',
			'亞': 'Zechariah', '撒迦利亞書': 'Zechariah',
			'瑪': 'Malachi', '瑪拉基書': 'Malachi',
			// 新約
			'太': 'Matthew', '馬太福音': 'Matthew',
			'可': 'Mark', '馬可福音': 'Mark',
			'路': 'Luke', '路加福音': 'Luke',
			'約': 'John', '約翰福音': 'John',
			'徒': 'Acts', '使徒行傳': 'Acts',
			'羅': 'Romans', '羅馬書': 'Romans',
			'林前': '1 Corinthians', '哥林多前書': '1 Corinthians',
			'林後': '2 Corinthians', '哥林多後書': '2 Corinthians',
			'加': 'Galatians', '加拉太書': 'Galatians',
			'弗': 'Ephesians', '以弗所書': 'Ephesians',
			'腓': 'Philippians', '腓立比書': 'Philippians',
			'西': 'Colossians', '歌羅西書': 'Colossians',
			'帖前': '1 Thessalonians', '帖撒羅尼迦前書': '1 Thessalonians',
			'帖後': '2 Thessalonians', '帖撒羅尼迦後書': '2 Thessalonians',
			'提前': '1 Timothy', '提摩太前書': '1 Timothy',
			'提後': '2 Timothy', '提摩太後書': '2 Timothy',
			'多': 'Titus', '提多書': 'Titus',
			'門': 'Philemon', '腓利門書': 'Philemon',
			'來': 'Hebrews', '希伯來書': 'Hebrews',
			'雅': 'James', '雅各書': 'James',
			'彼前': '1 Peter', '彼得前書': '1 Peter',
			'彼後': '2 Peter', '彼得後書': '2 Peter',
			'約一': '1 John', '約翰一書': '1 John',
			'約二': '2 John', '約翰二書': '2 John',
			'約三': '3 John', '約翰三書': '3 John',
			'猶': 'Jude', '猶大書': 'Jude',
			'啟': 'Revelation', '啟示錄': 'Revelation'
		},

		// Generate regex patterns for book detection
		generateBookRegex: function() {
			var allBooks = [];
			
			// Add English books
			for (var abbrev in this.englishBooks) {
				allBooks.push(escapeRegex(abbrev));
			}
			
			// Add Chinese books
			for (var abbrev in this.chineseBooks) {
				allBooks.push(escapeRegex(abbrev));
			}
			
			// Sort by length (longest first) to avoid partial matches
			allBooks.sort(function(a, b) {
				return b.length - a.length;
			});
			
			return allBooks.join('|');
		},

		// Main regex pattern for Bible references
		getReferenceRegex: function() {
			var bookPattern = this.generateBookRegex();
			// Pattern matches: Book Chapter:Verse or Book Chapter:Verse-Verse or Book Chapter
			return new RegExp(
				'\\b(' + bookPattern + ')\\s*(\\d+)(?::(\\d+)(?:-(\\d+))?)?\\b',
				'gi'
			);
		},

		// Parse a Bible reference string
		parseReference: function(referenceText) {
			var regex = this.getReferenceRegex();
			var match = regex.exec(referenceText);
			
			if (!match) {
				return null;
			}
			
			var bookAbbrev = match[1];
			var chapter = parseInt(match[2]);
			var startVerse = match[3] ? parseInt(match[3]) : null;
			var endVerse = match[4] ? parseInt(match[4]) : startVerse;
			
			// Normalize book name
			var bookName = this.normalizeBookName(bookAbbrev);
			if (!bookName) {
				return null;
			}
			
			return {
				book: bookName,
				chapter: chapter,
				startVerse: startVerse,
				endVerse: endVerse,
				originalText: match[0]
			};
		},

		// Normalize book abbreviation to full name
		normalizeBookName: function(abbrev) {
			// Check English books first
			if (this.englishBooks[abbrev]) {
				return this.englishBooks[abbrev];
			}
			
			// Check Chinese books
			if (this.chineseBooks[abbrev]) {
				return this.chineseBooks[abbrev];
			}
			
			// Case-insensitive search for English books
			for (var key in this.englishBooks) {
				if (key.toLowerCase() === abbrev.toLowerCase()) {
					return this.englishBooks[key];
				}
			}
			
			return null;
		},

		// Find all Bible references in text
		findReferences: function(text) {
			var references = [];
			var regex = this.getReferenceRegex();
			var match;
			
			while ((match = regex.exec(text)) !== null) {
				var reference = this.parseReference(match[0]);
				if (reference) {
					reference.index = match.index;
					reference.length = match[0].length;
					references.push(reference);
				}
			}
			
			return references;
		}
	};

	/**
	 * DOM Scanner for Bible Reference Auto-Detection
	 * Scans DOM for Bible references and converts them to interactive links
	 */
	var bibleDOMScanner = {
		// Configuration
		config: {
			enabled: true,
			defaultVersion: 'KJV',
			processedClass: 'bible-here-processed',
			linkClass: 'bible-here-auto-link',
			excludeSelectors: [
				'script', 'style', 'code', 'pre', 'textarea', 'input',
				'.bible-here-reader', '.bible-search-interface',
				'.bible-here-processed', '.bible-here-auto-link'
			],
			includeSelectors: [
				'p', 'div', 'span', 'td', 'th', 'li', 'blockquote',
				'article', 'section', 'main', 'aside', 'header', 'footer'
			],
			// Performance optimization settings
			throttleDelay: 100,
			debounceDelay: 300,
			maxTextLength: 10000,
			batchSize: 50,
			processingTimeout: 16
		},

		// Initialize DOM scanner
		init: function(options) {
			if (options) {
				$.extend(this.config, options);
			}
			
			if (this.config.enabled) {
				this.scanDocument();
				this.observeChanges();
			}
		},

		// Scan entire document for Bible references
		scanDocument: function() {
			var self = this;
			
			// Find all text nodes that might contain Bible references
			var textNodes = this.getTextNodes(document.body);
			
			textNodes.forEach(function(node) {
				self.processTextNode(node);
			});
		},

		// Get all text nodes in the document
		getTextNodes: function(element) {
			var textNodes = [];
			var self = this;
			
			// Skip excluded elements
			if (this.shouldExcludeElement(element)) {
				return textNodes;
			}
			
			// Use TreeWalker for efficient text node traversal
			var walker = document.createTreeWalker(
				element,
				NodeFilter.SHOW_TEXT,
				{
					acceptNode: function(node) {
						// Skip empty or whitespace-only nodes
						if (!node.textContent.trim()) {
							return NodeFilter.FILTER_REJECT;
						}
						
						// Skip nodes in excluded elements
						if (self.shouldExcludeElement(node.parentElement)) {
							return NodeFilter.FILTER_REJECT;
						}
						
						return NodeFilter.FILTER_ACCEPT;
					}
				},
				false
			);
			
			var node;
			while (node = walker.nextNode()) {
				textNodes.push(node);
			}
			
			return textNodes;
		},

		// Check if element should be excluded from processing
		shouldExcludeElement: function(element) {
			if (!element || element.nodeType !== Node.ELEMENT_NODE) {
				return false;
			}
			
			// Check if element matches any exclude selector
			for (var i = 0; i < this.config.excludeSelectors.length; i++) {
				if (element.matches && element.matches(this.config.excludeSelectors[i])) {
					return true;
				}
				
				// Check if any parent matches exclude selector
				if (element.closest && element.closest(this.config.excludeSelectors[i])) {
					return true;
				}
			}
			
			return false;
		},

		// Check if text node should be processed (performance optimization)
		shouldProcessTextNode: function(textNode) {
			var text = textNode.textContent;
			
			// Skip empty or very short text
			if (!text || text.trim().length < 3) {
				return false;
			}
			
			// Skip very long text nodes for performance
			if (text.length > this.config.maxTextLength) {
				return false;
			}
			
			// Skip if already processed
			if (textNode.parentElement && textNode.parentElement.hasAttribute('data-bible-processed')) {
				return false;
			}
			
			// Skip if no potential bible references (quick check)
			if (!/\b\d+:\d+|\b(Gen|Exo|Lev|Num|Deu|Jos|Jud|Rut|1Sa|2Sa|1Ki|2Ki|1Ch|2Ch|Ezr|Neh|Est|Job|Psa|Pro|Ecc|Son|Isa|Jer|Lam|Eze|Dan|Hos|Joe|Amo|Oba|Jon|Mic|Nah|Hab|Zep|Hag|Zec|Mal|Mat|Mar|Luk|Joh|Act|Rom|1Co|2Co|Gal|Eph|Phi|Col|1Th|2Th|1Ti|2Ti|Tit|Phm|Heb|Jam|1Pe|2Pe|1Jo|2Jo|3Jo|Jud|Rev|創|出|利|民|申|書|士|得|撒上|撒下|王上|王下|代上|代下|拉|尼|斯|伯|詩|箴|傳|歌|賽|耶|哀|結|但|何|珥|摩|俄|拿|彌|鴻|哈|番|該|亞|瑪|太|可|路|約|徒|羅|林前|林後|加|弗|腓|西|帖前|帖後|提前|提後|多|門|來|雅|彼前|彼後|約一|約二|約三|猶|啟)\b/i.test(text)) {
				return false;
			}
			
			return true;
		},
		
		// Process a single text node for Bible references
		processTextNode: function(textNode) {
			// Performance check
			if (!this.shouldProcessTextNode(textNode)) {
				return;
			}
			
			var text = textNode.textContent;
			var references = bibleReferenceEngine.findReferences(text);
			
			if (references.length === 0) {
				return;
			}
			
			// Mark parent as processed to avoid reprocessing
			if (textNode.parentElement) {
				textNode.parentElement.setAttribute('data-bible-processed', 'true');
			}
			
			// Create document fragment to hold new content
			var fragment = document.createDocumentFragment();
			var lastIndex = 0;
			
			// Process each reference
			references.forEach(function(reference) {
				// Add text before reference
				if (reference.index > lastIndex) {
					var beforeText = text.substring(lastIndex, reference.index);
					fragment.appendChild(document.createTextNode(beforeText));
				}
				
				// Create link for reference
				var link = this.createReferenceLink(reference);
				fragment.appendChild(link);
				
				lastIndex = reference.index + reference.length;
			}.bind(this));
			
			// Add remaining text
			if (lastIndex < text.length) {
				var remainingText = text.substring(lastIndex);
				fragment.appendChild(document.createTextNode(remainingText));
			}
			
			// Replace text node with fragment
			textNode.parentNode.replaceChild(fragment, textNode);
		},

		// Create interactive link for Bible reference
		createReferenceLink: function(reference) {
			var link = document.createElement('span');
			link.className = this.config.linkClass;
			link.textContent = reference.originalText;
			link.style.cursor = 'pointer';
			link.style.color = '#0073aa';
			link.style.textDecoration = 'underline';
			link.style.borderBottom = '1px dotted #0073aa';
			
			// Store reference data
			link.setAttribute('data-book', reference.book);
			link.setAttribute('data-chapter', reference.chapter);
			if (reference.startVerse) {
				link.setAttribute('data-start-verse', reference.startVerse);
			}
			if (reference.endVerse && reference.endVerse !== reference.startVerse) {
				link.setAttribute('data-end-verse', reference.endVerse);
			}
			link.setAttribute('data-version', this.config.defaultVersion);
			
			// Add hover effect
			link.addEventListener('mouseenter', function() {
				this.style.backgroundColor = '#f0f8ff';
				this.style.padding = '2px 4px';
				this.style.borderRadius = '3px';
			});
			
			link.addEventListener('mouseleave', function() {
				this.style.backgroundColor = 'transparent';
				this.style.padding = '0';
				this.style.borderRadius = '0';
			});
			
			// Add click handler
			link.addEventListener('click', function(e) {
				e.preventDefault();
				biblePopupSystem.showPopup(this, reference);
			});
			
			return link;
		},

		// Observe DOM changes for dynamic content
		observeChanges: function() {
			if (!window.MutationObserver) {
				return;
			}
			
			var self = this;
			var debounceTimer;
			
			var observer = new MutationObserver(function(mutations) {
				// Debounce mutations to avoid excessive processing
				clearTimeout(debounceTimer);
				debounceTimer = setTimeout(function() {
					var nodesToProcess = [];
					
					mutations.forEach(function(mutation) {
						if (mutation.type === 'childList') {
							mutation.addedNodes.forEach(function(node) {
								if (node.nodeType === Node.ELEMENT_NODE && 
									!node.hasAttribute('data-bible-processed')) {
									nodesToProcess.push(node);
								}
							});
						}
					});
					
					// Process nodes in batches
					if (nodesToProcess.length > 0) {
						self.processBatch(nodesToProcess);
					}
				}, self.config.debounceDelay);
			});
			
			observer.observe(document.body, {
				childList: true,
				subtree: true
			});
		},

		// Process a specific element
		processElement: function(element) {
			if (this.shouldExcludeElement(element)) {
				return;
			}
			
			var textNodes = this.getTextNodes(element);
			var self = this;
			
			textNodes.forEach(function(node) {
				self.processTextNode(node);
			});
		},

		// Enable/disable scanner
		setEnabled: function(enabled) {
			this.config.enabled = enabled;
			if (enabled) {
				this.scanDocument();
			}
		},

		// Process nodes in batches for better performance
		processBatch: function(nodes) {
			var self = this;
			var currentBatch = 0;
			
			function processBatchChunk() {
				var chunkStartTime = performance.now();
				
				for (var i = currentBatch; i < Math.min(currentBatch + self.config.batchSize, nodes.length); i++) {
					// Check if we've exceeded processing time limit
					if (performance.now() - chunkStartTime > self.config.processingTimeout) {
						currentBatch = i;
						// Continue processing in next frame
						requestAnimationFrame(processBatchChunk);
						return;
					}
					
					self.processElement(nodes[i]);
				}
				
				currentBatch += self.config.batchSize;
				
				// Continue with next batch if there are more nodes
				if (currentBatch < nodes.length) {
					requestAnimationFrame(processBatchChunk);
				}
			}
			
			// Start processing
			requestAnimationFrame(processBatchChunk);
		},
		
		// Throttled processing for better performance
		throttledProcess: function(element) {
			var self = this;
			
			if (!this.throttleTimer) {
				this.throttleTimer = setTimeout(function() {
					self.processElement(element);
					self.throttleTimer = null;
				}, this.config.throttleDelay);
			}
		},
		
		// Update configuration
		updateConfig: function(newConfig) {
			$.extend(this.config, newConfig);
		}
	};

	/**
	 * Bible Popup System
	 * Handles popup display for Bible references
	 */
	var biblePopupSystem = {
		// Configuration
		config: {
			popupClass: 'bible-here-popup',
			overlayClass: 'bible-here-popup-overlay',
			contentClass: 'bible-here-popup-content',
			headerClass: 'bible-here-popup-header',
			bodyClass: 'bible-here-popup-body',
			footerClass: 'bible-here-popup-footer',
			closeClass: 'bible-here-popup-close',
			loadingClass: 'bible-here-popup-loading',
			errorClass: 'bible-here-popup-error',
			defaultVersion: 'KJV',
			maxWidth: '600px',
			maxHeight: '80vh',
			animationDuration: 200
		},

		// Current popup state
		currentPopup: null,
		currentReference: null,
		currentTrigger: null,

		// Initialize popup system
		init: function(options) {
			if (options) {
				$.extend(this.config, options);
			}
			
			this.createStyles();
			this.bindGlobalEvents();
		},

		// Create CSS styles for popup
		createStyles: function() {
			if (document.getElementById('bible-here-popup-styles')) {
				return;
			}
			
			var style = document.createElement('style');
			style.id = 'bible-here-popup-styles';
			style.textContent = `
				.${this.config.overlayClass} {
					position: fixed;
					top: 0;
					left: 0;
					width: 100%;
					height: 100%;
					background: rgba(0, 0, 0, 0.5);
					z-index: 999999;
					display: flex;
					align-items: center;
					justify-content: center;
					padding: 20px;
					box-sizing: border-box;
					opacity: 0;
					transition: opacity ${this.config.animationDuration}ms ease;
				}
				
				.${this.config.overlayClass}.show {
					opacity: 1;
				}
				
				.${this.config.popupClass} {
					background: white;
					border-radius: 8px;
					box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
					max-width: ${this.config.maxWidth};
					max-height: ${this.config.maxHeight};
					width: 100%;
					display: flex;
					flex-direction: column;
					position: relative;
					transform: scale(0.9) translateY(-20px);
					transition: transform ${this.config.animationDuration}ms ease;
				}
				
				.${this.config.overlayClass}.show .${this.config.popupClass} {
					transform: scale(1) translateY(0);
				}
				
				.${this.config.headerClass} {
					padding: 20px 20px 10px;
					border-bottom: 1px solid #eee;
					display: flex;
					justify-content: space-between;
					align-items: center;
					flex-shrink: 0;
				}
				
				.${this.config.headerClass} h3 {
					margin: 0;
					font-size: 18px;
					color: #333;
					font-weight: 600;
				}
				
				.${this.config.closeClass} {
					background: none;
					border: none;
					font-size: 24px;
					cursor: pointer;
					color: #666;
					padding: 0;
					width: 30px;
					height: 30px;
					display: flex;
					align-items: center;
					justify-content: center;
					border-radius: 50%;
					transition: background-color 0.2s ease;
				}
				
				.${this.config.closeClass}:hover {
					background-color: #f0f0f0;
					color: #333;
				}
				
				.${this.config.bodyClass} {
					padding: 20px;
					overflow-y: auto;
					flex: 1;
					min-height: 0;
				}
				
				.${this.config.footerClass} {
					padding: 10px 20px 20px;
					border-top: 1px solid #eee;
					display: flex;
					justify-content: space-between;
					align-items: center;
					flex-shrink: 0;
				}
				
				.${this.config.loadingClass} {
					text-align: center;
					padding: 40px 20px;
					color: #666;
				}
				
				.${this.config.errorClass} {
					text-align: center;
					padding: 40px 20px;
					color: #d63638;
				}
				
				.bible-verse {
					margin-bottom: 12px;
					line-height: 1.6;
				}
				
				.bible-verse-number {
					font-weight: bold;
					color: #0073aa;
					margin-right: 8px;
				}
				
				.bible-verse-text {
					color: #333;
				}
				
				.bible-navigation {
					display: flex;
					gap: 10px;
				}
				
				.bible-nav-btn {
					padding: 8px 16px;
					background: #0073aa;
					color: white;
					border: none;
					border-radius: 4px;
					cursor: pointer;
					font-size: 14px;
					transition: background-color 0.2s ease;
				}
				
				.bible-nav-btn:hover {
					background: #005a87;
				}
				
				.bible-nav-btn:disabled {
					background: #ccc;
					cursor: not-allowed;
				}
				
				@media (max-width: 768px) {
					.${this.config.overlayClass} {
						padding: 10px;
					}
					
					.${this.config.popupClass} {
						max-height: 90vh;
					}
					
					.${this.config.headerClass},
					.${this.config.bodyClass},
					.${this.config.footerClass} {
						padding-left: 15px;
						padding-right: 15px;
					}
				}
			`;
			
			document.head.appendChild(style);
		},

		// Bind global events
		bindGlobalEvents: function() {
			var self = this;
			
			// Close popup on Escape key
			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape' && self.currentPopup) {
					self.closePopup();
				}
			});
		},

		// Show popup for Bible reference
		showPopup: function(trigger, reference, version) {
			this.currentTrigger = trigger;
			this.currentReference = reference;
			
			// Use specified version or default
			var selectedVersion = version || trigger.getAttribute('data-version') || this.config.defaultVersion;
			
			// Create popup if it doesn't exist
			if (!this.currentPopup) {
				this.createPopup();
			}
			
			// Show loading state
			this.showLoading();
			
			// Load verse content
			this.loadVerseContent(reference, selectedVersion);
			
			// Show popup
			this.currentPopup.style.display = 'flex';
			setTimeout(function() {
				this.currentPopup.classList.add('show');
			}.bind(this), 10);
		},

		// Create popup HTML structure
		createPopup: function() {
			var overlay = document.createElement('div');
			overlay.className = this.config.overlayClass;
			overlay.style.display = 'none';
			
			var popup = document.createElement('div');
			popup.className = this.config.popupClass;
			
			// Header
			var header = document.createElement('div');
			header.className = this.config.headerClass;
			
			var title = document.createElement('h3');
			title.id = 'popup-title';
			header.appendChild(title);
			
			var closeBtn = document.createElement('button');
			closeBtn.className = this.config.closeClass;
			closeBtn.innerHTML = '&times;';
			closeBtn.setAttribute('aria-label', 'Close');
			header.appendChild(closeBtn);
			
			// Body
			var body = document.createElement('div');
			body.className = this.config.bodyClass;
			body.id = 'popup-body';
			
			// Footer
			var footer = document.createElement('div');
			footer.className = this.config.footerClass;
			footer.id = 'popup-footer';
			
			// Assemble popup
			popup.appendChild(header);
			popup.appendChild(body);
			popup.appendChild(footer);
			overlay.appendChild(popup);
			
			// Add to document
			document.body.appendChild(overlay);
			
			// Bind events
			this.bindPopupEvents(overlay, closeBtn);
			
			this.currentPopup = overlay;
		},

		// Bind popup events
		bindPopupEvents: function(overlay, closeBtn) {
			var self = this;
			
			// Close on overlay click
			overlay.addEventListener('click', function(e) {
				if (e.target === overlay) {
					self.closePopup();
				}
			});
			
			// Close on close button click
			closeBtn.addEventListener('click', function() {
				self.closePopup();
			});
		},

		// Show loading state
		showLoading: function() {
			var body = document.getElementById('popup-body');
			var title = document.getElementById('popup-title');
			var footer = document.getElementById('popup-footer');
			
			title.textContent = 'Loading...';
			body.innerHTML = '<div class="' + this.config.loadingClass + '">Loading Bible verse...</div>';
			footer.innerHTML = '';
		},

		// Load verse content via AJAX
		loadVerseContent: function(reference, version) {
			var self = this;
			
			$.ajax({
				url: bibleHereAjax.ajaxurl,
				type: 'POST',
				data: {
					action: 'bible_here_get_verses',
					nonce: bibleHereAjax.nonce,
					version: version,
					book: reference.book,
					chapter: reference.chapter,
					start_verse: reference.startVerse || 1,
					end_verse: reference.endVerse || reference.startVerse || 1
				},
				success: function(response) {
					if (response.success && response.data.verses) {
						self.displayVerseContent(response.data, reference, version);
					} else {
						self.showError('Failed to load verse content.');
					}
				},
				error: function() {
					self.showError('Network error occurred while loading verse.');
				}
			});
		},

		// Display verse content in popup
		displayVerseContent: function(data, reference, version) {
			var title = document.getElementById('popup-title');
			var body = document.getElementById('popup-body');
			var footer = document.getElementById('popup-footer');
			
			// Set title
			var titleText = reference.book + ' ' + reference.chapter;
			if (reference.startVerse) {
				titleText += ':' + reference.startVerse;
				if (reference.endVerse && reference.endVerse !== reference.startVerse) {
					titleText += '-' + reference.endVerse;
				}
			}
			titleText += ' (' + version + ')';
			title.textContent = titleText;
			
			// Display verses
			var versesHtml = '';
			data.verses.forEach(function(verse) {
				versesHtml += '<div class="bible-verse">';
				versesHtml += '<span class="bible-verse-number">' + verse.verse + '</span>';
				versesHtml += '<span class="bible-verse-text">' + verse.text + '</span>';
				versesHtml += '</div>';
			});
			
			body.innerHTML = versesHtml;
			
			// Add navigation buttons
			this.createNavigationButtons(footer, reference, version);
		},

		// Create navigation buttons
		createNavigationButtons: function(footer, reference, version) {
			var navHtml = '<div class="bible-navigation">';
			
			// Previous chapter button
			if (reference.chapter > 1) {
				navHtml += '<button class="bible-nav-btn" data-action="prev-chapter">Previous Chapter</button>';
			}
			
			// Next chapter button
			navHtml += '<button class="bible-nav-btn" data-action="next-chapter">Next Chapter</button>';
			
			navHtml += '</div>';
			
			footer.innerHTML = navHtml;
			
			// Bind navigation events
			this.bindNavigationEvents(footer, reference, version);
		},

		// Bind navigation button events
		bindNavigationEvents: function(footer, reference, version) {
			var self = this;
			var buttons = footer.querySelectorAll('.bible-nav-btn');
			
			buttons.forEach(function(button) {
				button.addEventListener('click', function() {
					var action = this.getAttribute('data-action');
					var newReference = Object.assign({}, reference);
					
					if (action === 'prev-chapter') {
						newReference.chapter = parseInt(reference.chapter) - 1;
					} else if (action === 'next-chapter') {
						newReference.chapter = parseInt(reference.chapter) + 1;
					}
					
					// Reset verse range for chapter navigation
					newReference.startVerse = 1;
					newReference.endVerse = null;
					
					self.showLoading();
					self.loadVerseContent(newReference, version);
					self.currentReference = newReference;
				});
			});
		},

		// Show error message
		showError: function(message) {
			var body = document.getElementById('popup-body');
			var title = document.getElementById('popup-title');
			var footer = document.getElementById('popup-footer');
			
			title.textContent = 'Error';
			body.innerHTML = '<div class="' + this.config.errorClass + '">' + message + '</div>';
			footer.innerHTML = '';
		},

		// Close popup
		closePopup: function() {
			if (!this.currentPopup) {
				return;
			}
			
			var self = this;
			this.currentPopup.classList.remove('show');
			
			setTimeout(function() {
				if (self.currentPopup) {
					self.currentPopup.style.display = 'none';
				}
			}, this.config.animationDuration);
			
			this.currentReference = null;
			this.currentTrigger = null;
		}
	};

	// Bible Shortcode Processor
	window.bibleShortcodeProcessor = {
		// Configuration
		config: {
			shortcodePattern: /\[bible-here\s+([^\]]+)\]/gi,
			refPattern: /ref=["']([^"']+)["']/i,
			versionPattern: /version=["']([^"']+)["']/i,
			stylePattern: /style=["']([^"']+)["']/i,
			classPattern: /class=["']([^"']+)["']/i,
			defaultVersion: 'NIV',
			defaultClass: 'bible-shortcode-link',
			processOnLoad: true
		},

		// Initialize shortcode processor
		init: function() {
			if (this.config.processOnLoad) {
				this.processDocument();
			}
			
			// Watch for dynamic content changes
			this.observeChanges();
		},

		// Process entire document for shortcodes
		processDocument: function() {
			this.processElement(document.body);
		},

		// Process specific element for shortcodes
		processElement: function(element) {
			if (!element || element.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			// Skip if already processed
			if (element.hasAttribute('data-bible-processed')) {
				return;
			}

			var textNodes = this.getTextNodes(element);
			var self = this;

			textNodes.forEach(function(node) {
				self.processTextNode(node);
			});

			// Mark as processed
			element.setAttribute('data-bible-processed', 'true');
		},

		// Get all text nodes from element
		getTextNodes: function(element) {
			var textNodes = [];
			var walker = document.createTreeWalker(
				element,
				NodeFilter.SHOW_TEXT,
				{
					acceptNode: function(node) {
						// Skip script, style, and other non-content elements
						var parent = node.parentElement;
						if (!parent) return NodeFilter.FILTER_REJECT;
						
						var tagName = parent.tagName.toLowerCase();
						if (['script', 'style', 'noscript', 'textarea', 'pre', 'code'].includes(tagName)) {
							return NodeFilter.FILTER_REJECT;
						}
						
						// Skip if parent already has bible reference
						if (parent.classList.contains('bible-reference') || 
							parent.classList.contains('bible-shortcode-link')) {
							return NodeFilter.FILTER_REJECT;
						}
						
						return NodeFilter.FILTER_ACCEPT;
					}
				}
			);

			var node;
			while (node = walker.nextNode()) {
				textNodes.push(node);
			}

			return textNodes;
		},

		// Process individual text node
		processTextNode: function(textNode) {
			var text = textNode.textContent;
			var matches = [];
			var match;

			// Reset regex lastIndex
			this.config.shortcodePattern.lastIndex = 0;

			// Find all shortcode matches
			while ((match = this.config.shortcodePattern.exec(text)) !== null) {
				matches.push({
					fullMatch: match[0],
					attributes: match[1],
					index: match.index,
					length: match[0].length
				});
			}

			// Process matches in reverse order to maintain indices
			if (matches.length > 0) {
				this.replaceShortcodes(textNode, matches.reverse());
			}
		},

		// Replace shortcodes with interactive elements
		replaceShortcodes: function(textNode, matches) {
			var parent = textNode.parentNode;
			var text = textNode.textContent;
			var offset = 0;

			matches.forEach(function(match) {
				var beforeText = text.substring(0, match.index + offset);
				var afterText = text.substring(match.index + offset + match.length);
				
				// Parse shortcode attributes
				var attributes = this.parseShortcodeAttributes(match.attributes);
				
				if (attributes.ref) {
					// Create replacement element
					var linkElement = this.createShortcodeElement(attributes);
					
					// Split text node and insert link
					if (beforeText) {
						var beforeNode = document.createTextNode(beforeText);
						parent.insertBefore(beforeNode, textNode);
					}
					
					parent.insertBefore(linkElement, textNode);
					
					// Update text and offset for next iteration
					text = afterText;
					textNode.textContent = afterText;
					offset = 0;
				}
			}.bind(this));
		},

		// Parse shortcode attributes
		parseShortcodeAttributes: function(attributeString) {
			var attributes = {};
			
			// Extract ref
			var refMatch = this.config.refPattern.exec(attributeString);
			if (refMatch) {
				attributes.ref = refMatch[1];
			}
			
			// Extract version
			var versionMatch = this.config.versionPattern.exec(attributeString);
			if (versionMatch) {
				attributes.version = versionMatch[1];
			} else {
				attributes.version = this.config.defaultVersion;
			}
			
			// Extract style
			var styleMatch = this.config.stylePattern.exec(attributeString);
			if (styleMatch) {
				attributes.style = styleMatch[1];
			}
			
			// Extract class
			var classMatch = this.config.classPattern.exec(attributeString);
			if (classMatch) {
				attributes.class = classMatch[1];
			} else {
				attributes.class = this.config.defaultClass;
			}
			
			return attributes;
		},

		// Create shortcode element
		createShortcodeElement: function(attributes) {
			var link = document.createElement('a');
			link.href = '#';
			link.className = attributes.class;
			link.textContent = attributes.ref;
			
			// Add data attributes
			link.setAttribute('data-bible-ref', attributes.ref);
			link.setAttribute('data-bible-version', attributes.version);
			link.setAttribute('data-bible-shortcode', 'true');
			
			// Apply custom style if provided
			if (attributes.style) {
				link.setAttribute('style', attributes.style);
			}
			
			// Bind click event
			this.bindShortcodeEvents(link, attributes);
			
			return link;
		},

		// Bind shortcode element events
		bindShortcodeEvents: function(element, attributes) {
			var self = this;
			
			// Click event
			element.addEventListener('click', function(e) {
				e.preventDefault();
				
				// Parse reference using the reference engine
				if (window.bibleReferenceEngine) {
					var parsedRef = window.bibleReferenceEngine.parseReference(attributes.ref);
					if (parsedRef) {
						// Show popup using the popup system
						if (window.biblePopupSystem) {
							window.biblePopupSystem.showPopup(parsedRef, attributes.version, element);
						}
					}
				}
			});
			
			// Hover events for preview (optional)
			var hoverTimeout;
			element.addEventListener('mouseenter', function() {
				clearTimeout(hoverTimeout);
				hoverTimeout = setTimeout(function() {
					// Could add hover preview functionality here
				}, 500);
			});
			
			element.addEventListener('mouseleave', function() {
				clearTimeout(hoverTimeout);
			});
		},

		// Observe DOM changes for dynamic content
		observeChanges: function() {
			if (!window.MutationObserver) {
				return;
			}
			
			var self = this;
			var observer = new MutationObserver(function(mutations) {
				mutations.forEach(function(mutation) {
					if (mutation.type === 'childList') {
						mutation.addedNodes.forEach(function(node) {
							if (node.nodeType === Node.ELEMENT_NODE) {
								self.processElement(node);
							}
						});
					}
				});
			});
			
			observer.observe(document.body, {
				childList: true,
				subtree: true
			});
			
			this.mutationObserver = observer;
		},

		// Enable/disable shortcode processing
		setEnabled: function(enabled) {
			this.config.processOnLoad = enabled;
			
			if (enabled) {
				this.processDocument();
			}
		},

		// Update configuration
		updateConfig: function(newConfig) {
			Object.assign(this.config, newConfig);
		},

		// Manual processing trigger
		refresh: function() {
			// Remove processed markers
			var processedElements = document.querySelectorAll('[data-bible-processed]');
			processedElements.forEach(function(element) {
				element.removeAttribute('data-bible-processed');
			});
			
			// Reprocess document
			this.processDocument();
		}
	};

})( jQuery );
