jQuery(function($){
  var $notice = $('#wvp-health-complete-notice');
  if(!$notice.length) return;
  $notice.hide();
  $(document).on('wvpHealthQuizStepChange',function(e,data){
    // When the event is dispatched as a DOM CustomEvent the payload is in
    // e.detail or e.originalEvent.detail. Support both cases and the jQuery
    // trigger format where data is passed as the second argument.
    var info = data || (e.originalEvent && e.originalEvent.detail) || e.detail;
    if(info && info.currentStep === info.stepCount - 1){
      $notice.stop(true,true).slideDown();
    }else{
      $notice.hide();
    }
  });
});