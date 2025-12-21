<?php

if( !function_exists('next3_core') ){
    function next3_core(){

        $obj = new \stdClass();
        $obj->plugin = '\Next3Offload\N3aws_Plugin';
        $obj->plugin_ins = \Next3Offload\N3aws_Plugin::instance();
       
        $obj->admin = '\Next3Offload\Utilities\N3aws_Admin';
        $obj->admin_ins = \Next3Offload\Utilities\N3aws_Admin::instance();
       
        $obj->load_module = '\Next3Offload\Modules\Load';
        $obj->load_module_ins = \Next3Offload\Modules\Load::instance();
       
        $obj->pro_init = '\Next3Offload\Modules\Proactive\N3aws_Init';
        $obj->pro_init_ins = \Next3Offload\Modules\Proactive\N3aws_Init::instance();
       
        $obj->provider = '\Next3Offload\Modules\Provider';
        $obj->provider_ins = \Next3Offload\Modules\Provider::instance();

        $obj->action = '\Next3Offload\Modules\Action';
        $obj->action_ins = \Next3Offload\Modules\Action::instance();

        $obj->compatiblility = '\Next3Offload\Modules\Compatiblility';
        $obj->compatiblility_ins = \Next3Offload\Modules\Compatiblility::instance();
       
        $obj->support = '\Next3Offload\Modules\Support';
        $obj->support_ins = \Next3Offload\Modules\Support::instance();

        $obj->optimizer = '\Next3Offload\Modules\Optimizer';
        $obj->optimizer_ins = \Next3Offload\Modules\Optimizer::instance();

        $obj->svg = '\Next3Offload\Modules\Svg';
        $obj->svg_ins = \Next3Offload\Modules\Svg::instance();

        $obj->webp = '\Next3Offload\Modules\Webp';
        $obj->webp_ins = \Next3Offload\Modules\Webp::instance();

        $obj->database = '\Next3Offload\Modules\Database\Action';
        $obj->database_ins = \Next3Offload\Modules\Database\Action::instance();

        $obj->assets = '\Next3Offload\Modules\Assets';
        $obj->assets_ins = \Next3Offload\Modules\Assets::instance();

        return $obj;
    }
}


if( !function_exists('nx3aws_admin_role') ){
    function nx3aws_admin_role(){
        return apply_filters( 'nx3aws_admin_role', 'manage_options' );
    }
}

if( !function_exists('next3_print') ){
    function next3_print( $content ){
        return $content;
    }
}

if( !function_exists('next3_same_values') ){
    function next3_same_values( $array = [], $status =false ){
        if( $status ){
            return $array;
        }
        if( !empty($array) && is_array($array) ){
            $new = [];
            foreach($array as $same){
                if( !in_array($same, $new)){
                    $new[] = $same;
                }
            }
            $array = $new;
        }
        return $array;
    }
}

if( !function_exists('next3_admin_url') ){
    function next3_admin_url( $page='', $multi = NEXT3_MULTI_SITE, $url_method = 'self' ){

        if ( is_multisite() && $multi) {
            $url_method = 'network';
        }
        
        switch ( $url_method ) {
			case 'self':
				$base_url = admin_url( $page );
				break;
			default:
				
                if( NEXT3_MULTI_SITE ){
                    $base_url = network_admin_url( $page );
                } else {
                    $base_url = admin_url( $page );
                }
		}
        return $base_url;
    }
}


if( !function_exists('next3_home_url') ){
    function next3_home_url( $page ='', $multi = NEXT3_MULTI_SITE, $url_method = 'self' ){

        if ( is_multisite()  && $multi ) {
            $url_method = 'network';
        }
        
        switch ( $url_method ) {
			case 'self':
				$base_url = home_url( $page );
				break;
			default:
				
                if( NEXT3_MULTI_SITE ){
                    $base_url = network_home_url( $page );
                } else {
                    $base_url = home_url( $page );
                }
		}
        return $base_url;
    }
}

if( !function_exists('next3_rest_url') ){
    function next3_rest_url( $blogid = null, $multi = NEXT3_MULTI_SITE, $url_method = 'self' ){

        if ( is_multisite()  && $multi ) {
            $url_method = 'network';
        }
        
        switch ( $url_method ) {
			case 'self':
				$base_url = get_rest_url( next3_get_current_network_id() );
				break;
			default:
				
                if( NEXT3_MULTI_SITE ){
                    $base_url = get_rest_url( $blogid );
                } else {
                    $base_url = get_rest_url( next3_get_current_network_id() );
                }
		}
        return $base_url;
    }
}

