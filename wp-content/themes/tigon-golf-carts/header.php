<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tigon Golf Carts — Premium Electric Golf Carts, Street Legal Vehicles & Low-Speed Transportation. New, Used, Sales, Service & Rentals.">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="tigon-header" role="banner">
    <div class="tigon-header__inner">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="tigon-header__logo" aria-label="Tigon Golf Carts Home">
            <?php if (has_custom_logo()) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <span style="color:#c8a84e;font-family:var(--tigon-font-heading);font-size:1.5rem;font-weight:800;">TIGON</span>
            <?php endif; ?>
        </a>

        <nav class="tigon-header__nav" role="navigation" aria-label="Primary Navigation">
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'container'      => false,
                'fallback_cb'    => function () {
                    echo '<ul>';
                    echo '<li><a href="' . esc_url(home_url('/')) . '">Home</a></li>';
                    echo '<li><a href="' . esc_url(home_url('/shop/')) . '">Inventory</a></li>';
                    echo '<li><a href="' . esc_url(home_url('/brands-manufacturers/')) . '">Brands</a></li>';
                    echo '<li><a href="' . esc_url(home_url('/golf-cart-services/')) . '">Services</a></li>';
                    echo '<li><a href="' . esc_url(home_url('/about-tigon-golf-carts/')) . '">About</a></li>';
                    echo '<li><a href="' . esc_url(home_url('/contact/')) . '">Contact</a></li>';
                    echo '</ul>';
                },
            ]);
            ?>
        </nav>

        <a href="tel:1-844-844-6638" class="tigon-header__phone">1-844-844-6638</a>
    </div>
</header>

<main id="main-content" class="tigon-main">
