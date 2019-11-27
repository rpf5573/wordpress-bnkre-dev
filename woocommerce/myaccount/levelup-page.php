<?php
/**
 * Level up form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/levelup.php.
 *
 */

defined( 'ABSPATH' ) || exit; 
global $wp;
global $wpdb; ?>

<div class="woocommerce-levelup"> <?php
	// 이 유저가 이미 요청을 보냈다면 또 보내지 못하도록 해야지~
	$user_id = get_current_user_id();
	$ml_members = ML_Members::getInstance();
	$levelup_request_info = $ml_members->get_levelup_request_info( $user_id );

	// 방금 막 보낸거라면(에러가 있을 수도 있으니까 message를 넘겨준거다)
	if ( isset($_GET['message']) && isset($_GET['isSuccess']) ) {
		$class_name = 'message ';
		$class_name .= ( $_GET['isSuccess'] == '1' ? 'waiting' : 'error' );
		$message = $_GET['message'];
		echo "<div class='{$class_name}'>{$message}</div>";
	}
	// 방금 막보낸건 아니고, 다른 페이지 갔다가 다시 돌아왔을때
	// 이미 요청을 보낸적이 있다면
	else if ( ! is_null( $levelup_request_info ) ) {
		$class_name = 'message ';
		$message = '';
		// 방금 막 보낸거여서 메세지가 있다면
		if ( $levelup_request_info->status == 0 ) {
			$class_name .= 'waiting';
			$message = '승인 대기중입니다';
		} else if ( $levelup_request_info->status == 1 ) {
			$class_name .= 'allowed';
			$message = '이미 등업되었습니다';
		} else {
			$class_name .= 'rejected';
			$message = '승인 거절당했습니다';
		}
		echo "<div class='{$class_name}'>{$message}</div>";
	}
	// 에러메세지가 있다 한들!! 폼은 보여줘야할거 아니여~ 또 신청할 수도 있잖여, 안그래?
	get_template_part( 'woocommerce/myaccount/levelup-request', 'tabs' );
	?>
</div>