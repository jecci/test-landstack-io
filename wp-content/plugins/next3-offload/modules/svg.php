<?php 
namespace Next3Offload\Modules;
defined( 'ABSPATH' ) || exit;

class Svg{
    private static $instance;

    public function init() { 
        $settings_options = next3_options();
        $enable_svg = ($settings_options['storage']['enable_svg']) ?? 'no';
        if( $enable_svg == 'yes'){
            
            if(current_user_can('manage_options') && is_admin()){
                add_filter( 'upload_mimes', array( $this, 'allow_svg' ) );
                add_filter( 'wp_handle_upload_prefilter', array( $this, 'check_for_svg' ) );
                add_filter( 'wp_check_filetype_and_ext', array( $this, 'fix_mime_type_svg' ), 99, 4 );
                add_filter( 'wp_prepare_attachment_for_js', array( $this, 'fix_admin_preview' ), 10, 3 );
                add_filter( 'wp_get_attachment_image_src', array( $this, 'one_pixel_fix' ), 10, 4 );
                add_filter( 'admin_post_thumbnail_html', array( $this, 'featured_image_fix' ), 10, 3 );
    
                add_action( 'admin_head', array( $this, 'load_custom_admin_style' ) );
    
                add_action( 'get_image_tag', array( $this, 'get_image_tag_override' ), 10, 6 );
                add_filter( 'wp_generate_attachment_metadata', array( $this, 'skip_svg_regeneration' ), 10, 2 );
                add_filter( 'wp_get_attachment_metadata', array( $this, 'metadata_error_fix' ), 10, 2 );
                add_filter( 'wp_calculate_image_srcset_meta', array( $this, 'disable_srcset' ), 10, 4 );
            }
        
        }
    }

    public function current_user_can_upload_svg() {
        $upload_roles = get_option( 'safe_svg_upload_roles', [] );

        // Fallback to upload_files check for backwards compatibility.
        if ( empty( $upload_roles ) ) {
            return current_user_can( 'upload_files' );
        }

        return current_user_can( 'safe_svg_upload_svg' );
    }

    public function allow_svg( $mimes ) {
        if ( $this->current_user_can_upload_svg() ) {
            $mimes['svg']  = 'image/svg+xml';
            $mimes['svgz'] = 'image/svg+xml';
        }

        return $mimes;
    }

    public function check_for_svg( $file ) {

        if ( ! isset( $file['tmp_name'] ) ) {
            return $file;
        }

        $file_name   = isset( $file['name'] ) ? $file['name'] : '';
        $wp_filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file_name );
        $type        = ! empty( $wp_filetype['type'] ) ? $wp_filetype['type'] : '';

        if ( 'image/svg+xml' === $type ) {
            if ( ! $this->current_user_can_upload_svg() ) {
                $file['error'] = __('Sorry, you are not allowed to upload SVG files.', 'next3-offload');
                return $file;
            }
        }

