<div class="nx-container">
    <div class="row">
        <div class="nx-col-md-8 nx-col-sm-12 nx-pt-50 nx-mx-auto">
            <div class="next3offload-settings-wrap">
                <div class="wrap-header">
                    <h2> <?php esc_html_e('Setup', 'next3-offload');?> </h2>
                    <div class="next3-progressbars-wrap">
                        <div class="next3-progressbar-show">
                        <?php 
                        if($stepno == 1){
                            echo esc_html('STEP 1/4: Activate license');
                        } else if( $stepno == 2){
                            echo esc_html('STEP 2/4');
                        } else if( $stepno == 3){
                            echo esc_html('STEP 3/4');
                        } else if( $stepno == 4 ){
                            echo esc_html('STEP 4/4: Configuration');
                        }
                        ?>
                        </div>
                    </div>
                </div>
                <?php
                $credentials = next3_credentials();
                ?>
                <div class="next3offload-body">
                    <div class="next3offload-steps" >
                    <?php if( !empty($step_msg) ){?><p class="error-print"><span>&#x26A0</span> <?php echo next3_print($step_msg);?></p><?php }?>
                        <div class="offload-content">
                            <?php 
                            switch($step){
            
                                case 'license':
                                    $status = \Next3Offload\Utilities\Check\N3aws_Valid::instance()->_get_action();
                                    $key_data = next3_get_option('__validate_author_next3aws_keys__', '');
                                    $data = \Next3Offload\Utilities\Check\N3aws_Valid::instance()->get_pro($key_data);
                                    $typeitem = isset($data->typeitem) ? $data->typeitem : '';
            
                                    include( next3_core()->plugin::plugin_dir().'templates/step/license.php' );
                                    break;
                                
                                case 'provider':
                                    include( next3_core()->plugin::plugin_dir().'templates/step/providers.php' );
                                    break;
                                
                                case 'config':
                                    include( next3_core()->plugin::plugin_dir().'templates/step/config.php' );
                                    break;
                
                                default:
                                    include( next3_core()->plugin::plugin_dir().'templates/step/service.php' );
                                    break;
                            }
                            ?>
                        </div>
                    </div>     
                </div>        
            </div>

        </div>
    </div>
</div>