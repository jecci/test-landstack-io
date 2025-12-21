<div class="providers-data">
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
    
    ?>
                            
    <h3 class="next3offload-heading"><?php echo esc_html__('Cloud configuration', 'next3-offload');?></h3>
    <div class="next3offload-config-area"> 
        <?php do_action('next3/setup/config/before', $credentials);?>
        <div class="provider-settings">
            <div class="next3-config-settings">
                <?php do_action('next3/setup/config/tr/before', $credentials);?>
                <div class="nx_div_tr">
                    <div class="nx_div_td">
                        <strong><?php echo esc_html__('Provider', 'next3-offload');?></strong>
                    </div>
                    <div class="nx_div_td">
                        <a href="<?php echo next3_admin_url( 'admin.php?page=next3aws&step=provider' );?>" class="next3offload-submit"><?php echo esc_html($name_provider);?><i class="dashicons dashicons-edit"></i></a>
                    </div>
                </div>
                <?php
                $obj = next3_core()->provider_ins->load($provider)->access();
                if( $obj ){
                   
                    $message = $obj->getStatus();
                    $setup_data = $obj->get_access();
                    $default_region = ($setup_data['default_region']) ?? $default_region;
                    
                    if( $message == 'success'){
                    ?>
                        <?php if( in_array($provider, ['aws', 'digital', 'wasabi', 'objects'])){
                            $buckets_list = $obj->get_buckets();
                        ?>

                            <div class="nx_div_tr nx_aws_region hide_tr">
                                <div class="nx_div_td">
                                    <strong><?php echo esc_html__('Region', 'next3-offload');?></strong>
                                </div>
                                <div class="nx_div_td">
                                    <select name="nx3_region" class="nx3_new_region_input">
                                        <?php
                                        foreach( next3_aws_region('', $provider) as $k=>$v){
                                            $selected = ( $k == $default_region) ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo esc_attr($k);?>" <?php echo esc_attr($selected);?>><?php echo esc_html($v);?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="nx_div_tr">
                                <div class="nx_div_td">
                                    <h4><strong><?php echo esc_html__('Bucket', 'next3-offload');?></strong></h4>
                                </div>
                                <div class="nx_div_td">
                                    
                                    <input type="text" name="nx3_exiting_bucket" class="settings-input nx3_exiting_bucket_input <?php if( !empty($default_bucket)){ echo esc_attr('hide_tr');} ?>" value="<?php echo esc_attr($default_bucket);?>" placeholder="Bucket name here">
                                    <div class="nx_bucket_data_views">
                                    <?php if( !empty($default_bucket)){?>
                                        <a href="#" class="next3offload-submit nx-change-bucket"><?php echo esc_html($default_bucket);?><i class="dashicons dashicons-edit"></i></a>
                                    <?php }?>
                                    <?php if( !empty($default_region)){?> 
                                    <p><?php echo next3_aws_region( $default_region, $provider);?></p> 
                                    <?php }?>

                                    <?php 
                                    if( !empty($default_bucket) && in_array($provider, ['aws', 'digital', 'wasabi'])){
                                        $status_public = $obj->public_access_blocked( $default_bucket );
                                        if( $status_public == true){
                                            ?>
                                            <p><strong><?php echo __('Warning: <strong>Block All Public Access</strong> setting is currently enabled.', 'next3-offload');?></strong></p>
                                            <!--p><?php echo __('If you\'re not planning on using Amazon CloudFront for delivery, you need to disable <strong>Block All Public Access</strong> setting. ', 'next3-offload');?></p-->
                                            <button type="button" class="nx3_disabled_public_access backcolor" data-type="settings"><?php echo esc_html__('Disable "Block All Public Access"', 'next3-offload');?></button>
                            
                                            <?php
                                        } else{
                                            ?>
                                            <p><strong><?php echo __('<strong>Block All Public Access</strong> is disabled.', 'next3-offload');?></strong></p>
                                            <button type="button" class="nx3_remove_all_files backcolor" data-type="delete-files"><?php echo esc_html__('Remove all Files from this Bucket', 'next3-offload');?></button>
                                            <?php
                                        }
                                    }
                                    ?>
                                    </div>
                                    <ul class="nx_bucket_lists_views hide_tr">
                                        <?php
                                        if( isset($buckets_list['data']) && !empty($buckets_list['data']) ){
                                            foreach($buckets_list['data'] as $v){
                                                ?>
                                                <li data-value="<?php echo esc_attr($v);?>"><?php echo esc_html($v);?></li>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                            <div class="nx_div_tr">
                                <p class="nx3_msg_error"></p>
                            </div>
                            <div class="nx_div_tr bucket_footer">
                                <button type="button" class="nx3_buccket_exiting"><?php echo esc_html__('Browse Buckets', 'next3-offload');?></button>
                                <button type="button" class="nx3_buccket_new"><?php echo esc_html__('Create Bucket', 'next3-offload');?></button>
                                <button type="button" class="nx3_buccket_action nx3_exiting_bucket_btn backcolor" data-type="settings"><?php echo esc_html__('Save Settings', 'next3-offload');?></button>
                            </div>
                        <?php } else if( in_array($provider, ['bunny']) ){  ?>

                            <div class="nx_div_tr nx_aws_region nx_aws_region_buuny <?php if( !empty($default_bucket)){ echo esc_attr('hide_tr');} ?>">
                                <div class="nx_div_td">
                                    <strong><?php echo esc_html__('Region', 'next3-offload');?></strong>
                                </div>
                                <div class="nx_div_td">
                                    <select name="nx3_region" class="nx3_new_region_input">
                                        <?php
                                        foreach( next3_aws_region('', $provider) as $k=>$v){
                                            $selected = ( $k == $default_region) ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo esc_attr($k);?>" <?php echo esc_attr($selected);?>><?php echo esc_html($v);?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="nx_div_tr">
                                <div class="nx_div_td">
                                    <h4><strong><?php echo esc_html__('Storage', 'next3-offload');?></strong></h4>
                                </div>
                                <div class="nx_div_td">
                                    
                                    <input type="text" name="nx3_exiting_bucket" class="settings-input nx3_exiting_bucket_input <?php if( !empty($default_bucket)){ echo esc_attr('hide_tr');} ?>" value="<?php echo esc_attr($default_bucket);?>" placeholder="Storage name here">
                                    <div class="nx_bucket_data_views">
                                    <?php if( !empty($default_bucket)){?>
                                        <a href="#" class="next3offload-submit nx-change-bucket"><?php echo esc_html($default_bucket);?><i class="dashicons dashicons-edit"></i></a>
                                    <?php }?>
                                    <?php if( !empty($default_region) && !empty($default_bucket) ){?> 
                                    <p><?php echo next3_aws_region( $default_region, $provider);?></p> 
                                    <?php }?>
                                    
                                    </div>
                                   
                                </div>
                            </div>
                            <div class="nx_div_tr">
                                <p class="nx3_msg_error"></p>
                            </div>
                            <div class="nx_div_tr bucket_footer">
                                <button type="button" class="nx3_buccket_action nx3_exiting_bucket_btn" data-type="settings"><?php echo esc_html__('Save Settings', 'next3-offload');?></button>
                            </div>

                        <?php } ?>
                        
                    <?php 
                    }else{
                        ?>
                        <div class="nx_div_tr">
                            <div class="nx_div_td">
                                <p style="font-size: 20px;"><?php echo esc_html__('Sorry! your credentails is wrong.', 'next3-offload')?></p>
                                <a href="<?php echo next3_admin_url( 'admin.php?page=next3aws&step=provider' );?>" class="next3offload-submit"><i></i> <?php echo esc_html('Back to Providers', 'next3-offload');?></a>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
                <?php do_action('next3/setup/config/tr/after', $credentials);?>
            </div>
        </div>
        <?php do_action('next3/setup/config/after', $credentials);?>
    </div>

</div>