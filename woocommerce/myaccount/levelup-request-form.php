<form id="levelup-request-form" class="light-forms" method="POST" action="<?php echo admin_url( 'admin-post.php' ); ?>" novalidate autocomplete> <?php
  wp_nonce_field('levelup_request_nonce_check', 'levelup_request_nonce_field'); ?>
  <input type="hidden" name="redirect_url" value="<?php echo home_url( $wp->request ); ?>">
  <input type="hidden" name="action" value="process_levelup_form">
  <div class="form-field">
    <div class="label-container">
      <label for="new-role">등업유형</label>
      <span class="error-message">*등업유형을 선택해주시기 바랍니다</span>
    </div>
    <select name="new-role" id="new-role" required>
      <option selected disabled hidden value=""> -- 선택 -- </option>
      <option value="approved_user">일반등업</option>
      <option value="partner">파트너</option>
      <option value="vip">VIP</option>
    </select>
  </div>
  <div class="form-field">
    <div class="label-container">
      <label for="register-purpose">가입목적</label>
      <span class="error-message">*가입목적을 선택해주시기 바랍니다</span>
    </div>
    <select name="register-purpose" id="register-purpose" required>
    </select>
  </div>
  <div class="form-field">
    <div class="label-container">
      <label>SMS인증</label>
    </div>
    <button class="sms_auth_btn buttons button-3" type="button">SMS인증</button>
    <input type="text" style="display:none;" name="sms_auth_imp_uid">
    <span class="error-message">*SMS인증을 해주시기 바랍니다</span>
  </div>
  <div class="form-field">
    <div class="label-container">
      <label for="personal-info-agree">개인정보제공 활용 동의</label>
    </div>
    <?php wc_get_template_part( 'myaccount/personal-info', 'agree' ); ?>
    <div class="tb-space-10 d-flex middle">
      <span>동의하십니까?</span>
      <div class="checkbox md-checkbox ml-10">
        <input type="checkbox" name="personal-info-agree" id="personal-info-agree" required>
        <label for="personal-info-agree"></label>
      </div>
      <span class="error-message">*개인정보제공 활용에 동의해주시기 바랍니다</span>
    </div>
  </div>
  <div class="form-field">
    <div class="label-container">
      <label for="service-agree">서비스 이용약관 동의</label>
    </div>
    <?php wc_get_template_part( 'myaccount/service', 'agree' ); ?>
    <div class="tb-space-10 d-flex middle">
      <span>동의하십니까?</span>
      <div class="checkbox md-checkbox ml-10">
        <input type="checkbox" name="service-agree" id="service-agree" required>
        <label for="service-agree"></label>
      </div>
      <span class="error-message">*서비스 이용약관에 동의해주시기 바랍니다</span>
    </div>
  </div>
  <div class="submit-btn-container clearfix">
    <button class="buttons button-2" type="submit">등업신청</button>
  </div>
</form>