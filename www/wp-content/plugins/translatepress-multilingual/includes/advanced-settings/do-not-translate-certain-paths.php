<?php

add_filter( 'trp_register_advanced_settings', 'trp_register_do_not_translate_certain_paths', 120 );
function trp_register_do_not_translate_certain_paths( $settings_array ){

    $settings_array[] = array(
        'type'        => 'custom',
        'name'        => 'translateable_content',
        'rows'        => array( 'option' => 'radio', 'paths' => 'textarea' ),
        'label'       => esc_html__( 'Do not translate certain paths', 'translatepress-multilingual' ),
        'description' => wp_kses(  __( 'Choose what paths can be translated. Supports wildcard at the end of the path.<br>For example, to exclude https://example.com/some/path you can either use the rule /some/path/ or /some/*.<br>Enter each rule on it\'s own line. To exclude the home page use {{home}}.', 'translatepress-multilingual' ), array( 'br' => array() )),
    );

	return $settings_array;

}

add_filter( 'trp_advanced_setting_custom_translateable_content', 'trp_output_do_not_translate_certain_paths' );
function trp_output_do_not_translate_certain_paths( $setting ){

    $trp_settings = ( new TRP_Settings() )->get_settings();

    ?>
    <tr id="trp-adv-translate-certain-paths">
        <th scope="row"><?php echo $setting['label']; ?></th>
        <td>
            <div class="trp-adv-holder">
                <label>
                    <input type='radio' id='$setting_name' name="trp_advanced_settings[<?php echo $setting['name']; ?>][option]" value="exclude" <?php echo isset( $trp_settings['trp_advanced_settings'][$setting['name']]['option'] ) && $trp_settings['trp_advanced_settings'][$setting['name']]['option'] == 'exclude' ? 'checked' : ''; ?>>
                    <?php _e( 'Exclude Paths From Translation', 'translatepress' ); ?>
                </label>

                <label>
                    <input type='radio' id='$setting_name' name="trp_advanced_settings[<?php echo $setting['name']; ?>][option]" value="include" <?php echo isset( $trp_settings['trp_advanced_settings'][$setting['name']]['option'] ) && $trp_settings['trp_advanced_settings'][$setting['name']]['option'] == 'include' ? 'checked' : ''; ?> >
                    <?php _e( 'Translate Only Certain Paths', 'translatepress' ); ?>
                </label>
            </div>

            <textarea class="trp-adv-big-textarea" name="trp_advanced_settings[<?php echo $setting['name']; ?>][paths]"><?php echo isset( $trp_settings['trp_advanced_settings'][$setting['name']]['paths'] ) ? $trp_settings['trp_advanced_settings'][$setting['name']]['paths'] : ''; ?></textarea>

            <p class="description"><?php echo $setting['description']; ?></p>
        </td>
    </tr>


    <?php
    return;
}

// Prevent TranslatePress from loading on excluded pages
add_action( 'trp_allow_tp_to_run', 'trp_exclude_include_paths_to_run_on', 2 );
function trp_exclude_include_paths_to_run_on(){

    if( is_admin() )
        return true;

    if( isset( $_GET['trp-edit-translation'] ) && ( $_GET['trp-edit-translation'] == 'true' || $_GET['trp-edit-translation'] == 'preview' ) )
        return true;

    if( isset( $_GET['trp-string-translation'] ) && $_GET['trp-string-translation'] == 'true' )
        return true;

    $settings          = get_option( 'trp_settings', false );
    $advanced_settings = get_option( 'trp_advanced_settings', false );

    if( empty( $advanced_settings ) || !isset( $advanced_settings['translateable_content'] ) || !isset( $advanced_settings['translateable_content']['option'] ) || empty( $advanced_settings['translateable_content']['paths'] ) )
        return true;

    $trp           = TRP_Translate_Press::get_trp_instance();
    $url_converter = $trp->get_component('url_converter');
    $current_lang  = $url_converter->get_lang_from_url_string( $url_converter->cur_page_url() );

    if( empty( $current_lang ) )
        $current_lang = $settings['default-language'];

    if ( $url_converter->is_sitemap_path() )
        return true;

    // Skip checks if this is not the default language
    if( !empty( $current_lang ) && $settings['default-language'] != $current_lang )
        return true;

    $paths        = explode("\n", str_replace("\r", "", $advanced_settings['translateable_content']['paths'] ) );
    $current_slug = sanitize_text_field( $_SERVER['REQUEST_URI'] );

    $replace = '/';

    if( isset( $settings['add-subdirectory-to-default-language'] ) && $settings['add-subdirectory-to-default-language'] == 'yes' )
        $replace .= $settings['url-slugs'][$current_lang];

    $current_slug = str_replace( $replace, '', untrailingslashit( $current_slug ) );

    // Explode get params
    $current_slug = explode( '?', $current_slug );

    if( isset( $current_slug[1] ) ){
        $current_get  = $current_slug[1];
        $current_slug = $current_slug[0];
    } else {
        $current_slug = $current_slug[0];
    }

    if( empty( $current_slug ) || $current_slug == '/' )
        $current_slug = "{{home}}";
    else
        $current_slug = '/' . ltrim( $current_slug, '/' );

    if( $advanced_settings['translateable_content']['option'] == 'exclude' ){

        foreach( $paths as $path ){

            if( !empty( $path ) ){

                if( untrailingslashit( $current_slug ) == untrailingslashit( $path ) || ( strpos( $path, '*' ) !== false && strpos( untrailingslashit( $current_slug ), str_replace( '/*', '', $path ) ) !== false ) )
                    return false;

            }
        }

    } else if( $advanced_settings['translateable_content']['option'] == 'include' ){

        foreach( $paths as $path ){

            if( !empty( $path ) ){
                if( untrailingslashit( $current_slug ) == untrailingslashit( $path ) || ( strpos( $path, '*' ) !== false && strpos( untrailingslashit( $current_slug ), str_replace( '/*', '', $path ) ) !== false ) )
                    return true;
            }

        }

        return false;

    }

    return true;

}

