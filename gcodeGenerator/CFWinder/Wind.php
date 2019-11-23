<?php
// BRANCH: Nosecone

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Wind
 * 
 * This incarnation of Wind is for creating a Nose COne.
 * It is assumed that the nose cone is widest on LHS, near the motor, where the wind starts
 * It is also assumed we have a constant radius component of the nose on LHS, which is what the coupler slips
 * into. 
 * 
 * We Wind each pass starting off like a standard tube. Then when we get to a certain point, we calculate all the points
 * for the 3 axis to create the Nose Cone
 * Then at some point during the wind of the Nose Cone, BEFORE reaching the RHS, we stop this part, and then finish off, like we do for Tubes.
 * Then we spin certain amount and then go back. The important thing is that when we get back, we are BACK to where we started...
 * Then we do one revolution + BIT (to advance CF) and then REPEAT.
 * 
 * Where we stop the wind on the right depends our the properties we want at the RHS.
 * 
 *
 * @author joema
 */
class Wind {
    // NOSE CONE CONFIGURATION
    private $nose_cone_start_x;               // Where the Nose Cone starts  (and radius starts to decrease)
    private $nose_cone_stop_x;                // Where nose cone ends (and radius stops decreasing)
    // IT is important to note that nose_cone_stop_x > nose_cone_start_x
    private $nose_cone_top_radius;            // Radius at "top" of Nose Cone
    private $nose_cone_cf_closest_approach_to_tip;      // Closest distance in meters from TIP that the CF gets to
    private $nose_cone_wind_time_per_pass;              // There and Back in seconds
    private $seconds_per_tick;               // How long does each data point occupy in time.
    private $nose_cone_num_data_points;       // Number of data points per pass
    private $nose_cone_points;                // Where we collect all the incremental data points
    private $nose_cone_cf_double_up;          // # of layers next to each other
    
    // Variables used to help define how we create the cylindrical section of the NoseCone.
    private $nose_cone_cf_angle;              // The CF angle at BASE of the nose cone (used for cylinder)
    private $cylinder_transition_start_wind;  // Cylindrical portion of the nose cone (for all layers) - the Angle in degrees
    private $cylinder_transition_end_wind;    // Cylindrical portion of the nose cone (for all layers) - the Angle in degrees
    private $min_spindle_turn;                // Minimum spin turn at Lead in
    
    private $turn_around_splits;              // How many times you need to return back to the X = 0 position before you are back 
                                              // to Mandrel angular position.
                                              // 
                                              // The intention here is that rather than have successive passes RIGHT next to each other, we space them out.
                                              // by 2 x pi()/$this->turn_around_splits radians.
                                              // 
                                              // e.g. if this is four, then each time you return, you spin:-
                                              //    Back to beginning
                                              //    PLUS 
                                              //    AMount to bring you 2 x pi()/$this->turn_around_splits around
                                              // 
                                              //   If this amount is less than 180 degrees, we add an additional 360 degrees on to it.
                                              // 
                                              // When we get to the beginning, (i.e. we do $this->turn_around_splits passes, we ALSO add ADVANCEMENT to move forward!!)
                                              // This is so we don't overlap.
                                              // 
 
    
    
                                             
    
    /* The CF takes a "straight line", a geodesic. This is to ensure tight (no slip fit) 
     * 
     */

    private $mandrel_speed_conversion;        // By Default, Mandrel Speed in LinuxCNC is in mm/min...
                                              // to use this, multiple the angular speed in radians by this number.
    
    private $mandrelRadius;                   // Radius of Mandrel - meters
    private $eyeletDistance;                  // Perpendicular distance from mandrel surface to eyelet dispensing CF.
    private $eyeletHeight;                    // Height above Mandrel center
    

    
    private $layers;                          // Layers - on definition for all!
    private $cf_width;                        // Width of fiber in Meters
    private $straight_feed_rate;              // Rate of laydown of CF during straight sections.
    private $spindle_direction;               // Direction the spindle spins. Clockwise is default = +1
    
    private $number_of_layers;                // Number of layers
    private $lead_distance;                   // The maximum distance by which the dispenser will lead the Mandrel/CF contact.
    private $transition_end_length;           // This is just the Lead distance, but we define it to out of clarity.
    private $layer_properties;                // Properties of layers
    
    private $optimum_z_angle;                 // The optimum angle for the cf_angle required...to minimize CF width reduction.
    private $transition_steps_in = 15;         // How many steps to break the transition into.
    private $transition_steps_out = 15;        // How many steps to break the transition into...for OUT part of pass.
    
    // Self-imposed limits
    private $max_feed_rate = 12000;           // Maximum feed rate (mm/min)
    private $min_feed_rate = 500;             // Minimum feed rate - used at ends
    private $transition_feed_rate;            // Feed rate of the transition
    
    
    private $fudge_factor = 1.0;              // Trying to reduce chance of SLIP.  IT is basically a fudge factor which acknowledges errors in measuring and the whole system.
                                              // We increase the angle by which we advance the Z-axis by a little. At present this is NOT used during transition at end of pass.

    
    private $max_x;                         // Max position the Train is moved...we work this out, so we know how far to move the heat gun at the end.
    
    
    private $current_x;                     // Units are mm - This is the train
    private $current_s;                     // Units are degrees - this is the spindle  (s is for spindle)
    private $current_z;                     // Units are degrees - this is third axis...using z-axis to control
    private $current_pass;                  // The Pass we are on.
     
    private $start_x;                       // Starting position of Train - meters    
    private $start_s;                       // Starting position of Spindle - Degrees   (s is for spindle)
    private $gcodes;                        // Array of codes
    public  $sig_figures;                   // Number of digits after decimal point
    public  $cf_weight_per_meter;           // 1 meter of Carbon Fiber weighs 0.8grams
    
    public  $wind_time;                     // Time required to wind.
    
    // Length of CF - each layer
    public  $length;
    
    // Holds the angle required and specific states
    private $transition_in_schedule;
    private $transition_out_schedule;
    
    // Hold the Moves required
    private $transition_in_move;
    private $transition_out_move;
    
    

// s_angle is the change each time ... Total of s_angle is 90 degrees 
// z_angle is the absolute angle
// x_travel is the absolute position.
// cf_angle is the ABSOLUTE angle
    

