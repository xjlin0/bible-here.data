<?php

/**
 * Provide a admin area view for the commentaries list
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/xjlin0
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/admin/partials
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get commentary manager instance
require_once plugin_dir_path( dirname( __FILE__ ) ) . '../includes/class-bible-here-commentary-manager.php';
$commentary_manager = new Bible_Here_Commentary_Manager();

// Get database instance for books and versions
require_once plugin_dir_path( dirname( __FILE__ ) ) . '../includes/class-bible-here-database.php';
$database = new Bible_Here_Database();

// Handle pagination
$page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$per_page = 20;
$offset = ( $page - 1 ) * $per_page;

// Handle search and filters
$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$book_filter = isset( $_GET['book'] ) ? intval( $_GET['book'] ) : 0;
$author_filter = isset( $_GET['author'] ) ? intval( $_GET['author'] ) : 0;
$source_filter = isset( $_GET['source'] ) ? intval( $_GET['source'] ) : 0;
$type_filter = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '';
$language_filter = isset( $_GET['language'] ) ? sanitize_text_field( $_GET['language'] ) : '';

// Build query options
$query_options = array(
    'limit' => $per_page,
    'offset' => $offset,
    'search' => $search,
    'book_number' => $book_filter,
    'author_id' => $author_filter,
    'source_id' => $source_filter,
    'commentary_type' => $type_filter,
    'language' => $language_filter,
    'is_active' => true
);

// Get commentaries and total count
$commentaries = $commentary_manager->search_commentaries( $query_options );
$total_commentaries = $commentary_manager->get_commentaries_count( $query_options );

// Get filter options
$books = $database->get_books();
$authors = $commentary_manager->get_commentary_authors();
$sources = $commentary_manager->get_commentary_sources();

// Calculate pagination
$total_pages = ceil( $total_commentaries / $per_page );

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'Commentaries', 'bible-here' ); ?></h1>
    <a href="<?php echo admin_url( 'admin.php?page=bible-here-commentaries&action=add' ); ?>" class="page-title-action">
        <?php _e( 'Add New', 'bible-here' ); ?>
    </a>
    <hr class="wp-header-end">

    <!-- Search and Filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="bible-here-commentaries" />
                
                <!-- Search -->
                <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php _e( 'Search commentaries...', 'bible-here' ); ?>" />
                
                <!-- Book Filter -->
                <select name="book">
                    <option value="0"><?php _e( 'All Books', 'bible-here' ); ?></option>
                    <?php foreach ( $books as $book ) : ?>
                        <option value="<?php echo $book->book_number; ?>" <?php selected( $book_filter, $book->book_number ); ?>>
                            <?php echo esc_html( $book->book_name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Author Filter -->
                <select name="author">
                    <option value="0"><?php _e( 'All Authors', 'bible-here' ); ?></option>
                    <?php foreach ( $authors as $author ) : ?>
                        <option value="<?php echo $author->id; ?>" <?php selected( $author_filter, $author->id ); ?>>
                            <?php echo esc_html( $author->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Source Filter -->
                <select name="source">
                    <option value="0"><?php _e( 'All Sources', 'bible-here' ); ?></option>
                    <?php foreach ( $sources as $source ) : ?>
                        <option value="<?php echo $source->id; ?>" <?php selected( $source_filter, $source->id ); ?>>
                            <?php echo esc_html( $source->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Type Filter -->
                <select name="type">
                    <option value=""><?php _e( 'All Types', 'bible-here' ); ?></option>
                    <option value="verse" <?php selected( $type_filter, 'verse' ); ?>><?php _e( 'Verse Commentary', 'bible-here' ); ?></option>
                    <option value="chapter" <?php selected( $type_filter, 'chapter' ); ?>><?php _e( 'Chapter Commentary', 'bible-here' ); ?></option>
                    <option value="book" <?php selected( $type_filter, 'book' ); ?>><?php _e( 'Book Commentary', 'bible-here' ); ?></option>
                    <option value="devotional" <?php selected( $type_filter, 'devotional' ); ?>><?php _e( 'Devotional', 'bible-here' ); ?></option>
                </select>
                
                <!-- Language Filter -->
                <select name="language">
                    <option value=""><?php _e( 'All Languages', 'bible-here' ); ?></option>
                    <option value="en" <?php selected( $language_filter, 'en' ); ?>><?php _e( 'English', 'bible-here' ); ?></option>
                    <option value="zh" <?php selected( $language_filter, 'zh' ); ?>><?php _e( 'Chinese', 'bible-here' ); ?></option>
                    <option value="es" <?php selected( $language_filter, 'es' ); ?>><?php _e( 'Spanish', 'bible-here' ); ?></option>
                </select>
                
                <input type="submit" class="button" value="<?php _e( 'Filter', 'bible-here' ); ?>" />
                
                <?php if ( $search || $book_filter || $author_filter || $source_filter || $type_filter || $language_filter ) : ?>
                    <a href="<?php echo admin_url( 'admin.php?page=bible-here-commentaries' ); ?>" class="button">
                        <?php _e( 'Clear Filters', 'bible-here' ); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Pagination -->
        <?php if ( $total_pages > 1 ) : ?>
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf( _n( '%s item', '%s items', $total_commentaries, 'bible-here' ), number_format_i18n( $total_commentaries ) ); ?>
                </span>
                <?php
                echo paginate_links( array(
                    'base' => add_query_arg( 'paged', '%#%' ),
                    'format' => '',
                    'prev_text' => __( '&laquo;' ),
                    'next_text' => __( '&raquo;' ),
                    'total' => $total_pages,
                    'current' => $page
                ));
                ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Commentaries Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-cb check-column">
                    <input type="checkbox" />
                </th>
                <th scope="col" class="manage-column column-reference"><?php _e( 'Reference', 'bible-here' ); ?></th>
                <th scope="col" class="manage-column column-commentary"><?php _e( 'Commentary', 'bible-here' ); ?></th>
                <th scope="col" class="manage-column column-author"><?php _e( 'Author', 'bible-here' ); ?></th>
                <th scope="col" class="manage-column column-source"><?php _e( 'Source', 'bible-here' ); ?></th>
                <th scope="col" class="manage-column column-type"><?php _e( 'Type', 'bible-here' ); ?></th>
                <th scope="col" class="manage-column column-language"><?php _e( 'Language', 'bible-here' ); ?></th>
                <th scope="col" class="manage-column column-date"><?php _e( 'Date', 'bible-here' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $commentaries ) ) : ?>
                <?php foreach ( $commentaries as $commentary ) : ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="commentary[]" value="<?php echo $commentary->id; ?>" />
                        </th>
                        <td class="column-reference">
                            <strong>
                                <a href="<?php echo admin_url( 'admin.php?page=bible-here-commentaries&action=edit&id=' . $commentary->id ); ?>">
                                    <?php 
                                    $book = $database->get_book_by_number( $commentary->book_number );
                                    echo esc_html( $book->book_name . ' ' . $commentary->chapter_number . ':' . $commentary->verse_number );
                                    ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo admin_url( 'admin.php?page=bible-here-commentaries&action=edit&id=' . $commentary->id ); ?>">
                                        <?php _e( 'Edit', 'bible-here' ); ?>
                                    </a> |
                                </span>
                                <span class="view">
                                    <a href="<?php echo admin_url( 'admin.php?page=bible-here-commentaries&action=view&id=' . $commentary->id ); ?>">
                                        <?php _e( 'View', 'bible-here' ); ?>
                                    </a> |
                                </span>
                                <span class="delete">
                                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=bible-here-commentaries&action=delete&id=' . $commentary->id ), 'delete_commentary_' . $commentary->id ); ?>" 
                                       onclick="return confirm('<?php _e( 'Are you sure you want to delete this commentary?', 'bible-here' ); ?>')">
                                        <?php _e( 'Delete', 'bible-here' ); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td class="column-commentary">
                            <?php echo wp_trim_words( wp_strip_all_tags( $commentary->commentary ), 20, '...' ); ?>
                        </td>
                        <td class="column-author">
                            <?php echo esc_html( $commentary->author_name ?: __( 'Unknown', 'bible-here' ) ); ?>
                        </td>
                        <td class="column-source">
                            <?php echo esc_html( $commentary->source_name ?: __( 'Unknown', 'bible-here' ) ); ?>
                        </td>
                        <td class="column-type">
                            <?php 
                            $types = array(
                                'verse' => __( 'Verse', 'bible-here' ),
                                'chapter' => __( 'Chapter', 'bible-here' ),
                                'book' => __( 'Book', 'bible-here' ),
                                'devotional' => __( 'Devotional', 'bible-here' )
                            );
                            echo esc_html( $types[ $commentary->commentary_type ] ?? $commentary->commentary_type );
                            ?>
                        </td>
                        <td class="column-language">
                            <?php 
                            $languages = array(
                                'en' => __( 'English', 'bible-here' ),
                                'zh' => __( 'Chinese', 'bible-here' ),
                                'es' => __( 'Spanish', 'bible-here' )
                            );
                            echo esc_html( $languages[ $commentary->language ] ?? $commentary->language );
                            ?>
                        </td>
                        <td class="column-date">
                            <?php echo esc_html( mysql2date( get_option( 'date_format' ), $commentary->created_at ) ); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="8" class="no-items">
                        <?php _e( 'No commentaries found.', 'bible-here' ); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Bottom Pagination -->
    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf( _n( '%s item', '%s items', $total_commentaries, 'bible-here' ), number_format_i18n( $total_commentaries ) ); ?>
                </span>
                <?php
                echo paginate_links( array(
                    'base' => add_query_arg( 'paged', '%#%' ),
                    'format' => '',
                    'prev_text' => __( '&laquo;' ),
                    'next_text' => __( '&raquo;' ),
                    'total' => $total_pages,
                    'current' => $page
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.column-reference { width: 15%; }
.column-commentary { width: 35%; }
.column-author { width: 12%; }
.column-source { width: 12%; }
.column-type { width: 10%; }
.column-language { width: 8%; }
.column-date { width: 8%; }
</style>