add_filter( 'trp_allow_language_redirect', 'trp_exclude_include_do_not_redirect_on_excluded_pages', 20, 3 );
function trp_exclude_include_do_not_redirect_on_excluded_pages( $redirect, $language, $url ){

    if( isset( $_GET['trp-edit-translation'] ) && ( $_GET['trp-edit-translation'] == 'true' || $_GET['trp-edit-translation'] == 'preview' ) )
        return $redirect;

    if( isset( $_GET['trp-string-translation'] ) && $_GET['trp-string-translation'] == 'true' )
        return $redirect;

    $settings          = get_option( 'trp_settings', false );
    $advanced_settings = get_option( 'trp_advanced_settings', false );

    if( empty( $advanced_settings ) || !isset( $advanced_settings['translateable_content'] ) || !isset( $advanced_settings['translateable_content']['option'] ) || empty( $advanced_settings['translateable_content']['paths'] ) )
        return $redirect;

    if( empty( $language ) || $language != $settings['default-language'] )
        return $redirect;

    $replace = trailingslashit( home_url() );

    $current_slug = str_replace( $replace, '', trailingslashit( $url ) );
    $paths        = explode("\n", str_replace("\r", "", $advanced_settings['translateable_content']['paths'] ) );

    // Explode get params
    $current_slug = explode( '?', $current_slug );

    if( isset( $current_slug[1] ) ){
        $current_get  = $current_slug[1];
        $current_slug = $current_slug[0];
    } else {
        $current_slug = $current_slug[0];
    }

    if( empty( $current_slug ) || $current_slug == '/' )
        $current_slug = "{{home}}";
    else
        $current_slug = '/' . ltrim( $current_slug, '/' );

    if( $advanced_settings['translateable_content']['option'] == 'exclude' ){

        foreach( $paths as $path ){

            if( !empty( $path ) ){

                if( untrailingslashit( $current_slug ) == untrailingslashit( $path ) || ( strpos( $path, '*' ) !== false && strpos( untrailingslashit( $current_slug ), str_replace( '/*', '', $path ) ) !== false ) )
                    return false;

            }
        }

    } else if( $advanced_settings['translateable_content']['option'] == 'include' ){

        foreach( $paths as $path ){

            if( !empty( $path ) ){
                if( untrailingslashit( $current_slug ) == untrailingslashit( $path ) || ( strpos( $path, '*' ) !== false && strpos( untrailingslashit( $current_slug ), str_replace( '/*', '', $path ) ) !== false ) )
                    return $redirect;
            }

        }

        return false;

    }

    return $redirect;

}

