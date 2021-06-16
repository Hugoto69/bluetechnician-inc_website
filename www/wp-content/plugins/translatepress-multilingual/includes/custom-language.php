<?php
add_image_size( 'trp-custom-language-flag', 16, 12 );

// Register country flag size for use in Add Media modal
add_filter( 'image_size_names_choose', 'trp_add_flag_sizes' );
function trp_add_flag_sizes( $sizes ) {
	return array_merge( $sizes, array(
		'trp-custom-language-flag' => __( 'Custom Language Flag' )
	) );
}

add_filter( 'trp_wp_languages', 'trpc_add_custom_language', 10, 2 );
function trpc_add_custom_language( $languages ) {

	$option = get_option( 'trp_advanced_settings', true );

	if ( isset( $option['custom_language'] ) ) {
		//print_r($option['custom_language'];

		foreach ( $option['custom_language']['cuslangname'] as $key => $value ) {

			$lang = $option["custom_language"]["cuslangiso"][ $key ];
			if ( array_key_exists( $lang, $languages ) ) {
				return $languages;
			}

			$custom_language_iso    = $option["custom_language"]["cuslangiso"][ $key ];
			$custom_language_name   = $option["custom_language"]["cuslangname"][ $key ];
			$custom_language_native = $option["custom_language"]["cuslangnative"][ $key ];
			$custom_language_slug   = $option["custom_language"]["cuslangslug"][ $key ];

			$languages[ $lang ] = array(
				'language'     => $lang,
				'english_name' => $custom_language_name,
				'native_name'  => $custom_language_native,
				'iso'          => array( $custom_language_slug )
			);

			global $TRP_LANGUAGE;

			if ( isset( $option["cuslangisrtl"] ) && $option["cuslangisrtl"] === 'yes' && $TRP_LANGUAGE === $custom_language_iso ) {
				$GLOBALS['text_direction'] = 'rtl';
			}
		}
	}

	return $languages;
}

add_filter('gettext_with_context', 'trpc_language_rtl', 10, 4);
function trpc_language_rtl($translated, $text, $context, $domain){
	$option = get_option( 'trp_advanced_settings', true );
	global $TRP_LANGUAGE;

	if ( isset( $option['custom_language'] ) ) {
		foreach ( $option['custom_language']['cuslangname'] as $key => $value ) {
			$custom_language_iso = $option["custom_language"]["cuslangiso"][$key];
			if($text == 'ltr' && $context == "text direction" && isset($option["custom_language"]["cuslangisrtl"][0]) && $option["custom_language"]["cuslangisrtl"][0] === 'yes' && $TRP_LANGUAGE === $custom_language_iso){
				$translated = 'rtl';
			}
		}
	}
	return $translated;
}

add_filter( 'trp_flags_path', 'trpc_flags_path_custom', 10, 2 );
/**
 * @param $original_flags_path
 * @param $language_code
 *
 * @return mixed
 *
 * Returns the original flags path for original languages
 * Or the custom flag path for flags uploaded into the media library
 * The image is returned resized to the custom size dictated bu trp-custom-language-flag
 *
 */
function trpc_flags_path_custom( $original_flags_path,  $language_code ) {

	// only change the folder path for the custom languages:
	$option = get_option( 'trp_advanced_settings', true );

	if ( isset( $option['custom_language'] ) ) {
		foreach ( $option['custom_language']['cuslangname'] as $key => $value ) {
			if ($language_code === $option["custom_language"]["cuslangiso"][$key] ) {
				$attachment_array = wp_get_attachment_image_src(attachment_url_to_postid($option["custom_language"]["cuslangflag"][ $key ]), 'trp-custom-language-flag');
				return isset($attachment_array) ? $attachment_array[0] : $option["custom_language"]["cuslangflag"][ $key ];
			}
		}
	}
	return $original_flags_path;
}


add_filter( 'trp_flag_file_name', 'trpc_flag_name_custom', 10, 2 );
/**
 * @param $original_flags_path
 * @param $language_code
 *
 * @return string
 *
 * For the custom languages the flag name is contained into the flag path
 * it does not follow the naming pattern language.png
 * So no need to return anything in that case
 */
function trpc_flag_name_custom ( $original_flags_path,  $language_code ){
	// only change flag name for the custom languages:
	$option = get_option( 'trp_advanced_settings', true );
	if ( isset( $option['custom_language'] ) ) {
		foreach ( $option['custom_language']['cuslangname'] as $key => $value ) {
			if ($language_code === $option["custom_language"]["cuslangiso"][$key] ) {
				return '';
			}
		}
	}
	return $original_flags_path;
}

