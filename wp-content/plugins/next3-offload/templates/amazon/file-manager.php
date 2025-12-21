<section class="next3aws-filemanager"> 
    <div class="neheader-file">
        <h3><?php echo esc_html__('File Manager', 'next3-offload');?> 
            <button type="button" class="nxadd-new" id="next3-add-files"><?php echo esc_html__('Upload File', 'next3-offload');?></button>
            <button type="button" class="nxadd-new" id="next3-add-folder"><?php echo esc_html__('New Folder', 'next3-offload');?></button>
        </h3>
        <ul class="next3list">
            <li><i class="dashicons dashicons-image-rotate refreshicon"></i></li>
            <li><i class="dashicons dashicons-editor-ul next3-listitem" data-id="list"></i></li>
            <li class="nx3-active"><i class="dashicons dashicons-grid-view next3-listitem" data-id="grid"></i></li>
        </ul>
        <div class="media-toolbar-primary search-form"><label for="media-search-input" class="media-search-input-label">Search</label><input type="search" id="media-search-input" class="search"></div>
    </div>
    <div class="n3content-file" store="<?php echo esc_attr($getBucket);?>">
        <span class="dashicons dashicons-arrow-left-alt2 backicon nxicon-open"></span>
        <span class="dashicons dashicons-arrow-right-alt2 nexticon"></span>
        <ul class="append-nx3-media themedev-aws-s3-ul">
        </ul>
    </div>
</div>