    public function __construct($mandrelRadius, $eyeletDistance, $eyeletHeight, $cf_width, $transition_feed_rate, $straight_feed_rate,
                                $spindle_direction, $start_x=0,  $start_s=0, $start_z=0,
                                $number_of_layers, $cylinder_transition_start_wind, $cylinder_transition_end_wind, 
                                $nose_cone_start_x, $nose_cone_stop_x, $nose_cone_top_radius, $nose_cone_cf_closest_approach_to_tip) {
        
        
        
        // Basic dimensions of set-up        
        $this->eyeletDistance      = $eyeletDistance;
        $this->eyeletHeight        = $eyeletHeight;
        $this->mandrelRadius       = $mandrelRadius;
        $this->cf_width            = $cf_width;
        $this->spindle_direction   = $spindle_direction;
        
        $this->mandrel_speed_conversion = 0.0381;
        
        // Layers we want to create
        $this->number_of_layers    = $number_of_layers;
        $this->nose_cone_cf_angle  = 99999; // An absurd number that will not be min
        
        
        // Starting position
        $this->start_x = $start_x;
        $this->start_s = $start_s;
        $this->start_z = $start_z;
        
        // Variables to track current location
        $this->current_x = $start_x;
        $this->current_s = $start_s;
        $this->current_z = $start_z;
        
        
        $this->gcodes = array();
        
        
        $this->cf_weight_per_meter = 0.8;
        $this->sig_figures = 5;
        $this->wind_time = 0;
        
        
        $this->transition_feed_rate = $transition_feed_rate;
        $this->straight_feed_rate   = $straight_feed_rate;
        
        
        // Nose Cone parameters
        $this->nose_cone_start_x    = $nose_cone_start_x;
        $this->nose_cone_stop_x     = $nose_cone_stop_x;
        $this->nose_cone_top_radius = $nose_cone_top_radius;
        $this->nose_cone_cf_closest_approach_to_tip = $nose_cone_cf_closest_approach_to_tip;
        
        
        $this->nose_cone_cf_double_up = 2;
        
        
        $this->nose_cone_wind_time_per_pass         = 10;     // Seconds to do there and back
        $this->nose_cone_num_data_points            = 200;
        $this->seconds_per_tick                     = 0.1;
        
        $this->cylinder_transition_start_wind = $cylinder_transition_start_wind;
        $this->cylinder_transition_end_wind   = $cylinder_transition_end_wind;
        $this->min_spindle_turn               = 180; // Constant until I feel it is better as a parameter
        
        // May make this parameter driven later.
        $this->turn_around_splits = 4;

    }
    
    
    public function getNoseConeStartX() {
        return $this->nose_cone_start_x;
    }    

    public function getNoseConeStopX() {
        return $this->nose_cone_stop_x;
    }    

    public function getNoseConeTopRadius($layer) {
        return $this->nose_cone_top_radius;
    }        
    
    public function getLeadDistance($layer) {
        return $this->layer_properties[$layer]['lead_distance'];
    }    
    
    public function getNumberOfLayers() {
        return $this->number_of_layers;
    }
   
    public function getNoseConeCFAngle() {
        return $this->nose_cone_cf_angle;
    }
    
    public function getOptimumZAngle() {
        return $this->optimum_z_angle;
    }
    
    public function getTransitionStartWind() {
        return $this->cylinder_transition_start_wind;
    }
    
    public function getTransitionEndWind() {
        return $this->cylinder_transition_end_wind;
    }
    
    public function getStraightFeedRate() {
        return $this->straight_feed_rate;
    }

    public function getTransitionFeedRate() {
        return $this->transition_feed_rate;
    }
    
    public function getSpindleDirection() {
        return $this->spindle_direction;
    }
    
       
 
    /*
    public function getCFWeight() {
             return $this->cf_weight_per_meter * $this->calculateActualCFLengthRequiredOneLayer();
    }  
     * 
     */
    

    
    
    public function getCFWidth() {
        return $this->cf_width;
    }

    public function getMandrelCircumference() {
        return 2 * pi() * $this->mandrelRadius;
    }
    
    public function getMandrelDiameter() {
        return 2 * $this->mandrelRadius;
    }


    public function getMandrelRadius() {
        return $this->mandrelRadius;
    }    
    
    public function getEyeletDistance() {
        return $this->eyeletDistance;
    }       
    
    public function getEyeletHeight() {
        return $this->eyeletHeight;
    }  
    
    

    
 
    
    
    /*
     * This is the distance we need to advance (meters) tangentially to ensure
     * there is no gap and no overlap of the CF fiber.
     */
    public function idealCFAdvancement() {
        return $this->cf_width /cos($this->getNoseConeCFAngle() * pi()/180);
    }
      
 
    /*
     * This is the distance we need to advance (meters) tangentially to ensure
     * there is no gap and no overlap of the CF fiber.
     */
    public function idealCFAdvancementAngle() {
        return (180/pi()) * $this->idealCFAdvancement() / $this->mandrel_speed_conversion;
    }
        
    

    
    


    
    /*
     * calculate startTransitionXDistance($layer)
     * 
     * Calculate how far we move the carriage for the START transition
     * 
     * This is NOT the start of the tube since the CF trails the dispenser!!
     * 
     */
    public function startTransitionXDistance($layer)
    {
        return $this->layer_properties[$layer]['transition_start_length'];
    }
    

    

    

    

    /* 
     * This calculates length of CF for just the straight section (not including transitions)
     */
    public function calculateCFLength($cf_angle, $x_length) {
        $len = abs($x_length / cos(pi() * $cf_angle/180));  // Changed to Useful Tube Length
        return $len;
    }    
    

   
    
    /*
     * Given the Y travel distance (degrees) to take place, we want to calculate how far to move the carriage
     * 
     * Result returned is in meters
     * 
     */
    public function calculateXTravelMeters($s_angle, $cf_angle) {
       return ($s_angle * pi()/180) * $this->mandrelRadius/ tan(pi() * $cf_angle/180);
    }
    
    
    /*
     * Given the X travel distance and CF Angle, work out the rotation distance. M - same units as X
     * 
     * Result returned is in meters
     * 
     */
    public function calculateYTravelMeters($x_travel, $cf_angle) {
       return $x_travel * tan(pi() * $cf_angle/180);
    }

    
    /*
     * Given the X travel distance and CF Angle, work out the rotation distance. M - same units as X
     * 
     * Result returned is in degrees
     * 
     */
    public function calculateYTravelDegrees($x_travel, $cf_angle) {
       return (180 / pi()) * $this->calculateYTravelMeters($x_travel, $cf_angle) / $this->getMandrelRadius();
    }
    
    
    public function addTime($wind_time) {
        $this->wind_time = $this->wind_time + $wind_time;
    }


    public function getTime() {
        return $this->wind_time;
    }

        
    /*
     * Work out the angle (y-axis) that the CF makes....to be a tangent with the Mandrel
     * 
     * y-axis is the Spindle axis.
     */
    public function calculateCFYAngle($radius, $x_gap, $y_gap) {
        $error = 1/1000;
        for ($i=0; $i< 90; $i=$i+0.1) {
            // $lhs = $this->mandrelRadius;
            $lhs = $radius;
            // $rhs = sin(pi() * $i / 180) * ($this->mandrelRadius + $this->eyeletDistance) + $this->eyeletHeight * cos(pi() * $i / 180);
            $rhs = sin(pi() * $i / 180) * ($radius + $x_gap) + $y_gap * cos(pi() * $i / 180);
           
            if (abs($rhs - $lhs) < $error) {
                break;
            }
        }
        return $i;   
    }
    
    
    /*
     * Determine vector from origin to mandrel surface where CF intersects (excluding z-axis)... i.e assume 2D
     */
    public function deriveVectorOriginMandrel($angle) {
        
        $vector[0] = $this->mandrelRadius * sin(pi() * $angle / 180);
        $vector[1] = $this->mandrelRadius * cos(pi() * $angle / 180);
        $vector[2] = 0;        
        
        return $vector;
    }
    
    
    /*
     * Determine vector from CF dispenser to the Mandrel surface.
     * 
     * We ignore the Z-Axis...i.e. we are looking in 2-D only here.
     */
    public function deriveVectorDispenserMandrel($angle) {
        $vector[0] = $this->mandrelRadius * sin(pi() * $angle/180) - ($this->mandrelRadius + $this->eyeletDistance);
        $vector[1] = $this->mandrelRadius * cos(pi() * $angle / 180) - $this->eyeletHeight;
        $vector[2] = 0;
        
        return $vector;
    }    
    
