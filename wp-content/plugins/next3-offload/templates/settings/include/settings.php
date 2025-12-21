<?php 
    $credentials = next3_credentials();

    $config_data = next3_providers();

    $provider = ($credentials['settings']['provider']) ?? '';
    $name_provider = ($config_data[$provider]['label']) ?? '';

    $prodiver_data = ($credentials['settings'][$provider]) ?? [];
    $default_bucket = ($prodiver_data['default_bucket']) ?? '';

    $default_region_1 = 'us-east-1';
    if( $provider == 'digital'){
        $default_region_1 = 'nyc3';
    } else if( $provider == 'bunny' ){
        $default_region_1 = 'de';
    }
    $default_region = ($prodiver_data['default_region']) ?? $default_region_1;
    
    $get_local_url_preview = next3_core()->action_ins->get_url_preview(true, 'photo.jpg', 'array');

    $settings_storage = ($settings_options['storage']) ?? [ 'copy_file' => 'yes'];

    do_action('next3aws-settings-content-before', $settings_storage);
?>
<div class="next3aws-admin-panel" >
    <form action="" method="post" class="next3-offload-settings-panel" data-type="storage">

        <?php do_action('next3aws-settings-contentdiv-after', $settings_storage);?>

        <div class="next3aws-admin-toolbox-item">
            <div class="next3-config-settings">
                <div class="nx_div_tr">
                    <div class="nx_div_td">
                        <strong><?php echo esc_html__('Provider', 'next3-offload');?></strong>
                    </div>
                    <div class="nx_div_td">
                        <?php if( !NEXT3_SELF_MODE ){?><a href="<?php echo next3_admin_url( 'admin.php?page=next3aws&step=provider' );?>" class="next3offload-submit"><?php echo esc_html($name_provider);?><i class="dashicons dashicons-edit"></i></a><?php }?>
                    </div>
                </div>
                
                
                <?php if( in_array($provider, ['aws', 'digital', 'wasabi']) ){ ?>
                
                    <div class="nx_div_tr">
                        <div class="nx_div_td">
                            <strong><?php echo esc_html__('Bucket', 'next3-offload');?></strong>
                        </div>
                        <div class="nx_div_td">
                            <div class="nx_bucket_data_views">
                            <?php if( !empty($default_bucket)){?>
                                <?php if( !NEXT3_SELF_MODE ){?><a href="<?php echo next3_admin_url( 'admin.php?page=next3aws&step=config' );?>" class="next3offload-submit"><?php echo esc_html($default_bucket);?><i class="dashicons dashicons-edit"></i></a><?php } ?>
                            <?php }?>
                            <?php if( !empty(trim($default_region))){?> 
                            <p><?php echo next3_aws_region( trim($default_region), $provider);?></p> 
                            <?php }?>
                            
                            </div>
                        </div>
                    </div>
                <?php } else if( in_array($provider, ['bunny', 'objects'])){ ?> 
                    <div class="nx_div_tr">
                    <div class="nx_div_td">
                        <strong><?php echo esc_html__('Storage Name', 'next3-offload');?></strong>
                    </div>
                    <div class="nx_div_td">
                        <div class="nx_bucket_data_views">
                        <?php if( !empty($default_bucket)){?>
                            <?php if( !NEXT3_SELF_MODE ){?><a href="<?php echo next3_admin_url( 'admin.php?page=next3aws&step=config' );?>" class="next3offload-submit"><?php echo esc_html($default_bucket);?><i class="dashicons dashicons-edit"></i></a><?php } ?>
                        <?php }?>
                        <?php if( !empty(trim($default_region))){?> 
                        <p><?php echo next3_aws_region( trim($default_region), $provider);?></p> 
                        <?php }?>
                        
                        </div>
                    </div>
                </div>
                <?php } ?>
                <div class="nx_div_tr">
                    <div class="nx_div_td">
                        <strong><?php echo esc_html__('Preview URL', 'next3-offload');?></strong>
                    </div>
                    <div class="nx_div_td preview-url">
                    <p><?php echo esc_html__('When a media URL is rewritten, it will use the following structure based on the current Storage and Delivery Settings:', 'next3-offload');?></p>
                        <div>
                        <?php 
                       
                        $url_data = ($get_local_url_preview['url']) ?? [];
                        $title_url = ($get_local_url_preview['title_url']) ?? [];
                        $i = 1;
                        $count_total = count($url_data);
                        
                        foreach($url_data as $k=>$v){
                            $title = ($title_url[$k]) ?? '';

                            $slash = ($count_total > $i) ? '/' : '';
                            ?>
                            <div data-title="<?php echo esc_html($title);?>">
                                <div class="data-label">
                                    <?php echo esc_html($title);?>
                                </div>
                                <div class="data-prefix">
                                    <?php echo esc_html($v) . $slash;?>
                                </div>
                            </div>
                            <?php
                            $i++;
                        }
                        ?>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>

        <div class="next3aws-admin-toolbox-item">
            <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('General Options', 'next3-offload');?></h3>
            
            <div class="next3aws-footer-content">
                <h4 for="next3-copy-files"><?php echo esc_html__('Offload media', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $copyfiles = 'yes';
                        if( !empty($settings_storage) ){
                            $copyfiles = ($settings_storage['copy_file']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($copyfiles == 'yes') ? 'checked' : '');?> name="next3settings[storage][copy_file]" id="next3-copy-files" class=""  value="yes">
                            <span><?php echo esc_html__('Copies media files to the storage provider after being uploaded, edited, or optimized.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>
            
            <div class="next3aws-footer-content">
                <h4 for="next3-wpmedia_upload"><?php echo esc_html__('Copy to local server', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $wpmedia_upload = 'no';
                        if( !empty($settings_storage) ){
                            $wpmedia_upload = ($settings_storage['wpmedia_upload']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($wpmedia_upload == 'yes') ? 'checked' : '');?> name="next3settings[storage][wpmedia_upload]" id="next3-wpmedia_upload" class=""  value="yes">
                            <span><?php echo esc_html__('Copies media files to the local server after being uploaded using Next3 File Manager.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>

        </div>
        
        <div class="next3aws-admin-toolbox-item">
            <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Setup Path', 'next3-offload');?></h3>
            
            <div class="next3aws-footer-content">
                <h4 for="next3-upload-path"><?php echo esc_html__('Add prefix to bucket path', 'next3-offload');?></h4>
                
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $enable_path = 'yes';
                        if( !empty($settings_storage) ){
                            $enable_path = ($settings_storage['enable_path']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" data-next3-target=".next3_enable_path_section" <?php echo esc_attr( ($enable_path == 'yes') ? 'checked' : '');?> name="next3settings[storage][enable_path]" id="next3-enable_path" class=""  value="yes">
                            <span><?php echo esc_html__('Groups media from this site together by using a common prefix in the bucket path of offloaded media files.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>
                <div class="files-table next3_enable_path_section <?php echo esc_attr( ($enable_path != 'yes') ? 'next3-closed' : '');?>">
                    <div class="settings-item">
                        <?php 
                        $upload_path = next3_core()->action_ins->get_upload_prefix();
                        if( !empty($settings_storage) ){
                            $upload_path = ($settings_storage['upload_path']) ?? $upload_path;
                        }
                        ?>
                        <label>
                            <input type="text" name="next3settings[storage][upload_path]" id="next3-upload-path" class="settings-input" placeholder="wp-content/uploads/sites/{site_id}/" value="<?php echo esc_attr( $upload_path );?>">
                        </label>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content">
                <h4 for="next3-folder-format"><?php echo esc_html__('Add year & month to bucket path', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $folder_format = 'yes';
                        if( !empty($settings_storage) ){
                            $folder_format = ($settings_storage['folder_format']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($folder_format == 'yes') ? 'checked' : '');?> name="next3settings[storage][folder_format]" id="next3-folder-format" class=""  value="yes">
                            <span><?php echo __('Provides another level of organization within the bucket by including the year & month in which the file was uploaded to the site.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content">
                <h4 for="next3-addition_folder"><?php echo esc_html__('Add object version to bucket path', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $addition_folder = 'no';
                        if( !empty($settings_storage) ){
                            $addition_folder = ($settings_storage['addition_folder']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($addition_folder == 'yes') ? 'checked' : '');?> name="next3settings[storage][addition_folder]" id="next3-addition_folder" class=""  value="yes">
                            <span><?php echo __('Ensures the latest version of a media item gets delivered by adding a unique timestamp to the bucket path.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content">
                <h4 for="next3-addition_folder"><?php echo esc_html__('Rename files', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $unique_file = 'no';
                        if( !empty($settings_storage) ){
                            $unique_file = ($settings_storage['unique_file']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($unique_file == 'yes') ? 'checked' : '');?> name="next3settings[storage][unique_file]" id="next3-unique_file" class=""  value="yes">
                            <span><?php echo __('Automatically rename your files name when offload (unique name).', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>

        </div>

        <div class="next3aws-admin-toolbox-item">
            <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('File Management', 'next3-offload');?></h3>
            
            <div class="next3aws-footer-content">
                <h4 for="next3-enable_mine"><?php echo esc_html__('Offload {multiple mime types} format to cloud', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $enable_mine = 'no';
                        if( !empty($settings_storage) ){
                            $enable_mine = ($settings_storage['enable_mine']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" data-next3-target=".next3_enable_file_extention" <?php echo esc_attr( ($enable_mine == 'yes') ? 'checked' : '');?> name="next3settings[storage][enable_mine]" id="next3-enable_mine" class=""  value="yes">
                            <span><?php echo __('Click switch to multiple mime types support enabled.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>
                     
            </div>
            
            <div class="next3aws-footer-content next3_enable_file_extention <?php echo esc_attr( ($enable_mine != 'yes') ? 'next3-closed' : '');?>">
                <h4 for="next3-remove_local"><?php echo esc_html__('Select files extension', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $selected_files = ['all'];
                        if( !empty($settings_storage) ){
                            $selected_files = ($settings_storage['selected_files']) ?? ['all'];
                        }
                        $options_file_type = apply_filters('next3/selected/file/types', array_merge(['all'], next3_allowed_mime_ext()));
                        ?>
                         <select name="next3settings[storage][selected_files][]" class="next3offload-select2" multiple="multiple">
                            <?php
                                foreach($options_file_type as $v){
                                    ?>
                                    <option value="<?php echo esc_attr( $v );?>" <?php echo in_array($v, $selected_files) ? esc_attr('selected') : '';?>><?php echo esc_html( $v );?></option>
                                    <?php
                                }
                            ?>
                        </select>
                        
                    </div>
                
                </div>

            </div>
            
            <div class="next3aws-footer-content">
                <h4 for="next3-enable_mine"><?php echo esc_html__('SVG file', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $enable_svg = 'no';
                        if( !empty($settings_storage) ){
                            $enable_svg = ($settings_storage['enable_svg']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($enable_svg == 'yes') ? 'checked' : '');?> name="next3settings[storage][enable_svg]" id="next3-enable_svg" class=""  value="yes">
                            <span><?php echo __('Click switch to allow upload SVG file.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>
                     
            </div>

        </div>

        <div class="next3aws-admin-toolbox-item">
            <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Advanced Options', 'next3-offload');?></h3>
            
            <div class="next3aws-footer-content">
                <h4 for="next3-remove_local"><?php echo esc_html__('Remove local media', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $remove_local = 'no';
                        if( !empty($settings_storage) ){
                            $remove_local = ($settings_storage['remove_local']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($remove_local == 'yes') ? 'checked' : '');?> name="next3settings[storage][remove_local]" id="next3-remove_local" class=""  value="yes">
                            <span><?php echo __('Frees up storage space by deleting local media files after they have been offloaded.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content">
                <h4 for="next3-remove_cloud"><?php echo esc_html__('Remove files from cloud', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $remove_cloud = 'no';
                        if( !empty($settings_storage) ){
                            $remove_cloud = ($settings_storage['remove_cloud']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($remove_cloud == 'yes') ? 'checked' : '');?> name="next3settings[storage][remove_cloud]" id="next3-remove_cloud" class=""  value="yes">
                            <span><?php echo __('Delete a file from the cloud (bucket) once it has been deleted from the local server (WP Media).', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>

            <div class="next3aws-footer-content">
                <h4 for="next3-remove_cloud"><?php echo esc_html__('Offload settings', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $offload_limit = 10000;
                        if( !empty($settings_storage) ){
                            $offload_limit = ($settings_storage['offload_limit']) ?? $offload_limit;
                        }
                        ?>
                        
                        <label>
                            <input type="number" style="max-width: 100px;" name="next3settings[storage][offload_limit]" id="next3-offload_limit" class="settings-input" placeholder="Max 20,000" max="20000" min="0" step="100" value="<?php echo esc_attr( $offload_limit );?>">
                            <span><?php echo __('Setup your offload limit [Max value for single offload: 20,000]. But you can offload unlimited files with multiple batches.', 'next3-offload');?></span>
                        
                        </label>
                    </div>
                    <div class="settings-item">
                        <?php 
                        $offload_paged = 1;
                        if( !empty($settings_storage) ){
                            $offload_paged = ($settings_storage['offload_paged']) ?? $offload_paged;
                        }
                        ?>
                        
                        <label>
                            <input type="number" style="max-width: 100px;" name="next3settings[storage][offload_paged]" id="next3-offload_paged" class="settings-input" placeholder="Number of Paged: 1" max="1000" min="0" value="<?php echo esc_attr( $offload_paged );?>">
                            <span><?php echo __('Set up number of paged', 'next3-offload');?></span>
                        
                        </label>
                    </div>
                
                </div>

            </div>
            

        </div>

        <?php do_action('next3aws-settings-contentdiv-after', $settings_storage);?>

        <div class="next3aws-admin-setting-button border-top">
            <button type="submit" class="full-demo"><?php echo esc_html__('Save Settings', 'next3-offload');?></button>
        </div>
    </form>
</div>
<?php do_action('next3aws-settings-content-after', $settings_storage);?>