<?php
/**
 * Cross References Management Page
 *
 * This file is used to markup the admin-facing aspects of the plugin.
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

// Get current action
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$cross_reference_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bible_here_cross_reference_nonce']) && wp_verify_nonce($_POST['bible_here_cross_reference_nonce'], 'bible_here_cross_reference_action')) {
        $cross_reference_manager = new Bible_Here_Cross_Reference_Manager();
        
        if ($action === 'add' || $action === 'edit') {
            $cross_reference_data = array(
                'book_number' => intval($_POST['book_number']),
                'chapter_number' => intval($_POST['chapter_number']),
                'verse_number' => intval($_POST['verse_number']),
                'ref_book_number' => intval($_POST['ref_book_number']),
                'ref_chapter_number' => intval($_POST['ref_chapter_number']),
                'ref_verse_number' => intval($_POST['ref_verse_number']),
                'ref_verse_end' => !empty($_POST['ref_verse_end']) ? intval($_POST['ref_verse_end']) : null,
                'reference_type' => sanitize_text_field($_POST['reference_type']),
                'strength' => intval($_POST['strength']),
                'notes' => wp_kses_post($_POST['notes']),
                'source' => sanitize_text_field($_POST['source']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            );
            
            if ($action === 'add') {
                $result = $cross_reference_manager->add_cross_reference($cross_reference_data);
                if ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p>Cross reference added successfully!</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>Error adding cross reference.</p></div>';
                }
            } elseif ($action === 'edit' && $cross_reference_id) {
                $result = $cross_reference_manager->update_cross_reference($cross_reference_id, $cross_reference_data);
                if ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p>Cross reference updated successfully!</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>Error updating cross reference.</p></div>';
                }
            }
        }
    }
}

// Handle delete action
if ($action === 'delete' && $cross_reference_id && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_cross_reference_' . $cross_reference_id)) {
    $cross_reference_manager = new Bible_Here_Cross_Reference_Manager();
    $result = $cross_reference_manager->delete_cross_reference($cross_reference_id);
    if ($result) {
        echo '<div class="notice notice-success is-dismissible"><p>Cross reference deleted successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Error deleting cross reference.</p></div>';
    }
    $action = 'list';
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline">Cross References</h1>
    
    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=bible-here-cross-references&action=add'); ?>" class="page-title-action">Add New</a>
        <hr class="wp-header-end">
        <?php include_once 'bible-here-admin-cross-references-list.php'; ?>
    <?php elseif ($action === 'add'): ?>
        <hr class="wp-header-end">
        <h2>Add New Cross Reference</h2>
        <?php include_once 'bible-here-admin-cross-references-form.php'; ?>
    <?php elseif ($action === 'edit' && $cross_reference_id): ?>
        <hr class="wp-header-end">
        <h2>Edit Cross Reference</h2>
        <?php include_once 'bible-here-admin-cross-references-form.php'; ?>
    <?php elseif ($action === 'view' && $cross_reference_id): ?>
        <hr class="wp-header-end">
        <?php include_once 'bible-here-admin-cross-references-view.php'; ?>
    <?php else: ?>
        <hr class="wp-header-end">
        <div class="notice notice-error">
            <p>Invalid action or missing cross reference ID.</p>
        </div>
        <p><a href="<?php echo admin_url('admin.php?page=bible-here-cross-references'); ?>" class="button">Back to Cross References</a></p>
    <?php endif; ?>
</div>

<style>
.cross-references-admin {
    max-width: 1200px;
}

.cross-references-admin .form-table th {
    width: 150px;
}

.cross-references-admin .form-table td {
    padding: 15px 10px;
}

.cross-references-admin .form-table select,
.cross-references-admin .form-table input[type="text"],
.cross-references-admin .form-table input[type="number"] {
    width: 100%;
    max-width: 300px;
}

.cross-references-admin .form-table textarea {
    width: 100%;
    max-width: 500px;
    height: 100px;
}

.cross-references-admin .reference-preview {
    margin-top: 10px;
    padding: 10px;
    background: #f0f8ff;
    border: 1px solid #b3d9ff;
    border-radius: 4px;
    font-size: 14px;
}

.cross-references-admin .reference-strength {
    display: flex;
    align-items: center;
    gap: 10px;
}

.cross-references-admin .strength-indicator {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    text-align: center;
    line-height: 20px;
    font-size: 12px;
    font-weight: bold;
    color: white;
}

.cross-references-admin .strength-1 { background: #dc3232; }
.cross-references-admin .strength-2 { background: #ff9500; }
.cross-references-admin .strength-3 { background: #ffb900; }
.cross-references-admin .strength-4 { background: #46b450; }
.cross-references-admin .strength-5 { background: #00a32a; }

.cross-references-admin .reference-type-info {
    margin-top: 5px;
    font-size: 12px;
    color: #666;
    font-style: italic;
}

@media (max-width: 768px) {
    .cross-references-admin .form-table th,
    .cross-references-admin .form-table td {
        display: block;
        width: 100%;
    }
    
    .cross-references-admin .form-table th {
        padding-bottom: 5px;
    }
    
    .cross-references-admin .form-table td {
        padding-top: 5px;
        padding-bottom: 20px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Update reference preview when fields change
    function updateReferencePreview() {
        var bookNumber = $('#book_number').val();
        var chapterNumber = $('#chapter_number').val();
        var verseNumber = $('#verse_number').val();
        var refBookNumber = $('#ref_book_number').val();
        var refChapterNumber = $('#ref_chapter_number').val();
        var refVerseNumber = $('#ref_verse_number').val();
        var refVerseEnd = $('#ref_verse_end').val();
        
        if (bookNumber && chapterNumber && verseNumber && refBookNumber && refChapterNumber && refVerseNumber) {
            var sourceRef = getBookName(bookNumber) + ' ' + chapterNumber + ':' + verseNumber;
            var targetRef = getBookName(refBookNumber) + ' ' + refChapterNumber + ':' + refVerseNumber;
            if (refVerseEnd && refVerseEnd != refVerseNumber) {
                targetRef += '-' + refVerseEnd;
            }
            
            $('#reference-preview').html('<strong>Reference:</strong> ' + sourceRef + ' → ' + targetRef).show();
        } else {
            $('#reference-preview').hide();
        }
    }
    
    // Update strength indicator
    function updateStrengthIndicator() {
        var strength = $('#strength').val();
        var indicator = $('.strength-indicator');
        indicator.removeClass('strength-1 strength-2 strength-3 strength-4 strength-5');
        indicator.addClass('strength-' + strength);
        indicator.text(strength);
    }
    
    // Update reference type info
    function updateReferenceTypeInfo() {
        var type = $('#reference_type').val();
        var info = '';
        
        switch(type) {
            case 'parallel':
                info = 'Similar content or parallel passage';
                break;
            case 'theme':
                info = 'Related by theme or topic';
                break;
            case 'word':
                info = 'Related by key word or phrase';
                break;
            case 'concept':
                info = 'Related by theological concept';
                break;
            case 'prophecy':
                info = 'Prophetic reference';
                break;
            case 'fulfillment':
                info = 'Fulfillment of prophecy';
                break;
            case 'contrast':
                info = 'Contrasting passage';
                break;
            case 'illustration':
                info = 'Illustrative example';
                break;
        }
        
        $('.reference-type-info').text(info);
    }
    
    // Simple book name mapping (you might want to use a more complete list)
    function getBookName(bookNumber) {
        var books = {
            1: 'Genesis', 2: 'Exodus', 3: 'Leviticus', 4: 'Numbers', 5: 'Deuteronomy',
            6: 'Joshua', 7: 'Judges', 8: 'Ruth', 9: '1 Samuel', 10: '2 Samuel',
            11: '1 Kings', 12: '2 Kings', 13: '1 Chronicles', 14: '2 Chronicles', 15: 'Ezra',
            16: 'Nehemiah', 17: 'Esther', 18: 'Job', 19: 'Psalms', 20: 'Proverbs',
            21: 'Ecclesiastes', 22: 'Song of Songs', 23: 'Isaiah', 24: 'Jeremiah', 25: 'Lamentations',
            26: 'Ezekiel', 27: 'Daniel', 28: 'Hosea', 29: 'Joel', 30: 'Amos',
            31: 'Obadiah', 32: 'Jonah', 33: 'Micah', 34: 'Nahum', 35: 'Habakkuk',
            36: 'Zephaniah', 37: 'Haggai', 38: 'Zechariah', 39: 'Malachi',
            40: 'Matthew', 41: 'Mark', 42: 'Luke', 43: 'John', 44: 'Acts',
            45: 'Romans', 46: '1 Corinthians', 47: '2 Corinthians', 48: 'Galatians', 49: 'Ephesians',
            50: 'Philippians', 51: 'Colossians', 52: '1 Thessalonians', 53: '2 Thessalonians', 54: '1 Timothy',
            55: '2 Timothy', 56: 'Titus', 57: 'Philemon', 58: 'Hebrews', 59: 'James',
            60: '1 Peter', 61: '2 Peter', 62: '1 John', 63: '2 John', 64: '3 John',
            65: 'Jude', 66: 'Revelation'
        };
        return books[bookNumber] || 'Book ' + bookNumber;
    }
    
    // Bind events
    $('#book_number, #chapter_number, #verse_number, #ref_book_number, #ref_chapter_number, #ref_verse_number, #ref_verse_end').on('change', updateReferencePreview);
    $('#strength').on('change', updateStrengthIndicator);
    $('#reference_type').on('change', updateReferenceTypeInfo);
    
    // Initialize
    updateReferencePreview();
    updateStrengthIndicator();
    updateReferenceTypeInfo();
});
</script>