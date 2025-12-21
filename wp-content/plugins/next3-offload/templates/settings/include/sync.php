<?php 
$settings_sync = ($settings_options['sync']) ?? [ 'css_offload' => 'no', 'js_offload' => 'no', 'minify_css' => 'no', 'minify_js' => 'no'];

$wpoffload = ($offload_store['wpoffload']) ?? 0;
$wpoffload_done = ($offload_store['wpoffload_done']) ?? 0;
$wpoffload_per = ($offload_store['wpoffload_per']) ?? 0;
$total_optimize = ($offload_store['total_optimize']) ?? 0;
$total_webp_done = ($offload_store['total_webp_done']) ?? 0;
$total_compress_done = ($offload_store['total_compress_done']) ?? 0;
$webp_per = ($offload_store['webp_per']) ?? 0;
$compress_per = ($offload_store['compress_per']) ?? 0;
$total_cloud = ($offload_store['total_cloud']) ?? 0;
$offload = ($offload_store['offload']) ?? 0;
$cloud_per = ($offload_store['cloudto_per']) ?? 0;
$unoffload = ($offload_store['unoffload']) ?? 0;
$total_local = ($offload_store['total_local']) ?? 0;
$localto_per = ($offload_store['localto_per']) ?? 0;


$offload_data = next3_get_option('_next3_offload_data', []);
    
$get_package = next3_license_package();

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

do_action('next3aws-sync-content-before', $settings_sync);
?>

