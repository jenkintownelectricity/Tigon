<?php
/**
 * Manufacturer/Model Filter Widget.
 *
 * Displays manufacturers as expandable accordion items with nested
 * model links and product counts. Used on shop/archive sidebars.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Widgets {

    /**
     * Register the widget.
     */
    public static function register() {
        register_widget( 'Tigon_Manufacturer_Filter_Widget' );
    }
}

/**
 * Manufacturer/Model accordion filter widget for WooCommerce archives.
 */
class Tigon_Manufacturer_Filter_Widget extends WP_Widget {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(
            'tigon_manufacturer_filter',
            'Tigon: Manufacturer/Model Filter',
            array(
                'description' => 'Filter products by manufacturer and model with expandable accordion.',
                'classname'   => 'widget_tigon_manufacturer_filter',
            )
        );
    }

    /**
     * Front-end display.
     *
     * @param array $args     Widget args.
     * @param array $instance Widget instance.
     */
    public function widget( $args, $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : 'Filter by Brand';
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

        $show_counts = ! empty( $instance['show_counts'] );

        echo $args['before_widget'];

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $this->render_filter( $show_counts );

        echo $args['after_widget'];
    }

    /**
     * Render the accordion manufacturer/model filter.
     *
     * @param bool $show_counts Whether to show product counts.
     */
    private function render_filter( $show_counts = true ) {
        // Get all top-level (manufacturer) product categories.
        $manufacturers = get_terms( array(
            'taxonomy'   => 'product_cat',
            'parent'     => 0,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );

        if ( empty( $manufacturers ) || is_wp_error( $manufacturers ) ) {
            echo '<p>No manufacturers found.</p>';
            return;
        }

        // Determine currently active category for highlighting.
        $current_cat_id = 0;
        if ( is_product_category() ) {
            $queried = get_queried_object();
            if ( $queried ) {
                $current_cat_id = $queried->term_id;
            }
        }

        echo '<div class="tigon-filter-widget">';

        foreach ( $manufacturers as $manufacturer ) {
            // Get child (model) categories.
            $models = get_terms( array(
                'taxonomy'   => 'product_cat',
                'parent'     => $manufacturer->term_id,
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ) );

            $has_models    = ! empty( $models ) && ! is_wp_error( $models );
            $is_active     = ( $current_cat_id === $manufacturer->term_id );
            $child_active  = false;

            if ( $has_models ) {
                foreach ( $models as $model ) {
                    if ( $current_cat_id === $model->term_id ) {
                        $child_active = true;
                        break;
                    }
                }
            }

            $expanded = $is_active || $child_active;

            echo '<div class="tigon-filter-manufacturer' . ( $expanded ? ' tigon-expanded' : '' ) . '">';

            // Manufacturer header.
            echo '<div class="tigon-filter-header" data-manufacturer="' . esc_attr( $manufacturer->slug ) . '">';
            echo '<a href="' . esc_url( get_term_link( $manufacturer ) ) . '" class="tigon-manufacturer-link' . ( $is_active ? ' tigon-active' : '' ) . '">';
            echo esc_html( $manufacturer->name );
            if ( $show_counts ) {
                echo ' <span class="tigon-count">(' . (int) $manufacturer->count . ')</span>';
            }
            echo '</a>';

            if ( $has_models ) {
                echo '<button type="button" class="tigon-toggle" aria-expanded="' . ( $expanded ? 'true' : 'false' ) . '" aria-label="Toggle ' . esc_attr( $manufacturer->name ) . ' models">';
                echo '<span class="tigon-toggle-icon">' . ( $expanded ? '−' : '+' ) . '</span>';
                echo '</button>';
            }

            echo '</div>'; // .tigon-filter-header

            // Model list.
            if ( $has_models ) {
                echo '<ul class="tigon-filter-models"' . ( $expanded ? '' : ' style="display:none;"' ) . '>';
                foreach ( $models as $model ) {
                    $model_active = ( $current_cat_id === $model->term_id );
                    echo '<li class="tigon-filter-model">';
                    echo '<a href="' . esc_url( get_term_link( $model ) ) . '"' . ( $model_active ? ' class="tigon-active"' : '' ) . '>';
                    echo esc_html( $model->name );
                    if ( $show_counts ) {
                        echo ' <span class="tigon-count">(' . (int) $model->count . ')</span>';
                    }
                    echo '</a>';
                    echo '</li>';
                }
                echo '</ul>';
            }

            echo '</div>'; // .tigon-filter-manufacturer
        }

        echo '</div>'; // .tigon-filter-widget
    }

    /**
     * Back-end widget form.
     *
     * @param array $instance Current settings.
     * @return void
     */
    public function form( $instance ) {
        $title       = isset( $instance['title'] ) ? $instance['title'] : 'Filter by Brand';
        $show_counts = isset( $instance['show_counts'] ) ? (bool) $instance['show_counts'] : true;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Title:</label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <input class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_counts' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'show_counts' ) ); ?>"
                   type="checkbox" <?php checked( $show_counts ); ?> />
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_counts' ) ); ?>">Show product counts</label>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values.
     *
     * @param array $new_instance New values.
     * @param array $old_instance Old values.
     * @return array
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title']       = sanitize_text_field( $new_instance['title'] );
        $instance['show_counts'] = ! empty( $new_instance['show_counts'] );
        return $instance;
    }
}
