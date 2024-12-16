<?php

namespace Drupal\booking_calendar\Service;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Datetime\DrupalDateTime;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse; 

class BookingCalendarService extends ControllerBase{

        /**
         *
         * @var \Drupal\Core\Config\ConfigFactoryInterface
         */
        
        protected $configFactory;
        
        private $webform_id = 'booking_calendar';
        public $title_display = true;
        public $cal_title ;
        public $cal_language = 'en';
        public $days;
        
        public $cal_first_day_of_the_week = 0; // In most organization it is Sunday 
        public $cal_opening_window = 1;
        public $current_day_bookable = true;
    
        public $months_tr = array("January"=>"Janvier", "February"=>"F&eacute;vrier", "March"=>"Mars", "April"=>"Avril","May"=>"Mai",
                                   "June"=>"Juin", "July"=>"Juillet", "August"=>"Aout", "September"=>"Septembre", "October"=>"Octobre",
                                   "November"=>"Novembre", "December"=>"D&eacute;cembre",
                             );
        
        
        
        public $day_tr    = array("Monday"=>"Lundi", "Tuesday"=>"Mardi", "Wednesday"=>"Mercredi", "Thursday"=>"Jeudi", "Friday"=>"Vendredi", 
                                "Saturday"=>"Samedi","Sunday"=>"Dimanche",
                             );
        
        public $days_list = array(
                                'en' =>array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                                'fr' =>array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'),
                             );
        
        public $legend_list = array(
                                "en" => ['Date/Booking closed', 'Booking opened', 'Next/Previous month'],
                                "fr" => ['Date/R&eacute;servation ferm&eacute;e', 'R&eacute;servation ouverte', 'Mois pr&eacute;c&eacute;dent'],
                              );
        
        public $cal_closed_days;
        public $cal_opened_days;
        public $cal_half_days;
        public $vacation_days = '';
        public $recurring_annual_dayoff = '';
        public $recurring_monthly_dayoff = '';
        
        public $num_fields_fd;
        public $num_fields_hd;
        public $full_day_slots = array();
        public $half_day_slots = array();
        public $total_full_day_places=0;
        public $total_half_day_places=0;

        
        /**
         *
         * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
         */
        
        public function __construct(ConfigFactoryInterface $configFactory) {
            
                $this->configFactory = $configFactory;
                $this->loadSettings();
         }    
       
        public function loadSettings(){
            
            $config = $this->configFactory->get('booking_calendar.settings');
            
            $this->title_display = $config->get('title_display') ?? false; // Display title: True/False
            $this->cal_title = $config->get('title_value') ?? '';   // Calendar title
            $this->cal_language = $config->get('cal_lang') ?? 'en';   // Calendar language
            $this->days = $this->days_list[$this->cal_language];
            $this->cal_first_day_of_the_week = $config->get('cal_first_day') ?? 0;  // 0: Sunday as first day of the week
            $this->cal_opening_window = $config->get('cal_opening_window') ?? -2; // Opening window of the calendar
            
            
            $this->current_day_bookable = $config->get('current_day_bookable') ?? true ; // Check if the current day need to be open for booking
            
            $this->cal_closed_days = $config->get('cal_closed_days') ?? array();   // Closed days - eg: Weekend
            $this->cal_opened_days = $config->get('cal_opened_days') ?? array();
            $this->cal_half_days = $config->get('cal_half_days') ?? array(); ;   // Half days
            
            $this->vacation_days = $config->get('vacation_days') ?? '';
            $this->recurring_annual_dayoff = $config->get('recurring_dayoff') ?? '';
            $this->recurring_monthly_dayoff = $config->get('recurring_monthly_dayoff') ?? '';
            
            
            $this->num_fields_fd = $config->get('num_fields_fd') ?? 0; // Full day - number of time slots open
            $this->num_fields_hd = $config->get('num_fields_hd') ?? 0; // Half day - number of time slots open
            
            $this->full_day_slots = json_decode($config->get('full_day_slots') ?? '[]', TRUE) ?? array();
            $this->half_day_slots = json_decode($config->get('half_day_slots') ?? '[]', TRUE) ?? array();
            
            $this->total_full_day_places = array_sum($this->full_day_slots); // Full day - total number of places
            $this->total_half_day_places = array_sum($this->half_day_slots); // Half day - total number of places
            
        }
        
        // Date -> Time Slot -> #Places
        