if( !function_exists('next3_providers') ){
    function next3_providers(){
        $credentials = next3_credentials();
        $google_json = ($credentials['settings']['google']['credentails']['json_data']) ?? '';
        if( !empty($google_json) ){
            $google_json = stripslashes( $google_json);
        }
        

        return apply_filters('next3/providers/add', [
            'aws' => apply_filters('next3/providers/aws', [
                'label' => esc_html__('Amazon S3', 'next3-offload'),
                'img' => next3_core()->plugin::plugin_url() . 'assets/img/aws-2.svg',
                'docs_link' => 'https://themedev.net/blog/amazon-s3-with-wordpress-to-offload-media/',
                'delivery_providers' => [
                    'aws' => [
                        'title' => esc_html__('Amazon S3', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/aws-2.svg',
                    ],
                    'cloudflare' => [
                        'title' => esc_html__('Cloudflare', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/cloudflare.svg',
                        'cname' => true,
                        'cname_label' => 'Enter Account ID *',
                        'cname_desc' => 'Rewrite media from a custom domain that has been pointed to Cloudflare R2.'
                    ],
                    'aws_cloudfront' => [
                        'title' => esc_html__('CloudFront', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/aws_cloudfront.svg',
                        'cname' => true,
                        'cname_label' => esc_html__('Enter CloudFront domain name (CNAME) *', 'next3-offload'),
                        'cname_desc' => 'Serves media from a custom domain that has been pointed to Amazon CloudFront.'
                    ],
                    'other' => [
                        'title' => esc_html__('Other', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/other.svg',
                        'cname' => true,
                        'cname_label' => esc_html__('Enter custom URL *', 'next3-offload'),
                        'cname_desc' => esc_html__('Use your custom domain url for delivery', 'next3-offload')
                    ],
                ],
                'field' => [
                    'wp' => [
                        'title' => esc_html__('Define access keys in wp-config.php', 'next3-offload'),
                        'docs_link' => 'https://support.themedev.net/docs/next3-offload/setup-provider-5539/aws-s3-5542/define-your-access-keys-5549/',
                        'desc' => 'Copy this code and replace or paste it into your wp-config.php page.',
                        'field' => [
                            'code_samp' => [
                                'type' => 'textarea',
                                'label' => esc_html__('Code', 'next3-offload'),
                                'readonly' => true,
                                'render' => false,
                                'default' => "define( 'NEXT3_SETTINGS', serialize( array(
    'provider' => 'aws',
    'access-key-id' => '********************',
    'secret-access-key' => '**************************************',
    'endpoint' => '',
    'region' => '',
) ) );"
                            ],
                        ]
                    ],
                    'credentails' => [
                        'title' => esc_html__('I understand, it has risks but I would like to store access keys in the database (not recommended). ', 'next3-offload'),
                        'docs_link' => 'https://support.themedev.net/docs/next3-offload/setup-provider-5539/aws-s3-5542/define-your-access-keys-5549/',
                        'desc' => esc_html__('Storing your access keys in the database is less secure than the options above, but if you are okay with that, go ahead and enter your keys in the form below.', 'next3-offload'),
                        'field' => [
                            'access_key' => [
                                'type' => 'password',
                                'label' => esc_html__('Access Key ID', 'next3-offload'),
                                'default' => ($credentials['settings']['aws']['credentails']['access_key']) ?? ''
                            ],
                            'secret_key' => [
                                'type' => 'password',
                                'label' => esc_html__('Secret Access Key', 'next3-offload'),
                                'default' => ($credentials['settings']['aws']['credentails']['secret_key']) ?? ''
                            ],
                            'default_region' => [
                                'type' => 'select',
                                'label' => esc_html__('Default Region', 'next3-offload'),
                                'default' => ($credentials['settings']['aws']['default_region']) ?? 'eu-west-2',
                                'options' => next3_aws_region('', 'aws')
                            ],
                        ]
                    ],
                ]

            ]),
           
            'digital' => apply_filters('next3/providers/digital', [
                'label' => esc_html__('DigitalOcean Spaces', 'next3-offload'),
                'img' => next3_core()->plugin::plugin_url() . 'assets/img/digital-1.svg',
                //'upcomming' => 'upcomming',
                'docs_link' => 'https://themedev.net/blog/offload-wordpress-media-to-digital-ocean/',
                'delivery_providers' => [
                    'digital' => [
                        'title' => esc_html__('DigitalOcean Spaces', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/digital-1.svg',
                    ],
                    'keycdn' => [
                        'title' => esc_html__('KeyCDN', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/keysdn.svg',
                        'cname' => true,
                        'cname_label' => esc_html__('Enter KeyCDN domain name (CNAME) *', 'next3-offload'),
                        'cname_desc' => 'Serves media from a custom domain that has been pointed to KeyCDN.'
                    ],
                    
                    'digital_cdn' => [
                        'title' => esc_html__('DigitalOcean Spaces CDN', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/digital-1.svg',
                        'cname' => true,
                        'cname_label' => esc_html__('Enter Spaces CDN url', 'next3-offload'),
                        'cname_desc' => 'Serves media from a custom domain that has been pointed to DigitalOcean CDN.'
                    ],
                    'other' => [
                        'title' => esc_html__('Other', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/other.svg',
                        'cname' => true,
                        'cname_label' => esc_html__('Enter custom URL *', 'next3-offload'),
                        'cname_desc' => esc_html__('Use your custom domain url for delivery', 'next3-offload')
                    ],
                ],
                'field' => [
                    'wp' => [
                        'title' => esc_html__('Define access keys in wp-config.php', 'next3-offload'),
                        'docs_link' => 'https://support.themedev.net/docs/next3-offload/setup-provider-5539/',
                        'desc' => 'Copy this code and replace or paste it into your wp-config.php page.',
                        'field' => [
                            'code_samp' => [
                                'type' => 'textarea',
                                'label' => esc_html__('Code:', 'next3-offload'),
                                'readonly' => true,
                                'render' => false,
                                'default' => "define( 'NEXT3_SETTINGS', serialize( array(
    'provider' => 'digital',
    'access-key-id' => '********************',
    'secret-access-key' => '**************************************',
    'region' => '',
) ) );"
                            ],
                        ]
                    ],
                    'credentails' => [
                        'title' => esc_html__('I understand, it has risks but I would like to store access keys in the database (not recommended). ', 'next3-offload'),
                        'docs_link' => 'https://support.themedev.net/docs/next3-offload/setup-provider-5539/',
                        'desc' => esc_html__('Storing your access keys in the database is less secure than the options above, but if you are okay with that, go ahead and enter your keys in the form below.', 'next3-offload'),
                        'field' => [
                            'access_key' => [
                                'type' => 'password',
                                'label' => esc_html__('Spaces Access Key', 'next3-offload'),
                                'default' => ($credentials['settings']['digital']['credentails']['access_key']) ?? ''
                            ],
                            'secret_key' => [
                                'type' => 'password',
                                'label' => esc_html__('Secret Key', 'next3-offload'),
                                'default' => ($credentials['settings']['digital']['credentails']['secret_key']) ?? ''
                            ],
                            'default_region' => [
                                'type' => 'select',
                                'label' => esc_html__('Default Region', 'next3-offload'),
                                'default' => ($credentials['settings']['digital']['default_region']) ?? 'nyc3',
                                'options' => next3_aws_region('', 'digital')
                            ],
                            
                        ]
                    ],
                ]
            ]),
            'wasabi' => apply_filters('next3/providers/wasabi', [
                'label' => esc_html__('Wasabi Cloud', 'next3-offload'),
                'img' => next3_core()->plugin::plugin_url() . 'assets/img/wasabi-icon.svg',
                //'upcomming' => 'upcomming',
                'docs_link' => 'https://themedev.net/blog/offload-wordpress-media-to-wasabi-cloud/',
                'delivery_providers' => [
                    'wasabi' => [
                        'title' => esc_html__('Wasabi Cloud', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/wasabi-icon.svg',
                        'cname' => false,
                        'cname_label' =>  esc_html__('Enter CDN host URL', 'next3-offload'),
                        'cname_desc' => 'Go to Dashboard - console.wasabisys.com > users > create user, then copy your hostname and paste it here.'
                    ],
                    'cloudflare' => [
                        'title' => esc_html__('Cloudflare', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/cloudflare.svg',
                        'cname' => true,
                        'cname_label' =>  esc_html__('Cloudflare custom domain (CNAME) *', 'next3-offload'),
                        'cname_desc' => 'Rewrite media from a custom domain that has been pointed to Cloudflare.'
                    ],
                    'other' => [
                        'title' => esc_html__('Other', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/other.svg',
                        'cname' => true,
                        'cname_label' => esc_html__('Enter custom URL *', 'next3-offload'),
                        'cname_desc' => esc_html__('Use your custom domain url for delivery', 'next3-offload')
                    ],
                ],
                'field' => [
                    'wp' => [
                        'title' => esc_html__('Define access keys in wp-config.php', 'next3-offload'),
                        'docs_link' => 'https://support.themedev.net/docs/next3-offload/setup-provider-5539/',
                        'desc' => 'Copy this code and replace or paste it into your wp-config.php page.',
                        'field' => [
                            'code_samp' => [
                                'type' => 'textarea',
                                'label' => esc_html__('Code:', 'next3-offload'),
                                'readonly' => true,
                                'render' => false,
                                'default' => "define( 'NEXT3_SETTINGS', serialize( array(
    'provider' => 'wasabi',
    'access-key-id' => '********************',
    'secret-access-key' => '**************************************',
    'region' => '',
) ) );"
                            ],
                        ]
                    ],
                    'credentails' => [
                        'title' => esc_html__('I understand, it has risks but I would like to store access keys in the database (not recommended). ', 'next3-offload'),
                        'docs_link' => 'https://support.themedev.net/docs/next3-offload/setup-provider-5539/',
                        'desc' => esc_html__('Storing your access keys in the database is less secure than the options above, but if you are okay with that, go ahead and enter your keys in the form below.', 'next3-offload'),
                        'field' => [
                            'access_key' => [
                                'type' => 'password',
                                'label' => esc_html__('Access Key', 'next3-offload'),
                                'default' => ($credentials['settings']['wasabi']['credentails']['access_key']) ?? ''
                            ],
                            'secret_key' => [
                                'type' => 'password',
                                'label' => esc_html__('Secret Key', 'next3-offload'),
                                'default' => ($credentials['settings']['wasabi']['credentails']['secret_key']) ?? ''
                            ],
                            'default_region' => [
                                'type' => 'select',
                                'label' => esc_html__('Default Region', 'next3-offload'),
                                'default' => ($credentials['settings']['wasabi']['default_region']) ?? 'eu-central-2',
                                'options' => next3_aws_region('', 'wasabi')
                            ],
                        ]
                    ],
                ]
            ]),
            'bunny' => apply_filters('next3/providers/bunny', [
                'label' => esc_html__('Bunny Storage', 'next3-offload'),
                'img' => next3_core()->plugin::plugin_url() . 'assets/img/bunny-icon.svg',
                'docs_link' => 'https://themedev.net/blog/integrate-bunny-cdn-to-offload-wordpress-media/',
                'delivery_providers' => [
                    'bunny' => [
                        'title' => esc_html__('Bunny CDN', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/bunny-icon.svg',
                        'cname' => true,
                        'cname_label' =>  esc_html__('Enter CDN host URL', 'next3-offload'),
                        'cname_desc' => 'Go to Dashboard - dash.bunny.net > Delivery > CDN > General > Hostname, then copy your hostname and paste it here.'
                    ],
                    
                    /*'bunny_stream' => [
                        'title' => esc_html__('Bunny Stream', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/bunny-icon.svg',
                        'cname' => true,
                        'cname_label' =>  esc_html__('Enter CDN host URL', 'next3-offload'),
                        'cname_desc' => 'Go to Dashboard - dash.bunny.net > Delivery > Stream > API > CDN Hostname, then copy your hostname and paste here.'
                    ],*/
                    'other' => [
                        'title' => esc_html__('Other', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/other.svg',
                        'cname' => true,
                        'cname_label' => esc_html__('Enter custom URL *', 'next3-offload'),
                        'cname_desc' => esc_html__('Use your custom domain url for delivery', 'next3-offload')
                    ],
                ],
                'field' => [
                    'wp' => [
                        'title' => esc_html__('Define access keys in wp-config.php', 'next3-offload'),
                        'docs_link' => 'https://support.themedev.net/docs/next3-offload/setup-provider-5539/',
                        'desc' => 'Copy this code and replace or paste it into your wp-config.php page.',
                        'field' => [
                            'code_samp' => [
                                'type' => 'textarea',
                                'label' => esc_html__('Code:', 'next3-offload'),
                                'readonly' => true,
                                'render' => false,
                                'default' => "define( 'NEXT3_SETTINGS', serialize( array(
    'provider' => 'bunny',
    'api-key' => 'api key or password',
) ) );"
                            ],
                        ]
                    ],
                    'credentails' => [
                        'title' => esc_html__('I understand, it has risks but I would like to store access keys in the database (not recommended). ', 'next3-offload'),
                        'docs_link' => 'https://support.themedev.net/docs/next3-offload/setup-provider-5539/',
                        'desc' => esc_html__('Storing your access keys in the database is less secure than the options above, but if you are okay with that, go ahead and enter your keys in the form below.', 'next3-offload'),
                        'field' => [
                            'api_key' => [
                                'type' => 'password',
                                'label' => esc_html__('API Key - Storage', 'next3-offload'),
                                'default' => ($credentials['settings']['bunny']['credentails']['api_key']) ?? ''
                            ],
                            /*'heading' => [
                                'type' => 'heading',
                                'label' => esc_html__('Or', 'next3-offload'),
                            ],
                            'stream_api' => [
                                'type' => 'password',
                                'label' => esc_html__('API Key - Stream (Optional)', 'next3-offload'),
                                'default' => ($credentials['settings']['bunny']['credentails']['stream_api']) ?? ''
                            ],
                            'video_id' => [
                                'type' => 'text',
                                'label' => esc_html__('Video Library ID (Optional)', 'next3-offload'),
                                'default' => ($credentials['settings']['bunny']['credentails']['video_id']) ?? ''
                            ],*/
                           
                        ]
                    ],
                ]
            ]),
            'objects' => apply_filters('next3/providers/objects', [
                'label' => esc_html__('S3 Object Storage', 'next3-offload'),
                'img' => next3_core()->plugin::plugin_url() . 'assets/img/object.svg',
                'docs_link' => 'https://themedev.net/blog/s3-compatible-objects-with-wordpress-to-offload-files/',
                'delivery_providers' => [
                    'objects' => [
                        'title' => esc_html__('S3 Object Storage', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/object.svg',
                    ],
                    'public_endpoint' => [
                        'title' => esc_html__('Delivery Endpoint', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/object.svg',
                        'cname' => true,
                        'cname_label' => 'Public URL\'s *',
                        'cname_desc' => 'Rewrite media from different of primary endpoint, that you can see your Cloud URL\'s (Example: n7g6.c1.e2-6.dev/media)'
                    ],
                    'other' => [
                        'title' => esc_html__('Other', 'next3-offload'),
                        'url' => '',
                        'img' => next3_core()->plugin::plugin_url() . 'assets/img/other.svg',
                        'cname' => true,
                        'cname_label' => esc_html__('Enter custom URL *', 'next3-offload'),
                        'cname_desc' => esc_html__('Use your custom domain url for delivery', 'next3-offload')
                    ],
                ],
                'field' => [
                    'wp' => [
                        'title' => esc_html__('Define access keys in wp-config.php', 'next3-offload'),
                        'docs_link' => 'https://support.themedev.net/docs/next3-offload/setup-provider-5539/',
                        'desc' => 'Copy this code and replace or paste it into your wp-config.php page.',
                        'field' => [
                            'code_samp' => [
                                'type' => 'textarea',
                                'label' => esc_html__('Code', 'next3-offload'),
                                'readonly' => true,
                                'render' => false,
                                'default' => "define( 'NEXT3_SETTINGS', serialize( array(
    'provider' => 'objects',
    'access-key-id' => '********************',
    'secret-access-key' => '**************************************',
    'endpoint' => '',
) ) );"
                            ],
                        ]
                    ],
                    'credentails' => [
                        'title' => esc_html__('I understand, it has risks but I would like to store access keys in the database (not recommended). ', 'next3-offload'),
                        'docs_link' => 'https://support.themedev.net/docs/next3-offload/setup-provider-5539/aws-s3-5542/define-your-access-keys-5549/',
                        'desc' => esc_html__('Storing your access keys in the database is less secure than the options above, but if you are okay with that, go ahead and enter your keys in the form below.', 'next3-offload'),
                        'field' => [
                            'access_key' => [
                                'type' => 'password',
                                'label' => esc_html__('Access Key ID', 'next3-offload'),
                                'default' => ($credentials['settings']['objects']['credentails']['access_key']) ?? ''
                            ],
                            'secret_key' => [
                                'type' => 'password',
                                'label' => esc_html__('Secret Access Key', 'next3-offload'),
                                'default' => ($credentials['settings']['objects']['credentails']['secret_key']) ?? ''
                            ],
                            'endpoint_stroage' => [
                                'type' => 'text',
                                'label' => esc_html__('Endpoint (Cluster URL)', 'next3-offload'),
                                'placeholder' => esc_html('Exam: https://worldportchapter.nl-ams1.upcloudobjects.com', 'next3-offload'),
                                'default' => ($credentials['settings']['objects']['credentails']['endpoint_stroage']) ?? '',
                            ],
                            
                        ]
                    ],
                ]

            ]),
            
        ]);
    }
}

