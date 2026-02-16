/**
 * Tigon Taxonomy - Filter Widget JS
 *
 * Handles accordion toggle behavior for the manufacturer/model filter widget.
 *
 * @package Tigon_Taxonomy
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        initAccordion();
    });

    /**
     * Initialize accordion toggle for manufacturer filter widget.
     */
    function initAccordion() {
        var $widget = $('.tigon-filter-widget');
        if (!$widget.length) {
            return;
        }

        // Toggle model list visibility on button click.
        $widget.on('click', '.tigon-toggle', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var $button = $(this);
            var $manufacturer = $button.closest('.tigon-filter-manufacturer');
            var $models = $manufacturer.find('.tigon-filter-models');
            var isExpanded = $button.attr('aria-expanded') === 'true';

            if (isExpanded) {
                // Collapse.
                $models.slideUp(200);
                $button.attr('aria-expanded', 'false');
                $button.find('.tigon-toggle-icon').text('+');
                $manufacturer.removeClass('tigon-expanded');
            } else {
                // Expand.
                $models.slideDown(200);
                $button.attr('aria-expanded', 'true');
                $button.find('.tigon-toggle-icon').text('−');
                $manufacturer.addClass('tigon-expanded');
            }
        });
    }

})(jQuery);