        return $file;
    }

    public function fix_mime_type_svg( $data, $file, $filename, $mimes){
        
        $ext = isset( $data['ext'] ) ? $data['ext'] : '';
        if ( strlen( $ext ) < 1 ) {
            $exploded = explode( '.', $filename );
            $ext      = strtolower( end( $exploded ) );
        }
        if ( 'svg' === $ext ) {
            $data['type'] = 'image/svg+xml';
            $data['ext']  = 'svg';
        } elseif ( 'svgz' === $ext ) {
            $data['type'] = 'image/svg+xml';
            $data['ext']  = 'svgz';
        }

        return $data;
    }

    public function fix_admin_preview( $response, $attachment, $meta ) {

        if ( 'image/svg+xml' === $response['mime'] ) {
            $dimensions = $this->svg_dimensions( get_attached_file( $attachment->ID ) );

            if ( $dimensions ) {
                $response = array_merge( $response, $dimensions );
            }

            $possible_sizes = apply_filters(
                'image_size_names_choose',
                array(
                    'full'      => __( 'Full Size' ),
                    'thumbnail' => __( 'Thumbnail' ),
                    'medium'    => __( 'Medium' ),
                    'large'     => __( 'Large' ),
                )
            );

            $sizes = array();

            foreach ( $possible_sizes as $size => $label ) {
                $default_height = 2000;
                $default_width  = 2000;

                if ( 'full' === $size && $dimensions ) {
                    $default_height = $dimensions['height'];
                    $default_width  = $dimensions['width'];
                }

                $sizes[ $size ] = array(
                    'height'      => get_option( "{$size}_size_w", $default_height ),
                    'width'       => get_option( "{$size}_size_h", $default_width ),
                    'url'         => $response['url'],
                    'orientation' => 'portrait',
                );
            }

            $response['sizes'] = $sizes;
            $response['icon']  = $response['url'];
        }

        return $response;
    }
    protected function svg_dimensions( $svg ) {
        if ( ! function_exists( 'simplexml_load_file' ) ) {
            return false;
        }

        $svg    = @simplexml_load_file( $svg ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        $width  = 0;
        $height = 0;
        if ( $svg ) {
            $attributes = $svg->attributes();

            if ( isset( $attributes->viewBox ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
                $sizes = explode( ' ', $attributes->viewBox ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
                if ( isset( $sizes[2], $sizes[3] ) ) {
                    $viewbox_width  = floatval( $sizes[2] );
                    $viewbox_height = floatval( $sizes[3] );
                }
            }

            if ( isset( $attributes->width, $attributes->height ) && is_numeric( (float) $attributes->width ) && is_numeric( (float) $attributes->height ) && ! $this->str_ends_with( (string) $attributes->width, '%' ) && ! $this->str_ends_with( (string) $attributes->height, '%' ) ) {
                $attr_width  = floatval( $attributes->width );
                $attr_height = floatval( $attributes->height );
            }

            $use_width_height = (bool) apply_filters( 'safe_svg_use_width_height_attributes', false, $svg );

            if ( $use_width_height ) {
                if ( isset( $attr_width, $attr_height ) ) {
                    $width  = $attr_width;
                    $height = $attr_height;
                } elseif ( isset( $viewbox_width, $viewbox_height ) ) {
                    $width  = $viewbox_width;
                    $height = $viewbox_height;
                }
            } else {
                if ( isset( $viewbox_width, $viewbox_height ) ) {
                    $width  = $viewbox_width;
                    $height = $viewbox_height;
                } elseif ( isset( $attr_width, $attr_height ) ) {
                    $width  = $attr_width;
                    $height = $attr_height;
                }
            }

            if ( ! $width && ! $height ) {
                return false;
            }
        }

        return array(
            'width'       => $width,
            'height'      => $height,
            'orientation' => ( $width > $height ) ? 'landscape' : 'portrait',
        );
    }

    protected function str_ends_with( $haystack, $needle ) {
        if ( function_exists( 'str_ends_with' ) ) {
            return str_ends_with( $haystack, $needle );
        }

        if ( '' === $haystack && '' !== $needle ) {
            return false;
        }

        $len = strlen( $needle );
        return 0 === substr_compare( $haystack, $needle, -$len, $len );
    }

    public function one_pixel_fix( $image, $attachment_id, $size, $icon ) {
        if ( get_post_mime_type( $attachment_id ) === 'image/svg+xml' ) {
            $dimensions = $this->svg_dimensions( get_attached_file( $attachment_id ) );

            if ( $dimensions ) {
                $image[1] = $dimensions['width'];
                $image[2] = $dimensions['height'];
            } else {
                $image[1] = 100;
                $image[2] = 100;
            }
        }

        return $image;
    }

    public function featured_image_fix( $content, $post_id, $thumbnail_id ) {
        $mime = get_post_mime_type( $thumbnail_id );

        if ( 'image/svg+xml' === $mime ) {
            $content = sprintf( '<span class="svg">%s</span>', $content );
        }

        return $content;
    }

    public function load_custom_admin_style() {
        echo next3_print( '<style type="text/css">
        #postimagediv .inside .svg img {
            width: 100%;
        }
        </style>');
    }

    public function get_image_tag_override( $html, $id, $alt, $title, $align, $size ) {
        $mime = get_post_mime_type( $id );

        if ( 'image/svg+xml' === $mime ) {
            if ( is_array( $size ) ) {
                $width  = $size[0];
                $height = $size[1];
            // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
            } elseif ( 'full' === $size && $dimensions = $this->svg_dimensions( get_attached_file( $id ) ) ) {
                $width  = $dimensions['width'];
                $height = $dimensions['height'];
            } else {
                $width  = get_option( "{$size}_size_w", false );
                $height = get_option( "{$size}_size_h", false );
            }

            if ( $height && $width ) {
                $html = str_replace( 'width="1" ', sprintf( 'width="%s" ', $width ), $html );
                $html = str_replace( 'height="1" ', sprintf( 'height="%s" ', $height ), $html );
            } else {
                $html = str_replace( 'width="1" ', '', $html );
                $html = str_replace( 'height="1" ', '', $html );
            }

            $html = str_replace( '/>', ' role="img" />', $html );
        }

        return $html;
    }

    public function skip_svg_regeneration( $metadata, $attachment_id ) {
        $mime = get_post_mime_type( $attachment_id );
        if ( 'image/svg+xml' === $mime ) {
            $additional_image_sizes = wp_get_additional_image_sizes();
            $svg_path               = get_attached_file( $attachment_id );
            $upload_dir             = wp_upload_dir();
            // get the path relative to /uploads/ - found no better way:
            $relative_path = str_replace( trailingslashit( $upload_dir['basedir'] ), '', $svg_path );
            $filename      = basename( $svg_path );

            $dimensions = $this->svg_dimensions( $svg_path );

            if ( ! $dimensions ) {
                return $metadata;
            }

            $metadata = array(
                'width'  => intval( $dimensions['width'] ),
                'height' => intval( $dimensions['height'] ),
                'file'   => $relative_path,
            );

            // Might come handy to create the sizes array too - But it's not needed for this workaround! Always links to original svg-file => Hey, it's a vector graphic! ;)
            $sizes = array();
            foreach ( get_intermediate_image_sizes() as $s ) {
                $sizes[ $s ] = array(
                    'width'  => '',
                    'height' => '',
                    'crop'   => false,
                );

                if ( isset( $additional_image_sizes[ $s ]['width'] ) ) {
                    // For theme-added sizes
                    $sizes[ $s ]['width'] = intval( $additional_image_sizes[ $s ]['width'] );
                } else {
                    // For default sizes set in options
                    $sizes[ $s ]['width'] = get_option( "{$s}_size_w" );
                }

                if ( isset( $additional_image_sizes[ $s ]['height'] ) ) {
                    // For theme-added sizes
                    $sizes[ $s ]['height'] = intval( $additional_image_sizes[ $s ]['height'] );
                } else {
                    // For default sizes set in options
                    $sizes[ $s ]['height'] = get_option( "{$s}_size_h" );
                }

                if ( isset( $additional_image_sizes[ $s ]['crop'] ) ) {
                    // For theme-added sizes
                    $sizes[ $s ]['crop'] = intval( $additional_image_sizes[ $s ]['crop'] );
                } else {
                    // For default sizes set in options
                    $sizes[ $s ]['crop'] = get_option( "{$s}_crop" );
                }

                $sizes[ $s ]['file']      = $filename;
                $sizes[ $s ]['mime-type'] = $mime;
            }
            $metadata['sizes'] = $sizes;
        }

        return $metadata;
    }

    public function metadata_error_fix( $data, $post_id ) {
        if ( is_wp_error( $data ) ) {
            $data = wp_generate_attachment_metadata( $post_id, get_attached_file( $post_id ) );
            wp_update_attachment_metadata( $post_id, $data );
        }
        return $data;
    }

    public function disable_srcset( $image_meta, $size_array, $image_src, $attachment_id ) {
        if ( $attachment_id && 'image/svg+xml' === get_post_mime_type( $attachment_id ) ) {
            $image_meta['sizes'] = array();
        }

        return $image_meta;
    }

    public static function instance(){
		if (!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
	}

}