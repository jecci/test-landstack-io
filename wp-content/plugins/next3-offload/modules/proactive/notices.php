<?php 
namespace Next3Offload\Modules\Proactive;
defined( 'ABSPATH' ) || exit;

class Notices{
    private static $instance;

    public function _init() {        
        add_action(	'admin_footer', [ $this, '__enqueue_scripts' ], 9999);
        add_action( 'wp_ajax_themedevnext3-ajax', [ $this, '__dismiss' ] );

		add_action( 'admin_notices', [ $this, '_generate_ads'], 100);
		add_action( 'network_admin_notices', [ $this, '_generate_ads'], 100);
		
    }

    public function _generate_ads( ){
        $screen = get_current_screen();
		$key = 'next3';
		
		$transient   = get_transient( '__set_ads_next', '');
		$transient_time   = get_transient( 'timeout___set_ads_next', 1 );
		
		if( !empty($transient) && $transient != $key ){
			return;
		}
		
		if(  false === $transient || empty( $transient ) ){
			set_transient( '__set_ads_next', $key , 86400 );
		}

		$ads = get_transient('__ads_center__', '');
		
        if (  false === $ads || empty( $ads ) ) {
			$ads = $this->_get_ads();
			if(!empty($ads)){
				set_transient( '__ads_center__', $ads , 86400 );
			}
		}
		
		if(empty($ads) ){
			return;
		}
		
        foreach($ads as $k=>$v):
			if( !empty($k) ){
			 $data['id'] = isset($v->id) ? $v->id : '';
			 $data['class'] = isset($v->class) ? $v->class : '';
			 $data['styles'] = isset($v->styles) ? $v->styles : '';
			 $data['name'] = isset($v->name) ? $v->name : '';
			 $data['title'] = isset($v->title) ? $v->title : '';
			 $data['des'] = isset($v->des) ? $v->des : '';
			 $data['url'] = isset($v->url) ? $v->url : '';
			 $data['logo'] = isset($v->logo) ? $v->logo : '';
			 $data['img_url'] = isset($v->img_url) ? $v->img_url : '';
 
			 $data['start_date'] = isset($v->start_date) ? $v->start_date : '';
			 $data['end_date'] = isset($v->end_date) ? $v->end_date : '';
			 $support = isset($v->support) ? $v->support : ['intro_pages'];
			 
			 if( !empty($data['start_date']) ){
				 if( $data['start_date'] >= time() ){
					 continue;
				 }
			 }
 
			 if( !empty($data['end_date'])){
				 if( $data['end_date'] <= time()){
					 continue;
				 }
			 }
			 
			 if( in_array($screen->id , $support) || $k == 'intro_pages' || $k == $key){
				 self::push(
					 [
						 'id'          => 'themedev-'.$data['id'],
						 'type'        => 'info',
						 'dismissible' => true,
						 'return'	  => $data,
						 'message'     => $data['des'],
					 ]
				 );
			 }
  
			}
		 endforeach;
    }


    public function _get_ads(){
		$current_user = wp_get_current_user();

		$parmas['plugin'] = 'next3';
		//$parmas['email'] = isset($current_user->user_email) ? $current_user->user_email : get_option( 'admin_email' );
		$parmas['name'] = isset($current_user->display_name ) ? $current_user->display_name  : get_option( 'blogname' );
		$parmas['website'] = home_url();
		
        $url = $this->get_edd_api().'/ads?'. http_build_query($parmas, '&');
        $args = array(
            'timeout'     => 60,
            'redirection' => 3,
            'httpversion' => '1.0',
            'blocking'    => true,
            'sslverify'   => true,
        ); 
		$res = wp_remote_get( $url, $args );
		
		if ( is_wp_error( $res ) ) {
			return;
		}
		if(!isset($res['body'])){
			return;
		}
        return (object) json_decode(
            (string) $res['body']
        ); 
    }

    public function get_edd_api(){
        return N3aws_Init::instance()->get_edd_api();
    }

