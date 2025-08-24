<?php

/**
 * Provide a admin area view for the commentary add/edit form
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

// Determine if this is an edit or add action
$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'add';
$commentary_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
$is_edit = ( $action === 'edit' && $commentary_id > 0 );

// Get commentary data for editing
$commentary = null;
if ( $is_edit ) {
    $commentary = $commentary_manager->get_commentary_by_id( $commentary_id );
    if ( ! $commentary ) {
        wp_die( __( 'Commentary not found.', 'bible-here' ) );
    }
}

// Get form data
$books = $database->get_books();
$authors = $commentary_manager->get_commentary_authors();
$sources = $commentary_manager->get_commentary_sources();

// Set default values
$form_data = array(
    'book_number' => $commentary ? $commentary->book_number : 1,
    'chapter_number' => $commentary ? $commentary->chapter_number : 1,
    'verse_number' => $commentary ? $commentary->verse_number : 1,
    'author_id' => $commentary ? $commentary->author_id : 0,
    'source_id' => $commentary ? $commentary->source_id : 0,
    'commentary_type' => $commentary ? $commentary->commentary_type : 'verse',
    'language' => $commentary ? $commentary->language : 'en',
    'commentary' => $commentary ? $commentary->commentary : '',
    'is_active' => $commentary ? $commentary->is_active : true
);

?>

<div class="wrap">
    <h1>
        <?php if ( $is_edit ) : ?>
            <?php _e( 'Edit Commentary', 'bible-here' ); ?>
        <?php else : ?>
            <?php _e( 'Add New Commentary', 'bible-here' ); ?>
        <?php endif; ?>
    </h1>
    
    <form method="post" action="<?php echo admin_url( 'admin.php?page=bible-here-commentaries' ); ?>" class="commentary-form">
        <?php wp_nonce_field( $is_edit ? 'edit_commentary' : 'add_commentary', 'commentary_nonce' ); ?>
        <input type="hidden" name="action" value="<?php echo $is_edit ? 'edit' : 'add'; ?>" />
        <?php if ( $is_edit ) : ?>
            <input type="hidden" name="commentary_id" value="<?php echo $commentary_id; ?>" />
        <?php endif; ?>
        
        <table class="form-table">
            <tbody>
                <!-- Bible Reference -->
                <tr>
                    <th scope="row">
                        <label for="book_number"><?php _e( 'Bible Reference', 'bible-here' ); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <div class="bible-reference-selector">
                            <!-- Book Selection -->
                            <select name="book_number" id="book_number" required>
                                <option value=""><?php _e( 'Select Book', 'bible-here' ); ?></option>
                                <?php foreach ( $books as $book ) : ?>
                                    <option value="<?php echo $book->book_number; ?>" <?php selected( $form_data['book_number'], $book->book_number ); ?>>
                                        <?php echo esc_html( $book->book_name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <!-- Chapter Selection -->
                            <label for="chapter_number"><?php _e( 'Chapter:', 'bible-here' ); ?></label>
                            <input type="number" name="chapter_number" id="chapter_number" value="<?php echo esc_attr( $form_data['chapter_number'] ); ?>" min="1" max="150" required />
                            
                            <!-- Verse Selection -->
                            <label for="verse_number"><?php _e( 'Verse:', 'bible-here' ); ?></label>
                            <input type="number" name="verse_number" id="verse_number" value="<?php echo esc_attr( $form_data['verse_number'] ); ?>" min="1" max="176" required />
                        </div>
                        <p class="description"><?php _e( 'Select the Bible book, chapter, and verse for this commentary.', 'bible-here' ); ?></p>
                    </td>
                </tr>
                
                <!-- Commentary Type -->
                <tr>
                    <th scope="row">
                        <label for="commentary_type"><?php _e( 'Commentary Type', 'bible-here' ); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select name="commentary_type" id="commentary_type" required>
                            <option value="verse" <?php selected( $form_data['commentary_type'], 'verse' ); ?>><?php _e( 'Verse Commentary', 'bible-here' ); ?></option>
                            <option value="chapter" <?php selected( $form_data['commentary_type'], 'chapter' ); ?>><?php _e( 'Chapter Commentary', 'bible-here' ); ?></option>
                            <option value="book" <?php selected( $form_data['commentary_type'], 'book' ); ?>><?php _e( 'Book Commentary', 'bible-here' ); ?></option>
                            <option value="devotional" <?php selected( $form_data['commentary_type'], 'devotional' ); ?>><?php _e( 'Devotional', 'bible-here' ); ?></option>
                        </select>
                        <p class="description"><?php _e( 'Select the type of commentary.', 'bible-here' ); ?></p>
                    </td>
                </tr>
                
                <!-- Author -->
                <tr>
                    <th scope="row">
                        <label for="author_id"><?php _e( 'Author', 'bible-here' ); ?></label>
                    </th>
                    <td>
                        <select name="author_id" id="author_id">
                            <option value="0"><?php _e( 'Select Author', 'bible-here' ); ?></option>
                            <?php foreach ( $authors as $author ) : ?>
                                <option value="<?php echo $author->id; ?>" <?php selected( $form_data['author_id'], $author->id ); ?>>
                                    <?php echo esc_html( $author->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e( 'Select the author of this commentary.', 'bible-here' ); ?>
                            <a href="#" id="add-new-author"><?php _e( 'Add New Author', 'bible-here' ); ?></a>
                        </p>
                        
                        <!-- New Author Form (Hidden) -->
                        <div id="new-author-form" style="display: none; margin-top: 10px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">
                            <h4><?php _e( 'Add New Author', 'bible-here' ); ?></h4>
                            <p>
                                <label for="new_author_name"><?php _e( 'Author Name:', 'bible-here' ); ?></label>
                                <input type="text" id="new_author_name" name="new_author_name" />
                            </p>
                            <p>
                                <label for="new_author_bio"><?php _e( 'Biography:', 'bible-here' ); ?></label>
                                <textarea id="new_author_bio" name="new_author_bio" rows="3"></textarea>
                            </p>
                            <p>
                                <button type="button" id="save-new-author" class="button"><?php _e( 'Save Author', 'bible-here' ); ?></button>
                                <button type="button" id="cancel-new-author" class="button"><?php _e( 'Cancel', 'bible-here' ); ?></button>
                            </p>
                        </div>
                    </td>
                </tr>
                
                <!-- Source -->
                <tr>
                    <th scope="row">
                        <label for="source_id"><?php _e( 'Source', 'bible-here' ); ?></label>
                    </th>
                    <td>
                        <select name="source_id" id="source_id">
                            <option value="0"><?php _e( 'Select Source', 'bible-here' ); ?></option>
                            <?php foreach ( $sources as $source ) : ?>
                                <option value="<?php echo $source->id; ?>" <?php selected( $form_data['source_id'], $source->id ); ?>>
                                    <?php echo esc_html( $source->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e( 'Select the source of this commentary.', 'bible-here' ); ?>
                            <a href="#" id="add-new-source"><?php _e( 'Add New Source', 'bible-here' ); ?></a>
                        </p>
                        
                        <!-- New Source Form (Hidden) -->
                        <div id="new-source-form" style="display: none; margin-top: 10px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">
                            <h4><?php _e( 'Add New Source', 'bible-here' ); ?></h4>
                            <p>
                                <label for="new_source_name"><?php _e( 'Source Name:', 'bible-here' ); ?></label>
                                <input type="text" id="new_source_name" name="new_source_name" />
                            </p>
                            <p>
                                <label for="new_source_description"><?php _e( 'Description:', 'bible-here' ); ?></label>
                                <textarea id="new_source_description" name="new_source_description" rows="3"></textarea>
                            </p>
                            <p>
                                <label for="new_source_url"><?php _e( 'URL:', 'bible-here' ); ?></label>
                                <input type="url" id="new_source_url" name="new_source_url" />
                            </p>
                            <p>
                                <button type="button" id="save-new-source" class="button"><?php _e( 'Save Source', 'bible-here' ); ?></button>
                                <button type="button" id="cancel-new-source" class="button"><?php _e( 'Cancel', 'bible-here' ); ?></button>
                            </p>
                        </div>
                    </td>
                </tr>
                
                <!-- Language -->
                <tr>
                    <th scope="row">
                        <label for="language"><?php _e( 'Language', 'bible-here' ); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select name="language" id="language" required>
                            <option value="en" <?php selected( $form_data['language'], 'en' ); ?>><?php _e( 'English', 'bible-here' ); ?></option>
                            <option value="zh" <?php selected( $form_data['language'], 'zh' ); ?>><?php _e( 'Chinese', 'bible-here' ); ?></option>
                            <option value="es" <?php selected( $form_data['language'], 'es' ); ?>><?php _e( 'Spanish', 'bible-here' ); ?></option>
                        </select>
                        <p class="description"><?php _e( 'Select the language of this commentary.', 'bible-here' ); ?></p>
                    </td>
                </tr>
                
                <!-- Commentary Content -->
                <tr>
                    <th scope="row">
                        <label for="commentary"><?php _e( 'Commentary', 'bible-here' ); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <?php
                        wp_editor( $form_data['commentary'], 'commentary', array(
                            'textarea_name' => 'commentary',
                            'textarea_rows' => 15,
                            'media_buttons' => true,
                            'teeny' => false,
                            'tinymce' => array(
                                'toolbar1' => 'formatselect,bold,italic,underline,strikethrough,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,wp_adv',
                                'toolbar2' => 'styleselect,|,pastetext,pasteword,removeformat,|,charmap,|,outdent,indent,|,undo,redo,wp_help'
                            )
                        ));
                        ?>
                        <p class="description"><?php _e( 'Enter the commentary content. You can use HTML formatting.', 'bible-here' ); ?></p>
                    </td>
                </tr>
                
                <!-- Status -->
                <tr>
                    <th scope="row">
                        <label for="is_active"><?php _e( 'Status', 'bible-here' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="is_active" id="is_active" value="1" <?php checked( $form_data['is_active'], true ); ?> />
                            <?php _e( 'Active (visible to users)', 'bible-here' ); ?>
                        </label>
                        <p class="description"><?php _e( 'Uncheck to hide this commentary from users.', 'bible-here' ); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $is_edit ? __( 'Update Commentary', 'bible-here' ) : __( 'Add Commentary', 'bible-here' ); ?>" />
            <a href="<?php echo admin_url( 'admin.php?page=bible-here-commentaries' ); ?>" class="button"><?php _e( 'Cancel', 'bible-here' ); ?></a>
        </p>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Add New Author functionality
    $('#add-new-author').click(function(e) {
        e.preventDefault();
        $('#new-author-form').slideToggle();
    });
    
    $('#cancel-new-author').click(function() {
        $('#new-author-form').slideUp();
        $('#new_author_name, #new_author_bio').val('');
    });
    
    $('#save-new-author').click(function() {
        var name = $('#new_author_name').val();
        var bio = $('#new_author_bio').val();
        
        if (!name) {
            alert('<?php _e( "Please enter author name.", "bible-here" ); ?>');
            return;
        }
        
        // AJAX call to save new author
        $.post(ajaxurl, {
            action: 'bible_here_add_author',
            name: name,
            bio: bio,
            nonce: '<?php echo wp_create_nonce( "bible_here_add_author" ); ?>'
        }, function(response) {
            if (response.success) {
                // Add new option to select
                $('#author_id').append('<option value="' + response.data.id + '" selected>' + response.data.name + '</option>');
                $('#new-author-form').slideUp();
                $('#new_author_name, #new_author_bio').val('');
            } else {
                alert('<?php _e( "Error adding author.", "bible-here" ); ?>');
            }
        });
    });
    
    // Add New Source functionality
    $('#add-new-source').click(function(e) {
        e.preventDefault();
        $('#new-source-form').slideToggle();
    });
    
    $('#cancel-new-source').click(function() {
        $('#new-source-form').slideUp();
        $('#new_source_name, #new_source_description, #new_source_url').val('');
    });
    
    $('#save-new-source').click(function() {
        var name = $('#new_source_name').val();
        var description = $('#new_source_description').val();
        var url = $('#new_source_url').val();
        
        if (!name) {
            alert('<?php _e( "Please enter source name.", "bible-here" ); ?>');
            return;
        }
        
        // AJAX call to save new source
        $.post(ajaxurl, {
            action: 'bible_here_add_source',
            name: name,
            description: description,
            url: url,
            nonce: '<?php echo wp_create_nonce( "bible_here_add_source" ); ?>'
        }, function(response) {
            if (response.success) {
                // Add new option to select
                $('#source_id').append('<option value="' + response.data.id + '" selected>' + response.data.name + '</option>');
                $('#new-source-form').slideUp();
                $('#new_source_name, #new_source_description, #new_source_url').val('');
            } else {
                alert('<?php _e( "Error adding source.", "bible-here" ); ?>');
            }
        });
    });
});
</script>

<style>
.commentary-form .form-table th {
    width: 200px;
}

.bible-reference-selector {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.bible-reference-selector select,
.bible-reference-selector input[type="number"] {
    margin: 0;
}

.bible-reference-selector input[type="number"] {
    width: 80px;
}

.required {
    color: #d63638;
}

#new-author-form,
#new-source-form {
    border-radius: 4px;
}

#new-author-form h4,
#new-source-form h4 {
    margin-top: 0;
}

#new-author-form input,
#new-author-form textarea,
#new-source-form input,
#new-source-form textarea {
    width: 100%;
    max-width: 400px;
}
</style>