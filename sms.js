(function($){
  var store_id = "imp93858932";
  var merchant_uid = 'merchant_' + new Date().getTime();
  var IMP = window.IMP;
  IMP.init(store_id);
  setTimeout(function(){
    var sms_auth_btn = $('.sms_auth_btn');
    sms_auth_btn.on('click', function(){
      IMP.certification({ // param
        merchant_uid: merchant_uid,
        min_age: 12,
      }, function (rsp) {
        if (rsp.success) {
          sms_auth_btn.text('인증 완료').addClass('button-2').removeClass('button-3');
          sms_auth_btn.attr('disabled', true);
          var imp_uid_input = $('input[name=sms_auth_imp_uid]');
          imp_uid_input.val(rsp.imp_uid);
        } else {
          alert("인증에 실패하였습니다. 에러 내용 : " . rsp.error_msg);
        }
      });
    });
  }, 3000);
})(jQuery);