<?php
/**
 * Template: Single Manufacturer
 *
 * Displays a manufacturer profile page with brand info, logo, description,
 * and a grid of products from that manufacturer's WooCommerce category.
 *
 * This template can be overridden by copying it to:
 * yourtheme/single-tigon_manufacturer.php
 *
 * @package Tigon_Taxonomy
 */

get_header();
?>

<div id="primary" class="content-area tigon-manufacturer-single">
    <main id="main" class="site-main" role="main">

        <?php while ( have_posts() ) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class( 'tigon-manufacturer-article' ); ?>>

                <header class="tigon-manufacturer-header">
                    <?php
                    // Brand logo.
                    $brand_logo_id = get_post_meta( get_the_ID(), '_tigon_brand_logo', true );
                    if ( $brand_logo_id ) :
                        $logo_url = wp_get_attachment_image_url( $brand_logo_id, 'medium' );
                        if ( $logo_url ) :
                            ?>
                            <div class="tigon-brand-logo">
                                <img src="<?php echo esc_url( $logo_url ); ?>"
                                     alt="<?php echo esc_attr( get_the_title() ); ?> logo" />
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <h1 class="tigon-manufacturer-title"><?php the_title(); ?></h1>

                    <?php
                    $tagline = get_post_meta( get_the_ID(), '_tigon_brand_tagline', true );
                    if ( $tagline ) :
                        ?>
                        <p class="tigon-brand-tagline"><?php echo esc_html( $tagline ); ?></p>
                    <?php endif; ?>

                    <?php
                    $brand_url = get_post_meta( get_the_ID(), '_tigon_brand_url', true );
                    if ( $brand_url ) :
                        ?>
                        <p class="tigon-brand-url">
                            <a href="<?php echo esc_url( $brand_url ); ?>" target="_blank" rel="noopener noreferrer">
                                Visit Official Website
                            </a>
                        </p>
                    <?php endif; ?>
                </header>

                <div class="tigon-manufacturer-content entry-content">
                    <?php the_content(); ?>
                </div>

                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="tigon-manufacturer-featured-image">
                        <?php the_post_thumbnail( 'large' ); ?>
                    </div>
                <?php endif; ?>

                <?php
                // Display products from the linked WooCommerce category.
                $wc_cat_id = get_post_meta( get_the_ID(), '_tigon_wc_category_id', true );
                if ( $wc_cat_id && class_exists( 'WooCommerce' ) ) :
                    $wc_term = get_term( (int) $wc_cat_id, 'product_cat' );
                    if ( $wc_term && ! is_wp_error( $wc_term ) ) :
                        ?>
                        <section class="tigon-manufacturer-products">
                            <h2>Available Models &amp; Inventory</h2>

                            <?php
                            // List child model categories.
                            $models = get_terms( array(
                                'taxonomy'   => 'product_cat',
                                'parent'     => $wc_term->term_id,
                                'hide_empty' => false,
                                'orderby'    => 'name',
                            ) );

                            if ( ! is_wp_error( $models ) && ! empty( $models ) ) :
                                ?>
                                <div class="tigon-model-grid">
                                    <?php foreach ( $models as $model ) : ?>
                                        <div class="tigon-model-card">
                                            <h3>
                                                <a href="<?php echo esc_url( get_term_link( $model ) ); ?>">
                                                    <?php echo esc_html( $model->name ); ?>
                                                </a>
                                            </h3>
                                            <?php if ( $model->description ) : ?>
                                                <p><?php echo esc_html( $model->description ); ?></p>
                                            <?php endif; ?>
                                            <span class="tigon-model-count">
                                                <?php echo (int) $model->count; ?> product<?php echo $model->count !== 1 ? 's' : ''; ?> available
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <p class="tigon-view-all">
                                <a href="<?php echo esc_url( get_term_link( $wc_term ) ); ?>" class="button">
                                    View All <?php echo esc_html( $wc_term->name ); ?> Products
                                </a>
                            </p>
                        </section>
                    <?php endif; ?>
                <?php endif; ?>

            </article>

        <?php endwhile; ?>

    </main>
</div>

<style>
    .tigon-manufacturer-header {
        text-align: center;
        padding: 30px 0;
    }
    .tigon-brand-logo {
        margin-bottom: 20px;
    }
    .tigon-brand-logo img {
        max-width: 200px;
        height: auto;
    }
    .tigon-brand-tagline {
        font-size: 1.2em;
        color: #555;
        font-style: italic;
    }
    .tigon-manufacturer-content {
        max-width: 800px;
        margin: 0 auto 30px;
        padding: 0 20px;
    }
    .tigon-manufacturer-products {
        padding: 30px 20px;
        max-width: 1200px;
        margin: 0 auto;
    }
    .tigon-manufacturer-products h2 {
        text-align: center;
        margin-bottom: 30px;
    }
    .tigon-model-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .tigon-model-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        transition: box-shadow 0.2s;
    }
    .tigon-model-card:hover {
        box-shadow: 0 2px 12px rgba(0,0,0,0.1);
    }
    .tigon-model-card h3 a {
        text-decoration: none;
        color: #1e1e1e;
    }
    .tigon-model-card h3 a:hover {
        color: #2271b1;
    }
    .tigon-model-count {
        font-size: 0.9em;
        color: #757575;
    }
    .tigon-view-all {
        text-align: center;
    }
</style>

<?php
get_footer();
