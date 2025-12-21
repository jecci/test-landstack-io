<div class="next3aws-addons-bannar-wrapper">
    <?php if( isset($addons['success']) &&  !empty($addons['message']) ){
        foreach($addons['message'] as $v){
        ?>
    <div class="next3aws-addons-bannar-item <?php esc_attr_e($v->type);?>">
        <div class="next3aws-addons-bannar-icon ">
            <?php if( !empty($v->icon) ){?>
            <span class="<?php esc_attr_e($v->icon);?>"></span>
            <?php }?>
            <?php if( !empty($v->image) ){?>
            <img src="<?php esc_attr_e($v->image);?>">
            <?php }?>
        </div>
        <div class="next3aws-addons-bannar-content">
            <h3 class="next3aws-addons-title"><?php echo esc_html__($v->title, 'next3-offload');?></h3>
            <p><?php echo esc_html__($v->desc, 'next3-offload');?></p>
            <div class="next3aws-addons-bannar-footer">
                <a class="next3aws-addons-button" href="<?php echo esc_url($v->url);?>"><?php echo esc_html__('Buy now','next3-offload');?></a>
                <span class="addons-price"><?php echo esc_html__($v->price, 'next3-offload');?></span>
            </div>
        </div>
    </div>
      <?php 
        }
    }?>  
</div>
