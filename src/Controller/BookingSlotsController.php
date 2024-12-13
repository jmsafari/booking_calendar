<?php

namespace Drupal\booking_calendar\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\booking_calendar\Service\BookingCalendarService;
use ErrorException;



Class BookingSlotsController extends ControllerBase{    
    

    /**
     * The Booking Calendar service.
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
    
    
    public function displayAvailableSlots($param){
        
        // In case of error
        $build = ['#markup' => 'Something wrong happened. Please try again...'];
        
        if($param === 'NA'){
            
             
            return $build;
        }
        try{
            
           if( !preg_match('/^\d{4}-\d{2}-\d{2}$/', $param) ){
               
               return $build;
           }
           
        }catch (ErrorException $e){
            
            return $build;
        }
        

        if( $this->bookingCalendarService->isTheDateOpened($param) === false ){
            
             $this->bookingCalendarService->inCaseOfErrorResponse();
        }
        
        $typeOfDay = $this->bookingCalendarService->isDayFullHalfClosed($param);

        $output = '';
        
        // Loop through the timeslots
        
        if($typeOfDay === 'f'){
            
            foreach ($this->bookingCalendarService->full_day_slots as $key => $value) {
                
                if($this->bookingCalendarService->countPlacesAlreadyTakenPerSlot($param,$key) < $value ){
                        $output .= '<a class="btn btn-primary w-auto px-4 me-1 mb-1" href="/form/booking-calendar?booking_datetime='.$param.'%20'.$key.'">'. $key.'</a>';
                }
            }
        }else if($typeOfDay === 'h'){
            
            foreach ($this->bookingCalendarService->half_day_slots as $key => $value) {
                if($this->bookingCalendarService->countPlacesAlreadyTakenPerSlot($param,$key) < $value ){
                        $output .= '<a class="btn btn-primary w-auto px-4 me-1 mb-1" href="/form/booking-calendar?booking_datetime='.$param.'%20'.$key.'">'. $key.'</a>';
                }
            }
        }
        
        // If all the time slots are booked
        // If the day is totally booked 
        
        if($output === ''){

            $this->bookingCalendarService->inCaseOfErrorResponse();
        }
        
        
        $booking_header = $this->bookingCalendarService->title_display  ? '<h1>'.$this->bookingCalendarService->cal_title.'</h1>': '';
        $available_hours = $this->bookingCalendarService->cal_language == 'en' ? 'Available hours' : 'Heures disponibles';
        
        if($this->bookingCalendarService->cal_language ==='en'){
        
             $formatted_date =  date('l, F j, Y', strtotime($param));
             
        }else if($this->bookingCalendarService->cal_language ==='fr'){
        
        // Date translated in French
        $jour = $this->bookingCalendarService->day_tr[date('l', strtotime($param))];
        $mois = $this->bookingCalendarService->months_tr[date('F', strtotime($param))];
        $annee = date('Y', strtotime($param));
        $la_date = date('j', strtotime($param));
        
        $formatted_date =  $jour." ".$la_date." ".$mois." ".$annee;
        }
        
        $build = [
            '#markup' => $booking_header.'
                          <h4>'.$formatted_date.'</h4>
                          <p>'.$available_hours.'</p>
                          <div class="row col-md-9  p-4 border border-primary rounded">'.$output.'</div>',
        ];

        return $build;
    }

}