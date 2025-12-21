<?php
namespace Next3Offload\Utilities;

if ( ! defined( 'ABSPATH' ) ) die( 'Forbidden' );

class N3aws_Dashboard{

	private static $instance;

	public function _init() {
		// Register Dashboard Widgets.
		add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widgets' ] );
		add_action( 'admin_enqueue_scripts', [ $this , '_admin_scripts'] );
	}

	public function register_dashboard_widgets() {
		wp_add_dashboard_widget( 'th-dashboard-overview', esc_html__( 'ThemeDev Overview', 'next-campaign' ), [ $this, 'nx_dashboard_overview_widget' ] );

		// Move our widget to top.
		global $wp_meta_boxes;

		$dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
		$ours = [
			'th-dashboard-overview' => $dashboard['th-dashboard-overview'],
		];

		$wp_meta_boxes['dashboard']['normal']['core'] = array_merge( $ours, $dashboard ); 
	}

	public function nx_dashboard_overview_widget() {

		$checkblog = $get_blogs = get_transient('__set_news_th', '');
        if (  false === $get_blogs || empty( $get_blogs ) ) {
			$params['page'] = 1;
            $params['per_page'] = 4;
            $params['orderby'] = 'modified';
            $get_blogs = $this->get_posts( $params );
		}
		if(empty($get_blogs) ){
			return;
		}
        $set_blogs = [];
		?>
		<div class="th-dashboard-widget">
			
			<?php if ( ! empty( $get_blogs ) ) : ?>
				<div class="th-overview__feed">
					<h3 class="th-heading th-divider_bottom"><?php echo esc_html__( 'News & Updates','next-campaign' ); ?></h3>
					<ul class="th-overview__posts">
						<?php 

						foreach ( $get_blogs as $v ) : 
							$id = ($v->id) ?? ($v['id']) ?? '';
                            $title = ($v->title->rendered) ?? ($v['title']) ?? '';
                            $excerpt = ($v->excerpt->rendered) ?? ($v['excerpt']) ?? '';
                            $slug = ($v->link) ?? ($v['slug']) ?? '';
                            $date = ($v->date) ?? ($v['date']) ?? '';

                            $slug = str_replace('https://support.themedev.net/', 'https://themedev.net/blog/', $slug);
                            $set_blogs[] = [
                                'id' => $id,
                                'title' => $title,
                                'excerpt' => $excerpt,
                                'slug' => $slug,
                                'date' => $date,
                            ];
							?>
							<li class="th-overview__post">
								<a href="<?php echo esc_url($slug ); ?>?utm_source=wpdash&utm_medium=news-feed&utm_id=article" class="th-overview__post-link" target="_blank">
									<?php echo esc_html( $title ); ?>
								</a>
								<p class="th-overview__post-description"><?php echo esc_html( substr(strip_tags($excerpt), 0, 200) ) . '...'; ?></p>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
			<div class="th-overview__footer th-divider_top">
				<ul>
					<?php foreach ( $this->get_dashboard_overview_widget_footer_actions() as $action_id => $action ) : ?>
						<li class="th-overview__<?php echo esc_attr( $action_id ); ?>"><a href="<?php echo esc_attr( $action['link'] ); ?>" target="_blank"><?php echo esc_html( $action['title'] ); ?> <span class="screen-reader-text"><?php echo esc_html__( '(opens in a new window)','next-campaign' ); ?></span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<?php

        if (  false === $checkblog || empty( $checkblog ) ) {
            set_transient( '__set_news_th', $set_blogs , 86400 );
        }
	}
	private function get_dashboard_overview_widget_footer_actions() {
		$base_actions = [
			'blog' => [
				'title' => esc_html__( 'Blog','nextwoo' ),
				'link' => 'https://themedev.net/blog/?utm_source=wpdash&utm_medium=news-feed&utm_id=blog',
			],
			'help' => [
				'title' => esc_html__( 'Bundle','nextwoo' ),
				'link' => 'https://themedev.net/pricing/?utm_source=wpdash&utm_medium=news-feed&utm_id=bundle',
			],
		];

		$additions_actions = [
			'go-pro' => [
				'title' => esc_html__( 'Latest Offers','nextwoo' ),
				'link' => 'https://themedev.net/coupon/?utm_source=wpdash&utm_medium=news-feed&utm_id=coupon',
			],
		];

		$additions_actions = apply_filters( 'themedev/admin/dashboard_overview_widget/footer_actions', $additions_actions );

		$actions = $base_actions + $additions_actions;

		return $actions;
	}

	public function get_posts( $arg ){
        $header   = array();
        $header[] = 'Content-length: 0';
        $header[] = 'Content-type: application/json; charset=utf-8';

        $id = ($arg['id']) ?? 0;
        $arg['page'] = ($arg['page']) ?? 1;
        $arg['per_page'] = ($arg['per_page']) ?? 6;
        
        if( $id != 0){
            $verify_url = "https://support.themedev.net/wp-json/wp/v2/posts/" . $id;
        } else {
            $verify_url = "https://support.themedev.net/wp-json/wp/v2/posts?".http_build_query($arg, '&');
        }
        
        $ch_verify = curl_init();
        curl_setopt( $ch_verify, CURLOPT_URL, $verify_url);
        curl_setopt( $ch_verify, CURLOPT_HTTPHEADER, $header );
        curl_setopt( $ch_verify, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch_verify, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch_verify, CURLOPT_CONNECTTIMEOUT, 5 );
        
        $result = curl_exec( $ch_verify );
        curl_close( $ch_verify );

        $result = ($result != "") ? json_decode($result) : false;
        return $result;
    }

	public function _admin_scripts(){
        // ads css
		wp_enqueue_style( 'themedev_ads', 'https://api.themedev.net/ads/assets/ads.css', false, time() );
        wp_enqueue_script( 'themedev_ads', 'https://api.themedev.net/ads/assets/ads.js', ['jquery'], time(), true ); 
    }
	public static function instance(){
		if (!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
	}
}
