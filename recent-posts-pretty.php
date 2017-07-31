<?php
/**
 * Plugin Name: Widget - Recent Posts (Pretty)
 * Author: Mike W. Leavitt
 * Version: 0.1.0
 * Description: A prettier version of the Recent Posts widget, which shows more information on the most recent post and allows the user to display a specific post type.
 */


// Regsiter the widget.
function cah_load_widget_pretty_recent() {

    register_widget( 'pretty_recent_widget' );
}
add_action( 'widgets_init', 'cah_load_widget_pretty_recent' );


function cah_load_widget_scripts() {

    wp_enqueue_style( 'widget_recent_pretty_css', plugin_dir_url( __FILE__ ) . 'css/recent-pretty-style.css' );
}
add_action( 'wp_enqueue_scripts', 'cah_load_widget_scripts' );


// Class declaration.
class pretty_recent_widget extends WP_Widget {

    // Constructor
    function __construct() {
        parent::__construct(
            'pretty_recent_widget',
            __( 'Recent Posts (Pretty)', 'recent-pretty' ),
            array( 'description' => __( 'A prettier version of the Recent Posts widget, which shows more information on the most recent post and allows the user to display a specific post type.', 'recent-pretty' ) )
        );
    }

    // Front end
    public function widget( $args, $instance ) {

        $title = apply_filters( 'widget_title', $instance['title'] );

        echo $args['before_widget'];
        if ( !empty( $title ) )
            echo $args['before_title'] . $title . $args['after_title'];

        // Arguments for the custom query.
        $q_args = array(
            'post_type' => $instance['post_type'],
            'post_status' => 'publish',
            'posts_per_page' => $instance['per_page'],
        );

        if ( !empty( $instance['category'] ) )
            $q_args['category_name'] = $instance['category'];

        $query = new WP_Query( $q_args );

        if ( $query->have_posts() ) {

            $count = 0;
            while( $query->have_posts() ) {

                $query->the_post();
                $id = get_the_ID();
                $post_title = get_the_title();
                $date = get_the_date();
                $categories = get_the_category();
                $permalink = get_the_permalink();
                $excerpt = get_the_excerpt();

                if ( $instance['post_type'] == 'article' ) {

                    $author_last = get_post_meta( $id, 'author1-last', true );
                    $author_first = get_post_meta( $id, 'author1-first', true );
                    $other_authors = get_post_meta( $id, 'other-authors', true );
                } // End if

                $other_auth_string = '';

                if ( !empty( $other_authors ) ) {

                    $other_arr = explode( ',', $other_authors );

                    if ( count( $other_arr ) > 1 ) {

                        for ( $i = 0; $i < count( $other_arr); $i++ ) {

                            if ( $i + 1 == count( $other_arr ) )
                                $other_auth_string .= ', and ';
                            else
                                $other_auth_string .= ', ';

                            $other_auth_string .= trim( $other_arr[$i] );
                        } // End for
                    } else {

                        $other_auth_string .= ' and ' . $other_arr[0];
                    } // End if
                } // End if

                $authors = '';
                $authors .= ( !empty( $author_first ) ) ? $author_first . ' ' : '';
                $authors .= $author_last . $other_auth_string;

                $post_category = [];
                foreach ($categories as $item) {

                    if ( !empty( $instance['category'] ) && strcasecmp( $instance['category'], $item->name ) == 0 )
                        continue;

                    if ( $item->name == 'Literary Features' && strcasecmp( $instance['category'], $item->name ) != 0)
                        continue;

                    array_push( $post_category, $item->name );
                }

                $post_cat_out = '';
                foreach ($post_category as $item) {
                    $post_cat_out .= $item;

                    if (next($post_category) !== false)
                        $post_cat_out .= ', ';
                }

                if ( $count == 0 ) {

                    if(kdmfi_has_featured_image("author-image", $id) && !has_post_thumbnail())
                        $thumbnail = kdmfi_get_featured_image_src( "author-image", "small", $id );

                    else if(has_post_thumbnail())
                        $thumbnail = get_the_post_thumbnail_url($id);

                    else
                        $thumbnail = get_stylesheet_directory_uri() . "/public/images/empty.png";

                    ?>
                        <div class="widget-row widget-first-item">
                            <a href="<?= $permalink ?>">
                                <div class="post-image" style="background-image: url('<?= $thumbnail ?>')"></div>
                                <div class="post-data">
                                    <h4><?= $post_title ?></h4>
                                    <p><?= $authors ?><span style="float: right;"><em><?= $post_cat_out ?></em></span></p>
                                    <p><?= $excerpt ?></p>
                                </div>
                            </a>
                        </div>
                    <?

                    $count++;
                } else {

                    ?>
                        <div class="widget-row">
                            <a href="<?= $permalink ?>">
                                <div class="post-data">
                                    <h4><?= $post_title ?></h4>
                                    <p><?= $authors ?><span style="float: right;"><em><?= $post_cat_out ?></em></span></p>
                                </div>
                            </a>
                        </div>
                    <?
                } // End if
            } // End while

            wp_reset_postdata();
        } // End if

        echo $args['after_widget'];
    } // End widget()


