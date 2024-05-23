<?php
/**
 * Plugin Name: Image Link Collector
 * Description: Collects and manages image links from all posts.
 * Version: 1.0
 * Author: BIHV
 */

// Enqueue Bootstrap, Magnific Popup CSS and JS
function ilc_enqueue_admin_assets() {
    wp_enqueue_style('bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
    wp_enqueue_script('jquery');
    wp_enqueue_script('popper-js', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js', array('jquery'), null, true);
    wp_enqueue_script('bootstrap-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery', 'popper-js'), null, true);
    wp_enqueue_style('ilc-admin-styles', plugin_dir_url(__FILE__) . 'css/admin-styles.css');
}
add_action('admin_enqueue_scripts', 'ilc_enqueue_admin_assets');

// Render admin page
function ilc_render_admin_page() {
    require_once plugin_dir_path( __FILE__ ) . 'class-ilc-list-table.php';
    $ilc_list_table = new ILC_List_Table();
    $ilc_list_table->prepare_items();
    ?>
    <div class="wrap">
        <h1>Image Link Collector</h1>
        <form method="post">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <?php $ilc_list_table->search_box('search', 'search_id'); ?>
            <?php $ilc_list_table->display(); ?>
        </form>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="ilc_collect_image_links">
            <?php wp_nonce_field('ilc_collect_image_links'); ?>
            <button type="submit" class="btn btn-success">Collect Image Links</button>
        </form>
    </div>
    <script>
        jQuery(document).ready(function($) {
            // Custom tooltip for image thumbnails
            $('.image-link').hover(function() {
                var thumbnailUrl = $(this).data('thumbnail');
                $(this).tooltip({
                    html: true,
                    title: '<img src="' + thumbnailUrl + '" alt="Thumbnail" class="img-thumbnail">',
                    container: 'body',
                    trigger: 'hover'
                });
                $(this).tooltip('show');
            }, function() {
                $(this).tooltip('hide');
            });
        });
    </script>
    <?php
}

// Add admin menu
function ilc_admin_menu() {
    add_menu_page('Image Link Collector', 'Image Link Collector', 'manage_options', 'ilc_admin_page', 'ilc_render_admin_page', 'dashicons-images-alt2');
}
add_action('admin_menu', 'ilc_admin_menu');

// Handle collect image links action
function ilc_collect_image_links_action() {
    if (!current_user_can('manage_options')) {
        return;
    }
    if (!wp_verify_nonce($_POST['_wpnonce'], 'ilc_collect_image_links')) {
        return;
    }
    ilc_collect_image_links();
    wp_redirect(admin_url('admin.php?page=ilc_admin_page'));
    exit;
}
add_action('admin_post_ilc_collect_image_links', 'ilc_collect_image_links_action');

// Collect image links from all posts
function ilc_collect_image_links() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_links';
    $wpdb->query("TRUNCATE TABLE $table_name");

    $args = array(
        'post_type' => 'post',
        'posts_per_page' => -1
    );
    $posts = get_posts($args);

    foreach ($posts as $post) {
        if (preg_match_all('/<img.*?src=["\'](.*?)["\']/', $post->post_content, $matches)) {
            foreach ($matches[1] as $image_url) {
                $wpdb->insert($table_name, array(
                    'post_link' => get_permalink($post->ID),
                    'image_link' => $image_url
                ));
            }
        }
    }
}

// Create database table on plugin activation
function ilc_create_db_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_links';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_link text NOT NULL,
        image_link text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'ilc_create_db_table');
?>