<?php

add_filter('trp_register_advanced_settings', 'trp_register_custom_language', 2285);
/*
 * To use the 'mixed' type for advanced settings, there needs to be specified the type of the control
 * There are 4 options to choose from:
 * text: simple textbox
 * textarea: classic textarea used in TP advanced options
 * select: a dropdown select box with the possible options set in a sub-array
 *  like 'option_name'   => array ('label'=> esc_html__( 'Option label', 'translatepress-multilingual' ), 'type' => 'select', 'values' => array ( __('Volvo','translatepress-multilingual') , __('Saab', 'translatepress-multilingual'), __('Scania', 'translatepress-multilingual') ) ),
 *
 *
 * checkbox: a classic checkbox with the checked value always set to 'yes' and the unchecked value to empty.
 * For the elements that don't require pre-determined values, leave the 'values' array empty
 *
 */
function trp_register_custom_language($settings_array){

	$settings_array[] = array(
		'name'          => 'custom_language',
		'columns'       => array (
							'cuslangname' => array ('label' => esc_html__( 'Language name', 'translatepress-multilingual' ), 'type' => 'text', 'values' => '' ),
							'cuslangnative' => array ('label' => esc_html__( 'Native name', 'translatepress-multilingual' ), 'type' => 'text', 'values' => '' ),
							'cuslangiso' => array ('label' => esc_html__( 'ISO code', 'translatepress-multilingual' ), 'type' => 'text', 'values' => '' ),
							'cuslangslug' => array ('label' => esc_html__( 'URL slug', 'translatepress-multilingual' ), 'type' => 'text', 'values' => '' ),
							'cuslangflag' => array ('label' => esc_html__( 'Flag URL', 'translatepress-multilingual' ), 'type' => 'textarea', 'values' => '' ),
							'cuslangisrtl' => array ('label' => esc_html__( 'Text RTL', 'translatepress-multilingual' ), 'type' => 'checkbox', 'values' => '' ),
		),
		'type'          => 'mixed',
		'label'         => esc_html__( 'Custom language', 'translatepress-multilingual' ),
		'description'   => wp_kses(  __( 'Adds custom languages to TranslatePress.<br>Will be available under General settings, All Languages list.<br>For custom flag, first upload the image in media library then paste the URL.<br>Changing or deleting a custom language will impact translations and site URL\'s.', 'translatepress-multilingual' ), array( 'br' => array() )),
	);

    return $settings_array;
}
