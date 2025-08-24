<?php
/**
 * Bible Search Interface Template
 *
 * This file is used to markup the search interface for the plugin.
 *
 * @link       https://github.com/bible-here/bible-here
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/public/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div class="bible-search-interface" id="bible-search-<?php echo esc_attr($search_id); ?>">
	<!-- Search Header -->
	<div class="bible-search-header">
		<h3 class="search-title"><?php _e('Bible Search', 'bible-here'); ?></h3>
		<div class="search-stats" style="display: none;">
			<span class="search-results-count">0</span> <?php _e('results found', 'bible-here'); ?>
		</div>
	</div>

	<!-- Search Form -->
	<div class="bible-search-form">
		<div class="search-input-container">
			<input type="text" 
				   class="bible-search-input" 
				   id="search-input-<?php echo esc_attr($search_id); ?>"
				   placeholder="<?php _e('Enter keywords to search...', 'bible-here'); ?>"
				   autocomplete="off">
			<button type="button" class="bible-search-button" id="search-btn-<?php echo esc_attr($search_id); ?>">
				<span class="search-icon">🔍</span>
				<span class="search-text"><?php _e('Search', 'bible-here'); ?></span>
			</button>
			<button type="button" class="bible-search-clear" id="clear-btn-<?php echo esc_attr($search_id); ?>" style="display: none;">
				<span class="clear-icon">✕</span>
			</button>
		</div>

		<!-- Search Suggestions -->
		<div class="search-suggestions" id="suggestions-<?php echo esc_attr($search_id); ?>" style="display: none;">
			<ul class="suggestions-list"></ul>
		</div>
	</div>

	<!-- Advanced Search Filters -->
	<div class="bible-search-filters">
		<div class="filters-toggle">
			<button type="button" class="toggle-filters-btn">
				<span class="toggle-text"><?php _e('Advanced Search', 'bible-here'); ?></span>
				<span class="toggle-icon">▼</span>
			</button>
		</div>

		<div class="filters-content" style="display: none;">
			<div class="filter-row">
				<div class="filter-group">
					<label for="version-filter-<?php echo esc_attr($search_id); ?>"><?php _e('Bible Version:', 'bible-here'); ?></label>
					<select class="version-filter" id="version-filter-<?php echo esc_attr($search_id); ?>">
						<option value=""><?php _e('All Versions', 'bible-here'); ?></option>
						<!-- Versions will be populated by JavaScript -->
					</select>
				</div>

				<div class="filter-group">
					<label for="book-filter-<?php echo esc_attr($search_id); ?>"><?php _e('Book:', 'bible-here'); ?></label>
					<select class="book-filter" id="book-filter-<?php echo esc_attr($search_id); ?>">
						<option value=""><?php _e('All Books', 'bible-here'); ?></option>
						<!-- Books will be populated by JavaScript -->
					</select>
				</div>
			</div>

			<div class="filter-row">
				<div class="filter-group">
					<label for="search-mode-<?php echo esc_attr($search_id); ?>"><?php _e('Search Mode:', 'bible-here'); ?></label>
					<select class="search-mode-filter" id="search-mode-<?php echo esc_attr($search_id); ?>">
						<option value="natural"><?php _e('Natural Language', 'bible-here'); ?></option>
						<option value="boolean"><?php _e('Boolean Search', 'bible-here'); ?></option>
						<option value="ngram"><?php _e('Ngram (CJK)', 'bible-here'); ?></option>
					</select>
				</div>

				<div class="filter-group">
					<label for="sort-by-<?php echo esc_attr($search_id); ?>"><?php _e('Sort By:', 'bible-here'); ?></label>
					<select class="sort-by-filter" id="sort-by-<?php echo esc_attr($search_id); ?>">
						<option value="relevance"><?php _e('Relevance', 'bible-here'); ?></option>
						<option value="reference"><?php _e('Bible Reference', 'bible-here'); ?></option>
					</select>
				</div>
			</div>

			<div class="filter-actions">
				<button type="button" class="apply-filters-btn"><?php _e('Apply Filters', 'bible-here'); ?></button>
				<button type="button" class="reset-filters-btn"><?php _e('Reset', 'bible-here'); ?></button>
			</div>
		</div>
	</div>

	<!-- Search Results -->
	<div class="bible-search-results" id="results-<?php echo esc_attr($search_id); ?>">
		<!-- Loading State -->
		<div class="search-loading" style="display: none;">
			<div class="loading-spinner"></div>
			<p><?php _e('Searching...', 'bible-here'); ?></p>
		</div>

		<!-- Empty State -->
		<div class="search-empty-state" style="display: none;">
			<div class="empty-icon">📖</div>
			<h4><?php _e('No results found', 'bible-here'); ?></h4>
			<p><?php _e('Try adjusting your search terms or filters.', 'bible-here'); ?></p>
		</div>

		<!-- Results List -->
		<div class="search-results-list" style="display: none;">
			<!-- Results will be populated by JavaScript -->
		</div>

		<!-- Pagination -->
		<div class="search-pagination" style="display: none;">
			<div class="pagination-info">
				<span class="current-page">1</span> <?php _e('of', 'bible-here'); ?> <span class="total-pages">1</span>
			</div>
			<div class="pagination-controls">
				<button type="button" class="pagination-btn prev-btn" disabled>
					<span>‹</span> <?php _e('Previous', 'bible-here'); ?>
				</button>
				<div class="pagination-numbers"></div>
				<button type="button" class="pagination-btn next-btn">
					<?php _e('Next', 'bible-here'); ?> <span>›</span>
				</button>
			</div>
		</div>
	</div>

	<!-- Search History -->
	<div class="bible-search-history" style="display: none;">
		<div class="history-header">
			<h4><?php _e('Recent Searches', 'bible-here'); ?></h4>
			<button type="button" class="clear-history-btn"><?php _e('Clear', 'bible-here'); ?></button>
		</div>
		<div class="history-list">
			<!-- History items will be populated by JavaScript -->
		</div>
	</div>
</div>

<!-- Search Result Item Template -->
<script type="text/template" id="search-result-template">
	<div class="search-result-item" data-version="{{version}}" data-book="{{book_number}}" data-chapter="{{chapter_number}}" data-verse="{{verse_number}}">
		<div class="search-result-header">
			<div class="search-result-reference">
				<span class="book-name">{{book_name}}</span>
				<span class="chapter-verse">{{chapter_number}}:{{verse_number}}</span>
				<span class="version-badge">{{version}}</span>
			</div>
			<div class="search-result-actions">
				<button type="button" class="view-context-btn" title="<?php _e('View Context', 'bible-here'); ?>">
					<span>👁</span>
				</button>
				<button type="button" class="copy-verse-btn" title="<?php _e('Copy Verse', 'bible-here'); ?>">
					<span>📋</span>
				</button>
			</div>
		</div>
		<div class="search-result-text">
			{{highlighted_verse}}
		</div>
		<div class="search-result-context" style="display: none;">
			<!-- Context verses will be loaded here -->
		</div>
	</div>
</script>

<!-- Search History Item Template -->
<script type="text/template" id="search-history-template">
	<div class="history-item" data-query="{{query}}">
		<div class="history-query">{{query}}</div>
		<div class="history-meta">
			<span class="history-date">{{date}}</span>
			<span class="history-count">{{count}} <?php _e('results', 'bible-here'); ?></span>
		</div>
	</div>
</script>