<?php 
namespace Next3Offload\Modules;
defined( 'ABSPATH' ) || exit;

class Compatiblility{
    private static $instance;

    private $deleting_attachment = false;

    public function init() { 

        
        add_filter( 'attachment_url_to_postid', [ $this, 'attachment_url_to_postid' ], 20, 2 ); 
        add_filter( 'wp_get_attachment_metadata', [ $this, 'wp_get_attachment_metadata' ], 119, 2 );
        add_filter( 'wp_calculate_image_srcset', [ $this, 'wp_calculate_image_srcset' ], 119, 5 );
        add_filter( 'wp_calculate_image_srcset_meta', [ $this, 'wp_calculate_image_srcset_meta' ], 119, 4 );
    
        //media filter
        add_filter( 'wp_unique_filename', [ $this, 'wp_unique_filename' ], 12, 3 );
        //add_filter( 'wp_update_attachment_metadata', [ $this, 'wp_update_attachment_metadata' ], 991, 2 );
        add_action( 'wp_generate_attachment_metadata', array( $this, 'wp_update_attachment_metadata' ), 119, 2 );
        
        // delete media
        add_filter( 'pre_delete_attachment',[ $this, 'pre_delete_attachment' ], 99 );
        add_filter( 'delete_attachment', [ $this, 'delete_attachment' ], 99 );
		add_action( 'delete_post', [ $this, 'delete_post' ] );

        add_filter( 'update_attached_file', [ $this, 'update_attached_file' ], 119, 2 );
		add_filter( 'update_post_metadata', [ $this, 'update_post_metadata' ], 119, 5 );

        // Attachment screens/modals
        add_action( 'add_meta_boxes', array( $this, 'add_media_metabox' ) );
        add_filter('attachment_fields_to_save', [ $this, 'attachment_save_post'], 10, 2); 

        // AJAX
		add_action( 'wp_ajax_next3_get_attachment_provider_details', array( $this, 'ajax_get_attachment_provider_details' ) );

        // upload/edit file
        add_action('edit_updated', [$this, 'add_attachment_action'], 991, 1);
        

        // Rewriting URLs
        //add_filter('image_downsize', [ $this, 'image_downsize'], 999, 3);
		add_filter( 'get_attached_file', [ $this, 'get_attached_file' ], 119, 2 );
		add_filter( 'wp_get_original_image_path', [ $this, 'get_attached_file' ], 119, 2 );
        add_filter( 'wp_get_attachment_url', [ $this, 'wp_get_attachment_url' ], 119, 2 );
        add_filter( 'wp_get_attachment_image_attributes', [ $this, 'wp_get_attachment_image_attributes' ], 999, 3 );
		add_filter( 'get_image_tag', array( $this, 'get_image_tag' ), 119, 6 );
        add_filter( 'wp_get_attachment_image_src', [ $this, 'wp_get_attachment_image_src' ], 119, 4 );
        add_filter( 'wp_prepare_attachment_for_js', [ $this, 'wp_prepare_attachment_for_js', ], 119, 3 );
        add_filter( 'image_get_intermediate_size', [ $this, 'image_get_intermediate_size' ], 119, 3 );
        add_filter( 'wp_audio_shortcode', [ $this, 'wp_media_shortcode' ], 110, 5 );
		add_filter( 'wp_video_shortcode', [ $this, 'wp_media_shortcode' ], 110, 5 );

        // srcset
        add_filter( 'wp_image_file_matches_image_meta', [ $this, 'image_file_matches_image_meta' ], 10, 4 );


        // WordPress MU Domain Mapping plugin compatibility.
        //add_filter( 'next3_get_orig_siteurl', array( $this, 'get_orig_siteurl' ) );
       
        if(current_user_can('manage_options')){
            // custom column attachment
            add_filter( 'media_row_actions', [ $this, 'post_row_actions_list' ], 10, 2 );
            add_filter( 'manage_media_columns', [ $this, 'set_columns_lists' ]);
            add_action( 'manage_media_custom_column', [ $this, 'render_column_list' ], 10, 2 );
        }

        // acf
        if ( class_exists( 'acf_field_image_crop' ) ) {
			add_filter( 'wp_get_attachment_metadata', array( $this, 'wp_get_attachment_metadata' ), 10, 2 );
		}

    }

    public function attachment_url_to_postid( $post_id, $url ){
        if ( ! is_null( $post_id ) ) {
			return $post_id;
		}
       
        return $post_id;
    }

    public function wp_get_attachment_metadata( $data, $attachment_id ) {

        $status = next3_check_rewrite('rewrite', $attachment_id);
        if( !$status ){
            return $data;
        }

        global $wp_current_filter;

        // cache 
        $to_cache = isset($_GET['next3-cache']) ? [] : $this->get_post_cache( $attachment_id, 'next3_wp_get_attachment_metadata');
        if( isset($to_cache[ $attachment_id ])){
            return ($to_cache[ $attachment_id ]) ?? $data;
        }
        // cache end

		if (
			is_array( $wp_current_filter ) &&
			! empty( $wp_current_filter[0] ) &&
			'the_content' === $wp_current_filter[0]
		) {
			if (isset($data['file']) && ! empty( $data['file'] ) ) {
				$data['file'] = next3_core()->compatiblility_ins->encode_filename_in_path( $data['file'], $attachment_id);
			}

			if ( ! empty( $data['sizes'] ) ) {
                $sizes = $data['sizes'];
                foreach( $sizes as $k=>$v){
                    if( isset($v['file']) && !empty($v['file']) ){
                        $sizes[$k]['file'] = next3_core()->compatiblility_ins->encode_filename_in_path( $v['file'], $attachment_id);
                    }
                }
                $data['sizes'] = $sizes;
			}
		}

        // cache 
        $to_cache[ $attachment_id ] = $data;
        next3_core()->compatiblility_ins->set_post_cache($attachment_id, 'next3_wp_get_attachment_metadata', $to_cache);
        // cache end

		return $data;
	}

