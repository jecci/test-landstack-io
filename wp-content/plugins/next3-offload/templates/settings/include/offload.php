<?php
    $off_per = ($offload_store['offload_per']) ?? 0;
    $unoff_per = ($offload_store['unoffload_per']) ?? 0;
    $clean_per = ($offload_store['clean_per']) ?? 0;
    $off_per_data = ($offload_store['offload']) ?? 0;
    $unoff_per_data = ($offload_store['unoffload']) ?? 0;
    $total_per_data = ($offload_store['total']) ?? 0;
    $total_clean = ($offload_store['clean']) ?? 0;

    $msg_offload = esc_html__($unoff_per . '% Local files needs to be offload in the cloud', 'next3-offload');
    if( $off_per != 0){
        $msg_offload = esc_html__( $off_per .'% Media files has been offloaded', 'next3-offload');
    }
            
    $status_offload = '';
    $txt_offload = '0% (0/0)';
    $persent_offload = '0';

    $credentials = next3_credentials();
    $config_data = next3_providers();

    $provider = ($credentials['settings']['provider']) ?? '';
    $name_provider = ($config_data[$provider]['label']) ?? '';

   
    $offload_data = next3_get_option('_next3_offload_data', []);
    
    if( !empty($offload_data)){
        $status_offload = ($offload_data['status']) ?? '';
        $total_offload = ($offload_data['total']) ?? 0;
        $start_offload = ($offload_data['start']) ?? 0;
        $type_offload = ($offload_data['type']) ?? '';

        if($status_offload == 'pause' && $type_offload == 'offload'){
            $msg_offload = esc_html__( $unoff_per .'% Local files are offloading process, Paused!', 'next3-offload');
        }

        $start_offload = ($total_offload < $start_offload) ? $total_offload : $start_offload;
        if( $start_offload != 0  && $total_offload != 0){
            $persent_offload = floor(( $start_offload * 100) / $total_offload);
        }
        $txt_offload = $persent_offload . '% ('.$start_offload.'/'. $total_offload .')';
        if( $total_offload <= $start_offload || $type_offload != 'offload'){
            $status_offload = '';
            $persent_offload = 0;
            $txt_offload = '0% (0/0)';
        }
        
    }
    
?>

<?php do_action('next3aws-offload-content-before', $settings_options, $offload_data);?>  

<div class="next3aws-admin-toolbox-item next3-offload-wrap"  data-action="offload">
    <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Offload your existing media files', 'next3-offload');?></h3>
    <p><?php echo esc_html__('You should have options to offload your existing media, and also media compress and WebP.', 'next3-offload');?></p>
        
    <div class="next3aws-footer-content next3-offload-section">
        <p class="show-process"><?php echo esc_html__($msg_offload, 'next3-offload');?></p>
        <div class="next3-settings-progressbars next3-offload-bar <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>">
            <div class="complate-process" style="width:<?php echo esc_attr($persent_offload);?>%;"></div>
            <div class="next3-progressbar next3-progress-process" data-title="Process offload media"><span><?php echo esc_html($txt_offload);?></span></div>
        </div>
        <button class="action-button next3-offload-target next3-offload-action <?php echo esc_attr( ($status_offload == '') ? 'nxopen' : '' );?>" data-type="offload" href="#"><?php echo esc_html__('Offload now', 'next3-offload');?></button>
        <button class="action-button next3-offload-target next3-offload-pause <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="<?php echo esc_attr( ($status_offload == 'pause') ? 'resume' : 'pause' );?>" href="#"><?php echo esc_html( ($status_offload == 'pause') ? 'Resume' : 'Pause' );?> </button>
        <button class="action-button next3-offload-target next3-offload-cancel <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="cancel" href="#"><?php echo esc_html__('Cancel', 'next3-offload');?></button>

        <p><i><br/><?php echo __('<strong>'. number_format($total_per_data - $off_per_data) . '</strong> Files has been found in local storage, now you can offload to the cloud ('.$name_provider.').', 'next3-offload');?> </i></p>
    </div>
    <div class="next3-offload-pie">
        <div class="next3-offload-pie-chart" style="background: conic-gradient(#165edd <?php echo esc_attr($off_per);?>%, #8d8d91 <?php echo esc_attr($off_per);?>% <?php echo esc_attr($unoff_per);?>%);" ></div>
        <p class="offload-data"><strong><?php echo esc_html__('Cloud storage:', 'next3-offload');?></strong> <span><?php echo esc_html($off_per);?>% (<?php echo number_format($off_per_data);?>/<?php echo number_format($total_per_data);?>)</span></p>
        <p class="unoffload-data"><strong><?php echo esc_html__('Local storage:', 'next3-offload');?></strong> <span><?php echo esc_html($unoff_per);?>% (<?php echo number_format($unoff_per_data);?>/<?php echo number_format($total_per_data);?>)</span></p>  
    </div>
