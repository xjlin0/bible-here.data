<?php

/**
 * Provide a admin area view for viewing a single commentary
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

// Get commentary ID
$commentary_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

if ( ! $commentary_id ) {
    wp_die( __( 'Invalid commentary ID.', 'bible-here' ) );
}

// Get commentary data
$commentary = $commentary_manager->get_commentary_by_id( $commentary_id );

if ( ! $commentary ) {
    wp_die( __( 'Commentary not found.', 'bible-here' ) );
}

// Get book information
$book = $database->get_book_by_number( $commentary->book_number );

// Get author and source information
$author = null;
$source = null;

if ( $commentary->author_id ) {
    $authors = $commentary_manager->get_commentary_authors( array( 'id' => $commentary->author_id ) );
    $author = ! empty( $authors ) ? $authors[0] : null;
}

if ( $commentary->source_id ) {
    $sources = $commentary_manager->get_commentary_sources( array( 'id' => $commentary->source_id ) );
    $source = ! empty( $sources ) ? $sources[0] : null;
}

// Format reference
$reference = $book->book_name . ' ' . $commentary->chapter_number . ':' . $commentary->verse_number;

// Get verse text for context
$verse_text = '';
try {
    $verses = $database->get_verses( 'kjv', $commentary->book_number, $commentary->chapter_number, $commentary->verse_number, $commentary->verse_number );
    if ( ! empty( $verses ) ) {
        $verse_text = $verses[0]->verse_text;
    }
} catch ( Exception $e ) {
    // Ignore errors when getting verse text
}

?>

<div class="wrap">
    <h1><?php _e( 'View Commentary', 'bible-here' ); ?></h1>
    
    <div class="commentary-view">
        <!-- Header Actions -->
        <div class="commentary-actions" style="margin-bottom: 20px;">
            <a href="<?php echo admin_url( 'admin.php?page=bible-here-commentaries&action=edit&id=' . $commentary->id ); ?>" class="button button-primary">
                <?php _e( 'Edit Commentary', 'bible-here' ); ?>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=bible-here-commentaries' ); ?>" class="button">
                <?php _e( 'Back to List', 'bible-here' ); ?>
            </a>
            <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=bible-here-commentaries&action=delete&id=' . $commentary->id ), 'delete_commentary_' . $commentary->id ); ?>" 
               class="button button-link-delete" 
               onclick="return confirm('<?php _e( 'Are you sure you want to delete this commentary?', 'bible-here' ); ?>')">
                <?php _e( 'Delete', 'bible-here' ); ?>
            </a>
        </div>
        
        <!-- Commentary Details -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e( 'Commentary Details', 'bible-here' ); ?></h2>
            </div>
            <div class="inside">
                <table class="form-table">
                    <tbody>
                        <!-- Bible Reference -->
                        <tr>
                            <th scope="row"><?php _e( 'Bible Reference', 'bible-here' ); ?></th>
                            <td>
                                <strong><?php echo esc_html( $reference ); ?></strong>
                                <?php if ( $verse_text ) : ?>
                                    <div class="verse-context" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-left: 4px solid #0073aa; font-style: italic;">
                                        "<?php echo esc_html( $verse_text ); ?>"
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <!-- Commentary Type -->
                        <tr>
                            <th scope="row"><?php _e( 'Commentary Type', 'bible-here' ); ?></th>
                            <td>
                                <?php 
                                $types = array(
                                    'verse' => __( 'Verse Commentary', 'bible-here' ),
                                    'chapter' => __( 'Chapter Commentary', 'bible-here' ),
                                    'book' => __( 'Book Commentary', 'bible-here' ),
                                    'devotional' => __( 'Devotional', 'bible-here' )
                                );
                                echo esc_html( $types[ $commentary->commentary_type ] ?? $commentary->commentary_type );
                                ?>
                            </td>
                        </tr>
                        
                        <!-- Author -->
                        <tr>
                            <th scope="row"><?php _e( 'Author', 'bible-here' ); ?></th>
                            <td>
                                <?php if ( $author ) : ?>
                                    <strong><?php echo esc_html( $author->name ); ?></strong>
                                    <?php if ( $author->bio ) : ?>
                                        <div class="author-bio" style="margin-top: 5px; color: #666;">
                                            <?php echo wp_kses_post( wpautop( $author->bio ) ); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <em><?php _e( 'Unknown Author', 'bible-here' ); ?></em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <!-- Source -->
                        <tr>
                            <th scope="row"><?php _e( 'Source', 'bible-here' ); ?></th>
                            <td>
                                <?php if ( $source ) : ?>
                                    <strong>
                                        <?php if ( $source->url ) : ?>
                                            <a href="<?php echo esc_url( $source->url ); ?>" target="_blank">
                                                <?php echo esc_html( $source->name ); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo esc_html( $source->name ); ?>
                                        <?php endif; ?>
                                    </strong>
                                    <?php if ( $source->description ) : ?>
                                        <div class="source-description" style="margin-top: 5px; color: #666;">
                                            <?php echo wp_kses_post( wpautop( $source->description ) ); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <em><?php _e( 'Unknown Source', 'bible-here' ); ?></em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <!-- Language -->
                        <tr>
                            <th scope="row"><?php _e( 'Language', 'bible-here' ); ?></th>
                            <td>
                                <?php 
                                $languages = array(
                                    'en' => __( 'English', 'bible-here' ),
                                    'zh' => __( 'Chinese', 'bible-here' ),
                                    'es' => __( 'Spanish', 'bible-here' )
                                );
                                echo esc_html( $languages[ $commentary->language ] ?? $commentary->language );
                                ?>
                            </td>
                        </tr>
                        
                        <!-- Status -->
                        <tr>
                            <th scope="row"><?php _e( 'Status', 'bible-here' ); ?></th>
                            <td>
                                <?php if ( $commentary->is_active ) : ?>
                                    <span class="status-active" style="color: #46b450; font-weight: bold;">
                                        ✓ <?php _e( 'Active', 'bible-here' ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="status-inactive" style="color: #dc3232; font-weight: bold;">
                                        ✗ <?php _e( 'Inactive', 'bible-here' ); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <!-- Created Date -->
                        <tr>
                            <th scope="row"><?php _e( 'Created', 'bible-here' ); ?></th>
                            <td>
                                <?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $commentary->created_at ) ); ?>
                            </td>
                        </tr>
                        
                        <!-- Updated Date -->
                        <?php if ( $commentary->updated_at && $commentary->updated_at !== $commentary->created_at ) : ?>
                            <tr>
                                <th scope="row"><?php _e( 'Last Updated', 'bible-here' ); ?></th>
                                <td>
                                    <?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $commentary->updated_at ) ); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Commentary Content -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e( 'Commentary Content', 'bible-here' ); ?></h2>
            </div>
            <div class="inside">
                <div class="commentary-content" style="padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 4px; line-height: 1.6;">
                    <?php echo wp_kses_post( wpautop( $commentary->commentary ) ); ?>
                </div>
            </div>
        </div>
        
        <!-- Preview -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e( 'Frontend Preview', 'bible-here' ); ?></h2>
            </div>
            <div class="inside">
                <p class="description"><?php _e( 'This is how the commentary will appear to users on the frontend:', 'bible-here' ); ?></p>
                <div class="commentary-preview" style="padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; margin-top: 10px;">
                    <!-- Simulate frontend commentary display -->
                    <div class="bible-commentary" data-commentary-id="<?php echo $commentary->id; ?>">
                        <div class="commentary-header" style="margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid #ddd;">
                            <strong class="commentary-reference"><?php echo esc_html( $reference ); ?></strong>
                            <?php if ( $author ) : ?>
                                <span class="commentary-author" style="color: #666; margin-left: 10px;">
                                    <?php _e( 'by', 'bible-here' ); ?> <?php echo esc_html( $author->name ); ?>
                                </span>
                            <?php endif; ?>
                            <span class="commentary-type" style="background: #0073aa; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-left: 10px;">
                                <?php echo esc_html( $types[ $commentary->commentary_type ] ?? $commentary->commentary_type ); ?>
                            </span>
                        </div>
                        <div class="commentary-text">
                            <?php echo wp_kses_post( wpautop( wp_trim_words( $commentary->commentary, 50, '...' ) ) ); ?>
                        </div>
                        <?php if ( $source && $source->url ) : ?>
                            <div class="commentary-source" style="margin-top: 10px; font-size: 12px; color: #666;">
                                <?php _e( 'Source:', 'bible-here' ); ?> 
                                <a href="<?php echo esc_url( $source->url ); ?>" target="_blank">
                                    <?php echo esc_html( $source->name ); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.commentary-view .form-table th {
    width: 150px;
    font-weight: 600;
}

.commentary-view .postbox {
    margin-bottom: 20px;
}

.commentary-content {
    font-size: 14px;
}

.commentary-content p {
    margin-bottom: 1em;
}

.commentary-content h1,
.commentary-content h2,
.commentary-content h3,
.commentary-content h4,
.commentary-content h5,
.commentary-content h6 {
    margin-top: 1.5em;
    margin-bottom: 0.5em;
}

.commentary-content blockquote {
    margin: 1em 0;
    padding: 0 1em;
    border-left: 4px solid #ddd;
    color: #666;
    font-style: italic;
}

.commentary-content ul,
.commentary-content ol {
    margin: 1em 0;
    padding-left: 2em;
}

.commentary-actions {
    border-bottom: 1px solid #ddd;
    padding-bottom: 15px;
}

.verse-context {
    font-family: Georgia, serif;
}

.author-bio,
.source-description {
    font-size: 13px;
}

.commentary-preview {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}
</style>