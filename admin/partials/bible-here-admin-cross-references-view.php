<?php
/**
 * Cross References View Page
 *
 * This file displays detailed information about a single cross reference.
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

// Get cross reference data
$cross_reference = null;
if ($cross_reference_id) {
    $cross_reference = $cross_reference_manager->get_cross_reference_by_id($cross_reference_id);
}

if (!$cross_reference) {
    echo '<div class="notice notice-error"><p>Cross reference not found.</p></div>';
    return;
}

// Get book names
$books = $database->get_books();
$book_names = array();
foreach ($books as $book) {
    $book_names[$book->book_number] = $book->book_name;
}

// Get reference types and sources
$types = $cross_reference_manager->get_reference_types();
$sources = $cross_reference_manager->get_reference_sources();

// Format reference text
$source_ref = $book_names[$cross_reference->book_number] . ' ' . $cross_reference->chapter_number . ':' . $cross_reference->verse_number;
$target_ref = $book_names[$cross_reference->ref_book_number] . ' ' . $cross_reference->ref_chapter_number . ':' . $cross_reference->ref_verse_number;
if ($cross_reference->ref_verse_end && $cross_reference->ref_verse_end != $cross_reference->ref_verse_number) {
    $target_ref .= '-' . $cross_reference->ref_verse_end;
}

// Get verse text for preview
$source_verse = $database->get_verse($cross_reference->book_number, $cross_reference->chapter_number, $cross_reference->verse_number);
$target_verses = array();
if ($cross_reference->ref_verse_end && $cross_reference->ref_verse_end != $cross_reference->ref_verse_number) {
    for ($v = $cross_reference->ref_verse_number; $v <= $cross_reference->ref_verse_end; $v++) {
        $verse = $database->get_verse($cross_reference->ref_book_number, $cross_reference->ref_chapter_number, $v);
        if ($verse) {
            $target_verses[] = $verse;
        }
    }
} else {
    $verse = $database->get_verse($cross_reference->ref_book_number, $cross_reference->ref_chapter_number, $cross_reference->ref_verse_number);
    if ($verse) {
        $target_verses[] = $verse;
    }
}

?>

<div class="cross-reference-view-container">
    <div class="cross-reference-header">
        <h2>Cross Reference Details</h2>
        <div class="header-actions">
            <a href="<?php echo admin_url('admin.php?page=bible-here-cross-references&action=edit&id=' . $cross_reference->id); ?>" class="button button-primary">Edit</a>
            <a href="<?php echo admin_url('admin.php?page=bible-here-cross-references'); ?>" class="button button-secondary">Back to List</a>
            <a href="#" class="button button-link-delete delete-cross-reference" data-id="<?php echo $cross_reference->id; ?>">Delete</a>
        </div>
    </div>
    
    <div class="cross-reference-details">
        <div class="detail-section">
            <h3>Reference Information</h3>
            <table class="detail-table">
                <tr>
                    <th>ID:</th>
                    <td><?php echo esc_html($cross_reference->id); ?></td>
                </tr>
                <tr>
                    <th>Source Reference:</th>
                    <td class="reference-text"><?php echo esc_html($source_ref); ?></td>
                </tr>
                <tr>
                    <th>Target Reference:</th>
                    <td class="reference-text"><?php echo esc_html($target_ref); ?></td>
                </tr>
                <tr>
                    <th>Reference Type:</th>
                    <td>
                        <span class="reference-type type-<?php echo esc_attr($cross_reference->reference_type); ?>">
                            <?php echo esc_html($types[$cross_reference->reference_type] ?? $cross_reference->reference_type); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Strength:</th>
                    <td>
                        <div class="strength-display">
                            <span class="strength-indicator strength-<?php echo esc_attr($cross_reference->strength); ?>">
                                <?php echo esc_html($cross_reference->strength); ?>
                            </span>
                            <span class="strength-stars">
                                <?php echo str_repeat('★', $cross_reference->strength) . str_repeat('☆', 5 - $cross_reference->strength); ?>
                            </span>
                            <span class="strength-label">
                                (<?php echo $cross_reference->strength; ?>/5)
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>Source:</th>
                    <td><?php echo $cross_reference->source ? esc_html($cross_reference->source) : '<em>Not specified</em>'; ?></td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td>
                        <span class="status-badge <?php echo $cross_reference->is_active ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $cross_reference->is_active ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Created:</th>
                    <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($cross_reference->created_at))); ?></td>
                </tr>
                <tr>
                    <th>Updated:</th>
                    <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($cross_reference->updated_at))); ?></td>
                </tr>
            </table>
        </div>
        
        <?php if ($cross_reference->notes): ?>
        <div class="detail-section">
            <h3>Notes</h3>
            <div class="notes-content">
                <?php echo wp_kses_post(nl2br($cross_reference->notes)); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="detail-section">
            <h3>Verse Preview</h3>
            <div class="verse-preview">
                <div class="source-verse">
                    <h4>Source: <?php echo esc_html($source_ref); ?></h4>
                    <?php if ($source_verse): ?>
                        <div class="verse-text">
                            <sup><?php echo esc_html($source_verse->verse); ?></sup>
                            <?php echo esc_html($source_verse->text); ?>
                        </div>
                    <?php else: ?>
                        <div class="verse-not-found">Verse text not available</div>
                    <?php endif; ?>
                </div>
                
                <div class="target-verses">
                    <h4>Target: <?php echo esc_html($target_ref); ?></h4>
                    <?php if (!empty($target_verses)): ?>
                        <div class="verse-text">
                            <?php foreach ($target_verses as $verse): ?>
                                <span class="verse-item">
                                    <sup><?php echo esc_html($verse->verse); ?></sup>
                                    <?php echo esc_html($verse->text); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="verse-not-found">Verse text not available</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Frontend Display Preview -->
        <div class="detail-section">
            <h3>Frontend Display Preview</h3>
            <div class="frontend-preview">
                <div class="bible-here-cross-references">
                    <div class="cross-reference-item" data-type="<?php echo esc_attr($cross_reference->reference_type); ?>" data-strength="<?php echo esc_attr($cross_reference->strength); ?>">
                        <div class="cross-reference-header">
                            <span class="cross-reference-text"><?php echo esc_html($target_ref); ?></span>
                            <span class="cross-reference-type"><?php echo esc_html($types[$cross_reference->reference_type] ?? $cross_reference->reference_type); ?></span>
                            <span class="cross-reference-strength strength-<?php echo esc_attr($cross_reference->strength); ?>">
                                <?php echo str_repeat('★', $cross_reference->strength); ?>
                            </span>
                        </div>
                        <?php if ($cross_reference->notes): ?>
                        <div class="cross-reference-notes">
                            <?php echo esc_html($cross_reference->notes); ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($cross_reference->source): ?>
                        <div class="cross-reference-source">
                            Source: <?php echo esc_html($cross_reference->source); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cross-reference-view-container {
    max-width: 900px;
    margin-top: 20px;
}

.cross-reference-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e0e0e0;
}

.cross-reference-header h2 {
    margin: 0;
    color: #23282d;
}

.header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.header-actions .button {
    margin: 0;
}

.cross-reference-details .detail-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.cross-reference-details .detail-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e0e0e0;
    color: #23282d;
}

.detail-table {
    width: 100%;
    border-collapse: collapse;
}

.detail-table th {
    width: 150px;
    padding: 12px 15px 12px 0;
    text-align: left;
    vertical-align: top;
    font-weight: 600;
    color: #555;
    border-bottom: 1px solid #f0f0f0;
}

.detail-table td {
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
    color: #333;
}

.detail-table .reference-text {
    font-family: 'Georgia', serif;
    font-size: 16px;
    font-weight: 500;
    color: #0073aa;
}

.reference-type {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    color: white;
}

.type-parallel { background: #0073aa; }
.type-theme { background: #46b450; }
.type-word { background: #ff9500; }
.type-concept { background: #9b59b6; }
.type-prophecy { background: #e74c3c; }
.type-fulfillment { background: #27ae60; }
.type-contrast { background: #f39c12; }
.type-illustration { background: #3498db; }

.strength-display {
    display: flex;
    align-items: center;
    gap: 10px;
}

.strength-indicator {
    display: inline-block;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    text-align: center;
    line-height: 30px;
    font-size: 14px;
    font-weight: bold;
    color: white;
}

.strength-1 { background: #dc3232; }
.strength-2 { background: #ff9500; }
.strength-3 { background: #ffb900; }
.strength-4 { background: #46b450; }
.strength-5 { background: #00a32a; }

.strength-stars {
    font-size: 16px;
    color: #ffa500;
}

.strength-label {
    font-size: 14px;
    color: #666;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-active {
    background: #46b450;
    color: white;
}

.status-inactive {
    background: #dc3232;
    color: white;
}

.notes-content {
    padding: 15px;
    background: #f8f9fa;
    border-left: 4px solid #0073aa;
    border-radius: 0 4px 4px 0;
    font-size: 14px;
    line-height: 1.6;
    color: #555;
}

.verse-preview {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.verse-preview h4 {
    margin: 0 0 10px 0;
    padding: 8px 12px;
    background: #f0f8ff;
    border-left: 4px solid #0073aa;
    font-size: 14px;
    font-weight: 600;
    color: #0073aa;
}

.verse-text {
    padding: 15px;
    background: #fafafa;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    font-family: 'Georgia', serif;
    font-size: 15px;
    line-height: 1.6;
    color: #333;
}

.verse-text sup {
    font-size: 12px;
    font-weight: bold;
    color: #0073aa;
    margin-right: 4px;
}

.verse-item {
    display: block;
    margin-bottom: 8px;
}

.verse-item:last-child {
    margin-bottom: 0;
}

.verse-not-found {
    padding: 15px;
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    color: #856404;
    font-style: italic;
    text-align: center;
}

.frontend-preview {
    padding: 20px;
    background: #f8f9fa;
    border: 2px dashed #ccc;
    border-radius: 4px;
}

.frontend-preview::before {
    content: 'Frontend Display:';
    display: block;
    margin-bottom: 15px;
    font-size: 12px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Cross Reference Styles (matching frontend) */