add_action( 'init', 'trp_exclude_include_redirect_to_default_language', 1 );
function trp_exclude_include_redirect_to_default_language(){

    if( isset( $_GET['trp-edit-translation'] ) && ( $_GET['trp-edit-translation'] == 'true' || $_GET['trp-edit-translation'] == 'preview' ) )
        return;

    if( isset( $_GET['trp-string-translation'] ) && $_GET['trp-string-translation'] == 'true' )
        return;

    if( is_admin() )
        return;

    $settings          = get_option( 'trp_settings', false );
    $advanced_settings = get_option( 'trp_advanced_settings', false );

    if( empty( $advanced_settings ) || !isset( $advanced_settings['translateable_content'] ) || !isset( $advanced_settings['translateable_content']['option'] ) || empty( $advanced_settings['translateable_content']['paths'] ) )
        return;

    global $TRP_LANGUAGE;
    $trp           = TRP_Translate_Press::get_trp_instance();
    $url_converter = $trp->get_component('url_converter');

    $current_original_url = $url_converter->get_url_for_language( $settings['default-language'], null, '' );

    // Attempt to redirect on default language only if the current URL contains the language
    if( !isset( $TRP_LANGUAGE ) || $settings['default-language'] == $TRP_LANGUAGE ){

        if( $url_converter->get_lang_from_url_string( $current_original_url ) === null )
            return;

    }

    $absolute_home = $url_converter->get_abs_home();

    // Take into account the subdirectory for default language option
    if ( isset( $settings['add-subdirectory-to-default-language'] ) && $settings['add-subdirectory-to-default-language'] == 'yes' )
        $absolute_home = trailingslashit( $absolute_home ) . $settings['url-slugs'][$settings['default-language']];

    $current_slug = str_replace( $absolute_home, '', untrailingslashit( $current_original_url ) );
    $paths        = explode("\n", str_replace("\r", "", $advanced_settings['translateable_content']['paths'] ) );

    // Remove language from this URL if present
    $current_original_url = str_replace( '/' . $settings['url-slugs'][$settings['default-language']], '', $current_original_url );

    // Explode get params
    $current_slug = explode( '?', $current_slug );

    if( isset( $current_slug[1] ) ){
        $current_get  = $current_slug[1];
        $current_slug = $current_slug[0];
    } else {
        $current_slug = $current_slug[0];
    }

    if( empty( $current_slug ) || $current_slug == '/' )
        $current_slug = "{{home}}";

    if( $advanced_settings['translateable_content']['option'] == 'exclude' ){

        foreach( $paths as $path ){

            if( !empty( $path ) ){

                if( untrailingslashit( $current_slug ) == untrailingslashit( $path ) || ( strpos( $path, '*' ) !== false && strpos( untrailingslashit( $current_slug ), str_replace( '/*', '', $path ) ) !== false ) ){

                    if( $url_converter->cur_page_url() != $current_original_url ){
                        wp_redirect( $current_original_url, 301 );
                        exit;
                    }

                }

            }
        }

    } else if( $advanced_settings['translateable_content']['option'] == 'include' ){

        foreach( $paths as $path ){

            if( !empty( $path ) ){
                if( untrailingslashit( $current_slug ) == untrailingslashit( $path ) || ( strpos( $path, '*' ) !== false && strpos( untrailingslashit( $current_slug ), str_replace( '/*', '', $path ) ) !== false ) )
                    return;
            }

        }

        if( $url_converter->cur_page_url() != $current_original_url ){
            wp_redirect( $current_original_url, 301 );
            exit;
        }

    }

}

