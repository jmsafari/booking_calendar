<?php

namespace Drupal\booking_calendar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\Cache;

use Exception;


class BookingCalendarConfigForm extends ConfigFormBase {

    private $days_names = array("Sunday","Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
    private $half_day_slots = array();
    private $full_day_slots = array();
    private $num_fd_entry_to_store = 0;
    private $num_hd_entry_to_store = 0;
    
    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {  // Iist all the configurations Editable by this script
        return ['booking_calendar.settings', 'webform.webform.booking_calendar'];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'booking_calendar_settings_form';
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        
        // Load existing configuration.(Configuration Form)
        $config = $this->config('booking_calendar.settings');
        
        // Load Notification WEBFORM configuration.
        $config_booking_form = $this->config('webform.webform.booking_calendar');
        
        // Initialize the dynamic fields count array if not set.
        
        $ini_num_fd = $config->get('num_fields_fd');
        $ini_num_hd = $config->get('num_fields_hd');
        
        if(!isset($ini_num_hd) || $ini_num_hd==0){   $ini_num_hd = 1 ;  }
        
        if(!isset($ini_num_fd) || $ini_num_fd==0){   $ini_num_fd = 1 ;  }
        
        
        if ($form_state->get('num_fields') === NULL) {
            $form_state->set('num_fields', [
                'hd' => $ini_num_hd,
                'fd' => $ini_num_fd
            ]);    // Default to 1 field if none.,
        }
        
       
        $num_fields = $form_state->get('num_fields');
        
        
        // Add horizontal tabs container to group related fields.

        $form['tabs'] = [
            '#type' => 'vertical_tabs', // Horizontal tabs can be replaced with vertical_tabs for vertical tab sets.
            '#title' => $this->t(''),
        ];
        
            // First tab group.
            $form['general_settings'] = [
                '#type' => 'details',
                '#title' => $this->t('General'),
                '#group' => 'tabs',
            ];
                        $form['general_settings']['title_display'] = [
                            '#type' => 'checkbox',
                            '#title' => $this->t('Display calendar title'),
                            '#default_value' => $config->get('title_display') ?? false,
                        ];
                        
                        $form['general_settings']['title_value'] = [
                            '#type' => 'textfield',
                            '#title' => $this->t('Calendar title'),
                            '#description' => $this->t('Enter a value for this setting.'),
                            '#default_value' => $config->get('title_value') ?? '',
                        ];
                        
                        $form['general_settings']['confirmation_form_title'] = [
                            '#type' => 'textfield',
                            '#title' => $this->t('Booking form title'),
                            '#description' => $this->t('Enter a value for this setting.'),
                            '#default_value' => $config_booking_form->get('title') ?? '',
                            '#required' => TRUE,
                        ];
                        
                        $form['general_settings']['cal_lang'] = [
                            '#type' => 'select',
                            '#title' => $this->t('Calendar language'),
                            '#options' => [
                                'en' => $this->t('English'),
                                'fr' => $this->t('French'),
                            ],
                            '#default_value' => $config->get('cal_lang') ?? 'en', // Load the saved value.
                            '#required' => TRUE,
                        ];
                        
                        $form['general_settings']['cal_first_day'] = [
                            '#type' => 'select',
                            '#title' => $this->t('First day of the week'),
                            '#options' => [
                                0 => $this->t('Sunday'),
                                1 => $this->t('Monday'),
                                2 => $this->t('Tuesday'),
                                3 => $this->t('Wednesday'),
                                4 => $this->t('Thursday'),
                                5 => $this->t('Friday'),
                                6 => $this->t('Saturday'),
                            ],
                            '#default_value' => $config->get('cal_first_day') ?? 0, // Load the saved value.
                            '#required' => TRUE,
                        ];
                        
                        $form['general_settings']['cal_opening_window'] = [
                            '#type' => 'select',
                            '#title' => $this->t('Opening window'),
                            '#options' => [
                               -1 => $this->t('Close calendar'),
                                0 => $this->t('Only current month'),
                                1 => $this->t('1 Month'),
                                2 => $this->t('2 Months'),
                                3 => $this->t('3 Months'),
                                4 => $this->t('4 Months'),
                                5 => $this->t('5 Months'),
                                6 => $this->t('6 Months'),
                            ],
                            '#default_value' => $config->get('cal_opening_window') ?? -1, // Load the saved value.
                            '#required' => TRUE,
                        ];
                        
                        $form['general_settings']['current_day_bookable'] = [
                            '#type' => 'checkbox',
                            '#title' => $this->t('Current day - bookable'),
                            '#description' => $this->t('Check this box if booking can be made today.'),
                            '#default_value' => $config->get('current_day_bookable') ?? true,
                        ];
                        
            // Second tab group.
            $form['closed_day_settings'] = [
                '#type' => 'details',
                '#title' => $this->t('Closed days'),
                '#group' => 'tabs',
            ];
                        $form['closed_day_settings']['cal_closed_days'] = [
                            '#type' => 'checkboxes',
                            '#title' => $this->t('Closed day(s)'),
                            '#description' => $this->t('Select the days that need to be closed. Eg: The weekend - Saturday and Sunday'),
                            '#options' => [
                                1 => $this->t('Sunday'),
                                2 => $this->t('Monday'),
                                3 => $this->t('Tuesday'),
                                4 => $this->t('Wednesday'),
                                5 => $this->t('Thursday'),
                                6 => $this->t('Friday'),
                                7 => $this->t('Saturday'),
                            ],
                            '#default_value' => $config->get('cal_closed_days') ?? [], // Load the saved value.
                        ];
                       
            // Third tab group.
            $form['half_day_settings'] = [
                '#type' => 'details',
                '#title' => $this->t('Half days'),
                '#group' => 'tabs',
           ]; 
                        $form['half_day_settings']['cal_half_days'] = [
                            '#type' => 'checkboxes',
                            '#title' => $this->t('Half day(s)'),
                            '#description' => $this->t('The days that are shorter than a normal business days'),
                            '#options' => [
                                1 => $this->t('Sunday'),
                                2 => $this->t('Monday'),
                                3 => $this->t('Tuesday'),
                                4 => $this->t('Wednesday'),
                                5 => $this->t('Thursday'),
                                6 => $this->t('Friday'),
                                7 => $this->t('Saturday'),
                            ],
                            '#default_value' => $config->get('cal_half_days') ?? [], // Load the saved value.
                        ];
                        
            // Fourth tab group.  [FULL DAYS]
            $form['available_slots_fd'] = [
                '#type' => 'details',
                '#title' => $this->t('Full days - slots'),
                '#group' => 'tabs',
            ];
                        
                        // Create a container to hold the dynamic fields.
                        $form['available_slots_fd']['slot_container_fd'] = [
                            '#type' => 'container',
                            '#attributes' => ['id' => 'dynamic-field-wrapper-fd'],
                        ];
                        
                        // Loop to generate each field.$full_slots_array
                        
                        //for ($i = 0; $i < $form_state->get('num_fields_hd'); $i++) {
                        // for ($i = 0; $i < $num_fields['fd'];$i++){
                        
                        try{
                            $full_slots_array =  json_decode($config->get('full_day_slots') ?? '[]', TRUE) ?? array(); 
                            $full_times_array = array_keys($full_slots_array) ?? array();  // ksort: To sort the array
                        } catch (Exception $e) {
                            $full_slots_array = array();
                            $full_times_array = array();
                        }
                        for ($i = 0; $i < $num_fields['fd'];$i++) {
                            
                            
                            $form_state->get('num_fields');
                            
                            $form['available_slots_fd']['slot_container_fd']['slot_group_fd_'. $i] = [
                                '#type' => 'details',
                                '#title' => $this->t('Time slot @number', ['@number' => ($i +1)]),
                                '#open' => TRUE,  // This controls whether the section is collapsed by default
                                
                            ];
                            
                            $form['available_slots_fd']['slot_container_fd']['slot_group_fd_'. $i]['slot_time_fd_'. $i] = [
                                '#type' => 'textfield',
                                '#default_value' => $full_times_array[$i] ?? '',
                                '#size' => 5, // Size of the input.
                                '#maxlength' => 5, // Max length for HH:mm.
                                '#attributes' => [
                                    'placeholder' => 'HH:MM', // Placeholder for expected input.
                                    'class' => ['timepicker w-auto'], // Class for JS to target.
                                ],
                                '#pattern' => '^(?:[01]\d|2[0-3]):[0-5]\d$', 
                                '#attached' => [
                                    'library' => [
                                        'booking_calendar/booking_calendar.jquery_timepicker', // Attach the timepicker library.
                                    ],
                                ],
                            ];
                            
                            $form['available_slots_fd']['slot_container_fd']['slot_group_fd_'. $i]['slot_places_fd_'. $i] = [
                                '#type' => 'number',
                                '#default_value' =>  $full_slots_array[$full_times_array[$i]] ?? '',
                                '#min' => 0,
                                '#max' => 100,
                                '#size' => 3,
                                '#attributes' => [
                                    'placeholder' => '#Places', // Placeholder for expected input.
                                    'class' => ['w-auto'],
                                ],
                            ];
                        }
                        
                        // FULL DAYS - BUTTONS
                        
                        // Button to add another field.
                        $form['available_slots_fd']['slot_container_fd']['add_field_fd'] = [
                            '#type' => 'submit',
                            '#value' => $this->t('Add Time slot @number', ['@number' => ($num_fields['fd']+1) ] ), 
                            '#submit' => ['::addFieldFd'],
                            '#ajax' => [
                                'callback' => '::ajaxCallbackFd',
                                'wrapper' => 'dynamic-field-wrapper-fd',
                            ],
                        ];
                        
                        // Button to remove the last field.
                        if ($num_fields['fd'] > 1) {
                            
                            $form['available_slots_fd']['slot_container_fd']['remove_field_fd'] = [
                                '#type' => 'submit',
                                '#value' => $this->t('Remove Time slot @number', ['@number' => $num_fields['fd']] ), 
                                '#submit' => ['::removeFieldFd'],
                                '#ajax' => [
                                    'callback' => '::ajaxCallbackFd',
                                    'wrapper' => 'dynamic-field-wrapper-fd',
                                ],
                            ];
                        }
                            
            // Fifth tab group.  [HALF DAYS]
            $form['available_slots_hd'] = [
                 '#type' => 'details',
                 '#title' => $this->t('Half days - slots'),
                 '#group' => 'tabs',
            ];   
                            
                            // Create a container to hold the dynamic fields.
            $form['available_slots_hd']['slot_container_hd'] = [
                                '#type' => 'container',
                                '#attributes' => ['id' => 'dynamic-field-wrapper-hd'],
                            ];
                                
                            try{
                                $half_slots_array = json_decode($config->get('half_day_slots') ?? '[]', TRUE) ?? array(); 
                                $half_times_array = array_keys( $half_slots_array) ?? array();
                                
                            } catch (Exception $e) {
                                $half_slots_array = array();
                                $half_times_array = array();
                            }
                            
                                                 
                            for ($i = 0; $i < $num_fields['hd'];$i++) {
                                
                                
                               $form_state->get('num_fields');
                                
                               $form['available_slots_hd']['slot_container_hd']['slot_group_hd_'. $i] = [
                                    '#type' => 'details',
                                    '#title' => $this->t('Time slot @number', ['@number' => ($i +1)]),
                                    '#open' => TRUE,  // This controls whether the section is collapsed by default
                                    
                                ];                    
                                
                               $form['available_slots_hd']['slot_container_hd']['slot_group_hd_'. $i]['slot_time_hd_'. $i] = [
                                    '#type' => 'textfield',
                                   '#default_value' => $half_times_array[$i] ?? '',
                                    '#size' => 5, // Size of the input.
                                    '#maxlength' => 5, // Max length for HH:mm.
                                    '#pattern' => '^(?:[01]\d|2[0-3]):[0-5]\d$', 
                                    '#attributes' => [
                                        'placeholder' => 'HH:MM', // Placeholder for expected input.
                                        'class' => ['timepicker w-auto'], // Class for JS to target.
                                    ],
                                    '#attached' => [
                                        'library' => [
                                            'booking_calendar/booking_calendar.jquery_timepicker', // Attach the timepicker library.
                                        ],
                                    ],
                                ];
                                
                               $form['available_slots_hd']['slot_container_hd']['slot_group_hd_'. $i]['slot_places_hd_'. $i] = [
                                    '#type' => 'number',
                                   '#default_value' => $half_slots_array[$half_times_array[$i]] ?? '',
                                    '#min' => 0,
                                    '#max' => 100,
                                    '#size' => 3,
                                    '#attributes' => [
                                        'placeholder' => '#Places', // Placeholder for expected input.
                                        'class' => ['w-auto'],
                                    ],
                                ];
                            }
                            
                            
                   // HALF DAYS - BUTTONS
                   
                            // Button to add another field.
                            $form['available_slots_hd']['slot_container_hd']['add_field_hd'] = [
                                '#type' => 'submit',
                                '#value' => $this->t('Add Time slot  @number', ['@number' => ($num_fields['hd'] +1)] ), 
                                '#submit' => ['::addFieldHd'],
                                '#ajax' => [
                                    'callback' => '::ajaxCallbackHd',
                                    'wrapper' => 'dynamic-field-wrapper-hd',
                                ],
                            ];
                            
                            // Button to remove the last field.
                            if ($num_fields['hd'] > 1) { 
                                
                                $form['available_slots_hd']['slot_container_hd']['remove_field_hd'] = [
                                    '#type' => 'submit',
                                    '#value' => $this->t('Remove Time slot  @number', ['@number' => $num_fields['hd']] ), 
                                    '#submit' => ['::removeFieldHd'],
                                    '#ajax' => [
                                        'callback' => '::ajaxCallbackHd',
                                        'wrapper' => 'dynamic-field-wrapper-hd',
                                    ],
                                ];
                            }
                            
                            
            // Sixth tab group.
            $form['monthly_days'] = [
                '#type' => 'details',
                '#title' => $this->t('(Recurring) monthly days off'),
                '#group' => 'tabs',
            ];
                            $form['monthly_days']['recurring_monthly_dayoff'] = [
                                '#type' => 'textarea',
                                '#title' => $this->t('Enter the dates: DD,DD,... and Use coma as separator.'),
                                '#description' => $this->t('Eg: I keep the 1st, the 25th and the 26th of every month for my VIP customers.<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;No online booking will be allowed.<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Then my settings will be: 01,25,26'),
                                '#maxlength' => 92, // Max number of characters.
                                '#default_value' => $config->get('recurring_monthly_dayoff')  ?? '',
                                '#attributes' => [
                                    'id' => 'textarea_with_monthly',
                                    'class' => ['monthly-class'],  // Give an CLASS for JavaScript targeting.
                                ],
                                '#attached' => [
                                    'library' => [
                                        'booking_calendar/booking_calendar.textarea_with_chars_counter', // Attach JavaScript script.
                                    ],
                                ],
                                
                            ];
                            
                            $form['monthly_days']['chars_counter'] = [
                                '#markup' => '<div><small id="chars_counter_monthly"></small></div>',
                            ];
                            
                            
            // Seventh tab group.
            $form['public_days'] = [
                '#type' => 'details',
                '#title' => $this->t('(Recurring) annual days off / Public holidays'),
                '#group' => 'tabs',
            ];
                            $form['public_days']['recurring_dayoff'] = [
                                '#type' => 'textarea',
                                '#title' => $this->t('Enter the dates: MM-DD,MM-DD,... and Use coma as separator.'),
                                '#description' => $this->t('Eg: My establishment is closed every 4th of July for the Independance day, on Christmas and on my birthday 27th of March.<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;No online booking will be allowed.<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Then my settings will be: 07-04,03-27,12-25'),
                                '#maxlength' => 300, // Max number of characters.
                                '#default_value' => $config->get('recurring_dayoff')  ?? '',
                                '#attributes' => [
                                    'id' => 'textarea_with_annual',
                                    'class' => ['annual-class'],  // Give an CLASS for JavaScript targeting.
                                ],
                                '#attached' => [
                                    'library' => [
                                        'booking_calendar/booking_calendar.textarea_with_chars_counter', // Attach JavaScript script.
                                    ],
                                ],
                                
                            ];
                            
                            $form['public_days']['chars_counter'] = [
                                '#markup' => '<div><small id="chars_counter_annual"></small></div>',
                            ];
                                                        
                            
            // Eighth tab group.
            $form['holiday_vacation_days'] = [
                '#type' => 'details',
                '#title' => $this->t('Days off / Vacations / Emergency'),
                '#group' => 'tabs',
            ];
                            $form['holiday_vacation_days']['vacation_days'] = [
                                '#type' => 'textarea',
                                '#title' => $this->t('Enter the dates: YYYY-MM-DD,YYY-MM-DD,... and Use coma as separator.'),
                                '#description' => $this->t('Eg: I will need to go see my parents tomorrow 19th and again in 26th of September 2024.<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;No online booking will be allowed.<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Then my settings will be: 2024-09-19,2024-09-26'),
                                '#maxlength' => 500, // Max number of characters.
                                '#default_value' => $config->get('vacation_days')  ?? '',
                                '#attributes' => [
                                    'id' => 'textarea_with_vacations',
                                    'class' => ['email-class'],  // Give an CLASS for JavaScript targeting.
                                ],
                                '#attached' => [
                                        'library' => [
                                            'booking_calendar/booking_calendar.textarea_with_chars_counter', // Attach JavaScript script.
                                        ],
                                 ],
                                
                            ];
                            
                            $form['holiday_vacation_days']['chars_counter'] = [
                                '#markup' => '<div><small id="chars_counter_vacations"></small></div>',
                            ];
                            
            // Nineth tab group.
            $form['notification_email_settings'] = [
                '#type' => 'details',
                '#title' => $this->t('Notification - Email settings'),
                '#group' => 'tabs',
            ];
                            
                            $form['notification_email_settings']['email_notif_status'] = [
                                '#type' => 'checkbox',
                                '#title' => $this->t('Enabled'),
                                '#default_value' =>$config_booking_form->get('handlers')['email']['status'] ?? false,
                            ];
                            $form['notification_email_settings']['cc_email'] = [
                                '#type' => 'email',
                                '#title' => $this->t('CC email'),
                                '#default_value' => $config_booking_form->get('handlers')['email']['settings']['cc_mail'] ?? '',
                            ];
                            $form['notification_email_settings']['bcc_email'] = [
                                '#type' => 'email',
                                '#title' => $this->t('BCC email'),
                                '#default_value' => $config_booking_form->get('handlers')['email']['settings']['bcc_mail']  ?? '',
                            ];
                            $form['notification_email_settings']['reply_to_email'] = [
                                '#type' => 'email',
                                '#title' => $this->t('Reply to email'),
                                '#default_value' => $config_booking_form->get('handlers')['email']['settings']['bcc_mail']  ?? '',
                            ];
                            $form['notification_email_settings']['subject'] = [
                                '#type' => 'textfield',
                                '#title' => $this->t('Subject'),
                                '#default_value' => $config_booking_form->get('handlers')['email']['settings']['subject']  ?? '',
                            ];
                            $form['notification_email_settings']['email_body'] = [
                                '#type' => 'textarea',
                                '#title' => $this->t('Message'),
                                '#description' => 'Use <b>[webform_submission:values:surname]</b>, <b>[webform_submission:values:booking_datetime]</b> and <b>[webform_submission:values:description]</b> in your message to insert the Fullname, the date of booking and/or the additional information provided by the booker',
                                '#maxlength' => 500, // Max number of characters.
                                '#default_value' => $config_booking_form->get('handlers')['email']['settings']['body']  ?? '',
                                '#attributes' => [
                                    'id' => 'textarea_with_email',
                                    'class' => ['email-class'],  // Give an CLASS for JavaScript targeting.
                                ],
                                '#attached' => [
                                    'library' => [
                                        'booking_calendar/booking_calendar.textarea_with_chars_counter2', // Attach JavaScript script.
                                    ],
                                ],
                            ];
                            
                            $form['notification_email_settings']['chars_counter'] = [
                                '#markup' => '<div><small id="chars_counter_email"></small></div>',
                            ];
                            
        // Tenth tab group.
        $form['confirmation_settings'] = [
            '#type' => 'details',
            '#title' => $this->t('Booking confirmation'),
            '#group' => 'tabs',
        ];
                            $form['confirmation_settings']['confirmation_message'] = [
                                '#type' => 'textfield',
                                '#title' => $this->t('Confirmation message'),
                                '#description' => $this->t('Text to display after a successful booking'),
                                '#default_value' => $config_booking_form->get('settings')['confirmation_message']  ?? 'Your Appointment is confirmed',
                                
                            ];
                            
                            $form['confirmation_settings']['confirmation_url'] = [
                                '#type' => 'textfield',
                                '#title' => $this->t('Confirmation - Redirect to URL'),
                                '#description' => $this->t('where to redirect user after booking.<br />NOTE: Use relative path. Start with forward slash.'),
                                '#default_value' => $config_booking_form->get('settings')['confirmation_url']  ?? '/calendar',
                                
                            ];
                        
        return parent::buildForm($form, $form_state);
    }
    
    
   /**
   *  FULL DAYS: Ajax callback to refresh the dynamic fields wrapper.
   */
   public function ajaxCallbackFd(array &$form, FormStateInterface $form_state) {
       
       return $form['available_slots_fd']['slot_container_fd']; 
   }
   
   /**
    *  HALF DAYS: Ajax callback to refresh the dynamic fields wrapper.
    */
   public function ajaxCallbackHd(array &$form, FormStateInterface $form_state) {
       return $form['available_slots_hd']['slot_container_hd'];
       
   }
   

   /**
    *  FULL DAYS: Submit handler to add a field.
    */
   public function addFieldFd(array &$form, FormStateInterface $form_state) {
       $num_fields = $form_state->get('num_fields');
       $num_fields['fd']  = $num_fields['fd'] + 1;
       $form_state->set('num_fields', $num_fields);
        
       $form_state->setRebuild(TRUE);
   }
   
   /**
    *  HALF DAYS: Submit handler to add a field.
    */
   public function addFieldHd(array &$form, FormStateInterface $form_state) {
       $num_fields = $form_state->get('num_fields');
       $num_fields['hd']  = $num_fields['hd'] + 1;
       $form_state->set('num_fields', $num_fields);
       
       $form_state->setRebuild(TRUE);
   }
   

   /**
    *  FULL DAYS: Submit handler to remove the last field.
    */
    public function removeFieldFd(array &$form, FormStateInterface $form_state) {
         $num_fields = $form_state->get('num_fields');
         if ($num_fields['fd'] > 1) {
             
             $num_fields['fd']  = $num_fields['fd'] - 1;
             $form_state->set('num_fields', $num_fields);
         }
         $form_state->setRebuild(TRUE);
     }

    
    /**
      *  HALF DAYS: Submit handler to remove the last field.
      */
     public function removeFieldHd(array &$form, FormStateInterface $form_state) {
         $num_fields = $form_state->get('num_fields');
         if ($num_fields['hd'] > 1) {
             
             $num_fields['hd']  = $num_fields['hd'] - 1;
             $form_state->set('num_fields', $num_fields);
         }
         $form_state->setRebuild(TRUE);
     }
     
     /**
      * {@inheritdoc}
      */
     public function validateForm(array &$form, FormStateInterface $form_state) {


         //////  START - SLOTS CHECK  ///////////////////////////////
         
         $num_fields = $form_state->get('num_fields');
         
         $this->half_day_slots = array();
         $this->num_hd_entry_to_store = 0;
         
         for ($i = 0; $i < $num_fields['hd']; $i++) {
             
             
             $hd_time =  $form_state->getValue('slot_time_hd_' . $i);
             $hd_places = $form_state->getValue('slot_places_hd_' . $i);
             //For a slot to be valid a TIME should be set + Number of PLACES need to be greater than 0
             if(isset($hd_places) && isset($hd_time) && trim($hd_time) !== ''  && $hd_places > 0 &&  trim($hd_places) !== ''){
                 $this->half_day_slots[$hd_time] = $hd_places;
                 $this->num_hd_entry_to_store++;
             }
         }
         
         $this->full_day_slots = array();
         $this->num_fd_entry_to_store = 0;
         
         for ($i = 0; $i < $num_fields['fd']; $i++) {
             
             $fd_time =  $form_state->getValue('slot_time_fd_' . $i);
             $fd_places = $form_state->getValue('slot_places_fd_' . $i);
             //For a slot to be valid a TIME should be set + Number of PLACES need to be greater than 0
             if(isset($fd_places) && isset($fd_time) && trim($fd_time) !== ''  && $fd_places > 0 &&  trim($fd_places) !== ''){
                 
                 $this->full_day_slots[$fd_time] = $fd_places;
                 $this->num_fd_entry_to_store++;
             }
         }
         
         
         if($this->num_fd_entry_to_store === 0 && count(array_diff_assoc($form_state->getValue('cal_half_days'), $form_state->getValue('cal_closed_days'))) > 0){
             
             $form_state->setErrorByName('full_day_slots', $this->t('You needs to define time slot(s) for <strong>Full days</strong>.'));
         }else{
             ksort($this->full_day_slots);  //to sort the array by its keys
         }
         
         if($this->num_hd_entry_to_store === 0 && array_sum($form_state->getValue('cal_half_days')) > 0){
             
             $form_state->setErrorByName('half_day_slots', $this->t('You needs to define time slot(s) for <strong>Half days</strong>.'));
         }else{
             
             ksort($this->half_day_slots); //to sort the array by its keys
         }
         
        //////  END - SLOTS CHECK  ///////////////////////////////
         
         $array_conflictual_choices = array();
         
         
         foreach ($form_state->getValue('cal_closed_days') as $key => $value) {
             // At the same time half day and closed day
             if($form_state->getValue('cal_half_days')[$key] !== 0 && $value !== 0){ 
                 $array_conflictual_choices[] = $this->days_names[$key-1];  // full day
             }
         }
         
         if(count($array_conflictual_choices) > 0){
             
             $form_state->setErrorByName('cal_closed_days', $this->t('A day can\'t be both Closed and Opened.<br /> Have a look at the selected "Closed days" and "Half days" - '.implode(', ', $array_conflictual_choices)));
         }

         // Regular expression for YYYY-MM-DD.
         $pattern1 = '/^\d{4}-\d{2}-\d{2}(,\d{4}-\d{2}-\d{2})*$/';
         
         // Regular expression for MM-DD.
         $pattern2 = '/^\d{2}-\d{2}(,\d{2}-\d{2})*$/';
         
         // Regular expression for DD.
         $pattern3 = '/^\d{2}(,\d{2})*$/';
               
         // Replace multiple new lines, semicolon and comma with one comma
         //Remove and Trim new lines, \t  and spaces
         
         
         $dates_value =  trim(preg_replace('/[;,\\n]+/', ',', preg_replace('/\s+/', ',', Xss::filter($form_state->getValue('recurring_monthly_dayoff')))), " \t\n\r\0\x0B,;");
         if ($dates_value !=='' && !preg_match($pattern3, $dates_value)) {
             $form_state->setErrorByName('vacation_days', $this->t('(Recurring) monthly days off: The dates must be in the format DD,DD,...,DD'));
         }else{
             
             $form_state->setValue('recurring_monthly_dayoff', $this->removeDuplicate($dates_value));
         }
         
         $dates_value =  trim(preg_replace('/[;,\\n]+/', ',', preg_replace('/\s+/', ',', Xss::filter($form_state->getValue('recurring_dayoff')))), " \t\n\r\0\x0B,;");
         
         if ($dates_value !=='' && !preg_match($pattern2, $dates_value)) {
             $form_state->setErrorByName('recurring_dayoff', $this->t('(Recurring) annual days off / Public holidays: The dates must be in the format MM-DD,MM-DD,...,MM-DD'));
         }else{
             
             $form_state->setValue('recurring_dayoff', $this->removeDuplicate($dates_value));
         }
         
         
         $dates_value =  trim(preg_replace('/[;,\\n]+/', ',', preg_replace('/\s+/', ',', Xss::filter($form_state->getValue('vacation_days')))), " \t\n\r\0\x0B,;");
         
         if ($dates_value !=='' && !preg_match($pattern1, $dates_value)) {
             $form_state->setErrorByName('vacation_days', $this->t('Days off / Vacations / Emergency: The dates must be in the format YYYY-MM-DD,YYYY-MM-DD,...,YYYY-MM-DD'));
         }else{
             
             $form_state->setValue('vacation_days', $this->removeDuplicate($dates_value));
         }
         
         // To validate the PATH entered into the field "Confirmation - Redirect to URL"
         if(!preg_match('/^\/[a-zA-Z0-9\-._~:\/]*(\?[a-zA-Z0-9=&]*)?(#[a-zA-Z0-9\-_]*)?$/', trim($form_state->getValue('confirmation_url')))){
                
             $form_state->setErrorByName('confirmation_url', $this->t('Confirmation - Redirect to URL: Please make sure the relative PATH is well written and start with a forward slash.'));
         }
         
         
         // To check if the notification can be enabled
         if(( empty($form_state->getValue('subject')) || empty($form_state->getValue('email_body')) ) && $form_state->getValue('email_notif_status') ){
         
             $form_state->setErrorByName('email_notif_status', $this->t('The fields "Subject" and "Message" need to be filled before enabling the email notifications.'));
         }
         
         parent::validateForm($form, $form_state);
     }
     
     
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        
        
  //      \Drupal::logger('booking_calendar')->info('<pre>' . print_r($form_state->getValues(), TRUE) . '</pre>'); 
        
        $array_opened_days = array();
        foreach ($form_state->getValue('cal_closed_days') as $key => $value) {
            // Check if the key exists in both arrays and if both values are 0
            if ($form_state->getValue('cal_half_days')[$key] !== 0 && $value === 0) {
                $array_opened_days[$key] = 'h'; // half day
            }else if($form_state->getValue('cal_half_days')[$key] === 0 && $value === 0){
                $array_opened_days[$key] = 'f';  // full day
            }else{
                $array_opened_days[$key] = 'c';  // full day
            }
        }
        
        // Save the form values in the configuration.
        $this->config('booking_calendar.settings')
        ->set('title_display', $form_state->getValue('title_display'))
        ->set('title_value', $form_state->getValue('title_value'))
        ->set('cal_lang', $form_state->getValue('cal_lang'))
        ->set('cal_first_day', $form_state->getValue('cal_first_day'))
        ->set('cal_closed_days', $form_state->getValue('cal_closed_days'))
        ->set('cal_half_days', $form_state->getValue('cal_half_days'))
        ->set('cal_opened_days', $array_opened_days)
        ->set('cal_opening_window', $form_state->getValue('cal_opening_window'))
        ->set('current_day_bookable', $form_state->getValue('current_day_bookable'))
        
        // Replace multiple new lines, semicolon and comma with one comma
        //Remove and Trim new lines, \t  and spaces
        ->set('vacation_days', $form_state->getValue('vacation_days'))
        ->set('recurring_dayoff', $form_state->getValue('recurring_dayoff'))
        ->set('recurring_monthly_dayoff', $form_state->getValue('recurring_monthly_dayoff'))
        
        ->set('num_fields_hd', $this->num_hd_entry_to_store)
        ->set('num_fields_fd', $this->num_fd_entry_to_store)
        
        // Save the dynamic fields as arrays in the configuration.
        ->set('half_day_slots', json_encode($this->half_day_slots))
        ->set('full_day_slots', json_encode($this->full_day_slots))
        
        ->save();
       
        // Save the WEBFORM NOTIFICATION form Settings
        
        $config_booking_form = $this->config('webform.webform.booking_calendar');
        $config_booking_form_handlers = $config_booking_form->get('handlers');
        $config_booking_form_settings = $config_booking_form->get('settings');
        
        // HANDLERS
        $config_booking_form_handlers['email']['status'] = $form_state->getValue('email_notif_status');
        $config_booking_form_handlers['email']['settings']['cc_mail'] = $form_state->getValue('cc_email');
        $config_booking_form_handlers['email']['settings']['bcc_mail'] = $form_state->getValue('bcc_email');
        $config_booking_form_handlers['email']['settings']['reply_to'] = $form_state->getValue('reply_to_email');
        $config_booking_form_handlers['email']['settings']['subject'] = $form_state->getValue('subject');
        $config_booking_form_handlers['email']['settings']['body'] =  Xss::filter($form_state->getValue('email_body')); // Xss::filter() to only keep Basic HTML
        
        
        //SETTINGS
        $config_booking_form_settings['confirmation_message'] = trim(Xss::filter( $form_state->getValue('confirmation_message')));
        $config_booking_form_settings['confirmation_url'] = trim($form_state->getValue('confirmation_url'));
        
        
        // Set the updated nested configuration back.
        $config_booking_form
        ->set('handlers', $config_booking_form_handlers)
        ->set('settings', $config_booking_form_settings)
        ->set('title', $form_state->getValue('confirmation_form_title'))
        ->save();
        
        // Clear the whole cache
        drupal_flush_all_caches();  
        
        // Optionally, if you want to manually delete cache items for your module:
       // $this->clear_booking_calendar_data_cache();
    }
    
    
    private function removeDuplicate($stringToAssess){
        
        if($stringToAssess !=='' ){
            $dates_array = explode(',', $stringToAssess);
            $unique_dates = array_unique($dates_array);
            return implode(',', $unique_dates);
        }
        return '';
    }
    
    private function clear_booking_calendar_data_cache() {

        // If you want to manually delete cache items for your module:
        $cid = 'booking_calendar'; // Module cache ID
        \Drupal::service('cache.data')->delete($cid);
    }
}