</div>

<div class="next3aws-admin-toolbox-item">
    <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Individual offload existing media files', 'next3-offload');?></h3>
    <div class="next3aws-footer-content">
        <p><?php echo esc_html__('You should have options to offload your existing media, both individually or multiple.', 'next3-offload');?></p>
        <a class="action-button-upload" href="<?php echo esc_url(next3_admin_url( 'upload.php?mode=list' ));?>"><?php echo esc_html__('Offload now!', 'next3-offload');?></a>
    </div>
</div>
<?php

$msg_offload = esc_html__($unoff_per . '% Media files has been local storage', 'next3-offload');
if( $off_per != 0){
    $msg_offload = esc_html__( $off_per .'% Files has been offloaded, you can move to local storage', 'next3-offload');
}
if( !empty($offload_data)){
    $status_offload = ($offload_data['status']) ?? '';
    $total_offload = ($offload_data['total']) ?? 0;
    $start_offload = ($offload_data['start']) ?? 0;
    $type_offload = ($offload_data['type']) ?? '';

    if($status_offload == 'pause' && $type_offload == 'unoffload'){
        $msg_offload = esc_html__( $off_per .'% Files are moving process in local storage, Paused!', 'next3-offload');
    }
    
    $start_offload = ($total_offload < $start_offload) ? $total_offload : $start_offload;
    if( $start_offload != 0  && $total_offload != 0){
        $persent_offload = floor(( $start_offload * 100) / $total_offload);
    }
    $txt_offload = $persent_offload . '% ('.$start_offload.'/'. $total_offload .')';
    if( $total_offload <= $start_offload || $type_offload != 'unoffload'){
        $status_offload = '';
        $persent_offload = 0;
        $txt_offload = '0% (0/0)';
    }
    
}
?>

<div class="next3aws-admin-toolbox-item next3-offload-wrap"  data-action="unoffload">
    <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Offloaded files restore to local storage', 'next3-offload');?></h3>
    <div class="next3aws-footer-content next3-offload-section">
        <p class="show-process"><?php echo esc_html__($msg_offload, 'next3-offload');?></p>
        <div class="next3-settings-progressbars next3-offload-bar <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>">
            <div class="complate-process" style="width:<?php echo esc_attr($persent_offload);?>%;"></div>
            <div class="next3-progressbar next3-progress-process" data-title="Process offload media"><span><?php echo esc_html($txt_offload);?></span></div>
        </div>
        <button class="action-button next3-offload-target next3-offload-action <?php echo esc_attr( ($status_offload == '') ? 'nxopen' : '' );?>" data-type="unoffload" href="#"><?php echo esc_html__('Move now', 'next3-offload');?></button>
        <button class="action-button next3-offload-target next3-offload-pause <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="<?php echo esc_attr( ($status_offload == 'pause') ? 'resume' : 'pause' );?>" href="#"><?php echo esc_html( ($status_offload == 'pause') ? 'Resume' : 'Pause' );?> </button>
        <button class="action-button next3-offload-target next3-offload-cancel <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="cancel" href="#"><?php echo esc_html__('Cancel', 'next3-offload');?></button>
        <p><i><br/><?php echo __('<strong>'. number_format($off_per_data) . '</strong> Files has been found in the cloud ('.$name_provider.'), now you can restore to the local storage.', 'next3-offload');?> </i></p>
    </div>
    <div class="next3-offload-pie">
        <p class="unoffload-data"><strong><?php echo esc_html__('Local storage:', 'next3-offload');?></strong> <span><?php echo esc_html($unoff_per);?>% (<?php echo number_format($unoff_per_data);?>/<?php echo number_format($total_per_data);?>)</span></p>  
        <p class="offload-data"><strong><?php echo esc_html__('Cloud storage:', 'next3-offload');?></strong> <span><?php echo esc_html($off_per);?>% (<?php echo number_format($off_per_data);?>/<?php echo number_format($total_per_data);?>)</span></p>
    </div>
</div>

<?php

