(function( $ ) {
	'use strict';

	/**
	 * Bible Here Admin JavaScript
	 * Handles shortcode preview and editor integration
	 */

	// Bible Here Shortcode Preview System
	var bibleShortcodePreview = {
		config: {
			previewContainer: '.bible-shortcode-preview',
			shortcodePattern: /\[bible-here\s+([^\]]+)\]/g,
			ajaxUrl: ajaxurl || '/wp-admin/admin-ajax.php',
			nonce: bible_here_admin.nonce || ''
		},

		// Initialize the preview system
		init: function() {
			this.addEditorButton();
			this.bindPreviewEvents();
			this.initShortcodePreview();
		},

		// Add Bible Here button to editor
		addEditorButton: function() {
			var self = this;
			
			// For Classic Editor (TinyMCE)
			if (typeof tinymce !== 'undefined') {
				tinymce.PluginManager.add('bible_here_button', function(editor) {
					editor.addButton('bible_here_button', {
						text: 'Bible Here',
						icon: 'dashicon dashicons-book',
						tooltip: 'Insert Bible Reference',
						onclick: function() {
							self.openShortcodeDialog(editor);
						}
					});
				});
			}

			// For Block Editor (Gutenberg)
			if (typeof wp !== 'undefined' && wp.blocks) {
				this.registerGutenbergBlock();
			}

			// For Text Editor (QuickTags)
			if (typeof QTags !== 'undefined') {
				QTags.addButton('bible_here', 'Bible Here', function() {
					self.insertQuickTag();
				});
			}
		},

		// Open shortcode dialog
		openShortcodeDialog: function(editor) {
			var self = this;
			
			// Create dialog HTML
			var dialogHtml = '<div id="bible-shortcode-dialog" title="Insert Bible Reference">' +
				'<form id="bible-shortcode-form">' +
					'<table class="form-table">' +
						'<tr>' +
							'<th><label for="bible-ref">Reference:</label></th>' +
							'<td><input type="text" id="bible-ref" name="ref" placeholder="e.g., John 3:16" required /></td>' +
						'</tr>' +
						'<tr>' +
							'<th><label for="bible-version">Version:</label></th>' +
							'<td>' +
								'<select id="bible-version" name="version">' +
									'<option value="">Default</option>' +
									'<option value="niv">NIV</option>' +
									'<option value="esv">ESV</option>' +
									'<option value="nlt">NLT</option>' +
									'<option value="kjv">KJV</option>' +
									'<option value="cunp">CUNP</option>' +
								'</select>' +
							'</td>' +
						'</tr>' +
						'<tr>' +
							'<th><label for="bible-style">Style:</label></th>' +
							'<td>' +
								'<select id="bible-style" name="style">' +
									'<option value="">Default</option>' +
									'<option value="inline">Inline</option>' +
									'<option value="block">Block</option>' +
									'<option value="popup">Popup</option>' +
								'</select>' +
							'</td>' +
						'</tr>' +
						'<tr>' +
							'<th><label for="bible-class">CSS Class:</label></th>' +
							'<td><input type="text" id="bible-class" name="class" placeholder="custom-class" /></td>' +
						'</tr>' +
					'</table>' +
					'<div class="bible-preview-container">' +
						'<h4>Preview:</h4>' +
						'<div id="bible-shortcode-preview">Enter a reference to see preview</div>' +
					'</div>' +
				'</form>' +
			'</div>';

			// Add dialog to body if not exists
			if (!$('#bible-shortcode-dialog').length) {
				$('body').append(dialogHtml);
			}

			// Initialize jQuery UI dialog
			$('#bible-shortcode-dialog').dialog({
				modal: true,
				width: 600,
				height: 500,
				resizable: false,
				buttons: {
					'Insert': function() {
						var shortcode = self.generateShortcode();
						if (shortcode && editor) {
							editor.insertContent(shortcode);
						}
						$(this).dialog('close');
					},
					'Cancel': function() {
						$(this).dialog('close');
					}
				},
				open: function() {
					// Bind preview events
					$('#bible-shortcode-form input, #bible-shortcode-form select').on('input change', function() {
						self.updatePreview();
					});
				}
			});
		},

		// Generate shortcode from form data
		generateShortcode: function() {
			var ref = $('#bible-ref').val().trim();
			if (!ref) {
				alert('Please enter a Bible reference.');
				return '';
			}

			var shortcode = '[bible-here ref="' + ref + '"';
			
			var version = $('#bible-version').val();
			if (version) {
				shortcode += ' version="' + version + '"';
			}

			var style = $('#bible-style').val();
			if (style) {
				shortcode += ' style="' + style + '"';
			}

			var cssClass = $('#bible-class').val().trim();
			if (cssClass) {
				shortcode += ' class="' + cssClass + '"';
			}

			shortcode += ']';
			return shortcode;
		},

		// Update preview
		updatePreview: function() {
			var shortcode = this.generateShortcode();
			if (!shortcode) {
				$('#bible-shortcode-preview').html('Enter a reference to see preview');
				return;
			}

			var self = this;
			$('#bible-shortcode-preview').html('<div class="loading">Loading preview...</div>');

			// AJAX request for preview
			$.ajax({
				url: this.config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'bible_here_preview_shortcode',
					shortcode: shortcode,
					nonce: this.config.nonce
				},
				success: function(response) {
					if (response.success) {
						$('#bible-shortcode-preview').html(response.data);
					} else {
						$('#bible-shortcode-preview').html('<div class="error">Preview error: ' + (response.data || 'Unknown error') + '</div>');
					}
				},
				error: function() {
					$('#bible-shortcode-preview').html('<div class="error">Failed to load preview</div>');
				}
			});
		},

		// Insert quick tag for text editor
		insertQuickTag: function() {
			var ref = prompt('Enter Bible reference (e.g., John 3:16):');
			if (ref) {
				var shortcode = '[bible-here ref="' + ref + '"]';
				QTags.insertContent(shortcode);
			}
		},

		// Register Gutenberg block
		registerGutenbergBlock: function() {
			if (!wp.blocks || !wp.element || !wp.components) {
				return;
			}

			var registerBlockType = wp.blocks.registerBlockType;
			var createElement = wp.element.createElement;
			var TextControl = wp.components.TextControl;
			var SelectControl = wp.components.SelectControl;
			var InspectorControls = wp.blockEditor.InspectorControls;
			var PanelBody = wp.components.PanelBody;

			registerBlockType('bible-here/reference', {
				title: 'Bible Reference',
				icon: 'book',
				category: 'widgets',
				attributes: {
					ref: {
						type: 'string',
						default: ''
					},
					version: {
						type: 'string',
						default: ''
					},
					style: {
						type: 'string',
						default: ''
					}
				},
				edit: function(props) {
					var attributes = props.attributes;
					var setAttributes = props.setAttributes;

					return createElement('div', {
						className: 'bible-here-block-editor'
					}, [
						createElement(InspectorControls, {}, [
							createElement(PanelBody, {
								title: 'Bible Reference Settings'
							}, [
								createElement(TextControl, {
									label: 'Reference',
									value: attributes.ref,
									onChange: function(value) {
										setAttributes({ ref: value });
									},
									placeholder: 'e.g., John 3:16'
								}),
								createElement(SelectControl, {
									label: 'Version',
									value: attributes.version,
									onChange: function(value) {
										setAttributes({ version: value });
									},
									options: [
										{ label: 'Default', value: '' },
										{ label: 'NIV', value: 'niv' },
										{ label: 'ESV', value: 'esv' },
										{ label: 'NLT', value: 'nlt' },
										{ label: 'KJV', value: 'kjv' },
										{ label: 'CUNP', value: 'cunp' }
									]
								})
							])
						]),
						createElement('div', {
							className: 'bible-here-block-preview'
						}, [
							createElement('h4', {}, 'Bible Reference'),
							createElement('p', {}, attributes.ref || 'Enter a Bible reference')
						])
					]);
				},
				save: function(props) {
					// Return shortcode for frontend
					var attributes = props.attributes;
					var shortcode = '[bible-here ref="' + attributes.ref + '"';
					
					if (attributes.version) {
						shortcode += ' version="' + attributes.version + '"';
					}
					if (attributes.style) {
						shortcode += ' style="' + attributes.style + '"';
					}
					
					shortcode += ']';
					return shortcode;
				}
			});
		},

		// Bind preview events
		bindPreviewEvents: function() {
			// Live preview in post editor
			$(document).on('input', 'textarea[name="content"]', function() {
				// Debounce preview updates
				clearTimeout(this.previewTimer);
				this.previewTimer = setTimeout(function() {
					// Update preview if shortcodes detected
				}, 1000);
			});
		},

		// Initialize shortcode preview in existing content
		initShortcodePreview: function() {
			// Find existing shortcodes and add preview functionality
			$(document).ready(function() {
				$('.bible-shortcode-preview').each(function() {
					var $this = $(this);
					var shortcode = $this.data('shortcode');
					if (shortcode) {
						// Load preview content
					}
				});
			});
		}
	};

	// Initialize when document is ready
	$(document).ready(function() {
		bibleShortcodePreview.init();
	});

})( jQuery );
