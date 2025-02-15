(function($) {
    'use strict';
    
    const DocumentUploader = {
        init() {
            this.bindEvents();
            this.setupValidation();
        },

        bindEvents() {
            $('#document-publisher-form').on('submit', this.handleSubmit.bind(this));
            $('#target_page').on('change', this.updateOrder.bind(this));
            $('#document_file').on('change', this.validateFile.bind(this));
            // New events for file source selection
            $('input[name="upload_choice"]').on('change', this.toggleFileSource.bind(this));
            $('#select-media-button').on('click', this.openMediaLibrary.bind(this));
        },

        setupValidation() {
            this.allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ];
        },

        validateFile(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (!this.allowedTypes.includes(file.type)) {
                alert('Tipo di file non supportato');
                e.target.value = '';
                return;
            }

            // Chiamata al metodo aggiornato
            this.updateFileInfo(file);
        },

        updateFileInfo(file) {
            // Estrae il nome senza estensione
            var nameWithoutExt = file.name.split('.').slice(0, -1).join('.');
            console.log('Nome originale (senza estensione):', nameWithoutExt);
            
            // Sostituisce trattini e underscore con spazi
            var cleaned = nameWithoutExt.replace(/[-_]+/g, ' ');
            console.log('Dopo sostituzione di trattini/underscore:', cleaned);
            
            // Normalizza la capitalizzazione: solo la prima lettera in maiuscolo, il resto in minuscolo
            cleaned = cleaned.charAt(0).toUpperCase() + cleaned.slice(1).toLowerCase();
            console.log('Nome finale normalizzato:', cleaned);
            
            // Aggiorna il campo document_name con il valore "ripulito"
            $('#document_name').val(cleaned);
            $('#file-size').text('Dimensione: ' + (file.size / 1024).toFixed(2) + ' KB');
        },

        toggleFileSource(e) {
            const choice = $(e.target).val();
            if (choice === 'upload') {
                $('#upload-file-section').show();
                $('#document_file').prop('required', true);
                $('#media-gallery-section').hide();
            } else {
                $('#upload-file-section').hide();
                $('#document_file').prop('required', false);
                $('#media-gallery-section').show();
            }
        },

        openMediaLibrary() {
            var frame = wp.media({
                title: 'Seleziona un file',
                button: { text: 'Utilizza questo file' },
                multiple: false
            });
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#document_url').val(attachment.url);
                $('#selected-media-preview').html('<a href="'+attachment.url+'" target="_blank">'+attachment.filename+'</a>');
                // Process and set the document name based on the selected file
                var nameWithoutExt = attachment.filename.split('.').slice(0, -1).join('.');
                var cleaned = nameWithoutExt.replace(/[-_]+/g, ' ');
                cleaned = cleaned.charAt(0).toUpperCase() + cleaned.slice(1).toLowerCase();
                $('#document_name').val(cleaned);
            });
            frame.open();
        },

        updateOrder(e) {
            var pageId = $(e.target).val();
            if (pageId) {
                $.ajax({
                    url: ajaxurl,
                    data: {
                        action: 'get_documents_for_page',
                        page_id: pageId,
                        nonce: docPublisher.nonce
                    },
                    success: function(response) {
                        var $precedingDocument = $('#preceding_document');
                        $precedingDocument.empty();
                        $precedingDocument.append('<option value="-1" selected>Alla fine di tutti</option>');
                        $precedingDocument.append('<option value="0">Prima di tutti</option>');
                        if (response.success && response.data.length > 0) {
                            response.data.forEach(function(doc) {
                                $precedingDocument.append('<option value="' + doc.ID + '">' + doc.post_title + '</option>');
                            });
                        }
                    }
                });
            }
        },

        handleSubmit(e) {
            e.preventDefault();
            console.log("handleSubmit: form submission triggered");

            var $form = $(e.target);
            var $submit = $form.find(':submit');
            var $progress = $('#upload-progress');
            var $progressBar = $('#progress-bar');
            var $progressText = $('#progress-text');
            var $fileSize = $('#file-size');

            // Mostra la barra di avanzamento
            $progress.show();
            $progressBar.val(0);
            $progressText.text('0%');
            
            var formData = new FormData(e.target);
            formData.append('action', 'publish_document');
            formData.append('publish_document_nonce', docPublisher.nonce);
            
            // Debug: log formData keys
            for (var pair of formData.entries()) {
                console.log(pair[0]+ ': ' + pair[1]);
            }
            
            $.ajax({
                url: docPublisher.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $submit.prop('disabled', true);
                    var file = $('#document_file')[0].files[0];
                    if (file) {
                        $fileSize.text('Dimensione file: ' + (file.size / (1024 * 1024)).toFixed(2) + ' MB');
                    }
                },
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                            $progressBar.val(percentComplete);
                            $progressText.text(percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    console.log("AJAX success:", response);
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert(response.data || 'Errore durante il caricamento');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX error:", textStatus, errorThrown);
                    alert('Errore durante il caricamento');
                },
                complete: function() {
                    $submit.prop('disabled', false);
                    $progress.fadeOut();
                }
            });
        }
    };

    $(document).ready(() => DocumentUploader.init());
})(jQuery);