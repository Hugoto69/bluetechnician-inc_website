<tr>
    <th scope="row"> <?php _e( 'All Languages', 'translatepress-multilingual' ) ?> </th>
    <td>
        <table id="trp-languages-table">
            <thead>
                <tr>
                    <th colspan="2"><?php _e( 'Language', 'translatepress-multilingual' ); ?></th>
                    <th><?php _e( 'Code', 'translatepress-multilingual' ); ?></th>
                    <th><?php _e( 'Slug', 'translatepress-multilingual' ); ?></th>
                    <th><?php _e( 'Active', 'translatepress-multilingual' ); ?></th>
                </tr>
            </thead>
            <tbody id="trp-sortable-languages">

            <?php foreach ( $this->settings['translation-languages'] as $selected_language_code ){
                $default_language = ( $selected_language_code == $this->settings['default-language'] );?>
                <tr class="trp-language">
                    <td><span class="trp-sortable-handle"></span></td>
                    <td>
                        <select name="trp_settings[translation-languages][]" class="trp-select2 trp-translation-language" <?php echo ( $default_language ) ? 'disabled' : '' ?>>
	                        <?php foreach( $languages as $language_code => $language_name ){ ?>
                                <option title="<?php echo $language_code; ?>" value="<?php echo $language_code; ?>" <?php echo ( $language_code == $selected_language_code ) ? 'selected' : ''; ?>>
			                        <?php echo ( $default_language ) ? 'Default: ' : ''; ?>
			                        <?php echo $language_name; ?>
                                </option>
	                        <?php }?>
                        </select>
                    </td>
                    <td>
                        <input class="trp-language-code trp-code-slug" type="text" disabled value="<?php echo esc_html( $selected_language_code ); ?>">
                    </td>
                    <td>
                        <input class="trp-language-slug  trp-code-slug" name="trp_settings[url-slugs][<?php echo $selected_language_code ?>]" type="text" style="text-transform: lowercase;" value="<?php echo $this->url_converter->get_url_slug( $selected_language_code, false ); ?>">
                    </td>
                    <td align="center">
                        <input type="checkbox" class="trp-translation-published" name="trp_settings[publish-languages][]" value="<?php echo $selected_language_code; ?>" <?php echo ( in_array( $selected_language_code, $this->settings['publish-languages'] ) ) ? 'checked ' : ''; echo ( $default_language ) ? 'disabled ' : ''; ?> />
                        <?php if ( $default_language ) { ?>
                                <input type="hidden" class="trp-hidden-default-language" name="trp_settings[translation-languages][]" value="<?php echo $selected_language_code;?>" />
                                <input type="hidden" class="trp-hidden-default-language" name="trp_settings[publish-languages][]" value="<?php echo $selected_language_code;?>" />
                        <?php } ?>
                    </td>
                    <td>
                        <a class="trp-remove-language" style=" <?php echo ( $default_language ) ? 'display:none' : '' ?>" data-confirm-message="<?php _e( 'Are you sure you want to remove this language?', 'translatepress-multilingual' ); ?>"><?php _e( 'Remove', 'translatepress-multilingual' ); ?></a>
                    </td>
                </tr>
            <?php }?>
            </tbody>
        </table>
        <div id="trp-new-language">
            <select id="trp-select-language" class="trp-select2 trp-translation-language" >
                <option value=""><?php _e( 'Choose...', 'translatepress-multilingual' );?></option>
                <?php foreach( $languages as $language_code => $language_name ){ ?>
                    <option title="<?php echo $language_code; ?>" value="<?php echo $language_code; ?>">
                        <?php echo $language_name; ?>
                    </option>
                <?php }?>
            </select>
            <button type="button" id="trp-add-language" class="button-secondary"><?php _e( 'Add', 'translatepress-multilingual' );?></button>
        </div>
        <p class="description">
            <?php _e( 'Select the languages you wish to make your website available in.', 'translatepress-multilingual' ); ?>
        </p>
    </td>
</tr>
