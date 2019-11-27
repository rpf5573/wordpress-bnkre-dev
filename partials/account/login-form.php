<?php if (!class_exists('WooCommerce')) return; ?>

<div class="sign-in-box element gogosing">
	<div class="title-style-1">
		<i class="material-icons user-area-icon">person</i>
		<h5><?php _e( 'Sign in', 'my-listing' ) ?></h5>
	</div>

	<h4>회원가입</h4>

	<?php echo do_shortcode( '[theme-my-login default_action="login" show_links=0]', false ); ?>

	<?php c27()->get_partial( 'spinner', [
		'color' => '#777',
		'classes' => 'center-vh',
		'size' => 24,
		'width' => 2.5,
	] ) ?>
</div>