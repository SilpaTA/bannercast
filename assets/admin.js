/* ════════════════════════════════════════
   Notice Bar Pro – Admin JS
════════════════════════════════════════ */
(function ($) {
    'use strict';

    var A = window.BC_ADMIN || {};

    /* ══════════════════════════════════
       LIST PAGE
    ══════════════════════════════════ */

    /* Toggle enable/disable */
    $(document).on('change', '.bc-toggle-enabled', function () {
        var $chk = $(this);
        var id   = $chk.data('id');
        $.post(A.ajaxurl, {
            action:  'bc_toggle_message',
            post_id: id,
            nonce:   A.nonce
        }).done(function (res) {
            if (res.success) {
                $chk.closest('.bc-msg-card').toggleClass('is-inactive', !res.data.enabled);
            }
        });
    });

    /* Delete */
    $(document).on('click', '.bc-delete-btn', function () {
        var $btn = $(this);
        var id   = $btn.data('id');
        if (!confirm(A.confirm_del)) return;
        $.post(A.ajaxurl, {
            action:  'bc_delete_message',
            post_id: id,
            nonce:   A.nonce
        }).done(function (res) {
            if (res.success) {
                $btn.closest('.bc-msg-card').fadeOut(300, function () { $(this).remove(); });
            }
        });
    });

    /* Copy shortcode (list page) */
    $(document).on('click', '.bc-copy-btn', function () {
        var $btn = $(this);
        var sc   = $btn.data('sc');
        copyText(sc, $btn);
    });


    /* ══════════════════════════════════
       EDIT PAGE
    ══════════════════════════════════ */

    if ($('#bc-post-id').length) {
        initEditPage();
    }

    function initEditPage() {

        /* ── Colour pickers ── */
        $('.bc-color-picker').wpColorPicker({ change: debounce(updatePreview, 80), clear: debounce(updatePreview, 80) });

        /* ── Display-on toggle ── */
        $('#bc-display-on').on('change', function () {
            $('#bc-selected-wrap').toggle($(this).val() === 'selected');
        });

        /* ── Scroll speed ── */
        $('#bc-scroll-enabled').on('change', function () {
            $('#bc-speed-row').toggle(this.checked);
            updatePreview();
        });

        $('#bc-scroll-speed').on('input', function () {
            $('#bc-speed-val').text($(this).val() + ' px/s');
            updatePreview();
        });

        /* ── Position radio styling ── */
        $('[name="bc-position"]').on('change', function () {
            $('.bc-radio-card').removeClass('is-selected');
            $(this).closest('.bc-radio-card').addClass('is-selected');
            updatePreview();
        });

        /* ── Live preview on any input ── */
        $('#bc-settings-form, .bc-wrap').on('input change', 'input:not(.bc-color-picker), select, textarea', debounce(updatePreview, 120));

        /* ── Media uploader ── */
        var mediaFrame;
        $('#bc-upload-bg').on('click', function (e) {
            e.preventDefault();
            if (mediaFrame) { mediaFrame.open(); return; }
            mediaFrame = wp.media({ title: A.media_title, button: { text: A.media_button }, multiple: false, library: { type: 'image' } });
            mediaFrame.on('select', function () {
                var att = mediaFrame.state().get('selection').first().toJSON();
                $('#bc-bg-image').val(att.url);
                $('#bc-bg-image-id').val(att.id);
                $('#bc-bg-preview').attr('src', att.url).show();
                $('#bc-remove-bg').show();
                updatePreview();
            });
            mediaFrame.open();
        });

        $('#bc-remove-bg').on('click', function () {
            $('#bc-bg-image, #bc-bg-image-id').val('');
            $('#bc-bg-preview').hide();
            $(this).hide();
            updatePreview();
        });

        /* ── Save ── */
        $('#bc-save-btn').on('click', saveMessage);

        /* ── Copy shortcode in edit sidebar ── */
        $(document).on('click', '#bc-sc-copy', function () {
            copyText($(this).data('sc'), $(this));
        });

        /* Init preview */
        updatePreview();
    }

    /* ── Live preview update ──────────────────────────── */
    function updatePreview() {
        var $preview = $('#bc-live-preview');
        if (!$preview.length) return;

        var bgColor  = val('#bc-bg-color')    || '#1a73e8';
        var txtColor = val('#bc-font-color')  || '#ffffff';
        var fSize    = val('#bc-font-size')   || 15;
        var fFamily  = val('#bc-font-family') || 'inherit';
        var fWeight  = val('#bc-font-weight') || 'normal';
        var height   = val('#bc-bar-height')  || 44;
        var bgImage  = val('#bc-bg-image')    || '';
        var text     = val('#bc-text')        || 'Your message preview…';
        var scroll   = $('#bc-scroll-enabled').is(':checked');

        /* Apply styles */
        $preview.css({
            background: bgImage ? 'url(' + bgImage + ') center/cover no-repeat' : bgColor,
            color:       txtColor,
            fontSize:    fSize + 'px',
            fontFamily:  fFamily !== 'inherit' ? fFamily : '',
            fontWeight:  fWeight,
            minHeight:   height + 'px',
        });

        $preview.find('.bc-close').css('color', txtColor);

        /* Text */
        var plain = stripHtml(text) || 'Your message preview…';
        $preview.find('.bc-ticker-inner').html(
            '<span class="bc-message-item">' + plain + '</span>' +
            '<span class="bc-message-item">' + plain + '</span>'
        );

        /* Scroll vs static */
        $preview.toggleClass('bc-scroll', scroll).toggleClass('bc-static', !scroll);

        /* Restart admin ticker animation */
        var $inner = $preview.find('.bc-ticker-inner');
        $inner.css('animation', 'none');
        if (scroll) {
            setTimeout(function () {
                var speed   = parseFloat(val('#bc-scroll-speed')) || 50;
                var wrapW   = $preview.find('.bc-ticker').width() || 300;
                var textW   = $inner[0].scrollWidth / 2 || wrapW;
                var dur     = textW / speed;
                $inner.css('animation', 'bc-admin-preview-scroll ' + dur + 's linear infinite');
            }, 50);
        }
    }

    /* Inject preview keyframe once */
    if (!document.getElementById('bc-preview-kf')) {
        var style = document.createElement('style');
        style.id = 'bc-preview-kf';
        style.textContent = '@keyframes bc-admin-preview-scroll { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }';
        document.head.appendChild(style);
    }

    /* ── Save message ──────────────────────────────────── */
    function saveMessage() {
        var $btn = $('#bc-save-btn').prop('disabled', true).text('Saving…');
        var data = collectData();

        $.post(A.ajaxurl, {
            action: 'bc_save_message',
            nonce:  A.nonce,
            data:   data
        })
        .done(function (res) {
            if (res.success) {
                feedback(res.data.message, false);
                /* Update post_id if new */
                if (!$('#bc-post-id').val()) {
                    $('#bc-post-id').val(res.data.post_id);
                }
                /* Show shortcode */
                var sc = res.data.shortcode;
                var $scArea = $('#bc-shortcode-display');
                if ($scArea.length) {
                    $scArea.text(sc);
                    $scArea.siblings('.bc-copy-btn').data('sc', sc);
                } else {
                    $('#bc-save-btn').closest('.bc-save-row').after(
                        '<div class="bc-sc-row" style="margin-top:12px;">' +
                        '<code class="bc-sc-code" id="bc-shortcode-display">' + sc + '</code>' +
                        '<button class="bc-copy-btn" data-sc="' + esc(sc) + '" id="bc-sc-copy"><span class="dashicons dashicons-clipboard"></span></button>' +
                        '</div>'
                    );
                }
            } else {
                feedback('Error saving', true);
            }
        })
        .fail(function () { feedback('Server error', true); })
        .always(function () { $btn.prop('disabled', false).text('💾 Save Message'); });
    }

    function collectData() {
        var selected = [];
        $('.bc-selected-post:checked').each(function () { selected.push($(this).val()); });

        return {
            post_id:        val('#bc-post-id'),
            title:          val('#bc-title')         || 'Notice Message',
            text:           val('#bc-text'),
            enabled:        $('#bc-enabled').is(':checked')       ? 1 : undefined,
            position:       $('[name="bc-position"]:checked').val() || 'top',
            display_on:     val('#bc-display-on'),
            selected_posts: selected,
            close_button:   $('#bc-close-button').is(':checked')  ? 1 : undefined,
            link_url:       val('#bc-link-url'),
            link_target:    $('#bc-link-target').is(':checked') ? '_blank' : '_self',
            scroll_enabled: $('#bc-scroll-enabled').is(':checked') ? 1 : undefined,
            scroll_speed:   val('#bc-scroll-speed'),
            bg_color:       val('#bc-bg-color'),
            bg_image:       val('#bc-bg-image'),
            bg_image_id:    val('#bc-bg-image-id'),
            font_color:     val('#bc-font-color'),
            font_size:      val('#bc-font-size'),
            font_family:    val('#bc-font-family'),
            font_weight:    val('#bc-font-weight'),
            bar_height:     val('#bc-bar-height'),
            padding_x:      val('#bc-padding-x'),
            border_width:   val('#bc-border-width'),
            border_color:   val('#bc-border-color'),
            custom_css:     val('#bc-custom-css'),
        };
    }

    /* ── Helpers ─────────────────────────────────────── */
    function val(sel) { return $(sel).val() || ''; }

    function stripHtml(html) {
        var d = document.createElement('div');
        d.innerHTML = html;
        return d.textContent || d.innerText || '';
    }

    function feedback(msg, isError) {
        var $el = $('#bc-save-feedback').removeClass('is-error').text(msg);
        if (isError) $el.addClass('is-error');
        setTimeout(function () { $el.text(''); }, 3500);
    }

    function copyText(text, $trigger) {
        if (!text) return;
        navigator.clipboard.writeText(text).then(function () {
            var $icon = $trigger.find('.dashicons');
            $icon.removeClass('dashicons-clipboard').addClass('dashicons-yes');
            setTimeout(function () {
                $icon.removeClass('dashicons-yes').addClass('dashicons-clipboard');
            }, 1500);
        });
    }

    function esc(str) {
        return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;');
    }

    function debounce(fn, ms) {
        var t;
        return function () {
            clearTimeout(t);
            t = setTimeout(fn, ms);
        };
    }

}(jQuery));