    // Back end
    public function form( $instance ) {

        if ( isset( $instance['title'] ) ) {

            $title = $instance['title'];
        } else {

            $title = __( 'New Title', 'recent-pretty' );
        } // End if

        ?>
            <p>
                <label for="<?= $this->get_field_id( 'title' ); ?>"><?= _e( 'Title: ' ); ?></label>
                <input
                    class="widefat"
                    id="<?= $this->get_field_id( 'title' ); ?>"
                    name="<?= $this->get_field_name( 'title' ); ?>"
                    type="text"
                    value="<?= esc_attr( $title ) ?>"
                >
            </p>
        <?

        if ( isset( $instance['post_type'] ) ) {

            $type = $instance['post_type'];

        } else {

            $type = 'none';
        } // End if

        $pt_args = array(
            'public' => true
        );

        $post_types = get_post_types( $pt_args, 'objects' );
        uasort( $post_types, function( $p1, $p2 ) {
            return strcasecmp( $p1->label, $p2->label );
        });

        ?>
            <p>
                <label for="<?= $this->get_field_id( 'post_type' ); ?>"><?= _e( 'Post Type: ' ); ?></label>
                <select
                    id="<?= $this->get_field_id( 'post_type' ); ?>"
                    name="<?= $this->get_field_name( 'post_type' ); ?>"
                    value="<?= esc_attr( $type ); ?>"
                >
                    <option id="type_none" value="none" <?= ( $type == 'none' ) ? 'selected' : '' ?>>-- Select a Post Type --</option>

                    <?
                        foreach ( $post_types as $pt ) {
                        ?>
                            <option value="<?= $pt->rewrite['slug'] ?>" <?= ( strcasecmp( $type, $pt->rewrite['slug'] ) == 0 ) ? 'selected' : '' ?>><?= $pt->label ?></option>
                        <?
                        } // End foreach
                    ?>
                </select>
            </p>
        <?

        if ( isset( $instance['category'] ) ) {

            $category = $instance['category'];

        } else {

            $category = 'none';
        } // End if

        $categories = get_categories( array(
            'orderby'   => 'name',
            'order'     => 'ASC'
        ));

        ?>
            <p>
                <label for="<?= $this->get_field_id( 'category' ); ?>"><?= _e( 'Category: ' ); ?></label>
                <select
                    id="<?= $this->get_field_id( 'category' ); ?>"
                    name="<?= $this->get_field_name( 'category' ); ?>"
                    value="<?= esc_attr( $category ); ?>"
                >
                    <option id="category_none" value="none" <?= ( $category == 'none' ) ? 'selected' : '' ?>>None</option>
                    <?
                        foreach ( $categories as $cat ) {
                        ?>
                            <option value="<?= esc_attr( $cat->slug ); ?>" <?= ( strcasecmp( $category, $cat->slug ) == 0 ) ? 'selected' : '' ?>><?= $cat->name ?></option>
                        <?
                        }
                    ?>
                </select>
            </p>
        <?

        if ( isset( $instance['per_page'] ) ) {

            $per_page = $instance['per_page'];

        } else {

            $per_page = 3;
        }

        ?>
            <p>
                <label for="<?= $this->get_field_id( 'per_page' ); ?>"><?= _e( 'Number of Posts: ' ); ?></label>
                <input
                    class="widefat"
                    id="<?= $this->get_field_id( 'per_page' ); ?>"
                    name="<?= $this->get_field_name( 'per_page' ); ?>"
                    type="number"
                    value="<?= $per_page ?>"
                >
            </p>
        <?
    } // End form()


    public function update( $new_instance, $old_instance ) {

        $instance = array();

        foreach ( $new_instance as $key => $value ) {

            $instance[$key] = $value;
        } // End if

        return $instance;
    } // End update()
}
?>
