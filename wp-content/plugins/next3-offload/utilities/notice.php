<?php
namespace Next3Offload\Utilities;

if ( ! defined( 'ABSPATH' ) ) die( 'Forbidden' );

class N3aws_Notice{

	private static $instance;

	public function _init() {
		add_action(	'admin_footer', [ $this, 'enqueue_scripts' ], 9999);
		add_action( 'wp_ajax_next3aws-notices', [ $this, 'dismiss' ] );
	}

	public function dismiss() {

		$id   = ( isset( $_POST['id'] ) ) ? sanitize_key($_POST['id']) : '';
		$time = ( isset( $_POST['time'] ) ) ? sanitize_text_field($_POST['time']) : '';
		$meta = ( isset( $_POST['meta'] ) ) ? sanitize_meta($_POST['meta']) : '';

		// Valid inputs?
		if ( ! empty( $id ) ) {

			if ( 'user' === $meta ) {
				update_user_meta( get_current_user_id(), $id, true );
			} else {
				set_transient( $id, true, $time );
			}

			wp_send_json_success();
		}

		wp_send_json_error();
	}

	public function enqueue_scripts() {
		echo next3_print("
			<script>
			jQuery(document).ready(function ($) {
				$( '.next3aws .is-dismissible' ).on( 'click', '.notice-dismiss', function() {
					_this 		= $( this ).parents( '.next3aws' );
					var id 	= 	_this.attr( 'id' ) || '';
					var time 	= _this.attr( 'dismissible-time' ) || '';
					var meta 	= _this.attr( 'dismissible-meta' ) || '';
			
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action 	: 'next3aws-notices',
							id 		: id,
							meta 	: meta,
							time 	: time,
						},
					});
			
				});
			
			});
			</script>
		");
	}

	public static function push($notice) {

		$defaults = [
			'id'               => '',
			'type'             => 'info',
			'show_if'          => true,
			'message'          => '',
			'class'            => 'next3aws',
			'dismissible'      => false,
			'btn'			   => [],
			'dismissible-meta' => 'user',
			'dismissible-time' => WEEK_IN_SECONDS,
			'data'             => '',
		];

		$notice = wp_parse_args( $notice, $defaults );

		$classes = [ 'nextpayment-notice', 'notice' ];

		$classes[] = $notice['class'];
		if ( isset( $notice['type'] ) ) {
			$classes[] = 'notice-' . $notice['type'];
		}

		if ( true === $notice['dismissible'] ) {
			$classes[] = 'is-dismissible';

			$notice['data'] = ' dismissible-time=' . esc_attr( $notice['dismissible-time'] ) . ' ';
		}

		$notice_id    = 'next3aws-id-' . $notice['id'];
		$notice['id'] = $notice_id;
		if ( ! isset( $notice['id'] ) ) {
			$notice_id    = 'next3aws-id-' . $notice['id'];
			$notice['id'] = $notice_id;
		} else {
			$notice_id = $notice['id'];
		}

		$notice['classes'] = implode( ' ', $classes );

		$notice['data'] .= ' dismissible-meta=' . esc_attr( $notice['dismissible-meta'] ) . ' ';
		if ( 'user' === $notice['dismissible-meta'] ) {
			$expired = get_user_meta( get_current_user_id(), $notice_id, true );
		} elseif ( 'transient' === $notice['dismissible-meta'] ) {
			$expired = get_transient( $notice_id );
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
		?>
		<div id="<?php echo esc_attr( $notice['id'] ); ?>" class="<?php echo esc_attr( $notice['classes'] ); ?>" <?php echo $notice['data']; ?> >
			<p>
				<?php echo Help::_kses($notice['message']); ?>
			</p>

			<?php if(!empty($notice['btn'])):?>
			<p>
				<a href="<?php echo esc_url($notice['btn']['url']); ?>" class="button-primary"><?php echo esc_html($notice['btn']['label']); ?></a>
			</p>
			<?php endif; ?>
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