    public function vectorLength($vector) {
      $length = sqrt($vector[0] * $vector[0] + $vector[1] * $vector[1] + $vector[2] * $vector[2])    ;
      
      return $length;
    }
    
    
    public function vectorUnit($vector) {
        $length = sqrt($vector[0] * $vector[0] + $vector[1] * $vector[1] + $vector[2] * $vector[2])    ;
        
        $v[0] = $vector[0] / $length;
        $v[1] = $vector[1] / $length;
        $v[2] = $vector[2] / $length;
        
        return $v;
    }
    
    /*
     * Calculate maximum vector (v3 with z component) ... which will represent the CF when it leads the
     * intersection with the mandrel by the greatest distance.
     * 
     * This is the lead distance.
     * 
     */
    public function deriveMaxVectorDispenserMandrel($v2, $cf_angle) {
        
        $error = 0.25;
        // Start from 0 and go to 1 meter (1mm at a time)
        for ($i=0; $i< 1; $i=$i+0.0001) {
            
            $vector[0] = $v2[0];
            $vector[1] = $v2[1];
            $vector[2] = $i;
            
            $len = $this->vectorLength($vector);            
            
            $vector[0] = $vector[0]/$len;
            $vector[1] = $vector[1]/$len;
            $vector[2] = $vector[2]/$len;
                        
            $angle = acos($vector[2]) * 180 / pi();
                        
            if (abs($angle - $cf_angle) < $error) {
                break;
            }
        }
        return $i;
    }
    
    /*
     * Based on the distance from the dispenser to the point at which the CF hits the Mandrel, work
     * out the distance is based on angle of rotation.
     * 
     * $start_lead_distance - the distance (y axis) that the dispenser leads the point where CF meets mandrel
     * $mandrel_angle       - the angle by which we rotate the mandrel
     * $x_distance          - How far (in x axis) the CF is from dispenser to the point where CF meets the mandrel.
     * 
     */
    public function leadOutDistance($start_lead_distance, $mandrel_angle, $x_distance) {
        $xf = $start_lead_distance * exp(-1 * $this->mandrelRadius/abs($x_distance) * $mandrel_angle);
     
        
        return $xf;
    }
    
    
    /*
     * Based on the distance from the dispenser to the point at which the CF hits the Mandrel, work
     * out the distance is based on angle of rotation.
     * 
     * $start_lead_distance - the distance (y axis) that the dispenser leads the point where CF meets mandrel
     * $mandrel_angle       - the angle by which we rotate the mandrel
     * $x_distance          - How far (in x axis) the CF is from dispenser to the point where CF meets the mandrel.
     * 
     */
    public function leadInDistance($start_lead_distance, $mandrel_angle, $x_distance) {
        $xf = $start_lead_distance *(1 - exp(-$mandrel_angle * $this->mandrelRadius/abs($x_distance)));
        
        return $xf;
    }    
    
    
    /*
     *  Generate the "Out points" i.e. the positions of x,y,z axis for transistion (OUT)...i.e. getting to end of pass
     * 
     *  $mandrel_angle = angle we wish to pass during transition - degrees  (not radians)
     *  $steps         = number of steps to split the curve
     *  $start_lead_distance = distance (y axis) from CF/Mandrel -> CF Dispenser
     *  $x_distance    = distance (x axis) from the CF/Mandrel -> CF Dispenser
     * 
     */
    public function generateOutPoints($mandrel_angle, $steps, $start_lead_distance, $v3)
    {
        $step_size = $mandrel_angle / $steps;
        $cf_distance = $this->vectorLength($v3);
        $x_distance = $v3[0];
        
        for ($i = 0; $i <= $steps; $i++) {
            $y = $i * $step_size;
            $z = $this->leadOutDistance($start_lead_distance, $y * pi()/180, $cf_distance);
            
            $cf_vector[0] = $x_distance;
            $cf_vector[1] = $v3[1];
            $cf_vector[2] = $z;
                     
            $cf_vector = $this->vectorUnit($cf_vector);
            
            // Work out the angle by which we rotate the filament to ensure the filament width is not compromised
            $new_vector[0] = 0;
            $new_vector[1] = $cf_vector[1];
            $new_vector[2] = $cf_vector[2];
            $new_vector    = $this->vectorUnit($new_vector);
            $z_axis_angle = acos($new_vector[1]) * 180 / pi();
            
            // Work out angle of filament with resepect to the Mandrel axis which is the Z axis (cf_angle)
            $cf_angle = acos($cf_vector[2]) * 180 / pi();
            
            $this->transition_out_schedule[$i]['feedrate'] = $this->transition_feed_rate;
            $this->transition_out_schedule[$i]['s_angle']  = $y;
            $this->transition_out_schedule[$i]['z_angle']  = $z_axis_angle;   
            $this->transition_out_schedule[$i]['cf_angle'] = $cf_angle;
            
        }
    }

    
    public function sign($n) {
       return ($n > 0) - ($n < 0);
    }    
    
     /*
      *  Create the actual movements required for the IN transition.
      * 
      * We also calculate the final X position after this transition
      */
     public function createInMoveTransitionSchedule($layer) {
         $this->layer_properties[$layer]['transition_start_length'] = 0;
         $move_len = count($this->transition_in_schedule);
         for ($i = 1; $i < $move_len; $i++) {
             $feedrate  = $this->transition_in_schedule[$i]['feedrate'];  // Use the END feedrate
             $s_travel  = $this->transition_in_schedule[$i]['s_angle'] - $this->transition_in_schedule[$i-1]['s_angle'];   // Use the difference in angle
             $z_angle   = ($this->transition_in_schedule[$i-1]['z_angle'] + $this->transition_in_schedule[$i]['z_angle'])/2;   // Use the END z-angle
             $cf_angle  = $this->transition_in_schedule[$i]['cf_angle'];  // USe the END cf_angle
             
             // $x_travel = $this->calculateXTravelMeters($s_travel, $this->layers[$layer]['cf_angle']);
             $x_travel = $this->calculateXTravelMeters($s_travel, $this->getNoseConeCFAngle());
             
             $this->transition_in_move[$i-1]['feedrate'] = $feedrate;
             $this->transition_in_move[$i-1]['s_travel'] = $s_travel;
             $this->transition_in_move[$i-1]['z_angle']  = $z_angle * $this->fudge_factor; 
             $this->transition_in_move[$i-1]['cf_angle'] = $cf_angle;
             $this->transition_in_move[$i-1]['x_travel'] = $x_travel;
             
             $this->layer_properties[$layer]['transition_start_length'] = $this->layer_properties[$layer]['transition_start_length'] + $x_travel;
         }
         
     }
    
     
     
     /*
      *  Create the actual movements required for the OUT transition.
      */
     public function createOutMoveTransitionSchedule() {

         $move_len = count($this->transition_out_schedule);
         for ($i = 0; $i < $move_len-1; $i++) {
             $feedrate = $this->transition_out_schedule[$i]['feedrate'];  // Use the END feedrate
             $s_travel  = $this->transition_out_schedule[$i+1]['s_angle'] - $this->transition_out_schedule[$i]['s_angle'];  // Use the difference in angle
             $z_angle  = ($this->transition_out_schedule[$i]['z_angle'] + $this->transition_out_schedule[$i+1]['z_angle'])/2;    // Use the START z_angle
             
             $cf_angle = $this->transition_out_schedule[$i]['cf_angle'];  // USe the START cf_angle
             
             $this->transition_out_move[$i]['feedrate'] = $feedrate;
             $this->transition_out_move[$i]['s_travel']  = $s_travel;
             $this->transition_out_move[$i]['z_angle']  = $z_angle * $this->fudge_factor;
             $this->transition_out_move[$i]['cf_angle'] = $cf_angle;
             $this->transition_out_move[$i]['x_travel'] = 0;
         }         

     }
     
    
    