.bible-here-cross-references {
    margin: 10px 0;
}

.cross-reference-item {
    margin-bottom: 8px;
    padding: 10px 12px;
    background: #f9f9f9;
    border-left: 3px solid #0073aa;
    border-radius: 0 4px 4px 0;
    font-size: 14px;
    transition: all 0.2s ease;
}

.cross-reference-item:hover {
    background: #f0f8ff;
    border-left-color: #005a87;
}

.cross-reference-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.cross-reference-text {
    font-weight: 600;
    color: #0073aa;
    text-decoration: none;
}

.cross-reference-text:hover {
    text-decoration: underline;
}

.cross-reference-type {
    font-size: 11px;
    padding: 2px 6px;
    background: #e0e0e0;
    border-radius: 2px;
    color: #666;
    text-transform: uppercase;
}

.cross-reference-strength {
    font-size: 12px;
    color: #ffa500;
}

.cross-reference-notes {
    margin: 4px 0;
    font-size: 13px;
    color: #666;
    font-style: italic;
}

.cross-reference-source {
    margin-top: 4px;
    font-size: 12px;
    color: #888;
}

/* Responsive Design */
@media (max-width: 768px) {
    .cross-reference-view-container {
        max-width: 100%;
    }
    
    .cross-reference-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .header-actions {
        width: 100%;
        justify-content: flex-start;
        flex-wrap: wrap;
    }
    
    .cross-reference-details .detail-section {
        padding: 15px;
    }
    
    .detail-table th,
    .detail-table td {
        display: block;
        width: 100%;
        padding: 8px 0;
    }
    
    .detail-table th {
        padding-bottom: 4px;
        font-weight: 600;
        color: #333;
    }
    
    .detail-table td {
        padding-top: 0;
        padding-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .verse-preview {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .strength-display {
        flex-wrap: wrap;
    }
}

/* Print Styles */
@media print {
    .cross-reference-header .header-actions,
    .frontend-preview {
        display: none;
    }
    
    .cross-reference-view-container {
        max-width: 100%;
    }
    
    .cross-reference-details .detail-section {
        border: 1px solid #ccc;
        page-break-inside: avoid;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle delete action
    $('.delete-cross-reference').on('click', function(e) {
        e.preventDefault();
        
        var crossReferenceId = $(this).data('id');
        var confirmMessage = 'Are you sure you want to delete this cross reference? This action cannot be undone.';
        
        if (confirm(confirmMessage)) {
            // Create a form and submit it
            var form = $('<form>', {
                'method': 'POST',
                'action': '<?php echo admin_url("admin.php?page=bible-here-cross-references"); ?>'
            });
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'action',
                'value': 'delete'
            }));
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'id',
                'value': crossReferenceId
            }));
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'bible_here_cross_reference_nonce',
                'value': '<?php echo wp_create_nonce("bible_here_cross_reference_action"); ?>'
            }));
            
            $('body').append(form);
            form.submit();
        }
    });
    
    // Add tooltips for reference types
    $('.reference-type').each(function() {
        var type = $(this).text().toLowerCase();
        var tooltip = '';
        
        switch(type) {
            case 'parallel':
                tooltip = 'Similar content or parallel passage';
                break;
            case 'theme':
                tooltip = 'Related by theme or topic';
                break;
            case 'word':
                tooltip = 'Related by key word or phrase';
                break;
            case 'concept':
                tooltip = 'Related by theological concept';
                break;
            case 'prophecy':
                tooltip = 'Prophetic reference';
                break;
            case 'fulfillment':
                tooltip = 'Fulfillment of prophecy';
                break;
            case 'contrast':
                tooltip = 'Contrasting passage';
                break;
            case 'illustration':
                tooltip = 'Illustrative example';
                break;
        }
        
        if (tooltip) {
            $(this).attr('title', tooltip);
        }
    });
});
</script>