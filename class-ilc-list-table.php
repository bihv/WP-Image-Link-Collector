<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class ILC_List_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct(array(
            'singular' => __('Image Link', 'sp'),
            'plural' => __('Image Links', 'sp'),
            'ajax' => false
        ));
    }

    public static function get_image_links($per_page = 10, $page_number = 1) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'image_links';
        $sql = "SELECT * FROM $table_name";
        if (!empty($_REQUEST['s'])) {
            $sql .= ' WHERE post_link LIKE \'%' . esc_sql($_REQUEST['s']) . '%\'';
            $sql .= ' OR image_link LIKE \'%' . esc_sql($_REQUEST['s']) . '%\'';
        }
        $sql .= ' LIMIT ' . ($per_page * ($page_number - 1)) . ', ' . $per_page;
        return $wpdb->get_results($sql, 'ARRAY_A');
    }

    public static function record_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'image_links';
        $sql = "SELECT COUNT(*) FROM $table_name";
        return $wpdb->get_var($sql);
    }

    public function no_items() {
        _e('No image links found.', 'sp');
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'post_link':
                return '<a href="' . esc_url($item['post_link']) . '" target="_blank">' . esc_html($item['post_link']) . '</a>';
            case 'image_link':
                return '<a href="' . esc_url($item['image_link']) . '" target="_blank" class="image-link" data-thumbnail="' . esc_url($item['image_link']) . '">' . esc_html($item['image_link']) . '</a>';
            default:
                return print_r($item, true);
        }
    }

    function get_columns() {
        $columns = array(
            'post_link' => __('Post Link', 'sp'),
            'image_link' => __('Image Link', 'sp')
        );
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'post_link' => array('post_link', true),
            'image_link' => array('image_link', true)
        );
        return $sortable_columns;
    }

    function prepare_items() {
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
        $this->process_bulk_action();
		$per_page = $this->get_items_per_page('links_per_page', 10);
		$current_page = $this->get_pagenum();
        $total_items = self::record_count();
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
        $this->items = self::get_image_links($per_page, $current_page);
    }
}