    /*
     *  Generate the "IN points" i.e. the positions of x,y,z axis for transistion (IN)...i.e. start of pass
     * 
     *  $mandrel_angle = angle we wish to pass during transition - degrees  (not radians)
     *  $steps         = number of steps to split the curve
     *  $start_lead_distance = distance (y axis) from CF/Mandrel -> CF Dispenser
     *  $x_distance    = distance (x axis) from the CF/Mandrel -> CF Dispenser
     * 
     */
    public function generateInPoints($mandrel_angle, $steps, $start_lead_distance, $v3)
    {
        $step_size = $mandrel_angle / $steps;
        $cf_distance = $this->vectorLength($v3);
        $x_distance = $v3[0];
        
        for ($i = 0; $i <= $steps; $i++) {
            $y = $i * $step_size;
            $z = $this->leadInDistance($start_lead_distance, $y * pi()/180, $cf_distance);
            
            $cf_vector[0] = $x_distance;
            $cf_vector[1] = $v3[1];
            $cf_vector[2] = $z;
                     
            $cf_vector = $this->vectorUnit($cf_vector);
                        
            // Work out the angle by which we rotate the filament to ensure the filament width is not compromised
            $new_vector[0] = 0;
            $new_vector[1] = $cf_vector[1];
            $new_vector[2] = $cf_vector[2];
            $new_vector    = $this->vectorUnit($new_vector);
            $z_axis_angle  = acos($new_vector[1]) * 180 / pi();           
            
            // Work out angle of filament with resepect to the Mandrel axis which is the Z axis (cf_angle)
            $cf_angle = acos($cf_vector[2]) * 180 / pi();
            
            $this->transition_in_schedule[$i]['feedrate'] = $this->transition_feed_rate;
            $this->transition_in_schedule[$i]['s_angle']  = $y;
            $this->transition_in_schedule[$i]['z_angle']  = $z_axis_angle;
            $this->transition_in_schedule[$i]['cf_angle'] = $cf_angle;
               
        }
    }

    
    
