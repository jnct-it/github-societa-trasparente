(function($){
    'use strict';
    $(document).ready(function(){
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
                        nonce: docPublisher.nonce
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
})(jQuery);