    /**
	 * Dismiss Notice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __dismiss() {
		
		$id   = ( isset( $_POST['id'] ) ) ? sanitize_key($_POST['id']) : '';
		$time = ( isset( $_POST['time'] ) ) ? sanitize_text_field($_POST['time']) : '';
		$meta = ( isset( $_POST['meta'] ) ) ? sanitize_text_field($_POST['meta']) : '';
		
		$key_ = $id . '_'.get_current_user_id();

		if ( ! empty( $id ) ) {
			if ( 'user' === $meta ) {
				update_user_meta( get_current_user_id(), $id, true );
			} else {
				set_transient( $key_, true, $time );
			}
			wp_send_json_success();
		}
		wp_send_json_error();
	}

	public function __enqueue_scripts() {
		echo "
			<script>
			jQuery(document).ready(function ($) {
				$( '.themedevpro-notice.is-dismissible' ).on( 'click', '.notice-dismiss', function() {
					
					_this 		= $( this ).parents( '.themedev-next3-active' );
					var id 	= 	_this.attr( 'id' ) || '';
					var time 	= _this.attr( 'dismissible-time' ) || '';
					var meta 	= _this.attr( 'dismissible-meta' ) || '';
					var urld 	= _this.attr( 'dismissible-url' ) || '';
					
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action 	: 'themedevnext3-ajax',
							id 		: id,
							meta 	: meta,
							time 	: time
						},
						success: function (response) {
							window.open(urld);
						}
					});
			
				});
			
			});
			</script>
		";
	}

	/**
	 * Show Notices
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function push($notice) {

		$defaults = [
			'id'               => 'themedevpro',
			'type'             => 'info',
			'show_if'          => true,
			'message'          => '',
			'class'            => 'themedev-next3-active',
			'dismissible'      => false,
			'dismissible-meta' => 'user',
			'dismissible-time' => DAY_IN_SECONDS,
			'data'             => '',
		];

		$notice = wp_parse_args( $notice, $defaults );

		$classes = [ 'themedevpro-notice', 'notice', $notice['class']];

		$classes[] = ($notice['return']['class']) ?? $notice['class'];
		$img_url = ($notice['return']['img_url']) ?? '';
		if( !empty($img_url) ){
			$classes[] = 'is_image';
		}
		if ( isset( $notice['type'] ) ) {
			$classes[] = 'notice-' . $notice['type'];
		}

		if ( true === $notice['dismissible'] ) {
			$classes[] = 'is-dismissible';

			$notice['data'] = ' dismissible-time=' . esc_attr( $notice['dismissible-time'] ) . ' ';
		}

		$notice['data'] .= ' dismissible-url=' . esc_attr( $notice['return']['url'] ) . '';

		$notice_id    = $notice['id'];
		
		$notice['classes'] = implode( ' ', $classes );

		$notice['data'] .= ' dismissible-meta=' . esc_attr( $notice['dismissible-meta'] ) . ' ';
		if ( 'user' === $notice['dismissible-meta'] ) {
			$expired = get_user_meta( get_current_user_id(), $notice_id, true );
		} elseif ( 'transient' === $notice['dismissible-meta'] ) {
			$key_ = $notice_id.'_'.get_current_user_id();
			$expired = get_transient( $key_ );
		}

		if ( isset( $notice['show_if'] ) ) {
			if ( true === $notice['show_if'] ) {
				if ( false === $expired || empty( $expired ) ) {
					self::markup($notice);
				}
			}
		} else {
			self::markup($notice);
		}
	}

	public static function markup( $notice = [] ) {
        $data = isset($notice['return']) ? $notice['return'] : [];
		$img_url = isset($data['img_url']) ? $data['img_url'] : '';
		$logo = isset($data['logo']) ? $data['logo'] : '';
		$url = isset($data['url']) ? $data['url'] : '';
		$title = isset($data['title']) ? $data['title'] : '';
		$name = isset($data['name']) ? $data['name'] : '';
		$desc = isset($data['des']) ? $data['des'] : '';
		$styles = isset($data['styles']) ? $data['styles'] : '';

		?>
	
		<div id="<?php echo esc_attr( $notice['id'] ); ?>" class="themedevnotices <?php echo esc_attr( $notice['classes'] ); ?>" <?php echo $notice['data']; ?> style="background-image: url(<?php echo $img_url;?>); <?php echo $styles;?>" >
			<?php if( !empty($logo) ){?>	
			<div class="left-side">
				<img src="<?php echo esc_url($logo);?>" alt="<?php echo esc_html($name); ?>">
			</div>
			<?php }?>
			<div class="right-side">
				<h2 class="nxheading-notices"> <a href="<?php echo esc_url($url);?>" target="_blank"><?php echo $title; ?></a></h2>
				<p class="nxdetails-notices"><?php echo $desc; ?></p>
			</div>
			
		</div>
		<?php
	}

    public static function instance(){
		if (!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
	}
}