<?php defined('ABSPATH') or die("you do not have access to this page!"); ?>
<div id="rsssl-scan-pagination"></div>
<div class="rsssl-scan-options">
    <div class="rsssl-buttons-scan">
        <div class="rsssl-btn-group-scan" role="group" aria-label="...">
            <?php
            //check if the per page plugin is used.
            if (class_exists('REALLY_SIMPLE_SSL_PP')) {?>
                <button type="submit" class="btn btn-primary"  id="rsssl_do_scan" name="rsssl_do_scan"> <?php _e("SCAN", "really-simple-ssl-pro");?></button>
            <?php } else { ?>
                <button type="submit" class="button button-secondary" id="rsssl_do_scan_home" name="rsssl_do_scan_home"><?php _e("Quick Scan", "really-simple-ssl-pro");?></button>
                <button type="submit" class="button button-secondary"  id="rsssl_do_scan" name="rsssl_do_scan"><?php _e("Full Scan", "really-simple-ssl-pro");?></button>
                <?php if (get_option('rsssl_scan_active')){?>
                    <button type="submit" class="button button-rsssl-tertiary" id="rsssl_stop_scan" name="rsssl_stop_scan"><?php _e("Stop scan", "really-simple-ssl-pro");?></button>
                <?php } elseif (get_option('rsssl_progress')>0 && get_option('rsssl_progress')<99) { ?>
                    <button type="submit" class="button button-secondary" id="rsssl_resume_scan" name="rsssl_resume_scan"><?php _e("Resume", "really-simple-ssl-pro");?></button>
                <?php }?>
            <?php } ?>

            <?php do_action("rsssl_pro_rollback_button");?>
            <span class="rsssl-tooltip-left tooltip-left" data-rsssl-tooltip="<?php _e("When you enable this option, the URL's that were previously ignored are shown again.","really-simple-ssl-pro")?>">
               <input type="checkbox" name="rsssl_show_ignore_urls" id="rsssl_show_ignore_urls"  <?php echo (get_option("rsssl_show_ignore_urls")==1) ? 'checked="checked"' : "";?>>
               <?php _e("Show ignored URLs", "really-simple-ssl-pro");?>
            </span>
            <div id="progress-page-count"></div>
        </div>
    </div>
</div>

