<div class="providers-data">
    <form method="post" action="#" class="next3offload-settings" name="next3offload-settings">
                            
        <h3 class="next3offload-heading"><?php echo esc_html('Choose Cloud providers', 'next3-offload');?></h3>
        <div class="next3offload-providers"> 
        <?php
        
        $providers = next3_providers();
        if( !empty($providers) ){
            foreach( $providers as $k=>$v){
                if( empty($v) ){
                    continue;
                }
                $lavel = ($v['label']) ?? '';
                $img = ($v['img']) ?? '';
                $upcomming = ($v['upcomming']) ?? '';
                
                $enable = ($credentials['settings']['provider']) ?? 'aws';
                ?>
                <div class="next3offload-provider <?php echo esc_attr($upcomming);?> <?php echo esc_attr( !empty($upcomming) ? 'pro-disabled' : '' );?>">  
                    <div class="pro-img <?php echo ($enable == $k) ? esc_attr('activediv') : '';?>">
                        <?php if( !empty($img) ): ?>
                        <img src="<?php echo esc_url($img);?>" alt="<?php echo esc_attr($lavel);?>">
                        <?php endif;?> 
                        <!-- <i class="dashicons dashicons-yes"></i>    -->
                        <span class="nx-provider-marked"></span>
                    </div>
                    <div class="pro-img-label">
                        <input type="radio" <?php echo ($enable == $k) ? esc_attr('checked') : '';?> name="next3setup[settings][provider]" id="next3offload_<?php echo esc_attr($k);?>" value="<?php echo esc_attr($k);?>" data-value="<?php echo esc_attr($k);?>">
                        <label for="next3offload_<?php echo esc_attr($k);?>"><?php echo esc_html($lavel);?></label>    
                    </div>
                </div>
                <?php
            }
        }
        ?>
        </div>
        <div class="next3offload-providers-data">
            
        <?php
        if( !empty($providers) ){
            foreach( $providers as $k=>$v){
                if( empty($v) ){
                    continue;
                }
                $lavel = ($v['label']) ?? '';
                $docs_link = ($v['docs_link']) ?? '';
                $field = ($v['field']) ?? [];
                $provider = ($credentials['settings']['provider']) ?? 'aws';
                $upcomming = ($v['upcomming']) ?? '';

                if( !empty( $upcomming ) ){
                    continue;
                }
            ?>
            <div class="next3offload-provider-data render-filed-data provider-<?php echo esc_attr($k);?> <?php echo ($provider == $k) ? esc_attr('activediv') : '';?>">
                <h3 class="next3offload-heading"><?php echo esc_html( $lavel . ' Settings', 'next3-offload');?>
                <?php if( !empty($docs_link)){ ?>
                <a href="<?php echo esc_url($docs_link);?>" target="_blank"><?php echo esc_html( 'Follow this blog', 'next3-offload');?></a>
                <?php }?>
                </h3>
                <?php
                    if( !empty($field) ){
                        foreach($field as $kf=>$fl){
                            $f_title = ($fl['title']) ?? '';
                            $f_docs_link = ($fl['docs_link']) ?? '';
                            $f_desc = ($fl['desc']) ?? '';
                            $f_field = ($fl['field']) ?? [];

                            $type_provider = ($credentials['settings'][$k]['type']) ?? 'credentails';
                            $checked = ($kf == $type_provider) ? 'checked' : '';
                            ?>
                                <div class="next3offload-types">
                                    <div class="next3offload-types-header">
                                        <label>
                                        <input type="radio" class="next3offload-types-control" data-class="typbody-<?php echo esc_attr($k);?>" data-id="nxtypes-<?php echo esc_attr($k);?>-<?php echo esc_attr( $kf );?>" name="next3setup[settings][<?php echo esc_attr($k);?>][type]" value="<?php echo esc_attr( $kf );?>" <?php echo esc_attr($checked);?>>
                                        <div class="nx-option-check"></div>
                                        <?php if( !empty($f_title) ){?><span class="nx-options"><?php echo esc_html($f_title );?></span><?php }?>
                                        </label>
                                        <?php if( !empty($f_desc) || !empty($f_docs_link) ){?><p><?php echo next3_print($f_desc );?> <?php if( !empty($f_docs_link) ){?><a href="<?php echo esc_url($f_docs_link);?>" target="_blank"><?php echo esc_html('Read More' );?><?php }?></a> </p><?php }?> 
                                    </div>
                                    <div class="next3offload-types-body <?php echo esc_attr('typbody-' . $k);?> <?php echo esc_attr(( $kf == $type_provider ) ? 'nxopend' : '');?>" data-target="nxtypes-<?php echo esc_attr($k);?>-<?php echo esc_attr( $kf );?>">
                                     
                                     <?php if( !empty($f_field) ){
                                        foreach($f_field as $kk=>$vv){
                                            $readonly = isset($vv['readonly']) ? 'readonly' : '';
                                            $render = isset($vv['render']) ? $vv['render'] : true;
                                            $type = ($vv['type']) ?? 'text';
                                            $label = ($vv['label']) ?? '';
                                            $placeholder = ($vv['placeholder']) ?? '';
                                            $decs = ($vv['decs']) ?? '';
                                            $default = ($vv['default']) ?? '';
                                            $options = ($vv['options']) ?? [ '' => esc_html__('- Select once -', 'next3-offload')];

                                            $name = 'next3setup[settings]['. $k .']['. $kf .']';
                                            if( in_array($kk, ['default_region'])){
                                                $name = 'next3setup[settings]['. $k .']';
                                            }

                                            if($type == 'checkbox'){
                                            ?>
                                            <div class="settings-item">
                                                <?php if( !empty($label) ){?><label for="next3offload-<?php echo esc_attr($k);?>-<?php echo esc_attr( $kf );?>-<?php echo esc_attr($kk);?>"><?php echo esc_html__($label, 'next3-offload');?></label><?php }?>
                                                <p><input type="checkbox" <?php echo esc_attr( ($default == 'yes') ? 'checked' : '');?> <?php if($render == true){?>name="<?php echo esc_attr($name);?>[<?php echo esc_attr($kk);?>]"<?php }?> id="next3offload-<?php echo esc_attr($k);?>-<?php echo esc_attr( $kf );?>-<?php echo esc_attr($kk);?>" value="yes">
                                                <?php if( !empty($decs) ){?><?php echo esc_html__($decs, 'next3-offload');?><?php }?>
                                                </p> 
                                            </div>
                                            <?php
                                            } else if( $type == 'select' ) {
                                            ?>
                                                <div class="settings-item">
                                                <?php if( !empty($label) ){?><label for="next3offload-<?php echo esc_attr($k);?>-<?php echo esc_attr( $kf );?>-<?php echo esc_attr($kk);?>"><?php echo esc_html__($label, 'next3-offload');?></label><?php }?>
                                                    <select <?php if($render == true){?>name="<?php echo esc_attr($name);?>[<?php echo esc_attr($kk);?>]"<?php }?> id="next3offload-<?php echo esc_attr($k);?>-<?php echo esc_attr( $kf );?>-<?php echo esc_attr($kk);?>" class="settings-input">
                                                        
                                                        <?php if( !empty($options) ){
                                                            foreach($options as $op_k=>$op_v){
                                                                $selected = ($op_k == $default) ? true : false;
                                                                ?>
                                                                <option value="<?php echo esc_attr($op_k);?>" <?php echo ($selected == true) ? 'selected' : '';?>><?php echo esc_html__($op_v, 'next3-offload');?></option>
                                                                <?php
                                                            }
                                                        }?>
                                                    </select>
                                                    
                                                </div>
                                                <p><?php if( !empty($decs) ){?><?php echo esc_html($decs);?><?php }?></p>
                                                
                                            <?php   
                                            } else if( $type == 'textarea'){
                                                ?>
                                                <div class="settings-item">
                                                    <?php if( !empty($label) ){?><label class="nx-textarea-title" for="next3offload-<?php echo esc_attr($k);?>-<?php echo esc_attr( $kf );?>-<?php echo esc_attr($kk);?>"><?php echo esc_html__($label, 'next3-offload');?></label><?php }?>
                                                    <textarea type="<?php echo esc_attr($type);?>" <?php if($render == true){?>name="<?php echo esc_attr($name);?>[<?php echo esc_attr($kk);?>]"<?php }?>  id="next3offload-<?php echo esc_attr($k);?>-<?php echo esc_attr( $kf );?>-<?php echo esc_attr($kk);?>" class="settings-input" placeholder="<?php echo esc_html__($placeholder, 'next3-offload');?>" <?php echo esc_attr($readonly);?>><?php echo esc_attr( $default );?></textarea>
                                                </div>
                                                <?php
                                            } else if( $type == 'heading'){
                                                ?>
                                                <div class="settings-item">
                                                    <?php if( !empty($label) ){?><label for="next3offload-<?php echo esc_attr($k);?>-<?php echo esc_attr( $kf );?>-<?php echo esc_attr($kk);?>"><?php echo esc_html__($label, 'next3-offload');?></label><?php }?>
                                                </div>
                                                <?php
                                            } else {
                                                ?>
                                                <div class="settings-item">
                                                    <?php if( !empty($label) ){?><label for="next3offload-<?php echo esc_attr($k);?>-<?php echo esc_attr( $kf );?>-<?php echo esc_attr($kk);?>"><?php echo esc_html__($label, 'next3-offload');?></label><?php }?>
                                                    <input type="<?php echo esc_attr($type);?>" <?php if($render == true){?>name="<?php echo esc_attr($name);?>[<?php echo esc_attr($kk);?>]"<?php }?>  id="next3offload-<?php echo esc_attr($k);?>-<?php echo esc_attr( $kf );?>-<?php echo esc_attr($kk);?>" class="settings-input" placeholder="<?php echo esc_html__($placeholder, 'next3-offload');?>" value="<?php echo esc_attr( $default );?>">
                                                </div>
                                                <?php
                                            }
                                        }
                                    }
                                    ?>    
                                </div>
                                </div>
                            <?php
                        }
                    }
                ?>
            </div>
        <?php 
            }
        }?>
        </div>
        <div class="next3offload-footer">
            <div class="next3offload-admin-setting-button border-top">
                <a href="<?php echo next3_admin_url( 'admin.php?page=next3aws&step=service' );?>" class="prev-btn next3offload-submit"><i></i> <?php echo esc_html('Previous', 'next3-offload');?></a>
            </div>
            <div class="next3offload-admin-setting-button border-top">
                <button type="submit" class="next3offload-submit"><i></i> <?php echo esc_html('Next', 'next3-offload');?></button>
            </div>
        </div>
    </form>
</div>