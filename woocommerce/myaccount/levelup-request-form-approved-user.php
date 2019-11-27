<form id="levelup-request-form-approved-user" class="light-forms" method="POST" action="<?php echo admin_url( 'admin-post.php' ); ?>" novalidate autocomplete> <?php
  wp_nonce_field('levelup_request_nonce_check', 'levelup_request_nonce_field'); ?>
  <input type="hidden" name="redirect_url" value="<?php echo home_url( $wp->request ); ?>">
  <input type="hidden" name="action" value="process_levelup_form">
  <input type="hidden" name="new-role" value="approved_user">
  <div class="form-field field-required">
    <div class="label-container">
      <label for="au--user-name">성명</label>
      <span class="error-message">*성함을 입력해주시기 바랍니다</span>
    </div>
    <input id="au--user-name" type="text" name="user-name" required>
  </div>
  <div class="form-field field-required">
    <div class="label-container">
      <label for="au--register-purpose">가입목적</label>
      <span class="error-message">*가입목적을 선택해주시기 바랍니다</span>
    </div>
    <select name="register-purpose" id="au--register-purpose" required>
      <option selected disabled hidden value=""> -- 선택 -- </option>
      <option value="a">가입목적 A</option>
      <option value="b">가입목적 B</option>
      <option value="c">가입목적 C</option>
      <option value="d">가입목적 D</option>
      <option value="e">가입목적 E</option>
    </select>
  </div>
  <div class="form-field">
    <div class="label-container">
      <label>SMS인증</label>
    </div>
    <button class="sms_auth_btn buttons button-3" type="button">SMS인증하는 버튼</button>
  </div>
  <div class="form-field">
    <div class="label-container">
      <label for="au--personal-info-agree">개인정보제공 활용 동의</label>
    </div>
    <?php wc_get_template_part( 'myaccount/personal-info', 'agree' ); ?>
    <div class="tb-space-10 d-flex middle">
      <span>동의하십니까?</span>
      <div class="checkbox md-checkbox ml-10">
        <input type="checkbox" name="personal-info-agree" id="au--personal-info-agree" required>
        <label for="au--personal-info-agree"></label>
      </div>
      <span class="error-message">*개인정보제공 활용에 동의해주시기 바랍니다</span>
    </div>
  </div>
  <div class="form-field">
    <div class="label-container">
      <label for="au--location-info-agree">위치정보제공 동의</label>
    </div>
    <?php wc_get_template_part( 'myaccount/location-info', 'agree' ); ?>
    <div class="tb-space-10 d-flex middle">
      <span>동의하십니까?</span>
      <div class="checkbox md-checkbox ml-10">
        <input type="checkbox" name="location-info-agree" id="au--location-info-agree" required>
        <label for="au--location-info-agree"></label>
      </div>
      <span class="error-message">*위치정보제공에 동의해주시기 바랍니다</span>
    </div>
  </div>
  <div class="submit-btn-container clearfix">
    <button class="buttons button-2" type="submit">등업신청</button>
  </div>
</form>