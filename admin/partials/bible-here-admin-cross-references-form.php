<?php
/**
 * Cross References Form Page
 *
 * This file handles the add/edit form for cross references.
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

// Get cross reference data if editing
$cross_reference = null;
if ($action === 'edit' && $cross_reference_id) {
    $cross_reference = $cross_reference_manager->get_cross_reference_by_id($cross_reference_id);
    if (!$cross_reference) {
        echo '<div class="notice notice-error"><p>Cross reference not found.</p></div>';
        return;
    }
}

// Get available books
$books = $database->get_books();

// Get reference types and sources
$types = $cross_reference_manager->get_reference_types();
$sources = $cross_reference_manager->get_reference_sources();

// Set default values
$form_data = array(
    'book_number' => $cross_reference ? $cross_reference->book_number : '',
    'chapter_number' => $cross_reference ? $cross_reference->chapter_number : '',
    'verse_number' => $cross_reference ? $cross_reference->verse_number : '',
    'ref_book_number' => $cross_reference ? $cross_reference->ref_book_number : '',
    'ref_chapter_number' => $cross_reference ? $cross_reference->ref_chapter_number : '',
    'ref_verse_number' => $cross_reference ? $cross_reference->ref_verse_number : '',
    'ref_verse_end' => $cross_reference ? $cross_reference->ref_verse_end : '',
    'reference_type' => $cross_reference ? $cross_reference->reference_type : 'parallel',
    'strength' => $cross_reference ? $cross_reference->strength : 3,
    'notes' => $cross_reference ? $cross_reference->notes : '',
    'source' => $cross_reference ? $cross_reference->source : '',
    'is_active' => $cross_reference ? $cross_reference->is_active : 1
);

?>

<div class="cross-references-form-container">
    <form method="post" action="" class="cross-references-form">
        <?php wp_nonce_field('bible_here_cross_reference_action', 'bible_here_cross_reference_nonce'); ?>
        
        <div class="form-section">
            <h3>Source Reference</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="book_number">Book *</label>
                    </th>
                    <td>
                        <select name="book_number" id="book_number" required>
                            <option value="">Select Book</option>
                            <?php foreach ($books as $book): ?>
                                <option value="<?php echo esc_attr($book->book_number); ?>" 
                                        <?php selected($form_data['book_number'], $book->book_number); ?>>
                                    <?php echo esc_html($book->book_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="chapter_number">Chapter *</label>
                    </th>
                    <td>
                        <input type="number" name="chapter_number" id="chapter_number" 
                               value="<?php echo esc_attr($form_data['chapter_number']); ?>" 
                               min="1" max="150" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="verse_number">Verse *</label>
                    </th>
                    <td>
                        <input type="number" name="verse_number" id="verse_number" 
                               value="<?php echo esc_attr($form_data['verse_number']); ?>" 
                               min="1" max="176" required />
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="form-section">
            <h3>Target Reference</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="ref_book_number">Book *</label>
                    </th>
                    <td>
                        <select name="ref_book_number" id="ref_book_number" required>
                            <option value="">Select Book</option>
                            <?php foreach ($books as $book): ?>
                                <option value="<?php echo esc_attr($book->book_number); ?>" 
                                        <?php selected($form_data['ref_book_number'], $book->book_number); ?>>
                                    <?php echo esc_html($book->book_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ref_chapter_number">Chapter *</label>
                    </th>
                    <td>
                        <input type="number" name="ref_chapter_number" id="ref_chapter_number" 
                               value="<?php echo esc_attr($form_data['ref_chapter_number']); ?>" 
                               min="1" max="150" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ref_verse_number">Start Verse *</label>
                    </th>
                    <td>
                        <input type="number" name="ref_verse_number" id="ref_verse_number" 
                               value="<?php echo esc_attr($form_data['ref_verse_number']); ?>" 
                               min="1" max="176" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ref_verse_end">End Verse</label>
                    </th>
                    <td>
                        <input type="number" name="ref_verse_end" id="ref_verse_end" 
                               value="<?php echo esc_attr($form_data['ref_verse_end']); ?>" 
                               min="1" max="176" />
                        <p class="description">Leave empty if referencing a single verse</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="form-section">
            <h3>Reference Details</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="reference_type">Reference Type *</label>
                    </th>
                    <td>
                        <select name="reference_type" id="reference_type" required>
                            <?php foreach ($types as $type_key => $type_label): ?>
                                <option value="<?php echo esc_attr($type_key); ?>" 
                                        <?php selected($form_data['reference_type'], $type_key); ?>>
                                    <?php echo esc_html($type_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="reference-type-info"></div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="strength">Strength *</label>
                    </th>
                    <td>
                        <div class="reference-strength">
                            <input type="range" name="strength" id="strength" 
                                   value="<?php echo esc_attr($form_data['strength']); ?>" 
                                   min="1" max="5" step="1" />
                            <span class="strength-indicator strength-<?php echo esc_attr($form_data['strength']); ?>">
                                <?php echo esc_html($form_data['strength']); ?>
                            </span>
                            <div class="strength-labels">
                                <span>Weak (1)</span>
                                <span>Strong (5)</span>
                            </div>
                        </div>
                        <p class="description">How strong is this cross reference relationship?</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="source">Source</label>
                    </th>
                    <td>
                        <input type="text" name="source" id="source" 
                               value="<?php echo esc_attr($form_data['source']); ?>" 
                               list="source-suggestions" />
                        <datalist id="source-suggestions">
                            <?php foreach ($sources as $source): ?>
                                <option value="<?php echo esc_attr($source); ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <p class="description">Source of this cross reference (e.g., Treasury of Scripture Knowledge, Matthew Henry)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="notes">Notes</label>
                    </th>
                    <td>
                        <textarea name="notes" id="notes" rows="4"><?php echo esc_textarea($form_data['notes']); ?></textarea>
                        <p class="description">Additional notes about this cross reference</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="is_active">Status</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="is_active" id="is_active" value="1" 
                                   <?php checked($form_data['is_active'], 1); ?> />
                            Active
                        </label>
                        <p class="description">Inactive cross references will not be displayed</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Reference Preview -->
        <div class="form-section">
            <h3>Preview</h3>
            <div id="reference-preview" class="reference-preview" style="display: none;"></div>
        </div>
        
        <!-- Submit Buttons -->
        <div class="form-actions">
            <?php submit_button($action === 'edit' ? 'Update Cross Reference' : 'Add Cross Reference', 'primary', 'submit', false); ?>
            <a href="<?php echo admin_url('admin.php?page=bible-here-cross-references'); ?>" class="button button-secondary">Cancel</a>
            
            <?php if ($action === 'edit' && $cross_reference): ?>
                <a href="<?php echo admin_url('admin.php?page=bible-here-cross-references&action=view&id=' . $cross_reference->id); ?>" class="button button-secondary">View</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<style>
.cross-references-form-container {
    max-width: 800px;
    margin-top: 20px;
}

.cross-references-form .form-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.cross-references-form .form-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e0e0e0;
    color: #23282d;
}

.cross-references-form .form-table th {
    width: 150px;
    padding: 15px 10px 15px 0;
    vertical-align: top;
}

.cross-references-form .form-table td {
    padding: 15px 10px;
}

.cross-references-form .form-table select,
.cross-references-form .form-table input[type="text"],
.cross-references-form .form-table input[type="number"] {
    width: 100%;
    max-width: 300px;
}

.cross-references-form .form-table textarea {
    width: 100%;
    max-width: 500px;
    resize: vertical;
}

.cross-references-form .form-table .description {
    margin-top: 5px;
    font-size: 13px;
    color: #666;
    font-style: italic;
}

.reference-preview {
    padding: 15px;
    background: #f0f8ff;
    border: 1px solid #b3d9ff;
    border-radius: 4px;
    font-size: 14px;
    line-height: 1.5;
}

.reference-strength {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.reference-strength input[type="range"] {
    width: 200px;
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
    transition: all 0.3s ease;
}

.strength-1 { background: #dc3232; }
.strength-2 { background: #ff9500; }
.strength-3 { background: #ffb900; }
.strength-4 { background: #46b450; }
.strength-5 { background: #00a32a; }

.strength-labels {
    display: flex;
    justify-content: space-between;
    width: 200px;
    font-size: 12px;
    color: #666;
}

.reference-type-info {
    margin-top: 8px;
    padding: 8px 12px;
    background: #f8f9fa;
    border-left: 3px solid #0073aa;
    font-size: 13px;
    color: #555;
    border-radius: 0 4px 4px 0;
}

.form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    gap: 10px;
    align-items: center;
}

.form-actions .button {
    margin-right: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .cross-references-form-container {
        max-width: 100%;
    }
    
    .cross-references-form .form-section {
        padding: 15px;
    }
    
    .cross-references-form .form-table th,
    .cross-references-form .form-table td {
        display: block;
        width: 100%;
        padding: 10px 0;
    }
    
    .cross-references-form .form-table th {
        padding-bottom: 5px;
        font-weight: 600;
    }
    
    .cross-references-form .form-table td {
        padding-top: 0;
        padding-bottom: 20px;
    }
    
    .cross-references-form .form-table select,
    .cross-references-form .form-table input[type="text"],
    .cross-references-form .form-table input[type="number"],
    .cross-references-form .form-table textarea {
        max-width: 100%;
    }
    
    .reference-strength {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .form-actions .button {
        width: 100%;
        text-align: center;
        margin-bottom: 10px;
    }
}

/* Form Validation Styles */
.cross-references-form input:invalid,
.cross-references-form select:invalid {
    border-color: #dc3232;
    box-shadow: 0 0 2px rgba(220, 50, 50, 0.3);
}

