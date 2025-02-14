<?php 
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <h1>Pubblica Documento</h1>
    
    <?php settings_errors('document_publisher'); ?>
    
    <form id="document-publisher-form" method="post" enctype="multipart/form-data" class="document-publisher-form">
        <?php wp_nonce_field('publish_document_nonce', 'nonce'); ?>
        
        <table class="form-table">
            <!-- Pagina di Pubblicazione -->
            <tr>
                <th scope="row">
                    <label for="target_page">Pagina di Pubblicazione</label>
                </th>
                <td>
                    <?php 
                    // Recupera la pagina "Amministrazione Trasparente" e mostra solo le sue pagine figlie
                    $amministrazione_page = get_page_by_title('Amministrazione Trasparente');
                    if ( $amministrazione_page ) {
                        $dropdown = wp_dropdown_pages( array(
                            'name'             => 'target_page',
                            'id'               => 'target_page',
                            'show_option_none' => 'Seleziona una pagina',
                            'child_of'         => $amministrazione_page->ID,
                            'echo'             => false
                        ) );
                    } else {
                        $dropdown = wp_dropdown_pages( array(
                            'name'             => 'target_page',
                            'id'               => 'target_page',
                            'show_option_none' => 'Seleziona una pagina',
                            'echo'             => false
                        ) );
                    }
                    // Aggiunge l'attributo required al tag <select>
                    echo str_replace('<select', '<select required', $dropdown);
                    ?>
                </td>
            </tr>

            <!-- Selezione del File -->
            <tr>
                <th scope="row">
                    <label for="document_file">Seleziona File</label>
                </th>
                <td>
                    <input type="file" 
                           id="document_file" 
                           name="document_file" 
                           accept=".pdf,.doc,.docx,.xls,.xlsx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" 
                           required>
                    <p class="description" id="file-size">File supportati: PDF, DOC, DOCX, XLS, XLSX</p>
                </td>
            </tr>

            <!-- Nome del Documento -->
            <tr>
                <th scope="row">
                    <label for="document_name">Nome del documento visualizzato nel pulsante</label>
                </th>
                <td>
                    <input type="text" id="document_name" name="document_name" class="regular-text" required>
                    <p class="description">L'estensione verrà aggiunta automaticamente</p>
                </td>
            </tr>

            <!-- Posizione di Pubblicazione -->
            <tr>
                <th scope="row">
                    <label for="preceding_document">Posizione di pubblicazione</label>
                </th>
                <td>
                    <select name="preceding_document" id="preceding_document">
                        <option value="-1" selected>Alla fine di tutti</option>
                        <option value="0">Prima di tutti</option>
                    </select>
                    <p class="description">Seleziona la posizione in cui pubblicare il documento.</p>
                </td>
            </tr>

            <!-- Spazio per eventuali ulteriori testi -->
            <tr>
                <td colspan="2">
                    <hr style="margin: 20px 0;">
                    <p class="description" style="font-style: italic;">Se necessario è possibile inserire un testo aggiuntivo oltre al pulsante del documento pubblicato</p>
                </td>
            </tr>

            <!-- Titolo Visualizzato (opzionale) -->
            <tr>
                <th scope="row">
                    <label for="document_display_title">Titolo Visualizzato (opzionale)</label>
                </th>
                <td>
                    <input type="text" id="document_display_title" name="document_display_title" class="regular-text">
                    <p class="description">Titolo aggiuntivo da mostrare sopra il documento (lasciare vuoto per non mostrare)</p>
                </td>
            </tr>

            <!-- Descrizione (opzionale) -->
            <tr>
                <th scope="row">
                    <label for="document_description">Descrizione (opzionale)</label>
                </th>
                <td>
                    <?php
                    wp_editor(
                        '',
                        'document_description',
                        array(
                            'textarea_name' => 'document_description',
                            'textarea_rows' => 5,
                            'media_buttons' => false,
                            'teeny'         => true,
                            'quicktags'     => false
                        )
                    );
                    ?>
                    <p class="description">Testo aggiuntivo da mostrare tra il titolo e il documento (lasciare vuoto per non mostrare)</p>
                </td>
            </tr>
        </table>

        <!-- Upload Progress -->
        <div id="upload-progress" class="upload-progress-wrapper" style="display: none;">
            <div class="progress-info">
                <span id="file-size"></span>
                <span id="progress-text">0%</span>
            </div>
            <div class="progress-bar-container">
                <progress id="progress-bar" value="0" max="100"></progress>
            </div>
        </div>

        <?php submit_button('Pubblica Documento'); ?>
    </form>
</div>