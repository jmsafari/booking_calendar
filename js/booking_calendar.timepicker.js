(function ($, Drupal) {
	
  Drupal.behaviors.timePickerBehavior = {
    attach: function (context, settings) {
	      // Initialize the timepicker on the input with class 'timepicker'.
	      $('.timepicker', context).timepicker({
	        timeFormat: 'HH:mm',
	        interval: 1,
	        minTime: '00:00',
	        maxTime: '23:59',
	        defaultTime: '',
	        dynamic: false,
	        dropdown: true,
	        scrollbar: true
	      });
		  
		try {
		  $('#dynamic-field-wrapper-fd .details-wrapper', context).addClass('d-flex flex-row'); 
		}catch(error){}
		
	    try { 
          $('#dynamic-field-wrapper-hd .details-wrapper', context).addClass('d-flex flex-row');  
		}catch(error){}
    }
  };
})(jQuery, Drupal);