.cross-references-form input:valid,
.cross-references-form select:valid {
    border-color: #46b450;
}

/* Loading State */
.cross-references-form.loading {
    opacity: 0.6;
    pointer-events: none;
}

.cross-references-form.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 32px;
    height: 32px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #0073aa;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    transform: translate(-50%, -50%);
}

@keyframes spin {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
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
        var referenceType = $('#reference_type').val();
        var strength = $('#strength').val();
        var notes = $('#notes').val();
        
        if (bookNumber && chapterNumber && verseNumber && refBookNumber && refChapterNumber && refVerseNumber) {
            var sourceRef = getBookName(bookNumber) + ' ' + chapterNumber + ':' + verseNumber;
            var targetRef = getBookName(refBookNumber) + ' ' + refChapterNumber + ':' + refVerseNumber;
            if (refVerseEnd && refVerseEnd != refVerseNumber) {
                targetRef += '-' + refVerseEnd;
            }
            
            var typeLabel = $('#reference_type option:selected').text();
            var strengthStars = '★'.repeat(strength) + '☆'.repeat(5 - strength);
            
            var preview = '<div class="cross-reference-preview">';
            preview += '<div class="preview-header">';
            preview += '<strong>Cross Reference:</strong> ' + sourceRef + ' → ' + targetRef;
            preview += '</div>';
            preview += '<div class="preview-details">';
            preview += '<span class="preview-type">Type: ' + typeLabel + '</span> | ';
            preview += '<span class="preview-strength">Strength: ' + strengthStars + ' (' + strength + '/5)</span>';
            preview += '</div>';
            if (notes) {
                preview += '<div class="preview-notes"><strong>Notes:</strong> ' + notes + '</div>';
            }
            preview += '</div>';
            
            $('#reference-preview').html(preview).show();
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
                info = 'Similar content or parallel passage that covers the same topic or event.';
                break;
            case 'theme':
                info = 'Related by theme or topic, sharing common theological concepts.';
                break;
            case 'word':
                info = 'Related by key word or phrase that appears in both passages.';
                break;
            case 'concept':
                info = 'Related by theological concept or doctrine being taught.';
                break;
            case 'prophecy':
                info = 'Prophetic reference that predicts future events or conditions.';
                break;
            case 'fulfillment':
                info = 'Fulfillment of prophecy, showing how predictions came to pass.';
                break;
            case 'contrast':
                info = 'Contrasting passage that shows opposite or different perspective.';
                break;
            case 'illustration':
                info = 'Illustrative example that demonstrates or clarifies the concept.';
                break;
        }
        
        $('.reference-type-info').text(info);
    }
    
    // Validate verse range
    function validateVerseRange() {
        var startVerse = parseInt($('#ref_verse_number').val());
        var endVerse = parseInt($('#ref_verse_end').val());
        
        if (endVerse && endVerse <= startVerse) {
            $('#ref_verse_end').get(0).setCustomValidity('End verse must be greater than start verse');
        } else {
            $('#ref_verse_end').get(0).setCustomValidity('');
        }
    }
    
    // Simple book name mapping
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
    $('#book_number, #chapter_number, #verse_number, #ref_book_number, #ref_chapter_number, #ref_verse_number, #ref_verse_end, #reference_type, #notes').on('change input', updateReferencePreview);
    $('#strength').on('input', function() {
        updateStrengthIndicator();
        updateReferencePreview();
    });
    $('#reference_type').on('change', updateReferenceTypeInfo);
    $('#ref_verse_number, #ref_verse_end').on('change', validateVerseRange);
    
    // Initialize
    updateReferencePreview();
    updateStrengthIndicator();
    updateReferenceTypeInfo();
    
    // Form submission validation
    $('.cross-references-form').on('submit', function(e) {
        var isValid = true;
        
        // Check required fields
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).focus();
                return false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
        
        // Add loading state
        $(this).addClass('loading');
    });
});
</script>