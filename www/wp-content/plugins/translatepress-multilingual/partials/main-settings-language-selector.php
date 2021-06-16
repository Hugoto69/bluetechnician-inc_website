<tr>
    <th scope="row"> <?php esc_html_e( 'All Languages', 'translatepress-multilingual' ) ?> </th>
    <td>
        <table id="trp-languages-table">
            <thead>
                <tr>
                    <th colspan="2"><?php esc_html_e( 'Language', 'translatepress-multilingual' ); ?></th>
                    <th><?php esc_html_e( 'Code', 'translatepress-multilingual' ); ?></th>
                    <th><?php esc_html_e( 'Slug', 'translatepress-multilingual' ); ?></th>
                </tr>
            </thead>
            <tbody id="trp-sortable-languages" class="trp-language-selector-limited">

            <?php
            foreach ($this->settings['translation-languages'] as $selected_language_code ){
                $default_language = ( $selected_language_code == $this->settings['default-language'] );?>
                <tr class="trp-language">
                    <td><span class="trp-sortable-handle"></span></td>
                    <td>
                        <select name="trp_settings[translation-languages][]" class="trp-select2 trp-translation-language" <?php echo ( $default_language ) ? 'disabled' : '' ?>>
                            <?php foreach( $languages as $language_code => $language_name ){ ?>
                                <option title="<?php echo esc_attr( $language_code ); ?>" value="<?php echo esc_attr( $language_code ); ?>" <?php echo ( $language_code == $selected_language_code ) ? 'selected' : ''; ?>>
                                    <?php echo ( $default_language ) ? 'Default: ' : ''; ?>
                                    <?php echo esc_html( $language_name ); ?>
                                </option>
                            <?php }?>
                        </select>
                        <input type="hidden" class="trp-translation-published" name="trp_settings[publish-languages][]" value="<?php echo esc_attr( $selected_language_code );?>" />
                        <?php if ( $default_language ) { ?>
                            <input type="hidden" class="trp-hidden-default-language" name="trp_settings[translation-languages][]" value="<?php echo esc_attr( $selected_language_code );?>" />
                        <?php } ?>
                    </td>
                    <td>
                        <input class="trp-language-code trp-code-slug" type="text" disabled value="<?php echo esc_attr( $selected_language_code ); ?>">
                    </td>
                    <td>
                        <input class="trp-language-slug trp-code-slug" name="trp_settings[url-slugs][<?php echo esc_attr( $selected_language_code ); ?>]" type="text" style="text-transform: lowercase;" value="<?php echo esc_attr( $this->url_converter->get_url_slug( $selected_language_code, false ) ); ?>">
                    </td>
                    <td>
                        <a class="trp-remove-language" style=" <?php echo ( $default_language ) ? 'display:none' : '' ?>" data-confirm-message="<?php esc_attr_e( 'Are you sure you want to remove this language?', 'translatepress-multilingual' ); ?>"><?php esc_html_e( 'Remove', 'translatepress-multilingual' ); ?></a>
                    </td>
                </tr>
            <?php }?>
            </tbody>
        </table>
        <div id="trp-new-language">
            <select id="trp-select-language" class="trp-select2 trp-translation-language" >
                <option value=""><?php esc_html_e( 'Choose...', 'translatepress-multilingual' );?></option>
                <?php foreach( $languages as $language_code => $language_name ){ ?>
                    <option title="<?php echo esc_attr( $language_code ); ?>" value="<?php echo esc_attr( $language_code ); ?>">
                        <?php echo esc_html( $language_name ); ?>
                    </option>
                <?php }?>
            </select>
            <button type="button" id="trp-add-language" class="button-secondary"><?php esc_html_e( 'Add', 'translatepress-multilingual' );?></button>
        </div>
        <p class="description">
            <?php
            esc_html_e( 'Select the language you wish to make your website available in.', 'translatepress-multilingual');
            ?>
        </p>
        <p class="trp-upsell-multiple-languages" style="display: none;">
            <?php
            if ( trp_is_paid_version() ){
                $url = 'https://translatepress.com/account/';
                $lnk = sprintf( wp_kses( __( 'To add <strong>more than two languages</strong> download and install the <strong>Extra Languages Add-on</strong> from <a href="%s" class="trp-translatepress-account-page" target="_blank" title="TranslatePress Account page">your account</a>. Once activated, you\'ll be able to add unlimited languages.', 'translatepress-multilingual' ), array( 'strong' => array(), 'br' => array(), 'a' => array( 'href' => array(), 'title' => array(), 'target' => array(), 'class' => array() ) ) ), esc_url( $url ) );
            }else {
                $url = trp_add_affiliate_id_to_link('https://translatepress.com/?utm_source=wpbackend&utm_medium=clientsite&utm_content=multiple_languages&utm_campaign=tpfree');
                $lnk = sprintf(
                    // Translators: %1$s is the URL to the add-ons. %2$2 is for the TranslatePress add-on verbiage.
                    __( 'To add <strong>more than two languages</strong> and support for SEO Title, Description, Slug and more check out <a href="%1$s" target="_blank" title="%2$s">%2$s</a>.', 'translatepress-multilingual' ),
                    esc_url( $url ),
                    _x( 'TranslatePress Advanced Add-ons', 'Verbiage for the TranslatePress Advanced add-ons', 'translatepress-multilingual' )
                );
                $lnk .= '<br/><br />' . __('Not only are you getting extra features and premium support, but you also help fund the future development of TranslatePress.', 'translatepress-multilingual');
                $lnk .= sprintf(
                    '<br /><br />' . '<a href="%1$s" class="button button-primary" target="_blank" title="%2$s">%2$s</a>',
                    esc_url( $url ),
                    _x( 'TranslatePress Advanced Add-ons', 'Link to the TranslatePress add-ons', 'translatepress-multilingual' )
                );
            }
            echo wp_kses_post( $lnk ); // Post kses for more generalized output that is more forgiving and has late escaping.
            ?>
        </p>
    </td>
</tr>
