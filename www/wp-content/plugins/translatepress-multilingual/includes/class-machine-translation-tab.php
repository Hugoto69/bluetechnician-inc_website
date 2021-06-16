<?php

class TRP_Machine_Translation_Tab {

    private $settings;

    public function __construct( $settings ) {

        $this->settings = $settings;

        add_action( 'plugins_loaded', array( $this, 'add_upsell_filter' ) );

    }

    /*
    * Add new tab to TP settings
    *
    * Hooked to trp_settings_tabs
    */
    public function add_tab_to_navigation( $tabs ){
        $tab = array(
            'name'  => __( 'Automatic Translation', 'translatepress-multilingual' ),
            'url'   => admin_url( 'admin.php?page=trp_machine_translation' ),
            'page'  => 'trp_machine_translation'
        );

        array_splice( $tabs, 2, 0, array( $tab ) );

        return $tabs;
    }

    /*
    * Add submenu for advanced page tab
    *
    * Hooked to admin_menu
    */
    public function add_submenu_page() {
        add_submenu_page( 'TRPHidden', 'TranslatePress Automatic Translation', 'TRPHidden', apply_filters( 'trp_settings_capability', 'manage_options' ), 'trp_machine_translation', array( $this, 'machine_translation_page_content' ) );
        add_submenu_page( 'TRPHidden', 'TranslatePress Test Automatic Translation API', 'TRPHidden', apply_filters( 'trp_settings_capability', 'manage_options' ), 'trp_test_machine_api', array( $this, 'test_api_page_content' ) );
    }

    /**
    * Register setting
    *
    * Hooked to admin_init
    */
    public function register_setting(){
        register_setting( 'trp_machine_translation_settings', 'trp_machine_translation_settings', array( $this, 'sanitize_settings' ) );
    }

    /**
    * Output admin notices after saving settings.
    */
    public function admin_notices(){
        if( isset( $_GET['page'] ) && $_GET['page'] == 'trp_machine_translation' )
            settings_errors();
    }

    /*
    * Sanitize settings
    */
    public function sanitize_settings($mt_settings ){
        if( !empty( $mt_settings['machine-translation'] ) )
            $mt_settings['machine-translation'] = sanitize_text_field( $mt_settings['machine-translation']  );
        else
            $mt_settings['machine-translation'] = 'no';

        if( !empty( $mt_settings['translation-engine'] ) )
            $mt_settings['translation-engine'] = sanitize_text_field( $mt_settings['translation-engine']  );
        else
            $mt_settings['translation-engine'] = 'google_translate_v2';

        if( !empty( $mt_settings['block-crawlers'] ) )
            $mt_settings['block-crawlers'] = sanitize_text_field( $mt_settings['block-crawlers']  );
        else
            $mt_settings['block-crawlers'] = 'no';

        return apply_filters( 'trp_machine_translation_sanitize_settings', $mt_settings );
    }

    /*
    * Automatic Translation
    */
    public function machine_translation_page_content(){
        $trp                       = TRP_Translate_Press::get_trp_instance();

        $machine_translator_logger = $trp->get_component( 'machine_translator_logger' );
        $machine_translator_logger->maybe_reset_counter_date();

        $machine_translator        = $trp->get_component( 'machine_translator' );

        require_once TRP_PLUGIN_DIR . 'partials/machine-translation-settings-page.php';
    }

    /**
    * Test selected API functionality
    */
    public function test_api_page_content(){
        require_once TRP_PLUGIN_DIR . 'partials/test-api-settings-page.php';
    }

    public function load_engines(){
        include_once TRP_PLUGIN_DIR . 'includes/google-translate/functions.php';
        include_once TRP_PLUGIN_DIR . 'includes/google-translate/class-google-translate-v2-machine-translator.php';
    }

    public function get_active_engine( ){
        // This $default is just a fail safe. Should never be used. The real default is set in TRP_Settings->set_options function
        $default = 'TRP_Google_Translate_V2_Machine_Translator';

        if( empty( $this->settings['trp_machine_translation_settings']['translation-engine'] ) )
            $value = $default;
        else {
            $value = 'TRP_' . ucwords( $this->settings['trp_machine_translation_settings']['translation-engine'] ) . '_Machine_Translator'; // class name needs to follow this pattern

            if( !class_exists( $value ) )
                $value = $default;
        }

        return new $value( $this->settings );
    }

    public function add_upsell_filter(){
        if( !class_exists( 'TRP_DeepL' ) )
            add_filter( 'trp_machine_translation_engines', [ $this, 'translation_engines_upsell' ], 20 );
    }

    public function translation_engines_upsell( $engines ){
        $engines[] = array( 'value' => 'deepl_upsell', 'label' => __( 'DeepL', 'translatepress-multilingual' ) );

        return $engines;
    }

    public function display_unsupported_languages(){
        $trp = TRP_Translate_Press::get_trp_instance();
        $machine_translator = $trp->get_component( 'machine_translator' );
        $trp_languages = $trp->get_component( 'languages' );

        if ( 'yes' === $this->settings['trp_machine_translation_settings']['machine-translation'] &&
            !empty( $machine_translator->get_api_key() ) &&
            !$machine_translator->check_languages_availability($this->settings['translation-languages'])
        ){

            $language_names = $trp_languages->get_language_names( $this->settings['translation-languages'], 'english_name' );

            ?>
            <tr id="trp_unsupported_languages">
                <th scope=row><?php esc_html_e( 'Unsupported languages', 'translatepress-multilingual' ); ?></th>
                <td>
                    <ul class="trp-unsupported-languages">
                        <?php
                        foreach ( $this->settings['translation-languages'] as $language_code ) {
                            if ( !$machine_translator->check_languages_availability( array( $language_code ) ) ) {
                                echo '<li>' . $language_names[$language_code] . '</li>';
                            }
                        }
                        ?>
                   </ul>
                  <a href="<?php echo esc_url( admin_url( 'admin.php?page=trp_machine_translation&trp_recheck_supported_languages=1&trp_recheck_supported_languages_nonce=' . wp_create_nonce('trp_recheck_supported_languages') ) ); ?>" class="button-secondary"><?php _e( 'Recheck supported languages', 'translatepress-multilingual' ); ?></a>
                  <p><i><?php echo sprintf( __( '(last checked on %s)', 'translatepress-multilingual' ), esc_html( $machine_translator->get_last_checked_supported_languages() ) ); ?> </i></p>
                   <p class="description">
                       <?php echo wp_kses( __( 'The selected automatic translation engine does not provide support for these languages.<br>You can still manually translate pages in these languages using the Translation Editor.', 'translatepress-multilingual' ), array( 'br' => array() ) ); ?>
                   </p>
                </td>
            </tr>
            <?php
        }
    }
}
