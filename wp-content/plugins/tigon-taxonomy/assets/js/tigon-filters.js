/**
 * Tigon Filters — Accordion toggle and AJAX product filtering support.
 *
 * @package Tigon_Taxonomy
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        initAccordion();
    });

    /**
     * Initialize accordion toggle behavior for manufacturer/model filter.
     */
    function initAccordion() {
        var $accordion = $('.tigon-filter-accordion');

        if (!$accordion.length) {
            return;
        }

        // Toggle panel on header click (but not on the manufacturer link itself).
        $accordion.on('click', '.tigon-accordion-toggle', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var $item = $(this).closest('.tigon-accordion-item');
            togglePanel($item);
        });

        // Also toggle on header click (excluding link clicks).
        $accordion.on('click', '.tigon-accordion-header', function (e) {
            // If the user clicked the manufacturer link, let it navigate.
            if ($(e.target).hasClass('tigon-manufacturer-link') || $(e.target).closest('.tigon-manufacturer-link').length) {
                return;
            }

            e.preventDefault();
            var $item = $(this).closest('.tigon-accordion-item');
            togglePanel($item);
        });

        // Keyboard accessibility: Enter/Space toggles accordion.
        $accordion.on('keydown', '.tigon-accordion-header', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                // Only toggle if not focused on the link.
                if (!$(e.target).hasClass('tigon-manufacturer-link')) {
                    e.preventDefault();
                    var $item = $(this).closest('.tigon-accordion-item');
                    togglePanel($item);
                }
            }
        });
    }

    /**
     * Toggle an accordion panel open/closed.
     *
     * @param {jQuery} $item The accordion item to toggle.
     */
    function togglePanel($item) {
        var $panel  = $item.find('.tigon-accordion-panel');
        var $header = $item.find('.tigon-accordion-header');

        if (!$panel.length) {
            return;
        }

        var isExpanded = $header.attr('aria-expanded') === 'true';

        if (isExpanded) {
            $panel.slideUp(200);
            $header.attr('aria-expanded', 'false');
            $item.removeClass('tigon-accordion-active');
        } else {
            $panel.slideDown(200);
            $header.attr('aria-expanded', 'true');
            $item.addClass('tigon-accordion-active');
        }
    }

})(jQuery);
