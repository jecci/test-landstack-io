<div class="services-data">
    <form method="post" action="#" class="next3offload-settings" name="next3offload-settings">
                            
        <h3 class="next3offload-heading"><?php echo esc_html('Select Service', 'next3-offload');?></h3>
        <div class="next3offload-services"> 
        <?php
        $get_package = next3_license_package();

        $services = [
            'offload' => [ 'label' => 'Offload media', 'icon' => '']
        ];

        //if( in_array($get_package, ['business', 'developer', 'extended']) ){
            $services['optimization'] = [ 'label' => 'Image optimization', 'icon' => ''];
            $services['database'] = [ 'label' => 'Database optimization', 'icon' => ''];
       // }
        $services = apply_filters('next3/services/add', $services);

        if( !empty($services) ){
            foreach( $services as $k=>$v){
                if( empty($v) ){
                    continue;
                }
                $lavel = ($v['label']) ?? '';
                $img = ($v['icon']) ?? '';
                
                $enable = ($credentials['settings']['services']) ?? ['offload', 'optimization', 'database'];
                ?>
                <div class="next3offload-service ">  
                    <div class="pro-img <?php echo (in_array($k, $enable)) ? esc_attr('activediv') : '';?>">
                        <input type="checkbox" <?php echo (in_array($k, $enable)) ? esc_attr('checked') : '';?> name="next3setup[settings][services][]" id="next3offload_<?php echo esc_attr($k);?>" value="<?php echo esc_attr($k);?>" data-value="<?php echo esc_attr($k);?>">
                        <div class="nx-select-bg-border"></div>
                        <div class="nx-checkmark"></div>
                        <label for="next3offload_<?php echo esc_attr($k);?>"><?php echo esc_html($lavel);?></label>   
                    </div>
                </div>
                <?php
            }
        }
        ?>
        </div>
        
        <div class="next3offload-footer">
            <div class="next3offload-admin-setting-button border-top">
                <a class="prev-btn" href="<?php echo next3_admin_url( 'admin.php?page=next3aws&step=license' );?>" class="next3offload-submit"><i></i> <?php echo esc_html('Previous', 'next3-offload');?></a>
            </div>
            <div class="next3offload-admin-setting-button border-top">
                <button type="submit" class="next3offload-submit"><i></i> <?php echo esc_html('Next', 'next3-offload');?></button>
            </div>
        </div>
    </form>
</div>