    public function deriveOptimumCFAngle ($v3, $z_distance) 
    {
        $v3[2] = $z_distance;
        $v3[0] = 0;
        
        $v = $this->vectorUnit($v3);
        
        return acos($v[1]) * 180 / pi() * $this->fudge_factor;
    }
    

    
    
    
    
    
public function generatePassCone($layer) {
        $this->current_pass++; 
        
        // Set Direction on START of pass
        $direction = 1;
        
        // The starting Angle - RIGHT AT BEGINNING
        $s_angle_starting_angle = 0;
        
        // Amount to rotate around...to get the different layers done.
        $advancement_number = ($this->current_pass - 1) % $this->nose_cone_cf_double_up;
        $this->addGcodeComment("ADVANCEMENT_NUMBER: " . $advancement_number);
            
            
        // In normal cylinders a Pass was in a SINGLE DIRECTION. i.e. you would need two passes to get back to where you started
        // For Nose Cone a Single Pass gets you there AND back...so each pass, except for the first, we need to advanced the CF.
        // Hence the simple condition below.
        if ($this->current_pass > 1) {
            
            $this->addGcodeComment("ADVANCING THE CF");
            $feedrate = $this->transition_feed_rate + 99;
            

            
            //$this->addGcodeComment("s_angle_starting_angle S: " . $s_angle_starting_angle);
            $this->addGcodeComment("CURRENT S: " . $this->current_s);
            
            // Work out how many degrees to get back to the beginning
            $degrees_back_to_beginning = 360 * (1 - (($this->current_s - $s_angle_starting_angle)/360 - floor(($this->current_s - $s_angle_starting_angle)/360)));            
            $this->addGcodeComment("Degrees back: " . $degrees_back_to_beginning);
            
            // Work out how far to advance it...AROUND the cone
            // current_pass-1     nose_cone_cf_double_up    =  VALUE
            // ----------------------------------------------------
            //      2-1 = 1     %     2                     =    1
            //      3-1 = 2     %     2                     =    0
            //      4-1 = 3     %     2                     =    1
            //      5-1 = 4     %     2                     =    0
            //      6-1 = 5     %     2                     =    1
            // .
            // .
            // .
            // So only on 3rd, 5th, 7th... passes we want to advance by turn_around_splits
            //
            //
            // 
            // In EXAMPLE below, the /2 (denominator) is the nose_cone_cf_double_up
            //                   and the %4 is because turn_around_splits = 4
            // ------------------------------------------------------------------------------------------------------------------------------------------------------
            //      1                                                                                            = 0
            //      2                 floor((2 - 1)/2) %4  =  0                                           x 90   = 0
            //      3                 floor((3 - 1)/2) %4  =  1                                           x 90   = 90
            //      4                 floor((4 - 1)/2) %4  =  1                                           x 90   = 90
            //      5                 floor((5 - 1)/2) %4  =  2                                           x 90   = 180
            //      6                 floor((6 - 1)/2) %4  =  2                                           x 90   = 180
            //      7                 floor((7 - 1)/2) %4  =  3                                           x 90   = 270
            //      8                 floor((8 - 1)/2) %4  =  3                                           x 90   = 270
            //      9                 floor((9 - 1)/2) %4  =  0                                           x 90   = 0
            //     10                 floor((10- 1)/2) %4  =  0                                           x 90   = 0
            //     11                 floor((11 - 1)/2) %4  =  1                                          x 90   = 90 
            //     12                 floor((12 - 1)/2) %4  =  1                                          x 90   = 90
            //     13                 floor((13 - 1)/2) %4  =  2                                          x 90   = 180
            //      .
            //      .
            //      .
            //      .       
            //      
            //      The above works when $advancement_number = 2 ... i.e. we have dups of the angle on right 0 0 90 90 180 180 ...
            //      If we were to have THREE CF strips adjacent, then $advancement_number would be 1,2,3 and we would have 0 0 0 90 90 90 180 180 180 ....
            //          
            // $spindle_move_amount = $degrees_back_to_beginning + (($this->current_pass % $this->turn_around_splits) - 1) * 360/$this->turn_around_splits;
            $spindle_move_amount = $degrees_back_to_beginning + floor(($this->current_pass  - 1)/$this->nose_cone_cf_double_up) % $this->turn_around_splits   * 360/$this->turn_around_splits;
      
            
            
            // The paramter $this->nose_cone_cf_double_up (Despite its name) indicates how may adjacent layers we have. Normally this would be two.
            // When equal to 2, it effectively doubles the width of the Carbon Fiber being laid down.
            // We go through all the adjacent layers...from 1 ... $this->$this->nose_cone_cf_double_up. Each time we do, we need to move around by ONE CF width.
            // The variable $advancement_number is what we use to keep track of which CF we are up to.
            $this->addGcodeComment("BEFORE: " . $spindle_move_amount);
            $this->addGcodeComment("ADVANCEMENT_NUMBER: " . $advancement_number);
            $spindle_move_amount = $spindle_move_amount + $advancement_number * $this->idealCFAdvancementAngle();
            $this->addGcodeComment("AFTER: " . $spindle_move_amount);
            
                        
            
            // $this->addGcodeComment("MOVE AMOUNT: " . $spindle_move_amount);
            // Extra Spindle turn is more of a guide for Nose Cone... it is the minimum degrees to turn at end
            // If to get back to beginning we exceed the minimum, we do just this.
            // If we don't, then we need to move back to beginning AND then move another 360 degrees.
            //
            // We assume $this->layers[$layer]['extra_spindle_turn']) < 360.
            //
            if ($spindle_move_amount < $this->min_spindle_turn) {
                $spindle_move_amount = $spindle_move_amount + 360;
            }
            $this->addGcodeComment("MOVE AMOUNT BASE: " . $spindle_move_amount);
            
            // Offset is number of times we need to pass before we apply a PERMANENT  CFAdvancement. Applied when we get back to the beginning.
            
            $offset = floor(($this->current_pass  - 1)/ ($this->turn_around_splits * $this->nose_cone_cf_double_up)) ;
            
            $this->addGcodeComment("OFFSET: " . $offset);
            
            
            // Advancement Angle
            $spindle_move_amount = $spindle_move_amount + $offset * $this->idealCFAdvancementAngle() * $this->nose_cone_cf_double_up;
            
            
            $this->addGcodeComment("FINAL MOVE AMOUNT: " . $spindle_move_amount);
            
            
            $s_travel = $spindle_move_amount;
            $z_angle = 0;
            $this->generateYCode($layer, $s_travel, $z_angle, $feedrate);
        } 
        
        
        
        $this->addGcodeComment("START OF TRANSISTION IN");
                
                
        // Do the Transition
        foreach ($this->transition_in_move as $key => $value) {
            
            $cf_angle = $value['cf_angle'];
            $s_travel = $value['s_travel'];
            $feedrate = $value['feedrate'];
            $z_angle  = $value['z_angle'];
            $x_travel = $value['x_travel'];
            
            // In the transitions we ALWAYS move at speed that will allow us to get to the desired CF ANGLE
            $x_travel = $x_travel;

            // Work out how far to rotate the z-axis
            $z_angle = $this->getSpindleDirection() * $direction * $z_angle;          
            
            // print "X_TRAVEL = $x_travel <br />";
            $this->generateXYCode($layer, $x_travel, $s_travel, $z_angle, $feedrate, $this->getNoseConeCFAngle());            
        }
        
        
        
        
        $this->addGcodeComment("START OF CYLINDRICAL");
        
        # This is Where we do the CONE Component
        # This depends upon direction....
        #                   starting X, starting Y, starting z 
        #
        # FIRST, we need to constant radius section until certain distance.
        
        # This has to be some distance PAST the start of the Cone Base...because the Carbon Fiber vectors is trailing
        # the position of the DElivery Head. WE know the distance it trails depends upon the CF Angle 
        # We are adopting the CYLINDER code and this "distance" was calulated PER layer.
        $x_travel = $this->getNoseConeStartX() + $this->layer_properties[$layer]['lead_distance'] - $this->current_x;
        
        $nose_cone_cylinder_start = $this->current_x;
       
        # The Angular distance to travel to ensure correct laydown angle
        // $s_travel = $this->calculateYTravelDegrees($x_travel, $this->layers[$layer]['cf_angle']);
        $s_travel = $this->calculateYTravelDegrees($x_travel, $this->getNoseConeCFAngle());
        
        
        # The optimum z angle for the cylinder sections
        $z_angle  = $this->getSpindleDirection() * $direction * $this->optimum_z_angle;  // work out how much further to rotate from current position to get to optimum angle.
        
        # TODO - This should be okay...we MAY want it to be similar to the NoseCone start speed, though probably not critical
        $feedrate = $this->straight_feed_rate;
        
        # This SHOULD be OKAY.
        $this->generateXYCode($layer, $x_travel, $s_travel, $z_angle, $feedrate, $this->getNoseConeCFAngle());        
        

        
        
        /* Now we need to do the Cone */
        $this->addGcodeComment("START OF CONE - Path: " . $advancement_number);
        
        foreach ($this->nose_cone_points[$advancement_number] as $key => $value) {
           // Skip the first point - we don't have speed data and this mucks up movements.         
        //   if ($key > 1) {
               // $cf_angle = $value['cf_angle'];
               $s_travel = $this->nose_cone_points[$advancement_number][$key]['s_travel'];;
               $feedrate = $this->nose_cone_points[$advancement_number][$key]['feedrate'];
               $z_angle  = $this->nose_cone_points[$advancement_number][$key]['z_angle'];
               $x_travel = $this->nose_cone_points[$advancement_number][$key]['x_travel'];
               
              // $this->addGcodeComment("X_TRAVEL: " . $x_travel);
            
               // In the transitions we ALWAYS move at speed that will allow us to get to the desired CF ANGLE
               $x_travel = $x_travel;

               // Work out how far to rotate the z-axis
               $z_angle = $this->getSpindleDirection() * $z_angle;          

               $this->generateXYCode($layer, $x_travel, $s_travel, $z_angle, $feedrate, $this->getNoseConeCFAngle());  
               
       //    }
        }
        
        
        # We are going back now...so direction changes.
        $direction = -1;
        
        // Do the nose Cylindrical move back
        $this->addGcodeComment("CYLINDER BACK...");
        # This is Where we do the CONE Component
        # This depends upon direction....
        #                   starting X, starting Y, starting z 
        #
        # FIRST, we need to constant radius section until certain distance.
        
        # This has to be some distance PAST the start of the Cone Base...because the Carbon Fiber vectors is trailing
        # the position of the DElivery Head. WE know the distance it trails depends upon the CF Angle 
        # We are adopting the CYLINDER code and this "distance" was calulated PER layer.
        $x_travel = ($nose_cone_cylinder_start - $this->getNoseConeStartX() - $this->layer_properties[$layer]['lead_distance']);
       
        # See if we are bringing x_travel OUT OF Bounds (< 0)
        if (abs($x_travel) > $this->current_x) {
            // If we are, then correct it!
            $x_travel = -1 * $this->current_x;
        }
        
        # The Angular distance to travel to ensure correct laydown angle
        $s_travel = abs($this->calculateYTravelDegrees($x_travel, $this->getNoseConeCFAngle()));       
        
        # The optimum z angle for the cylinder sections
        $z_angle  = $this->getSpindleDirection() * $direction * $this->optimum_z_angle;  // work out how much further to rotate from current position to get to optimum angle.
        
        # TODO - This should be okay...we MAY want it to be similar to the NoseCone start speed, though probably not critical
        $feedrate = $this->straight_feed_rate;
        
        # This SHOULD be OKAY.
        $this->generateXYCode($layer, $x_travel, $s_travel, $z_angle, $feedrate, $this->getNoseConeCFAngle()); 

        
        
        
        
        $this->addGcodeComment("TRANSITION OUT");
        // Do a transition - i.e. no x-movement, only Y and Z
        foreach ($this->transition_out_move as $key => $value) {
            $cf_angle = $value['cf_angle'];
            $s_travel = $value['s_travel'];
            $feedrate = $value['feedrate'];
            $z_angle  = $value['z_angle'];
            
            // Work out how far to rotate the z-axis
            $z_angle = $this->getSpindleDirection() * $direction * $z_angle;

            $this->generateYCode($layer, $s_travel, $z_angle, $feedrate);            
        }

        // End the transition by orientating the z-axis (CF guide) to zero degrees. Not moving anything else
        $z_angle  = 0;
        $s_travel = 0;
        $feedrate = $this->transition_feed_rate;
        $this->generateYCode($layer, $s_travel, $z_angle, $feedrate);
        
    }

    
    /*
     * This function was created during creation of Nose Cone. It's purpose is to work out how many passes
     * to cover entire bottom of Nose Cone. We can work this out by looking at circumference of bottom, knowing the
     * amount we advance each time and dividing the second into the first.
     */
    public function getNumberOfPasses() {
        
        $num_passes = ceil(360 / $this->idealCFAdvancementAngle());
        
        return $num_passes;
    }
    
