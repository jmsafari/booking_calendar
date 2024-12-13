(function ($, Drupal) {
  Drupal.behaviors.characterCounter = {
	    attach: function (context, settings) {
		
		      const $textarea1 = $('#textarea_with_annual');
			  const $textarea2 = $('#textarea_with_monthly');
			  const $textarea3 = $('#textarea_with_vacations');
	 		  const $textarea4 = $('#textarea_with_email');
		
		      const maxLength1 = $textarea1.attr('maxlength');
		      const $counter1 = $('#chars_counter_annual');
		
		      const maxLength2 = $textarea2.attr('maxlength');
		      const $counter2 = $('#chars_counter_monthly');
		
		      const maxLength3 = $textarea3.attr('maxlength');
		      const $counter3 = $('#chars_counter_vacations');

		      const maxLength4 = $textarea4.attr('maxlength');
		      const $counter4 = $('#chars_counter_email');
		
		      $textarea1.on('input', function () {
			
		        const remaining1 = maxLength1 - $textarea1.val().length;
		        $counter1.text(remaining1 + ' characters remaining');
		      });
		
		      $textarea2.on('input', function () {
		
		        const remaining2 = maxLength2 - $textarea2.val().length;
		        $counter2.text(remaining2 + ' characters remaining');
		      });
		
		      $textarea3.on('input', function () {
		
		        const remaining3 = maxLength3 - $textarea3.val().length;
		        $counter3.text(remaining3 + ' characters remaining');
		      });

		      $textarea4.on('input', function () {
		
		        const remaining4 = maxLength4 - $textarea4.val().length;
		        $counter4.text(remaining4 + ' characters remaining');
		      });
	    }
  };
})(jQuery, Drupal);