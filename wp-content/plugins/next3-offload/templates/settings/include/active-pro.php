<div class="nextactive-license">
    <div class="nextactive_container" >
        <form action="" method="POST" class="nextactive-form" id="nextactive-form">
            <div class="ins-active-license">
                <ol class="nextul">
                    <li> <?php echo esc_html__('At first, go to your ', 'next3-offload');?> <a href="http://account.themedev.net/" target="_blank"> <?php echo esc_html__('ThemeDev account', 'next3-offload');?> </a></li>
                    <li> <?php echo esc_html__('Login to your account. ', 'next3-offload');?> <a href="http://account.themedev.net/?views=login" target="_blank"> <?php echo esc_html__('Click here', 'next3-offload');?> </a> </li>
                    <li> <?php echo esc_html__('Go to my account >> products >> tab. ', 'next3-offload');?> <a href="http://account.themedev.net/?views=products" target="_blank"> <?php echo esc_html__('Click here', 'next3-offload');?></a> </li>
                    <li> <?php echo esc_html__('Click on >> licenses >> for collect your license key.', 'next3-offload');?> </li>
                    <li> <?php echo esc_html__('Copy license key and paste on the field. Then click on the "Activate license" Button', 'next3-offload');?> </li>
                    <li> <?php echo esc_html__('If you don\'t have any license key, just click on buy button.', 'next3-offload');?> <a href="https://www.themedev.net/next3-offload/" target="_blank"> <?php echo esc_html__(' Buy now', 'next3-offload');?> </a></li>
                </ol>
            </div>
            <div class="ins-active-license">
                <?php if($status == 'active'){?>
                    <div class="nextactive-message next-success"><a href="" class="__revoke_license" data-keys="<?php echo esc_attr($key_data);?>" >  <?php echo esc_html('Click to revoke license. ', 'nextcode');?> <?php if($typeitem == 'check'){ echo esc_html__('This license work only for 10 days.', 'nextcode');}?></a></div>
                <?php }else{?>
                    <div class="license-key">
                    <label for="_license_key">
                        <input type="text" name="key_license" id="key_license" class="license-input" placeholder="<?php echo esc_attr('Please paste your license key', 'next3-offload'); ?>" value="<?php echo esc_attr($key_data);?>">
                    </label>
                    <button type="submit" name="_active_license" class="next_active_license-button"><?php echo esc_html('Activate license', 'next3-offload');?> </button>
                </div>
                <div class="nextactive-message"></div>
                 <?php }?>  
            </div>
        </form>
    </div>  
</div>