if( !function_exists('next3_credentials_default') ){
    function next3_credentials_default(){
        $credentials = next3_get_option('theme-dev-aws-credentials', true);
        $data = [
            'settings' => [
                'provider' => 'aws',
                'aws' => [
                    'type' => 'credentails',
                    'credentails' => [
                        'access_key' => ($credentials['aws_access_id']) ?? '',
                        'secret_key' => ($credentials['aws_secret_access_key']) ?? '',
                        'region' => ($credentials['region']) ?? '',
                    ]
                ]
            ]
        ];
        return $data;
    }
}


if( !function_exists('next3_credentials_key') ){
    function next3_credentials_key(){
        return '__next3_settings';
    }
}
if( !function_exists('next3_credentials') ){
    function next3_credentials(){
        return next3_get_option(next3_credentials_key(), []);
    }
}

if( !function_exists('next3_options_key') ){
    function next3_options_key(){
        return '__next3_options';
    }
}

if( !function_exists('next3_options') ){
    function next3_options(){
        $defult = [
            'storage' => [
                'copy_file' => 'yes',
                'enable_path' => 'yes',
                'folder_format' => 'yes',
                'offload_limit' => 5000,
                'offload_paged' => 1
            ],
            'delivery' => [
                'rewrite_urls' => 'yes',
            ],
            'optimization' => [
                'compression' => 'no', 
                'compression_level' => 'none', 
                'backup_orginal' => 'yes',
                'optimizer_resize_images' => '2560'
            ],
            'assets' => [
                'css_offload' => 'no',
                'js_offload' => 'no',
                'minify_css' => 'no',
                'minify_js' => 'no',
            ]
        ];
        $credentials = next3_credentials();
        $services = ($credentials['settings']['services']) ?? ['offload', 'optimization', 'database'];
        if( !in_array('offload', $services)){
            $defult = [
                'storage' => [
                ],
                'delivery' => [
                    'rewrite_urls' => 'yes',
                    'disable_cache' => 'yes',
                ],
                'optimization' => [
                    'compression' => 'no', 
                    'compression_level' => 'none', 
                    'backup_orginal' => 'yes',
                    'optimizer_resize_images' => '2560'
                ],
                'assets' => [
                ]
            ];
        }
        $data = next3_get_option(next3_options_key(), $defult);

        if( !in_array('offload', $services)){
            $data['delivery']['rewrite_urls'] = 'yes';
            $data['delivery']['disable_cache'] = 'yes';
        }

        return $data;
    }
}