    public function calculateXSpeed($feedrate, $x_travel, $cf_angle) {
        
        $x_speed = abs($feedrate/ sqrt(1 + pow($cf_angle/$x_travel, 2)))/60;
        
        return $x_speed;
    }
    
    
    /*
     * Given a x pos (meters), output the appropriate value suitable for the Tube Winder (mm)
     */    
    public function generateXPosValue($xpos) {
       return 1000 * round($xpos, $this->sig_figures);    
        
    }
    
    /*
     * Given a y pos (degrees), output the appropriate value suitable for the Tube Winder (mm)
     */
    public function generateYPosValue($ypos) {

       return $this->getSpindleDirection() * 1000 * round($ypos * (pi()/180) * $this->mandrelRadius, $this->sig_figures);    
        
    }
    
    /*
     * Given a z pos (degrees), output the appropriate value
     */
    public function generateZPosValue($zpos) {

       return round($zpos, 0);
    }    
    
    

    
    /*
     * Calculate the Tangential velocity (m/sec) given the feedrate (degrees/min)
     * 
     * V = w r
     */
    public function calculateYSpeed($feedrate) {
        $rotation_speed = (pi()/180) * $feedrate / 60;   // Rotational rate in Radians/second
        
        $y_speed = abs($rotation_speed * $this->mandrelRadius);
        
        return $y_speed;
    }

    
    /* 
     * Used to generate code to move the train along the 'main section'
     * 
     * At this point in time, the Z-axis should already be aligned appropriately,
     * so there is no rules here to change it.
     * 
     * $x_travel in meters
     * $s_travel in degrees
     * $z_travel in degrees
     * $feedrate in mm/minute
     * 
     */
    public function generateXYCode($layer, $x_travel, $s_travel, $z_angle, $feedrate, $cf_angle) {
                
        // Calculate new positions
        $this->current_x = $this->current_x + $x_travel;
        $this->current_s = $this->current_s + $s_travel;
        $this->current_z = $z_angle;
        
        if ($this->current_x < 0) {
            $this->addGcodeComment("Tried to send X to " . $this->current_x . ". Setting to zero.");
            $this->current_x = 0;
        }
        
        // Calculate the time to do this maneuvere        
        $wind_time = abs($x_travel) / $this->calculateXSpeed($feedrate, $x_travel, $cf_angle);
        
        // Add to the total time 
        $this->addTime($wind_time);
        
        // Update CF length.
        if (! is_null($layer)) {
           $this->length[$layer] = $this->length[$layer] + $this->calculateCFLength($cf_angle, $x_travel);
        }
        
        $code_text = "G1 F" . $feedrate . " X" . $this->generateXPosValue($this->current_x) . " Y" . $this->generateYPosValue($this->current_s) . " Z" . $this->generateZPosValue($this->current_z);
        array_push($this->gcodes, $code_text);
        
        
        // Calculate max_x - for layers
        if (!is_null($layer)) {
           if ($this->current_x > $this->max_x) {
               $this->max_x = $this->current_x;
           }   
        }
    }
    
    
    public function generateYCode($layer, $s_travel, $z_angle, $feedrate) { 
        // Calculate new positions
        $this->current_s = $this->current_s + $s_travel;
        $this->current_z = $z_angle;

        // Calculate the time to do this maneuver - in seconds
        $wind_time = $s_travel / ($feedrate/60);
        
        // Add to the total time 
        $this->addTime($wind_time);
        
        // Update distance
        $this->length[$layer] = $this->length[$layer] + $this->mandrelRadius * $s_travel * pi()/180;
        // We KNOW THIS is approximate at the transition ends, because we are discounting the X distance. We will update this later
        
        
        $code_text = "G1 F" . $feedrate . " Y" . $this->generateYPosValue($this->current_s) . " Z" . $this->generateZPosValue($this->current_z);
        array_push($this->gcodes, $code_text);
    }    
    
    
    /*
     * This function was built during NoseCone coding
     * 
     * The purpose of this function is to return the radius of the Nose Cone at the point of which the CF 
     * is on the Mandrel. 
     * 
     * The need became apparent after it was realised that the orientation of the fiber (z-axis) depends
     * upon the vector from dispenser to mandrel surface, but to know this vector, we need to know the 
     * radius at the point it makes contact...$x - SOME_DISTANCE.... not $x where the dispenser is
     * 
     * $x = Current position (meters) of the dispenser head
     * $lead_distance - how far we lead the point of contact of CF with mandrel (meters)
     * 
     * Assumptions
     *  
     */
    public function getMandrelRadiusAtX($x, $lead_distance) {
        
        
        // See Where we are relation to the "start", "stop" of the cone and return appropriate radius of mandrel at this point
        if ($x - $lead_distance > $this-> $this->nose_cone_start_x) {
            return $this->getMandrelRadius();
        } else if ($x - $lead_distance > $this->nose_cone_stop_x) {
            $radius = $this->nose_cone_top_radius;
        } else {
            $radius = ($x - $lead_distance) * ($this->nose_cone_top_radius - $this->getMandrelRadius())/($this->nose_cone_stop_x - $this->nose_cone_start_x) + $this->getMandrelRadius();
        }

        
        
        }
    
    
    public function getGcodesCount() {
        return count($this->gcodes);
    }
    
    public function printGCodes()
    {
        print "<pre>";
        print_r($this->gcodes);
        print "</pre>";
    }
    
    
    /* 
     * Purpose of this function is to generate ALL Gcodes
     * 
     */
    public function generateGCodes() {
        
        
       // Generate Pre-Amble
        array_push($this->gcodes, "G21");
        array_push($this->gcodes, "G64 P0.01");
        array_push($this->gcodes, "M1");
        array_push($this->gcodes, "G1 F6000 X" . 1000 * $this->start_x);
        array_push($this->gcodes, "G1 F6000 Y" . $this->start_s);
        array_push($this->gcodes, "G1 F6000 Z" . $this->start_z);        
        array_push($this->gcodes, "M1");

        // Perform Calculations
        $layer = 0;
        $this->calculations($layer);
        
        // Get the wind "started"
        // Create all the passes
        for ($layer = 0; $layer < $this->getNumberOfLayers(); $layer++) {
            // Print out # of passes
            $this->addGcodeComment("Number of passes: " . $this->getNumberOfPasses());
            
            // Laydown the Carbon Fiber
            $this->current_pass = 0;

            // Create All the Passes
            for ($i = 1; $i <= $this->getNumberOfPasses(); $i++) {
               $this->addGcodeComment("Pass: " . $i . " of " . $this->getNumberOfPasses());

               //Create the pass
               $this->generatePassCone($layer);
            }
        }
       
       // Finish the GCode file
       array_push($this->gcodes, "M2");
       array_push($this->gcodes, "$");
    }
    
    
    public function printGCodesToFile($file="/tmp/gcode.ngc") {
        $fp = fopen($file, 'w');
         for ($i = 0; $i < count($this->gcodes); $i++) {
             fwrite($fp, $this->gcodes[$i] . "\n");
         }
           
        
        fclose($fp);
    }

    

