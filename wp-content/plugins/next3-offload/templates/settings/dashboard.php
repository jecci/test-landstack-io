<?php 
$status_data = next3_service_status();

$status_optimization = ($status_data['optimization']) ?? false;
$develeper_status = ($status_data['develeper']) ?? false;
$assets_status = ($status_data['assets']) ?? false;
$offload_status = ($status_data['offload']) ?? false;
$database_status = ($status_data['database']) ?? false;

$default_tab = 'settings';
if( !$offload_status ){
    $default_tab = 'optimization';
}

$offload_store = next3_core()->action_ins->get_offload_count();

$website_logo = next3_core()->plugin::plugin_url() . 'assets/img/logo.png';
?>
<section class="next3aws-section"> 

    <div class="next3aws-content">
        <div class="next3aws-nav-menu">
            <ul class="nav-setting">
                <li class="logo_area"><a href="<?php echo esc_url(apply_filters('next3/whitelavel/website/url', 'https://next3offload.com/'));?>" target="_blank"><img src="<?php echo esc_url(apply_filters('next3/whitelavel/logo/url', $website_logo));?>" alt="<?php echo esc_html(apply_filters('next3/whitelavel/logo/name', 'Next3 Offload Media Plugin'));?>"></a></li>

                <?php do_action('next3aws/navtab/before');?>
                <?php if( $offload_status ){?>
                <li <?php if($default_tab == 'settings'){?>class="active"<?php }?> ><a href="#ntab=settings" id="nv-settings"> <i class="dashicons dashicons-cloud-upload"></i> <?php echo esc_html__('Storage Settings', 'next3-offload');?></a></li>
                <li <?php if($default_tab == 'delivery'){?>class="active"<?php }?> ><a href="#ntab=delivery" id="nv-delivery"> <i class="dashicons dashicons-cloud-saved"></i> <?php echo esc_html__('Delivery Settings', 'next3-offload');?></a></li>
                <?php }?>

                <?php if( $status_optimization || $database_status){?>
                <li <?php if($default_tab == 'optimization'){?>class="active"<?php }?>  ><a href="#ntab=optimization" id="nv-optimization"> <i class="dashicons dashicons-update"></i> <?php echo esc_html__('Optimization', 'next3-offload');?></a></li>
                <?php }?>

                <?php if( $offload_status ){?>
                <li <?php if($default_tab == 'assets'){?>class="active"<?php }?> ><a href="#ntab=assets" id="nv-assets"> <i class="dashicons dashicons-filter"></i> <?php echo esc_html__('Assets', 'next3-offload');?></a></li>
                
                <li <?php if($default_tab == 'offload'){?>class="active"<?php }?> ><a href="#ntab=offload" id="nv-offload"> <i class="dashicons dashicons-cloud"></i> <?php echo esc_html__('Offload Settings', 'next3-offload');?></a></li>
                
                <li <?php if($default_tab == 'sync'){?>class="active"<?php }?> ><a href="#ntab=sync" id="nv-sync"> <i class="dashicons dashicons-update-alt"></i> <?php echo esc_html__('Sync Settings', 'next3-offload');?></a></li>
                <?php }?>

                <li><a href="#ntab=tools" id="nv-tools"> <i class="dashicons dashicons-admin-tools"></i> <?php echo esc_html__('Tools', 'next3-offload');?></a></li>
                
                <?php do_action('next3aws/navtab/after');?>
                
                <li><a href="#ntab=addons" id="nv-addons"> <i class="dashicons dashicons-plugins-checked"></i> <?php echo esc_html__('Addons', 'next3-offload');?></a></li>
                
                <?php if( !NEXT3_SELF_MODE ){?>
                <li><a href="#ntab=license" id="nv-license"> <i class="dashicons dashicons-lock"></i> <?php echo esc_html__('Activate License', 'next3-offload');?></a></li>
                <?php }?>
                
                
                <li><a href="<?php echo esc_url('https://next3offload.com/?utm_source=next3&utm_medium=Dashboard&utm_campaign=Plugin+Dashboard&utm_id=plugin');?>" target="_blank" id="nv-livepreview"> <?php echo esc_html__('Visit website', 'next3-offload');?></a></li>
                
                <li><a></a></li>
            </ul>
        </div>

        <div class="next3aws-content-area">
            <h1> <?php echo esc_html(apply_filters('next3/whitelavel/logo/name', 'Next3 Offload'));?> <span class="next3aws-version"><?php echo esc_html__('Version: ', 'next3-offload');?> <?php _e( \Next3Offload\N3aws_Plugin::version());?></span></h1>
            <?php if( !NEXT3_SELF_MODE ){?>
            <a href="<?php echo esc_url(next3_admin_url().'admin.php?page=next3aws&step=service');?>" class="nxbutton submit-button right-submit"><?php echo esc_html__('Reset Preference', 'next3-offload');?></a>
            <?php }?>
            <div class="message-view <?php if( next3_get_option('__validate_author_next3aws__') == 'active'){?>hide-message<?php }?>"> <?php echo esc_html__('Please activate your license ', 'next3-offload');?></div>
            <div class="message-view <?php if( !NEXT3_SELF_MODE ){?>hide-message<?php }?>"> <?php echo __('<strong>Trial Mode</strong> is enabled. You can only view it on <strong>Trial Mode</strong>.', 'next3-offload');?></div>

            <div class="settings-content">

                <?php do_action('next3aws/content/before');?>  

                <?php if( $offload_status ){?>
                <div id="settings" class="ncode-tabs-settings <?php if($default_tab == 'settings'){?>active<?php }?> ">
                    <div class="heading-label ">
                        <h3> <?php esc_html_e('Storage Settings', 'next3-offload');?> </h3>      
                        <?php include( __DIR__ .'/include/settings.php');?>
                    </div> 
                </div>

                <div id="delivery" class="ncode-tabs-delivery <?php if($default_tab == 'delivery'){?>active<?php }?> ">
                    <div class="heading-label ">
                        <h3> <?php esc_html_e('Delivery Settings', 'next3-offload');?> </h3>      
                        <?php include( __DIR__ .'/include/delivery.php');?>
                    </div> 
                </div>

                <?php }?>

                <?php if( $status_optimization || $database_status){?>
                <div id="optimization" class="ncode-tabs-optimization <?php if($default_tab == 'optimization'){?>active<?php }?> ">
                    <div class="heading-label ">
                        <h3> <?php esc_html_e('Optimization Settings', 'next3-offload');?> </h3>      
                        <?php include( __DIR__ .'/include/optimization.php');?>
                    </div> 
                </div>
                <?php }?>

                <?php if( $offload_status ){?>
                <div id="offload" class="ncode-tabs-offload <?php if($default_tab == 'offload'){?>active<?php }?> ">
                    <div class="heading-label ">
                        <h3> <?php esc_html_e('Offload Settings', 'next3-offload');?> </h3>      
                        <?php include( __DIR__ .'/include/offload.php');?>
                    </div> 
                </div>

                <div id="assets" class="ncode-tabs-assets <?php if($default_tab == 'assets'){?>active<?php }?> ">
                    <div class="heading-label ">
                        <h3> <?php esc_html_e('Assets Tools', 'next3-offload');?> </h3>      
                        <?php include( __DIR__ .'/include/assets.php');?>
                    </div> 
                </div>

                <div id="sync" class="ncode-tabs-sync <?php if($default_tab == 'sync'){?>active<?php }?> ">
                    <div class="heading-label ">
                        <h3> <?php esc_html_e('Sync Settings', 'next3-offload');?> </h3>      
                        <?php include( __DIR__ .'/include/sync.php');?>
                    </div> 
                </div>
                <?php }?>


                <?php if( !NEXT3_SELF_MODE ){?>
                <div id="license" class="ncode-tabs-license">
                    <div class="heading-label ">
                        <h3> <?php esc_html_e('Active License', 'next3-offload');?> </h3> 
                        <?php include( __DIR__ .'/include/active-pro.php');?>
                    </div> 
                </div>
                <?php }?>

                <div id="tools" class="ncode-tabs-tools">
                    <div class="heading-label ">
                        <h3> <?php esc_html_e('Tools', 'next3-offload');?> </h3> 
                        <?php 
                        include( __DIR__ .'/include/tools.php');
                        ?> 
                    </div> 
                </div>
                
                <div id="addons" class="ncode-tabs-tools">
                    <div class="heading-label ">
                        <h3> <?php esc_html_e('Addons', 'next3-offload');?> </h3> 
                        <?php 
                        include( __DIR__ .'/include/addons.php');
                        ?> 
                    </div> 
                </div>

                <?php do_action('next3aws/content/after');?>

            </div>

        </div>
    </div>

</section>
