<?php
/*
Plugin Name: Gestione Documenti Società Trasparente
Description: Gestione e pubblicazione documenti per la sezione Società Trasparente
Version: 1.80
Author: Andrea Gouchon
Text Domain: doc-publisher
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

// Check Elementor dependency
function doc_publisher_check_elementor() {
    if (!did_action('elementor/loaded')) {
        add_action('admin_notices', function() {
            $message = sprintf(
                'Gestione Documenti Società Trasparente richiede Elementor per funzionare. <a href="%s">Installa Elementor</a>.',
                admin_url('plugin-install.php?s=Elementor&tab=search&type=term')
            );
            echo '<div class="error"><p>' . $message . '</p></div>';
        });
        return false;
    }
    return true;
}

// Definizioni costanti
define('DOC_PUBLISHER_VERSION', '1.80');
define('DOC_PUBLISHER_PATH', plugin_dir_path(__FILE__));
define('DOC_PUBLISHER_URL', plugin_dir_url(__FILE__));

// Add file type validation constant
define('DOC_PUBLISHER_ALLOWED_TYPES', array(
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
));

// Modifica il testo dei menu
function modify_doc_publisher_menu_text($translated_text, $text, $domain) {
    switch ($translated_text) {
        case 'Gestione Documenti':
            return 'Gestione Documenti Società Trasparente';
        default:
            return $translated_text;
    }
}
add_filter('gettext', 'modify_doc_publisher_menu_text', 20, 3);

// Disabilita il controllo degli aggiornamenti per questo plugin
add_filter('site_transient_update_plugins', function($transient) {
    if (isset($transient->response[plugin_basename(__FILE__)])) {
        unset($transient->response[plugin_basename(__FILE__)]);
    }
    return $transient;
});

// Caricamento file principali
require_once DOC_PUBLISHER_PATH . 'includes/class-post-type.php';
require_once DOC_PUBLISHER_PATH . 'includes/class-admin.php';
require_once DOC_PUBLISHER_PATH . 'includes/functions.php';

// Inizializzazione classi principali
function doc_publisher_init() {
    if (!doc_publisher_check_elementor()) {
        return;
    }
    
    // Inizializza le classi solo se Elementor è attivo
    new Doc_Publisher_Post_Type();
    new Doc_Publisher_Admin();
}
add_action('plugins_loaded', 'doc_publisher_init');

// Update the widget registration function
function doc_publisher_load_elementor_widget() {
    require_once DOC_PUBLISHER_PATH . 'widgets/class-document-widget.php';
    
    // Make sure Elementor is loaded
    if (did_action('elementor/loaded')) {
        // Register the widget
        \Elementor\Plugin::instance()->widgets_manager->register(new \Elementor_Document_Widget());
    }
}

// Change the hook to elementor/widgets/register
add_action('elementor/widgets/register', 'doc_publisher_load_elementor_widget');

// Registrazione stili e script
function doc_publisher_register_assets() {
    wp_register_style(
        'document-button-style',
        DOC_PUBLISHER_URL . 'assets/css/document-button.css',
        array(),
        DOC_PUBLISHER_VERSION
    );
}
add_action('wp_enqueue_scripts', 'doc_publisher_register_assets');

// Add AJAX handler for document submission
add_action('wp_ajax_publish_document', 'handle_document_submission');
add_action('wp_ajax_nopriv_publish_document', 'handle_document_submission');

// Attivazione plugin
function doc_publisher_activate() {
    // Crea il custom post type
    $post_type = new Doc_Publisher_Post_Type();
    $post_type->register_post_type();
    
    // Aggiorna i rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'doc_publisher_activate');

// Disattivazione plugin
function doc_publisher_deactivate() {
    // Pulisci i rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'doc_publisher_deactivate');