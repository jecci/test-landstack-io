<?php
    $msg = isset($_GET['msg']) ? sanitize_text_field($_GET['msg']) : '';
    switch($msg){
        case 'settings_cleared':
            echo next3_print('<h4> '. esc_html__('Successfully reset plugin settings...', 'next3-offload') .' </h4>');
        break;
        case 'cache_cleared':
            echo next3_print('<h4> '. esc_html__('Successfully reset credentials...', 'next3-offload') .' </h4>');
        break;
        case 'css_unoffload':
            echo next3_print('<h4> '. esc_html__('Successfully deleted CSS offload information...', 'next3-offload') .' </h4>');
        break;
        case 'js_unoffload':
            echo next3_print('<h4> '. esc_html__('Successfully deleted JS offload information...', 'next3-offload') .' </h4>');
        break;
        case 'restore_compress':
            echo next3_print('<h4> '. esc_html__('Successfully restored all backup...', 'next3-offload') .' </h4>');
        break;
        case 'webp_remove':
            echo next3_print('<h4> '. esc_html__('Successfully deleted all WebP...', 'next3-offload') .' </h4>');
        break;
        case 'wpoffload_action':
            echo next3_print('<h4> '. esc_html__('Successfully restored to Next3...', 'next3-offload') .' </h4>');
        break;
        
    }

    $wpoffload = ($offload_store['wpoffload']) ?? 0;
    $wpoffload_done = ($offload_store['wpoffload_done']) ?? 0;
    $wpoffload_per = ($offload_store['wpoffload_per']) ?? 0;
    $total_optimize = ($offload_store['total_optimize']) ?? 0;
    $total_webp_done = ($offload_store['total_webp_done']) ?? 0;
    $total_compress_done = ($offload_store['total_compress_done']) ?? 0;
    $webp_per = ($offload_store['webp_per']) ?? 0;
    $compress_per = ($offload_store['compress_per']) ?? 0;
    $total_backup = ($offload_store['total_backup']) ?? 0;
    

    $offload_data = next3_get_option('_next3_offload_data', []);
     
    $get_package = next3_license_package();
?>

<?php do_action('next3aws-tools-content-before', $offload_data);?>  