if( !function_exists('next3_update_option') ){
    function next3_update_option($key, $data, $status = true, $multi = NEXT3_MULTI_SITE){
        if ( is_multisite() && $multi ) {
            $return = update_site_option($key, $data);
            //$return = update_network_option(next3_get_current_network_id(), $key, $data);
        } else{
            $return = update_option($key, $data, $status);
            //$return = update_network_option(next3_get_current_network_id(), $key, $data);
        }
        return $return;
    }
}

if( !function_exists('next3_get_option') ){
    function next3_get_option($key, $defalut = [], $multi = NEXT3_MULTI_SITE){
        if ( is_multisite() && $multi ) {
            $return = get_site_option($key, $defalut);
            //$return = get_network_option(next3_get_current_network_id(), $key, $defalut);
        } else{
            $return = get_option($key, $defalut);
            //$return = get_network_option(next3_get_current_network_id(), $key, $defalut);
        }
        return $return;
    }
}

if( !function_exists('next3_delete_option') ){
    function next3_delete_option($key, $multi = NEXT3_MULTI_SITE){
        if ( is_multisite() && $multi ) {
            $return = delete_site_option($key);
            //$return = delete_network_option(next3_get_current_network_id(), $key);
        } else{
            $return = delete_option($key);
            //$return = delete_network_option(next3_get_current_network_id(), $key);
        }
        return $return;
    }
}


if( !function_exists('next3_get_current_network_id') ){
    function next3_get_current_network_id(){
        return get_current_network_id();
    }
}

if( !function_exists('next3_get_current_blog_id') ){
    function next3_get_current_blog_id(){
        return get_current_blog_id();
    }
}

if( !function_exists('next3_get_current_blogs') ){
    function next3_get_current_blogs(){
        global $wpdb;
        $res = [];
        if ( is_multisite() && NEXT3_MULTI_SITE ) {
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
            
            foreach ($blog_ids as $blog_id) {
                $res[] = $blog_id;
            }
        } else {
            $res = [0];
        }
        return $res;
    }
}

if( !function_exists('next3_check_options') ){
    function next3_check_options( $key, $is_multisite = false ) {
		//$value = false === $is_multisite ? next3_get_option( $key ) : get_option( $key ); //get_site_option
		$value = next3_get_option( $key );
		if ( 1 === (int) $value ) {
			return true;
		}
		return false;
	}
}

if( !function_exists('next3_check_post_meta') ){
    function next3_check_post_meta($id, $key, $default = true ) {
		$value = next3_get_post_meta($id, $key);
		if ( 1 === (int) $value ) {
			return true;
		}
		return false;
	}
}

if( !function_exists('next3_check_webp_ext') ){
    function next3_check_webp_ext($string, $default = 'webp' ) {
		if( empty($string) ){
            return false;
        }
		if ( $default == strtolower( end( explode('.', $string)) ) ) {
			return true;
		}
		return false;
	}
}

if( !function_exists('next3_upload_status') ){
    function next3_upload_status(){
        $credentials = next3_credentials();
        $provider = ($credentials['settings']['provider']) ?? '';
        // check enable srvices
        $services = ($credentials['settings']['services']) ?? ['offload', 'optimization', 'database'];
        if( !in_array('offload', $services)){
            return false;
        }

        if( in_array( $provider, ['bunny', 'wasabi'])){
            //return true;
        }
        $prodiver_data = ($credentials['settings'][$provider]) ?? [];
        $file_permission = ($prodiver_data['file_permission']) ?? false;
        return $file_permission;
    }
}

if(!function_exists('next3_aws_region') ){
    function next3_aws_region( $name = '', $provider = 'aws'){
        if( !empty($name) && $provider == 'bunny' ){
            return $name;
        }
        return next3_core()->provider_ins->load($provider)->access( false )->get_regions( $name );
    }
}


