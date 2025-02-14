
jQuery(document).ready(function($) {
    let mediaUploader;
    let uploadButton = $('#upload_document_button');
    let mediaButton = $('#media_document_button');
    let urlInput = $('#document_url');
    let preview = $('#document_preview');

    uploadButton.click(function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Carica Documento',
            button: {
                text: 'Usa questo documento'
            },
            library: {
                type: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            updateDocumentPreview(attachment);
        });

        mediaUploader.open();
    });

    mediaButton.click(function(e) {
        e.preventDefault();
        let mediaFrame = wp.media({
            title: 'Seleziona Documento',
            button: {
                text: 'Usa questo documento'
            },
            library: {
                type: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            },
            multiple: false
        });

        mediaFrame.on('select', function() {
            const attachment = mediaFrame.state().get('selection').first().toJSON();
            updateDocumentPreview(attachment);
        });

        mediaFrame.open();
    });

    function updateDocumentPreview(attachment) {
        urlInput.val(attachment.url);
        preview.html(`
            <div class="document-preview">
                <span class="dashicons dashicons-media-document"></span>
                <span class="filename">${attachment.filename}</span>
                <button type="button" class="remove-document button">
                    <span class="dashicons dashicons-no"></span>
                </button>
            </div>
        `);
    }

    preview.on('click', '.remove-document', function() {
        urlInput.val('');
        preview.empty();
    });
});