// only force custom links in paths that are translatable
add_filter( 'trp_force_custom_links', 'trp_exclude_include_filter_custom_links', 10, 4);
function trp_exclude_include_filter_custom_links( $new_url, $url, $TRP_LANGUAGE, $a_href ){

    if( isset( $_GET['trp-edit-translation'] ) && ( $_GET['trp-edit-translation'] == 'true' || $_GET['trp-edit-translation'] == 'preview' ) )
        return $new_url;

    if( isset( $_GET['trp-string-translation'] ) && $_GET['trp-string-translation'] == 'true' )
        return $new_url;

    $advanced_settings = get_option( 'trp_advanced_settings', false );
    $settings          = get_option( 'trp_settings', false );

    if( empty( $advanced_settings ) || !isset( $advanced_settings['translateable_content'] ) || !isset( $advanced_settings['translateable_content']['option'] ) || empty( $advanced_settings['translateable_content']['paths'] ) )
        return $new_url;

    global $TRP_LANGUAGE;
    $trp           = TRP_Translate_Press::get_trp_instance();
    $url_converter = $trp->get_component('url_converter');

    if( !isset( $TRP_LANGUAGE ) || $settings['default-language'] == $TRP_LANGUAGE )
        return;

    $current_original_url = $url_converter->get_url_for_language( $settings['default-language'], $new_url, '' );

    // Remove language from this URL if present
    $current_original_url = str_replace( '/' . $settings['url-slugs'][$settings['default-language']], '', $current_original_url );

    $absolute_home        = $url_converter->get_abs_home();

    $current_slug = str_replace( $absolute_home, '', untrailingslashit( $current_original_url ) );
    $paths        = explode("\n", str_replace("\r", "", $advanced_settings['translateable_content']['paths'] ) );

    // Explode get params
    $current_slug = explode( '?', $current_slug );

    if( isset( $current_slug[1] ) ){
        $current_get  = $current_slug[1];
        $current_slug = $current_slug[0];
    } else {
        $current_slug = $current_slug[0];
    }

    if( empty( $current_slug ) || $current_slug == '/' )
        $current_slug = "{{home}}";

    if( $advanced_settings['translateable_content']['option'] == 'exclude' ){

        foreach( $paths as $path ){

            if( !empty( $path ) ){
                if( untrailingslashit( $current_slug ) == untrailingslashit( $path ) || ( strpos( $path, '*' ) !== false && strpos( untrailingslashit( $current_slug ), str_replace( '/*', '', $path ) ) !== false ) )
                    return $current_original_url;
            }

        }

    } else if( $advanced_settings['translateable_content']['option'] == 'include' ){

        foreach( $paths as $path ){

            if( !empty( $path ) ){
                if( untrailingslashit( $current_slug ) == untrailingslashit( $path ) || ( strpos( $path, '*' ) !== false && strpos( untrailingslashit( $current_slug ), str_replace( '/*', '', $path ) ) !== false ) )
                    return $new_url;
            }

        }

        return $current_original_url;

    }

    return $new_url;

}

add_action( 'init', 'trp_exclude_include_add_sitemap_filter' );
function trp_exclude_include_add_sitemap_filter(){
    if( defined( 'TRP_SP_PLUGIN_VERSION' ) && version_compare( TRP_SP_PLUGIN_VERSION, '1.3.6', '>=' ) )
        add_filter( 'trp_xml_sitemap_output_for_url', 'trp_exclude_include_filter_sitemap_links', 10, 6 );
}

function trp_exclude_include_filter_sitemap_links( $new_output, $output, $settings, $alternate, $all_lang_urls, $url ){

    $advanced_settings = get_option( 'trp_advanced_settings', false );
    $settings          = get_option( 'trp_settings', false );

    if( empty( $advanced_settings ) || !isset( $advanced_settings['translateable_content'] ) || !isset( $advanced_settings['translateable_content']['option'] ) || empty( $advanced_settings['translateable_content']['paths'] ) )
        return $new_output;

    global $TRP_LANGUAGE;
    $trp           = TRP_Translate_Press::get_trp_instance();
    $url_converter = $trp->get_component('url_converter');

    if( empty( $url['loc'] ) )
        return $new_output;

    $current_original_url = $url_converter->get_url_for_language( $settings['default-language'], $url['loc'], '' );
    $absolute_home        = $url_converter->get_abs_home();

    $current_slug = str_replace( $absolute_home, '', untrailingslashit( $current_original_url ) );
    $paths        = explode("\n", str_replace("\r", "", $advanced_settings['translateable_content']['paths'] ) );

    // Explode get params
    $current_slug = explode( '?', $current_slug );

    if( isset( $current_slug[1] ) ){
        $current_get  = $current_slug[1];
        $current_slug = $current_slug[0];
    } else {
        $current_slug = $current_slug[0];
    }

    if( empty( $current_slug ) || $current_slug == '/' )
        $current_slug = "{{home}}";

    if( $advanced_settings['translateable_content']['option'] == 'exclude' ){

        foreach( $paths as $path ){

            if( !empty( $path ) ){
                if( untrailingslashit( $current_slug ) == untrailingslashit( $path ) || ( strpos( $path, '*' ) !== false && strpos( untrailingslashit( $current_slug ), str_replace( '/*', '', $path ) ) !== false ) )
                    return $output;
            }

        }

    } else if( $advanced_settings['translateable_content']['option'] == 'include' ){

        foreach( $paths as $path ){

            if( !empty( $path ) ){
                if( untrailingslashit( $current_slug ) == untrailingslashit( $path ) || ( strpos( $path, '*' ) !== false && strpos( untrailingslashit( $current_slug ), str_replace( '/*', '', $path ) ) !== false ) )
                    return $new_output;
            }

        }

        return $output;

    }

    return $new_output;

}