if( !function_exists( 'next3_sanitize' ) ){
    function next3_sanitize($value, $senitize_func = 'sanitize_text_field'){
        $senitize_func = (in_array($senitize_func, [
                'sanitize_email', 
                'sanitize_file_name', 
                'sanitize_hex_color', 
                'sanitize_hex_color_no_hash', 
                'sanitize_html_class', 
                'sanitize_key', 
                'sanitize_meta', 
                'sanitize_mime_type',
                'sanitize_sql_orderby',
                'sanitize_option',
                'sanitize_text_field',
                'sanitize_title',
                'sanitize_title_for_query',
                'sanitize_title_with_dashes',
                'sanitize_user',
                'esc_url_raw',
                'wp_filter_nohtml_kses',
            ])) ? $senitize_func : 'sanitize_text_field';
        
        if(!is_array($value)){
            return $senitize_func($value);
        }else{
            return array_map(function($inner_value) use ($senitize_func){
                return next3_sanitize($inner_value, $senitize_func);
            }, $value);
        }
	}
}
if( !function_exists('next3_media_action_strings') ){

    function next3_media_action_strings( $string = null ) {
        $not_verified_value = __( 'No', 'next3-offload');
        $not_verified_value .= '&nbsp;';
        
        $strings = apply_filters( 'next3_media_action_strings', array(
            'provider'      => _x( 'Storage Provider', 'Storage provider key name', 'next3-offload'),
            'provider_name' => _x( 'Storage Provider', 'Storage provider name', 'next3-offload'),
            'bucket'        => _x( 'Bucket', 'Bucket name', 'next3-offload'),
            'key'           => _x( 'Path', 'Path to file in bucket', 'next3-offload'),
            'region'        => _x( 'Region', 'Location of bucket', 'next3-offload'),
            'acl'           => _x( 'Access', 'Access control list of the file in bucket', 'next3-offload'),
            'url'           => __( 'Preview URL', 'next3-offload'),
            'is_verified'   => _x( 'Verified', 'Whether or not metadata has been verified', 'next3-offload'),
            'not_verified'  => $not_verified_value,
        ) );
    
        if ( ! is_null( $string ) ) {
            return isset( $strings[ $string ] ) ? $strings[ $string ] : '';
        }
    
        return $strings;
    }
}

if (!function_exists('next3_post_id_by_meta')) {
	
	function next3_post_id_by_meta($key, $value) {
		global $wpdb;
		$meta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->postmeta." WHERE meta_key=%s AND meta_value=%s", $key, $value ) );
        if (is_array($meta) && !empty($meta) && isset($meta[0])) {
			$meta = $meta[0];
		}		
		if (is_object($meta)) {
			return $meta->post_id;
		}
		else {
			return false;
		}
	}
}

if (!function_exists('next3_wp_get_attachment_url')) {
    function next3_wp_get_attachment_url($attachment_id, $size = 'full'){
        return next3_core()->action_ins->get_attatchment_url_preview($attachment_id, $size );
    }
}

if (!function_exists('next3_get_metadata_size')) {
    function next3_get_metadata_size($attachment_id, $size = 'full', $return = false){
        return next3_core()->action_ins->next3_get_metadata_size($attachment_id, $size, $return);
    }
}
if (!function_exists('next3_check_setup')) {
    function next3_check_setup( $type = 'status'){
        $setup = next3_core()->admin_ins->check_setup();
        if($type == 'array'){
            return $setup;
        }
        $step = ($setup['step']) ?? '';
        return ($step == 'dashboard') ? true : false;
    }
}

if (!function_exists('next3_service_status')) {
    function next3_service_status( $type = 'status'){
        $credentials = next3_credentials();
        $services = ($credentials['settings']['services']) ?? ['offload', 'optimization', 'database'];

        $get_package = next3_license_package();

        $status_optimization = false;
        if( in_array('optimization', $services)){
            $status_optimization = true;
        }

        $status_database = false;
        if( in_array('database', $services)){
            $status_database = true;
        }

        $status_offload = false;
        if( in_array('offload', $services)){
            $status_offload = true;
        }

        $develeper_status = false;
        if( in_array($get_package, ['business', 'developer', 'extended']) ){
            $develeper_status = true;
        }

        $assets_status = false;
        if( in_array($get_package, ['developer', 'extended']) ){
            $assets_status = true;
        }

        $status = [
            'optimization' => $status_optimization,
            'database' => $status_database,
            'offload' => $status_offload,
            'optimization' => $status_optimization,
            'assets' => $assets_status,
            'develeper' => $develeper_status,
        ];

        return isset( $status[ $type ]) ? $status[ $type ] : $status;
    }
}

if (!function_exists('next3_check_rewrite')) {
    function next3_check_rewrite( $type = 'none', $attachment_id = 0 ){ // rewrite
        $result = false;
        $check_steup = next3_check_setup();
        if( !$check_steup ){
            return $result;
        }
        $settings_options = next3_options();
        
        if( $type == 'rewrite'){
            $rewrite_urls = 'yes';
            if( !empty($settings_options) ){
                $rewrite_urls = ($settings_options['delivery']['rewrite_urls']) ?? 'no';
                $remove_local = ($settings_options['storage']['remove_local']) ?? 'no';
                if( $remove_local == 'yes'){
                    $rewrite_urls = 'yes';
                }
                
            }
            return ($rewrite_urls == 'yes' || true === next3_check_post_meta($attachment_id, 'next3_optimizer_is_converted_to_webp')) ? true : false;
        }
        
        return $result;
    }
}

if (!function_exists('next3_allowed_mime_ext')) {
    function next3_allowed_mime_ext(){
        return apply_filters( 'next3_allowed_mime_ext', [
            '.jpg', 
            '.jpeg', 
            '.png', 
            '.txt', 
            '.gif', 
            '.ico',
            '.pdf', 
            '.svg', 
            '.json',
            '.mp4', 
            '.avi', 
            '.mkv', 
            '.mov', 
            '.flv', 
            '.swf', 
            '.wmv', 
            '.m4a', 
            '.ogg', 
            '.wav', 
            '.m4v', 
            '.mpg', 
            '.ogv', 
            '.3gp', 
            '.3g2',
            '.zip', 
            '.rar', 
            '.tar', 
            '.iso', 
            '.mar',
            '.ppt',
            '.pptx',
            '.pps',
            '.ppsx',
            '.odt',
            '.xls',
            '.xlsx',
            '.psd', 
            '.ai', 
            '.eps',
            '.indd',
            '.3ds',
            '.skp',
            '.script',
            '.mp3',
            '.webp',
            '.mpz',
            '.doc', 
            '.docx', 
            '.rtf', 
            '.odt',
            '.7z', 
            '.gz',
            '.aac', 
            '.flac'
        ] );
    }
}

if (!function_exists('next3_allowed_mime_type')) {
    function next3_allowed_mime_type(){
        return apply_filters( 'next3_allowed_mime_type', get_allowed_mime_types());
    }
}

