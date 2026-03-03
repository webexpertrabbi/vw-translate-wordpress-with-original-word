/**
 * VW Translate Admin JavaScript
 *
 * @package VW_Translate
 * @since   1.0.0
 */

/* global jQuery, vwTranslate */

(function ($) {
	'use strict';

	var VWTranslateAdmin = {

		/**
		 * Initialize the admin scripts.
		 */
		init: function () {
			this.bindEvents();
			this.moveAdminNotices();
		},

		/**
		 * Move WordPress admin notices out of our plugin wrapper
		 * so they don't intrude on our custom header design.
		 */
		moveAdminNotices: function () {
			// Move any WP notices that got injected inside .vw-translate-wrap
			// to right before it, so they display above our page instead of inside the header.
			var $wrap = $('.vw-translate-wrap');
			if ($wrap.length) {
				$wrap.find('> .notice, > .updated, > .update-nag, > .error, > div.notice, > div.updated, > div.error').each(function () {
					$(this).insertBefore($wrap);
				});
			}

			// Also catch notices placed between .wrap heading and content by WP core.
			$('.vwt-page-header').siblings('.notice, .updated, .update-nag, .error').each(function () {
				$(this).insertBefore($wrap);
			});
		},

		/**
		 * Bind all event handlers.
		 */
		bindEvents: function () {
			// Scan strings.
			$(document).on('click', '#vw-translate-scan-btn', this.scanStrings);

			// Translate button - toggle inline editor.
			$(document).on('click', '.vw-translate-btn-translate', this.toggleTranslateEditor);

			// Save individual translation.
			$(document).on('click', '.vw-translate-save-single', this.saveSingleTranslation);

			// Save all translations for a string.
			$(document).on('click', '.vw-translate-save-all', this.saveAllTranslations);

			// Close editor.
			$(document).on('click', '.vw-translate-cancel-edit', this.closeEditor);

			// Delete string.
			$(document).on('click', '.vw-translate-btn-delete', this.deleteString);

			// Add language.
			$(document).on('click', '#vw-translate-add-lang-btn', this.addLanguage);

			// Delete language.
			$(document).on('click', '.vw-translate-delete-lang', this.deleteLanguage);

			// Set default language.
			$(document).on('click', '.vw-translate-set-default', this.setDefaultLanguage);

			// Save settings.
			$(document).on('click', '#vw-translate-save-settings', this.saveSettings);

			// Clear cache.
			$(document).on('click', '#vw-translate-clear-cache', this.clearCache);

			// Add manual string.
			$(document).on('click', '#vw-translate-add-string-btn', this.addManualString);

			// Language preset — custom dropdown.
			$(document).on('click', '.vwt-csd-trigger', VWTranslateAdmin.toggleLangPreset);
			$(document).on('click', '.vwt-csd-item', VWTranslateAdmin.selectLangPresetItem);
			$(document).on('input', '.vwt-csd-search', VWTranslateAdmin.filterLangPreset);
			$(document).on('click', function (e) {
				if (!$(e.target).closest('.vwt-custom-select').length) {
					$('.vwt-custom-select').removeClass('vwt-csd-open');
					$('.vwt-csd-trigger').attr('aria-expanded', 'false');
				}
			});

			// Size picker buttons.
			$(document).on('click', '.vwt-sz-btn', this.handleSizePicker);

			// Search strings.
			$(document).on('click', '#vw-translate-search-btn', this.searchStrings);
			$(document).on('keypress', '#vw-translate-search-input', function (e) {
				if (e.which === 13) {
					e.preventDefault();
					$('#vw-translate-search-btn').click();
				}
			});

			// Filter by source type.
			$(document).on('change', '#vw-translate-filter-source', this.filterStrings);

			// Scan option toggle — highlight selected card.
			$(document).on('change', 'input[name="scan_type"]', function () {
				$('.vwt-scan-option').removeClass('selected');
				$(this).closest('.vwt-scan-option').addClass('selected');
			});

			// Initialize: mark the default-selected scan option.
			$('.vwt-scan-option input[name="scan_type"]:checked').closest('.vwt-scan-option').addClass('selected');
		},

		/**
		 * Show a notice message.
		 *
		 * @param {string} message Notice text.
		 * @param {string} type    Notice type: success, error, info.
		 */
		showNotice: function (message, type) {
			type = type || 'success';
			var icons = {
				success: 'dashicons-yes-alt',
				error: 'dashicons-dismiss',
				info: 'dashicons-info'
			};
			var icon = icons[type] || icons.info;
			var $toast = $('<div class="vwt-toast ' + type + '">' +
				'<span class="dashicons ' + icon + '"></span>' +
				'<span class="toast-text">' + message + '</span>' +
				'<button type="button" class="toast-close">&times;</button>' +
			'</div>');
			$('body').append($toast);
			setTimeout(function () { $toast.addClass('show'); }, 10);
			$toast.on('click', '.toast-close', function () {
				$toast.removeClass('show');
				setTimeout(function () { $toast.remove(); }, 300);
			});
			setTimeout(function () {
				$toast.removeClass('show');
				setTimeout(function () { $toast.remove(); }, 300);
			}, 4000);
		},

		/**
		 * Scan strings via AJAX.
		 *
		 * @param {Event} e Click event.
		 */
		scanStrings: function (e) {
			e.preventDefault();

			var $btn = $(this);
			var scanType = $('input[name="scan_type"]:checked').val() || 'all';

			$btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + vwTranslate.strings.scanning);
			$('.vwt-scan-progress').show();
			$('.vwt-scan-results').hide();

			var $progressFill = $('.vwt-scan-progress .progress-fill');
			var $progressStatus = $('.vwt-scan-progress .progress-status');
			var $progressPct = $('.vwt-scan-progress .progress-pct');

			// Realistic incremental progress simulation.
			var currentProgress = 0;
			var scanDone = false;

			var progressSteps = [
				{ pct: 5,  text: 'Initializing scanner…',             delay: 300 },
				{ pct: 12, text: 'Clearing previous data…',            delay: 600 },
				{ pct: 20, text: 'Collecting URLs & files…',            delay: 1200 },
				{ pct: 30, text: 'Fetching page content…',              delay: 2500 },
				{ pct: 42, text: 'Extracting visible strings…',         delay: 4000 },
				{ pct: 55, text: 'Processing strings…',                 delay: 6000 },
				{ pct: 65, text: 'Filtering duplicates…',               delay: 8000 },
				{ pct: 74, text: 'Saving to database…',                 delay: 10000 },
				{ pct: 82, text: 'Almost there…',                       delay: 13000 },
				{ pct: 88, text: 'Finalizing scan…',                    delay: 16000 },
				{ pct: 93, text: 'Wrapping up…',                        delay: 20000 }
			];

			var stepTimers = [];
			$.each(progressSteps, function (i, step) {
				var timer = setTimeout(function () {
					if (!scanDone) {
						currentProgress = step.pct;
						$progressFill.css('width', step.pct + '%');
						$progressPct.text(step.pct + '%');
						$progressStatus.text(step.text);
					}
				}, step.delay);
				stepTimers.push(timer);
			});

			$progressFill.css('width', '2%');
			$progressPct.text('2%');
			$progressStatus.text('Starting scan…');

			$.ajax({
				url: vwTranslate.ajaxUrl,
				type: 'POST',
				data: {
					action: 'vw_translate_scan',
					nonce: vwTranslate.nonce,
					scan_type: scanType
				},
				success: function (response) {
					scanDone = true;
					$.each(stepTimers, function (i, t) { clearTimeout(t); });

					// Animate to 100%.
					$progressFill.css('width', '100%');
					$progressPct.text('100%');

					if (response.success) {
						$progressStatus.text(vwTranslate.strings.scanComplete);
						VWTranslateAdmin.showScanResults(response.data);
						VWTranslateAdmin.showNotice(response.data.message, 'success');
					} else {
						$progressStatus.text(vwTranslate.strings.scanError);
						VWTranslateAdmin.showNotice(response.data.message, 'error');
					}
				},
				error: function () {
					scanDone = true;
					$.each(stepTimers, function (i, t) { clearTimeout(t); });
					$progressFill.css('width', '100%');
					$progressPct.text('100%');
					$progressStatus.text(vwTranslate.strings.scanError);
					VWTranslateAdmin.showNotice(vwTranslate.strings.scanError, 'error');
				},
				complete: function () {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> Start Scan');
				}
			});
		},

		/**
		 * Display scan results.
		 *
		 * @param {Object} data Scan results data.
		 */
		showScanResults: function (data) {
			var $results = $('.vwt-scan-results');
			$results.find('.results-grid').html(
				'<div class="result-metric">' +
					'<span class="metric-num">' + data.total_found + '</span>' +
					'<span class="metric-label">Total Found</span>' +
				'</div>' +
				'<div class="result-metric">' +
					'<span class="metric-num">' + data.total_new + '</span>' +
					'<span class="metric-label">New Added</span>' +
				'</div>' +
				'<div class="result-metric">' +
					'<span class="metric-num">' + data.total_existing + '</span>' +
					'<span class="metric-label">Already Existing</span>' +
				'</div>'
			);
			$results.show();
		},

		/**
		 * Toggle the inline translation editor.
		 *
		 * @param {Event} e Click event.
		 */
		toggleTranslateEditor: function (e) {
			e.preventDefault();

			var $btn = $(this);
			var stringId = $btn.data('string-id');
			var $row = $btn.closest('tr');
			var $editorRow = $row.next('.vwt-editor-row');

			// Close other open editors.
			$('.vwt-editor-row').not($editorRow).remove();

			if ($editorRow.length) {
				$editorRow.remove();
				return;
			}

			// Load translations via AJAX.
			$.ajax({
				url: vwTranslate.ajaxUrl,
				type: 'POST',
				data: {
					action: 'vw_translate_get_translations',
					nonce: vwTranslate.nonce,
					string_id: stringId
				},
				beforeSend: function () {
					$btn.prop('disabled', true);
				},
				success: function (response) {
					if (response.success) {
						VWTranslateAdmin.renderEditor($row, stringId, response.data);
					} else {
						VWTranslateAdmin.showNotice(response.data.message, 'error');
					}
				},
				error: function () {
					VWTranslateAdmin.showNotice(vwTranslate.strings.saveError, 'error');
				},
				complete: function () {
					$btn.prop('disabled', false);
				}
			});
		},

		/**
		 * Render the inline translation editor.
		 *
		 * @param {jQuery} $row     Table row.
		 * @param {number} stringId String ID.
		 * @param {Object} data     Translation data.
		 */
		renderEditor: function ($row, stringId, data) {
			var colspan = $row.find('td').length;
			var html = '<tr class="vwt-editor-row"><td colspan="' + colspan + '">';
			html += '<div class="vwt-inline-editor" data-string-id="' + stringId + '">';
			html += '<div class="editor-original"><strong>Original:</strong> <code>' + $('<span>').text(data.string.original_string).html() + '</code></div>';

			if (data.languages && data.languages.length > 0) {
				html += '<div class="editor-langs-grid">';
				$.each(data.languages, function (i, lang) {
					var existingTranslation = data.translations[lang.language_code] || '';

					html += '<div class="editor-lang-row" data-lang="' + lang.language_code + '">';
					html += '<div class="editor-lang-label">';
					if (lang.flag) {
						html += '<span class="lang-flag">' + lang.flag + '</span>';
					}
					html += '<span>' + $('<span>').text(lang.language_name).html() + '</span>';
					html += '</div>';
					html += '<div class="editor-lang-input">';
					html += '<textarea class="vw-translate-translation-input" placeholder="' +
						$('<span>').text(lang.language_name).html() + '…" data-lang="' + lang.language_code + '">' +
						$('<span>').text(existingTranslation).html() + '</textarea>';
					html += '</div>';
					html += '<div class="editor-lang-save">';
					html += '<button type="button" class="vwt-btn vwt-btn-sm vw-translate-save-single" data-string-id="' +
						stringId + '" data-lang="' + lang.language_code + '">Save</button>';
					html += '<span class="vwt-saved-check">✓</span>';
					html += '</div>';
					html += '</div>';
				});
				html += '</div>';
			} else {
				html += '<div class="vwt-empty-state" style="padding:20px;"><p>No languages configured. Please add languages first.</p></div>';
			}

			html += '<div class="editor-footer">';
			html += '<button type="button" class="vwt-btn vw-translate-cancel-edit">Close</button>';
			html += '<button type="button" class="vwt-btn vwt-btn-primary vw-translate-save-all" data-string-id="' + stringId + '">Save All</button>';
			html += '</div>';
			html += '</div></td></tr>';

			$row.after(html);
		},

		/**
		 * Save a single translation.
		 *
		 * @param {Event} e Click event.
		 */
		saveSingleTranslation: function (e) {
			e.preventDefault();

			var $btn = $(this);
			var stringId = $btn.data('string-id');
			var langCode = $btn.data('lang');
			var $row = $btn.closest('.editor-lang-row');
			var translation = $row.find('textarea').val();
			var $indicator = $row.find('.vwt-saved-check');

			$.ajax({
				url: vwTranslate.ajaxUrl,
				type: 'POST',
				data: {
					action: 'vw_translate_save_translation',
					nonce: vwTranslate.nonce,
					string_id: stringId,
					language_code: langCode,
					translation: translation
				},
				beforeSend: function () {
					$btn.prop('disabled', true).text('...');
				},
				success: function (response) {
					if (response.success) {
						$indicator.addClass('show');
						setTimeout(function () {
							$indicator.removeClass('show');
						}, 2000);
						VWTranslateAdmin.updateTranslationBadges(stringId, langCode, translation);
						VWTranslateAdmin.showNotice(vwTranslate.strings.saved, 'success');
					} else {
						VWTranslateAdmin.showNotice(response.data.message, 'error');
					}
				},
				error: function () {
					VWTranslateAdmin.showNotice(vwTranslate.strings.saveError, 'error');
				},
				complete: function () {
					$btn.prop('disabled', false).text('Save');
				}
			});
		},

		/**
		 * Save all translations for a string.
		 *
		 * @param {Event} e Click event.
		 */
		saveAllTranslations: function (e) {
			e.preventDefault();

			var $btn = $(this);
			var stringId = $btn.data('string-id');
			var $editor = $btn.closest('.vwt-inline-editor');
			var $rows = $editor.find('.editor-lang-row');

			$btn.prop('disabled', true).text(vwTranslate.strings.saving);

			var promises = [];

			$rows.each(function () {
				var $row = $(this);
				var langCode = $row.data('lang');
				var translation = $row.find('textarea').val();

				var promise = $.ajax({
					url: vwTranslate.ajaxUrl,
					type: 'POST',
					data: {
						action: 'vw_translate_save_translation',
						nonce: vwTranslate.nonce,
						string_id: stringId,
						language_code: langCode,
						translation: translation
					}
				});

				promises.push(promise);
			});

			$.when.apply($, promises).then(
				function () {
					VWTranslateAdmin.showNotice(vwTranslate.strings.saved, 'success');
					// Update badges.
					$rows.each(function () {
						var $row = $(this);
						var langCode = $row.data('lang');
						var translation = $row.find('textarea').val();
						VWTranslateAdmin.updateTranslationBadges(stringId, langCode, translation);
						$row.find('.vwt-saved-check').addClass('show');
					});
				},
				function () {
					VWTranslateAdmin.showNotice(vwTranslate.strings.saveError, 'error');
				}
			).always(function () {
				$btn.prop('disabled', false).text('Save All');
			});
		},

		/**
		 * Update translation badges in the table row.
		 *
		 * @param {number} stringId    String ID.
		 * @param {string} langCode   Language code.
		 * @param {string} translation Translation text.
		 */
		updateTranslationBadges: function (stringId, langCode, translation) {
			var $badge = $('[data-string-row="' + stringId + '"] .vwt-lang-badge[data-lang="' + langCode + '"]');
			if ($badge.length) {
				if (translation && translation.trim() !== '') {
					$badge.addClass('translated');
				} else {
					$badge.removeClass('translated');
				}
			}
		},

		/**
		 * Close the inline editor.
		 *
		 * @param {Event} e Click event.
		 */
		closeEditor: function (e) {
			e.preventDefault();
			$(this).closest('.vwt-editor-row').remove();
		},

		/**
		 * Delete a string.
		 *
		 * @param {Event} e Click event.
		 */
		deleteString: function (e) {
			e.preventDefault();

			if (!confirm(vwTranslate.strings.confirmDelete)) {
				return;
			}

			var $btn = $(this);
			var stringId = $btn.data('string-id');

			$.ajax({
				url: vwTranslate.ajaxUrl,
				type: 'POST',
				data: {
					action: 'vw_translate_delete_string',
					nonce: vwTranslate.nonce,
					string_id: stringId
				},
				beforeSend: function () {
					$btn.prop('disabled', true);
				},
				success: function (response) {
					if (response.success) {
						// Remove the row.
						$btn.closest('tr').next('.vwt-editor-row').remove();
						$btn.closest('tr').fadeOut(400, function () {
							$(this).remove();
						});
						VWTranslateAdmin.showNotice(response.data.message, 'success');
					} else {
						VWTranslateAdmin.showNotice(response.data.message, 'error');
					}
				},
				error: function () {
					VWTranslateAdmin.showNotice(vwTranslate.strings.deleteError, 'error');
				},
				complete: function () {
					$btn.prop('disabled', false);
				}
			});
		},

		/**
		 * Add a new language.
		 *
		 * @param {Event} e Click event.
		 */
		addLanguage: function (e) {
			e.preventDefault();

			var langCode = $('#vw-translate-lang-code').val();
			var langName = $('#vw-translate-lang-name').val();
			var nativeName = $('#vw-translate-lang-native').val();
			var flag = $('#vw-translate-lang-flag').val();
			var isDefault = $('#vw-translate-lang-default').is(':checked') ? 1 : 0;

			if (!langCode || !langName) {
				VWTranslateAdmin.showNotice('Language code and name are required.', 'error');
				return;
			}

			var $btn = $(this);

			$.ajax({
				url: vwTranslate.ajaxUrl,
				type: 'POST',
				data: {
					action: 'vw_translate_add_language',
					nonce: vwTranslate.nonce,
					language_code: langCode,
					language_name: langName,
					native_name: nativeName,
					flag: flag,
					is_default: isDefault
				},
				beforeSend: function () {
					$btn.prop('disabled', true);
				},
				success: function (response) {
					if (response.success) {
						VWTranslateAdmin.showNotice(response.data.message, 'success');
						// Reload page to show updated list.
						setTimeout(function () {
							window.location.reload();
						}, 1000);
					} else {
						VWTranslateAdmin.showNotice(response.data.message, 'error');
					}
				},
				error: function () {
					VWTranslateAdmin.showNotice(vwTranslate.strings.saveError, 'error');
				},
				complete: function () {
					$btn.prop('disabled', false);
				}
			});
		},

		/**
		 * Delete a language.
		 *
		 * @param {Event} e Click event.
		 */
		deleteLanguage: function (e) {
			e.preventDefault();

			if (!confirm(vwTranslate.strings.confirmDelete)) {
				return;
			}

			var $btn = $(this);
			var langId = $btn.data('lang-id');

			$.ajax({
				url: vwTranslate.ajaxUrl,
				type: 'POST',
				data: {
					action: 'vw_translate_delete_language',
					nonce: vwTranslate.nonce,
					language_id: langId
				},
				success: function (response) {
					if (response.success) {
						VWTranslateAdmin.showNotice(response.data.message, 'success');
						$btn.closest('li').fadeOut(400, function () {
							$(this).remove();
						});
					} else {
						VWTranslateAdmin.showNotice(response.data.message, 'error');
					}
				},
				error: function () {
					VWTranslateAdmin.showNotice(vwTranslate.strings.deleteError, 'error');
				}
			});
		},

		/**
		 * Set default language.
		 *
		 * @param {Event} e Click event.
		 */
		setDefaultLanguage: function (e) {
			e.preventDefault();

			var $btn = $(this);
			var langId = $btn.data('lang-id');

			$.ajax({
				url: vwTranslate.ajaxUrl,
				type: 'POST',
				data: {
					action: 'vw_translate_set_default_language',
					nonce: vwTranslate.nonce,
					language_id: langId
				},
				success: function (response) {
					if (response.success) {
						VWTranslateAdmin.showNotice(response.data.message, 'success');
						setTimeout(function () {
							window.location.reload();
						}, 1000);
					} else {
						VWTranslateAdmin.showNotice(response.data.message, 'error');
					}
				}
			});
		},

		/**
		 * Save plugin settings.
		 *
		 * @param {Event} e Click event.
		 */
		saveSettings: function (e) {
			e.preventDefault();

			var $btn = $(this);
			var $wrap = $btn.closest('.vw-translate-wrap');

			var data = {
				action: 'vw_translate_save_settings',
				nonce: vwTranslate.nonce,
				enable_url_param: $wrap.find('#vw-translate-enable-url-param').is(':checked') ? 1 : 0,
				enable_cookie: $wrap.find('#vw-translate-enable-cookie').is(':checked') ? 1 : 0,
				cookie_duration: $wrap.find('#vw-translate-cookie-duration').val(),
				enable_switcher: $wrap.find('#vw-translate-enable-switcher').is(':checked') ? 1 : 0,
				switcher_position: $wrap.find('#vw-translate-switcher-position').val(),
				scan_depth: $wrap.find('#vw-translate-scan-depth').val(),
				exclude_admin: $wrap.find('#vw-translate-exclude-admin').is(':checked') ? 1 : 0,
				cache_translations: $wrap.find('#vw-translate-cache-translations').is(':checked') ? 1 : 0,
				cache_duration: $wrap.find('#vw-translate-cache-duration').val(),
				shortcode_style : $wrap.find('input[name="shortcode_style"]:checked').val() || 'dropdown',
				size_dropdown   : $wrap.find('input[name="size_dropdown"]').val()   || 'md',
				size_pills      : $wrap.find('input[name="size_pills"]').val()      || 'md',
				size_minimal    : $wrap.find('input[name="size_minimal"]').val()    || 'md',
				size_cards      : $wrap.find('input[name="size_cards"]').val()      || 'md',
				size_elegant    : $wrap.find('input[name="size_elegant"]').val()    || 'md',
				size_flag_code  : $wrap.find('input[name="size_flag_code"]').val()  || 'md',
				size_flag_only  : $wrap.find('input[name="size_flag_only"]').val()  || 'md'
			};

			$.ajax({
				url: vwTranslate.ajaxUrl,
				type: 'POST',
				data: data,
				beforeSend: function () {
					$btn.prop('disabled', true).text(vwTranslate.strings.saving);
				},
				success: function (response) {
					if (response.success) {
						VWTranslateAdmin.showNotice(response.data.message, 'success');
					} else {
						VWTranslateAdmin.showNotice(response.data.message, 'error');
					}
				},
				error: function () {
					VWTranslateAdmin.showNotice(vwTranslate.strings.saveError, 'error');
				},
				complete: function () {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Settings');
				}
			});
		},

		/**
		 * Clear all translation caches.
		 *
		 * @param {Event} e Click event.
		 */
		clearCache: function (e) {
			e.preventDefault();

			var $btn = $(this);

			$.ajax({
				url: vwTranslate.ajaxUrl,
				type: 'POST',
				data: {
					action: 'vw_translate_clear_cache',
					nonce: vwTranslate.nonce
				},
				beforeSend: function () {
					$btn.prop('disabled', true).text('Clearing...');
				},
				success: function (response) {
					if (response.success) {
						VWTranslateAdmin.showNotice(response.data.message, 'success');
					} else {
						VWTranslateAdmin.showNotice(response.data.message, 'error');
					}
				},
				error: function () {
					VWTranslateAdmin.showNotice('Failed to clear cache.', 'error');
				},
				complete: function () {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Clear Translation Cache');
				}
			});
		},

		/**
		 * Handle size picker (S/M/L) button click inside a style card.
		 *
		 * Stops event bubbling so the outer style-selector label is not toggled
		 * when the user only wants to change the size of a non-active style.
		 *
		 * @param {Event} e Click event.
		 */
		handleSizePicker: function (e) {
			e.stopPropagation();
			var $btn    = $(this);
			var $picker = $btn.closest('.vwt-size-picker');
			$picker.find('.vwt-sz-btn').removeClass('active');
			$btn.addClass('active');
			$picker.find('input[type="hidden"]').val($btn.data('size'));
		},

		/**
		 * Add a manual string.
		 *
		 * @param {Event} e Click event.
		 */
		addManualString: function (e) {
			e.preventDefault();

			var $input = $('#vw-translate-manual-string');
			var originalString = $input.val().trim();

			if (!originalString) {
				VWTranslateAdmin.showNotice('Please enter a string.', 'error');
				return;
			}

			var $btn = $(this);

			$.ajax({
				url: vwTranslate.ajaxUrl,
				type: 'POST',
				data: {
					action: 'vw_translate_add_manual_string',
					nonce: vwTranslate.nonce,
					original_string: originalString
				},
				beforeSend: function () {
					$btn.prop('disabled', true);
				},
				success: function (response) {
					if (response.success) {
						VWTranslateAdmin.showNotice(response.data.message, 'success');
						$input.val('');
						setTimeout(function () {
							window.location.reload();
						}, 1000);
					} else {
						VWTranslateAdmin.showNotice(response.data.message, 'error');
					}
				},
				error: function () {
					VWTranslateAdmin.showNotice(vwTranslate.strings.saveError, 'error');
				},
				complete: function () {
					$btn.prop('disabled', false);
				}
			});
		},

		/**
		 * Toggle the custom language preset dropdown open/closed.
		 */
		toggleLangPreset: function (e) {
			e.stopPropagation();
			var $trigger = $(this);
			var $wrap = $trigger.closest('.vwt-custom-select');
			var isOpen = $wrap.hasClass('vwt-csd-open');

			// Close any other open selects.
			$('.vwt-custom-select').not($wrap).removeClass('vwt-csd-open');
			$('.vwt-csd-trigger').not($trigger).attr('aria-expanded', 'false');

			$wrap.toggleClass('vwt-csd-open', !isOpen);
			$trigger.attr('aria-expanded', String(!isOpen));

			if (!isOpen) {
				// Clear search and show all items.
				var $search = $wrap.find('.vwt-csd-search');
				$search.val('');
				$wrap.find('.vwt-csd-item').show();
				$wrap.find('.vwt-csd-empty').hide();
				setTimeout(function () { $search.focus(); }, 40);
				// Scroll selected item into view.
				var $sel = $wrap.find('.vwt-csd-selected-item');
				if ($sel.length) {
					$sel[0].scrollIntoView({ block: 'nearest' });
				}
			}
		},

		/**
		 * Filter language list by search input.
		 */
		filterLangPreset: function () {
			var q = $(this).val().toLowerCase().trim();
			var $wrap = $(this).closest('.vwt-custom-select');
			var $items = $wrap.find('.vwt-csd-item');
			var visible = 0;

			$items.each(function () {
				var haystack = $(this).data('search') || '';
				var match = !q || haystack.indexOf(q) !== -1;
				$(this).toggle(match);
				if (match) { visible++; }
			});

			$wrap.find('.vwt-csd-empty').toggle(visible === 0);
		},

		/**
		 * Select a language preset item and fill the form fields.
		 */
		selectLangPresetItem: function (e) {
			e.stopPropagation();
			var $item = $(this);
			var $wrap = $item.closest('.vwt-custom-select');
			var $trigger = $wrap.find('.vwt-csd-trigger');

			// Mark selected.
			$wrap.find('.vwt-csd-item').removeClass('vwt-csd-selected-item');
			$item.addClass('vwt-csd-selected-item');

			// Build the trigger display.
			var flagHtml = $item.find('.vwt-csd-item-flag').html() || '';
			var name = $item.find('.vwt-csd-item-name').text();
			var native = $item.find('.vwt-csd-item-native').text();
			var code = $item.find('.vwt-csd-item-code').text();

			$trigger.find('.vwt-csd-selected').html(
				'<span class="vwt-csd-item-flag">' + flagHtml + '</span>' +
				'<span class="vwt-csd-selected-name">' + $('<span>').text(name).html() + '</span>' +
				'<span class="vwt-csd-selected-native">' + $('<span>').text(native).html() + '</span>' +
				'<span class="vwt-csd-selected-code">' + $('<span>').text(code).html() + '</span>'
			);

			// Fill form fields.
			$('#vw-translate-lang-code').val($item.data('value'));
			$('#vw-translate-lang-name').val($item.data('name'));
			$('#vw-translate-lang-native').val($item.data('native'));
			$('#vw-translate-lang-flag').val($item.data('flag'));

			// Close dropdown.
			$wrap.removeClass('vwt-csd-open');
			$trigger.attr('aria-expanded', 'false');
		},

		/**
		 * Search strings.
		 *
		 * @param {Event} e Click event.
		 */
		searchStrings: function (e) {
			e.preventDefault();

			var search = $('#vw-translate-search-input').val();
			var currentUrl = new URL(window.location.href);
			currentUrl.searchParams.set('s', search);
			currentUrl.searchParams.set('paged', '1');
			window.location.href = currentUrl.toString();
		},

		/**
		 * Filter strings by source type.
		 */
		filterStrings: function () {
			var source = $(this).val();
			var currentUrl = new URL(window.location.href);
			if (source) {
				currentUrl.searchParams.set('source_type', source);
			} else {
				currentUrl.searchParams.delete('source_type');
			}
			currentUrl.searchParams.set('paged', '1');
			window.location.href = currentUrl.toString();
		}
	};

	$(document).ready(function () {
		VWTranslateAdmin.init();
	});
})(jQuery);