<?php if( $offload_status ){

$msg_offload = __(number_format($wpoffload - $wpoffload_done) .' Files need to migrate to "Next3 Offload"', 'next3-offload');
if( $wpoffload != 0){
    $msg_offload = __( $wpoffload_per .'% Files have been migrated', 'next3-offload');
}
if( !empty($offload_data)){
    $status_offload = ($offload_data['status']) ?? '';
    $total_offload = ($offload_data['total']) ?? 0;
    $start_offload = ($offload_data['start']) ?? 0;
    $type_offload = ($offload_data['type']) ?? '';

    if($status_offload == 'pause' && $type_offload == 'wpoffload'){
        $msg_offload = __( number_format($wpoffload - $wpoffload_done) .' Files are migration process to "Next3 Offload", Paused!', 'next3-offload');
    }

    $start_offload = ($total_offload < $start_offload) ? $total_offload : $start_offload;
    if( $start_offload != 0  && $total_offload != 0){
        $persent_offload = floor(( $start_offload * 100) / $total_offload);
    }
    $txt_offload = $persent_offload . '% ('.$start_offload.'/'. $total_offload .')';
    if( $total_offload <= $start_offload || $type_offload != 'wpoffload'){
        $status_offload = '';
        $persent_offload = 0;
        $txt_offload = '0% (0/0)';
    }
    
}
?>

<div class="next3aws-admin-toolbox-item next3-offload-wrap"  data-action="wpoffload">
    <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('WP Offload Media to Next3 Offload migration', 'next3-offload');?></h3>
    <div class="next3aws-footer-content next3-offload-section">
       <p class="show-process"><?php echo esc_html__($msg_offload, 'next3-offload');?></p>
        <div class="next3-settings-progressbars next3-offload-bar <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>">
            <div class="complate-process" style="width:<?php echo esc_attr($wpoffload_per);?>%;"></div>
            <div class="next3-progressbar next3-progress-process" data-title="Process offload media"><span><?php echo esc_html($txt_offload);?></span></div>
        </div>
        <?php 
            if ( class_exists( '\WP_Offload_Media_Autoloader' ) ) {
        ?>
        <button class="action-button next3-offload-target next3-offload-action <?php echo esc_attr( ($status_offload == '') ? 'nxopen' : '' );?>" data-type="wpoffload" href="#"><?php echo esc_html__('Migrate Now', 'next3-offload');?></button>
        <button class="action-button next3-offload-target next3-offload-pause <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="<?php echo esc_attr( ($status_offload == 'pause') ? 'resume' : 'pause' );?>" href="#"><?php echo esc_html( ($status_offload == 'pause') ? 'Resume' : 'Pause' );?> </button>
        <button class="action-button next3-offload-target next3-offload-cancel <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="cancel" href="#"><?php echo esc_html__('Cancel', 'next3-offload');?></button>
        <p><i><br/><?php echo __('<strong>' . number_format($wpoffload - $wpoffload_done) . '</strong> Files has been offloaded via "WP Offload Media", you can migrate to Next3 ('.$name_provider.').', 'next3-offload');?> </i></p>
        
        <?php }else{?>
            <p><i><?php echo esc_html__('Must be activate the "WP Offload Media" plugin, then you can start the migration process.', 'next3-offload');?></i>
                <!--a href="<?php echo esc_url(next3_admin_url( 'admin.php?page=next3aws&nx3_action=wpoffload_next3#ntab=tools' ));?>"><?php echo esc_html__('Restore All', 'next3-offload');?></a-->      
            </p>
        <?php }?>
        
    </div>
    <div class="next3-offload-pie">
        <p class="wpoffload-data"><strong><?php echo esc_html__('WP Offload Media:', 'next3-offload');?></strong> <span><?php echo esc_html($wpoffload_per);?>% (<?php echo number_format($wpoffload_done);?>/<?php echo number_format($wpoffload);?>)</span></p>
    </div>
</div>

<?php
$msg_offload = esc_html__($cloud_per .'% Files need to copy into ' . $name_provider, 'next3-offload');
if( $total_cloud != 0){
    //$msg_offload = esc_html__( $cloud_per .'% Files have been copied into ' . $name_provider, 'next3-offload');
}
if( !empty($offload_data)){
    $status_offload = ($offload_data['status']) ?? '';
    $total_offload = ($offload_data['total']) ?? 0;
    $start_offload = ($offload_data['start']) ?? 0;
    $type_offload = ($offload_data['type']) ?? '';

    if($status_offload == 'pause' && $type_offload == 'cloudto'){
        $msg_offload = esc_html__( $cloud_per .'% Files need to copy into '. $name_provider .', Paused!', 'next3-offload');
    }

    $start_offload = ($total_offload < $start_offload) ? $total_offload : $start_offload;
    if( $start_offload != 0  && $total_offload != 0){
        $persent_offload = floor(( $start_offload * 100) / $total_offload);
    }
    $txt_offload = $persent_offload . '% ('.$start_offload.'/'. $total_offload .')';
    if( $total_offload <= $start_offload || $type_offload != 'cloudto'){
        $status_offload = '';
        $persent_offload = 0;
        $txt_offload = '0% (0/0)';
    }
    
}
?>
<div class="next3aws-admin-toolbox-item next3-offload-wrap <?php echo esc_attr(($develeper_status == false) ? 'disabled business-plan' : '');?>"  data-action="cloudto">
    <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Cloud to Cloud ('.$name_provider.') copy', 'next3-offload');?></h3>
    <div class="next3aws-footer-content next3-offload-section">
        <p class="show-process"><?php echo esc_html__($msg_offload, 'next3-offload');?></p>
        <div class="next3-settings-progressbars next3-offload-bar <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>">
            <div class="complate-process" style="width:<?php echo esc_attr($cloud_per);?>%;"></div>
            <div class="next3-progressbar next3-progress-process" data-title="Process offload media"><span><?php echo esc_html($txt_offload);?></span></div>
        </div>
        <button class="action-button next3-offload-target next3-offload-action <?php echo esc_attr( ($status_offload == '') ? 'nxopen' : '' );?>" data-type="cloudto" href="#"><?php echo esc_html__('Copy Now', 'next3-offload');?></button>
        <button class="action-button next3-offload-target next3-offload-pause <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="<?php echo esc_attr( ($status_offload == 'pause') ? 'resume' : 'pause' );?>" href="#"><?php echo esc_html( ($status_offload == 'pause') ? 'Resume' : 'Pause' );?> </button>
        <button class="action-button next3-offload-target next3-offload-cancel <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="cancel" href="#"><?php echo esc_html__('Cancel', 'next3-offload');?></button>
        <p><i><br/><?php echo __('<strong>'. number_format($total_cloud) . '</strong> Files has been found in other cloud, you can copy to '.$name_provider.'.', 'next3-offload');?> </i></p>
    </div>
    <div class="next3-offload-pie">
        <p class="cloudto-data"><strong><?php echo esc_html__('Total Offloaded Files:', 'next3-offload');?></strong> <span><?php echo esc_html($cloud_per);?>% (<?php echo number_format($total_cloud);?>/<?php echo number_format($offload);?>)</span></p>
    </div>
</div>  

<?php
$msg_offload = esc_html__($localto_per .'% Files are sync between local and ' . $name_provider, 'next3-offload');
if( $total_local != 0){
    //$msg_offload = esc_html__( $localto_per .'% Files have been sync with ' . $name_provider, 'next3-offload');
}
if( !empty($offload_data)){
    $status_offload = ($offload_data['status']) ?? '';
    $total_offload = ($offload_data['total']) ?? 0;
    $start_offload = ($offload_data['start']) ?? 0;
    $type_offload = ($offload_data['type']) ?? '';

    if($status_offload == 'pause' && $type_offload == 'localto'){
        $msg_offload = esc_html__( $localto_per .'% Files are sync between local and '. $name_provider .', Paused!', 'next3-offload');
    }

    $start_offload = ($total_offload < $start_offload) ? $total_offload : $start_offload;
    if( $start_offload != 0  && $total_offload != 0){
        $persent_offload = floor(( $start_offload * 100) / $total_offload);
    }
    $txt_offload = $persent_offload . '% ('.$start_offload.'/'. $total_offload .')';
    if( $total_offload <= $start_offload || $type_offload != 'localto'){
        $status_offload = '';
        $persent_offload = 0;
        $txt_offload = '0% (0/0)';
    }
    
}
?>
<div class="next3aws-admin-toolbox-item next3-offload-wrap <?php echo esc_attr(($assets_status == false) ? 'disabled developer-plan' : '');?>"  data-action="localto">
    <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Local files between Cloud ('.$name_provider.') flies sync', 'next3-offload');?></h3>
    <div class="next3aws-footer-content next3-offload-section">
        <p class="show-process"><?php echo esc_html__($msg_offload, 'next3-offload');?></p>
        <div class="next3-settings-progressbars next3-offload-bar <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>">
            <div class="complate-process" style="width:<?php echo esc_attr($localto_per);?>%;"></div>
            <div class="next3-progressbar next3-progress-process" data-title="Process offload media"><span><?php echo esc_html($txt_offload);?></span></div>
        </div>
        <button class="action-button next3-offload-target next3-offload-action <?php echo esc_attr( ($status_offload == '') ? 'nxopen' : '' );?>" data-type="localto" href="#"><?php echo esc_html__('Sync Now', 'next3-offload');?></button>
        <button class="action-button next3-offload-target next3-offload-pause <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="<?php echo esc_attr( ($status_offload == 'pause') ? 'resume' : 'pause' );?>" href="#"><?php echo esc_html( ($status_offload == 'pause') ? 'Resume' : 'Pause' );?> </button>
        <button class="action-button next3-offload-target next3-offload-cancel <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="cancel" href="#"><?php echo esc_html__('Cancel', 'next3-offload');?></button>
        <p><i><br/><?php echo __('<strong>' . number_format($total_local) . '</strong> Files has been matched between local and cloud ('.$name_provider.'), you can merge with local files.', 'next3-offload');?> </i></p>
    </div>
    <div class="next3-offload-pie">
        <p class="localto-data"><strong><?php echo esc_html__('Total Matching Files:', 'next3-offload');?></strong> <span><?php echo esc_html($localto_per);?>% (<?php echo number_format($total_local);?>/<?php echo number_format($unoffload);?>)</span></p>
    </div>
</div> 

<?php }?>

<?php do_action('next3aws-sync-content-after', $settings_sync);?>