if (!function_exists('next3__get_available_file_types')) {
    function next3__get_available_file_types(){
        $mime_types_serialized = trim( file_get_contents( next3_core()->plugin::plugin_dir() . 'assets/file-types-list.json' ) ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	    return json_decode( $mime_types_serialized, true );
    }
}

if (!function_exists('next3__format_raw_custom_types')) {
    function next3__format_raw_custom_types( $file_data_raw ) {

        $file_data   = array();
        $description = isset( $file_data_raw['desc'] ) ? array_map( 'sanitize_text_field', $file_data_raw['desc'] ) : array();
        $mime_types  = isset( $file_data_raw['mime'] ) ? array_map( 'sanitize_text_field', $file_data_raw['mime'] ) : array();
        $extentions  = isset( $file_data_raw['ext'] ) ? array_map( 'sanitize_text_field', $file_data_raw['ext'] ) : array();

        foreach ( $description as $key => $desc ) {
            $file_data[ $key ]['desc'] = $desc;
        }

        foreach ( $mime_types as $key => $mime_type ) {
            $file_data[ $key ]['mime'] = strpos( $mime_type, ',' ) === false ? $mime_type : array_filter( array_map( 'trim', explode( ',', $mime_type ) ) );
        }

        foreach ( $extentions as $key => $extention ) {
            $file_data[ $key ]['ext'] = '.' . strtolower( ltrim( $extention, '.' ) );
        }

        return $file_data;
    }
}

if( !function_exists('next3_allowed_compression_level') ){
    function next3_allowed_compression_level(){
        return apply_filters( 'next3_allowed_compression_levels', [
            '0' => 'None',
            '1' => 'Low (25%)',
            '2' => 'Medium (60%)',
            '3' => 'High (85%)',
        ]);
    }
}
if( !function_exists('next3_exclude_css_list') ){
    function next3_exclude_css_list( $type = 'styles', $all_include = true, $get = ''){
        $assets = next3_core()->assets_ins->assets_list();
       
        $css = ($assets[$type]) ?? [];
        $css_header = ($css['header']) ?? [];
        $css_default = ($css['default']) ?? [];
        $css_dnon_minified = ($css['non_minified']) ?? [];

        $get_return = [];
        $css_load = [];
        if( $all_include == true){
            //$css_load = ['all' => 'All'];
        }
        
        foreach($css_header as $v){
            $handler = ($v['value']) ?? '';
            $title = ($v['title']) ?? '';
            $group = ($v['group']) ?? '';
            if( empty($handler) || empty($title) ){
                continue;
            }
            if( !in_array($handler, $css_load) ){
                $css_load[ $handler ] = $title;
            }
            if( !empty($get) && $handler == $get){
                $get_return = $v;
            }
        }

        foreach($css_default as $v){
            $handler = ($v['value']) ?? '';
            $title = ($v['title']) ?? '';
            $group = ($v['group']) ?? '';
            if( empty($handler) || empty($title) ){
                continue;
            }
            if( !in_array($handler, $css_load) ){
                $css_load[ $handler ] = $title;
            }
            if( !empty($get) && $handler == $get){
                $get_return = $v;
            }
        }

        foreach($css_dnon_minified as $v){
            $handler = ($v['value']) ?? '';
            $title = ($v['title']) ?? '';
            $group = ($v['group']) ?? '';
            if( empty($handler) || empty($title) ){
                continue;
            }
            if( !in_array($handler, $css_load) ){
                $css_load[ $handler ] = $title;
            }
            if( !empty($get) && $handler == $get){
                $get_return = $v;
            }
        }
        if( !empty($get)){
            return $get_return;
        }
        return apply_filters( 'next3_selected_include_'. $type, $css_load);
    }
}

if( !function_exists('next3_allowed_max_image_size') ){
    function next3_allowed_max_image_size(){
        $sizes = next3_core()->optimizer_ins->default_max_width_sizes;
        $new_arr = [];

        foreach($sizes as $v){
            $value = ($v['value']) ?? '';
            $label = ($v['label']) ?? '';
            $new_arr[ $value ] = $label;
        }

        return apply_filters( 'next3_allowed_max_image_sizes', $new_arr);
    }
}

if( !function_exists('next3_wp_offload_table')){
    function next3_wp_offload_table( $blog_id){
        global $wpdb;

        $blog_id = empty($blog_id) ? next3_get_current_blog_id() : $blog_id;

		return $wpdb->get_blog_prefix( $blog_id ) . 'as3cf_items';
    }
}


if( !function_exists('next3_random_string')){
    function next3_random_string($length = 20) {
        $key = time() . '-';
        $keys = array_merge(range(0, 9), range('a', 'z'));
    
        for ($i = 0; $i < $length; $i++) {
            $key .= $keys[array_rand($keys)];
        }
    
        return $key;
    }
}

if(!function_exists('next3_license_package')){
    function next3_license_package(){
        $key = next3_get_option('__validate_author_next3aws_keys__', '');
        if( empty($key) || !class_exists('\Next3Offload\Utilities\Check\N3aws_Valid') ){
            return 'invalid';
        }
        $data = \Next3Offload\Utilities\Check\N3aws_Valid::instance()->get_pro($key);
        $datalicense = isset($data->datalicense) ? $data->datalicense : '';
        if( empty($datalicense)){
            return 'invalid';
        }
        if( $datalicense == 'extended'){
            return 'extended';
        }
        if( isset($datalicense->license_limit) ){
            $limit = ($datalicense->license_limit) ?? 1;
            if( $limit == 1){
                return 'personal';
            } else if( $limit == 0){
                return 'developer';
            } else if( $limit == 10){
                return 'extended';
            } else{
                return 'business';
            }
        }
        return 'invalid';
    }
}


if( !function_exists('next3_update_post_meta') ){
    function next3_update_post_meta($id, $key, $data, $multi = NEXT3_MULTI_SITE){
        if ( is_multisite() && $multi ) {
            $return = update_post_meta($id, $key, $data);
        } else{
            $return = update_post_meta($id, $key, $data);
        }
        return $return;
    }
}


if( !function_exists('next3_delete_post_meta') ){
    function next3_delete_post_meta($id, $key, $multi = NEXT3_MULTI_SITE){
        if ( is_multisite() && $multi ) {
            $return = delete_post_meta($id, $key);
        } else{
            $return = delete_post_meta($id, $key);
        }
        return $return;
    }
}

if( !function_exists('next3_get_post_meta')){
    function next3_get_post_meta($postid = '', $meta_key = '', $post_type = 'attachment', $post_status = 'inherit') {

        if( empty( $meta_key ) || empty($postid)){
            return false;
        }
       
        if( metadata_exists('post', $postid, $meta_key) === true ) {
            return get_post_meta($postid, $meta_key, true);
        }
        
        global $wpdb;
        $meta_values = $wpdb->get_col( $wpdb->prepare( "
            SELECT pm.meta_value FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = %s 
            AND pm.post_id = %s 
            AND p.post_type = %s 
            AND p.post_status = %s 
        ", $meta_key, $postid, $post_type, $post_status ) );
       

        if( !empty($meta_values) && isset($meta_values[0])){
            return maybe_unserialize( $meta_values[0] );
        }
        return false;
    }
}

if( !function_exists('next3_get_attached_file')){
    function next3_get_attached_file( $postid, $status = true){
        $source_file = get_attached_file( $postid, $status);
        if( empty($source_file) || !is_file($source_file)){

            $uploads = wp_get_upload_dir();
            $source_file = next3_get_post_meta( $postid, '_wp_attached_file');
            if ( $source_file && ! str_starts_with( $source_file, '/' ) && ! preg_match( '|^.:\\\|', $source_file ) ) {
                if ( false === $uploads['error'] ) {
                    $source_file = $uploads['basedir'] . "/$source_file";
                }
            }
        }
        return $source_file;
    }
}
if( !function_exists('next3_wp_get_attachment_metadata')){
    function next3_wp_get_attachment_metadata( $attachment_id = 0, $unfiltered = false ) {
        $attachment_id = (int) $attachment_id;
    
        if ( ! $attachment_id ) {
            return false;
        }
    
        $data = next3_get_post_meta( $attachment_id, '_wp_attachment_metadata');
    
        if ( ! $data ) {
            return false;
        }
    
        if ( $unfiltered ) {
            return $data;
        }
    
        /**
         * Filters the attachment meta data.
         *
         * @since 2.1.0
         *
         * @param array $data          Array of meta data for the given attachment.
         * @param int   $attachment_id Attachment post ID.
         */
        return apply_filters( 'wp_get_attachment_metadata', $data, $attachment_id );
    }
}


if( !function_exists('next3wp_get_attachment_url') ){
    function next3wp_get_attachment_url( $attachment_id = 0 ){

        global $pagenow;

        $attachment_id = (int) $attachment_id;

        $post = get_post( $attachment_id );
    
        if ( ! $post ) {
            return false;
        }
    
        if ( 'attachment' !== $post->post_type ) {
            return false;
        }
        $url = '';
        // Get attached file.
        $file = next3_get_post_meta( $post->ID, '_wp_attached_file', true );
        if ( $file ) {
            // Get upload directory.
            $uploads = wp_get_upload_dir();
            if ( $uploads && false === $uploads['error'] ) {
                // Check that the upload base exists in the file location.
                if ( str_starts_with( $file, $uploads['basedir'] ) ) {
                    // Replace file location with url location.
                    $url = str_replace( $uploads['basedir'], $uploads['baseurl'], $file );
                } elseif ( str_contains( $file, 'wp-content/uploads' ) ) {
                    // Get the directory name relative to the basedir (back compat for pre-2.7 uploads).
                    $url = trailingslashit( $uploads['baseurl'] . '/' . _wp_get_attachment_relative_path( $file ) ) . wp_basename( $file );
                } else {
                    // It's a newly-uploaded file, therefore $file is relative to the basedir.
                    $url = $uploads['baseurl'] . "/$file";
                }
            }
        }

        /*
        * If any of the above options failed, Fallback on the GUID as used pre-2.7,
        * not recommended to rely upon this.
        */
        if ( ! $url ) {
            $url = get_the_guid( $post->ID );
        }

        // On SSL front end, URLs should be HTTPS.
        if ( is_ssl() && ! is_admin() && 'wp-login.php' !== $pagenow ) {
            $url = set_url_scheme( $url );
        }
        return $url;
    }
}

add_action('admin_head', 'next3_custom_width');

if( !function_exists('next3_custom_width') ){
    function next3_custom_width() {
    echo next3_print( '<style type="text/css">
table.media .column-title .media-icon img {
    width: 60px !important;
}
</style>');
    }
}


if( !function_exists('next3_offload_count_setup') ){
    function next3_offload_count_setup( $status_count = true ){
        
        $res = next3_get_option('next3_oflload_count', []);
        if( $status_count && !empty($res) ){
            return $res;
        }
        $settings_options = next3_options();
        $credentials = next3_credentials();
        $provider = ($credentials['settings']['provider']) ?? '';

        global $wpdb;
        
        //total count
        $total = 0;
        $total_offload = $total_clean = $total_wpoffload = $total_compress_done = $total_compress_done = $total_webp_done = $total_cloud = $total_local = $total_backup = 0;
        $source_type = 'media-library';

        if ( is_multisite() && NEXT3_MULTI_SITE ) {
            
            foreach (next3_get_current_blogs() as $blog_id) {
                // total
                $table_name = $wpdb->get_blog_prefix($blog_id) . "posts"; // Get posts table for the site
            
                $count = $wpdb->get_var("
                    SELECT COUNT(*) FROM $table_name 
                    WHERE post_type = 'attachment' 
                    AND post_status = 'inherit'
                ");
                $total += (int) $count;

                $table_name = $wpdb->get_blog_prefix($blog_id) . "postmeta"; // Get posts table for the site
                //total offload
                $count = $wpdb->get_var( "SELECT COUNT(meta.meta_id) FROM $table_name as meta WHERE meta.meta_key = '_next3_attached_file'");
                $total_offload += (int) $count;
                // total clean
                $count = $wpdb->get_var( "SELECT COUNT(meta.meta_id) FROM $table_name as meta WHERE meta.meta_key = '_next3_clean_status'");
                $total_clean += (int) $count;

                // total wp offload media
                if ( class_exists( '\WP_Offload_Media_Autoloader') ) {
                    $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) from ". next3_wp_offload_table($blog_id) ." where source_type = %s", $source_type ) );
                    $total_wpoffload += (int) $count;
                }
                // total compress
                $count = $wpdb->get_var( "SELECT COUNT(meta.meta_id) FROM $table_name as meta WHERE meta.meta_key = 'next3_optimizer_is_optimized'");
                $total_compress_done += (int) $count;

                // total webp
                $count = $wpdb->get_var( "SELECT COUNT(meta.meta_id) FROM $table_name as meta WHERE meta.meta_key = 'next3_optimizer_is_converted_to_webp'");
                $total_webp_done += (int) $count;
                // total backup
                $count = $wpdb->get_var( "SELECT COUNT(meta.meta_id) FROM $table_name as meta WHERE meta.meta_key = 'next3_optimizer_orginal_file'");
                $total_backup += (int) $count;
                // cloud count
                $count = $wpdb->get_var( "SELECT COUNT(meta.meta_id) FROM $table_name as meta WHERE meta.meta_key = '_next3_provider' AND meta.meta_value != '".$provider."'");
                $total_cloud += (int) $count;
            }
        }else{
            // total
            $total = $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->posts} 
                WHERE post_type = 'attachment' 
                AND post_status = 'inherit'
            ");
            // total offload
            $total_offload = $wpdb->get_var( "SELECT COUNT(meta.meta_id) FROM $wpdb->postmeta as meta WHERE meta.meta_key = '_next3_attached_file'");
            //total clean
            $total_clean = $wpdb->get_var( "SELECT COUNT(meta.meta_id) FROM $wpdb->postmeta as meta WHERE meta.meta_key = '_next3_clean_status'");
            // total wp offload media
            if ( class_exists( '\WP_Offload_Media_Autoloader' ) ) {
                $total_wpoffload = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) from ". next3_wp_offload_table() ." where source_type = %s", $source_type ) );
            }
            // total compress
            $total_compress_done = $wpdb->get_var( "SELECT COUNT(meta.meta_id) FROM $wpdb->postmeta as meta WHERE meta.meta_key = 'next3_optimizer_is_optimized'");
            // total webp
            $total_webp_done = $wpdb->get_var( "SELECT COUNT(meta.meta_id) FROM $wpdb->postmeta as meta WHERE meta.meta_key = 'next3_optimizer_is_converted_to_webp'");
            // total backup
            $total_backup = $wpdb->get_var( "SELECT COUNT(meta.meta_id) FROM $wpdb->postmeta as meta WHERE meta.meta_key = 'next3_optimizer_orginal_file'");

            // cloud count
            $count = $wpdb->get_var( "SELECT COUNT(meta.meta_id) FROM $wpdb->postmeta as meta WHERE meta.meta_key = '_next3_provider' AND meta.meta_value != '".$provider."'");
            $total_cloud += (int) $count;
        }
        
        $res['wpoffload'] = $total_wpoffload;
        $res['wpoffload_done'] = ($res['wpoffload_done']) ?? 0;
        
        $total_offload = ( $total < $total_offload) ? (int) $total : (int) $total_offload;

        $total_unoffload = $total_optimize = (int) ($total - $total_offload);

        $total_compress_done = ( $total < $total_compress_done) ? (int) $total : (int) $total_compress_done;

        
        $total_webp_done = ( $total < $total_webp_done) ? (int) $total : (int) $total_webp_done;
        
        // assets offload count
        $get_package = next3_license_package();
        if( in_array($get_package, ['developer', 'extended']) ){
            // css
            $exclude_css = ($settings_options['assets']['exclude_css']) ?? [];
            $all_css_files = next3_exclude_css_list('styles', false);

            $offload_css = next3_get_option('next3_offload_styles', []); 

            if( !empty($exclude_css) && is_array($exclude_css) ){
                foreach($exclude_css as $v){
                    if( array_key_exists($v, $all_css_files) ){
                        unset( $all_css_files[ $v ]);
                    }
                }
            }
            $total_css = count( $all_css_files );
            $total_css_done = count( $offload_css );
            $total_css_done = ($total_css_done >= $total_css) ? $total_css : $total_css_done;
            //js 
            $exclude_js = ($settings_options['assets']['exclude_js']) ?? [];
            $all_js_files = next3_exclude_css_list('scripts', false);

            $offload_js = next3_get_option('next3_offload_scripts', []);

            if( !empty($exclude_js) && is_array($exclude_js) ){
                foreach($exclude_js as $v){
                    if( array_key_exists($v, $all_js_files) ){
                        unset( $all_js_files[ $v ]);
                    }
                }
            }

            $total_js = count( $all_js_files );
            $total_js_done = count( $offload_js );
            $total_js_done = ($total_js_done >= $total_js) ? $total_js : $total_js_done;

            $res['total_styles'] = (int) $total_css;
            $res['total_styles_done'] = (int) $total_css_done;

            $res['total_scripts'] = (int) $total_js;
            $res['total_scripts_done'] = (int) $total_js_done;
        }
        //end assets offload count
        $total_local = next3_local_cloud_sync('total_count', 'path_new', $status_count);

        $res['total'] = (int) $total;
        $res['unoffload'] = (int) $total_unoffload;
        $res['offload'] = (int) $total_offload;
        $res['clean'] = (int) $total_clean;

        $res['total_optimize'] = (int) $total_optimize;
        $res['total_webp_done'] = (int) $total_webp_done;
        $res['total_compress_done'] = (int) $total_compress_done;

        $res['total_cloud'] = (int) $total_cloud;
        $res['total_local'] = (int) $total_local;
        $res['total_backup'] = (int) $total_backup;
        
        next3_update_option('next3_oflload_count', $res);

        return $res;
    }
}

if( !function_exists('next3_local_cloud_sync') ){
    function next3_local_cloud_sync($res_return = 'total_count', $cloud_status = 'path_new', $status_count = true ){
        global $wpdb;
        $res = 0;

        $credentials = next3_credentials();
        $provider = ($credentials['settings']['provider']) ?? '';
        $prodiver_data = ($credentials['settings'][$provider]) ?? [];
        $default_bucket = ($prodiver_data['default_bucket']) ?? '';
        $default_region = ($prodiver_data['default_region']) ?? 'eu-west-2';

        if( $provider == '' || $default_bucket == '' || $default_region == ''){
			return $res;
		}

        $obj = next3_core()->provider_ins->load($provider)->access();

        if( !$obj || !$obj->check_configration()){
            return $res;
        }
        
        $remove_path = '';
        $settings_options = next3_options();
        $enable_path = ($settings_options['storage']['enable_path']) ?? 'no';
        $upload_path = ($settings_options['storage']['upload_path']) ?? next3_core()->action_ins->get_upload_prefix();
        if( !empty($upload_path) && $enable_path == 'yes'){
            $remove_path = $upload_path;
        }
        
        $data_json = $obj->get_save_json_data($default_bucket);

        $res_array = next3_local_cloud_sync_by_name($data_json, $remove_path, [], $cloud_status);
        
        if( $res_return == 'cloud'){
            return $res_array;
        }

        if(empty($res_array)){
            return $res;
        }

        $site = next3_get_current_blogs();

        $args = [
            'post_status' => 'inherit',
            'orderby'     => 'DESC',
            'order'       => 'ID',
        ];
        $args['posts_per_page'] = -1;
        $args['post_type'] = 'attachment';

        $args['meta_query'] = array(
            'relation' => 'AND',
            array(
                'key'     => '_next3_attached_file',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => '_wp_attached_file',
                'value' => $res_array,
                'compare' => 'IN'
            )
        );

        $total_count = 0;
        $post = [];
        foreach($site as $blog_id){
            if( $blog_id != 0){
                switch_to_blog($blog_id);
            }

            $query = new \WP_Query( $args );
            $count = $query->found_posts;

            $total_count += (int) $count;
            
            /*if( $count > 0){
                foreach($query->posts as $v){
                    $post_id = ($v->ID) ?? 0;
                    if( $post_id == 0){
                        continue;
                    }
                    if( next3_get_post_meta($post_id, '_next3_attached_file') === false){
                        $post[] = $post_id . '__' . $blog_id;
                        $total_count++;
                    }
                }
            }*/
            if( $blog_id != 0){
                wp_reset_postdata();
                restore_current_blog();
            }
        }
        
        if( $res_return == 'post' ){
            return $post;
        } else if( $res_return == 'all' ){
            return [
                'total_count' => $total_count,
                'cloud' => $res_array,
                'post' => $post,
            ];
        }
        return $total_count;
    }
}

if( !function_exists('next3_local_cloud_sync_by_name')){
    function next3_local_cloud_sync_by_name( $data_json, $remove_path, $return = [], $status = 'full'){
        if( is_array($data_json) ){
            foreach($data_json as $k=>$v){
                if( isset($v['name']) && isset($v['url']) ){
                    $path = ($v['path']) ?? '';
                    $v['path_new'] = str_replace($remove_path, '', $path);
                    if($status == 'full'){
                        $return[] = $v;
                    } else if( $status == 'path_new' ){
                        $return[] = $v['path_new'];
                    } else if( $status == 'path' ){
                        $return[] = $v['path'];
                    } else if( $status == 'url' ){
                        $return[] = $v['url'];
                    } else if( $status == 'name' ){
                        $return[] = $v['name'];
                    }
                    
                } else {
                   $return = next3_local_cloud_sync_by_name($v, $remove_path, $return, $status);
                }
            }
        }
        return $return;
    }
}

if( !function_exists('next3_get_permissions')){
    function next3_get_permissions($file) {
        $perms = fileperms($file);

        $info = '';

        // File Type
        if (($perms & 0xC000) == 0xC000) {
            $info = 's'; // Socket
        } elseif (($perms & 0xA000) == 0xA000) {
            $info = 'l'; // Symbolic Link
        } elseif (($perms & 0x8000) == 0x8000) {
            $info = '-'; // Regular
        } elseif (($perms & 0x6000) == 0x6000) {
            $info = 'b'; // Block special
        } elseif (($perms & 0x4000) == 0x4000) {
            $info = 'd'; // Directory
        } elseif (($perms & 0x2000) == 0x2000) {
            $info = 'c'; // Character special
        } elseif (($perms & 0x1000) == 0x1000) {
            $info = 'p'; // FIFO pipe
        } else {
            $info = 'u'; // Unknown
        }

        // Owner
        $info .= (($perms & 0x0100) ? 'r' : '-');
        $info .= (($perms & 0x0080) ? 'w' : '-');
        $info .= (($perms & 0x0040) ?
                    (($perms & 0x0800) ? 's' : 'x') :
                    (($perms & 0x0800) ? 'S' : '-'));

        // Group
        $info .= (($perms & 0x0020) ? 'r' : '-');
        $info .= (($perms & 0x0010) ? 'w' : '-');
        $info .= (($perms & 0x0008) ?
                    (($perms & 0x0400) ? 's' : 'x') :
                    (($perms & 0x0400) ? 'S' : '-'));

        // World
        $info .= (($perms & 0x0004) ? 'r' : '-');
        $info .= (($perms & 0x0002) ? 'w' : '-');
        $info .= (($perms & 0x0001) ?
                    (($perms & 0x0200) ? 't' : 'x') :
                    (($perms & 0x0200) ? 'T' : '-'));

        return $info;
    }
}