<?php 
if( $status_optimization ){

$persent_offload = '0';
$status_offload = '';
$txt_offload = '0% (0/0)';

$msg_offload = esc_html__('Compress: '.$compress_per .'% and WebP: '.$webp_per.'% files need to optimize', 'next3-offload');
if( $total_webp_done != 0 || $total_compress_done != 0){
    $msg_offload = esc_html__( 'Compress: '.$compress_per .'% and WebP: '.$webp_per.'% files has been optimized', 'next3-offload');
}
if( !empty($offload_data)){
    $status_offload = ($offload_data['status']) ?? '';
    $total_offload = ($offload_data['total']) ?? 0;
    $start_offload = ($offload_data['start']) ?? 0;
    $type_offload = ($offload_data['type']) ?? '';

    if($status_offload == 'pause' && $type_offload == 'compress'){
        $msg_offload = esc_html__( 'Compress: '. number_format($total_optimize - $total_compress_done)  .' and WebP: '. number_format($total_optimize - $total_webp_done) .' Local files are optimizing process, Paused!', 'next3-offload');
    }

    $start_offload = ($total_offload < $start_offload) ? $total_offload : $start_offload;
    if( $start_offload != 0  && $total_offload != 0){
        $persent_offload = floor(( $start_offload * 100) / $total_offload);
    }
    $txt_offload = $persent_offload . '% ('.$start_offload.'/'. $total_offload .')';
    if( $total_offload <= $start_offload || $type_offload != 'compress'){
        $status_offload = '';
        $persent_offload = 0;
        $txt_offload = '0% (0/0)';
    }
    
}
?>


<div class="next3aws-admin-toolbox-item next3-offload-wrap <?php echo esc_attr(($develeper_status == false) ? 'disabled business-plan' : '');?>"  data-action="compress">
    <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Compress & WebP convert: only local storage media files', 'next3-offload');?></h3>
    <div class="next3aws-footer-content next3-offload-section">
        <p class="show-process"><?php echo esc_html__($msg_offload, 'next3-offload');?></p>
        <div class="next3-settings-progressbars next3-offload-bar <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>">
            <div class="complate-process" style="width:<?php echo esc_attr($persent_offload);?>%;"></div>
            <div class="next3-progressbar next3-progress-process" data-title="Process offload media"><span><?php echo esc_html($txt_offload);?></span></div>
        </div>
        <button class="action-button next3-offload-target next3-offload-action <?php echo esc_attr( ($status_offload == '') ? 'nxopen' : '' );?>" data-type="compress" href="#"><?php echo esc_html__('Start now', 'next3-offload');?></button>
        <button class="action-button next3-offload-target next3-offload-pause <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="<?php echo esc_attr( ($status_offload == 'pause') ? 'resume' : 'pause' );?>" href="#"><?php echo esc_html( ($status_offload == 'pause') ? 'Resume' : 'Pause' );?> </button>
        <button class="action-button next3-offload-target next3-offload-cancel <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="cancel" href="#"><?php echo esc_html__('Cancel', 'next3-offload');?></button>

        <p><i><br/><?php echo __('<strong>'. number_format($total_optimize ) . '</strong> Files has been found in local storage !optimize, now you can optimize all files.', 'next3-offload');?> </i></p>
    </div>
    <div class="next3-offload-pie">
        <p class="compress-data"><strong><?php echo esc_html__('Compress files:', 'next3-offload');?></strong> <span><?php echo esc_html($compress_per);?>% (<?php echo number_format($total_compress_done);?>/<?php echo number_format($total_optimize);?>)</span></p>
        <p class="webp-data"><strong><?php echo esc_html__('WebP files:', 'next3-offload');?></strong> <span><?php echo esc_html($webp_per);?>% (<?php echo number_format($total_webp_done);?>/<?php echo number_format($total_optimize);?>)</span></p>  
    </div>
</div>

<?php

$msg_offload = esc_html__(number_format($total_backup) .' Backup files you will restore in local', 'next3-offload');

if( !empty($offload_data)){
    $status_offload = ($offload_data['status']) ?? '';
    $total_offload = ($offload_data['total']) ?? 0;
    $start_offload = ($offload_data['start']) ?? 0;
    $type_offload = ($offload_data['type']) ?? '';

    if($status_offload == 'pause' && $type_offload == 'backupre'){
        $msg_offload = esc_html__( number_format($total_backup) .' Backup files restore process, Paused!', 'next3-offload');
    }

    $start_offload = ($total_offload < $start_offload) ? $total_offload : $start_offload;
    if( $start_offload != 0  && $total_offload != 0){
        $persent_offload = floor(( $start_offload * 100) / $total_offload);
    }
    $txt_offload = $persent_offload . '% ('.$start_offload.'/'. $total_offload .')';
    if( $total_offload <= $start_offload || $type_offload != 'backupre'){
        $status_offload = '';
        $persent_offload = 0;
        $txt_offload = '0% (0/0)';
    }
    
}
?>

<div class="next3aws-admin-toolbox-item next3-offload-wrap <?php echo esc_attr(($develeper_status == false) ? 'disabled business-plan' : '');?>"  data-action="backupre">
    <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Restore backup', 'next3-offload');?></h3>
    <div class="next3aws-footer-content next3-offload-section">
        <p class="show-process"><?php echo esc_html__($msg_offload, 'next3-offload');?></p>
        <div class="next3-settings-progressbars next3-offload-bar <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>">
            <div class="complate-process" style="width:<?php echo esc_attr($persent_offload);?>%;"></div>
            <div class="next3-progressbar next3-progress-process" data-title="Process offload media"><span><?php echo esc_html($txt_offload);?></span></div>
        </div>
        <button class="action-button next3-offload-target next3-offload-action <?php echo esc_attr( ($status_offload == '') ? 'nxopen' : '' );?>" data-type="backupre" href="#"><?php echo esc_html__('Restore now', 'next3-offload');?></button>
        <button class="action-button next3-offload-target next3-offload-pause <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="<?php echo esc_attr( ($status_offload == 'pause') ? 'resume' : 'pause' );?>" href="#"><?php echo esc_html( ($status_offload == 'pause') ? 'Resume' : 'Pause' );?> </button>
        <button class="action-button next3-offload-target next3-offload-cancel <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="cancel" href="#"><?php echo esc_html__('Cancel', 'next3-offload');?></button>

        <p><i><br/><?php echo __('<strong>'. number_format($total_backup ) . '</strong> Backup files has been found in local storage, now you can restore all files.', 'next3-offload');?> </i></p>
    </div>
</div>

<?php

$msg_offload = esc_html__(number_format($total_backup) .' Backup files you will delete in local', 'next3-offload');

if( !empty($offload_data)){
    $status_offload = ($offload_data['status']) ?? '';
    $total_offload = ($offload_data['total']) ?? 0;
    $start_offload = ($offload_data['start']) ?? 0;
    $type_offload = ($offload_data['type']) ?? '';

    if($status_offload == 'pause' && $type_offload == 'backude'){
        $msg_offload = esc_html__( number_format($total_backup) .' Backup files delete process, Paused!', 'next3-offload');
    }

    $start_offload = ($total_offload < $start_offload) ? $total_offload : $start_offload;
    if( $start_offload != 0  && $total_offload != 0){
        $persent_offload = floor(( $start_offload * 100) / $total_offload);
    }
    $txt_offload = $persent_offload . '% ('.$start_offload.'/'. $total_offload .')';
    if( $total_offload <= $start_offload || $type_offload != 'backude'){
        $status_offload = '';
        $persent_offload = 0;
        $txt_offload = '0% (0/0)';
    }
    
}
?>

<div class="next3aws-admin-toolbox-item next3-offload-wrap <?php echo esc_attr(($develeper_status == false) ? 'disabled business-plan' : '');?>"  data-action="backude">
    <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Delete backup', 'next3-offload');?></h3>
    <div class="next3aws-footer-content next3-offload-section">
        <p class="show-process"><?php echo esc_html__($msg_offload, 'next3-offload');?></p>
        <div class="next3-settings-progressbars next3-offload-bar <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>">
            <div class="complate-process" style="width:<?php echo esc_attr($persent_offload);?>%;"></div>
            <div class="next3-progressbar next3-progress-process" data-title="Process offload media"><span><?php echo esc_html($txt_offload);?></span></div>
        </div>
        <button class="action-button next3-offload-target next3-offload-action <?php echo esc_attr( ($status_offload == '') ? 'nxopen' : '' );?>" data-type="backude" href="#"><?php echo esc_html__('Delete now', 'next3-offload');?></button>
        <button class="action-button next3-offload-target next3-offload-pause <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="<?php echo esc_attr( ($status_offload == 'pause') ? 'resume' : 'pause' );?>" href="#"><?php echo esc_html( ($status_offload == 'pause') ? 'Resume' : 'Pause' );?> </button>
        <button class="action-button next3-offload-target next3-offload-cancel <?php echo esc_attr( ($status_offload != '') ? 'nxopen' : '' );?>" data-type="cancel" href="#"><?php echo esc_html__('Cancel', 'next3-offload');?></button>

        <p><i><br/><?php echo __('<strong>'. number_format($total_backup ) . '</strong> Backup files has been found in local storage, now you can delete all files.', 'next3-offload');?> </i></p>
    </div>
</div>

<?php }?>


