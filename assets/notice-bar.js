/* ════════════════════════════════════════
   Notice Bar Pro – Frontend JS
════════════════════════════════════════ */
(function ($) {
    'use strict';

    var COOKIE_PREFIX = 'bc_closed_';

    /* ── Init all bars on the page ─────── */
    function init() {
        $('.notice-bar').each(function () {
            var $bar = $(this);
            var id   = $bar.data('id');

            /* Check dismiss cookie */
            if (id && getCookie(COOKIE_PREFIX + id) === '1') {
                $bar.addClass('nb-hidden');
                return;
            }

            /* Body padding for fixed bars */
            applyBodyPadding($bar);

            /* Start RAF ticker if scrolling */
            if ($bar.hasClass('nb-scroll')) {
                var $inner = $bar.find('.nb-ticker-inner');
                var speed  = parseFloat($inner.data('speed')) || 50;
                startTicker($inner[0], speed);
            }

            /* Close button */
            $bar.on('click', '.nb-close', function () {
                $bar.addClass('nb-hidden');
                if (id) setCookie(COOKIE_PREFIX + id, '1', 1);
                recalcBodyPadding();
            });
        });
    }

    /* ── Body padding ──────────────────── */
    function applyBodyPadding($bar) {
        var h = $bar.outerHeight() || 0;
        if ($bar.hasClass('nb-top')) {
            var cur = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nb-top-height')) || 0;
            // Stack multiple top bars
            document.documentElement.style.setProperty('--nb-top-height', (cur + h) + 'px');
            $('body').addClass('nb-has-top-bar');
        }
        if ($bar.hasClass('nb-bottom')) {
            var curB = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nb-bottom-height')) || 0;
            document.documentElement.style.setProperty('--nb-bottom-height', (curB + h) + 'px');
            $('body').addClass('nb-has-bottom-bar');
        }
    }

    function recalcBodyPadding() {
        /* Recalculate after a bar is closed */
        var topH = 0, botH = 0;
        $('.notice-bar:not(.nb-hidden)').each(function () {
            var $b = $(this);
            if ($b.hasClass('nb-top'))    topH += $b.outerHeight();
            if ($b.hasClass('nb-bottom')) botH += $b.outerHeight();
        });
        document.documentElement.style.setProperty('--nb-top-height',    topH + 'px');
        document.documentElement.style.setProperty('--nb-bottom-height', botH + 'px');
        if (!topH) $('body').removeClass('nb-has-top-bar');
        if (!botH) $('body').removeClass('nb-has-bottom-bar');
    }

    /* ── Seamless RAF ticker ────────────
     *
     * The ticker-inner holds TWO identical copies of the message.
     * We translate left until offset === copyWidth (one full copy),
     * then snap back to 0 — seamless loop.
     *
     *  [ copy A ][ copy B ]   ← inner (2× wider)
     *  [  viewport  ]
     *
     * When copy A fully exits left, copy B is already in view;
     * resetting to 0 makes copy A reappear on the right instantly.
     * ─────────────────────────────────── */
    function startTicker(innerEl, speed) {
        if (!innerEl) return;

        var offset    = 0;
        var paused    = false;
        var lastTime  = null;
        var copyWidth = 0;
        var rafId;

        /* Wait two frames for layout to settle before measuring */
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                /* scrollWidth = both copies; half = one copy */
                copyWidth = innerEl.scrollWidth / 2;
                if (copyWidth <= 0) return;

                function step(ts) {
                    if (!paused) {
                        if (lastTime !== null) {
                            var dt = (ts - lastTime) / 1000;   /* seconds */
                            offset += speed * dt;
                            if (offset >= copyWidth) offset -= copyWidth;   /* seamless reset */
                        }
                        lastTime = ts;
                        innerEl.style.transform = 'translateX(-' + offset.toFixed(2) + 'px)';
                    } else {
                        lastTime = null;   /* avoid jump when resumed */
                    }
                    rafId = requestAnimationFrame(step);
                }

                rafId = requestAnimationFrame(step);

                /* Pause on hover */
                innerEl.addEventListener('mouseenter', function () { paused = true; });
                innerEl.addEventListener('mouseleave', function () { paused = false; });
            });
        });
    }

    /* ── Cookie helpers ─────────────── */
    function setCookie(name, value, days) {
        var d = new Date();
        d.setTime(d.getTime() + days * 864e5);
        document.cookie = name + '=' + value + ';expires=' + d.toUTCString() + ';path=/';
    }

    function getCookie(name) {
        var m = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return m ? m[2] : '';
    }

    $(document).ready(init);

}(jQuery));