        public function OLD_countPlacesAlreadyTakenPerSlot($field_date = NULL, $field_time= NULL, $query_max_rows=NULL){
            
            if(isset($field_date) && isset($field_time)){
                
                // Define your date range for filtering, for example, submissions from the past 30 days.
                
                $connection = Database::getConnection();
                
                $query = $connection->select('webform_submission_data', 'wsd');
                
                // Select fields from the webform_submission_data table
                $query->fields('wsd', ['name']);
                
                $query->condition('wsd.webform_id', $this->webform_id);
                $query->condition('wsd.name', 'booking_datetime');
                $query->condition('wsd.value', $field_date.'T'.$field_time.'%', 'LIKE');
                $query->addExpression('COUNT(DISTINCT wsd.sid)', 'submission_count');
                
                // Use countQuery() to get a count of the results
                $count_query = $query->countQuery();
                $total_count = $count_query->execute()->fetchField();
                
                return $total_count ?? 0;
                
                
            } return -1;
        }
        
        
        public function countPlacesAlreadyTakenPerSlot($field_date = NULL, $field_time= NULL, $query_max_rows=NULL){
            
            if(isset($field_date) && isset($field_time )){
                
                // Define your date range for filtering, for example, submissions from the past 30 days.
                
                $connection = Database::getConnection();
                
                $query_limit = isset($query_max_rows) && is_numeric($query_max_rows)   ? " LIMIT :queryMaxRows " : " ";
                
                $query = "
                            SELECT COUNT(DISTINCT ws_joined.sid ) as total
                            FROM(
                            
                            	SELECT ws_data.sid as  sid
                            	FROM {webform_submission}  ws
                            	LEFT JOIN {webform_submission_data} ws_data
                            	ON ws.sid = ws_data.sid
                            	WHERE 
                                ws.locked= :lockedStatus
                                AND ws.created  
                            	BETWEEN UNIX_TIMESTAMP(DATE_ADD(:dateOfInterest, INTERVAL -:openedWindow MONTH)) 
                            	AND UNIX_TIMESTAMP(DATE_ADD(:dateOfInterest, INTERVAL :openedWindow MONTH))
                            	AND ws_data.name = :fieldName
                            	AND ws_data.value LIKE :dateTimeOfInterest ".$query_limit.")  ws_joined ";
                
                $args = [
                    ':dateOfInterest' => $field_date,   
                    ':openedWindow' => 6,  
                    ':lockedStatus' => 0,
                    ':fieldName' => 'booking_datetime',
                    ':dateTimeOfInterest' => $field_date.'T'.$field_time.'%',
                    ':queryMaxRows' =>  $query_max_rows ?? 0,
                ];
                
                $result = $connection->query($query, $args);
                $total_count = $result->fetchField();
                
                return $total_count ?? 0;
                
            } return -1;
        }
        
        
        // Given Date ->  #Total #Places
        
        public function placesTakenForGivenDay($dateOfInterest=NULL ){
   
            if(isset($dateOfInterest)){
                
                
                $connection = Database::getConnection();
                
                $query = "
                            SELECT COUNT(DISTINCT ws_joined.sid ) as total
                            FROM(
                    
                            	SELECT ws_data.sid as  sid
                            	FROM {webform_submission}  ws
                            	LEFT JOIN {webform_submission_data} ws_data
                            	ON ws.sid = ws_data.sid
                            	WHERE 
                                ws.locked= :lockedStatus
                                AND ws.created
                            	BETWEEN UNIX_TIMESTAMP(DATE_ADD(:dateOfInterest, INTERVAL -:openedWindow MONTH))
                            	AND UNIX_TIMESTAMP(DATE_ADD(:dateOfInterest, INTERVAL :openedWindow MONTH))
                            	AND ws_data.name = :fieldName
                            	AND ws_data.value LIKE :dateTimeOfInterest ) as ws_joined ";
                
                $args = [
                    ':dateOfInterest' => $dateOfInterest,
                    ':openedWindow' => 6,
                    ':lockedStatus' => 0,
                    ':fieldName' => 'booking_datetime',
                    ':dateTimeOfInterest' => $dateOfInterest.'%',
                ];
                
                $result = $connection->query($query, $args);
                $total_count = $result->fetchField();
                
                return $total_count ?? 0;
                
            } return -1;
            
            
            
        }
        
        public function OLD_placesTakenForGivenDay($dateOfInterest ){
            
            
            $connection = Database::getConnection();
            
            $query = $connection->select('webform_submission_data', 'wsd');
            
            // Select fields from the webform_submission_data table
            $query->fields('wsd', ['name']);
            
            $query->condition('wsd.webform_id', $this->webform_id);
            $query->condition('wsd.name', 'booking_datetime');
            $query->condition('wsd.value', $dateOfInterest.'%', 'LIKE');
            $query->addExpression('COUNT(DISTINCT wsd.sid)', 'submission_count');
            
            // Use countQuery() to get a count of the results
            $count_query = $query->countQuery();
            $total_count = $count_query->execute()->fetchField();
            
            return $total_count ?? 0;
        }
    