$msg_offload = esc_html__(number_format($off_per_data) .' Offloaded files need to remove from local storage', 'next3-offload');
if( $clean_per != 0){
    $msg_offload = esc_html__( $clean_per .'% Offloaded files has been removed from local storage', 'next3-offload');
}
if( !empty($offload_data)){
    $status_offload = ($offload_data['status']) ?? '';
    $total_offload = ($offload_data['total']) ?? 0;
    $start_offload = ($offload_data['start']) ?? 0;
    $type_offload = ($offload_data['type']) ?? '';

    if($status_offload == 'pause' && $type_offload == 'clean'){
        $msg_offload = esc_html__( number_format($off_per_data) .' Offloaded files are removing process from local storage, Paused!', 'next3-offload');
    }

    $start_offload = ($total_offload < $start_offload) ? $total_offload : $start_offload;
    if( $start_offload != 0  && $total_offload != 0){
        $persent_offload = floor(( $start_offload * 100) / $total_offload);
    }
    $txt_offload = $persent_offload . '% ('.$start_offload.'/'. $total_offload .')';
    if( $total_offload <= $start_offload || $type_offload != 'clean'){
        $status_offload = '';
        $persent_offload = 0;
        $txt_offload = '0% (0/0)';
    }
    
}
?>

<div class="next3aws-admin-toolbox-item next3-offload-wrap"  data-action="clean">
    <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Clean up offloaded files from local storage (!cloud)', 'next3-offload');?></h3>
    <div class="next3aws-footer-content next3-offload-section">
        <p class="show-process"><?php echo esc_html__($msg_offload, 'next3-offload');?></p>
        <div class="next3-settings-progressbars next3-offload-bar <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>">
            <div class="complate-process" style="width:<?php echo esc_attr($persent_offload);?>%;"></div>
            <div class="next3-progressbar next3-progress-process" data-title="Process offload media"><span><?php echo esc_html($txt_offload);?></span></div>
        </div>
        <button class="action-button next3-offload-target next3-offload-action <?php echo esc_attr( ($status_offload == '') ? 'nxopen' : '' );?>" data-type="clean" href="#"><?php echo esc_html__('Clean now', 'next3-offload');?></button>
        <button class="action-button next3-offload-target next3-offload-pause <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="<?php echo esc_attr( ($status_offload == 'pause') ? 'resume' : 'pause' );?>" href="#"><?php echo esc_html( ($status_offload == 'pause') ? 'Resume' : 'Pause' );?> </button>
        <button class="action-button next3-offload-target next3-offload-cancel <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="cancel" href="#"><?php echo esc_html__('Cancel', 'next3-offload');?></button>
        <p><i><br/><?php echo __('<strong>'. number_format($off_per_data - $total_clean) . '</strong> Files has been stored in the local storage after offloaded ('.$name_provider.'), now you can clean files from the local storage.', 'next3-offload');?> </i></p>
    </div>
    <div class="next3-offload-pie">
        <p class="offload-data"><strong><?php echo esc_html__('Offload files:', 'next3-offload');?></strong> <span><?php echo esc_html($off_per);?>% (<?php echo number_format($off_per_data);?>/<?php echo number_format($total_per_data);?>)</span></p>
        <p class="clean-data"><strong><?php echo esc_html__('Cleaned files:', 'next3-offload');?></strong> <span><?php echo esc_html($clean_per);?>% (<?php echo number_format($total_clean);?>/<?php echo number_format($off_per_data);?>)</span></p>  
    </div>
</div>

