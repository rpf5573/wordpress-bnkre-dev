(function($){
  var store_id = "imp63297414";
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
          if ( ! sms_auth_ajax.nonce || !sms_auth_ajax.url ) { alert("Nonce is undefined. 잘못된 접근입니다"); return }
          jQuery.ajax({
            url: sms_auth_ajax.url,
            method: "POST",
            headers: { "Content-Type": "application/json" },
            data: {
              nonce: sms_auth_ajax.nonce,
              imp_uid: rsp.imp_uid,
              action: 'process_sms_auth'
            }
          });
        } else {
          console.log(rsp);
          // alert("인증에 실패하였습니다. 에러 내용 : " . rsp.error_msg);
        }
      });
    });
  }, 3000);
})(jQuery);