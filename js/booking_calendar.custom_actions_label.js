(function ($) {
  $(document).ready(function() {
	
	$('#edit-action option[value="webform_submission_delete_action"]').text('Delete');
	$('#edit-action option[value="webform_submission_make_unlock_action"]').text('Confirm Appointment');
	$('#edit-action option[value="webform_submission_make_lock_action"]').text('Cancelled Appointment');
  });
})(jQuery);