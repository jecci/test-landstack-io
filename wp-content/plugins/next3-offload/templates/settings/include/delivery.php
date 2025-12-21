<?php 
    $credentials = next3_credentials();

    $config_data = next3_providers();

    $provider = ($credentials['settings']['provider']) ?? '';
    $name_provider = ($config_data[$provider]['label']) ?? '';
    $delivery_providers = ($config_data[$provider]['delivery_providers']) ?? [];

    $prodiver_data = ($credentials['settings'][$provider]) ?? [];
    $default_bucket = ($prodiver_data['default_bucket']) ?? '';
    $default_region = ($prodiver_data['default_region']) ?? 'us-east-1';

    $settings_delivery = ($settings_options['delivery']) ?? [ 'rewrite_urls' => 'yes'];

    do_action('next3aws-delivery-content-before', $settings_delivery);
?>
<div class="next3aws-admin-panel" >
    <form action="" method="post" class="next3-offload-settings-panel" data-type="delivery">

        <?php do_action('next3aws-delivery-contentdiv-before', $settings_delivery);?>

        <div class="next3aws-admin-toolbox-item">
            <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Select Provider', 'next3-offload');?></h3>
            
            <div class="next3-config-settings">
                <div class="delivery-provider-section">
                    <div class="delivery-providers">
                    <?php
                    $delivery_provider = 'yes';
                    if( !empty($settings_delivery) ){
                        $delivery_provider = ($settings_delivery['provider']) ?? $provider;
                    }
                    if( !empty($delivery_providers) ){
                            foreach($delivery_providers as $k=>$v){
                                $title = ($v['title']) ?? $k;
                                $f_docs_link = ($v['url']) ?? '';
                                $f_desc = ($v['desc']) ?? '';
                                $img = ($v['img']) ?? '';
                                ?>
                                <div class="delivery-provider provider-<?php echo esc_attr($k);?> <?php echo esc_attr( ($delivery_provider == $k) ? 'checked-rpovider' : '');?>">
                                    <label for="next3-provider-<?php echo esc_attr($k);?>">
                                        <?php if( !empty($img) ): ?>
                                        <img src="<?php echo esc_url($img);?>" alt="<?php echo esc_attr($title);?>">
                                        <?php endif;?> 
                                        <input type="radio" <?php echo esc_attr( ($delivery_provider == $k) ? 'checked' : '');?> name="next3settings[delivery][provider]" id="next3-provider-<?php echo esc_attr($k);?>" class=""  value="<?php echo esc_attr($k);?>">
                                        <span><?php echo esc_html__($title, 'next3-offload');?></span>
                                    </label>
                                    <i class="dashicons dashicons-yes"></i>
                                </div>
                                <?php
                            }
                    }
                    ?>
                    </div>
                    <div class="delivery-providers-data">
                        <?php
                        if( !empty($delivery_providers) ){
                            foreach($delivery_providers as $k=>$v){
                                $cname = ($v['cname']) ?? false;
                                $cname_label = ($v['cname_label']) ?? '';
                                $cname_desc = ($v['cname_desc']) ?? '';
                                if( !$cname ){
                                    continue;
                                }
                                $value = ($settings_delivery['provider_data'][$k]) ?? '';
                                ?>
                                <div class="delivery-provider-data provider-data-<?php echo esc_attr($k);?> <?php echo esc_attr( ($delivery_provider == $k) ? 'checked-provider' : '');?>">
                                    <span><?php echo esc_html__($cname_desc, 'next3-offload');?></span>
                                    <input type="text" placeholder="<?php echo esc_attr($cname_label);?>"  name="next3settings[delivery][provider_data][<?php echo esc_attr($k);?>]" id="next3-prodata-<?php echo esc_attr($k);?>" class="settings-input"  value="<?php echo esc_attr( $value );?>">
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>
                
            </div>
        </div>
        
        <div class="next3aws-admin-toolbox-item">
            <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Basic Options', 'next3-offload');?></h3>
            
            <div class="next3aws-footer-content">
                <h4 for="next3-rewrite_urls"><?php echo esc_html__('Rewrite media URLs to deliver offloaded media', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $rewrite_urls = 'yes';
                        if( !empty($settings_delivery) ){
                            $rewrite_urls = ($settings_delivery['rewrite_urls']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($rewrite_urls == 'yes') ? 'checked' : '');?> name="next3settings[delivery][rewrite_urls]" id="next3-rewrite_urls" class=""  value="yes">
                            <span><?php echo esc_html__('Serves offloaded media files by rewriting local URLs so that they point to cloud.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>
            <div class="next3aws-footer-content">
                <h4 for="next3-force_https"><?php echo esc_html__('Force HTTPS', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $force_https = 'yes';
                        if( !empty($settings_delivery) ){
                            $force_https = ($settings_delivery['force_https']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($force_https == 'yes') ? 'checked' : '');?> name="next3settings[delivery][force_https]" id="next3-force_https" class=""  value="yes">
                            <span><?php echo esc_html__('Uses HTTPS for every offloaded media item instead of using the scheme of the current page.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>

            <div class="next3aws-footer-content">
                <h4 for="next3-force_cdn"><?php echo esc_html__('Force CDN delivery', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $force_cdn = 'yes';
                        if( !empty($settings_delivery) ){
                            $force_cdn = ($settings_delivery['force_cdn']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($force_cdn == 'yes') ? 'checked' : '');?> name="next3settings[delivery][force_cdn]" id="next3-force_cdn" class=""  value="yes">
                            <span><?php echo esc_html__('Always all offloaded media files by rewriting URL\'s from selected Delivery Provider.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>

            <div class="next3aws-footer-content">
                <h4 for="next3-disable_cache"><?php echo esc_html__('Disable delivery cache', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $disable_cache = 'yes';
                        if( !empty($settings_delivery) ){
                            $disable_cache = ($settings_delivery['disable_cache']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($disable_cache == 'yes') ? 'checked' : '');?> name="next3settings[delivery][disable_cache]" id="next3-disable_cache" class=""  value="yes">
                            <span><?php echo esc_html__('Disable page & post content delivery cache.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>

            <div class="next3aws-footer-content">
                <h4 for="next3-disable_cache"><?php echo esc_html__('Force content rewrite', 'next3-offload');?></h4>
                <div class="files-table">
                    <div class="settings-item">
                        <?php 
                        $content_rewrite = 'yes';
                        if( !empty($settings_delivery) ){
                            $content_rewrite = ($settings_delivery['content_rewrite']) ?? 'no';
                        }
                        ?>
                        <label>
                            <input type="checkbox" <?php echo esc_attr( ($content_rewrite == 'yes') ? 'checked' : '');?> name="next3settings[delivery][content_rewrite]" id="next3-content_rewrite" class=""  value="yes">
                            <span><?php echo esc_html__('Enabled the option, to force rewrite media URLs in the frontend.', 'next3-offload');?></span>
                        </label>
                    </div>
                
                </div>

            </div>

        </div>

        <?php do_action('next3aws-delivery-contentdiv-after', $settings_delivery);?>

        <div class="next3aws-admin-setting-button border-top">
            <button type="submit" class="full-demo"><?php echo esc_html__('Save Settings', 'next3-offload');?></button>
        </div>
    </form>
</div>
<?php do_action('next3aws-delivery-content-after', $settings_delivery);?>