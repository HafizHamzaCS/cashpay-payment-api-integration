jQuery(document).ready(function ($) {
  jQuery(document).on( "click", "#verify_otp_place_order", function () {
    var response = $(this).attr('data-response');
    var otp = $('#OTP').val();
    if(response != '' && otp != ''){
      var form_data = new FormData(
        jQuery("form.checkout.woocommerce-checkout")[0]
      );
      form_data.append("action", "hma_cashpay_confirm_pyment_action");
      form_data.append("otp", otp);
      form_data.append("response", response);
      jQuery.ajax({
        url: cashpay.ajax_url,
        type: "POST",
        data: form_data,
        contentType: false,
        processData: false,
        beforeSend: function () {
          $(".hma_cashpay_confirm_account_msg").hide();
          $(".hma_cashpay_confirm_account_ajax_loader").show();
        },
        success: function (response) {
          console.log('final response'+response);
          if(response == 200){
            $('#place_order').trigger('click');
          }
        },
      });
    }
  });
     $(window).load(function() { 
      setTimeout(function(){
        $('.payment_methods li').each(function(){
          if($(this).find('input:checked').val() == 'cashpay'){
            $(document).find('#place_order').addClass('sb_validate_must');
            // console.log('testing if ', $(this).find('input').val());
           }
        })
      }, 2000);
    });
    jQuery(document).on( "click", ".sb_validate_must", function (e) {
      e.preventDefault();
      alert('Please verify payment details');
      return false;
    });
    jQuery(document).on( "click", ".payment_methods li", function () {
      if($(this).find('input').val() == 'cashpay'){
        $('#place_order').addClass('sb_validate_must');
      }
      else{
        $('#place_order').removeClass('sb_validate_must');
      }
    });
    jQuery(document).on( "change", ".sb_validation", function () {
      $(document).find('#place_order').addClass('sb_validate_must');
      var valid = 1;
      $('.sb_validation').each( function(){
        if($(this).val() == ''){
          valid = 0;
        }
      })
      if (valid == 1) {
        // $("#CustomerCashPayCode").empty();
        var form_data = new FormData(
          jQuery("form.checkout.woocommerce-checkout")[0]
        );
        form_data.append("action", "hma_cashpay_init_pyment_action");
        jQuery.ajax({
          url: cashpay.ajax_url,
          type: "POST",
          data: form_data,
          contentType: false,
          processData: false,
          beforeSend: function () {
            $(".hma_cashpay_confirm_account_msg").hide();
            $(".hma_cashpay_confirm_account_ajax_loader").show();
          },
          success: function (response) {
            console.log(response);



            if (res == "201") {
              console.log('sahib is here');
            }
            else{

              var obj = $.parseJSON(response);
              console.log(obj);
              var res = obj.res;
              var error_code = obj.error_code;
              var res_msg = obj.res_msg;
              var trans_code = obj.trans_code;
              console.log(res); // 201
              console.log(error_code); // 201
              console.log(res_msg); // 201
              console.log(trans_code); // 201

              $(".hma_cashpay_confirm_account_ajax_loader").hide();

              $('.sb_parent').show();
              $('#verify_otp_place_order').attr('data-response', trans_code);
            }
            // else {
              $(".hma_cashpay_confirm_account_msg")
                .removeClass("hma_confirm_account_ms_success")
                .addClass("hma_cashpay_confirm_account_msg_error");
            // }
            $(".hma_cashpay_confirm_account_msg").show().html(res_msg);
            $(".form-row-p_conferm_code").show();
            $(".hma_cashpay_confirm_account_ajax_loader").hide();
            // $("#CustomerCashPayCode").val("");
          },
        });
      }
    }
  );
});
