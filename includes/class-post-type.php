<?php
class Doc_Publisher_Post_Type {
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_filter('manage_documento_posts_columns', array($this, 'add_columns'));
        add_action('manage_documento_posts_custom_column', array($this, 'populate_columns'), 10, 2);
        add_filter('manage_edit-documento_sortable_columns', array($this, 'make_columns_sortable'));
    }

    public function register_post_type() {
        $args = array(
            'public' => true,
            'label'  => 'Documenti',
            'supports' => array('title'),
            'show_in_rest' => true,
            'show_in_menu' => false,
            'menu_icon' => 'dashicons-media-document'
        );
        register_post_type('documento', $args);
    }

    public function add_columns($columns) {
        $new_columns = array();
        foreach($columns as $key => $title) {
            if ($key === 'date') {
                $new_columns['target_page'] = 'Pagina';
                $new_columns['order'] = 'Ordinamento';
            }
            $new_columns[$key] = $title;
        }
        return $new_columns;
    }

    public function populate_columns($column, $post_id) {
        switch ($column) {
            case 'target_page':
                $page_id = get_post_meta($post_id, '_target_page', true);
                $page = get_post($page_id);
                echo $page ? esc_html($page->post_title) : '';
                break;
            case 'order':
                echo get_post_meta($post_id, '_document_order', true);
                break;
        }
    }

    public function make_columns_sortable($columns) {
        $columns['target_page'] = 'target_page';
        $columns['order'] = 'order';
        return $columns;
    }
}