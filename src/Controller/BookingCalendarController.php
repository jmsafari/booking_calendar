<?php

namespace Drupal\booking_calendar\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\booking_calendar\Service\BookingCalendarService;


Class BookingCalendarController extends ControllerBase{
    
    
    /**
     * The greeting service.
     *
     * @var \Drupal\booking_calendar\Service\BookingCalendarService
     */
    protected $bookingCalendarService;
    
    
    /**
     * BookingCalendarController constructor.
     *
     * @param \Drupal\booking_calendar\Service\BookingCalendarService $booking_calendar_service
     *   The greeting service.
     */
    public function __construct(BookingCalendarService $booking_calendar_service) {
        $this->bookingCalendarService = $booking_calendar_service;  
    }
   
    
    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
                    $container->get('booking_calendar.calendar_service')
            );
    }
    

	/**
	 * Generates a simple calendar interface for booking events.
	 */


	public function displayCalendar($param){
	      
	    
	      // Get the current month and year on the Calendar.
	      // $current_date = new DrupalDateTime('+1 month');
	      // Create a new date for the first day of the current month.
	    
	      $current_date = new DrupalDateTime('first day of this month');
	      $current_date->modify($param.' month');
	    
	      // REMOVED: $current_date = new DrupalDateTime($param.' month');
	      $year = $current_date->format('Y');
	      $month = $current_date->format('m');
	      
	      $availability_class = 'closed_day';                        // CSS class to apply for booked and closed dates

	      $month_fullname = $this->bookingCalendarService->cal_language == 'en' ?  $current_date->format('F') : $this->bookingCalendarService->months_tr[$current_date->format('F')] ; // English vs French translation
	      
	      // First day of the month.
	      $first_day_of_month = new DrupalDateTime("$year-$month-01");
	      
	      // NOTE: $first_day_of_month->format('N') will return the day of the 1st day of the current month, Values range from 1-7, eg: 1: Monday, 2: Tuesday, ... 7: Sunday 
	      // %7 will enable Sunday to be the 1st day of the week, %6 Saturday to be the 1st day of the week etc. 
	      
	      // $this->cal_first_day_of_the_week: First day of the week we want to see in the calendar
	      //  +7 to correct php modulo operator.
	      // To get -1%7 = 6 instead of -1%7 = -1

	      $first_day_of_week = ($first_day_of_month->format('w') - $this->bookingCalendarService->cal_first_day_of_the_week +7 ) % 7 ;    
	                                                                                                          
	      
	      // Calculate number of days in the month.
	      $days_in_month = $first_day_of_month->format('t');
	      
	      // Get the previous month total number of days.
	      // Create a new date for the first day of the previous month.
	    
	      $previous_date = new DrupalDateTime('first day of this month');
	      $previous_date->modify(($param-1).' month');
	    
	      // REMOVED : $previous_date = new DrupalDateTime(($param-1).' month');
	      $prev_year = $previous_date->format('Y');
	      $prev_month = $previous_date->format('m');
	      $days_in_prev_month = new DrupalDateTime("$prev_year-$prev_month-01");
	      $tot_days_in_prev_month = $days_in_prev_month->format('t');
	      
		
	      $output = $this->bookingCalendarService->title_display ? '<h1>'.$this->bookingCalendarService->cal_title.'</h1>': '';
                       
		  $output .= '<table class="booking_calendar">
                          <thead><tr class="cal_header" >';

		  
		  // Top calendar menu.
		  
		  $output .= "<th colspan=\"1\"> <a href=\"/booking-calendar/".($param-1)."\"><i class=\"far fa-arrow-alt-circle-left fa-2x\"></i></a></th>
                      <th colspan=\"1\"> <a href=\"/booking-calendar/0\"><i class=\"fa fa-home fa-2x\"></i></a></th>
                      <th colspan=\"4\"  class=\"text-center\"><h4>".$month_fullname." ".$year."</h4></th>
                      <th colspan=\"1\" class=\"text-end\"><a href=\"/booking-calendar/".($param+1)."\"><i class=\"far fa-arrow-alt-circle-right fa-2x\"></i></a> </th>";
		  $output .= '</tr></thead><thead><tr>';  
		  
		  // Calendar headers for days of the week. -
          // Starting with the selected first day of the week
		  
		  for ($i = 0; $i < 7 ; $i++){
		      $output .= "<th>".$this->bookingCalendarService->days[($i+ $this->bookingCalendarService->cal_first_day_of_the_week)%7]."</th>";
		  
	       }
		  
		  $output .= '</tr></thead>';
		  
		  $output .= '<tbody><tr>';
		  
		  // Add blank cells for days before the first of the month.
		  for ($i = 0; $i < $first_day_of_week; $i++) {
		      $output .= '<td class="empty">'. ($tot_days_in_prev_month - $first_day_of_week + $i + 1).'</td>';
		  }
		  
		  // Fill in the days of the month.
		  for ($day = 1; $day <= $days_in_month; $day++) {
		      
		      
    		      if (($day + $first_day_of_week - 1) % 7 == 0) {
    		          $output .= '</tr><tr>';
    		      }
        		    
		          if( $this->bookingCalendarService->isTheDateOpened($year.'-'.$month.'-'.$day)){
		              
		              
		                        $typeOfDate = $this->bookingCalendarService->isDayFullHalfClosed($year.'-'.$month.'-'.$day);
		                        
		                        if($typeOfDate === 'f'){
		                            
		                            $availability_class = $this->bookingCalendarService->placesTakenForGivenDay($year.'-'.$month.'-'.$day)< $this->bookingCalendarService->total_full_day_places ? 'available_day fw-bold': 'closed_day' ;
		                            $output .= "<td class=\"".$availability_class."\"  style=\"padding: 1.5rem;\"><a class=\"text-decoration-none\" href=\"/calendar/book/".$year."-".sprintf("%02d", $month)."-".sprintf("%02d", $day)."\">".$day."</a></td>";
		                        }else if( $typeOfDate === 'h'){
                		            
                		            $availability_class = $this->bookingCalendarService->placesTakenForGivenDay($year.'-'.$month.'-'.$day)< $this->bookingCalendarService->total_half_day_places ? 'available_day half_day fw-bold' : 'closed_day' ;      
                		            $output .= "<td class=\"".$availability_class."\"  style=\"padding: 1.5rem;\" ><a class=\"text-decoration-none\" href=\"/calendar/book/".$year."-".sprintf("%02d", $month)."-".sprintf("%02d", $day)."\">".$day."</a></td>";
		                        } 
    		      }else{
    		          $availability_class = 'closed_day';
    		          $output .= "<td class=\"".$availability_class."\"  style=\"padding: 1.5rem;\">".$day."</a></td>";
    		      }
    	  }
		  
		  // Add remaining empty cells after the end of the month.
		  $remaining_days = 7 - (($days_in_month + $first_day_of_week) % 7);
		  if ($remaining_days < 7) {
			for ($i = 0; $i < $remaining_days; $i++) {
			  $output .= '<td class="empty">'.($i+1).'</td>';
			}
		  }
		  
		  $output .= '</tr></tbody>';
		  $output .= '</table>';
		  
		  
		  $legend = '<div class="row mb-2">
                                    <div class="col-md-4 d-flex">
                                        <div class="legend-icon-xx closed-bg"></div>
                                        <div class="legend-label">'.$this->bookingCalendarService->legend_list[$this->bookingCalendarService->cal_language][0].'</div>
                                    </div>
                                    <div class="col-md-4 d-flex">
                                        <div class="legend-icon-xx opened-bg"></div>
                                        <div class="legend-label">'.$this->bookingCalendarService->legend_list[$this->bookingCalendarService->cal_language][1].'</div>
                                    </div>
                                    <div class="col-md-4 d-flex">
                                        <div class="legend-icon-xx prev-next-month"></div>
                                        <div class="legend-label">'.$this->bookingCalendarService->legend_list[$this->bookingCalendarService->cal_language][2].'</div>
                                    </div>
                        </div>';
		  
		  
		  $build = [
		      '#markup' => '<div class="col-md-10 p-0">'.$output.'</div><div class="col-md-10 legend-container">'.$legend.'</div>',
		  ];
		  
		  // Attach the CSS library.
		  $build['#attached']['library'][] = 'booking_calendar/booking_calendar.styles';
		  $build['#attached']['library'][] = 'booking_calendar/booking_calendar.bootstrap_css';
		  $build['#attached']['library'][] = 'booking_calendar/booking_calendar.fontawesome_css';
		  
		  // Attach the JavaScript library.
		  $build['#attached']['library'][] = 'booking_calendar/booking_calendar.bootstrap_js';
		  $build['#attached']['library'][] = 'booking_calendar/booking_calendar.fontawesome_js';
		  
		  return $build;	
	}	
	
	
	/**
	 * Generates a simple Dashboard.
	 */
	
	
	public function displayBookingDashboard($param){
	    
	    
	    // Get the current month and year on the Calendar.
	    // $current_date = new DrupalDateTime('+1 month');
	    
	    $current_date = new DrupalDateTime($param.' month');
	    $year = $current_date->format('Y');
	    $month = $current_date->format('m');
	    
	    $availability_class = 'closed_day';                        // CSS class to apply for booked and closed dates
	    
	    $month_fullname = $this->bookingCalendarService->cal_language == 'en' ?  $current_date->format('F') : $this->bookingCalendarService->months_tr[$current_date->format('F')] ; // English vs French translation
	    
	    // First day of the month.
	    $first_day_of_month = new DrupalDateTime("$year-$month-01");
	    
	    // NOTE: $first_day_of_month->format('N') will return the day of the 1st day of the current month, Values range from 1-7, eg: 1: Monday, 2: Tuesday, ... 7: Sunday
	    // %7 will enable Sunday to be the 1st day of the week, %6 Saturday to be the 1st day of the week etc.
	    
	    // $this->cal_first_day_of_the_week: First day of the week we want to see in the calendar
	    //  +7 to correct php modulo operator.
	    // To get -1%7 = 6 instead of -1%7 = -1
	    
	    $first_day_of_week = ($first_day_of_month->format('w') - $this->bookingCalendarService->cal_first_day_of_the_week +7 ) % 7 ;
	    
	    
	    // Calculate number of days in the month.
	    $days_in_month = $first_day_of_month->format('t');
	    
	    // Get the previous month total number of days.
	    $previous_date = new DrupalDateTime(($param-1).' month');
	    $prev_year = $previous_date->format('Y');
	    $prev_month = $previous_date->format('m');
	    $days_in_prev_month = new DrupalDateTime("$prev_year-$prev_month-01");
	    $tot_days_in_prev_month = $days_in_prev_month->format('t');
	    
	    
	    $output = '<h1>Dashboard</h1>';
	    
	    
	    if($this->bookingCalendarService->cal_opening_window === -1){
	        
	        $output .= 'The calendar is set to "Closed". Go to the settings page to open it - <a class="btn btn-warning" href="/admin/config/system/booking-calendar-settings">Calendar settings page</a>';
	        
	    }else if(in_array('f', $this->bookingCalendarService->cal_opened_days,TRUE)){
	        
	        if($this->bookingCalendarService->num_fields_fd===0){
	            
	            $output .= 'You need to add at least one slot for Full days. Go to the settings page to open it - <a class="btn btn-warning" href="/admin/config/system/booking-calendar-settings">Calendar settings page</a>';
	        }
	        
	    }else if(in_array('h', $this->bookingCalendarService->cal_opened_days,TRUE)){
	        
	        if($this->bookingCalendarService->num_fields_hd===0){

	            $output .= 'You need to add at least one slot for Half days. Go to the settings page to open it - <a class="btn btn-warning" href="/admin/config/system/booking-calendar-settings">Calendar settings page</a>';
	        }
	        
	    }
	    
	    
	    $output .= '<table class="booking_calendar">
                          <thead><tr class="cal_header" >';
	    
	    
	    // Top calendar menu.
	    
	    $output .= "<th colspan=\"1\"> <a href=\"/booking-dashboard/".($param-1)."\"><i class=\"far fa-arrow-alt-circle-left fa-2x\"></i></a></th>
                      <th colspan=\"1\"> <a href=\"/booking-dashboard/0\"><i class=\"fa fa-home fa-2x\"></i></a></th>
                      <th colspan=\"2\"  class=\"text-center\"><h4>".$month_fullname." ".$year."</h4></th>
                      <th colspan=\"1\" class=\"text-end\"><a href=\"/find-appointments\"><i class=\"fa fa-search fa-2x\"></i></a> </th>
                      <th colspan=\"1\" class=\"text-end\"><a href=\"/admin/config/system/booking-calendar-settings\"><i class=\"fa fa-cog fa-2x\"></i></a> </th>
                      <th colspan=\"1\" class=\"text-end\"><a href=\"/booking-dashboard/".($param+1)."\"><i class=\"far fa-arrow-alt-circle-right fa-2x\"></i></a> </th>";
	    $output .= '</tr></thead><thead><tr>';
	    
	    // Calendar headers for days of the week. -
	    // Starting with the selected first day of the week
	    
	    for ($i = 0; $i < 7 ; $i++){
	        $output .= "<th>".$this->bookingCalendarService->days[($i+ $this->bookingCalendarService->cal_first_day_of_the_week)%7]."</th>";
	        
	    }
	    
	    $output .= '</tr></thead>';
	    
	    $output .= '<tbody><tr>';
	    
	    // Add blank cells for days before the first of the month.
	    for ($i = 0; $i < $first_day_of_week; $i++) {
	        $output .= '<td class="empty">'. ($tot_days_in_prev_month - $first_day_of_week + $i + 1).'</td>';
	    }
	    
	    // Fill in the days of the month.
	    for ($day = 1; $day <= $days_in_month; $day++) {
	        
	        
	        if (($day + $first_day_of_week - 1) % 7 == 0) {
	            $output .= '</tr><tr>';
	        }
	        
	        $dateClosedFor = $this->bookingCalendarService->dateClosedFor($year.'-'.$month.'-'.$day);
	        
	        if( !$dateClosedFor){ 
	            
	            
	            $typeOfDate = $this->bookingCalendarService->isDayFullHalfClosed($year.'-'.$month.'-'.$day);
	            
	            if($typeOfDate === 'f'){
	                
	                if($this->bookingCalendarService->placesTakenForGivenDay($year.'-'.$month.'-'.$day)< $this->bookingCalendarService->total_full_day_places){
	                    
	                     // full_day 
	                    $output .= "<td class=\"available_day\" style=\"padding: 1.5rem;\"><big><a class=\"text-decoration-none badge badge-success\" href=\"/calendar/book/".$year."-".sprintf("%02d", $month)."-".sprintf("%02d", $day)."\">".sprintf("%02d", $day)." - F</a></big></td>";
	                }else{
	                    // closed_day
	                    $output .= "<td class=\"available_day\" style=\"padding: 1.5rem;\"><big><span class=\"text-decoration-none badge badge-danger\">".sprintf("%02d", $day)." - F</span></big></td>";
	                }
	            }else if( $typeOfDate === 'h'){
	                
	               // $availability_class = $this->bookingCalendarService->placesTakenForGivenDay($year.'-'.$month.'-'.$day)< $this->bookingCalendarService->total_half_day_places ? 'available_day half_day' : 'closed_day' ;
	               
	                if($this->bookingCalendarService->placesTakenForGivenDay($year.'-'.$month.'-'.$day)< $this->bookingCalendarService->total_half_day_places){
	                    // half_day
	                    $output .= "<td class=\"available_day\" style=\"padding: 1.5rem;\" ><big><a class=\"text-decoration-none badge badge-success\" href=\"/calendar/book/".$year."-".sprintf("%02d", $month)."-".sprintf("%02d", $day)."\">".sprintf("%02d", $day)." - H</a></big></td>";
	                }else{
	                    
	                    // closed_day
	                    $output .= "<td class=\"available_day\" style=\"padding: 1.5rem;\" ><big><span class=\"badge badge-danger\">".$day." - H</span></big></td>";
	                }  
	            }
	        }elseif($dateClosedFor === 1) {
	            // closed_day
	            $output .= "<td class=\"closed_day\" style=\"padding: 1.5rem;\">".$day."</td>";
	        }elseif($dateClosedFor === 2) {
	            // closed_day - Vacations
	            $output .= "<td class=\"closed_day\" style=\"padding: 1.5rem;\"><big><span class=\"badge badge-dark\">".$day." - VE</span></big></td>";
	        }elseif($dateClosedFor === 3) {
	            // closed_day - Annual date off
	            $output .= "<td class=\"closed_day\" style=\"padding: 1.5rem;\"><big><span class=\"badge badge-dark\">".$day." - RA</span></big></td>";
	        }elseif($dateClosedFor === 4) {
	            // closed_day - Monthly date off
	            $output .= "<td class=\"closed_day\" style=\"padding: 1.5rem;\"><big><span class=\"badge badge-dark\">".$day." - RM</span></big></td>";
	        }
	    }
	    
	    // Add remaining empty cells after the end of the month.
	    $remaining_days = 7 - (($days_in_month + $first_day_of_week) % 7);
	    if ($remaining_days < 7) {
	        for ($i = 0; $i < $remaining_days; $i++) {
	            $output .= '<td class="empty">'.($i+1).'</td>';
	        }
	    }
	    
	    $output .= '</tr></tbody>';
	    $output .= '</table>';

        
        $legend = '
                        <div class="row mb-2">
                                    <div class="col-md-4 d-flex">
                                        <div class="legend-icon-xx closed-bg"></div>
                                        <div class="legend-label">'.$this->bookingCalendarService->legend_list['en'][0].'</div>
                                    </div>
                                    <div class="col-md-4 d-flex">
                                        <div class="legend-icon-xx opened-bg"></div>
                                        <div class="legend-label">'.$this->bookingCalendarService->legend_list['en'][1].'</div>
                                    </div>
                                    <div class="col-md-4 d-flex">
                                        <div class="legend-icon-xx prev-next-month"></div>
                                        <div class="legend-label">'.$this->bookingCalendarService->legend_list['en'][2].'</div>
                                    </div>
                        </div>
                        <div class="row  mb-2">
                                    <div class="col-md-4 d-flex">
                                        <div class="legend-icon-xx badge badge-danger">DD - F/H</div>
                                        <div class="legend-label">All the slots are taken</div>
                                    </div>
                                    <div class="col-md-4 d-flex">
                                        <div class="legend-icon-xx badge badge-success">DD - F/H</div>
                                        <div class="legend-label">Slots are still available</div>
                                    </div>
                                    <div class="col-md-4 d-flex">
                                        <div class="legend-icon-xx badge badge-dark">DD - VE/RA/RM</div>
                                        <div class="legend-label">Special - Closed dates</div>
                                    </div>
                        </div>
                        <div class="row">
                                    <div class="col-md-12">
<strong>Opened day:</strong> Full day (F) / Half day (H), <strong>DD:</strong> Date in double digits format, <strong>VE:</strong> Vacations OR Emergency, <strong>RA:</strong> Recurring annual day off, <strong>RM:</strong> Reccurring monthly day off
                                    </div>
                        </div>
                    ';
	    
	    
	    $build = [
	        '#markup' => '<div class="col-md-12 p-0">'.$output.'</div><div class="col-md-12 legend-container">'.$legend.'</div>',
	    ];
	    
	    // Attach the CSS library.
	    $build['#attached']['library'][] = 'booking_calendar/booking_calendar.styles';
	    $build['#attached']['library'][] = 'booking_calendar/booking_calendar.bootstrap_css';
	    $build['#attached']['library'][] = 'booking_calendar/booking_calendar.fontawesome_css';
	    
	    // Attach the JavaScript library.
	    $build['#attached']['library'][] = 'booking_calendar/booking_calendar.bootstrap_js';
	    $build['#attached']['library'][] = 'booking_calendar/booking_calendar.fontawesome_js';
	    
	    return $build;
	}	
	
	
	
	public function displayMessage(){
	    
	    $output = '<div class="alert alert-info text-center" role="alert">
                      <p>The selected slot is no longer available. Please choose another slot.</p>
                        <a class="btn btn-primary" href="/calendar">Back to calendar</a>
                    </div>' ;
	    
	    $build = [
	        '#markup' => '<div class="col-md-8">'.$output.'</div>',
	    ];
	    return $build;
	    
	}
}
