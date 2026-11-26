/**
 * DeepMind Slider Admin JS
 * Media frame integration, slide reordering, layout selector, and modal editor
 */

/* global wp, jQuery, lumosSliderAdmin */
(function ($) {
  'use strict';

  $(document).ready(function () {
    const $modal = $('#lumos-slide-modal');
    const $form = $('#lumos-slide-form');

    // 1. Layout Selector Cards (Hero vs Card)
    function setLayoutSelection(layout) {
      $('#slide-layout-input').val(layout);
      $('.lumos-layout-card').removeClass('is-selected');
      $(`.lumos-layout-card[data-layout="${layout}"]`).addClass('is-selected');

      // Auto adjust heading tag recommendation
      if (layout === 'top-center') {
        $('#slide-title-tag').val('h1');
      } else if (layout === 'top-left') {
        $('#slide-title-tag').val('h2');
      }
    }

    $(document).on('click', '.lumos-layout-card', function () {
      const layout = $(this).data('layout');
      setLayoutSelection(layout);
    });

    // 2. Media Type Selector (Video vs Image)
    function setMediaType(type) {
      $('#slide-media-type-input').val(type);
      $('.lumos-radio-card').removeClass('is-selected');
      $(`.lumos-radio-card[data-type="${type}"]`).addClass('is-selected');

      if (type === 'video') {
        $('.lumos-video-fields').show();
        $('.lumos-image-fields').hide();
        $('.lumos-spec-pill.pill-video, .lumos-spec-pill.pill-poster').addClass('is-active-pill');
        $('.lumos-spec-pill.pill-image').removeClass('is-active-pill');
      } else {
        $('.lumos-video-fields').hide();
        $('.lumos-image-fields').show();
        $('.lumos-spec-pill.pill-image').addClass('is-active-pill');
        $('.lumos-spec-pill.pill-video, .lumos-spec-pill.pill-poster').removeClass('is-active-pill');
      }
    }

    $(document).on('click', '.lumos-radio-card', function () {
      const type = $(this).data('type');
      setMediaType(type);
    });

    // Toggle Modal Media Specs Details
    $(document).on('click', '#btn-toggle-modal-specs', function (e) {
      e.preventDefault();
      const $details = $('#modal-specs-details');
      if ($details.is(':visible')) {
        $details.slideUp(200);
        $(this).html('View Specifications &darr;');
      } else {
        $details.slideDown(200);
        $(this).html('Hide Specifications &uarr;');
      }
    });

    // 3. Button Option Collapsibles (Turn buttons on/off)
    $(document).on('change', '.btn-toggle-options', function () {
      const target = $(this).data('target');
      if ($(this).is(':checked')) {
        $(target).slideDown(200);
      } else {
        $(target).slideUp(200);
      }
    });

    // 4. Quick Slide Status Toggle (Active / Disabled directly from list)
    $(document).on('change', '.btn-toggle-slide-active', function () {
      const $checkbox = $(this);
      const slideId = $checkbox.data('id');
      const $card = $checkbox.closest('.lumos-slide-card');
      const $statusLabel = $card.find('.lumos-status-label');

      $.post(
        lumosSliderAdmin.ajax_url,
        {
          action: 'lumos_toggle_slide_status',
          nonce: lumosSliderAdmin.nonce,
          slide_id: slideId
        },
        function (res) {
          if (res.success) {
            const isActive = res.data.active;
            if (isActive) {
              $card.removeClass('is-disabled').addClass('is-active');
              $statusLabel.text('Active');
            } else {
              $card.removeClass('is-active').addClass('is-disabled');
              $statusLabel.text('Off');
            }

            // Update stored json in card
            const slideData = $card.data('slide');
            if (slideData) {
              slideData.active = isActive;
              $card.data('slide', slideData);
            }
          } else {
            alert(res.data || 'Failed to update slide status');
            $checkbox.prop('checked', !$checkbox.prop('checked'));
          }
        }
      ).fail(function () {
        alert('Network error while toggling status');
        $checkbox.prop('checked', !$checkbox.prop('checked'));
      });
    });

    // 5. WordPress Media Uploader Helper
    function openMediaFrame(title, type, onSelect) {
      if (typeof wp === 'undefined' || !wp.media) {
        alert('WordPress Media Library is only available inside WP Admin.');
        return;
      }

      const frame = wp.media({
        title: title,
        button: { text: 'Use this media' },
        library: { type: type },
        multiple: false
      });

      frame.on('select', function () {
        const attachment = frame.state().get('selection').first().toJSON();
        onSelect(attachment);
      });

      frame.open();
    }

    // Video selector
    $('#btn-select-video').on('click', function (e) {
      e.preventDefault();
      openMediaFrame('Select or Upload Video (MP4 / WebM)', 'video', function (attachment) {
        $('#slide-video-url').val(attachment.url);
      });
    });

    // Poster selector
    $('#btn-select-poster').on('click', function (e) {
      e.preventDefault();
      openMediaFrame('Select Fallback Poster Image', 'image', function (attachment) {
        $('#slide-poster-url').val(attachment.url);
        $('#preview-poster').attr('src', attachment.url);
        $('#box-preview-poster').addClass('has-media');
      });
    });

    // Background Image selector
    $('#btn-select-image').on('click', function (e) {
      e.preventDefault();
      openMediaFrame('Select Background Image', 'image', function (attachment) {
        $('#slide-image-url').val(attachment.url);
        $('#preview-image').attr('src', attachment.url);
        $('#box-preview-image').addClass('has-media');
      });
    });

    // Live URL inputs preview update
    $('#slide-poster-url').on('input change', function () {
      const val = $(this).val().trim();
      if (val) {
        $('#preview-poster').attr('src', val);
        $('#box-preview-poster').addClass('has-media');
      } else {
        $('#box-preview-poster').removeClass('has-media');
      }
    });

    $('#slide-image-url').on('input change', function () {
      const val = $(this).val().trim();
      if (val) {
        $('#preview-image').attr('src', val);
        $('#box-preview-image').addClass('has-media');
      } else {
        $('#box-preview-image').removeClass('has-media');
      }
    });

    // Custom Slide Logo Helpers (SVG or PNG only)
    function isSvgOrPng(url, mime) {
      if (mime && (mime === 'image/svg+xml' || mime === 'image/png')) {
        return true;
      }
      if (!url) return false;
      const clean = url.split('?')[0].split('#')[0].toLowerCase();
      return clean.endsWith('.svg') || clean.endsWith('.png') ||
             url.startsWith('data:image/svg+xml') || url.startsWith('data:image/png');
    }

    function updateLogoPreview(url) {
      if (url && isSvgOrPng(url)) {
        $('#slide-logo-preview-img').attr('src', url);
        const ext = url.split('.').pop().split('?')[0].toUpperCase();
        $('#slide-logo-preview-meta').text(ext.length <= 4 ? ext + ' Graphic' : 'Custom Logo');
        $('#slide-logo-preview').show();
        $('#btn-clear-logo').show();
      } else {
        $('#slide-logo-preview').hide();
        $('#btn-clear-logo').hide();
      }
    }

    // Toggle logo fields
    $(document).on('change', '#slide-logo-enable', function () {
      if ($(this).is(':checked')) {
        $('#slide-logo-fields').slideDown(200);
      } else {
        $('#slide-logo-fields').slideUp(200);
      }
    });

    // Logo Media Picker (SVG or PNG only)
    $('#btn-select-logo').on('click', function (e) {
      e.preventDefault();
      openMediaFrame('Select Custom Logo (.svg or .png only)', 'image', function (attachment) {
        if (!isSvgOrPng(attachment.url, attachment.mime)) {
          alert('Invalid format! Custom slide logo must be an SVG (.svg) or PNG (.png) file.');
          return;
        }
        $('#slide-logo-url').val(attachment.url);
        updateLogoPreview(attachment.url);
      });
    });

    // Clear Logo
    $('#btn-clear-logo').on('click', function (e) {
      e.preventDefault();
      $('#slide-logo-url').val('');
      updateLogoPreview('');
    });

    // Logo URL input change
    $('#slide-logo-url').on('input change', function () {
      const val = $(this).val().trim();
      if (val && !isSvgOrPng(val)) {
        alert('Notice: Logo format must be .svg or .png. Other formats will be rejected upon saving.');
      }
      updateLogoPreview(val);
    });

    // 6. Open Add New Slide Modal
    $('#btn-add-slide').on('click', function (e) {
      e.preventDefault();
      $('#modal-title-text').text('Add New Slide');
      $form[0].reset();
      $('#slide-id').val('new');
      $('#slide-active-checkbox').prop('checked', true);

      setLayoutSelection('top-center');
      setMediaType('video');

      $('#slide-btn1-enable').prop('checked', true);
      $('#btn1-options-box').show();

      $('#slide-btn2-enable').prop('checked', false);
      $('#btn2-options-box').hide();

      // Reset Logo fields
      $('#slide-logo-enable').prop('checked', false);
      $('#slide-logo-fields').hide();
      $('#slide-logo-url').val('');
      $('#slide-logo-position').val('top');
      $('#slide-logo-alt').val('');
      updateLogoPreview('');

      $('#box-preview-poster').removeClass('has-media');
      $('#box-preview-image').removeClass('has-media');
      $modal.addClass('is-open');
    });

    // 7. Open Edit Slide Modal
    $(document).on('click', '.btn-edit-slide', function (e) {
      e.preventDefault();
      const slideData = $(this).closest('.lumos-slide-card').data('slide');
      if (!slideData) return;

      $('#modal-title-text').text('Edit Slide');
      $('#slide-id').val(slideData.id);

      // Active status
      const isActive = !('active' in slideData) || slideData.active === true || slideData.active === '1' || slideData.active === 1;
      $('#slide-active-checkbox').prop('checked', isActive);

      // Layout
      setLayoutSelection(slideData.layout || 'top-center');

      // Title & Text
      $('#slide-title').val(slideData.title || '');
      $('#slide-title-tag').val(slideData.title_tag || 'h2');
      $('#slide-desc').val(slideData.description || '');

      // Logo
      const logoEnabled = !!slideData.logo_enable;
      $('#slide-logo-enable').prop('checked', logoEnabled);
      if (logoEnabled) {
        $('#slide-logo-fields').show();
      } else {
        $('#slide-logo-fields').hide();
      }
      $('#slide-logo-url').val(slideData.logo_url || '');
      $('#slide-logo-position').val(slideData.logo_position || 'top');
      $('#slide-logo-alt').val(slideData.logo_alt || '');
      updateLogoPreview(slideData.logo_url || '');

      // Media
      const mediaType = slideData.media_type || 'video';
      setMediaType(mediaType);

      $('#slide-video-url').val(slideData.video_url || '');
      $('#slide-poster-url').val(slideData.poster_url || '');
      $('#slide-image-url').val(slideData.image_url || '');

      if (slideData.poster_url) {
        $('#preview-poster').attr('src', slideData.poster_url);
        $('#box-preview-poster').addClass('has-media');
      } else {
        $('#box-preview-poster').removeClass('has-media');
      }

      if (slideData.image_url) {
        $('#preview-image').attr('src', slideData.image_url);
        $('#box-preview-image').addClass('has-media');
      } else {
        $('#box-preview-image').removeClass('has-media');
      }

      $('#slide-scrim-type').val(slideData.scrim_type || 'scrim-dark');

      // Button 1
      const btn1Enabled = !!slideData.button_1_enable;
      $('#slide-btn1-enable').prop('checked', btn1Enabled);
      $('#slide-btn1-text').val(slideData.button_1_text || '');
      $('#slide-btn1-url').val(slideData.button_1_url || '');
      $('#slide-btn1-target').prop('checked', slideData.button_1_target === '_blank');
      $('#slide-btn1-style').val(slideData.button_1_style || 'primary');
      $('#slide-btn1-icon').val(slideData.button_1_icon || 'none');
      if (btn1Enabled) {
        $('#btn1-options-box').show();
      } else {
        $('#btn1-options-box').hide();
      }

      // Button 2
      const btn2Enabled = !!slideData.button_2_enable;
      $('#slide-btn2-enable').prop('checked', btn2Enabled);
      $('#slide-btn2-text').val(slideData.button_2_text || '');
      $('#slide-btn2-url').val(slideData.button_2_url || '');
      $('#slide-btn2-target').prop('checked', slideData.button_2_target === '_blank');
      $('#slide-btn2-style').val(slideData.button_2_style || 'protected');
      $('#slide-btn2-icon').val(slideData.button_2_icon || 'none');
      if (btn2Enabled) {
        $('#btn2-options-box').show();
      } else {
        $('#btn2-options-box').hide();
      }

      $modal.addClass('is-open');
    });

    // Close Modal
    $('.lumos-modal-close, #btn-modal-cancel').on('click', function (e) {
      e.preventDefault();
      $modal.removeClass('is-open');
    });

    $modal.on('click', function (e) {
      if ($(e.target).is($modal)) {
        $modal.removeClass('is-open');
      }
    });

    // 8. Delete Slide
    $(document).on('click', '.btn-delete-slide', function (e) {
      e.preventDefault();
      if (!confirm('Are you sure you want to delete this slide?')) return;

      const slideId = $(this).closest('.lumos-slide-card').data('id');
      const $card = $(this).closest('.lumos-slide-card');

      $.post(
        lumosSliderAdmin.ajax_url,
        {
          action: 'lumos_delete_slide',
          nonce: lumosSliderAdmin.nonce,
          slide_id: slideId
        },
        function (res) {
          if (res.success) {
            $card.fadeOut(300, function () { $(this).remove(); });
          } else {
            alert(res.data || 'Failed to delete slide');
          }
        }
      );
    });

    // 9. Duplicate Slide
    $(document).on('click', '.btn-duplicate-slide', function (e) {
      e.preventDefault();
      const slideId = $(this).closest('.lumos-slide-card').data('id');

      $.post(
        lumosSliderAdmin.ajax_url,
        {
          action: 'lumos_duplicate_slide',
          nonce: lumosSliderAdmin.nonce,
          slide_id: slideId
        },
        function (res) {
          if (res.success) {
            window.location.reload();
          } else {
            alert(res.data || 'Failed to duplicate slide');
          }
        }
      );
    });

    // 10. Reset / Restore Demo Slides
    $('#btn-reset-demo').on('click', function (e) {
      e.preventDefault();
      if (!confirm('This will restore the 4 default DeepMind Gemini Robotics slides. Continue?')) return;

      $.post(
        lumosSliderAdmin.ajax_url,
        {
          action: 'lumos_reset_demo_slides',
          nonce: lumosSliderAdmin.nonce
        },
        function (res) {
          if (res.success) {
            window.location.reload();
          } else {
            alert(res.data || 'Failed to restore demo slides');
          }
        }
      );
    });

    // 11. Drag and drop reordering
    if ($.fn.sortable) {
      $('#lumos-slides-container').sortable({
        handle: '.lumos-slide-drag-handle',
        placeholder: 'lumos-slide-placeholder',
        update: function () {
          const order = [];
          $('#lumos-slides-container .lumos-slide-card').each(function () {
            order.push($(this).data('id'));
          });

          $.post(lumosSliderAdmin.ajax_url, {
            action: 'lumos_save_slide_order',
            nonce: lumosSliderAdmin.nonce,
            order: order
          });
        }
      });
    }

    // 12. Save Slide Form Submission
    $form.on('submit', function (e) {
      e.preventDefault();
      const formData = $form.serialize();

      $.post(
        lumosSliderAdmin.ajax_url,
        formData + '&action=lumos_save_slide&nonce=' + lumosSliderAdmin.nonce,
        function (res) {
          if (res.success) {
            window.location.reload();
          } else {
            alert(res.data || 'Error saving slide');
          }
        }
      );
    });

    // Copy shortcode button
    $('#btn-copy-shortcode').on('click', function (e) {
      e.preventDefault();
      const text = '[deepmind_slider]';
      navigator.clipboard.writeText(text).then(function () {
        $('#btn-copy-shortcode').text('Copied!');
        setTimeout(function () {
          $('#btn-copy-shortcode').text('Copy Shortcode');
        }, 2000);
      });
    });

    // 13. Import & Export Dropzone and AJAX Form Handler
    const $dropzone = $('#lumos-file-dropzone');
    const $fileInput = $('#import_file');
    const $fileInfo = $('#lumos-selected-file-info');
    const $fileNameDisplay = $('#lumos-selected-filename');
    const $importForm = $('#lumos-import-form');
    const $importStatus = $('#lumos-import-status');
    const $btnImportSubmit = $('#btn-submit-import');

    if ($dropzone.length) {
      $dropzone.on('click', function () {
        $fileInput.trigger('click');
      });

      $dropzone.on('dragover dragenter', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $dropzone.addClass('is-dragover');
      });

      $dropzone.on('dragleave dragend drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $dropzone.removeClass('is-dragover');
      });

      $dropzone.on('drop', function (e) {
        const files = e.originalEvent.dataTransfer.files;
        if (files && files.length) {
          $fileInput[0].files = files;
          updateSelectedFile(files[0]);
        }
      });

      $fileInput.on('change', function () {
        if (this.files && this.files.length) {
          updateSelectedFile(this.files[0]);
        }
      });

      function updateSelectedFile(file) {
        const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
        $fileNameDisplay.text(file.name + ' (' + sizeMb + ' MB)');
        $fileInfo.show();
      }
    }

    if ($importForm.length) {
      $importForm.on('submit', function (e) {
        e.preventDefault();

        if (!$fileInput[0].files || !$fileInput[0].files.length) {
          alert('Please select an export file (.zip or .json) to import.');
          return;
        }

        const formData = new FormData(this);
        formData.append('action', 'lumos_import_slides');
        formData.append('nonce', lumosSliderAdmin.nonce);

        $btnImportSubmit.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Importing Package...');
        $importStatus.html('<div class="lumos-ie-alert lumos-ie-alert-info"><span class="dashicons dashicons-update spin"></span> Uploading backup, extracting archive, and registering media in Media Library... Please wait.</div>').slideDown(200);

        $.ajax({
          url: lumosSliderAdmin.ajax_url,
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: function (res) {
            $btnImportSubmit.prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> <span>Upload and Import Package</span>');
            if (res.success) {
              $importStatus.html('<div class="lumos-ie-alert lumos-ie-alert-success"><span class="dashicons dashicons-yes-alt"></span> ' + (res.data.message || 'Import completed successfully!') + ' Redirecting to slides...</div>');
              setTimeout(function () {
                window.location.href = 'admin.php?page=lumos-deepmind-slider';
              }, 1600);
            } else {
              $importStatus.html('<div class="lumos-ie-alert lumos-ie-alert-error"><span class="dashicons dashicons-warning"></span> ' + (res.data || 'Import failed.') + '</div>');
            }
          },
          error: function (xhr, status, err) {
            $btnImportSubmit.prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> <span>Upload and Import Package</span>');
            $importStatus.html('<div class="lumos-ie-alert lumos-ie-alert-error"><span class="dashicons dashicons-warning"></span> Server error occurred during import: ' + err + '</div>');
          }
        });
      });
    }
  });
})(jQuery);
