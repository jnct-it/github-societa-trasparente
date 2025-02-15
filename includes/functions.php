<?php
add_action('wp_ajax_publish_document', 'handle_document_submission');

function get_next_document_order($page_id) {
    global $wpdb;
    $last_order = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM $wpdb->postmeta 
         WHERE meta_key = '_document_order' 
         AND post_id IN (
             SELECT post_id FROM $wpdb->postmeta 
             WHERE meta_key = '_target_page' 
             AND meta_value = %d
         )
         ORDER BY CAST(meta_value AS SIGNED) DESC 
         LIMIT 1",
        $page_id
    ));
    return $last_order ? intval($last_order) + 1 : 10;
}

function handle_document_submission() {
    check_ajax_referer('publish_document_action', 'publish_document_nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permessi insufficienti');
    }
    
    // New condition: if a file URL from the Media Gallery is provided, use it
    if (!empty($_FILES['document_file']['name'])) {
        $upload_result = handle_file_upload();
    } elseif (isset($_POST['document_url']) && !empty($_POST['document_url'])) {
        $upload_result = array('url' => sanitize_text_field($_POST['document_url']));
    } else {
        wp_send_json_error('Nessun file selezionato');
    }
    
    if (is_wp_error($upload_result)) {
        wp_send_json_error($upload_result->get_error_message());
    }
    
    $post_id = create_document_post($upload_result);
    if (is_wp_error($post_id)) {
        wp_send_json_error($post_id->get_error_message());
    }
    
    update_page_modification_date($_POST['target_page']);
    
    // Save display title and description
    if (!empty($_POST['document_display_title'])) {
        update_post_meta($post_id, '_document_display_title', sanitize_text_field($_POST['document_display_title']));
    }
    if (!empty($_POST['document_description'])) {
        update_post_meta($post_id, '_document_description', wp_kses_post($_POST['document_description']));
    }
    
    update_document_order($post_id, $_POST['target_page'], $_POST['preceding_document']);
    
    wp_send_json_success();
}

function save_document_details($post_id) {
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

    update_document_order($post_id, $_POST['target_page'], $_POST['preceding_document']);
}

function update_document_order($post_id, $target_page, $preceding_document) {
    $preceding_document = intval($preceding_document);
    if ($preceding_document > 0) {
        $preceding_order = get_post_meta($preceding_document, '_document_order', true);
        $new_order = $preceding_order + 1;
        update_post_meta($post_id, '_document_order', $new_order);
        // Update order of subsequent documents
        $documents = get_documents_for_page($target_page);
        foreach ($documents as $document) {
            if ($document->ID != $post_id) {
                $current_order = get_post_meta($document->ID, '_document_order', true);
                if ($current_order >= $new_order) {
                    update_post_meta($document->ID, '_document_order', $current_order + 1);
                }
            }
        }
    } elseif ($preceding_document == -1) {
        $new_order = get_next_document_order($target_page);
        update_post_meta($post_id, '_document_order', $new_order);
    } else {
        // Handle "Prima di tutti" case
        $documents = get_documents_for_page($target_page);
        foreach ($documents as $document) {
            if ($document->ID != $post_id) {
                $current_order = get_post_meta($document->ID, '_document_order', true);
                update_post_meta($document->ID, '_document_order', $current_order + 1);
            }
        }
        update_post_meta($post_id, '_document_order', 1);
    }
}

function validate_file_type($file) {
    // Check if file is empty or zero size
    if (empty($file) || $file['size'] <= 0) {
        return new WP_Error('invalid_file', 'Il file è vuoto o non valido');
    }

    $allowed_types = array_values(DOC_PUBLISHER_ALLOWED_TYPES);
    $file_type = wp_check_filetype($file['name']);
    
    if (!in_array($file_type['type'], $allowed_types)) {
        return new WP_Error('invalid_file_type', 'Tipo di file non supportato. Sono permessi solo PDF, DOC, DOCX, XLS e XLSX.');
    }
    
    return true;
}

function handle_file_upload() {
    $file = $_FILES['document_file'];
    
    // Enhanced validation
    $validation = validate_file_type($file);
    if (is_wp_error($validation)) {
        return $validation;
    }

    $document_name = sanitize_text_field($_POST['document_name']);
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $final_filename = $document_name . '.' . $file_extension;

    $upload = wp_upload_bits($final_filename, null, file_get_contents($file['tmp_name']));
    
    if ($upload['error']) {
        return new WP_Error('upload_error', 'Errore nel salvataggio del file');
    }

    $attachment = array(
        'post_mime_type' => $file['type'],
        'post_title' => $document_name,
        'post_content' => '',
        'post_status' => 'inherit'
    );

    $attach_id = wp_insert_attachment($attachment, $upload['file']);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);

    return array(
        'url' => $upload['url'],
        'attachment_id' => $attach_id
    );
}

function create_document_post($upload_result) {
    // Build the meta input, adding the attachment ID if available
    $meta_input = array(
        '_document_url'   => $upload_result['url'],
        '_target_page'    => intval($_POST['target_page'])
    );
    if (isset($upload_result['attachment_id'])) {
        $meta_input['_document_attachment_id'] = $upload_result['attachment_id'];
    }
    
    $post_data = array(
        'post_title'    => sanitize_text_field($_POST['document_name']),
        'post_status'   => 'publish',
        'post_type'     => 'documento',
        'meta_input'    => $meta_input
    );

    return wp_insert_post($post_data);
}

function update_page_modification_date($page_id) {
    wp_update_post(array(
        'ID' => intval($page_id),
        'post_modified' => current_time('mysql'),
        'post_modified_gmt' => current_time('mysql', 1)
    ));
}

function get_document_preview_html($file_url, $mime_type) {
    $preview_html = '<div class="document-preview-thumbnail">';
    
    if (strpos($mime_type, 'pdf') !== false) {
        // For PDFs, use object tag for preview
        $preview_html .= sprintf(
            '<object data="%s#page=1&view=FitH" type="application/pdf" width="100%%" height="100%%">
                <p>Il tuo browser non supporta la visualizzazione PDF.</p>
            </object>',
            esc_url($file_url)
        );
    } else {
        $preview_html .= '<div class="no-preview">Anteprima non disponibile per questo tipo di file</div>';
    }
    
    $preview_html .= '</div>';
    return $preview_html;
}

function get_documents_for_page($page_id) {
    $args = array(
        'post_type' => 'documento',
        'meta_key' => '_target_page',
        'meta_value' => $page_id,
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
        'posts_per_page' => -1
    );
    return get_posts($args);
}