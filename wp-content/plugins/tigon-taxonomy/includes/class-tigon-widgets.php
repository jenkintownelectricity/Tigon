<?php
/**
 * Tigon Widgets — Manufacturer/Model filter widget.
 *
 * Provides an accordion-style sidebar widget that displays manufacturers
 * with expandable model lists. Clicking a model filters the product archive.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Widgets {

    /**
     * Register all widgets.
     */
    public static function register_widgets() {
        register_widget( 'Tigon_Manufacturer_Filter_Widget' );
    }
}

/**
 * Manufacturer/Model Filter Widget.
 *
 * Displays manufacturers as expandable accordion items with model links.
 * Shows product counts per manufacturer and model.
 */
class Tigon_Manufacturer_Filter_Widget extends WP_Widget {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(
            'tigon_manufacturer_filter',
            'Tigon: Manufacturer & Model Filter',
            array(
                'description' => 'Accordion filter listing manufacturers and their models with product counts.',
                'classname'   => 'tigon-manufacturer-filter-widget',
            )
        );
    }

    /**
     * Front-end widget output.
     *
     * @param array $args     Widget args (before_widget, after_widget, etc.).
     * @param array $instance Widget instance settings.
     */
    public function widget( $args, $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : 'Browse by Manufacturer';
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

        // Get top-level manufacturer categories (parent = 0) that have products.
        $manufacturers = get_terms( array(
            'taxonomy'   => 'product_cat',
            'parent'     => 0,
            'hide_empty' => ! empty( $instance['hide_empty'] ),
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );

        if ( is_wp_error( $manufacturers ) || empty( $manufacturers ) ) {
            return;
        }

        // Determine the currently active category for highlighting.
        $current_cat_id = 0;
        if ( is_tax( 'product_cat' ) ) {
            $queried = get_queried_object();
            if ( $queried ) {
                $current_cat_id = $queried->term_id;
            }
        }

        echo $args['before_widget'];

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        echo '<div class="tigon-filter-accordion">';

        foreach ( $manufacturers as $manufacturer ) {
            $models = get_terms( array(
                'taxonomy'   => 'product_cat',
                'parent'     => $manufacturer->term_id,
                'hide_empty' => ! empty( $instance['hide_empty'] ),
                'orderby'    => 'name',
                'order'      => 'ASC',
            ) );

            $is_active = ( $current_cat_id === $manufacturer->term_id );
            if ( ! $is_active && ! is_wp_error( $models ) && ! empty( $models ) ) {
                foreach ( $models as $model ) {
                    if ( $current_cat_id === $model->term_id ) {
                        $is_active = true;
                        break;
                    }
                }
            }

            $active_class  = $is_active ? ' tigon-accordion-active' : '';
            $expanded_attr = $is_active ? 'true' : 'false';
            $mfr_link      = get_term_link( $manufacturer );
            $mfr_count     = $manufacturer->count;
            ?>
            <div class="tigon-accordion-item<?php echo esc_attr( $active_class ); ?>">
                <div class="tigon-accordion-header" aria-expanded="<?php echo esc_attr( $expanded_attr ); ?>" role="button" tabindex="0">
                    <a href="<?php echo esc_url( $mfr_link ); ?>" class="tigon-manufacturer-link">
                        <?php echo esc_html( $manufacturer->name ); ?>
                    </a>
                    <span class="tigon-count">(<?php echo (int) $mfr_count; ?>)</span>
                    <?php if ( ! is_wp_error( $models ) && ! empty( $models ) ) : ?>
                        <span class="tigon-accordion-toggle" aria-hidden="true">&#9660;</span>
                    <?php endif; ?>
                </div>
                <?php if ( ! is_wp_error( $models ) && ! empty( $models ) ) : ?>
                    <ul class="tigon-accordion-panel" <?php echo $is_active ? '' : 'style="display:none;"'; ?>>
                        <?php foreach ( $models as $model ) :
                            $model_link    = get_term_link( $model );
                            $model_active  = ( $current_cat_id === $model->term_id ) ? ' class="tigon-model-active"' : '';
                            ?>
                            <li<?php echo $model_active; ?>>
                                <a href="<?php echo esc_url( $model_link ); ?>">
                                    <?php echo esc_html( $model->name ); ?>
                                </a>
                                <span class="tigon-count">(<?php echo (int) $model->count; ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <?php
        }

        echo '</div>';
        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @param array $instance Current settings.
     * @return void
     */
    public function form( $instance ) {
        $title      = isset( $instance['title'] ) ? $instance['title'] : 'Browse by Manufacturer';
        $hide_empty = isset( $instance['hide_empty'] ) ? (bool) $instance['hide_empty'] : true;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Title:</label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <input class="checkbox" type="checkbox"
                   id="<?php echo esc_attr( $this->get_field_id( 'hide_empty' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'hide_empty' ) ); ?>"
                   <?php checked( $hide_empty ); ?> />
            <label for="<?php echo esc_attr( $this->get_field_id( 'hide_empty' ) ); ?>">
                Hide empty categories
            </label>
        </p>
        <?php
    }

    /**
     * Save widget settings.
     *
     * @param array $new_instance New settings.
     * @param array $old_instance Previous settings.
     * @return array Sanitized settings.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title']      = sanitize_text_field( $new_instance['title'] );
        $instance['hide_empty'] = ! empty( $new_instance['hide_empty'] );
        return $instance;
    }
}
