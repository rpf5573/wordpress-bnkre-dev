<?php
/**
 * In listing creation flow, this template shows above the creation form.
 *
 * @since 1.6.3
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

// 지금 로그인이 문제가 아니라, 직원 아래라면?? 아니다 차라리 capacity를 하나 만들자.

$error_code = 401; // 로그인 필요
if ( is_user_logged_in() ) {
	$user_id = get_current_user_id();
	if ( user_can( $user_id, 'can_add_listing' ) ) {
		return;
	} else {
		$error_code = 402;
	}
}
?>

<div class="form-section-wrapper active" id="form-section-auth">
	<div class="element form-section">
		<div class="pf-body"> <?php
			if ( $error_code == 401 ) { ?>
				<fieldset class="fieldset-login_required">
					<p>새로운 리스팅을 등록하기 위해서는 로그인이 필요합니다</p>
					<p>
						<a href="#" data-toggle="modal" data-target="#sign-in-modal" class="buttons button-5">
							<i class="mi perm_identity"></i>
							<?php _e( 'Sign in', 'my-listing' ) ?>
						</a>
					</p>
				</fieldset>
				<?php
			}
			else if ( $error_code = 402 ) {
				$link = wc_get_account_endpoint_url( ML_Members::$levelup_endpoint ); ?>
				<fieldset class="fieldset-levelup_required">
					<p>리스팅을 등록할 권한이 없습니다</p>
					<p>
						<a href="<?php echo $link; ?>" class="buttons button-5">
							등업하기
						</a>
					</p>
				</fieldset> <?php
			}
		?>
		</div>
	</div>
</div>
