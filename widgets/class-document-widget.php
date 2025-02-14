<?php
class Elementor_Document_Widget extends \Elementor\Widget_Base {
    public function get_name() {
        return 'page_documents';
    }

    public function get_title() {
        return 'Documenti Società Trasparente';
    }

    public function get_icon() {
        return 'eicon-document-file';
    }

    public function get_categories() {
        return ['general'];
    }

    public function get_style_depends() {
        return ['document-button-style'];
    }

    public function get_script_depends() {
        return ['document-button-style'];
    }

    protected function register_controls() {
        $this->register_content_section();
        $this->register_style_section();
        $this->register_icon_section();
        $this->register_document_styles_section();
    }

    private function register_content_section() {
        $this->start_controls_section('content_section', [
            'label' => 'Selezione Documenti',
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $doc_options = $this->get_document_options();
        
        $this->add_control('selected_documents', [
            'label' => 'Seleziona Documenti da Mostrare',
            'type' => \Elementor\Controls_Manager::SELECT2,
            'multiple' => true,
            'options' => $doc_options,
            'default' => [],
            'description' => 'Lascia vuoto per mostrare tutti i documenti associati a questa pagina'
        ]);

        $this->add_control('icon_type', [
            'label' => 'Tipo Icona',
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'paperclip',
            'options' => [
                'paperclip' => 'Graffetta',
                'download' => 'Download',
                'file' => 'File',
                'pdf' => 'PDF',
                'excel' => 'Excel',
                'word' => 'Word',
                'none' => 'Nessuna icona',
            ],
        ]);

        $this->end_controls_section();
    }

    private function get_document_options() {
        $documents = get_posts([
            'post_type' => 'documento',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        return array_reduce($documents, function($options, $doc) {
            $page_id = get_post_meta($doc->ID, '_target_page', true);
            $page = get_post($page_id);
            $page_title = $page ? " (Pagina: {$page->post_title})" : '';
            $options[$doc->ID] = $doc->post_title . $page_title;
            return $options;
        }, []);
    }

    // Sezione Stile Pulsante
    private function register_style_section() {
        $this->start_controls_section(
            'style_section',
            [
                'label' => 'Stile Pulsante',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => 'Colore Testo',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => 'var(--e-global-color-dcb3b27)',
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'color: {{VALUE}} !important; fill: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'button_background_color',
            [
                'label' => 'Colore Sfondo',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#FFFFFF',
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'label' => 'Tipografia',
                'selector' => '{{WRAPPER}} .elementor-button',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_box_shadow',
                'label' => 'Ombra',
                'selector' => '{{WRAPPER}} .elementor-button',
            ]
        );

        $this->add_control(
            'button_padding',
            [
                'label' => 'Padding',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
                'default' => [
                    'top' => '15',
                    'right' => '30',
                    'bottom' => '15',
                    'left' => '30',
                    'unit' => 'px',
                ],
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label' => 'Raggio Bordo',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
                'default' => [
                    'top' => '4',
                    'right' => '4',
                    'bottom' => '4',
                    'left' => '4',
                    'unit' => 'px',
                ],
            ]
        );

        // Sezione Hover
        $this->add_control(
            'hover_heading',
            [
                'label' => 'Hover',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'button_hover_color',
            [
                'label' => 'Colore Testo Hover',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-button:hover' => 'color: {{VALUE}} !important; fill: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'button_background_hover_color',
            [
                'label' => 'Colore Sfondo Hover',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-button:hover' => 'background-color: {{VALUE}} !important;',
                ],
                'default' => '#f5f5f5',
            ]
        );

        $this->end_controls_section();
    }

    // Sezione Stile Icona
    private function register_icon_section() {
        $this->start_controls_section(
            'icon_style_section',
            [
                'label' => 'Stile Icona',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'icon_type!' => 'none',
                ],
            ]
        );

        $this->add_control(
            'icon_size',
            [
                'label' => 'Dimensione Icona',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button-icon svg' => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_control(
            'icon_spacing',
            [
                'label' => 'Spazio Icona',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 10,
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button-icon' => 'margin-right: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    // Document Title Style Section
    private function register_document_styles_section() {
        $this->start_controls_section(
            'document_title_style',
            [
                'label' => 'Stile Titolo Documento',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => 'Tipografia Titolo',
                'selector' => '{{WRAPPER}} .document-title',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => 'Colore Titolo',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .document-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'title_padding',
            [
                'label' => 'Padding Titolo',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .document-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Document Description Style Section
        $this->start_controls_section(
            'document_description_style',
            [
                'label' => 'Stile Descrizione',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'description_typography',
                'label' => 'Tipografia Descrizione',
                'selector' => '{{WRAPPER}} .document-description',
            ]
        );

        $this->add_control(
            'description_color',
            [
                'label' => 'Colore Descrizione',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .document-description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'description_padding',
            [
                'label' => 'Padding Descrizione',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .document-description' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Documents Spacing Section
        $this->start_controls_section(
            'documents_spacing_style',
            [
                'label' => 'Spaziatura Documenti',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'documents_spacing',
            [
                'label' => 'Spazio tra Documenti',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 10,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 30,
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-document-wrapper' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .elementor-document-wrapper:last-child' => 'margin-bottom: 0;',
                ],
            ]
        );

        $this->end_controls_section();
    }

protected function render() {
    $settings = $this->get_settings_for_display();
    $current_page_id = get_the_ID();

    // Modifica la query in base alla selezione
    $args = array(
        'post_type' => 'documento',
        'posts_per_page' => -1,
        'orderby' => 'meta_value_num',
        'meta_key' => '_document_order',
        'order' => 'ASC'
    );
    
    // Se sono stati selezionati documenti specifici
    if (!empty($settings['selected_documents'])) {
        $args['post__in'] = $settings['selected_documents'];
    } else {
        // Altrimenti usa il filtro per pagina standard
        $args['meta_query'] = array(
            array(
                'key' => '_target_page',
                'value' => $current_page_id,
                'compare' => '='
            )
        );
    }

    $documents = get_posts($args);
    
    foreach ($documents as $doc) {
        $doc_url = get_post_meta($doc->ID, '_document_url', true);
        if ($doc_url) {
            $this->render_document_button($doc, $doc_url);
        }
    }
}

protected function render_document_button($doc, $doc_url) {
    $settings = $this->get_settings_for_display();
    $display_title = get_post_meta($doc->ID, '_document_display_title', true);
    $description = get_post_meta($doc->ID, '_document_description', true);
    $file_extension = pathinfo($doc_url, PATHINFO_EXTENSION);
    $icon_svg = $this->get_icon_svg($settings['icon_type']);

    // Fix the document URL if needed
    $doc_url = $this->ensure_absolute_url($doc_url);
    ?>
    <div class="elementor-document-wrapper">
        <?php if (!empty($display_title)): ?>
            <h4 class="document-title"><?php echo esc_html($display_title); ?></h4>
        <?php endif; ?>
        
        <?php if (!empty($description)): ?>
            <div class="document-description"><?php echo wp_kses_post($description); ?></div>
        <?php endif; ?>
        
        <div class="elementor-element elementor-widget elementor-widget-button">
            <div class="elementor-widget-container">
                <div class="elementor-button-wrapper">
                    <a class="elementor-button elementor-button-link elementor-size-lg" 
                       href="<?php echo esc_url($doc_url); ?>" 
                       target="_blank">
                        <span class="elementor-button-content-wrapper">
                            <?php if ($settings['icon_type'] !== 'none'): ?>
                                <span class="elementor-button-icon">
                                    <?php echo $icon_svg; ?>
                                </span>
                            <?php endif; ?>
                            <span class="elementor-button-text">
                                <?php echo esc_html($doc->post_title . '.' . $file_extension); ?>
                            </span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
}

private function ensure_absolute_url($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $upload_dir = wp_upload_dir();
        $site_url = get_site_url();
        
        // If it's a relative path, make it absolute
        if (strpos($url, '/') === 0) {
            $url = $site_url . $url;
        } else {
            $url = $upload_dir['baseurl'] . '/' . ltrim($url, '/');
        }
    }
    return $url;
}

   private function get_icon_svg($icon_type) {
        switch ($icon_type) {
            case 'download':
                return '<svg aria-hidden="true" class="e-font-icon-svg" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                    <path d="M216 0h80c13.3 0 24 10.7 24 24v168h87.7c17.8 0 26.7 21.5 14.1 34.1L269.7 378.3c-7.5 7.5-19.8 7.5-27.3 0L90.1 226.1c-12.6-12.6-3.7-34.1 14.1-34.1H192V24c0-13.3 10.7-24 24-24zm296 400v80c0 13.3-10.7 24-24 24H24c-13.3 0-24-10.7-24-24v-80c0-13.3 10.7-24 24-24h146.7l49 49c20.1 20.1 52.5 20.1 72.6 0l49-49H488c13.3 0 24 10.7 24 24zm-124 88c11 0 20-9 20-20s-9-20-20-20-20 9-20 20 9 20 20 20zm64 0c11 0 20-9 20-20s-9-20-20-20-20 9-20 20 9 20 20 20z"></path>
                </svg>';
            case 'file':
                return '<svg aria-hidden="true" class="e-font-icon-svg" viewBox="0 0 384 512" xmlns="http://www.w3.org/2000/svg">
                    <path d="M224 136V0H24C10.7 0 0 10.7 0 24v464c0 13.3 10.7 24 24 24h336c13.3 0 24-10.7 24-24V160H248c-13.2 0-24-10.8-24-24zm160-14.1v6.1H256V0h6.1c6.4 0 12.5 2.5 17 7l97.9 98c4.5 4.5 7 10.6 7 16.9z"></path>
                </svg>';
            case 'pdf':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M320 464C328.8 464 336 456.8 336 448V416H384V448C384 483.3 355.3 512 320 512H64C28.65 512 0 483.3 0 448V416H48V448C48 456.8 55.16 464 64 464H320zM256 160C256 142.3 270.3 128 288 128H336V0H88C39.4 0 0 39.4 0 88V416H48V88C48 66.36 65.36 48 88 48H288V128H256V160zM384 128H336V192H384V128z"/></svg>';
            case 'excel':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M320 464C328.8 464 336 456.8 336 448V416H384V448C384 483.3 355.3 512 320 512H64C28.65 512 0 483.3 0 448V416H48V448C48 456.8 55.16 464 64 464H320zM208 320H256V288H208V320zM128 320H176V288H128V320zM208 384H256V352H208V384zM128 384H176V352H128V384z"/></svg>';
            default: // paperclip
                return '<svg aria-hidden="true" class="e-font-icon-svg" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                    <path d="M43.246 466.142c-58.43-60.289-57.341-157.511 1.386-217.581L254.392 34c44.316-45.332 116.351-45.336 160.671 0 43.89 44.894 43.943 117.329 0 162.276L232.214 383.128c-29.855 30.537-78.633 30.111-107.982-.998-28.275-29.97-27.368-77.473 1.452-106.953l143.743-146.835c6.182-6.314 16.312-6.422 22.626-.241l22.861 22.379c6.315 6.182 6.422 16.312.241 22.626L171.427 319.927c-4.932 5.045-5.236 13.428-.648 18.292 4.372 4.634 11.245 4.711 15.688.165l182.849-186.851c19.613-20.062 19.613-52.725-.011-72.798-19.189-19.627-49.957-19.637-69.154 0L90.39 293.295c-34.763 35.56-35.299 93.12-1.191 128.313 34.01 35.093 88.985 35.137 123.058.286l172.06-175.999c6.177-6.319 16.307-6.433 22.626-.256l22.877 22.364c6.319 6.177 6.434 16.307.256 22.626l-172.06 175.998c-59.576 60.938-155.943 60.216-214.77-.485z"></path>
                </svg>';
        }
    }
}