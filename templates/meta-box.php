<?php
if (!defined('ABSPATH')) exit;

$target_page = get_post_meta($post->ID, '_target_page', true);
$document_order = get_post_meta($post->ID, '_document_order', true);
$doc_url = get_post_meta($post->ID, '_document_url', true);
$pages = get_pages();
?>
<div class="document-details-wrapper">
    <table class="form-table">
        <tr>
            <th scope="row">
                <label>File Documento</label>
            </th>
            <td>
                <?php if ($doc_url): ?>
                    <div class="current-file">
                        <?php 
                        $mime_type = wp_check_filetype(basename($doc_url));
                        echo get_document_preview_html($doc_url, $mime_type['type']); 
                        ?>
                        <div class="document-actions">
                            <a href="<?php echo esc_url($doc_url); ?>" target="_blank" class="button">
                                <span class="dashicons dashicons-media-document"></span>
                                <?php echo esc_html(basename($doc_url)); ?>
                            </a>
                            <button type="button" class="button delete-document" data-document-id="<?php echo $post->ID; ?>">
                                <span class="dashicons dashicons-trash"></span>
                                Elimina
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="document-upload-wrapper">
                    <input type="hidden" name="document_url" id="document_url" value="<?php echo esc_url($doc_url); ?>">
                    <button type="button" class="button" id="upload_document_button">
                        <span class="dashicons dashicons-upload"></span>
                        Carica nuovo file
                    </button>
                    <button type="button" class="button" id="media_document_button">
                        <span class="dashicons dashicons-admin-media"></span>
                        Seleziona dalla libreria
                    </button>
                    <div id="document_preview"></div>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="target_page">Pagina di Pubblicazione</label>
            </th>
            <td>
                <?php
                wp_dropdown_pages(array(
                    'name' => 'target_page',
                    'id' => 'target_page',
                    'selected' => $target_page,
                    'show_option_none' => 'Seleziona una pagina',
                    'option_none_value' => '',
                    'hierarchical' => true
                ));
                ?>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="document_display_title">Titolo Visualizzato (opzionale)</label>
            </th>
            <td>
                <input type="text" id="document_display_title" name="document_display_title" 
                       value="<?php echo esc_attr(get_post_meta($post->ID, '_document_display_title', true)); ?>" 
                       class="regular-text">
                <p class="description">Titolo aggiuntivo da mostrare sopra il documento (lasciare vuoto per non mostrare)</p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="document_description">Descrizione (opzionale)</label>
            </th>
            <td>
                <?php
                wp_editor(
                    get_post_meta($post->ID, '_document_description', true),
                    'document_description',
                    array(
                        'textarea_name' => 'document_description',
                        'textarea_rows' => 5,
                        'media_buttons' => false,
                        'teeny' => true,
                        'quicktags' => false
                    )
                );
                ?>
                <p class="description">Testo aggiuntivo da mostrare tra il titolo e il documento (lasciare vuoto per non mostrare)</p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="preceding_document">Posizione di pubblicazione</label>
            </th>
            <td>
                <select id="preceding_document" name="preceding_document">
                    <option value="-1">Alla fine di tutti</option>
                    <option value="0">Prima di tutti</option>
                    <!-- Options will be populated dynamically via JavaScript -->
                </select>
                <p class="description">Pubblica il documento dopo quello selezionato</p>
            </td>
        </tr>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    $('.delete-document').click(function(e) {
        e.preventDefault();
        if (confirm('Sei sicuro di voler eliminare questo documento? Questa azione non può essere annullata.')) {
            var documentId = $(this).data('document-id');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_document_file',
                    document_id: documentId,
                    nonce: '<?php echo wp_create_nonce("delete_document_file"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Errore durante l\'eliminazione del file');
                    }
                },
                error: function() {
                    alert('Si è verificato un errore durante l\'elaborazione della richiesta');
                }
            });
        }
    });

    // Populate the position selector with existing documents
    $('#target_page').on('change', function() {
        var pageId = $(this).val();
        if (pageId) {
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'get_documents_for_page',
                    page_id: pageId,
                    nonce: '<?php echo wp_create_nonce("publish_document_action"); ?>'
                },
                success: function(response) {
                    var $precedingDocument = $('#preceding_document');
                    $precedingDocument.empty();
                    $precedingDocument.append('<option value="-1">Alla fine di tutti</option>');
                    $precedingDocument.append('<option value="0">Prima di tutti</option>');
                    if (response.success && response.data.length > 0) {
                        response.data.forEach(function(doc) {
                            if (doc.ID != <?php echo $post->ID; ?>) {
                                $precedingDocument.append('<option value="' + doc.ID + '">' + doc.post_title + '</option>');
                            }
                        });
                    }
                    // Set the current position as selected
                    var currentOrder = <?php echo $document_order; ?>;
                    $precedingDocument.val(currentOrder);
                }
            });
        }
    }).trigger('change'); // Trigger change event on page load to populate the selector
});
</script>