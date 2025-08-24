<?php
/**
 * Cross References List Page
 *
 * This file displays the list of cross references with pagination, search, and filtering.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$cross_reference_manager = new Bible_Here_Cross_Reference_Manager();
$database = new Bible_Here_Database();

// Get filter parameters
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$book_filter = isset($_GET['book']) ? intval($_GET['book']) : 0;
$type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$source_filter = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Pagination
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Build WHERE clause for filtering
$where_conditions = array();
$where_params = array();

if (!empty($search)) {
    $where_conditions[] = "(cr.notes LIKE %s OR cr.source LIKE %s)";
    $where_params[] = '%' . $wpdb->esc_like($search) . '%';
    $where_params[] = '%' . $wpdb->esc_like($search) . '%';
}

if ($book_filter > 0) {
    $where_conditions[] = "cr.book_number = %d";
    $where_params[] = $book_filter;
}

if (!empty($type_filter)) {
    $where_conditions[] = "cr.reference_type = %s";
    $where_params[] = $type_filter;
}

if (!empty($source_filter)) {
    $where_conditions[] = "cr.source = %s";
    $where_params[] = $source_filter;
}

if ($status_filter === 'active') {
    $where_conditions[] = "cr.is_active = 1";
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = "cr.is_active = 0";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
global $wpdb;
$count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}bible_here_cross_references cr {$where_clause}";
if (!empty($where_params)) {
    $total_items = $wpdb->get_var($wpdb->prepare($count_query, $where_params));
} else {
    $total_items = $wpdb->get_var($count_query);
}

// Get cross references
$query = "SELECT cr.*, 
                 b1.book_name as source_book_name,
                 b2.book_name as ref_book_name
          FROM {$wpdb->prefix}bible_here_cross_references cr
          LEFT JOIN {$wpdb->prefix}bible_here_books b1 ON cr.book_number = b1.book_number
          LEFT JOIN {$wpdb->prefix}bible_here_books b2 ON cr.ref_book_number = b2.book_number
          {$where_clause}
          ORDER BY cr.book_number, cr.chapter_number, cr.verse_number, cr.strength DESC
          LIMIT %d OFFSET %d";

$query_params = array_merge($where_params, array($per_page, $offset));
if (!empty($where_params)) {
    $cross_references = $wpdb->get_results($wpdb->prepare($query, $query_params));
} else {
    $cross_references = $wpdb->get_results($wpdb->prepare(
        "SELECT cr.*, 
                b1.book_name as source_book_name,
                b2.book_name as ref_book_name
         FROM {$wpdb->prefix}bible_here_cross_references cr
         LEFT JOIN {$wpdb->prefix}bible_here_books b1 ON cr.book_number = b1.book_number
         LEFT JOIN {$wpdb->prefix}bible_here_books b2 ON cr.ref_book_number = b2.book_number
         ORDER BY cr.book_number, cr.chapter_number, cr.verse_number, cr.strength DESC
         LIMIT %d OFFSET %d",
        $per_page, $offset
    ));
}

// Get available books for filter
$books = $database->get_books();

// Get available types and sources
$types = $cross_reference_manager->get_reference_types();
$sources = $cross_reference_manager->get_reference_sources();

// Calculate pagination
$total_pages = ceil($total_items / $per_page);

?>

<div class="cross-references-list-container">
    <!-- Search and Filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="bible-here-cross-references" />
                
                <!-- Search -->
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search cross references..." />
                
                <!-- Book Filter -->
                <select name="book">
                    <option value="">All Books</option>
                    <?php foreach ($books as $book): ?>
                        <option value="<?php echo esc_attr($book->book_number); ?>" <?php selected($book_filter, $book->book_number); ?>>
                            <?php echo esc_html($book->book_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Type Filter -->
                <select name="type">
                    <option value="">All Types</option>
                    <?php foreach ($types as $type_key => $type_label): ?>
                        <option value="<?php echo esc_attr($type_key); ?>" <?php selected($type_filter, $type_key); ?>>
                            <?php echo esc_html($type_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Source Filter -->
                <select name="source">
                    <option value="">All Sources</option>
                    <?php foreach ($sources as $source): ?>
                        <option value="<?php echo esc_attr($source); ?>" <?php selected($source_filter, $source); ?>>
                            <?php echo esc_html($source); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Status Filter -->
                <select name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php selected($status_filter, 'active'); ?>>Active</option>
                    <option value="inactive" <?php selected($status_filter, 'inactive'); ?>>Inactive</option>
                </select>
                
                <input type="submit" class="button" value="Filter" />
                
                <?php if (!empty($search) || $book_filter || !empty($type_filter) || !empty($source_filter) || !empty($status_filter)): ?>
                    <a href="<?php echo admin_url('admin.php?page=bible-here-cross-references'); ?>" class="button">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Pagination Info -->
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo number_format_i18n($total_items); ?> items</span>
            <?php if ($total_pages > 1): ?>
                <?php
                $page_links = paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $current_page,
                    'type' => 'plain'
                ));
                echo $page_links;
                ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Cross References Table -->
    <table class="wp-list-table widefat fixed striped cross-references">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-reference">Source Reference</th>
                <th scope="col" class="manage-column column-target">Target Reference</th>
                <th scope="col" class="manage-column column-type">Type</th>
                <th scope="col" class="manage-column column-strength">Strength</th>
                <th scope="col" class="manage-column column-source">Source</th>
                <th scope="col" class="manage-column column-status">Status</th>
                <th scope="col" class="manage-column column-date">Created</th>
                <th scope="col" class="manage-column column-actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($cross_references)): ?>
                <tr>
                    <td colspan="8" class="no-items">
                        <?php if (!empty($search) || $book_filter || !empty($type_filter) || !empty($source_filter) || !empty($status_filter)): ?>
                            No cross references found matching your criteria.
                        <?php else: ?>
                            No cross references found. <a href="<?php echo admin_url('admin.php?page=bible-here-cross-references&action=add'); ?>">Add the first one</a>.
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($cross_references as $cross_reference): ?>
                    <tr>
                        <td class="column-reference">
                            <strong>
                                <?php echo esc_html($cross_reference->source_book_name ?: 'Book ' . $cross_reference->book_number); ?>
                                <?php echo esc_html($cross_reference->chapter_number . ':' . $cross_reference->verse_number); ?>
                            </strong>
                        </td>
                        <td class="column-target">
                            <?php echo esc_html($cross_reference->ref_book_name ?: 'Book ' . $cross_reference->ref_book_number); ?>
                            <?php echo esc_html($cross_reference->ref_chapter_number . ':' . $cross_reference->ref_verse_number); ?>
                            <?php if ($cross_reference->ref_verse_end && $cross_reference->ref_verse_end != $cross_reference->ref_verse_number): ?>
                                -<?php echo esc_html($cross_reference->ref_verse_end); ?>
                            <?php endif; ?>
                        </td>
                        <td class="column-type">
                            <span class="reference-type reference-type-<?php echo esc_attr($cross_reference->reference_type); ?>">
                                <?php echo esc_html($types[$cross_reference->reference_type] ?? ucfirst($cross_reference->reference_type)); ?>
                            </span>
                        </td>
                        <td class="column-strength">
                            <span class="strength-indicator strength-<?php echo esc_attr($cross_reference->strength); ?>">
                                <?php echo esc_html($cross_reference->strength); ?>
                            </span>
                            <span class="strength-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i <= $cross_reference->strength ? 'filled' : 'empty'; ?>">★</span>
                                <?php endfor; ?>
                            </span>
                        </td>
                        <td class="column-source">
                            <?php echo esc_html($cross_reference->source ?: 'Unknown'); ?>
                        </td>
                        <td class="column-status">
                            <?php if ($cross_reference->is_active): ?>
                                <span class="status-active">Active</span>
                            <?php else: ?>
                                <span class="status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="column-date">
                            <?php echo esc_html(date('Y-m-d', strtotime($cross_reference->created_at))); ?>
                        </td>
                        <td class="column-actions">
                            <div class="row-actions">
                                <span class="view">
                                    <a href="<?php echo admin_url('admin.php?page=bible-here-cross-references&action=view&id=' . $cross_reference->id); ?>">View</a> |
                                </span>
                                <span class="edit">
                                    <a href="<?php echo admin_url('admin.php?page=bible-here-cross-references&action=edit&id=' . $cross_reference->id); ?>">Edit</a> |
                                </span>
                                <span class="delete">
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=bible-here-cross-references&action=delete&id=' . $cross_reference->id), 'delete_cross_reference_' . $cross_reference->id); ?>" 
                                       onclick="return confirm('Are you sure you want to delete this cross reference?');"
                                       class="delete-link">Delete</a>
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Bottom Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php echo $page_links; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.cross-references-list-container {
    margin-top: 20px;
}

.cross-references-list-container .tablenav {
    margin-bottom: 10px;
}

.cross-references-list-container .tablenav .actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.cross-references-list-container .tablenav input[type="search"] {
    width: 200px;
}

.cross-references-list-container .tablenav select {
    min-width: 120px;
}

.cross-references table.cross-references {
    margin-top: 10px;
}

.cross-references .column-reference {
    width: 15%;
}

.cross-references .column-target {
    width: 15%;
}

.cross-references .column-type {
    width: 12%;
}

.cross-references .column-strength {
    width: 10%;
}

.cross-references .column-source {
    width: 12%;
}

.cross-references .column-status {
    width: 8%;
}

.cross-references .column-date {
    width: 10%;
}

.cross-references .column-actions {
    width: 18%;
}

.reference-type {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
    color: white;
}

.reference-type-parallel { background: #0073aa; }
.reference-type-theme { background: #46b450; }
.reference-type-word { background: #ff9500; }
.reference-type-concept { background: #826eb4; }
.reference-type-prophecy { background: #dc3232; }
.reference-type-fulfillment { background: #00a32a; }
.reference-type-contrast { background: #646970; }
.reference-type-illustration { background: #f56e28; }

.strength-indicator {
    display: inline-block;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    text-align: center;
    line-height: 24px;
    font-size: 12px;
    font-weight: bold;
    color: white;
    margin-right: 5px;
}

.strength-1 { background: #dc3232; }
.strength-2 { background: #ff9500; }
.strength-3 { background: #ffb900; }
.strength-4 { background: #46b450; }
.strength-5 { background: #00a32a; }

.strength-stars {
    font-size: 12px;
    color: #ddd;
}

.strength-stars .star.filled {
    color: #ff9500;
}

.status-active {
    color: #46b450;
    font-weight: 600;
}

.status-inactive {
    color: #dc3232;
    font-weight: 600;
}

.delete-link {
    color: #dc3232;
}

.delete-link:hover {
    color: #a00;
}

.no-items {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-style: italic;
}

@media (max-width: 768px) {
    .cross-references-list-container .tablenav .actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .cross-references-list-container .tablenav input[type="search"],
    .cross-references-list-container .tablenav select {
        width: 100%;
        margin-bottom: 5px;
    }
    
    .cross-references table {
        font-size: 12px;
    }
    
    .cross-references .column-reference,
    .cross-references .column-target {
        width: 20%;
    }
    
    .cross-references .column-type,
    .cross-references .column-strength,
    .cross-references .column-source,
    .cross-references .column-status,
    .cross-references .column-date {
        width: 10%;
    }
    
    .cross-references .column-actions {
        width: 20%;
    }
    
    .strength-stars {
        display: none;
    }
}
</style>