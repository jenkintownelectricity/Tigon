/**
 * Tigon Golf Carts — Theme JavaScript
 *
 * Handles: Filter cascading, gallery interactions, mobile nav, AJAX inventory loading
 */

(function ($) {
    'use strict';

    const TigonTheme = {
        init() {
            this.initMobileNav();
            this.initFilterCascade();
            this.initGalleryViewer();
            this.initStickyHeader();
            this.initLazyLoad();
        },

        /* Mobile Navigation Toggle */
        initMobileNav() {
            const $toggle = $('.tigon-mobile-toggle');
            const $nav = $('.tigon-header__nav');
            $toggle.on('click', function () {
                $nav.toggleClass('tigon-header__nav--open');
                $(this).toggleClass('active');
            });
        },

        /* Cascading Taxonomy Filters — Model depends on Manufacturer */
        initFilterCascade() {
            const $mfgSelect = $('select[name="filter_manufacturer"]');
            const $modelSelect = $('select[name="filter_model"]');

            if (!$mfgSelect.length || !$modelSelect.length) return;

            // Store all model options
            const allModels = [];
            $modelSelect.find('option').each(function () {
                allModels.push({
                    value: $(this).val(),
                    text: $(this).text(),
                    manufacturer: $(this).data('manufacturer') || ''
                });
            });

            $mfgSelect.on('change', function () {
                const selected = $(this).val();
                $modelSelect.empty().append('<option value="">All Models</option>');

                allModels.forEach(function (opt) {
                    if (!opt.value) return;
                    if (!selected || opt.manufacturer === selected || !opt.manufacturer) {
                        $modelSelect.append(
                            $('<option>').val(opt.value).text(opt.text)
                        );
                    }
                });
            });
        },

        /* Simple Gallery Viewer for Single Product */
        initGalleryViewer() {
            const $mainImg = $('.tigon-single-cart__main-image img');
            if (!$mainImg.length) return;

            $(document).on('click', '.tigon-single-cart .tigon-cart-thumb, [data-gallery-thumb]', function () {
                const fullSrc = $(this).find('img').attr('src') ||
                    $(this).attr('data-full-src') ||
                    $(this).find('img').data('src');
                if (fullSrc) {
                    $mainImg.attr('src', fullSrc);
                }
            });
        },

        /* Sticky Header Scroll Effect */
        initStickyHeader() {
            const $header = $('.tigon-header');
            if (!$header.length) return;

            let lastScroll = 0;
            $(window).on('scroll', function () {
                const scrollTop = $(window).scrollTop();
                if (scrollTop > 100) {
                    $header.addClass('tigon-header--scrolled');
                } else {
                    $header.removeClass('tigon-header--scrolled');
                }
                lastScroll = scrollTop;
            });
        },

        /* Lazy Load Images */
        initLazyLoad() {
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                            }
                            observer.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(function (img) {
                    observer.observe(img);
                });
            }
        }
    };

    $(document).ready(function () {
        TigonTheme.init();
    });

})(jQuery);
