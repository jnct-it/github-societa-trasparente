<?php 
if ( ! current_user_can('edit_posts') ) {
    wp_die('Permessi insufficienti');
}
// Retrieve the "Amministrazione Trasparente" parent page
$parent_page = get_page_by_title('Amministrazione Trasparente');
?>
<div class="wrap">
    <h1>Ordina Documenti per Sezione</h1>
    <p>Seleziona la sezione in cui vuoi riorganizzare i documenti.</p>
    <?php
    // Using wp_dropdown_pages to replicate the "pagina di pubblicazione" selector,
    // but including only pages under "Amministrazione Trasparente"
    $dropdown = wp_dropdown_pages(array(
        'name'             => 'target_section',
        'id'               => 'target-section',
        'show_option_none' => '-- Seleziona Sezione --',
        'option_none_value'=> '',
        'sort_column'      => 'post_title',
        'child_of'         => $parent_page ? $parent_page->ID : 0,
        'echo'             => 0
    ));
    echo $dropdown;
    ?>
    <div id="documents-container" style="margin-top:20px; display:none;">
        <p>Trascina i documenti per modificare l’ordinamento.</p>
        <ul id="sortable-documents">
            <!-- Document items will be loaded via AJAX -->
        </ul>
        <div id="order-status" style="margin-top:10px;"></div>
    </div>
</div>
<script>
jQuery(document).ready(function($){
    // When a section is selected, load the corresponding documents
    $('#target-section').on('change', function(){
        var sectionId = $(this).val();
        if(sectionId){
            $.ajax({
                url: ajaxurl,
                method: 'GET',
                data: {
                    action: 'get_documents_for_page',
                    page_id: sectionId,
                    nonce: '<?php echo wp_create_nonce("publish_document_action"); ?>'
                },
                success: function(response){
                    if(response.success){
                        var ul = $('#sortable-documents');
                        ul.empty();
                        if(response.data.length > 0){
                            $.each(response.data, function(index, doc){
                                ul.append('<li id="document-'+doc.ID+'" style="cursor: move; padding: 8px; border: 1px solid #ddd; margin-bottom: 4px;">'+doc.post_title+'</li>');
                            });
                        } else {
                            ul.append('<li>Nessun documento trovato per questa sezione</li>');
                        }
                        $('#documents-container').show();
                    }
                }
            });
        } else {
            $('#documents-container').hide();
        }
    });
    
    // Enable sortable on the documents list
    $('#sortable-documents').sortable({
        update: function(event, ui) {
            var order = [];
            $('#sortable-documents li').each(function(){
                var id = $(this).attr('id').replace('document-','');
                order.push(id);
            });
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'update_documents_order',
                    order: order,
                    nonce: '<?php echo wp_create_nonce("publish_document_action"); ?>'
                },
                success: function(response) {
                    $('#order-status').html(response.success ? '<span style="color:green;">Ordinamento aggiornato!</span>' : '<span style="color:red;">Errore nell’aggiornamento</span>');
                },
                error: function() {
                    $('#order-status').html('<span style="color:red;">Errore AJAX</span>');
                }
            });
        }
    });
});
</script>
