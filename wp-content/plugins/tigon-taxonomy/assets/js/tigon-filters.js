/**
 * Tigon Filters — Manufacturer/Model filter widget accordion behavior.
 *
 * @package Tigon_Taxonomy
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Accordion toggle behavior.
        $('.tigon-filter-header').on('click', function(e) {
            // Don't toggle if clicking the manufacturer link directly.
            if ($(e.target).hasClass('tigon-mfr-link')) {
                return;
            }

            e.preventDefault();

            var $header = $(this);
            var $models = $header.next('.tigon-filter-models');
            var $toggle = $header.find('.tigon-toggle');

            if ($models.length === 0) {
                return;
            }

            $models.slideToggle(200, function() {
                if ($models.is(':visible')) {
                    $header.addClass('tigon-expanded');
                    $toggle.html('&#9660;');
                } else {
                    $header.removeClass('tigon-expanded');
                    $toggle.html('&#9654;');
                }
            });
        });

        // Allow clicking the toggle icon specifically.
        $('.tigon-toggle').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).closest('.tigon-filter-header').trigger('click');
        });
    });
})(jQuery);
