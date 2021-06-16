<?php
/* 100% match ms */
defined('ABSPATH') or die("you do not have access to this page!");
if (!class_exists('rsssl_importer')) {
    class rsssl_importer
    {
        private static $_this;

        function __construct()
        {
            if (isset(self::$_this))
                wp_die(sprintf(__('%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl'), get_class($this)));

            self::$_this = $this;

            add_action('wp_ajax_fix_post', array($this, 'fix_post'));
            add_action('wp_ajax_fix_postmeta', array($this, 'fix_postmeta'));
            add_action('wp_ajax_fix_widget', array($this, 'fix_widget'));
            add_action('wp_ajax_ignore_url', array($this, 'ignore_url'));
            add_action('wp_ajax_fix_file', array($this, 'fix_file'));
            add_action('wp_ajax_fix_cssjs', array($this, 'fix_cssjs'));
            add_action('wp_ajax_roll_back', array($this, 'rollback_filechanges'));
            add_action('rsssl_pro_rollback_button', array($this, 'rollback_button'));
        }

        static function this()
        {
            return self::$_this;
        }

	    /**
	     * Show rollback button
	     */
        public function rollback_button()
        {
	        $changed_files = get_option("rsssl_changed_files");
	        if ($changed_files) {
		        ?>
                <button type="button" data-toggle="modal" data-target="#roll-back-modal" class="button button-primary"
                        id="roll-back-file-changes"><?php _e("Roll back file changes", "really-simple-ssl-pro") ?></button>
		        <?php
	        }
        }

	    /**
         * Show message
	     * @param string $msg
	     * @param string $type
	     *
	     * @return string
	     */
        public function alert($msg, $type = "danger")
        {
            $html = '<div id="rsssl-alert" class="alert alert-' . $type . ' alert-dismissible fade in" role="alert">';
            $html .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
            $html .= '<span aria-hidden="true">&times;</span>';
            $html .= '</button>';
            $html .= $msg;
            $html .= '</div>';
            return $html;
        }

	    /**
         * Create backup
         *
	     * @param $filepath
	     */
        public function make_backup($filepath)
        {
            if (!current_user_can('manage_options')) return;
            $filename = basename($filepath);
            //if the backup is already there, do nothing
            if (file_exists(dirname($filepath) . "/" . "rsssl-bkp-" . $filename)) return;

            copy($filepath, dirname($filepath) . "/" . "rsssl-bkp-" . $filename);
            $changed_files = get_option("rsssl_changed_files");
            if (!$changed_files) $changed_files = array();
            $changed_files[$filepath] = 1;
            update_option("rsssl_changed_files", $changed_files);
        }

	    /**
         * change all files back to state before rssssl was used to fix mixed content.
         *
	     * @param $filepath
	     */

        public function rollback_filechanges($filepath)
        {
            $error = $this->alert(__("Someting went wrong. If this doesn't work, you can put the original files back by changing files named 'rsssl-bkp-filename' to filename.", "really-simple-ssl-pro"));
            if (current_user_can('manage_options') && isset($_POST["token"]) && wp_verify_nonce($_POST["token"], "rsssl_fix_post")) {
                $changed_files = get_option("rsssl_changed_files");
                if ($changed_files) {
                    foreach ($changed_files as $filepath => $val) {
                        $filename = basename($filepath);
                        copy(dirname($filepath) . "/" . "rsssl-bkp-" . $filename, $filepath);
                        unlink(dirname($filepath) . "/" . "rsssl-bkp-" . $filename);
                    }

                    update_option("rsssl_changed_files", array());
                    $error = $this->alert(__("Your files were restored.", "really-simple-ssl-pro"), "success");
                } else {
                    $error = $this->alert(__("Your files already were restored.", "really-simple-ssl-pro"));
                }
            }
            header("Content-Type: application/json");
            $response = json_encode(array('success' => false, 'error' => $error));
            echo $response;
            exit;

        }

	    /**
	     *  Fix http references in css and js
	     */

        public function fix_cssjs()
        {
            $error = $this->alert(__("Something went wrong. Please refresh the page and try again, or fix manually.", "really-simple-ssl-pro"));
            $response = json_encode(array('success' => false, 'error' => $error));
            if (current_user_can('manage_options') && isset($_POST["url"]) && isset($_POST["path"]) && isset($_POST["token"]) && wp_verify_nonce($_POST["token"], "rsssl_fix_post")) {
                $path = $_POST["path"];
                $path = trailingslashit(ABSPATH).$this->convert_to_dir($path);
                if (file_exists($path)) {
                    $file_url = $_POST["url"];
                    $content = file_get_contents($path);
                    $this->make_backup($path);
                    $new_url = str_replace("http://", "//", $file_url);
                    $content = str_replace($file_url, $new_url, $content);
                    file_put_contents($path, $content);
                    $this->remove_from_scan_array($file_url);
                    $response = json_encode(array('success' => true));

                } else {
                    $error = $this->alert(__("There was a problem editing the file. Please try manually.", "really-simple-ssl-pro"));
                    $response = json_encode(array('success' => false, 'error' => $error));
                }
            }
            header("Content-Type: application/json");
            echo $response;
            exit;
        }

	    /**
         * Convert URL to directory
	     * @param string $url
	     *
	     * @return string
	     */
        public function convert_to_dir($url)
        {
            return str_replace(home_url(), ABSPATH, $url);
        }

	    /**
	     * Download file, and fix reference to this file
	     */
        public function fix_file()
        {
            $error = $this->alert(__("Something went wrong. Please refresh the page and try again, or fix manually.", "really-simple-ssl-pro"));
            $response = json_encode(array('success' => false, 'error' => $error));
            if (current_user_can('manage_options') && isset($_POST["url"]) && isset($_POST["path"]) && isset($_POST["token"]) && wp_verify_nonce($_POST["token"], "rsssl_fix_post")) {
                $path = $_POST["path"];
                $path = trailingslashit(ABSPATH).$this->convert_to_dir($path);
                if (file_exists($path)) {

                    $file_url = $_POST["url"];
                    $content = file_get_contents($path);
                    $new_url = $this->download_image($file_url, false);

                    if ($new_url != $file_url) {
                        $this->make_backup($path);
                        $content = str_replace($file_url, $new_url, $content);
                        file_put_contents($path, $content);
                        $this->remove_from_scan_array($file_url);
                        $response = json_encode(array('success' => true));
                    }
                } else {
                    $error = $this->alert(__("The file could not be downloaded. It might not exist, or downloading is blocked. Fix manually.", "really-simple-ssl-pro"));
                    $response = json_encode(array('success' => false, 'error' => $error));
                }

            }
            header("Content-Type: application/json");
            echo $response;
            exit;
        }


        /**
         * fix mixed content in post
         *
         * @since  1.0
         *
         * @access public
         *
         */

        public function fix_post()
        {
            $error = $this->alert(__("Something went wrong. Please refresh the page and try again, or fix manually.", "really-simple-ssl-pro"));
            $response = json_encode(array('success' => false, 'error' => $error));

            if (current_user_can('manage_options') && isset($_POST["url"]) && isset($_POST["post_id"]) && isset($_POST["token"]) && wp_verify_nonce($_POST["token"], "rsssl_fix_post")) {
                $post_id = intval($_POST["post_id"]);
                $post = get_post($post_id);

                if ($post) {
                    $file_url = esc_url_raw($_POST["url"]);
                    $content = $post->post_content;
                    //download and insert image into media
                    $new_url = $this->download_image($file_url);

                    if ($new_url != $file_url && $this->is_file($file_url)) {
                        //replace old url with new url
                        $content = str_replace($file_url, $new_url, $content);

                        $updated_post = array(
                            'ID' => $post_id,
                            'post_content' => $content,
                        );
                        wp_update_post($updated_post);
                        $this->remove_from_scan_array($file_url, $post_id);
                        $response = json_encode(array('success' => true));
                    } else {
                        $error = $this->alert(__("The file could not be downloaded. The file might not exist, or downloading is be blocked by the server. Fix manually.", "really-simple-ssl-pro"));
                        $response = json_encode(array('success' => false, 'error' => $error));
                    }
                }
            }

            // response output
            header("Content-Type: application/json");
            echo $response;
            exit;
        }

        /**
         * fix mixed content in postmeta
         *
         * @since  2.1.0
         *
         * @access public
         *
         */

        public function fix_postmeta()
        {
            $error = $this->alert(__("Something went wrong. Please refresh the page and try again, or fix manually.", "really-simple-ssl-pro"));
            $response = json_encode(array('success' => false, 'error' => $error));
            if (current_user_can('manage_options') && isset($_POST["url"]) && isset($_POST["post_id"]) && isset($_POST["token"]) && wp_verify_nonce($_POST["token"], "rsssl_fix_post")) {
                $post_id = intval($_POST["post_id"]);
                $post = get_post($post_id);
                $meta_key = sanitize_title($_POST["path"]);

                if ($post) {
                    $file_url = esc_url_raw($_POST["url"]);
                    $content = get_post_meta($post_id, $meta_key, true);
                    $new_url = $this->download_image($file_url);
                    if ($new_url != $file_url && $this->is_file($file_url)) {
                        $content = str_replace($file_url, $new_url, $content);
                        update_post_meta($post_id, $meta_key, $content);
                        $this->remove_from_scan_array($file_url, $post_id);
                        $response = json_encode(array('success' => true));
                    } else {
                        $error = $this->alert(__("The file could not be downloaded. The file might not exist, or downloading is be blocked by the server. Fix manually.", "really-simple-ssl-pro"));
                        $response = json_encode(array('success' => false, 'error' => $error));
                    }
                }
            }
            header("Content-Type: application/json");
            echo $response;
            exit;
        }

        /**
         * A function to fix widgets
         *
         * @since 1.0
         * @access public
         *
         * */

        public function fix_widget()
        {
            //create default response
            $error = $this->alert(__("Something went wrong. Please refresh the page and try again, or fix manually.", "really-simple-ssl-pro"));
            $response = json_encode(array('success' => false, 'error' => $error));
            if (current_user_can('manage_options') && isset($_POST["url"]) && isset($_POST["widget_id"]) && isset($_POST["token"]) && wp_verify_nonce($_POST["token"], "rsssl_fix_post")) {

	            $widget_title = sanitize_title($_POST["widget_id"]);
                $file_url = sanitize_text_field($_POST["url"]);
                $widget_data = rsssl_scan::this()->get_widget_data($widget_title);

                //download and insert image into media
                $new_url = $this->download_image($file_url);
                if ($new_url != $file_url && $this->is_file($file_url)) {
                    //replace old url with new url
                    $html = str_replace($file_url, $new_url, $widget_data["html"]);
                    rsssl_scan::this()->update_widget_data($widget_title, $html);

                    //now update the scan array as well
                    $this->remove_from_scan_array($file_url, $widget_title);

                    // generate the response
                    $response = json_encode(array('success' => true));
                } else {
                    $error = $this->alert(__("The file could not be downloaded. The file might not exist, or downloading is be blocked by the server. Fix manually.", "really-simple-ssl-pro"));
                    $response = json_encode(array('success' => false, 'error' => $error));
                }
            }
            // response output
            header("Content-Type: application/json");
            echo $response;
            exit;
        }

	    /**
         * load_and_save: if true, changes are saved to DB
         *
	     * @param string $url
	     * @param bool|int $post_id
	     */

        public function remove_from_scan_array($url, $post_id = false)
        {
            rsssl_scan::this()->load_results();

            $css_js_with_mixed_content = rsssl_scan::this()->css_js_with_mixed_content;
            if (!empty($css_js_with_mixed_content)) {
                foreach ($css_js_with_mixed_content as $file => $urls) {
                    $urls = $this->unset_by_value($urls, $url);
                    $css_js_with_mixed_content[$file] = $urls;

                    if (count($css_js_with_mixed_content[$file]) == 0) {
                        unset($css_js_with_mixed_content[$file]);
                    }
                }
                rsssl_scan::this()->css_js_with_mixed_content = $css_js_with_mixed_content;
            }

            $blocked_resources = rsssl_scan::this()->blocked_resources;
            rsssl_scan::this()->blocked_resources = $this->unset_by_value($blocked_resources, $url);

            $files_with_blocked_resources = rsssl_scan::this()->files_with_blocked_resources;
            if (!empty($files_with_blocked_resources)) {
                foreach ($files_with_blocked_resources as $file => $urls) {
                    $urls = $this->unset_by_value($urls, $url);
                    $files_with_blocked_resources[$file] = $urls;

                    if (count($files_with_blocked_resources[$file]) == 0) {
                        unset($files_with_blocked_resources[$file]);
                    }
                    rsssl_scan::this()->files_with_blocked_resources = $files_with_blocked_resources;
                }
            }

            /*
             * posts
             *
             * */
            $posts_with_external_resources = rsssl_scan::this()->posts_with_external_resources;

            //find post id by url:
            if (!$post_id) $post_id = $this->find_post_id_by_url($posts_with_external_resources, $url);
            if ($post_id) {
                if (!empty($posts_with_external_resources) && isset($posts_with_external_resources[$post_id]) ) {
                    $posts_with_external_resources[$post_id] = $this->unset_by_value($posts_with_external_resources[$post_id], $url);

                    if (count($posts_with_external_resources[$post_id]) == 0) {
                        unset($posts_with_external_resources[$post_id]);
                        //only remove this post_id when no other blocked urls are found in this post
                        rsssl_scan::this()->posts_with_blocked_resources = $this->unset_by_value(rsssl_scan::this()->posts_with_blocked_resources, $post_id);
                    }
                    rsssl_scan::this()->posts_with_external_resources = $posts_with_external_resources;
                }
            }

            /*
             * postmeta
             *
             * */

            $postmeta_with_external_resources = rsssl_scan::this()->postmeta_with_external_resources;
            if (!$post_id) $post_id = $this->find_post_id_by_url($postmeta_with_external_resources, $url);
            if ($post_id) {

                if (!empty($postmeta_with_external_resources)) {
                    if (isset($postmeta_with_external_resources[$post_id]) && count($postmeta_with_external_resources[$post_id]) == 0) {
                        unset($postmeta_with_external_resources[$post_id]);
                        //only remove this post_id when no other blocked urls are found in this post
                        rsssl_scan::this()->postmeta_with_blocked_resources = $this->unset_by_value(rsssl_scan::this()->postmeta_with_blocked_resources, $post_id);
                    }
                    rsssl_scan::this()->postmeta_with_external_resources = $postmeta_with_external_resources;
                }
            }

            rsssl_scan::this()->traced_urls = $this->unset_by_value(rsssl_scan::this()->traced_urls, $url);
            rsssl_scan::this()->source_of_resource = $this->unset_by_value(rsssl_scan::this()->source_of_resource, $url);

            //save the data
            rsssl_scan::this()->save_results();
        }


	    /**
         * find the post id by url, by looping through the array
	     * @param array $posts_with_external_resources
	     * @param string $url
	     *
	     * @return bool|int
	     */

        private function find_post_id_by_url($posts_with_external_resources, $url)
        {
            foreach ($posts_with_external_resources as $post_id => $url_array) {
                $key = array_search($url, $url_array);
                if ($key !== false) return $post_id;
            }

            return false;
        }

	    /**
         * Unset item in an array by value
         *
	     * @param $arr
	     * @param $del_val
	     *
	     * @return mixed
	     */
        public function unset_by_value($arr, $del_val)
        {
            if ( !is_array($arr)) return $arr;

            if (($key = array_search($del_val, $arr)) !== false) {
                unset($arr[$key]);
            }
            return $arr;
        }

	    /**
         * Download image,
	     * insert into WP media library when $insert_into_media=true,
	     * and return the new url.
	     * on error, return original url
         *
	     * @param      $filepath
	     * @param bool $insert_into_media
	     *
	     * @return string
	     */

        function download_image($filepath, $insert_into_media = true)
        {
            $filename = basename($filepath);
            $uploads = wp_upload_dir();
            $upload_dir = $uploads['path'];
            $upload_url = $uploads['url'];

            $i = strrpos($filename, ".");
            //if no extension was found, we exit, but return the original file
            if (!$i) return $filepath;
            $l = strlen($filename) - $i;
            $ext = substr($filename, $i + 1, $l);
            $filename_no_ext = basename($filepath, "." . $ext);//substr($filename,0,strlen($filename)-strlen($ext)-1);

            if (!file_exists($upload_dir)) {
                mkdir($upload_dir);
            }

            $safe_title = sanitize_title($filename_no_ext) . "." . $ext;
            //check if this file actually exist
            if (!$this->is_file($filepath)) return $filepath;

            copy($filepath, $upload_dir . "/" . $safe_title);
            $filename_url = $upload_url . "/" . $safe_title;
            $filename_dir = $upload_dir . "/" . $safe_title;

            if ($insert_into_media) {
                $filetype = wp_check_filetype(basename($filename_dir), null);
                $args = array(
                    'guid' => $filename_url,
                    'post_mime_type' => $filetype['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', $filename_no_ext),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );

                $attachment_id = wp_insert_attachment($args, $filename_dir);
                // Generate the metadata for the attachment, and update the database record.
                $attach_data = wp_generate_attachment_metadata($attachment_id, $filename_dir);

                wp_update_attachment_metadata($attachment_id, $attach_data);
            }

            $filename_url = str_replace(array("https://", "http://"), "https://", $filename_url);
            return $filename_url;
        }

	    /**
         * Check if image is valid file
	     * @param $file
	     *
	     * @return bool
	     */
        public function is_file($file)
        {
            $file_headers = @get_headers($file);
            if (!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
                return false;
            } else {
                return true;
            }

        }

	    /**
         * Check if url exists
	     * @param string $url
	     *
	     * @return bool
	     */
        public function url_exists($url)
        {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($code == 200) {
                $status = true;
            } else {
                $status = false;
            }
            curl_close($ch);
            return $status;
        }

	    /**
	     * Ignore a URL
	     */
        public function ignore_url()
        {
            $error = $this->alert(__("Something went wrong.", "really-simple-ssl-pro"));
            $response = json_encode(array('success' => false, 'error' => $error));

            if (current_user_can('manage_options') && isset($_POST["url"]) && isset($_POST["token"]) && wp_verify_nonce($_POST["token"], "rsssl_ignore_url")) {
                $file_url = esc_url_raw($_POST["url"]);
                rsssl_scan::this()->load_results();
                $ignored_urls = rsssl_scan::this()->ignored_urls;
                if (!in_array($file_url, $ignored_urls)) $ignored_urls[] = $file_url;
                rsssl_scan::this()->ignored_urls = $ignored_urls;

	            rsssl_scan::this()->save_results();
                // generate the response
                $response = json_encode(array('success' => true));
            }
            header("Content-Type: application/json");
            echo $response;
            exit;
        }

    }//class closure
}
