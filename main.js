(function($){
  var pf_element = jQuery('.foldable > .element');
  if ( pf_element.length > 0 ) {
    var pf_head = pf_element.find('.pf-head');
    pf_head.append('<span class="mi expand_more"></span>');
    pf_head.on('click', function(){
      var $this = $(this);
      var parent = $this.closest('.element');
      if ( parent.hasClass('open') ) {
        parent.removeClass('open');
      } else {
        parent.addClass('open');
      }
    });
  }
  // pf_head
})(jQuery);

// request levelup form
(function($){

  function Validator(formId, formFieldClass, errorMessageDivClass) {
    this.form = $(formId);
    this.submitBtn = this.form.find('button[type=submit]');
    this.targets = [];
    this.showErrorMessage = function(el){
      var formField = el.closest(formFieldClass);
      var errorMessageBox = $(formField).find(errorMessageDivClass);
      errorMessageBox.css('display', 'inline-block');
    }
    this.hideErrorMessage = function(el){
      var formField = el.closest(formFieldClass);
      var errorMessageBox = $(formField).find(errorMessageDivClass);
      errorMessageBox.css('display', 'none');
    }
    this.validateSelect = function(el){
      var val = el.val();
      if ( ! val ) {
        this.showErrorMessage(el);
        return true;
      } else {
        this.hideErrorMessage(el);
        return false;
      }
    }
    this.validateInput = function(el){
      var val = el.val();
      // space바 제거하고 체크
      val = val.replace(/\s/gi, "");
      if (! val) {
        this.showErrorMessage(el);
        return true;
      }
      this.hideErrorMessage(el);
      return false;
    }
    this.validateCheckbox = function(el){
      if ( ! el.is(':checked') ) {
        this.showErrorMessage(el);
        return true;
      } else {
        this.hideErrorMessage(el);
        return false;
      }
    }
    this.addTargetFormField = function(selector){
      if ( ! selector ) {
        console.error("Target Selector is Empty");
        return false;
      }
      this.targets.push($(this.form.find(selector)));
    }
    this.getValues = function() {
      var data = {};
      this.targets.forEach(el => {
        data[el.prop('name')] = el.val();
      });
      data.action = $(this.form.find('input[name=action]')).val();
      data.redirect_url = $(this.form.find('input[name=redirect_url]')).val();
      data.levelup_request_nonce = $(this.form.find('input[name=levelup_request_nonce_field]')).val();
      return data;
    }
    this.handleSubmit = function(e) {
      $(".woocommerce-levelup").LoadingOverlay("show");
      e.preventDefault();
      this.submitBtn.attr('disabled', true);
      var hasError = false;
      this.targets.forEach(el => {
        var type = el.prop('type');
        if ( type == 'select-one' ) {
          hasError = this.validateSelect(el);
        }
        else if ( type == 'checkbox' ) {
          hasError = this.validateCheckbox(el);
        }
        else if ( type == 'text' ) {
          hasError = this.validateInput(el);
        }
      });
      if ( !hasError ) {
        var method = this.form.attr("method");
        var action = this.form.attr("action");
        var data = this.getValues();
        console.log('data', data);
        $.ajax({
          type: method,
          url: action,
          data: data,
          success: function (response) {
            $(".woocommerce-levelup").LoadingOverlay("hide", true);
            setTimeout(function(){
              if ( ! response.success && response.message ) {
                alert(response.message);
              }
              window.location.href = response.redirect_url;
            }, 300);
          }
        });
      } else {
        $(".woocommerce-levelup").LoadingOverlay("hide", true);
        this.submitBtn.attr('disabled', false);
      }
    }
    this.handleSubmit = this.handleSubmit.bind(this); // 이렇게 해줘야 handleSubmit 안에있는 this가 이 Validator를 가리키게된다
    this.form.on("submit", this.handleSubmit);
  }

  // approved user
  (function($){
    // for approved user
    var select = $('#levelup-request-form-approved-user select');
    if ( select.length > 0 ) {
      select.select2();
    }
    var formValidator = new Validator("#levelup-request-form-approved-user", '.form-field', '.error-message');
    formValidator.addTargetFormField('input[name=new-role]');
    formValidator.addTargetFormField('input[name=user-name]');
    formValidator.addTargetFormField('select[name=register-purpose]');
    formValidator.addTargetFormField('input[name=sms_auth_imp_uid]');
    formValidator.addTargetFormField('input[name=personal-info-agree]');
    formValidator.addTargetFormField('input[name=location-info-agree]');
  })(jQuery);

  // partner
  (function($){
    // for partner
    var select = $('#levelup-request-form-partner select');
    if ( select.length > 0 ) {
      select.select2();
    }
    var formValidator = new Validator("#levelup-request-form-partner", '.form-field', '.error-message');
    formValidator.addTargetFormField('input[name=new-role]');
    formValidator.addTargetFormField('input[name=user-name]');
    formValidator.addTargetFormField('select[name=register-purpose]');
    formValidator.addTargetFormField('input[name=sms_auth_imp_uid]');
    formValidator.addTargetFormField('input[name=personal-info-agree]');
    formValidator.addTargetFormField('input[name=location-info-agree]');
  })(jQuery);

  // vip
  (function($){
    // for partner
    var select = $('#levelup-request-form-vip select');
    if ( select.length > 0 ) {
      select.select2();
    }
    var formValidator = new Validator("#levelup-request-form-vip", '.form-field', '.error-message');
    formValidator.addTargetFormField('input[name=new-role]');
    formValidator.addTargetFormField('input[name=user-name]');
    formValidator.addTargetFormField('select[name=register-purpose]');
    formValidator.addTargetFormField('input[name=sms_auth_imp_uid]');
    formValidator.addTargetFormField('input[name=personal-info-agree]');
    formValidator.addTargetFormField('input[name=location-info-agree]');
  })(jQuery);

})(jQuery);

(function($){
  jQuery('.remote-tab').on('click', function() {
    var $this = $(this);
    if ( $this.hasClass('first') ) {
      $(".nav-tabs li:nth-child(1) .tab-switch").trigger("click");
    }
    else if ( $this.hasClass('second') ) {
      $(".nav-tabs li:nth-child(2) .tab-switch").trigger("click");
    }
    else if ( $this.hasClass('third') ) {
      $(".nav-tabs li:nth-child(3) .tab-switch").trigger("click");
    }
  });
})(jQuery);