<?php
$get_package = next3_license_package();
?>
<?php 
if( in_array($get_package, ['developer', 'extended']) ){

    $css_per = ($offload_store['styles_per']) ?? 0;
    $total_css = ($offload_store['total_styles']) ?? 0;
    $total_css_done = ($offload_store['total_styles_done']) ?? 0;

    $msg_offload = esc_html__(number_format($total_css) .' CSS files needs to be offload', 'next3-offload');
    if( $css_per != 0){
        $msg_offload = esc_html__( $css_per .'% CSS files has been offloaded', 'next3-offload');
    }
    if( !empty($offload_data)){
        $status_offload = ($offload_data['status']) ?? '';
        $total_offload = ($offload_data['total']) ?? 0;
        $start_offload = ($offload_data['start']) ?? 0;
        $type_offload = ($offload_data['type']) ?? '';

        if($status_offload == 'pause' && $type_offload == 'styles'){
            $msg_offload = esc_html__( number_format($total_css - $total_css_done) .' CSS files offloading process, Paused!', 'next3-offload');
        }

        $start_offload = ($total_offload < $start_offload) ? $total_offload : $start_offload;
        if( $start_offload != 0  && $total_offload != 0){
            $persent_offload = floor(( $start_offload * 100) / $total_offload);
        }
        $txt_offload = $persent_offload . '% ('.$start_offload.'/'. $total_offload .')';
        if( $total_offload <= $start_offload || $type_offload != 'styles'){
            $status_offload = '';
            $persent_offload = 0;
            $txt_offload = '0% (0/0)';
        }
        
    }    
?>
    <div class="next3aws-admin-toolbox-item next3-offload-wrap"  data-action="styles">
        <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Offload existing CSS files to cloud', 'next3-offload');?></h3>
        <div class="next3aws-footer-content next3-offload-section">
            <p class="show-process"><?php echo esc_html__($msg_offload, 'next3-offload');?></p>
            <div class="next3-settings-progressbars next3-offload-bar <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>">
                <div class="complate-process" style="width:<?php echo esc_attr($persent_offload);?>%;"></div>
                <div class="next3-progressbar next3-progress-process" data-title="Process offload media"><span><?php echo esc_html($txt_offload);?></span></div>
            </div>
            <button class="action-button next3-offload-target next3-offload-action <?php echo esc_attr( ($status_offload == '') ? 'nxopen' : '' );?>" data-type="styles" href="#"><?php echo esc_html__('Offload now', 'next3-offload');?></button>
            <button class="action-button next3-offload-target next3-offload-pause <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="<?php echo esc_attr( ($status_offload == 'pause') ? 'resume' : 'pause' );?>" href="#"><?php echo esc_html( ($status_offload == 'pause') ? 'Resume' : 'Pause' );?> </button>
            <button class="action-button next3-offload-target next3-offload-cancel <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="cancel" href="#"><?php echo esc_html__('Cancel', 'next3-offload');?></button>
        </div>
        <div class="next3-offload-pie">
            <p class="styles-data"><strong><?php echo esc_html__('CSS offload:', 'next3-offload');?></strong> <span><?php echo esc_html($css_per);?>% (<?php echo number_format($total_css_done);?>/<?php echo number_format($total_css);?>)</span></p>
        </div>
    </div>
    <?php
    $scripts_per = ($offload_store['scripts_per']) ?? 0;
    $total_scripts = ($offload_store['total_scripts']) ?? 0;
    $total_scripts_done = ($offload_store['total_scripts_done']) ?? 0;

    $msg_offload = esc_html__(number_format($total_scripts) .' JS files needs to be offload', 'next3-offload');
    if( $scripts_per != 0){
        $msg_offload = esc_html__( $scripts_per .'% JS files has been offloaded', 'next3-offload');
    }
    if( !empty($offload_data)){
        $status_offload = ($offload_data['status']) ?? '';
        $total_offload = ($offload_data['total']) ?? 0;
        $start_offload = ($offload_data['start']) ?? 0;
        $type_offload = ($offload_data['type']) ?? '';

        if($status_offload == 'pause' && $type_offload == 'scripts'){
            $msg_offload = esc_html__( number_format($total_scripts - $total_scripts_done) .' JS files offloading process, Paused!', 'next3-offload');
        }

        $start_offload = ($total_offload < $start_offload) ? $total_offload : $start_offload;
        if( $start_offload != 0  && $total_offload != 0){
            $persent_offload = floor(( $start_offload * 100) / $total_offload);
        }
        $txt_offload = $persent_offload . '% ('.$start_offload.'/'. $total_offload .')';
        if( $total_offload <= $start_offload || $type_offload != 'scripts'){
            $status_offload = '';
            $persent_offload = 0;
            $txt_offload = '0% (0/0)';
        }
        
    }  
    ?>
    <div class="next3aws-admin-toolbox-item next3-offload-wrap"  data-action="scripts">
        <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Offload existing JS files to cloud', 'next3-offload');?></h3>
        <div class="next3aws-footer-content next3-offload-section">
            <p class="show-process"><?php echo esc_html__($msg_offload, 'next3-offload');?></p>
            <div class="next3-settings-progressbars next3-offload-bar <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>">
                <div class="complate-process" style="width:<?php echo esc_attr($persent_offload);?>%;"></div>
                <div class="next3-progressbar next3-progress-process" data-title="Process offload media"><span><?php echo esc_html($txt_offload);?></span></div>
            </div>
            <button class="action-button next3-offload-target next3-offload-action <?php echo esc_attr( ($status_offload == '') ? 'nxopen' : '' );?>" data-type="scripts" href="#"><?php echo esc_html__('Offload now', 'next3-offload');?></button>
            <button class="action-button next3-offload-target next3-offload-pause <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="<?php echo esc_attr( ($status_offload == 'pause') ? 'resume' : 'pause' );?>" href="#"><?php echo esc_html( ($status_offload == 'pause') ? 'Resume' : 'Pause' );?> </button>
            <button class="action-button next3-offload-target next3-offload-cancel <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="cancel" href="#"><?php echo esc_html__('Cancel', 'next3-offload');?></button>
        </div>
        <div class="next3-offload-pie">
            <p class="scripts-data"><strong><?php echo esc_html__('JS offload:', 'next3-offload');?></strong> <span><?php echo esc_html($scripts_per);?>% (<?php echo number_format($total_scripts_done);?>/<?php echo number_format($total_scripts);?>)</span></p>
        </div>
    </div>
<?php }?>

<?php do_action('next3aws-offload-content-after', $settings_options, $offload_data);?>  