    public function wp_calculate_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
        if ( ! is_array( $sources ) ) {
			return $sources;
		}
        $status = next3_check_rewrite('rewrite', $attachment_id);
        if( !$status ){
            return $sources;
        }

         // cache 
         $to_cache = isset($_GET['next3-cache']) ? [] : $this->get_post_cache( $attachment_id, 'next3_wp_calculate_image_srcset');
         if( isset($to_cache[ $attachment_id ])){
             return ($to_cache[ $attachment_id ]) ?? $sources;
         }
         // cache end
        
        foreach ( $sources as $width => $source ) {
			$filename     = wp_basename( $source['url'] );
			$size         = $this->find_image_size_from_width( $image_meta['sizes'], $width, $filename );
            
			$provider_url = next3_core()->action_ins->get_attatchment_url_preview($attachment_id, $size); // modify size
			if ( false === $provider_url || is_wp_error( $provider_url ) ) {
				continue;
			}
			$sources[ $width ]['url'] = $provider_url;
		}

        // cache 
        $to_cache[ $attachment_id ] = $sources;
        next3_core()->compatiblility_ins->set_post_cache($attachment_id, 'next3_wp_calculate_image_srcset', $to_cache);
        // cache end

        return $sources;
    }

    protected function find_image_size_from_width( $sizes, $width, $filename ) {
		foreach ( $sizes as $name => $size ) {
			if ( $width === absint( $size['width'] ) && $size['file'] === $filename ) {
				return $name;
			}
		}
		return 'full';
	}

    public function wp_calculate_image_srcset_meta( $image_meta, $size_array, $image_src, $attachment_id ) {
		if ( empty( $image_meta['file'] ) ) {
			return $image_meta;
		}

        if ( false !== strpos( $image_src, $image_meta['file'] ) ) {
			return $image_meta;
		}

        $status = next3_check_rewrite('rewrite', $attachment_id);
        if( !$status ){
            return $image_meta;
        }

        // cache 
        $to_cache = isset($_GET['next3-cache']) ? [] : $this->get_post_cache( $attachment_id, 'next3_wp_calculate_image_srcset_meta');
        if( isset($to_cache[ $attachment_id ])){
            return ($to_cache[ $attachment_id ]) ?? $image_meta;
        }
        // cache end

        $image_basename = next3_core()->compatiblility_ins->encode_filename_in_path( wp_basename( $image_meta['file'] ), $attachment_id);

        $image_meta['file'] = $image_basename;

		if ( ! empty( $image_meta['sizes'] ) ) {
            
            $sizes = $image_meta['sizes'];
            foreach( $sizes as $k=>$v){
                if( isset($v['file']) && !empty($v['file']) ){
                    $sizes[$k]['file'] = next3_core()->compatiblility_ins->encode_filename_in_path( $v['file'], $attachment_id);
                }
            }
            $image_meta['sizes'] = $sizes;
		}

        // cache 
        $to_cache[ $attachment_id ] = $image_meta;
        next3_core()->compatiblility_ins->set_post_cache($attachment_id, 'next3_wp_calculate_image_srcset_meta', $to_cache);
        // cache end

		return $image_meta;
	}

    public function encode_filename_in_path( $file, $id = 0) {
        if(empty($file)){
			return $file;
		}
        // cache 
        $to_cache = isset($_GET['next3-cache']) ? [] : $this->get_post_cache( $id, 'next3_get_attached_file_name_cache');
        if( isset($to_cache[ $id ])){
            return ($to_cache[ $id ]) ?? $file;
        }
       
        // cache end

        $url = parse_url( $file );

        if ( ! isset( $url['path'] ) ) {
            return $file;
        }

        if ( isset( $url['query'] ) ) {
            $file_name = wp_basename( str_replace( '?' . $url['query'], '', $file ) );
        } else {
            $file_name = wp_basename( $file );
        }

        if ( false !== strpos( $file_name, '%' ) ) {
            return $file;
        }

        if( $id != 0 && true === next3_check_post_meta($id, 'next3_optimizer_is_converted_to_webp') ){
            if(strpos($file, ".webp") === false){
                $file .= '.webp';
            }
        }
        $encoded_file_name = rawurlencode( $file_name );

        // cache 
        $to_cache[ $id ] = str_replace( $file_name, $encoded_file_name, $file );
        next3_core()->compatiblility_ins->set_post_cache($id, 'next3_get_attached_file_name_cache', $to_cache);
        // cache end

        if ( $file_name === $encoded_file_name ) {
            return $file;
        }

        return str_replace( $file_name, $encoded_file_name, $file );
    }

    public function wp_unique_filename( $filename, $ext, $dir ) {
        if( ! next3_upload_status() ){
            return $filename;
        }
		$post_id = filter_input( INPUT_POST, 'post_id', FILTER_VALIDATE_INT );

		return $filename;
	}
    public function wp_update_attachment_metadata( $data, $post_id ) {
       
        if ( is_wp_error( $data ) ) {
			return $data;
		}

        $image_meta = ($data['image_meta']) ?? [];
        if( !empty($image_meta) ){
            $data['image_meta']['copyright'] = '@Next3 Offload Media';
            $data['image_meta']['credit'] = '@ThemeDev';
        }

        if ( ! empty( $data ) && function_exists( 'wp_get_registered_image_subsizes' ) && function_exists( 'wp_get_missing_image_subsizes' ) ) {

			if ( apply_filters( 'next3_wait_for_generate_attachment_metadata', false ) ) {
				return $data;
			}

			if ( empty( $data['sizes'] ) && wp_attachment_is_image( $post_id ) ) {

				$new_sizes     = wp_get_registered_image_subsizes();
				$new_sizes     = apply_filters( 'intermediate_image_sizes_advanced', $new_sizes, $data, $post_id );
				$missing_sizes = wp_get_missing_image_subsizes( $post_id );

				if ( ! empty( $new_sizes ) && ! empty( $missing_sizes ) && array_intersect_key( $missing_sizes, $new_sizes ) ) {
					return $data;
				}
			}
		}
        $settings_options = next3_options();
        $copy_file = ($settings_options['storage']['copy_file']) ?? 'no';
        $remove_local = isset($settings_options['storage']['remove_local']) ? true : false;
    
        $compression_enable = ($settings_options['optimization']['compression']) ?? 'no';
        $webp_enable = ($settings_options['optimization']['webp_enable']) ?? 'no';

        // check meta data
        if( empty($data['sizes']) || !isset($data['sizes']) ){
            $data = next3_wp_get_attachment_metadata( $post_id, true);
        }
        
        $status_action = true;
        // offload media
        if( next3_upload_status() && $copy_file == 'yes'){
            if( next3_get_post_meta($post_id, '_next3_attached_url') === false){
                next3_core()->action_ins->copy_to_cloud('', $post_id, $remove_local);
                $status_action = false;
            }
        }

        if( $status_action ){
            // check compress status 
            if( $compression_enable == 'yes'){
                next3_core()->optimizer_ins->optimize( $post_id, $data);
            }

            // webp files
            if($webp_enable == 'yes'){
                next3_core()->webp_ins->optimize( $post_id, $data);
            }
        }
        return $data;
    }

    public function pre_delete_attachment( $delete ) {
		if ( is_null( $delete ) ) {
			$this->deleting_attachment = true;
		}
		return $delete;
	}

    public function delete_attachment( $id ) {
        if( ! $this->deleting_attachment ){
            return $id;
        }

        if( next3_get_post_meta($id, '_next3_attached_url')){
                
            $filepath = next3_get_post_meta( $id, '_next3_attached_file');
            $provider = next3_get_post_meta( $id, '_next3_provider');
            $bucket = next3_get_post_meta( $id, '_next3_bucket');
            $region = next3_get_post_meta( $id, '_next3_region');

            $obj = next3_core()->provider_ins->load($provider)->access();
            if( !$obj || !$obj->check_configration()){
                return $id;
            }
           
            $message = $obj->getStatus();
            if( $message != 'success'){
                return $id;
            }

            if( true === next3_check_post_meta($id, 'next3_optimizer_is_converted_to_webp') ){
                if(strpos($filepath, ".webp") === false){
                    $filepath .= '.webp';
                }
            }
            
            $settings_options = next3_options();
            $remove_cloud = isset($settings_options['storage']['remove_cloud']) ? true : false;

            $resDelete['status'] = true;
            if( $remove_cloud ){
                $resDelete = $obj->get_deleteObjects( $bucket, $filepath);
                // offload media data
                next3_delete_post_meta( $id, '_next3_attached_file');
                next3_delete_post_meta( $id, '_next3_attached_url');
                next3_delete_post_meta( $id, '_next3_source_type');
                next3_delete_post_meta( $id, '_next3_provider');
                next3_delete_post_meta( $id, '_next3_provider_delivery');
                next3_delete_post_meta( $id, '_next3_bucket');
                next3_delete_post_meta( $id, '_next3_region');
                next3_delete_post_meta( $id, '_next3_clean_status');
                next3_delete_post_meta( $id, '_next3_rename_file');
                next3_delete_post_meta( $id, '_next3_rename_orginal');

                if( $remove_cloud ){
                    $crop = next3_get_post_meta( $id, '_next3_attachment_metadata');
                    if( isset($crop['sizes']) && !empty($crop['sizes']) ){
                        $size = [];
                        foreach($crop['sizes'] as $k=>$v){
                            if( !isset($v['file']) || empty($v['file']) ){
                                continue;
                            }
                            if( !in_array($v['file'], $size) ){
                                $size[] = $v['file'];
                            }
                            $file_name = $v['file'];
                            if( true === next3_check_post_meta($id, 'next3_optimizer_is_converted_to_webp') ){
                                if(strpos($file_name, ".webp") === false){
                                    $file_name .= '.webp';
                                }
                            }
                            $obj->get_deleteObjects( $bucket, $file_name);
                        }
                    }
                }
                next3_delete_post_meta(  $id, '_next3_attachment_metadata');
            }
        }
        // optimize file
        next3_core()->optimizer_ins->delete_media_action( $id);
        
        // webp files delete
        next3_core()->webp_ins->delete_media_action( $id);

        // compress
        next3_delete_post_meta( $id, 'next3_optimizer_orginal_file');

        return $id;
    }

    public function delete_post() {
		$this->deleting_attachment = false;
	}

    public function update_attached_file( $file, $attachment_id ) {
		if( ! next3_upload_status() ){
            return $file;
        }
        
		return apply_filters( 'next3_update_attached_file', $file, $attachment_id);
	}

    public function update_post_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		return $check;
	}

    public function get_attached_file( $file, $attachment_id ) {
        if ( $this->deleting_attachment ) {
			return $file;
		}
        // cache 
        $to_cache = isset($_GET['next3-cache']) ? [] : $this->get_post_cache( $attachment_id, 'next3_get_attached_file_name_cache');
        if( isset($to_cache[ $attachment_id ])){
            return ($to_cache[ $attachment_id ]) ?? $file;
        }
        // cache end

        $status = next3_check_rewrite('rewrite', $attachment_id);
        if( !$status ){
            return $file;
        }
        if( true === next3_check_post_meta($attachment_id, 'next3_optimizer_is_converted_to_webp') ){
            if(strpos($file, ".webp") === false){
                $file .= '.webp';
            }
        }
        // cache 
        $to_cache[ $attachment_id ] = $file;
        next3_core()->compatiblility_ins->set_post_cache($attachment_id, 'next3_get_attached_file_name_cache', $to_cache);
        // cache end

        return apply_filters( 'next3_get_attached_file', $file, $attachment_id);
    }

    public function wp_media_shortcode( $html, $atts, $media, $post_id, $library ) {
		return preg_replace( '/&#038;_=[0-9]+/', '', $html );
	}

    public function wp_get_attachment_url( $url, $post_id ) {
        $status = next3_check_rewrite('rewrite', $post_id);
        if( !$status ){
            return $url;
        }
        
        $file_new = next3_get_post_meta( $post_id, '_next3_attached_file');
        if( $file_new && !empty($file_new) ){
            $url_get = next3_get_post_meta( $post_id, '_next3_attached_url');
            if( !empty($url_get) ){
                $url = next3_core()->action_ins->get_attatchment_url_preview($post_id, 'full');
			}
        } else {
            if( true === next3_check_post_meta($post_id, 'next3_optimizer_is_converted_to_webp') ){
                if(strpos($url, ".webp") === false){
                    //$url .= '.webp';
                    $url = next3_core()->action_ins->get_attatchment_url_preview($post_id, 'full');
                }
            }
        }
        return apply_filters( 'next3_wp_get_attachment_url', $url, $post_id);
    }

    public function wp_get_attachment_image_attributes( $attr, $attachment, $size ){

        $post_id = ($attachment->ID) ?? 0;
        $status = next3_check_rewrite('rewrite', $post_id);
        if( !$status ){
            return $attr;
        }

        // cache 
        $to_cache = isset($_GET['next3-cache']) ? [] : $this->get_post_cache( $post_id, 'next3_wp_get_attachment_attributes');
        if( isset($to_cache[ $post_id ])){
            return ($to_cache[ $post_id ]) ?? $attr;
        }
        // cache end

        if ( is_array( $size ) ) {
			$size = $this->convert_dimensions_to_size_name( $post_id, $size );
		}
        
        $file_old = next3_get_post_meta( $post_id, '_awss3_attached_file');
        $file_new = next3_get_post_meta( $post_id, '_next3_attached_file');

        if( $file_old && !empty($file_old) ){
            if ( ! empty( $size ) && ! empty( $attr['src'] ) ) {
                $attr['src'] = next3_core()->action_ins->get_attatchment_url_preview($post_id, 'full');  // modify size
            }
        } 
        if( $file_new && !empty($file_new) ){
            if ( ! empty( $size ) && ! empty( $attr['src'] ) ) {
                $attr['src'] = next3_core()->action_ins->get_attatchment_url_preview($post_id, 'full'); // modify size
            }
        } else {
            if( true === next3_check_post_meta($post_id, 'next3_optimizer_is_converted_to_webp')){
                $attr['src'] = next3_core()->action_ins->get_attatchment_url_preview($post_id, 'full'); // modify size
            }
        }

        // cache 
        $to_cache[ $post_id ] = $attr;
        next3_core()->compatiblility_ins->set_post_cache($post_id, 'next3_wp_get_attachment_attributes', $to_cache);
        // cache end

        return apply_filters( 'next3_wp_get_attachment_image_attributes', $attr, $attachment, $size);
    }

    private function convert_dimensions_to_size_name( $attachment_id, $dimensions ) {
		$w                     = ( isset( $dimensions[0] ) && $dimensions[0] > 0 ) ? $dimensions[0] : 1;
		$h                     = ( isset( $dimensions[1] ) && $dimensions[1] > 0 ) ? $dimensions[1] : 1;
		$original_aspect_ratio = $w / $h;
		$meta                  = next3_wp_get_attachment_metadata( $attachment_id, true);

		if ( ! isset( $meta['sizes'] ) || empty( $meta['sizes'] ) ) {
			return null;
		}

		$sizes = $meta['sizes'];
		uasort( $sizes, function ( $a, $b ) {
			return ( $a['width'] * $a['height'] ) - ( $b['width'] * $b['height'] );
		} );

		$matches = [];
		foreach ( $sizes as $size => $value ) {
			if ( $w > $value['width'] || $h > $value['height'] ) {
				continue;
			}

			$aspect_ratio = $value['width'] / $value['height'];

			if ( $aspect_ratio === $original_aspect_ratio ) {
				return $size;
			}

			$matches[] = $size;
		}

		if ( ! empty( $matches ) ) {
			return $matches[0];
		}
		return null;
	}

    public function image_downsize( $status, $id, $size){
		return true;
	}

    public function get_image_tag( $html, $id, $alt, $title, $align, $size ){
        
        $status = next3_check_rewrite('rewrite', $id);
        if( !$status ){
            return $html;
        }

        if ( ! is_string( $html ) ) {
			return $html;
		}

		preg_match( '@\ssrc=[\'\"]([^\'\"]*)[\'\"]@', $html, $matches );

		if ( ! isset( $matches[1] ) ) {
			return $html;
		}
        $img_src     = $matches[1];
		$new_img_src = next3_core()->action_ins->get_attatchment_url_preview($id, 'full'); // modify $size
		
        return str_replace( $img_src, $new_img_src, $html );
    }

    public function wp_get_attachment_image_src( $image, $attachment_id, $size, $icon ){

        $status = next3_check_rewrite('rewrite', $attachment_id);
        if( !$status ){
            return $image;
        }

        // cache 
        $to_cache = isset($_GET['next3-cache']) ? [] : $this->get_post_cache( $attachment_id, 'next3_wp_get_attachment_image_src');
        if( isset($to_cache[ $attachment_id ])){
            return ($to_cache[ $attachment_id ]) ?? $image;
        }
        // cache end
        
        if ( isset( $image[0] ) ) {
            if ( is_array( $size ) ) {
                $size = $this->convert_dimensions_to_size_name( $attachment_id, $size );
            }
            $image[0] = next3_core()->action_ins->get_attatchment_url_preview($attachment_id, $size);  // modify $size
        }

        // cache 
        $to_cache[ $attachment_id ] = $image;
        next3_core()->compatiblility_ins->set_post_cache($attachment_id, 'next3_wp_get_attachment_image_src', $to_cache);
        // cache end

        return apply_filters( 'next3_wp_get_attachment_image_src', $image, $attachment_id, $size, $icon);
    }

    public function wp_prepare_attachment_for_js( $response, $attachment, $meta ){

        $status = next3_check_rewrite('rewrite',  $attachment->ID);
        
        // cache 
        $to_cache = isset($_GET['next3-cache']) ? [] : $this->get_post_cache( $attachment->ID, 'next3_wp_prepare_attachment_for_js');
        if( isset($to_cache[ $attachment->ID ])){
            return ($to_cache[ $attachment->ID ]) ?? $response;
        }
        // cache end

        if ( isset( $response['sizes'] ) && is_array( $response['sizes'] ) ) {
			foreach ( $response['sizes'] as $size => $value ) {
                if( !$status ){
                    continue;
                }
				$response['sizes'][ $size ]['url'] = next3_core()->action_ins->get_attatchment_url_preview($attachment->ID, 'full'); // modify $size
			}
		}

        // cache 
        $to_cache[ $attachment->ID ] = $response;
        next3_core()->compatiblility_ins->set_post_cache($attachment->ID, 'next3_wp_prepare_attachment_for_js', $to_cache);
        // cache end

        return apply_filters( 'next3_wp_prepare_attachment_for_js', $response, $attachment, $meta);
    }

    public function image_get_intermediate_size( $data, $post_id, $size ){
        $status = next3_check_rewrite('rewrite', $post_id);
        if( !$status ){
            return $data;
        }
        if ( isset( $data['url'] ) ) {
            if ( is_array( $size ) ) {
                $size = $this->convert_dimensions_to_size_name( $post_id, $size );
            }
			$data['url'] = next3_core()->action_ins->get_attatchment_url_preview($post_id, 'full'); // modify $size
		}
        return apply_filters( 'next3_image_get_intermediate_size', $data, $post_id, $size);
    }

    public function image_file_matches_image_meta( $match, $image_location, $image_meta, $source_id ) {
		$status = next3_check_rewrite('rewrite');
        if( !$status ){
            return $match;
        }
        return $match;
	}

    /*AWS upload file when upload wp media*/
    public function add_attachment_action( $post_id ){
        
        $settings_options = next3_options();
        $copy_file = ($settings_options['storage']['copy_file']) ?? 'no';
        
        if( empty($post_id) ){
            return;
        }
        $remove_local = isset($settings_options['storage']['remove_local']) ? true : false;
        $compression_enable = ($settings_options['optimization']['compression']) ?? 'no';
        $webp_enable = ($settings_options['optimization']['webp_enable']) ?? 'no';

        // check meta data
        $data = next3_wp_get_attachment_metadata( $post_id, true);
        
       
        $status_action = true;
        // offload media
        if( next3_upload_status() && $copy_file == 'yes'){
            if( next3_get_post_meta($post_id, '_next3_attached_url') === false){
                $res = next3_core()->action_ins->copy_to_cloud('', $post_id, $remove_local);
                if( isset(  $res['success']) ){
                    $url = ($res['message']) ?? '';
                    return $url;
                }
                $status_action = false;
            }
        }
        
        if( $status_action ){
            // check compress status 
            if( $compression_enable == 'yes'){
                next3_core()->optimizer_ins->optimize( $post_id, $data);
            }

            // webp files
            if($webp_enable == 'yes'){
                next3_core()->webp_ins->optimize( $post_id, $data);
            }
        }

        return;
    }

    /*AWS media preview extra info*/
    public function ajax_get_attachment_provider_details() {
		if ( ! isset( $_POST['id'] ) ) {
			return;
		}

		check_ajax_referer( 'get-attachment-next3-details', '_nonce' );

        $id  = intval( $_POST['id'] );
        if( next3_get_post_meta($id, '_next3_attached_url') === false){
            return;
        }
		$bucket = next3_get_post_meta( $id, '_next3_bucket');
		$region = next3_get_post_meta( $id, '_next3_region');
		$key = next3_get_post_meta( $id, '_next3_attached_file');
		$url = next3_get_post_meta( $id, '_next3_attached_url');
		$provider = next3_get_post_meta( $id, '_next3_provider');

        $config_data = next3_providers();
        $name_provider = ($config_data[$provider]['label']) ?? '';

        $get_url = next3_core()->action_ins->get_attatchment_url_preview($id, 'full');
        $get_url = empty($get_url) ? $url : $get_url;
        
		$data = array(
			'links'           => [],
			'provider_object' => [
                'provider_name' => $name_provider,
                'region' => next3_aws_region( $region, $provider),
                'bucket' => $bucket,
                'key' => $key,
                'url' => $get_url,
            ],
			'acl_toggle'      => true,
		);

		wp_send_json_success( $data );
	}

    // add metabox
    public function add_media_metabox(){
        global $post;
        $id = ($post->ID) ?? 0;

        $credentials = next3_credentials();
        $services = ($credentials['settings']['services']) ?? ['offload', 'optimization', 'database'];
        if( !in_array('offload', $services)){
            return;
        }
        if( next3_get_post_meta($id, '_next3_attached_url') === false){
            add_meta_box(
                'next3-mediabox',
                __( 'Offload - Next3', 'next3-offload' ),
                [ $this, 'upload_media_metabox_action' ],
                'attachment',
                'side',
                'core'
            );
            return;
        }

        add_meta_box(
			'next3-mediabox',
			__( 'Offload - Next3', 'next3-offload' ),
			[ $this, 'add_media_metabox_action' ],
			'attachment',
			'side',
			'core'
		);
    }


    public function add_media_metabox_action(){
        global $post;
        $id = ($post->ID) ?? 0;
        if( next3_get_post_meta($id, '_next3_attached_url') === false){
            return;
        }

        $file = next3_get_attached_file( $id, true );
        $bucket = next3_get_post_meta( $id, '_next3_bucket');
		$region = next3_get_post_meta( $id, '_next3_region');
		$key = next3_get_post_meta( $id, '_next3_attached_file');
		$url = next3_get_post_meta( $id, '_next3_attached_url');
		$type = next3_get_post_meta( $id, '_next3_source_type');
		$provider = next3_get_post_meta( $id, '_next3_provider');

        if( true === next3_check_post_meta($id, 'next3_optimizer_is_converted_to_webp') ){
            if(strpos($key, ".webp") === false){
                $key .= '.webp';
            }
        }

        $config_data = next3_providers();
        $name_provider = ($config_data[$provider]['label']) ?? '';

        $get_url = next3_core()->action_ins->get_attatchment_url_preview($id, 'full');
        $get_url = empty($get_url) ? $url : $get_url;
        ?>
        <p class="next3-offload-section" style="padding:0px 10px;">
			<label class="name_provider">
                <strong><?php echo esc_html__('Storage Provider:', 'next3-offload');?></strong>
                <span><?php echo esc_html($name_provider);?></span>
            </label>
        </p>
        <?php if( !empty($region) ){?>
        <p class="next3-offload-section" style="padding:0px 10px;">
			<label class="name_provider">
                <strong><?php echo esc_html__('Region:', 'next3-offload');?></strong>
                <span><?php echo esc_html( next3_aws_region( $region, $provider) );?></span>
            </label>
        </p>
        <?php }?>

        <?php if( !empty($bucket) ){?>
        <p class="next3-offload-section" style="padding:0px 10px;">
			<label class="name_provider">
                <strong><?php echo esc_html__('Bucket:', 'next3-offload');?></strong>
                <span><?php echo esc_html( $bucket );?></span>
            </label>
        </p>
        <?php }?>

        <?php if( !empty($key) ){?>
        <p class="next3-offload-section" style="padding:0px 10px;">
			<label class="name_provider">
                <strong><?php echo esc_html__('Path:', 'next3-offload');?></strong>
                <span><?php echo esc_html( $key );?></span>
            </label>
        </p>
        <?php }?>

        <?php if( !empty($type) ){?>
        <p class="next3-offload-section" style="padding:0px 10px;">
			<label class="name_provider">
                <strong><?php echo esc_html__('Uploaded via:', 'next3-offload');?></strong>
                <span><?php echo esc_html( $type == 'wp_media' ? 'WP Media' : 'Next3 File Manager' );?></span>
            </label>
        </p>
        <?php }?>

        <p class="next3-offload-section" style="padding:0px 10px;">
			<label class="name_provider">
                <strong><?php echo esc_html__('Access:', 'next3-offload');?></strong>
                <span><?php echo esc_html( 'Public' );?></span>
            </label>
        </p>
       

        <?php
    }

    public function upload_media_metabox_action(){
        global $post;
        $id = ($post->ID) ?? 0;
        if( next3_get_post_meta($id, '_next3_attached_url') === true){
            return;
        }
        $settings_options = next3_options();

        $file = next3_get_attached_file( $id, true );
        if( true === next3_check_post_meta($id, 'next3_optimizer_is_converted_to_webp') ){
            if(strpos($file, ".webp") === false){
                $file .= '.webp';
            }
        }
        $exp_file = explode('/', $file);

        $credentials = next3_credentials();

        $config_data = next3_providers();
    
        $provider = ($credentials['settings']['provider']) ?? '';
        $name_provider = ($config_data[$provider]['label']) ?? '';
    
        $prodiver_data = ($credentials['settings'][$provider]) ?? [];
        $default_bucket = ($prodiver_data['default_bucket']) ?? '';
        $default_region = ($prodiver_data['default_region']) ?? 'us-east-1';
    
        $get_local_url_preview = next3_core()->action_ins->get_url_preview( false, end($exp_file) );

        ?>
        <p class="next3-offload-section" style="padding:0px 10px;">
			<label class="name_provider">
                <strong><?php echo esc_html__('Storage Provider:', 'next3-offload');?></strong>
                <span><?php echo esc_html($name_provider);?></span>
                <?php if( !NEXT3_SELF_MODE ){?><a style="text-decoration: none;" href="<?php echo next3_admin_url( 'admin.php?page=next3aws&step=provider' );?>" class="next3offload-submit"><i class="dashicons dashicons-admin-links" style="font-size:16px;"></i></a><?php }?>
            </label>
        </p>
        <?php if( !empty($default_region) ){?>
        <p class="next3-offload-section" style="padding:0px 10px;">
			<label class="name_provider">
                <strong><?php echo esc_html__('Region:', 'next3-offload');?></strong>
                <span><?php echo esc_html( next3_aws_region( $default_region, $provider) );?></span>
            </label>
        </p>
        <?php }?>

        <?php if( !empty($default_bucket) ){?>
        <p class="next3-offload-section" style="padding:0px 10px;">
			<label class="name_provider">
                <strong><?php echo esc_html__('Bucket:', 'next3-offload');?></strong>
                <span><?php echo esc_html( $default_bucket );?></span>
                <?php if( !NEXT3_SELF_MODE ){?><a style="text-decoration: none;" href="<?php echo next3_admin_url( 'admin.php?page=next3aws&step=config' );?>" class="next3offload-submit"><i class="dashicons dashicons-admin-links" style="font-size:16px;"></i></a><?php } ?>
            </label>
        </p>
        <?php }?>

        <p class="next3-offload-section" style="padding:0px 10px; word-break: break-all;">
			<label class="name_provider">
                <strong><?php echo esc_html__('Preview:', 'next3-offload');?></strong>
                <span><?php echo esc_url( $get_local_url_preview );?></span>
            </label>
        </p>
        <?php 
        $copyfiles = 'yes';
        if( !empty($settings_options) ){
            $copyfiles = ($settings_options['storage']['copy_file']) ?? 'no';
        }
        ?>
        <div class="next3-offload-section" style="padding:5px 10px; word-break: break-all; ">
            <p style="margin: 0px;"><strong><?php echo esc_html__('Copy files to Cloud (bucket):', 'next3-offload');?></strong></p>
            <label for="next3-copy-files">
                 <input type="checkbox" <?php echo esc_attr( ($copyfiles == 'yes') ? 'checked' : '');?> name="next3upload[copy_file]" id="next3-copy-files" class="" value="yes">
                 
                 <?php echo esc_html__('Click to update, copy it to the cloud (bucket).', 'next3-offload');?>                     
            </label>
        </div>

        <?php 
        $remove_local = 'no';
        if( !empty($settings_options) ){
            $remove_local = ($settings_options['storage']['remove_local']) ?? 'no';
        }
        ?>
        <div class="next3-offload-section" style="padding:5px 10px; word-break: break-all; ">
            <p style="margin: 0px;"><strong><?php echo esc_html__('Remove Files From Server:', 'next3-offload');?></strong></p>
            <label for="next3-remove_local">
                 <input type="checkbox" <?php echo esc_attr( ($remove_local == 'yes') ? 'checked' : '');?> name="next3upload[remove_local]" id="next3-remove_local" class="" value="yes">
                 <?php echo esc_html__('Click to update, automtic delete files from local server.', 'next3-offload');?>                     
            </label>
        </div>
       
        <?php
    }

    public function attachment_save_post( $post, $attachment ){
        $post_id = ($post['ID']) ?? 0;
        
        if( empty($post_id) ){
            return;
        }

        $settings_options = next3_options();
        $copy_file = ($settings_options['storage']['copy_file']) ?? 'no';
        $remove_local = isset($settings_options['storage']['remove_local']) ? true : false;
        $compression_enable = ($settings_options['optimization']['compression']) ?? 'no';
        $webp_enable = ($settings_options['optimization']['webp_enable']) ?? 'no';

        // check meta data
        $data = next3_wp_get_attachment_metadata( $post_id, true);
        
        $status_action = true;
        // offload media
        if( next3_upload_status() ){
            if( next3_get_post_meta($post_id, '_next3_attached_url') === false){

                $postdata = ($_POST['next3upload']) ?? [];
                $copy_file = ($postdata['copy_file']) ?? 'no';
                $remove_local = isset($postdata['remove_local']) ? true : false;

                if( $copy_file == 'yes'){
                    $res = next3_core()->action_ins->copy_to_cloud('', $post_id, $remove_local);
                    if( isset(  $res['success']) ){
                        return ($res['message']) ?? '';
                    }
                    $status_action = false;
                }
            }
        }

        if( $status_action ){
            // check compress status 
            if( $compression_enable == 'yes'){
                next3_core()->optimizer_ins->optimize( $post_id, $data);
            }

            // webp files
            if($webp_enable == 'yes'){
                next3_core()->webp_ins->optimize( $post_id, $data);
            }
        }
        
        return $post;
    }

    public static function can_clone() {
        return current_user_can( 'edit_posts' );
    }

    public function post_row_actions_list($actions, \WP_Post $post){

        $status_data = next3_service_status();

        $offload_status = ($status_data['offload']) ?? false;
        $status_optimization = ($status_data['optimization']) ?? false;
        $develeper_status = ($status_data['develeper']) ?? false;

        if( $offload_status ){
            if ( self::can_clone() && next3_get_post_meta($post->ID, '_next3_attached_url') === false ) {
                $actions['next3-copy'] = sprintf( '<button type="button" class="button-link next3-attachment-copy media-library" data-type="copy" data-id="%1$s">%2$s</button>', $post->ID, esc_html__( 'Offload file to Cloud', 'next3-offload' ) );
            } else{
                $actions['next3-copy'] = sprintf( '<button type="button" class="button-link next3-attachment-copy media-library" data-type="move" data-id="%1$s">%2$s</button>', $post->ID, esc_html__( 'Restore file to WP Media', 'next3-offload' ) );
            }
        } 
        
        
        if( $status_optimization && $develeper_status && next3_get_post_meta($post->ID, '_next3_attached_url') === false ){

            if ( self::can_clone() && next3_get_post_meta($post->ID, 'next3_optimizer_is_optimized') === false ) {
                $actions['next3-compress'] = sprintf( '<button type="button" class="button-link next3-attachment-copy media-library" data-type="compress" data-id="%1$s">%2$s</button>', $post->ID, esc_html__( 'Compress', 'next3-offload' ) );
            } else {
                $actions['next3-compress'] = sprintf( '<button type="button" class="button-link next3-attachment-copy media-library" data-type="compress" data-id="%1$s">%2$s</button>', $post->ID, esc_html__( '!Compress', 'next3-offload' ) );
            }

            if ( self::can_clone() && next3_get_post_meta($post->ID, 'next3_optimizer_is_converted_to_webp') === false ) {
                $actions['next3-webp'] = sprintf( '<button type="button" class="button-link next3-attachment-copy media-library" data-type="webp" data-id="%1$s">%2$s</button>', $post->ID, esc_html__( 'WebP', 'next3-offload' ) );
            } else {
                $actions['next3-webp'] = sprintf( '<button type="button" class="button-link next3-attachment-copy media-library" data-type="webp" data-id="%1$s">%2$s</button>', $post->ID, esc_html__( '!WebP', 'next3-offload' ) );
            }
            
        }
		
		return $actions;
	}

    public function set_columns_lists( $columns){
		
		unset( $columns['date'] );
		unset( $columns['author'] );
		unset( $columns['comments'] );
		unset( $columns['parent'] );
		
		$columns['provider'] = esc_html__( 'Next3 Offload', 'next3-offload' );
		$columns['parent'] = esc_html__( 'Uploaded to', 'next3-offload' );
		$columns['author'] = esc_html__( 'Author', 'next3-offload' );
		$columns['date'] = esc_html__( 'Date', 'next3-offload' );
  
		return $columns;
	}

    public function render_column_list($column, $post_id ){

		$bucket = next3_get_post_meta( $post_id, '_next3_bucket');
		$region = next3_get_post_meta( $post_id, '_next3_region');
		$key = next3_get_post_meta( $post_id, '_next3_attached_file');
		$url = next3_get_post_meta( $post_id, '_next3_attached_url');
		$type = next3_get_post_meta( $post_id, '_next3_source_type');
		$provider = next3_get_post_meta( $post_id, '_next3_provider');
		
        $optimize = 'No';
        if( true === next3_check_post_meta($post_id, 'next3_optimizer_is_optimized') ){
            $optimize = 'Yes';
        }

        $webp = 'No';
        if( true === next3_check_post_meta($post_id, 'next3_optimizer_is_converted_to_webp') ){
            $webp = 'Yes';
        }
	  
        $config_data = next3_providers();
        $name_provider = ($config_data[$provider]['label']) ?? '';

		switch ( $column ) {
		  
            case 'provider':
                if( next3_get_post_meta($post_id, '_next3_attached_file')) {
                    if( !empty($name_provider ) ){
                        echo next3_print('<strong>Provider: </strong>');
                        echo esc_html( $name_provider ) . ' <br/> ';
                    }
                    if( !empty($bucket ) ){
                        echo next3_print('<strong>Bucket: </strong>');
                        echo esc_html( $bucket ) . ' <br> ';
                    }
                    if( !empty($region ) ){
                        echo next3_print('<strong>Region: </strong>');
                        echo esc_html( next3_aws_region( $region, $provider) ) . ' <br> ' ;
                    }
                    echo next3_print('<strong>Compress: </strong>');
                    echo esc_html( $optimize ) ;
                    echo next3_print(', <strong>WebP: </strong>');
                    echo esc_html( $webp ) ;
                } else {
                    echo next3_print( '<strong>Offload: </strong> No' ) . ' <br> ';
                    echo next3_print('<strong>Compress: </strong>');
                    echo esc_html( $optimize ) ;
                    echo next3_print(', <strong>WebP: </strong>');
                    echo esc_html( $webp ) ;
                }
            break;
                
		}
	}

    public function get_orig_siteurl( $siteurl ) {
		if ( defined( 'DOMAIN_MAPPING' ) && function_exists( 'get_original_url' ) ) {
			$siteurl = get_original_url( 'siteurl' );
		}

		return $siteurl;
	}


    public function set_post_cache( $post, $key, $data = []) {
		$post_id = next3_core()->compatiblility_ins::get_post_id( $post );
		if ( ! $post_id ) {
			return;
		}
		if ( wp_using_ext_object_cache() ) {
			$expires = apply_filters( $key . '_expires', DAY_IN_SECONDS, $post_id, $data );
			wp_cache_set( $post_id, $data, $key, $expires );
		} else {
			next3_update_post_meta( $post_id, $key, $data );
		}
	}

    public function get_post_cache( $post = null, $key = 'next3_cache_data' ) {
		$post_id = next3_core()->compatiblility_ins::get_post_id( $post );
		if ( ! $post_id ) {
			return array();
		}
		if ( wp_using_ext_object_cache() ) {
			$cache = wp_cache_get( $post_id, $key );
		} else {
			$cache = next3_get_post_meta( $post_id, $key);
		}

		$settings_options = next3_options();
        $disable_cache = ($settings_options['delivery']['disable_cache']) ?? 'no';

		if ( empty( $cache ) || $disable_cache == 'yes') {
			$cache = array();
		}

		return $cache;
	}

    public static function get_post_id( $post = null ) {
        if( !$post ){
            global $post;
        } 
        
        if( is_object($post) || is_array($post)){
            return (int) get_post_field( 'ID', $post );
        }

        return (int) $post;
    }
    
    public static function instance(){
		if (!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
	}

}