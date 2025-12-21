<?php 
    $settings_opti = ($settings_options['optimization']) ?? [ 'compression' => 'no', 'compression_level' => 'none', 'backup_orginal' => 'yes'];
    do_action('next3aws-optimization-content-before', $settings_opti);
?>
<div class="next3aws-admin-panel" >
    <form action="" method="post" class="next3-offload-settings-panel" data-type="optimization">
        
        <?php do_action('next3aws-optimization-contentdiv-after', $settings_opti);?>

        <?php if( $status_optimization ){?>
        <div class="next3aws-admin-toolbox-item <?php echo esc_attr(($develeper_status == false) ? 'disabled business-plan' : '');?>">
            <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Image Compression', 'next3-offload');?></h3>
            
            <div class="next3aws-footer-content">
                <!--h4 for="next3-rewrite_urls"><?php echo esc_html__('We will resize your images to decrease the space they occupy and the time needed for each image to load.', 'next3-offload');?></h4-->
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $compression = 'no';
                        if( !empty($settings_opti) ){
                            $compression = ($settings_opti['compression']) ?? 'no';
                        }
                        if( $develeper_status == false ){
                            $compression = 'yes';
                        }
                        ?>
                        <label>
                            <input type="checkbox" data-next3-target=".next3_enable_compression" <?php echo esc_attr( ($compression == 'yes') ? 'checked' : '');?> name="next3settings[optimization][compression]" id="next3-compression" class=""  value="yes">
                            <span><?php echo esc_html__('Enable this option >> media compression process will be start.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content next3_enable_compression <?php echo esc_attr( ($compression != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Back up all original images', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $backup_orginal = 'yes';
                        if( !empty($settings_opti) ){
                            $backup_orginal = ($settings_opti['backup_orginal']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($backup_orginal == 'yes') ? 'checked' : '');?> name="next3settings[optimization][backup_orginal]" id="next3-backup_orginal" class=""  value="yes">
                            <span><?php echo esc_html__('Enable this option for backup your orginal files. ', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>

            <div class="next3aws-footer-content next3_enable_compression <?php echo esc_attr( ($compression != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Compression level', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $compression_level = '0';
                        if( !empty($settings_opti) ){
                            $compression_level = ($settings_opti['compression_level']) ?? '0';
                        }
                        $options_file_level = apply_filters('next3/selected/compression/level', next3_allowed_compression_level());
                        ?>
                       <label>
                        <select name="next3settings[optimization][compression_level]" class="next3offload-select">
                            <?php
                                foreach($options_file_level as $k=>$v){
                                    ?>
                                    <option value="<?php echo esc_attr( $k );?>" <?php echo ($k == $compression_level) ? esc_attr('selected') : '';?>><?php echo esc_html( $v );?></option>
                                    <?php
                                }
                            ?>
                        </select>
                        
                            <span><?php echo esc_html__('Select the compression level you wish to use. The higher the compression, the larger the space saved.', 'next3-offload')?></span>
                        </label>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content next3_enable_compression <?php echo esc_attr( ($compression != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Overwrite compression level', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $overwrite_custom = 'yes';
                        if( !empty($settings_opti) ){
                            $overwrite_custom = ($settings_opti['overwrite_custom']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($overwrite_custom == 'yes') ? 'checked' : '');?> name="next3settings[optimization][overwrite_custom]" id="next3-overwrite_custom" class=""  value="yes">
                            <span><?php echo esc_html__('Enable this option for overwrite images with custom compression level.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content next3_enable_compression <?php echo esc_attr( ($compression != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Maximum image width', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $optimizer_resize_images = '2560';
                        if( !empty($settings_opti) ){
                            $optimizer_resize_images = ($settings_opti['optimizer_resize_images']) ?? '2560';
                        }
                        $options_max_image_size = apply_filters('next3/image/max/size', next3_allowed_max_image_size());
                        ?>
                       <label>
                        <select name="next3settings[optimization][optimizer_resize_images]" class="next3offload-select">
                            <?php
                                foreach($options_max_image_size as $k=>$v){
                                    ?>
                                    <option value="<?php echo esc_attr( $k );?>" <?php echo ($k == $optimizer_resize_images) ? esc_attr('selected') : '';?>><?php echo esc_html( $v );?></option>
                                    <?php
                                }
                            ?>
                        </select>
                        
                            <span><?php echo esc_html__('If you often upload or use large images on your website, you might want to start resizing them to fit a maximum width.', 'next3-offload')?></span>
                        </label>
                    </div>
                
                </div>

            </div>

        </div>
        
        <div class="next3aws-admin-toolbox-item <?php echo esc_attr(($develeper_status == false) ? 'disabled business-plan' : '');?>">
            <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('WebP Images', 'next3-offload');?></h3>
            
            <div class="next3aws-footer-content">
                <h4 for="next3-rewrite_urls"><?php echo esc_html__('Enable option for convert WebP format.', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $webp_enable = 'no';
                        if( !empty($settings_opti) ){
                            $webp_enable = ($settings_opti['webp_enable']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input data-next3-target=".next3_enable_webp" type="checkbox" <?php echo esc_attr( ($webp_enable == 'yes') ? 'checked' : '');?> name="next3settings[optimization][webp_enable]" id="next3-webp_enable" class=""  value="yes">
                            <span><?php echo esc_html__('WebP is a next generation image format supported by modern browsers which greatly reduces the size of standard image formats while keeping the same quality. Almost all current browsers work with WebP.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content next3_enable_webp <?php echo esc_attr( ($compression != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Back up all original images', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $wepbackup_orginal = 'yes';
                        if( !empty($settings_opti) ){
                            $wepbackup_orginal = ($settings_opti['wepbackup_orginal']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($wepbackup_orginal == 'yes') ? 'checked' : '');?> name="next3settings[optimization][wepbackup_orginal]" id="next3-wepbackup_orginal" class=""  value="yes">
                            <span><?php echo esc_html__('Enable this option for backup your orginal files.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>

            <div class="next3aws-footer-content next3_enable_webp <?php echo esc_attr( ($compression != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Overwrite WebP file', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $overwrite_webp = 'no';
                        if( !empty($settings_opti) ){
                            $overwrite_webp = ($settings_opti['overwrite_webp']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($overwrite_webp == 'yes') ? 'checked' : '');?> name="next3settings[optimization][overwrite_webp]" id="next3-overwrite_webp" class=""  value="yes">
                            <span><?php echo esc_html__('Enable this option for overwrite WebP file all time.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>

        </div>
        <?php }?>

        <?php if( $database_status){?>
        <div class="next3aws-admin-toolbox-item <?php echo esc_attr(($develeper_status == false) ? 'disabled business-plan' : '');?>">
            <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Database Optimization', 'next3-offload');?></h3>
            
            <div class="next3aws-footer-content">
                <h4 for="next3-rewrite_urls"><?php echo esc_html__('When scheduled database maintenance functionality is enabled, we will clean up your database once a week to keep it small and optimized.', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $database = 'no';
                        if( !empty($settings_opti) ){
                            $database = ($settings_opti['database']) ?? 'no';
                        }
                        if( $develeper_status == false ){
                            $database = 'yes';
                        }
                        ?>
                        <label>
                            <input type="checkbox" data-next3-target=".next3_enable_database" <?php echo esc_attr( ($database == 'yes') ? 'checked' : '');?> name="next3settings[optimization][database]" id="next3-database" class=""  value="yes">
                            <span><?php echo esc_html__('Enable this option >> database optimization process will be start.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content next3_enable_database <?php echo esc_attr( ($database != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Post & Page', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $draft_page = 'yes';
                        if( !empty($settings_opti) ){
                            $draft_page = ($settings_opti['draft_page']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($draft_page == 'yes') ? 'checked' : '');?> name="next3settings[optimization][draft_page]" id="next3-draft_page" class=""  value="yes">
                            <span><?php echo esc_html__('Delete all automatically created post and page drafts ', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $revisions_page = 'yes';
                        if( !empty($settings_opti) ){
                            $revisions_page = ($settings_opti['revisions_page']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($revisions_page == 'yes') ? 'checked' : '');?> name="next3settings[optimization][revisions_page]" id="next3-revisions_page" class=""  value="yes">
                            <span><?php echo esc_html__('Delete all page and post revisions', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $trash_page = 'yes';
                        if( !empty($settings_opti) ){
                            $trash_page = ($settings_opti['trash_page']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($trash_page == 'yes') ? 'checked' : '');?> name="next3settings[optimization][trash_page]" id="next3-trash_page" class=""  value="yes">
                            <span><?php echo esc_html__('Delete all posts and pages in your Trash', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>

            <div class="next3aws-footer-content next3_enable_database <?php echo esc_attr( ($database != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Comments', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $spam_comments = 'yes';
                        if( !empty($settings_opti) ){
                            $spam_comments = ($settings_opti['spam_comments']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($spam_comments == 'yes') ? 'checked' : '');?> name="next3settings[optimization][spam_comments]" id="next3-spam_comments" class=""  value="yes">
                            <span><?php echo esc_html__('Delete all comments marked as Spam', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $trash_comments = 'yes';
                        if( !empty($settings_opti) ){
                            $trash_comments = ($settings_opti['trash_comments']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($trash_comments == 'yes') ? 'checked' : '');?> name="next3settings[optimization][trash_comments]" id="next3-trash_comments" class=""  value="yes">
                            <span><?php echo esc_html__('Delete all comments in your Trash', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content next3_enable_database <?php echo esc_attr( ($database != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-force_https"><?php echo esc_html__('Transients', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $expire_transients = 'yes';
                        if( !empty($settings_opti) ){
                            $expire_transients = ($settings_opti['expire_transients']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($expire_transients == 'yes') ? 'checked' : '');?> name="next3settings[optimization][expire_transients]" id="next3-expire_transients" class=""  value="yes">
                            <span><?php echo esc_html__('Delete all expired Transients', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>
            </div>

        </div>
        <?php }?>

        <?php do_action('next3aws-optimization-contentdiv-after', $settings_opti);?>

        <div class="next3aws-admin-setting-button border-top">
            <button type="submit" class="full-demo"><?php echo esc_html__('Save Settings', 'next3-offload');?></button>
        </div>

    </form>
</div>
<?php do_action('next3aws-optimization-content-after', $settings_opti);?>