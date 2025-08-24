<?php

/**
 * Provide a admin area view for managing commentaries.
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/jacklinquan/bible-here
 * @since      1.0.0
 *
 * @package    Bible_Here
 * @subpackage Bible_Here/admin/partials
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current action
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$commentary_id = isset($_GET['commentary_id']) ? intval($_GET['commentary_id']) : 0;

// Initialize commentary manager
$commentary_manager = new Bible_Here_Commentary_Manager();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bible_here_commentary_nonce']) && wp_verify_nonce($_POST['bible_here_commentary_nonce'], 'bible_here_commentary_action')) {
        
        if (isset($_POST['add_commentary'])) {
            $commentary_data = array(
                'book_number' => intval($_POST['book_number']),
                'chapter_number' => intval($_POST['chapter_number']),
                'verse_number' => intval($_POST['verse_number']),
                'author_id' => intval($_POST['author_id']),
                'source_id' => intval($_POST['source_id']),
                'commentary' => wp_kses_post($_POST['commentary']),
                'commentary_type' => sanitize_text_field($_POST['commentary_type']),
                'language' => sanitize_text_field($_POST['language']),
                'rank' => intval($_POST['rank'])
            );
            
            $result = $commentary_manager->add_commentary($commentary_data);
            
            if ($result) {
                echo '<div class="notice notice-success"><p>' . __('Commentary added successfully.', 'bible-here') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Failed to add commentary.', 'bible-here') . '</p></div>';
            }
        }
        
        if (isset($_POST['update_commentary'])) {
            $commentary_data = array(
                'book_number' => intval($_POST['book_number']),
                'chapter_number' => intval($_POST['chapter_number']),
                'verse_number' => intval($_POST['verse_number']),
                'author_id' => intval($_POST['author_id']),
                'source_id' => intval($_POST['source_id']),
                'commentary' => wp_kses_post($_POST['commentary']),
                'commentary_type' => sanitize_text_field($_POST['commentary_type']),
                'language' => sanitize_text_field($_POST['language']),
                'rank' => intval($_POST['rank'])
            );
            
            $result = $commentary_manager->update_commentary($commentary_id, $commentary_data);
            
            if ($result) {
                echo '<div class="notice notice-success"><p>' . __('Commentary updated successfully.', 'bible-here') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Failed to update commentary.', 'bible-here') . '</p></div>';
            }
        }
        
        if (isset($_POST['delete_commentary'])) {
            $result = $commentary_manager->delete_commentary($commentary_id);
            
            if ($result) {
                echo '<div class="notice notice-success"><p>' . __('Commentary deleted successfully.', 'bible-here') . '</p></div>';
                $action = 'list'; // Redirect to list view
            } else {
                echo '<div class="notice notice-error"><p>' . __('Failed to delete commentary.', 'bible-here') . '</p></div>';
            }
        }
    }
}

// Get authors and sources for dropdowns
$authors = $commentary_manager->get_commentary_authors();
$sources = $commentary_manager->get_commentary_sources();

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Bible Commentaries', 'bible-here'); ?></h1>
    
    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=bible-here-commentaries&action=add'); ?>" class="page-title-action"><?php _e('Add New Commentary', 'bible-here'); ?></a>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <?php if ($action === 'list'): ?>
        <?php include 'commentary-list.php'; ?>
    <?php elseif ($action === 'add'): ?>
        <?php include 'commentary-form.php'; ?>
    <?php elseif ($action === 'edit'): ?>
        <?php include 'commentary-form.php'; ?>
    <?php elseif ($action === 'view'): ?>
        <?php include 'commentary-view.php'; ?>
    <?php endif; ?>
</div>

<?php
// Commentary List View
if ($action === 'list'):
?>
<div id="commentary-list">
    <form method="get">
        <input type="hidden" name="page" value="bible-here-commentaries">
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="filter_book">
                    <option value=""><?php _e('All Books', 'bible-here'); ?></option>
                    <?php
                    // Get books from database
                    $database = new Bible_Here_Database();
                    $books = $database->get_books();
                    $current_book = isset($_GET['filter_book']) ? intval($_GET['filter_book']) : '';
                    
                    foreach ($books as $book) {
                        $selected = ($current_book == $book->book_number) ? 'selected' : '';
                        echo '<option value="' . $book->book_number . '" ' . $selected . '>' . esc_html($book->long_name) . '</option>';
                    }
                    ?>
                </select>
                
                <select name="filter_author">
                    <option value=""><?php _e('All Authors', 'bible-here'); ?></option>
                    <?php
                    $current_author = isset($_GET['filter_author']) ? intval($_GET['filter_author']) : '';
                    
                    foreach ($authors as $author) {
                        $selected = ($current_author == $author->id) ? 'selected' : '';
                        echo '<option value="' . $author->id . '" ' . $selected . '>' . esc_html($author->name) . '</option>';
                    }
                    ?>
                </select>
                
                <select name="filter_source">
                    <option value=""><?php _e('All Sources', 'bible-here'); ?></option>
                    <?php
                    $current_source = isset($_GET['filter_source']) ? intval($_GET['filter_source']) : '';
                    
                    foreach ($sources as $source) {
                        $selected = ($current_source == $source->id) ? 'selected' : '';
                        echo '<option value="' . $source->id . '" ' . $selected . '>' . esc_html($source->name) . '</option>';
                    }
                    ?>
                </select>
                
                <input type="submit" class="button" value="<?php _e('Filter', 'bible-here'); ?>">
            </div>
            
            <div class="alignright">
                <input type="search" name="s" value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>" placeholder="<?php _e('Search commentaries...', 'bible-here'); ?>">
                <input type="submit" class="button" value="<?php _e('Search', 'bible-here'); ?>">
            </div>
        </div>
    </form>
    
    <?php
    // Build query options
    $query_options = array(
        'limit' => 20,
        'offset' => isset($_GET['paged']) ? (intval($_GET['paged']) - 1) * 20 : 0
    );
    
    if (!empty($_GET['filter_book'])) {
        $query_options['book_number'] = intval($_GET['filter_book']);
    }
    
    if (!empty($_GET['filter_author'])) {
        $query_options['author_id'] = intval($_GET['filter_author']);
    }
    
    if (!empty($_GET['filter_source'])) {
        $query_options['source_id'] = intval($_GET['filter_source']);
    }
    
    if (!empty($_GET['s'])) {
        $query_options['search'] = sanitize_text_field($_GET['s']);
    }
    
    // Get commentaries
    $commentaries = $commentary_manager->search_commentaries('', $query_options);
    ?>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-cb check-column">
                    <input type="checkbox">
                </th>
                <th scope="col" class="manage-column column-reference"><?php _e('Reference', 'bible-here'); ?></th>
                <th scope="col" class="manage-column column-commentary"><?php _e('Commentary', 'bible-here'); ?></th>
                <th scope="col" class="manage-column column-author"><?php _e('Author', 'bible-here'); ?></th>
                <th scope="col" class="manage-column column-source"><?php _e('Source', 'bible-here'); ?></th>
                <th scope="col" class="manage-column column-type"><?php _e('Type', 'bible-here'); ?></th>
                <th scope="col" class="manage-column column-language"><?php _e('Language', 'bible-here'); ?></th>
                <th scope="col" class="manage-column column-date"><?php _e('Date', 'bible-here'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($commentaries)): ?>
                <?php foreach ($commentaries as $commentary): ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="commentary[]" value="<?php echo $commentary->id; ?>">
                        </th>
                        <td class="column-reference">
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=bible-here-commentaries&action=edit&commentary_id=' . $commentary->id); ?>">
                                    <?php echo esc_html($commentary->book_name . ' ' . $commentary->chapter_number . ':' . $commentary->verse_number); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo admin_url('admin.php?page=bible-here-commentaries&action=edit&commentary_id=' . $commentary->id); ?>"><?php _e('Edit', 'bible-here'); ?></a> |
                                </span>
                                <span class="view">
                                    <a href="<?php echo admin_url('admin.php?page=bible-here-commentaries&action=view&commentary_id=' . $commentary->id); ?>"><?php _e('View', 'bible-here'); ?></a> |
                                </span>
                                <span class="delete">
                                    <a href="<?php echo admin_url('admin.php?page=bible-here-commentaries&action=delete&commentary_id=' . $commentary->id); ?>" 
                                       onclick="return confirm('<?php _e('Are you sure you want to delete this commentary?', 'bible-here'); ?>')"><?php _e('Delete', 'bible-here'); ?></a>
                                </span>
                            </div>
                        </td>
                        <td class="column-commentary">
                            <?php echo wp_trim_words(wp_strip_all_tags($commentary->commentary), 15, '...'); ?>
                        </td>
                        <td class="column-author">
                            <?php echo esc_html($commentary->author_name ?? __('Unknown', 'bible-here')); ?>
                        </td>
                        <td class="column-source">
                            <?php echo esc_html($commentary->source_name ?? __('Unknown', 'bible-here')); ?>
                        </td>
                        <td class="column-type">
                            <?php echo esc_html(ucfirst($commentary->commentary_type)); ?>
                        </td>
                        <td class="column-language">
                            <?php echo esc_html(strtoupper($commentary->language)); ?>
                        </td>
                        <td class="column-date">
                            <?php echo esc_html(mysql2date(get_option('date_format'), $commentary->created_at)); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8"><?php _e('No commentaries found.', 'bible-here'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
elseif ($action === 'add' || $action === 'edit'):
    // Get commentary data for editing
    $commentary = null;
    if ($action === 'edit' && $commentary_id) {
        // Get single commentary - this method needs to be implemented
        $commentaries = $commentary_manager->search_commentaries('', array('id' => $commentary_id, 'limit' => 1));
        $commentary = !empty($commentaries) ? $commentaries[0] : null;
    }
?>

<form method="post" action="">
    <?php wp_nonce_field('bible_here_commentary_action', 'bible_here_commentary_nonce'); ?>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="book_number"><?php _e('Book', 'bible-here'); ?></label>
            </th>
            <td>
                <select name="book_number" id="book_number" required>
                    <option value=""><?php _e('Select Book', 'bible-here'); ?></option>
                    <?php
                    $database = new Bible_Here_Database();
                    $books = $database->get_books();
                    $selected_book = $commentary ? $commentary->book_number : '';
                    
                    foreach ($books as $book) {
                        $selected = ($selected_book == $book->book_number) ? 'selected' : '';
                        echo '<option value="' . $book->book_number . '" ' . $selected . '>' . esc_html($book->long_name) . '</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="chapter_number"><?php _e('Chapter', 'bible-here'); ?></label>
            </th>
            <td>
                <input type="number" name="chapter_number" id="chapter_number" 
                       value="<?php echo $commentary ? esc_attr($commentary->chapter_number) : ''; ?>" 
                       min="1" required>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="verse_number"><?php _e('Verse', 'bible-here'); ?></label>
            </th>
            <td>
                <input type="number" name="verse_number" id="verse_number" 
                       value="<?php echo $commentary ? esc_attr($commentary->verse_number) : ''; ?>" 
                       min="1" required>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="author_id"><?php _e('Author', 'bible-here'); ?></label>
            </th>
            <td>
                <select name="author_id" id="author_id">
                    <option value=""><?php _e('Select Author', 'bible-here'); ?></option>
                    <?php
                    $selected_author = $commentary ? $commentary->author_id : '';
                    
                    foreach ($authors as $author) {
                        $selected = ($selected_author == $author->id) ? 'selected' : '';
                        echo '<option value="' . $author->id . '" ' . $selected . '>' . esc_html($author->name) . '</option>';
                    }
                    ?>
                </select>
                <p class="description"><?php _e('Select the author of this commentary.', 'bible-here'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="source_id"><?php _e('Source', 'bible-here'); ?></label>
            </th>
            <td>
                <select name="source_id" id="source_id">
                    <option value=""><?php _e('Select Source', 'bible-here'); ?></option>
                    <?php
                    $selected_source = $commentary ? $commentary->source_id : '';
                    
                    foreach ($sources as $source) {
                        $selected = ($selected_source == $source->id) ? 'selected' : '';
                        echo '<option value="' . $source->id . '" ' . $selected . '>' . esc_html($source->name) . '</option>';
                    }
                    ?>
                </select>
                <p class="description"><?php _e('Select the source of this commentary.', 'bible-here'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="commentary_type"><?php _e('Type', 'bible-here'); ?></label>
            </th>
            <td>
                <select name="commentary_type" id="commentary_type">
                    <?php
                    $types = array(
                        'exegetical' => __('Exegetical', 'bible-here'),
                        'devotional' => __('Devotional', 'bible-here'),
                        'expository' => __('Expository', 'bible-here'),
                        'historical' => __('Historical', 'bible-here'),
                        'theological' => __('Theological', 'bible-here')
                    );
                    
                    $selected_type = $commentary ? $commentary->commentary_type : 'exegetical';
                    
                    foreach ($types as $value => $label) {
                        $selected = ($selected_type == $value) ? 'selected' : '';
                        echo '<option value="' . $value . '" ' . $selected . '>' . esc_html($label) . '</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="language"><?php _e('Language', 'bible-here'); ?></label>
            </th>
            <td>
                <select name="language" id="language">
                    <?php
                    $languages = array(
                        'en' => __('English', 'bible-here'),
                        'zh' => __('Chinese', 'bible-here'),
                        'es' => __('Spanish', 'bible-here'),
                        'fr' => __('French', 'bible-here'),
                        'de' => __('German', 'bible-here')
                    );
                    
                    $selected_language = $commentary ? $commentary->language : 'en';
                    
                    foreach ($languages as $value => $label) {
                        $selected = ($selected_language == $value) ? 'selected' : '';
                        echo '<option value="' . $value . '" ' . $selected . '>' . esc_html($label) . '</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="rank"><?php _e('Rank', 'bible-here'); ?></label>
            </th>
            <td>
                <input type="number" name="rank" id="rank" 
                       value="<?php echo $commentary ? esc_attr($commentary->rank) : '1'; ?>" 
                       min="1" max="10">
                <p class="description"><?php _e('Priority ranking (1-10, where 1 is highest priority).', 'bible-here'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="commentary"><?php _e('Commentary Text', 'bible-here'); ?></label>
            </th>
            <td>
                <?php
                $content = $commentary ? $commentary->commentary : '';
                wp_editor($content, 'commentary', array(
                    'textarea_name' => 'commentary',
                    'textarea_rows' => 10,
                    'media_buttons' => false,
                    'teeny' => true
                ));
                ?>
            </td>
        </tr>
    </table>
    
    <p class="submit">
        <?php if ($action === 'add'): ?>
            <input type="submit" name="add_commentary" class="button-primary" value="<?php _e('Add Commentary', 'bible-here'); ?>">
        <?php else: ?>
            <input type="submit" name="update_commentary" class="button-primary" value="<?php _e('Update Commentary', 'bible-here'); ?>">
            <input type="submit" name="delete_commentary" class="button-secondary" 
                   value="<?php _e('Delete Commentary', 'bible-here'); ?>" 
                   onclick="return confirm('<?php _e('Are you sure you want to delete this commentary?', 'bible-here'); ?>')">
        <?php endif; ?>
        <a href="<?php echo admin_url('admin.php?page=bible-here-commentaries'); ?>" class="button"><?php _e('Cancel', 'bible-here'); ?></a>
    </p>
</form>

<?php
elseif ($action === 'view'):
    // Get commentary data for viewing
    $commentaries = $commentary_manager->search_commentaries('', array('id' => $commentary_id, 'limit' => 1));
    $commentary = !empty($commentaries) ? $commentaries[0] : null;
    
    if (!$commentary) {
        echo '<div class="notice notice-error"><p>' . __('Commentary not found.', 'bible-here') . '</p></div>';
        return;
    }
?>

<div class="commentary-view">
    <h2><?php echo esc_html($commentary->book_name . ' ' . $commentary->chapter_number . ':' . $commentary->verse_number); ?></h2>
    
    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('Author', 'bible-here'); ?></th>
            <td><?php echo esc_html($commentary->author_name ?? __('Unknown', 'bible-here')); ?></td>
        </tr>
        
        <tr>
            <th scope="row"><?php _e('Source', 'bible-here'); ?></th>
            <td><?php echo esc_html($commentary->source_name ?? __('Unknown', 'bible-here')); ?></td>
        </tr>
        
        <tr>
            <th scope="row"><?php _e('Type', 'bible-here'); ?></th>
            <td><?php echo esc_html(ucfirst($commentary->commentary_type)); ?></td>
        </tr>
        
        <tr>
            <th scope="row"><?php _e('Language', 'bible-here'); ?></th>
            <td><?php echo esc_html(strtoupper($commentary->language)); ?></td>
        </tr>
        
        <tr>
            <th scope="row"><?php _e('Rank', 'bible-here'); ?></th>
            <td><?php echo esc_html($commentary->rank); ?></td>
        </tr>
        
        <tr>
            <th scope="row"><?php _e('Created', 'bible-here'); ?></th>
            <td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $commentary->created_at)); ?></td>
        </tr>
        
        <?php if ($commentary->updated_at): ?>
        <tr>
            <th scope="row"><?php _e('Updated', 'bible-here'); ?></th>
            <td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $commentary->updated_at)); ?></td>
        </tr>
        <?php endif; ?>
    </table>
    
    <h3><?php _e('Commentary Text', 'bible-here'); ?></h3>
    <div class="commentary-content">
        <?php echo wp_kses_post($commentary->commentary); ?>
    </div>
    
    <p>
        <a href="<?php echo admin_url('admin.php?page=bible-here-commentaries&action=edit&commentary_id=' . $commentary->id); ?>" class="button-primary"><?php _e('Edit Commentary', 'bible-here'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=bible-here-commentaries'); ?>" class="button"><?php _e('Back to List', 'bible-here'); ?></a>
    </p>
</div>

<?php endif; ?>