    public function addGcodeComment($comment) {
        array_push($this->gcodes, "(" . $comment . ")");
    }
    
    
    public function calculations($layer) {
        // Do Cone Calcs first as we need to get the CF Angle for Cylinder section
        for ($i = 0; $i < $this->nose_cone_cf_double_up; $i++) {
            $distance = $this->nose_cone_cf_closest_approach_to_tip + ($i * $this->cf_width);
            // print "Generating Cone information for " . $distance . " - " . $i . "<br />";
           $this->calcsNoseCone($layer, $distance, $i);
        }
        
        $this->calcsCylinder($layer);
        
        
    }    
    
    
    
    public function calcsCylinder($layer) {
        
        
        // Calculations for Cylindrical part of Nose Cone
        $cf_angle_y = $this->calculateCFYAngle($this->getMandrelRadius(), $this->eyeletDistance, $this->eyeletHeight);

        $v2 = $this->deriveVectorOriginMandrel($cf_angle_y);
        
        $v3 = $this->deriveVectorDispenserMandrel($cf_angle_y);
        
        // $z_component = $this->deriveMaxVectorDispenserMandrel($v3, $this->layers[$layer]['cf_angle']);
        $z_component = $this->deriveMaxVectorDispenserMandrel($v3, $this->getNoseConeCFAngle());
        
        
        $this->layer_properties[$layer]['lead_distance'] = $z_component;
        $this->layer_properties[$layer]['transition_end_length'] = $z_component;
        
        // print "for noseconeCF Angle: " . $this->getNoseConeCFAngle() . " the cylinder has a lead distance of " . $z_component . "<br/>";
        
        // Work out 'optimum' angle for this
        $this->optimum_z_angle = $this->deriveOptimumCFAngle($v3, $z_component);
         
        // Generate transistion data for END of Pass
        $this->generateInPoints($this->getTransitionStartWind(), $this->transition_steps_in, $z_component, $v3);       
        
        // Generate transistion data for START of Pass
        $this->generateOutPoints($this->getTransitionEndWind(), $this->transition_steps_out, $z_component, $v3);
        
        // Generate array of moves to GET the transitions done
        $this->createInMoveTransitionSchedule($layer);
        $this->createOutMoveTransitionSchedule();
 
    }    

    

