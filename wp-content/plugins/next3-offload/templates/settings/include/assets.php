<?php 
$settings_assets = ($settings_options['assets']) ?? [ 'css_offload' => 'no', 'js_offload' => 'no', 'minify_css' => 'no', 'minify_js' => 'no'];
do_action('next3aws-assets-content-before', $settings_assets);
?>
<div class="next3aws-admin-panel" >
    <form action="" method="post" class="next3-offload-settings-panel" data-type="assets">
        
        <?php do_action('next3aws-assets-content-before', $settings_assets);?>

        <div class="next3aws-admin-toolbox-item <?php echo esc_attr(($assets_status == false) ? 'disabled developer-plan' : '');?>">
            <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('CSS Offload', 'next3-offload');?></h3>
            
            <div class="next3aws-footer-content">
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $css_offload = 'no';
                        if( !empty($settings_assets) ){
                            $css_offload = ($settings_assets['css_offload']) ?? 'no';
                        }
                        if( $assets_status == false ){
                            $css_offload = 'yes';
                        }
                        ?>
                        <label>
                            <input type="checkbox" data-next3-target=".next3_enable_css_offload" <?php echo esc_attr( ($css_offload == 'yes') ? 'checked' : '');?> name="next3settings[assets][css_offload]" id="next3-css_offload" class=""  value="yes">
                            <span><?php echo esc_html__('Enable this option >> offload your CSS files.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content next3_enable_css_offload <?php echo esc_attr( ($css_offload != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Exclude CSS files !offload', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $exclude_css = ['all'];
                        if( !empty($settings_assets) ){
                            $exclude_css = ($settings_assets['exclude_css']) ?? ['all'];
                        }
                        $exclude_css_list = apply_filters('next3/selected/include/css', next3_exclude_css_list());
                        ?>
                       <select name="next3settings[assets][exclude_css][]" class="next3offload-select2" multiple="multiple">
                            <?php
                                foreach($exclude_css_list as $k=>$v){
                                    ?>
                                    <option value="<?php echo esc_attr( $k );?>" <?php echo (in_array( $k, $exclude_css)) ? esc_attr('selected') : '';?>><?php echo esc_html( $v );?></option>
                                    <?php
                                }
                            ?>
                        </select>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content next3_enable_css_offload <?php echo esc_attr( ($css_offload != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Overwrite offloaded files', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $overwrite_css = 'no';
                        if( !empty($settings_assets) ){
                            $overwrite_css = ($settings_assets['overwrite_css']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($overwrite_css == 'yes') ? 'checked' : '');?> name="next3settings[assets][overwrite_css]" id="next3-overwrite_css" class=""  value="yes">
                            <span><?php echo esc_html__('Enable this option > offloaded CSS files overwrite.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content next3_enable_css_offload <?php echo esc_attr( ($css_offload != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Minify CSS files', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $minify_css = 'no';
                        if( !empty($settings_assets) ){
                            $minify_css = ($settings_assets['minify_css']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($minify_css == 'yes') ? 'checked' : '');?> name="next3settings[assets][minify_css]" id="next3-minify_css" class=""  value="yes">
                            <span><?php echo esc_html__('Minify your CSS files in order to reduce their size and the number of requests on the server.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>

            <div class="next3aws-footer-content next3_enable_css_offload <?php echo esc_attr( ($css_offload != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Add object version to bucket path', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $version_css = 'no';
                        if( !empty($settings_assets) ){
                            $version_css = ($settings_assets['version_css']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($version_css == 'yes') ? 'checked' : '');?> name="next3settings[assets][version_css]" id="next3-version_css" class=""  value="yes">
                            <span><?php echo esc_html__('Ensures the latest version of a media item gets delivered by adding a unique timestamp to the bucket path.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>


        </div>
        
        <div class="next3aws-admin-toolbox-item <?php echo esc_attr(($assets_status == false) ? 'disabled developer-plan' : '');?>">
            <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('JS Offload', 'next3-offload');?></h3>
            
            <div class="next3aws-footer-content">
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $js_offload = 'no';
                        if( !empty($settings_assets) ){
                            $js_offload = ($settings_assets['js_offload']) ?? 'no';
                        }
                        if( $assets_status == false ){
                            $js_offload = 'yes';
                        }
                        ?>
                        <label>
                            <input type="checkbox" data-next3-target=".next3_enable_js_offload" <?php echo esc_attr( ($js_offload == 'yes') ? 'checked' : '');?> name="next3settings[assets][js_offload]" id="next3-css_offload" class=""  value="yes">
                            <span><?php echo esc_html__('Enable this option >> offload your JS files.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content next3_enable_js_offload <?php echo esc_attr( ($js_offload != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Exclude JS files !offload', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $exclude_js = ['all'];
                        if( !empty($settings_assets) ){
                            $exclude_js = ($settings_assets['exclude_js']) ?? ['all'];
                        }
                        $include_scripts_list = apply_filters('next3/selected/include/scripts', next3_exclude_css_list('scripts'));
                        ?>
                       <select name="next3settings[assets][exclude_js][]" class="next3offload-select2" multiple="multiple">
                            <?php
                                foreach($include_scripts_list as $k=>$v){
                                    ?>
                                    <option value="<?php echo esc_attr( $k );?>" <?php echo (in_array( $k, $exclude_js)) ? esc_attr('selected') : '';?>><?php echo esc_html( $v );?></option>
                                    <?php
                                }
                            ?>
                        </select>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content next3_enable_js_offload <?php echo esc_attr( ($js_offload != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Overwrite offload files', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $overwrite_js = 'no';
                        if( !empty($settings_assets) ){
                            $overwrite_js = ($settings_assets['overwrite_js']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($overwrite_js == 'yes') ? 'checked' : '');?> name="next3settings[assets][overwrite_js]" id="next3-overwrite_js" class=""  value="yes">
                            <span><?php echo esc_html__('Enable this option > offloaded JS files overwrite.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content next3_enable_js_offload <?php echo esc_attr( ($js_offload != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Minify JS files', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $minify_js = 'no';
                        if( !empty($settings_assets) ){
                            $minify_js = ($settings_assets['minify_js']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($minify_js == 'yes') ? 'checked' : '');?> name="next3settings[assets][minify_js]" id="next3-minify_js" class=""  value="yes">
                            <span><?php echo esc_html__('Minify your CSS files in order to reduce their size and the number of requests on the server.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content next3_enable_js_offload <?php echo esc_attr( ($js_offload != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Add object version to bucket path', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $version_js = 'no';
                        if( !empty($settings_assets) ){
                            $version_js = ($settings_assets['version_js']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($version_js == 'yes') ? 'checked' : '');?> name="next3settings[assets][version_js]" id="next3-version_js" class=""  value="yes">
                            <span><?php echo esc_html__('Ensures the latest version of a media item gets delivered by adding a unique timestamp to the bucket path.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>

        </div>
        
        <div class="next3aws-admin-toolbox-item <?php echo esc_attr(($assets_status == false) ? 'disabled ' : '');?>">
            
            <div class="next3aws-footer-content">
            <h4 for="next3-force_https"><?php echo esc_html__('Remove query strings from static resources', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $version_query = 'no';
                        if( !empty($settings_assets) ){
                            $version_query = ($settings_assets['version_query']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($version_query == 'yes') ? 'checked' : '');?> name="next3settings[assets][version_query]" id="next3-version_query" class=""  value="yes">
                            <span><?php echo esc_html__('Removes version query strings from your static resources improving the caching of those resources.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>
        </div>

        <?php do_action('next3aws-assets-contentdiv-after', $settings_assets);?>

        <div class="next3aws-admin-setting-button border-top">
            <button type="submit" class="full-demo"><?php echo esc_html__('Save Settings', 'next3-offload');?></button>
        </div>
    </form>
</div>
<?php do_action('next3aws-assets-content-after', $settings_assets);?>