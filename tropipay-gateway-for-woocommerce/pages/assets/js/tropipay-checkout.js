jQuery(document).ready(function($){
  $('body').on('click','.checkout #tropipay_payment_method_card',function(){
    $('body').trigger('update_checkout');
  });
  $('body').on('click','.checkout #tropipay_payment_method_balance',function(){
    $('body').trigger('update_checkout');
  });

  // Auto-submit the payment form if it exists
  var $payForm = $('#tropipay_payment_form');
  if ($payForm.length) {
    $payForm.submit();
  }
});
