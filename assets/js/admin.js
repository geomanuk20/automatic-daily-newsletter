/**
 * Admin JavaScript for Auto Daily Newsletter
 */

(function($) {
	'use strict';

	$(document).ready(function() {

		// Toggle SMTP options based on Mailer Type selection
		$('input[name="adnl_mailer_type"]').on('change', function() {
			if ($(this).val() === 'smtp') {
				$('#adnl-smtp-credentials-section').slideDown(200);
			} else {
				$('#adnl-smtp-credentials-section').slideUp(200);
			}
		});

		// 1-Click SMTP Presets
		$(document).on('click', '.adnl-smtp-preset', function(e) {
			e.preventDefault();
			var btnText = $(this).text().toLowerCase();
			if (btnText.indexOf('gmail') !== -1 && typeof window.adnlApplyPreset === 'function') {
				window.adnlApplyPreset('gmail');
			} else if (btnText.indexOf('brevo') !== -1 && typeof window.adnlApplyPreset === 'function') {
				window.adnlApplyPreset('brevo');
			} else if (btnText.indexOf('sendgrid') !== -1 && typeof window.adnlApplyPreset === 'function') {
				window.adnlApplyPreset('sendgrid');
			} else if (btnText.indexOf('mailgun') !== -1 && typeof window.adnlApplyPreset === 'function') {
				window.adnlApplyPreset('mailgun');
			} else if (btnText.indexOf('aws') !== -1 && typeof window.adnlApplyPreset === 'function') {
				window.adnlApplyPreset('aws');
			} else {
				var host = $(this).data('host');
				var port = $(this).data('port');
				var enc  = $(this).data('enc');
				$('input[name="adnl_smtp_host"]').val(host);
				$('input[name="adnl_smtp_port"]').val(port);
				$('select[name="adnl_smtp_encryption"]').val(enc);
				$('input[name="adnl_smtp_auth"]').prop('checked', true);
			}
		});

		// Send Test Email AJAX
		$('#adnl-btn-send-test').on('click', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var email = $('#adnl-test-email-input').val();
			var $status = $('#adnl-test-status');

			if (!email) {
				alert('Please enter an email address for testing.');
				return;
			}

			$btn.prop('disabled', true).text(adnl_admin_obj.strings.sending);
			$status.hide().removeClass('adnl-alert-success adnl-alert-danger');

			$.post(adnl_admin_obj.ajax_url, {
				action: 'adnl_send_test_email',
				nonce: adnl_admin_obj.nonce,
				test_email: email
			}, function(response) {
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-email-alt"></span> Send Test Email');
				$status.show();
				if (response.success) {
					$status.addClass('adnl-alert-success').text(response.data.message);
				} else {
					$status.addClass('adnl-alert-danger').text(response.data.message);
				}
			}).fail(function() {
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-email-alt"></span> Send Test Email');
				$status.show().addClass('adnl-alert-danger').text(adnl_admin_obj.strings.error);
			});
		});

		// Trigger Manual Digest Dispatch Now (Header & Logs Tab)
		$(document).on('click', '#adnl-btn-manual-send, #adnl-btn-manual-send-logs', function(e) {
			e.preventDefault();
			if (!confirm(adnl_admin_obj.strings.confirm_manual_send)) {
				return;
			}

			var $btn = $(this);
			var originalHtml = $btn.html();
			$btn.prop('disabled', true).text(adnl_admin_obj.strings.sending);

			$.post(adnl_admin_obj.ajax_url, {
				action: 'adnl_manual_send',
				nonce: adnl_admin_obj.nonce
			}, function(response) {
				$btn.prop('disabled', false).html(originalHtml);
				if (response.success) {
					alert(response.data.message);
					window.location.reload();
				} else {
					alert(response.data.message);
				}
			}).fail(function() {
				$btn.prop('disabled', false).html(originalHtml);
				alert(adnl_admin_obj.strings.error);
			});
		});

		// Preview Newsletter Modal & Iframe
		$('#adnl-btn-preview-digest').on('click', function(e) {
			e.preventDefault();
			$('#adnl-preview-modal').fadeIn(150);
			$('#adnl-preview-loading').show();
			var currentSiteLogo = $('#adnl-site-logo-input').val();
			var currentHeaderTitle = $('input[name="adnl_header_title"]').val();

			$.post(adnl_admin_obj.ajax_url, {
				action: 'adnl_preview_digest',
				nonce: adnl_admin_obj.nonce,
				site_logo: currentSiteLogo,
				header_title: currentHeaderTitle
			}, function(response) {
				$('#adnl-preview-loading').hide();
				if (response.success) {
					var iframe = document.getElementById('adnl-preview-iframe');
					var doc = iframe.contentWindow || iframe.contentDocument.document || iframe.contentDocument;
					doc.document.open();
					doc.document.write(response.data.html);
					doc.document.close();
					$('#adnl-preview-iframe').show();
				} else {
					alert(response.data.message);
					$('#adnl-preview-modal').fadeOut(150);
				}
			}).fail(function() {
				$('#adnl-preview-loading').hide();
				alert(adnl_admin_obj.strings.error);
				$('#adnl-preview-modal').fadeOut(150);
			});
		});

		// Add Subscriber Modal Trigger (Subscribers tab & Dashboard)
		$(document).on('click', '#adnl-btn-add-subscriber-modal, #adnl-btn-add-subscriber-modal-dash', function(e) {
			e.preventDefault();
			$('#adnl-add-subscriber-modal').fadeIn(150);
			$('#adnl-new-sub-email').val('').focus();
			$('#adnl-new-sub-name').val('');
			$('#adnl-add-sub-error').hide();
		});

		// Import Subscribers Modal Trigger (Subscribers tab & Dashboard)
		$(document).on('click', '#adnl-btn-import-subscribers-modal, #adnl-btn-import-subscribers-modal-dash', function(e) {
			e.preventDefault();
			$('#adnl-import-subscriber-modal').fadeIn(150);
		});

		// Submit Add Subscriber AJAX
		$('#adnl-btn-submit-add-sub').on('click', function() {
			var email = $('#adnl-new-sub-email').val();
			var name = $('#adnl-new-sub-name').val();
			var $btn = $(this);
			var $err = $('#adnl-add-sub-error');

			if (!email) {
				$err.show().text('Please enter an email address.');
				return;
			}

			$btn.prop('disabled', true);
			$err.hide();

			$.post(adnl_admin_obj.ajax_url, {
				action: 'adnl_admin_add_subscriber',
				nonce: adnl_admin_obj.nonce,
				email: email,
				name: name
			}, function(response) {
				$btn.prop('disabled', false);
				if (response.success) {
					window.location.reload();
				} else {
					$err.show().text(response.data.message);
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				$err.show().text(adnl_admin_obj.strings.error);
			});
		});

		// Delete Subscriber AJAX
		$('.adnl-btn-delete-subscriber').on('click', function() {
			var $btn = $(this);
			var subId = $btn.data('id');

			if (!confirm(adnl_admin_obj.strings.confirm_delete_sub)) {
				return;
			}

			$btn.prop('disabled', true);

			$.post(adnl_admin_obj.ajax_url, {
				action: 'adnl_admin_delete_subscriber',
				nonce: adnl_admin_obj.nonce,
				subscriber_id: subId
			}, function(response) {
				if (response.success) {
					$('#subscriber-row-' + subId).fadeOut(300, function() {
						$(this).remove();
					});
				} else {
					alert(response.data.message);
					$btn.prop('disabled', false);
				}
			});
		});

		// Logo Upload Media Uploader
		$('#adnl-upload-logo-btn').on('click', function(e) {
			e.preventDefault();
			if (typeof wp !== 'undefined' && wp.media) {
				var frame = wp.media({
					title: 'Select or Upload Newsletter Logo',
					button: { text: 'Use this Logo' },
					multiple: false
				});
				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					$('#adnl-site-logo-input').val(attachment.url);
					$('#adnl-logo-preview-img').attr('src', attachment.url);
					$('#adnl-logo-preview-wrap').show();
					$('#adnl-remove-logo-btn').show();
				});
				frame.open();
			} else {
				// Fallback prompt if not inside full WP media context
				var customUrl = prompt('Enter the direct image URL for your logo (e.g. https://yoursite.com/logo.png):', $('#adnl-site-logo-input').val());
				if (customUrl) {
					$('#adnl-site-logo-input').val(customUrl);
					$('#adnl-logo-preview-img').attr('src', customUrl);
					$('#adnl-logo-preview-wrap').show();
					$('#adnl-remove-logo-btn').show();
				}
			}
		});

		function updateAllLogos(logoUrl, popupHeight) {
			popupHeight = parseInt(popupHeight || $('#adnl-popup-logo-height-slider').val() || 55, 10);

			if (logoUrl) {
				// 1. Settings logo preview
				$('#adnl-logo-preview-img')
					.attr('src', logoUrl)
					.css({ 'max-height': '70px', 'max-width': '260px', 'width': 'auto', 'height': 'auto' });
				$('#adnl-logo-preview-wrap').show();
				$('#adnl-remove-logo-btn').show();

				// 2. In-Admin Popup card preview
				var $liveImg = $('#adnl-live-preview-logo-img');
				if ($liveImg.length) {
					$liveImg.attr('src', logoUrl).attr('height', popupHeight).css({ 'height': popupHeight + 'px', 'max-height': popupHeight + 'px', 'width': 'auto' });
				} else {
					$('#adnl-live-preview-logo-wrap').html('<img id="adnl-live-preview-logo-img" src="' + logoUrl + '" height="' + popupHeight + '" style="height:' + popupHeight + 'px; max-height:' + popupHeight + 'px; max-width:280px; width:auto; display:block; margin:0 auto;" />');
				}

				// 3. Floating Bottom-Left Popup
				var $popImg = $('.adnl-popup-logo').find('img');
				if ($popImg.length) {
					$popImg.attr('src', logoUrl).attr('height', popupHeight).css({ 'height': popupHeight + 'px', 'max-height': popupHeight + 'px', 'width': 'auto' });
				} else {
					$('.adnl-popup-logo').html('<img src="' + logoUrl + '" height="' + popupHeight + '" style="height:' + popupHeight + 'px; max-height:' + popupHeight + 'px; max-width:280px; width:auto; display:block; margin:0 auto;" />');
				}

				// 4. If email preview iframe is loaded, update its logo too
				try {
					var $iframe = $('#adnl-preview-iframe');
					if ($iframe.length && $iframe[0].contentDocument) {
						var $iDoc = $($iframe[0].contentDocument);
						$iDoc.find('img[alt]').first()
							.attr('src', logoUrl)
							.css({ 'max-height': '80px', 'max-width': '380px', 'width': 'auto', 'height': 'auto' });
					}
				} catch(err) {}
			} else {
				// Dynamic brand mark
				var siteName = $('input[name="adnl_from_name"]').val() || $('input[name="adnl_header_title"]').val() || 'News';
				var initial  = siteName.charAt(0).toUpperCase() || 'N';
				var fallback = '<div style="display: flex; align-items: center; gap: 8px; justify-content: center;">' +
					'<span style="background: #e11d48; color: #ffffff; font-size: 14px; font-weight: 900; padding: 4px 7px; border-radius: 3px; font-family: sans-serif; line-height: 1;">' + initial + '</span>' +
					'<span style="font-size: 20px; font-weight: 800; color: #004b87; font-family: sans-serif; letter-spacing: -0.5px; line-height: 1;">' +
					siteName + '</span></div>';
				
				$('#adnl-logo-preview-wrap').hide();
				$('#adnl-remove-logo-btn').hide();
				$('#adnl-live-preview-logo-wrap').html(fallback);
				$('.adnl-popup-logo').html(fallback);
			}
		}

		// Frontend Popup Logo slider input event
		$('#adnl-popup-logo-height-slider').on('input change', function() {
			var h = $(this).val();
			$('#adnl-popup-logo-height-val').text(h + 'px');
			var logoUrl = $('#adnl-site-logo-input').val();
			updateAllLogos(logoUrl, h);
		});

		// Logo input change event
		$('#adnl-site-logo-input').on('input change', function() {
			var logoUrl = $(this).val();
			updateAllLogos(logoUrl);
		});

		// Header title change event
		$('input[name="adnl_header_title"]').on('input change', function() {
			var val = $(this).val();
			try {
				var $iframe = $('#adnl-preview-iframe');
				if ($iframe.length && $iframe[0].contentDocument) {
					$iframe.contents().find('h1').text(val);
				}
			} catch(e) {}
		});

		// Direct Logo Upload from Computer
		$('#adnl-direct-upload-logo-btn').on('click', function(e) {
			e.preventDefault();
			$('#adnl-logo-file-picker').click();
		});

		$('#adnl-logo-file-picker').on('change', function(e) {
			var file = e.target.files[0];
			if (file) {
				var reader = new FileReader();
				reader.onload = function(evt) {
					var dataUrl = evt.target.result;
					$('#adnl-site-logo-input').val(dataUrl).trigger('change');
				};
				reader.readAsDataURL(file);
			}
		});

		// Media Library Logo Upload
		$('#adnl-upload-logo-btn').on('click', function(e) {
			e.preventDefault();
			if (typeof wp !== 'undefined' && wp.media) {
				var frame = wp.media({
					title: 'Select or Upload Newsletter Publication Logo',
					button: { text: 'Use this Logo' },
					multiple: false
				});
				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					$('#adnl-site-logo-input').val(attachment.url).trigger('change');
				});
				frame.open();
			} else {
				$('#adnl-direct-upload-logo-btn').trigger('click');
			}
		});

		// Remove Logo
		$('#adnl-remove-logo-btn').on('click', function(e) {
			e.preventDefault();
			$('#adnl-site-logo-input').val('').trigger('change');
		});

		// Toggle Manual News Selection container
		$('input[name="adnl_selection_mode"]').on('change', function() {
			if ($(this).val() === 'manual') {
				$('#adnl-manual-selection-container').slideDown(200);
			} else {
				$('#adnl-manual-selection-container').slideUp(200);
			}
		});

		// Update checked post highlight and counter
		$('.adnl-post-checkbox').on('change', function() {
			var $row = $(this).closest('.adnl-post-select-row');
			if ($(this).is(':checked')) {
				$row.css('background', '#eff6ff');
			} else {
				$row.css('background', '#ffffff');
			}
			var count = $('.adnl-post-checkbox:checked').length;
			$('#adnl-selected-counter').text(count + ' Articles Selected');
		});

		// Filter News Rows (Today vs All + Search Query)
		function applyNewsFilter() {
			var activeFilter = $('.adnl-filter-btn.active').data('filter') || 'all';
			var searchTerm   = ($('#adnl-news-search').val() || '').toLowerCase().trim();

			$('.adnl-post-select-row').each(function() {
				var $row     = $(this);
				var isToday  = $row.data('is-today') === 1 || $row.data('is-today') === '1';
				var title    = ($row.data('title') || '').toString().toLowerCase();
				var category = ($row.data('category') || '').toString().toLowerCase();

				var matchesFilter = (activeFilter === 'all') || (activeFilter === 'today' && isToday);
				var matchesSearch = !searchTerm || title.indexOf(searchTerm) !== -1 || category.indexOf(searchTerm) !== -1;

				if (matchesFilter && matchesSearch) {
					$row.show();
				} else {
					$row.hide();
				}
			});
		}

		// Initial filter apply on page load if Today's news filter is active
		if ($('#adnl-filter-today.active').length) {
			applyNewsFilter();
		}

		// Switch Filter (Today vs All)
		$(document).on('click', '.adnl-filter-btn', function(e) {
			e.preventDefault();
			$('.adnl-filter-btn').removeClass('active button-primary');
			$(this).addClass('active button-primary');
			applyNewsFilter();
		});

		// Live Search Filter
		$(document).on('input', '#adnl-news-search', function() {
			applyNewsFilter();
		});

		// Select All Today
		$(document).on('click', '#adnl-btn-select-all-today', function(e) {
			e.preventDefault();
			$('.adnl-post-select-row[data-is-today="1"] .adnl-post-checkbox').prop('checked', true).trigger('change');
		});

		// Deselect All
		$(document).on('click', '#adnl-btn-clear-selection', function(e) {
			e.preventDefault();
			$('.adnl-post-checkbox').prop('checked', false).trigger('change');
		});


		// Position change event
		$('#adnl-popup-position-select').on('change', function() {
			var pos = $(this).val();
			var label = $(this).find('option:selected').text().replace(/^[^a-zA-Z]+/, '').trim();
			$('#adnl-btn-preview-label').text('Trigger ' + label + ' Preview');
			
			var $popup = $('#adnl-slidein-popup');
			if ($popup.length) {
				$popup.removeClass('adnl-pos-bottom-left adnl-pos-bottom-right adnl-pos-top-left adnl-pos-top-right adnl-pos-center adnl-popup-bottom-left')
					  .addClass('adnl-pos-' + pos);
			}
		});

		// Trigger Live Popup Preview in Admin at selected position
		$('#adnl-btn-preview-popup, #adnl-btn-preview-popup-again').on('click', function(e) {
			e.preventDefault();
			var $popup = $('#adnl-slidein-popup');
			if ($popup.length) {
				var pos = $('#adnl-popup-position-select').val() || 'bottom-left';
				$popup.removeClass('adnl-pos-bottom-left adnl-pos-bottom-right adnl-pos-top-left adnl-pos-top-right adnl-pos-center adnl-popup-bottom-left')
					  .addClass('adnl-pos-' + pos);

				var title = $('input[name="adnl_popup_title"]').val();
				var msg   = $('textarea[name="adnl_popup_message"]').val();
				var btn   = $('input[name="adnl_popup_button"]').val();
				if (title) $popup.find('.adnl-popup-title').text(title);
				if (msg) $popup.find('.adnl-popup-desc').text(msg);
				if (btn) $popup.find('.adnl-btn-text').text(btn);

				var logoH = $('#adnl-popup-logo-height-slider').val();
				if (logoH) {
					$popup.find('.adnl-popup-logo img').css({ 'height': logoH + 'px', 'max-height': logoH + 'px' });
				}

				$popup.show();
				setTimeout(function() {
					$popup.addClass('adnl-popup-visible');
				}, 50);
			}
		});

		// Live update in-admin popup preview card & screen popup as user types
		$('input[name="adnl_popup_title"]').on('input change', function() {
			var val = $(this).val() || 'HI THERE!';
			$('#adnl-live-preview-title').text(val);
			$('#adnl-slidein-popup .adnl-popup-title').text(val);
		});

		$('textarea[name="adnl_popup_message"]').on('input change', function() {
			var val = $(this).val() || 'Subscribe to our newsletter for daily news & updates delivered straight to your inbox.';
			$('#adnl-live-preview-desc').text(val);
			$('#adnl-slidein-popup .adnl-popup-desc').text(val);
		});

		$('input[name="adnl_popup_button"]').on('input change', function() {
			var val = $(this).val() || 'SUBMIT';
			$('#adnl-live-preview-btn-text').text(val);
			$('#adnl-slidein-popup .adnl-btn-text').text(val);
		});

		$('#adnl-popup-btn-color-input, input[name="adnl_popup_btn_color"]').on('input change', function() {
			var color = $(this).val();
			$('#adnl-live-preview-btn').css('background-color', color);
			$('#adnl-slidein-popup .adnl-popup-submit').css('background-color', color);
		});

		$('input[name="adnl_popup_placeholder"]').on('input change', function() {
			var ph = $(this).val() || 'Email';
			$('#adnl-live-preview-input').attr('placeholder', ph);
			$('#adnl-slidein-popup input.adnl-popup-input').attr('placeholder', ph);
		});

		$('#adnl-popup-show-logo-input').on('change', function() {
			if ($(this).is(':checked')) {
				$('#adnl-live-preview-logo-wrap').show();
				$('#adnl-slidein-popup .adnl-popup-logo').show();
			} else {
				$('#adnl-live-preview-logo-wrap').hide();
				$('#adnl-slidein-popup .adnl-popup-logo').hide();
			}
		});

		// Interactive test in Live Preview Card
		$('#adnl-live-preview-btn').on('click', function(e) {
			e.preventDefault();
			var email = $('#adnl-live-preview-input').val().trim();
			var $msg = $('#adnl-live-preview-msg');
			if (!email) {
				$msg.css('color', '#e11d48').text('Please enter a test email address above.').fadeIn(150);
				$('#adnl-live-preview-input').focus();
			} else if (email.indexOf('@') === -1 || email.indexOf('.') === -1) {
				$msg.css('color', '#e11d48').text('Please enter a valid email address.').fadeIn(150);
			} else {
				$msg.css('color', '#16a34a').text('✓ Live test successful! Subscription input & button working properly.').fadeIn(150);
				setTimeout(function() { $msg.fadeOut(300); }, 3500);
			}
		});

		$('#adnl-live-preview-form').on('submit', function(e) {
			e.preventDefault();
			$('#adnl-live-preview-btn').trigger('click');
		});

		// Popup Image Direct Upload from Computer
		$('#adnl-direct-upload-popup-img-btn').on('click', function(e) {
			e.preventDefault();
			$('#adnl-popup-file-picker').click();
		});

		$('#adnl-popup-file-picker').on('change', function(e) {
			var file = e.target.files[0];
			if (file) {
				var reader = new FileReader();
				reader.onload = function(evt) {
					var dataUrl = evt.target.result;
					$('#adnl-popup-image-input').val(dataUrl).trigger('change');
				};
				reader.readAsDataURL(file);
			}
		});

		// Popup Image Media Library Uploader
		$('#adnl-upload-popup-img-btn').on('click', function(e) {
			e.preventDefault();
			if (typeof wp !== 'undefined' && wp.media) {
				var frame = wp.media({
					title: 'Select or Upload Popup Side Image',
					button: { text: 'Use this Image' },
					multiple: false
				});
				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					$('#adnl-popup-image-input').val(attachment.url).trigger('change');
				});
				frame.open();
			} else {
				$('#adnl-direct-upload-popup-img-btn').trigger('click');
			}
		});

		// Reset Popup Image
		$('#adnl-reset-popup-img-btn').on('click', function(e) {
			e.preventDefault();
			var defaultImg = 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&w=600&q=80';
			$('#adnl-popup-image-input').val(defaultImg).trigger('change');
		});

		// Sync Popup Image changes everywhere
		$('#adnl-popup-image-input').on('input change', function() {
			var url = $(this).val();
			if (url) {
				$('#adnl-popup-thumb-img').attr('src', url);
				$('#adnl-live-preview-image').css('background-image', 'url(' + url + ')');
				$('.adnl-popup-image').css('background-image', 'url(' + url + ')');
			}
		});

		$('#adnl-btn-preview-popup-again').on('click', function(e) {
			e.preventDefault();
			$('#adnl-btn-preview-popup').trigger('click');
		});

		// Dynamic Live Clock for Active Timezone
		function updateLiveTimezoneClock() {
			var tz = $('#adnl-timezone-select').val();
			if (!tz) {
				try {
					tz = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
				} catch (e) {
					tz = 'UTC';
				}
			}
			try {
				var now = new Date();
				var timeStr = now.toLocaleTimeString('en-US', { timeZone: tz, hour: 'numeric', minute: '2-digit', hour12: true });
				$('#adnl-live-clock').text(timeStr);
				$('#adnl-live-tz-name').text(tz);
			} catch (e) {}
		}

		$('#adnl-timezone-select').on('change', function() {
			updateLiveTimezoneClock();
			var tz = $(this).val();
			if (tz) {
				var nowStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
				$('#adnl-tz-hint').show().html('✅ Selected timezone: <strong>' + tz + '</strong> (Current time: ' + nowStr + '). Click <strong>Save Post & Schedule Settings</strong> below to apply!');
			}
		});

		// Timezone Auto-Detection Button
		$('#adnl-detect-tz-btn').on('click', function(e) {
			e.preventDefault();
			try {
				var userTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
				if (userTz) {
					var $select = $('#adnl-timezone-select');
					if ($select.find('option[value="' + userTz + '"]').length === 0) {
						$select.append(new Option(userTz, userTz, true, true));
					} else {
						$select.val(userTz);
					}
					updateLiveTimezoneClock();
					var nowStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
					$('#adnl-tz-hint').show().html('✅ Set to your device location: <strong>' + userTz + '</strong> (Current time: ' + nowStr + '). Click <strong>Save Post & Schedule Settings</strong> below to apply!');
				}
			} catch (err) {
				alert('Could not detect timezone automatically. Please pick your timezone from the dropdown list.');
			}
		});

		// Tag Insertion into Email Subject Field
		$(document).on('click', '.adnl-insert-tag', function(e) {
			e.preventDefault();
			var tag = $(this).data('tag');
			var $textarea = $('#adnl_email_subject');
			if ($textarea.length === 0) return;

			var elem = $textarea[0];
			var startPos = elem.selectionStart || 0;
			var endPos = elem.selectionEnd || 0;
			var currentVal = $textarea.val();

			$textarea.val(currentVal.substring(0, startPos) + tag + currentVal.substring(endPos));
			$textarea.focus();
			elem.selectionStart = elem.selectionEnd = startPos + tag.length;
			updateLiveSubjectPreview();
		});

		// 7-News Random Presets Loader
		var sampleSubjectPresets = [
			"🔥 {top_story} — Plus {posts_count} more stories for {date}",
			"[{site_name}] {day} News Briefing: {random_story}",
			"⚡ Breaking: {top_story} ({date})",
			"Today's Top {posts_count} Stories You Need to Know: {random_story}",
			"Your Daily Digest for {day}, {date}: {top_story}",
			"📰 {random_story} — plus {posts_count} top updates today"
		];

		$(document).on('click', '#adnl-load-subject-presets', function(e) {
			e.preventDefault();
			var $textarea = $('#adnl_email_subject');
			$textarea.val(sampleSubjectPresets.join("\n"));
			updateLiveSubjectPreview();
		});

		// Sample news headlines for live JS simulation
		var sampleHeadlines = [
			"Next-Generation Quantum Chips Achieve Breakthrough in Real-Time Quantum Processing",
			"Global Green Energy Grid Links Cross-Continental Solar Corridors",
			"Central Banks Announce Synchronized Liquidity Framework for Digital Assets",
			"Autonomous Deep-Sea Rovers Discover Hydrothermal Ecosystems in Kermadec Trench",
			"Open-Source Foundation Releases Multimodal Agent Operating System",
			"Space Telescope Identifies Atmosphere Rich in Water Vapor on Exoplanet",
			"Global Manufacturing Survey Signals Rebound in High-Tech Industrial Output"
		];

		function updateLiveSubjectPreview() {
			var text = $('#adnl_email_subject').val() || "[Daily Digest] Today's Top Stories - {date}";
			var lines = text.split(/\r?\n|\|/).map(function(l) { return l.trim(); }).filter(function(l) { return l.length > 0; });
			if (lines.length === 0) {
				lines = ["[Daily Digest] Today's Top Stories - {date}"];
			}

			// Randomly pick one template from lines
			var chosenTemplate = lines[Math.floor(Math.random() * lines.length)];
			
			// Date & Tag variables
			var now = new Date();
			var dateOptions = { year: 'numeric', month: 'long', day: 'numeric' };
			var dayOptions = { weekday: 'long' };
			var monthOptions = { month: 'long' };
			
			var dateStr = now.toLocaleDateString('en-US', dateOptions);
			var dayStr = now.toLocaleDateString('en-US', dayOptions);
			var monthStr = now.toLocaleDateString('en-US', monthOptions);
			var yearStr = now.getFullYear().toString();
			var siteName = $('input[name="adnl_from_name"]').val() || $('input[name="adnl_header_title"]').val() || 'Daily News';
			var postCount = $('input[name="adnl_posts_count"]').val() || '7';

			var topStory = sampleHeadlines[0];
			var randStory = sampleHeadlines[Math.floor(Math.random() * sampleHeadlines.length)];

			var resolved = chosenTemplate
				.replace(/\{date\}/g, dateStr)
				.replace(/\{day\}/g, dayStr)
				.replace(/\{month\}/g, monthStr)
				.replace(/\{year\}/g, yearStr)
				.replace(/\{site_name\}/g, siteName)
				.replace(/\{posts_count\}/g, postCount)
				.replace(/\{top_story\}/g, topStory)
				.replace(/\{first_story\}/g, topStory)
				.replace(/\{headline\}/g, topStory)
				.replace(/\{random_story\}/g, randStory)
				.replace(/\{random_news\}/g, randStory);

			$('#adnl-subject-preview-text').text(resolved);
		}

		$('#adnl_email_subject').on('input keyup change', function() {
			updateLiveSubjectPreview();
		});

		$(document).on('click', '#adnl-shuffle-preview-btn', function(e) {
			e.preventDefault();
			updateLiveSubjectPreview();
		});

		// Close Modals
		$('.adnl-modal-close').on('click', function() {
			$(this).closest('.adnl-modal').fadeOut(150);
		});

		$(window).on('click', function(e) {
			if ($(e.target).hasClass('adnl-modal')) {
				$('.adnl-modal').fadeOut(150);
			}
		});

	});
})(jQuery);
