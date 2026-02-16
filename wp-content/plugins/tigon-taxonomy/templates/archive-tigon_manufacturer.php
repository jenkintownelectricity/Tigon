<?php
/**
 * Template: Manufacturer Archive
 *
 * Displays a grid of all manufacturers with logos, descriptions,
 * and links to their individual profile pages.
 *
 * This template can be overridden by copying it to:
 * yourtheme/archive-tigon_manufacturer.php
 *
 * @package Tigon_Taxonomy
 */

get_header();
?>

<div id="primary" class="content-area tigon-manufacturer-archive">
    <main id="main" class="site-main" role="main">

        <header class="page-header tigon-archive-header">
            <h1 class="page-title">Our Manufacturers</h1>
            <p class="tigon-archive-description">
                Browse our complete lineup of golf cart and electric vehicle brands.
                Click any manufacturer to view their available models and inventory.
            </p>
        </header>

        <?php if ( have_posts() ) : ?>

            <div class="tigon-manufacturer-grid">

                <?php while ( have_posts() ) : the_post(); ?>

                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'tigon-manufacturer-card' ); ?>>

                        <?php
                        $brand_logo_id = get_post_meta( get_the_ID(), '_tigon_brand_logo', true );
                        if ( $brand_logo_id ) :
                            $logo_url = wp_get_attachment_image_url( $brand_logo_id, 'medium' );
                            if ( $logo_url ) :
                                ?>
                                <div class="tigon-card-logo">
                                    <a href="<?php the_permalink(); ?>">
                                        <img src="<?php echo esc_url( $logo_url ); ?>"
                                             alt="<?php echo esc_attr( get_the_title() ); ?> logo" />
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php elseif ( has_post_thumbnail() ) : ?>
                            <div class="tigon-card-logo">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail( 'medium' ); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <h2 class="tigon-card-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>

                        <?php
                        $tagline = get_post_meta( get_the_ID(), '_tigon_brand_tagline', true );
                        if ( $tagline ) :
                            ?>
                            <p class="tigon-card-tagline"><?php echo esc_html( $tagline ); ?></p>
                        <?php endif; ?>

                        <?php if ( has_excerpt() ) : ?>
                            <div class="tigon-card-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                        <?php endif; ?>

                        <?php
                        // Show model count from linked WC category.
                        $wc_cat_id = get_post_meta( get_the_ID(), '_tigon_wc_category_id', true );
                        if ( $wc_cat_id ) :
                            $child_models = get_terms( array(
                                'taxonomy'   => 'product_cat',
                                'parent'     => (int) $wc_cat_id,
                                'hide_empty' => false,
                                'fields'     => 'count',
                            ) );
                            $model_count = is_wp_error( $child_models ) ? 0 : count( $child_models );
                            if ( $model_count > 0 ) :
                                ?>
                                <p class="tigon-card-model-count">
                                    <?php echo (int) $model_count; ?> model<?php echo $model_count !== 1 ? 's' : ''; ?> available
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>

                        <a href="<?php the_permalink(); ?>" class="tigon-card-link button">
                            View Models
                        </a>

                    </article>

                <?php endwhile; ?>

            </div>

            <?php the_posts_pagination( array(
                'prev_text' => '&laquo; Previous',
                'next_text' => 'Next &raquo;',
            ) ); ?>

        <?php else : ?>

            <p class="tigon-no-manufacturers">No manufacturers found. Check back soon for our brand lineup.</p>

        <?php endif; ?>

    </main>
</div>

<style>
    .tigon-archive-header {
        text-align: center;
        padding: 30px 20px;
    }
    .tigon-archive-description {
        color: #555;
        max-width: 600px;
        margin: 10px auto 0;
    }
    .tigon-manufacturer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
        padding: 0 20px 40px;
        max-width: 1200px;
        margin: 0 auto;
    }
    .tigon-manufacturer-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 24px;
        text-align: center;
        transition: box-shadow 0.2s, transform 0.2s;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .tigon-manufacturer-card:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .tigon-card-logo {
        margin-bottom: 16px;
    }
    .tigon-card-logo img {
        max-width: 150px;
        max-height: 100px;
        height: auto;
        object-fit: contain;
    }
    .tigon-card-title a {
        text-decoration: none;
        color: #1e1e1e;
        font-size: 1.3em;
    }
    .tigon-card-title a:hover {
        color: #2271b1;
    }
    .tigon-card-tagline {
        font-style: italic;
        color: #555;
        font-size: 0.9em;
        margin-top: 4px;
    }
    .tigon-card-excerpt {
        color: #444;
        font-size: 0.95em;
        margin: 8px 0;
    }
    .tigon-card-model-count {
        font-size: 0.85em;
        color: #757575;
        margin-bottom: 12px;
    }
    .tigon-card-link {
        margin-top: auto;
    }
    .tigon-no-manufacturers {
        text-align: center;
        padding: 40px 20px;
        color: #555;
    }
</style>

<?php
get_footer();