        public function monthsDifference($today_year, $today_month, $curr_year, $curr_month ){
            
            return  ($curr_year - $today_year) * 12 + ($curr_month - $today_month);
        }
        
        public function isDayFullHalfClosed($field_date){
            
                $dateOfInterest = new DrupalDateTime($field_date); // DATE OF INTEREST: $field_date format Y-m-d
                $dayOfInterest = $dateOfInterest->format('w')+1;   // DAY OF INTEREST [1: SUNDAY, 2:MONDAY,...6: SATURDAY]
                return  $this->cal_opened_days[$dayOfInterest];
        }
        
        
        public function isTheDateOpened($date_y_m_d, $time_hh_mm=NULL){
            
            
                $typeOfDate = $this->isDayFullHalfClosed($date_y_m_d);

                if($this->cal_opening_window == -1 || $typeOfDate === 'c' ){
                    
                    return false;
                }
                
                if(isset($time_hh_mm)){
                    
                    if($typeOfDate== 'f' && !in_array($time_hh_mm, array_keys($this->full_day_slots))){
                        
                        return false;
                    }else if($typeOfDate== 'h' && !in_array($time_hh_mm, array_keys($this->half_day_slots)) ){
                        
                        return false;
                    }
                }
                
                $dateOfInterest = new DrupalDateTime($date_y_m_d);
                
                // Open Window Interval [ $window_date_min, $window_date_max ]
                $window_date_min = $this->current_day_bookable ? new DrupalDateTime('now') : new DrupalDateTime('now +1 day');
                $window_date_max = new DrupalDateTime('now +'.$this->cal_opening_window.' month');
                
                if($this->cal_opening_window == 0){
                    
                    $window_date_max = new DrupalDateTime('last day of this month');
                }
                 
                // NOTE: We need to set time to 0 to normalize the comparison  with ->setTime(0, 0, 0)
                // $window_date_max->setTime(0, 0, 0)  &&  $window_date_min->setTime(0, 0, 0)
                // eg: DrupalDateTime('now') will return the current Date and time. 
                // While the $dateOfInterest dont return the time. Only the date. 
                    
                
                if( $dateOfInterest > $window_date_max->setTime(0, 0, 0)  ||  $window_date_min->setTime(0, 0, 0) > $dateOfInterest ||
                    in_array($date_y_m_d, explode(',',$this->vacation_days))  || in_array($dateOfInterest->format('m-d'), explode(',',$this->recurring_annual_dayoff)) 
                    || in_array($dateOfInterest->format('d'), explode(',',$this->recurring_monthly_dayoff))){          
                        
                    return false;
                }
                return true;
        }
        
        
        public function dateClosedFor($date_y_m_d){
            
                
                $typeOfDate = $this->isDayFullHalfClosed($date_y_m_d);
    
                $dateOfInterest = new DrupalDateTime($date_y_m_d);
                
                // Open Window Interval [ $window_date_min, $window_date_max ]
                $window_date_min = $this->current_day_bookable ? new DrupalDateTime('now') : new DrupalDateTime('now +1 day');
                $window_date_max = new DrupalDateTime('now +'.$this->cal_opening_window.' month');
                
                if($this->cal_opening_window == 0){
                    
                    $window_date_max = new DrupalDateTime('last day of this month');
                }
                
                // NOTE: We need to set time to 0 to normalize the comparison  with ->setTime(0, 0, 0)
                // $window_date_max->setTime(0, 0, 0)  &&  $window_date_min->setTime(0, 0, 0)
                // eg: DrupalDateTime('now') will return the current Date and time.
                // While the $dateOfInterest dont return the time. Only the date.
                
                // cal_opening_window == -1 : Booking Calendar is closed 
                if($this->cal_opening_window == -1 || $typeOfDate === 'c' || $dateOfInterest > $window_date_max->setTime(0, 0, 0) ||  $window_date_min->setTime(0, 0, 0) > $dateOfInterest ){
                    
                    return 1;   // The all calendar is closed OR the day related to the given date is closed 
                                // OR the given date is out of the open window of the calendar
                }else if(in_array($date_y_m_d, explode(',',$this->vacation_days))){
                    
                    return 2;  // Closed for vacations
                }else if(in_array($dateOfInterest->format('m-d'), explode(',',$this->recurring_annual_dayoff))){
                    
                    return 3;  // Recurring annual dayoff
                }else if(in_array($dateOfInterest->format('d'), explode(',',$this->recurring_monthly_dayoff))){
                    
                    return 4;  // Recurring monthly dayoff
                }
                    
                return false;  // Opened day
        }
        
        public function inCaseOfErrorResponse(){
            
            // Redirect to the calendar page
            $url = Url::fromRoute('booking_calendar.messages');
            $response = new RedirectResponse($url->toString(), 302);
            $response->send();
            exit();
        }     
}
