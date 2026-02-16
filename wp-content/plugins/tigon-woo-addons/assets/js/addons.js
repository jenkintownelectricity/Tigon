/**
 * Tigon WooCommerce Add-Ons — Frontend JavaScript
 * Handles dynamic pricing updates and add-on interactions
 */
(function($) {
    'use strict';

    const TigonAddons = {
        init() {
            this.$container = $('.tigon-product-addons');
            if (!this.$container.length) return;

            this.basePrice = parseFloat($('.woocommerce-Price-amount').first().text().replace(/[^0-9.]/g, '')) || 0;
            this.bindEvents();
            this.createTotalDisplay();
        },

        bindEvents() {
            this.$container.on('change', 'input[type="checkbox"]', () => this.updateTotal());
            this.$container.on('change', 'select', () => this.updateTotal());
            this.$container.on('change', 'input[type="radio"]', () => this.updateTotal());
            this.$container.on('input', 'input[type="number"]', () => this.updateTotal());
        },

        createTotalDisplay() {
            if (!this.$container.find('.tigon-addons-total').length) {
                this.$container.append(
                    '<div class="tigon-addons-total">' +
                    '<span>Add-ons Total:</span>' +
                    '<span class="tigon-addons-total__price">$0.00</span>' +
                    '</div>'
                );
            }
        },

        updateTotal() {
            let total = 0;

            // Checkboxes
            this.$container.find('input[type="checkbox"]:checked').each(function() {
                total += parseFloat($(this).data('price')) || 0;
            });

            // Selects
            this.$container.find('select').each(function() {
                const selected = $(this).find('option:selected');
                total += parseFloat(selected.data('price')) || 0;
            });

            // Radio buttons
            this.$container.find('input[type="radio"]:checked').each(function() {
                total += parseFloat($(this).data('price')) || 0;
            });

            // Number fields
            this.$container.find('input[type="number"]').each(function() {
                const qty = parseInt($(this).val()) || 0;
                const unitPrice = parseFloat($(this).data('price')) || 0;
                total += qty * unitPrice;
            });

            // Update display
            this.$container.find('.tigon-addons-total__price').text('$' + total.toFixed(2));

            // Trigger event for other scripts
            $(document).trigger('tigon_addons_total_changed', { total: total, basePrice: this.basePrice });
        }
    };

    $(document).ready(function() {
        TigonAddons.init();
    });

})(jQuery);