    /*
     * In calcsNoseCone, we need to calculate all the x_travel, s_travel, z_travel to move the 
     * tool to ensure we get a path as defined by CONE value
     * 
     * All values are "RELATIVE" to the current starting position and is returned to array nose_cone_points
     * 
     * $layer - layer we are on
     * $nose_cone_cf_closest_approach_to_tip - distance to TIP of cone
     * $j                                    - index identifying collection of points for given $nose_cone_cf_closest_approach_to_tip
     * 
     * This routine starts with points CLOSEST to NoseCone Tip and progressively travels away from it...by the width
     * of the CF. So we get overlap
     * 
     */
    public function calcsNoseCone($layer, $nose_cone_cf_closest_approach_to_tip, $j) {
        
        // DEBUGGING
        $debug = 1;
        
        
        // Conversion contants
        $meters_to_mm = 1000;
        $seconds_to_minutes = 60;
                 
        if ($debug == 1) {
           print $this->getMandrelRadius()  .   "   " . $this->nose_cone_top_radius . "   " . $this->nose_cone_stop_x . "   " . $this->nose_cone_start_x . "<br/>";
        }
        
        /* DERIVE CONSTANTS USED TO DERIVE OTHER QUANTITIES LATER ON. THESE CONSTANTS DEPEND UPON CONE TOPOLOGY */
        // Angles
        $alpha     = atan(($this->getMandrelRadius() - $this->nose_cone_top_radius)/ ($this->nose_cone_stop_x - $this->nose_cone_start_x));
        $cot_a     = 1 / tan($alpha);
        
        // Hyponensue distanace from base to theoretical point of cone. We say Theoretical because in actual fact we have a truncated Nose Cone
        $cone_hyp = $this->getMandrelRadius() / sin($alpha);
        
        // Theta
        $theta_a   = asin($nose_cone_cf_closest_approach_to_tip / $cone_hyp);
        
        // Ratio of circumferences of circles - 2d : 3d
        $k         = (2 * pi() * $cone_hyp)/ (2 * pi() * $this->getMandrelRadius());
        
        $m              = 2 * (pi()/2 - $theta_a);
        $lead_distance_reduction = 0;
        
         


            
        
if ($debug == 1) {        
        print "<pre>";
        print "Alpha:     " . $alpha . "<br/>";
        print "Cot_A:     " . $cot_a . "<br/>";
        print "Cone Hyp:  " . $cone_hyp . "<br/>";
        print "Theta A:   " . $theta_a . "<br/>";
        print "k:         " . $k . "<br/>";
        print "</pre>";
        
        
        print "<table border=1>";
        
        print "<tr>";
        print "<th>X (mm)</th>";
        print "<th>Y (mm)</th>";
        
        print "<th>Speed (mm/min)</th>";
        print "<th>X Travel (m)</th>";
        print "<th>S Travel (Deg)</th>";
        print "<th>Z Angle (Deg)</th>";
        
        print "<th>Mandrel Rotation Speed (mm/min)</th>";
        print "<th>Resin Bath Speed (mm/min)</th>";    
        print "<th>Angle of CF wrt Mandrel Axis</th>";        
        print "<th>Lead Distance</th>";
        print "<th>Lead Distance CHANGE</th>";
        print "<th>CF Angle Y</th>";
        print "</tr>";        
}        
        



        
        // Go through Time steps, and devise the Travel in X, Y, Z
        for ($i = 1; $i <= $this->nose_cone_num_data_points; $i = $i + 1) {
            
            // Derive 2-D special components - starting with angular component
            $theta_begin = $theta_a  + ($i - 1) * $m / ($this->nose_cone_num_data_points);
            $theta_end   = $theta_a  + $i * $m / ($this->nose_cone_num_data_points);
            
            $phi_begin   = $theta_begin * $k;
            $phi_end     = $theta_end   * $k;

            // 2-D space - where we are at.
            $radius_2d   = $nose_cone_cf_closest_approach_to_tip / sin($theta_end);

            // We are only interested in RELATIVE angles.
            $phi_rel     = $phi_end - $phi_begin;
            
            // Calculate angle in degrees
            $s_angle     = (180/pi()) * $phi_rel;
            
            // Calculate the Rotation m/sec
            $rotational_speed = $phi_rel/$this->seconds_per_tick;

            // Convert to mm/min 
            $rotational_speed       = $rotational_speed * $this->mandrel_speed_conversion * $meters_to_mm * $seconds_to_minutes;
            $rotation_speed_display = round($rotational_speed, 0);
            
            // Radius of cone at this point in time
            $radius_3d = $radius_2d / $k;
            
            
            // Distance from base of cone - in axial direction            
            $axial_distance_begin = ($cone_hyp - $nose_cone_cf_closest_approach_to_tip/Sin($theta_begin)) * ($cot_a / $k);
            $axial_distance_end   = ($cone_hyp - $nose_cone_cf_closest_approach_to_tip/Sin($theta_end)) * ($cot_a / $k);
            $axial_speed = ($axial_distance_end - $axial_distance_begin) / $this->seconds_per_tick;

            // Convert Axial speed to mm/min
            $axial_speed = $axial_speed * $meters_to_mm * $seconds_to_minutes;
            $axial_speed_display = round ($axial_speed);            
            
            
            // Combine Axial and Rotation speed (ignore radial speed... it is small)
            $total_speed = round(sqrt($axial_speed * $axial_speed + $rotational_speed * $rotational_speed), 0);
            $total_speed_display = round($total_speed, 0);
            
            
            // Derive some quantities required below to get vectors (CF dispenser to Mandrel)
            $gap = $this->mandrelRadius + $this->eyeletDistance - $radius_3d;
            $cf_angle_y = (pi()/180) * $this->calculateCFYAngle($radius_3d, $gap, $this->eyeletHeight);

          
            
            // Work out MANDREL (CF contact) <----> CF Dispenser VECTOR
            // We need to work this out, so we can determine the Z angle
            // DISTANCE RADIALLY
            $v[0] = $radius_3d * sin($cf_angle_y) - ($this->mandrelRadius + $this->eyeletDistance);
            
            // DISTANCE UP-DOWN
            $v[1] = $radius_3d * cos($cf_angle_y) - $this->eyeletHeight;
            
            // DISTANCE LONG AXIS
            // speed
            if ($total_speed <> 0) {
               $angle_with_respect_to_mandrel_axis = acos($axial_speed / $total_speed);
            } else  {
               $angle_with_respect_to_mandrel_axis = pi()/2;    
            }
            
            
            // vector component in direction of mandrel axle
            $v[2] =  (($this->mandrelRadius + $this->eyeletDistance) - $radius_3d * sin($cf_angle_y))/ tan($angle_with_respect_to_mandrel_axis);
            $lead_distance = $v[2];
            
            
            // We do some rounding and convert to degrees for display.
            $angle_with_respect_to_mandrel_axis_display = round((180/pi()) * $angle_with_respect_to_mandrel_axis, 0);
                         
            // Put on to plane
            $v[0] = 0;
            // Get the Unit vector
            $v_unit = $this->vectorUnit($v);
            

            // For the first step, we get the original lead distance            
            if ($i == 1) {
              // Calculations for Cylindrical part - required to find the LEAD DISTANCE at interface between cylinder and cone parts
              $cf_angle_y = $this->calculateCFYAngle($this->getMandrelRadius(), $this->eyeletDistance, $this->eyeletHeight);
              $v3 = $this->deriveVectorDispenserMandrel($cf_angle_y); 
               
              $this->nose_cone_cf_angle = min($this->nose_cone_cf_angle, round((180/pi()) * $angle_with_respect_to_mandrel_axis, 0));
              $original_lead_distance = $this->deriveMaxVectorDispenserMandrel($v3, $this->nose_cone_cf_angle);

              // print "ORIGINAL LEAD DISTANCE (" . $i . ") is: " . $original_lead_distance . "<br/>";            
            }
            
            
            // As the resin bath travels down the X axis, the CF leads the mandrel/CF contact point less and less
            // i.e. it reduces. We calculate that reduction here.
            $previous_lead_distance_reduction = $lead_distance_reduction;
            $lead_distance_reduction = $original_lead_distance - $lead_distance;
            
            // X - POS - Carriage direction
            $x_pos = round(($cone_hyp - $nose_cone_cf_closest_approach_to_tip/Sin($theta_end)) * ($cot_a/$k) *  $meters_to_mm, 1);                        
            // Y - POS - Mandrel rotation
            $y_pos = round($phi_rel * $this->getMandrelRadius() * $meters_to_mm,1);
            // Z - POS - Presentation of the CF by dispenser head
            $z_pos = $this->sign($axial_speed) * round((180 / pi()) * acos($v_unit[1]),0);
            
            // We want the amount that the resin bath must TRAVEL from the previous position to the new position
            $x_pos_begin = ($cone_hyp - $nose_cone_cf_closest_approach_to_tip/sin($theta_begin)) * ($cot_a/$k) - $previous_lead_distance_reduction;
            $x_pos_end   = ($cone_hyp - $nose_cone_cf_closest_approach_to_tip/sin($theta_end)) * ($cot_a/$k) - $lead_distance_reduction;
            $x_travel    = $x_pos_end - $x_pos_begin;            
            
            // Set the values
            $this->nose_cone_points[$j][$i]['x_travel'] = $x_travel;
            $this->nose_cone_points[$j][$i]['s_travel'] = $s_angle;
            $this->nose_cone_points[$j][$i]['z_angle']  = $z_pos;
            $this->nose_cone_points[$j][$i]['feedrate'] = $total_speed_display;
            
                       
            
            
if ($debug == 1) {            
            print "<tr>";
            print "<td>" . $x_pos . "mm</td>";
            print "<td>" . $y_pos . "mm</td>";
            
            print "<td>" . $total_speed_display . "</td>";
            print "<td>" . $this->nose_cone_points[$j][$i]['x_travel'] . " </td>";
            print "<td>" . $this->nose_cone_points[$j][$i]['s_travel'] . " </td>";
            print "<td>" . $this->nose_cone_points[$j][$i]['z_angle'] . " </td>";
            print "<td>" . $rotation_speed_display . "</td>";
            print "<td>" . $axial_speed_display . "</td>";   
            print "<td>" . $angle_with_respect_to_mandrel_axis_display . "</td>";
            print "<td>" . round($lead_distance * $meters_to_mm, 1) . "</td>";
            print "<td>" . round($lead_distance_reduction * $meters_to_mm, 1) . "</td>";
            print "<td>" . $cf_angle_y . "</td>";
            print "</tr>";
}            
                        
        }
        
if ($debug == 1) {   
        print "</table>";
} 


     // We wish to know HOW long the cylindrical section of the nose cone is.
     // This isn't STRAIGHT-FORWARD
     // We know the WHOLE cylindial part is bewteen X = 0 (if $this->start_x - 0) AND $this->nose_cone_start_x
     // The LOWER part of this is the transition.... We know the WHOLE transition requires the 
     // dispenser to move from $x = 0 to $x = $this->startTransitionXDistance($layer)
     // HOWEVER
     // The CF contact with the Mandrel is lagging by $this->layer_properties[$layer]['lead_distance']
     //
     // HENCE THE CALCULATION BELOW
     //
     $length_cylinder = $this->nose_cone_start_x - $this->start_x - ($this->startTransitionXDistance($layer) - $this->layer_properties[$layer]['lead_distance']);
     if ($debug == 1) {
         print "Length of IN transition: " . $this->startTransitionXDistance($layer) . "<br>";
         print "Lead Distance:           " . $this->layer_properties[$layer]['lead_distance'] . "<br />";
         print "Length of Cylindrical Section: " . $length_cylinder . "<br/>";
     }


    }


}