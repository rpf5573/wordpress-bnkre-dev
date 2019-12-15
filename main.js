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

  $.fn.updateSelect2Items = function(items, config){
    var that = this;
    that.html(''); // 먼저 싹 비우고
    that.select2("destroy");
    for(var k in items){
        var data = items[k];
        that.append("<option value='"+ data.val +"'>" + data.text + "</option>"); // 체운다
    }
    that.select2(config || {});
  };

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

  (function($){
    var select = $('#levelup-request-form select');
    if ( select.length > 0 ) {
      select.select2();
    }
    var formValidator = new Validator("#levelup-request-form", '.form-field', '.error-message');
    formValidator.addTargetFormField('select[name=new-role]');
    formValidator.addTargetFormField('select[name=register-purpose]');
    formValidator.addTargetFormField('input[name=sms_auth_imp_uid]');
    formValidator.addTargetFormField('input[name=personal-info-agree]');
    formValidator.addTargetFormField('input[name=service-agree]');

    // 등업유형이 변경될때마다 가입목적도 바뀌어야함
    $('select[name=new-role]').on('change', function(){
      $this = $(this);
      var role = $this.val();
      var registerPurposeSelect = $('select[name=register-purpose]');
      if ( role == 'approved_user' ) {
        registerPurposeSelect.updateSelect2Items([
          {
            val: '부동산 임대차 또는 매입/매각을 희망합니다',
            text: '부동산 임대차 또는 매입/매각을 희망합니다'
          }
        ], {});
      }
      else if ( role == 'partner' ) {
        registerPurposeSelect.updateSelect2Items([
          {
            val: '부동산등 관련 유사업종 종사자로서 파트너회원을 희망합니다',
            text: '부동산등 관련 유사업종 종사자로서 파트너회원을 희망합니다'
          },
        ], {});
      }
      else if ( role == 'vip' ) {
        registerPurposeSelect.updateSelect2Items([
          {
            val: '부동산 소유자 또는 관리업체로서 서비스이용을 희망합니다',
            text: '부동산 소유자 또는 관리업체로서 서비스이용을 희망합니다'
          },
        ], {});
      }
    });
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

(function($){
  var select = $('.bnkre-contact-form select');
  if ( select.length > 0 ) {
    select.select2();
  }
})(jQuery);