<?php if( $offload_status ){?>
    
<div class="next3aws-admin-toolbox-item <?php echo esc_attr(($assets_status == false) ? 'disabled developer-plan' : '');?>">
    <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Restore offloaded CSS files', 'next3-offload');?></h3>
    <div class="next3aws-footer-content">
        <p><?php echo esc_html__('This tool will delete all CSS offloaded files information.', 'next3-offload');?></p>
        <a class="reset-button1 action-button" href="<?php echo esc_url(next3_admin_url( 'admin.php?page=next3aws&nx3_action=css_settings#ntab=tools' ));?>"><?php echo esc_html__('Delete offloaded CSS', 'next3-offload');?></a>
    </div>
</div>

<div class="next3aws-admin-toolbox-item <?php echo esc_attr(($assets_status == false) ? 'disabled developer-plan' : '');?>">
    <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Restore offloaded JS files', 'next3-offload');?></h3>
    <div class="next3aws-footer-content">
        <p><?php echo esc_html__('This tool will delete all JS offloaded files information.', 'next3-offload');?></p>
        <a class="reset-button1 action-button" href="<?php echo esc_url(next3_admin_url( 'admin.php?page=next3aws&nx3_action=js_settings#ntab=tools' ));?>"><?php echo esc_html__('Delete offloaded JS', 'next3-offload');?></a>
    </div>
</div>

<?php }?>

<div class="next3aws-admin-toolbox-item">
    <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Reset credentials', 'next3-offload');?></h3>
    <div class="next3aws-footer-content">
        <p><?php echo esc_html__('This tool will remove provider credentials of Next3 Offload', 'next3-offload');?></p>
        <a class="reset-button1 action-button" href="<?php echo esc_url(next3_admin_url( 'admin.php?page=next3aws&nx3_action=clear_cache#ntab=tools' ));?>"><?php echo esc_html__('Reset', 'next3-offload');?></a>
    </div>
   
</div>

<div class="next3aws-admin-toolbox-item">
    <h3 class="next3aws-toolbox-heading"><?php echo esc_html__('Reset settings', 'next3-offload');?></h3>
    <div class="next3aws-footer-content">
        <p><?php echo esc_html__('This tool will delete all the plugin settings of Next3 Offload', 'next3-offload');?></p>
        <a class="reset-button1 action-button" href="<?php echo esc_url(next3_admin_url( 'admin.php?page=next3aws&nx3_action=clear_settings#ntab=tools' ));?>"><?php echo esc_html__('Reset', 'next3-offload');?></a>
    </div>
</div>

<?php do_action('next3aws-tools-content-after', $offload_data);?>  