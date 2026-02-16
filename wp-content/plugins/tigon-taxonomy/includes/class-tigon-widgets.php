<?php
/**
 * Tigon Widgets — Manufacturer/Model filter widget for product archives.
 *
 * Displays an accordion-style list of manufacturers with expandable model lists.
 * Shows product counts per manufacturer and per model.
 * Clicking a model filters the archive to that category.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Widgets {

    /**
     * Register the widget with WordPress.
     */
    public static function register() {
        register_widget( 'Tigon_Manufacturer_Filter_Widget' );
    }
}

/**
 * Manufacturer/Model Filter Widget.
 */
class Tigon_Manufacturer_Filter_Widget extends WP_Widget {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(
            'tigon_manufacturer_filter',
            'Tigon Manufacturer/Model Filter',
            array(
                'description' => 'Filter products by manufacturer and model with expandable accordion.',
                'classname'   => 'widget_tigon_manufacturer_filter',
            )
        );
    }

    /**
     * Front-end display of the widget.
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : 'Browse by Manufacturer';
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

        // Get all top-level manufacturer categories.
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

        // Determine currently active category for highlighting.
        $current_cat_id = 0;
        if ( is_product_category() ) {
            $current_cat    = get_queried_object();
            $current_cat_id = $current_cat ? $current_cat->term_id : 0;
        }

        echo $args['before_widget'];

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        echo '<div class="tigon-filter-accordion">';

        foreach ( $manufacturers as $manufacturer ) {
            // Get child model categories.
            $models = get_terms( array(
                'taxonomy'   => 'product_cat',
                'parent'     => $manufacturer->term_id,
                'hide_empty' => ! empty( $instance['hide_empty'] ),
                'orderby'    => 'name',
                'order'      => 'ASC',
            ) );

            $is_active  = ( $current_cat_id === $manufacturer->term_id );
            $has_active_child = false;

            if ( ! is_wp_error( $models ) && ! empty( $models ) ) {
                foreach ( $models as $model ) {
                    if ( $current_cat_id === $model->term_id ) {
                        $has_active_child = true;
                        break;
                    }
                }
            }

            $expanded = $is_active || $has_active_child;

            $mfr_class = 'tigon-filter-manufacturer';
            if ( $is_active ) {
                $mfr_class .= ' tigon-active';
            }

            echo '<div class="' . esc_attr( $mfr_class ) . '">';

            // Manufacturer header (accordion toggle).
            echo '<div class="tigon-filter-header' . ( $expanded ? ' tigon-expanded' : '' ) . '" ';
            echo 'data-manufacturer="' . esc_attr( $manufacturer->slug ) . '">';
            echo '<a href="' . esc_url( get_term_link( $manufacturer ) ) . '" class="tigon-mfr-link">';
            echo esc_html( $manufacturer->name );
            echo '</a>';
            echo '<span class="tigon-count">(' . (int) $manufacturer->count . ')</span>';

            if ( ! is_wp_error( $models ) && ! empty( $models ) ) {
                echo '<span class="tigon-toggle">' . ( $expanded ? '&#9660;' : '&#9654;' ) . '</span>';
            }

            echo '</div>';

            // Model list (collapsible).
            if ( ! is_wp_error( $models ) && ! empty( $models ) ) {
                $style = $expanded ? '' : ' style="display:none;"';
                echo '<ul class="tigon-filter-models"' . $style . '>';

                foreach ( $models as $model ) {
                    $model_class = 'tigon-filter-model';
                    if ( $current_cat_id === $model->term_id ) {
                        $model_class .= ' tigon-active';
                    }

                    echo '<li class="' . esc_attr( $model_class ) . '">';
                    echo '<a href="' . esc_url( get_term_link( $model ) ) . '">';
                    echo esc_html( $model->name );
                    echo '</a>';
                    echo '<span class="tigon-count">(' . (int) $model->count . ')</span>';
                    echo '</li>';
                }

                echo '</ul>';
            }

            echo '</div>';
        }

        echo '</div>';

        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {
        $title      = ! empty( $instance['title'] ) ? $instance['title'] : 'Browse by Manufacturer';
        $hide_empty = ! empty( $instance['hide_empty'] ) ? (bool) $instance['hide_empty'] : false;
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
     * Sanitize widget form values as they are saved.
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     * @return array Updated safe values.
     */
    public function update( $new_instance, $old_instance ) {
        $instance               = array();
        $instance['title']      = sanitize_text_field( $new_instance['title'] );
        $instance['hide_empty'] = ! empty( $new_instance['hide_empty'] );

        return $instance;
    }
}
