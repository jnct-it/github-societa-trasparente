<?php
class Doc_Publisher_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_documento', array($this, 'save_document_details'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_get_next_order', array($this, 'get_next_order_callback'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
        add_action('wp_ajax_delete_document_file', array($this, 'handle_document_file_deletion'));
        add_action('wp_ajax_publish_document', array($this, 'handle_document_publish'));
        add_action('init', array($this, 'remove_editor_from_document'), 100);
        add_action('wp_ajax_get_documents_for_page', array($this, 'get_documents_for_page_callback'));
        add_action('wp_ajax_update_documents_order', array($this, 'update_documents_order_callback'));
    }

    public function add_menu_pages() {
        add_menu_page(
            'Società Trasparente', // Changed title
            'Società Trasparente', // Changed menu text
            'edit_posts',
            'document-publisher',
            array($this, 'render_publisher_page'),
            'dashicons-media-document',
            20
        );
        
        add_submenu_page(
            'document-publisher',
            'Pubblica Documento', // Changed title
            'Pubblica Documento', // Changed menu text
            'edit_posts',
            'document-publisher'
        );
        
        add_submenu_page(
            'document-publisher',
            'Gestisci Documenti',
            'Gestisci Esistenti',
            'edit_posts',
            'edit.php?post_type=documento'
        );

        add_submenu_page(
            'document-publisher',
            'Ordina Documenti',
            'Ordina Documenti',
            'edit_posts',
            'order-documenti',
            array($this, 'render_sortable_documents_page')
        );
    }

    public function enqueue_scripts($hook) {
        if ('toplevel_page_document-publisher' === $hook) {
            // Enqueue CSS first
            wp_enqueue_style(
                'document-publisher-admin',
                DOC_PUBLISHER_URL . 'assets/css/admin.css',
                array(),
                DOC_PUBLISHER_VERSION
            );
            
            // Then enqueue JS
            wp_enqueue_script(
                'document-publisher-admin',
                DOC_PUBLISHER_URL . 'assets/js/admin.js',
                array('jquery'),
                DOC_PUBLISHER_VERSION,
                true
            );

            wp_localize_script('document-publisher-admin', 'docPublisher', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('publish_document_action')
            ));
        }
        
        // Carica gli stili solo nelle pagine necessarie
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            $screen = get_current_screen();
            if ('documento' === $screen->post_type) {
                wp_enqueue_style(
                    'document-publisher-admin',
                    DOC_PUBLISHER_URL . 'assets/css/admin.css',
                    array(),
                    '1.0.0'
                );
                wp_enqueue_media();
                wp_enqueue_script(
                    'document-media-upload',
                    DOC_PUBLISHER_URL . 'assets/js/media-upload.js',
                    array('jquery'),
                    DOC_PUBLISHER_VERSION,
                    true
                );
            }
        }

        if ('toplevel_page_document-publisher' === $hook || 
            ('post.php' === $hook || 'post-new.php' === $hook)) {
            wp_add_inline_style('document-publisher-admin', '
                #wpcontent { 
                    background-color: #f0f6fc; 
                }
                .wrap {
                    background: white;
                    padding: 20px;
                    border-radius: 4px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
            ');
        }
    }

    public function render_publisher_page() {
        require_once DOC_PUBLISHER_PATH . 'templates/publisher-form.php';
    }

    public function add_meta_boxes() {
        add_meta_box(
            'document_details',
            'Dettagli Documento',
            array($this, 'render_meta_box'),
            'documento',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('save_document_details', 'document_details_nonce');
        require_once DOC_PUBLISHER_PATH . 'templates/meta-box.php';
    }

    public function save_document_details($post_id) {
        if (!isset($_POST['document_details_nonce']) || 
            !wp_verify_nonce($_POST['document_details_nonce'], 'save_document_details')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Handle new file upload if provided
        if (!empty($_FILES['new_document_file']['name'])) {
            // Delete old file if exists
            $old_attachment_id = get_post_meta($post_id, '_document_attachment_id', true);
            if ($old_attachment_id) {
                wp_delete_attachment($old_attachment_id, true);
            }

            // Upload new file
            $file = $_FILES['new_document_file'];
            $upload = wp_handle_upload($file, array('test_form' => false));

            if (!isset($upload['error'])) {
                // Create attachment in Media Library
                $attachment = array(
                    'post_mime_type' => $upload['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );

                $attach_id = wp_insert_attachment($attachment, $upload['file']);
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
                wp_update_attachment_metadata($attach_id, $attach_data);

                // Update document metadata
                update_post_meta($post_id, '_document_url', $upload['url']);
                update_post_meta($post_id, '_document_attachment_id', $attach_id);
            }
        }

        // Handle media selection
        if (isset($_POST['document_url']) && !empty($_POST['document_url'])) {
            $attachment_id = attachment_url_to_postid($_POST['document_url']);
            if ($attachment_id) {
                update_post_meta($post_id, '_document_url', $_POST['document_url']);
                update_post_meta($post_id, '_document_attachment_id', $attachment_id);
            }
        }

        // Save display title and description
        if (isset($_POST['document_display_title'])) {
            update_post_meta($post_id, '_document_display_title', sanitize_text_field($_POST['document_display_title']));
        }
        
        if (isset($_POST['document_description'])) {
            update_post_meta($post_id, '_document_description', wp_kses_post($_POST['document_description']));
        }

        $this->update_document_meta($post_id);
    }

    private function update_document_meta($post_id) {
        if (isset($_POST['target_page'])) {
            $old_page_id = get_post_meta($post_id, '_target_page', true);
            $new_page_id = intval($_POST['target_page']);
            update_post_meta($post_id, '_target_page', $new_page_id);
            
            if ($old_page_id != $new_page_id) {
                wp_update_post(array(
                    'ID' => $new_page_id,
                    'post_modified' => current_time('mysql'),
                    'post_modified_gmt' => current_time('mysql', 1)
                ));
            }
        }

        if (isset($_POST['preceding_document'])) {
            update_document_order($post_id, $_POST['target_page'], $_POST['preceding_document']);
        }
    }

    public function handle_document_file_deletion() {
        check_ajax_referer('delete_document_file', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permessi insufficienti');
        }

        $document_id = intval($_POST['document_id']);
        
        // Ottieni l'ID dell'allegato
        $attachment_id = get_post_meta($document_id, '_document_attachment_id', true);
        
        // Elimina l'allegato dalla Media Library
        if ($attachment_id) {
            wp_delete_attachment($attachment_id, true);
        }
        
        // Rimuovi i metadati del documento
        delete_post_meta($document_id, '_document_url');
        delete_post_meta($document_id, '_document_attachment_id');
        
        wp_send_json_success();
    }

    public function handle_document_publish() {
        check_ajax_referer('publish_document_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permessi insufficienti');
        }

        if (empty($_FILES['document_file'])) {
            wp_send_json_error('Nessun file caricato');
        }

        $upload = wp_handle_upload($_FILES['document_file'], array('test_form' => false));
        
        if (isset($upload['error'])) {
            wp_send_json_error($upload['error']);
        }

        // Fix the upload URL before saving
        $upload['url'] = $this->fix_upload_url($upload['url']);

        // Create document post
        $post_data = array(
            'post_title'    => sanitize_text_field($_POST['document_name']),
            'post_status'   => 'publish',
            'post_type'     => 'documento'
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            wp_send_json_error('Errore nella creazione del documento');
        }

        // Save document metadata
        update_post_meta($post_id, '_document_url', $upload['url']);
        update_post_meta($post_id, '_target_page', intval($_POST['target_page']));
        
        // Save display title and description
        if (!empty($_POST['document_display_title'])) {
            update_post_meta($post_id, '_document_display_title', sanitize_text_field($_POST['document_display_title']));
        }
        if (!empty($_POST['document_description'])) {
            update_post_meta($post_id, '_document_description', wp_kses_post($_POST['document_description']));
        }

        wp_send_json_success(array('post_id' => $post_id));
    }

    // Add this new method to fix upload URLs
    private function fix_upload_url($url) {
        $upload_dir = wp_upload_dir();
        $site_url = get_site_url();
        
        // Replace any absolute server path with site URL
        if (strpos($url, $upload_dir['basedir']) !== false) {
            $relative_path = str_replace($upload_dir['basedir'], '', $url);
            $url = $upload_dir['baseurl'] . $relative_path;
        }
        
        return $url;
    }

    public function get_next_order_callback() {
        $page_id = intval($_GET['page_id']);
        echo get_next_document_order($page_id);
        wp_die();
    }

    public function show_admin_notices() {
        if (isset($_GET['doc_publisher_message'])) {
            $message = sanitize_text_field($_GET['doc_publisher_message']);
            $class = 'notice notice-success is-dismissible';
            $message = $this->get_message_text($message);
            
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
        }
    }

    private function get_message_text($code) {
        $messages = array(
            'published' => 'Documento pubblicato con successo!',
            'updated' => 'Documento aggiornato con successo!',
            'deleted' => 'Documento eliminato con successo!'
        );
        return isset($messages[$code]) ? $messages[$code] : '';
    }

    public function remove_editor_from_document() {
        remove_post_type_support('documento', 'editor');
    }

    public function get_documents_for_page_callback() {
        check_ajax_referer('publish_document_action', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permessi insufficienti');
        }

        $page_id = intval($_GET['page_id']);
        $documents = get_documents_for_page($page_id);

        if (empty($documents)) {
            wp_send_json_success(array());
        }

        $response = array();
        foreach ($documents as $document) {
            $response[] = array(
                'ID' => $document->ID,
                'post_title' => $document->post_title
            );
        }

        wp_send_json_success($response);
    }

    public function render_sortable_documents_page() {
        require_once DOC_PUBLISHER_PATH . 'templates/sortable-documents.php';
    }

    public function update_documents_order_callback() {
        check_ajax_referer('publish_document_action', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        // Expected order as an array of document IDs in new order
        $new_order = isset($_POST['order']) ? $_POST['order'] : array();
        if (empty($new_order) || !is_array($new_order)) {
            wp_send_json_error('Nessun ordine ricevuto');
        }
        
        // Update each document's _document_order meta with new order index  
        foreach ($new_order as $index => $post_id) {
            update_post_meta(intval($post_id), '_document_order', $index + 1);
        }
        
        wp_send_json_success('Ordinamento aggiornato');
    }
}