<?php

use Drupal\webform\WebformSubmissionInterface;

/**
 * Implements hook_webform_submission_presave().
 */
 
function booking_calendar_webform_submission_presave(WebformSubmissionInterface $submission) {
        
       
        $field_time = substr($submission->getElementData('booking_datetime'), 11, 5);
        $field_date = substr($submission->getElementData('booking_datetime'), 0, 10);
        
        $booking_calendar_service = \Drupal::service('booking_calendar.calendar_service');
        
        
        if(!($booking_calendar_service->isTheDateOpened($field_date, $field_time)) ){
            
            $booking_calendar_service->inCaseOfErrorResponse();
        }

        
        // Slot availability check
        
        $typeOfDay = $booking_calendar_service->isDayFullHalfClosed($field_date);
        
        
        if($typeOfDay==='c'){
            
            // The status of the day has been changed to CLOSED
            $booking_calendar_service->inCaseOfErrorResponse();
            
        }else if($typeOfDay==='f' && in_array($field_time , array_keys($booking_calendar_service->full_day_slots)) ){
            
            // Variable $queryMaxRows to optimize the query - LIMIT the number of rows needed to make a decision
            $queryMaxRows = $booking_calendar_service->full_day_slots[$field_time];
            $total_count = $booking_calendar_service->countPlacesAlreadyTakenPerSlot($field_date, $field_time, $queryMaxRows);
            
            if($total_count === $queryMaxRows) {
               
                    // No more place available
                    $booking_calendar_service->inCaseOfErrorResponse();
            }
        }else if($typeOfDay ==='h' && in_array($field_time , array_keys($booking_calendar_service->half_day_slots))){
            
            // Variable $queryMaxRows to optimize the query - LIMIT the number of rows needed to make a decision
            $queryMaxRows = $booking_calendar_service->half_day_slots[$field_time];
            $total_count = $booking_calendar_service->countPlacesAlreadyTakenPerSlot($field_date, $field_time, $queryMaxRows);
            
            if($total_count === $queryMaxRows) {
                
                // No more place available
                $booking_calendar_service->inCaseOfErrorResponse();
            }   
        }else{
            
            // If  the time selected belong to none of the slots
            $booking_calendar_service->inCaseOfErrorResponse();
        }
}


/**
 * Implements hook_views_bulk_operations_alter(). for the Appointments view
 */
function booking_calendar_views_bulk_operations_alter(array &$operations, \Drupal\views\ViewExecutable $view) {
    // Check if we're altering the correct view by checking the view ID
    if ($view->id() == 'booked_appointments') {
        // Modify the label of an existing bulk operation, e.g., delete
        if (isset($operations['delete'])) {
            $operations['delete']['title'] = t('Remove selected items');
        }
        
        // You can also add a new custom bulk operation to the dropdown
   /*     $operations['mark_as_reviewed'] = [
            'title' => t('Mark selected items as reviewed'),
            'form' => 'my_custom_form_function', // Your custom form handler
        ];
    */
    }
}




