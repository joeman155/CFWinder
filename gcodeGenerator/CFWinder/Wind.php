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
    

    

    private $cf_width;                        // Width of fiber in Meters
    private $straight_feed_rate;              // Rate of laydown of CF during straight sections.
    private $spindle_direction;               // Direction the spindle spins. Clockwise is default = +1
    
    private $layers;                          // Information to describe how to build each layer
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
                                $layers, $nose_cone_start_x, $nose_cone_stop_x, $nose_cone_top_radius) {
        
        
        
        // Basic dimensions of set-up        
        $this->eyeletDistance      = $eyeletDistance;
        $this->eyeletHeight        = $eyeletHeight;
        $this->mandrelRadius       = $mandrelRadius;
        $this->cf_width            = $cf_width;
        $this->spindle_direction   = $spindle_direction;
        
        $this->mandrel_speed_conversion = 0.0381;
        
        // Layers we want to create
        $this->layers              = $layers;
        
        
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
        
        $this->nose_cone_cf_closest_approach_to_tip = 0.17;   // 0.15 meters from tip
        $this->nose_cone_wind_time_per_pass         = 10;     // Seconds to do there and back
        $this->nose_cone_num_data_points            = 200;
        $this->seconds_per_tick                     = 0.1;
        
        
        // May make this parameter driven later.
        $this->turn_around_splits = 4;

    }
    
    
    public function getNoseConeStartX($layer) {
        return $this->nose_cone_start_x;
    }    

    public function getNoseConeStopX($layer) {
        return $this->nose_cone_stop_x;
    }    

    public function getNoseConeTopRadius($layer) {
        return $this->nose_cone_top_radius;
    }        
    
    public function getLeadDistance($layer) {
        return $this->layer_properties[$layer]['lead_distance'];
    }    
    
    public function getTubeStart($layer) {
        return $this->layer_properties[$layer]['transition_start_length'];
    }
    
    public function getLayers() {
        return $this->layers;
    }
   
    public function getOptimumZAngle() {
        return $this->optimum_z_angle;
    }
    
    public function getTransitionStartWind() {
        return $this->transition_start_wind;
    }
    
    public function getTransitionEndWind() {
        return $this->transition_end_wind;
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
    
    public function getLength($layer) {
        return $this->length[$layer];
    }
       
    
    public function getCFWeight() {
             return $this->cf_weight_per_meter * $this->calculateActualCFLengthRequiredOneLayer();
    }  
    
    public function getWindAnglePerPass($layer) {
             return $this->layers[$layer]['wind_angle_per_pass'];
    }
    
    public function getCFAngle($layer) {
             return $this->layers[$layer]['cf_angle'];
    }
    
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
     * 
     * This is length of tube for where the tube is EXACTLY wound at the desired cf_angle
     * 
     */
    public function getTubeLength($layer) {
        return ($this->getWindAnglePerPass($layer)* pi()/180) * $this->mandrelRadius/ tan(pi() * $this->getCFAngle($layer)/180);
    }
    


    /*
     * getTotalTubeLength 
     * 
     * Get the total tube length...which is the total X-movement of the carriage
     * 
     */
    public function getTotalTubeLength($layer) {
        return $this->getTubeLength($layer) + $this->startTransitionXDistance($layer);
    }

    
    /*
     * Return total length of transitions
     * 
     * Think about this carefully...the startTransitionXDistance is the distance of the carriage
     * 
     * at the end of getTotalXTransitionDistance, the carriage leads the CF/Mandrel by the LEAD distance
     * 
     * So the TRANSITION distance at beginning is getTotalXTransitionDistance - lead_distance
     * 
     * The END transition distance is JUST the lead_distance
     * 
     * SO, the TOTAL transition length is getTotalXTransitionDistance - lead_distance + lead_distance = getTotalXTransitionDistance
     * 
     */
    public function getTotalXTransitionDistance($layer) {
        return $this->startTransitionXDistance($layer); //  - $this->lead_distance + $this->lead_distance;
    }
    
    
    /*
     * This is the distance we need to advance (meters) tangentially to ensure
     * there is no gap and no overlap of the CF fiber.
     */
    public function idealCFAdvancement($layer) {
        return $this->cf_width /cos($this->getCFAngle($layer) * pi()/180);
    }
    
    public function actualCFAdvancement($layer) {
        return $this->getMandrelCircumference() / $this->calculatePassesToCoverMandrel($layer);
    }    
    
    public function actualCFAdvancementAngle($layer) {
        return round(360 * $this->actualCFAdvancement($layer) /$this->getMandrelCircumference(),3);
    }   
 
    /*
     * This is the distance we need to advance (meters) tangentially to ensure
     * there is no gap and no overlap of the CF fiber.
     */
    public function idealCFAdvancementAngle($layer) {
        return (180/pi()) * $this->idealCFAdvancement($layer) / $this->mandrel_speed_conversion;
    }
        
    
    /*
     * This is for the Cylindrical part BEFORE the nosecone 'section'
     */
    public function calculateSurfaceArea($layer) {
        return pi() * 2 * $this->mandrelRadius * $this->getTubeLength($layer);  // Changed to useful length
    }
    
    /*
     * This is length of CF for one LAYER
     * .
     * NOTE, we need go X times in one direction and X times in the other direction. So...one could 
     *       say we actually do two layers in ONE pass.
     * 
     * NOTE: This does NOT take into account the transitional areas, where CF is unfortunately wasted
     * 
     * i.e. the goal here is to work out how much CF area to cover CENTRAL area of tube, not transitions
     */
    public function calculateCFLengthRequiredOneLayer($layer) {
        return 2 * $this->calculateSurfaceArea($layer)/ $this->cf_width;
    }
    
    
    public function calculateActualCFLengthRequiredOneLayer($layer) {
        // PER PASS
        
        // We multiple by 2 (two) at beginning because we actually do TWO layers...one from left and one from right.
        return 2 * $this->calculatePassesToCoverMandrel($layer) * $this->calculateCFMetersOnePass($layer);
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
     * calculateStraightXLength 
     * 
     * Calcualte the length of tube with NO transitions
     * 
     */
    public function calculateStraightXLength($layer)
    {
        return $this->getTubeLength($layer);
    }
    

    
    /* 
     * This calcualtes CF for just one pass from left to right. It includes transitions 
     */
    public function calculateCFMetersOnePass($layer) {
        
        if ($this->layers[$layer]['extra_spindle_turn'] > 0) {
            $extra_spindle_turn_distance = (pi()/180) * $this->layers[$layer]['extra_spindle_turn'] * $this->mandrelRadius;
        } else {
            $extra_spindle_turn_distance = 0;
        }
        
        $begin_transition_length = 0;   // TODO
        $end_transition_length   = 0;   // TODO
        
        return  $begin_transition_length + $this->calculateStraightXLength($layer) + $end_transition_length + $extra_spindle_turn_distance; 
    }
    
    
    /* 
     * This calculates length of CF for just the straight section (not including transitions)
     */
    public function calculateCFMetersOnePassStraight($layer) {
        return $this->getTubeLength($layer) / cos(pi() * $this->getCFAngle($layer)/180);  // Changed to Useful Tube Length
    }
    

    /* 
     * This calculates length of CF for just the straight section (not including transitions)
     */
    public function calculateCFLength($cf_angle, $x_length) {
        $len = abs($x_length / cos(pi() * $cf_angle/180));  // Changed to Useful Tube Length
        return $len;
    }    
    
    /* 
     * We choose ceil to ensure that we don't have gaps. I'd rather a "LITTLE" bit of overlap, rather than
     * gaps!
     * 
     * NOTE: This is ultimately to cover the "useful" length of the tube...not the transition area
     *       For the transition area, we expect the CF to be slightly thicker.
     * 
     */
    public function calculatePassesToCoverMandrel($layer) {
        return ceil(($this->calculateCFLengthRequiredOneLayer($layer)/2) / $this->calculateCFMetersOnePassStraight($layer));
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
             
             $x_travel = $this->calculateXTravelMeters($s_travel, $this->layers[$layer]['cf_angle']);
             
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
    

    
    
    
    public function generatePass($layer) {
        
        $this->current_pass++; 
        
        if ($this->current_pass %2 == 0) {
            $direction = -1;
        } else {
            $direction = +1;
        }
        
        // Only advance if on 3,5,7... pass. i.e. not on first pass or even pass
        // We use transision feed rates.
        if ($this->current_pass > 1 && $this->current_pass % 2 == 1) {
            $feedrate = $this->transition_feed_rate + 99;
            $s_travel = $this->actualCFAdvancementAngle($layer) + $this->layers[$layer]['extra_spindle_turn'];
            $z_angle = 0;
            $this->generateYCode($layer, $s_travel, $z_angle, $feedrate);
        } elseif ($this->current_pass > 1 && $this->current_pass % 2 == 0 && $this->layers[$layer]['extra_spindle_turn'] > 0) {
            // Add the SPIN at the end (if one is requested). Purpose of this is to try and maintain tension in the CF.
            $feedrate = $this->transition_feed_rate + 98;
            $s_travel = $this->layers[$layer]['extra_spindle_turn'];
            $z_angle = 0;
            $this->generateYCode($layer, $s_travel, $z_angle, $feedrate); 
        }
        

        
        // Do the Transition
        foreach ($this->transition_in_move as $key => $value) {
            $cf_angle = $value['cf_angle'];
            $s_travel = $value['s_travel'];
            $feedrate = $value['feedrate'];
            $z_angle  = $value['z_angle'];
            $x_travel = $value['x_travel'];
            
            // In the transitions we ALWAYS move at speed that will allow us to get to the desired CF ANGLE
            $x_travel = $direction * $x_travel;

            // Work out how far to rotate the z-axis
            $z_angle = $this->getSpindleDirection() * $direction * $z_angle;          

            $this->generateXYCode($layer, $x_travel, $s_travel, $z_angle, $feedrate, $this->getCFAngle($layer));            
        }
        
        
        # Max Speed
        $x_travel = $direction * $this->getTubeLength($layer);
        $s_travel = $this->getWindAnglePerPass($layer);
        $z_angle  = $this->getSpindleDirection() * $direction * $this->optimum_z_angle;  // work out how much further to rotate from current position to get to optimum angle.
        $feedrate = $this->straight_feed_rate;
        $this->generateXYCode($layer, $x_travel, $s_travel, $z_angle, $feedrate, $this->getCFAngle($layer));
   
        

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
    
    
public function generatePassCone($layer, $s_angle_starting_angle) {
        
        $this->current_pass++; 
        
        // Set Direction on START of pass
        $direction = 1;
        

        
        // In normal cylinders a Pass was in a SINGLE DIRECTION. i.e. you would need two passes to get back to where you started
        // For Nose Cone a Single Pass gets you there AND back...so each pass, except for the first, we need to advanced the CF.
        // Hence the simple condition below.
        if ($this->current_pass > 1) {
            
            $this->addGcodeComment("ADVANCING THE CF");
            $feedrate = $this->transition_feed_rate + 99;
            
            // Work out how many degrees to get back to the beginning
            $degrees_back_to_beginning = 360 * (1 - (($this->current_s - $s_angle_before)/360 - floor(($this->current_s - $s_angle_before)/360)));
            
            $this->addGcodeComment("CURRENT S: " . $this->current_s);
            
            $this->addGcodeComment("Degrees back: " . $degrees_back_to_beginning);

            
            $move_amount = $degrees_back_to_beginning + ($this->current_pass % $this->turn_around_splits) * 360/$this->turn_around_splits;
            
            $this->addGcodeComment("MOVE AMOUNT: " . $move_amount);
            // Extra SPindle turn is more of a guide for Nose Cone... it is the minimum degrees to turn at end
            // If to get back to beginning we exceed the minimum, we do just this.
            // If we don't, then we need to move back to beginning AND then move another 360 degrees.
            if ($move_amount < $this->layers[$layer]['extra_spindle_turn']) {
                $move_amount = $move_amount + 360;
            }
            $this->addGcodeComment("MOVE AMOUNT2: " . $move_amount);
            
            
            // after $this->turn_around_splits turns, we go advance a little
            if ($this->current_pass % $this->turn_around_splits == 0) {
                $move_amount = $move_amount + $this->actualCFAdvancementAngle($layer);
            }
            
            $this->addGcodeComment("MOVE AMOUNT3: " . $move_amount);
            
            
            $s_travel = $move_amount;
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
            
            $this->generateXYCode($layer, $x_travel, $s_travel, $z_angle, $feedrate, $this->getCFAngle($layer));            
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
        $x_travel = $this->getNoseConeStartX($layer) + $this->layer_properties[$layer]['lead_distance'] - $this->current_x;
        
        $nose_cone_cylinder_start = $this->current_x;
       
        # The Angular distance to travel to ensure correct laydown angle
        $s_travel = $this->calculateYTravelDegrees($x_travel, $this->layers[$layer]['cf_angle']);
        
        # The optimum z angle for the cylinder sections
        $z_angle  = $this->getSpindleDirection() * $direction * $this->optimum_z_angle;  // work out how much further to rotate from current position to get to optimum angle.
        
        # TODO - This should be okay...we MAY want it to be similar to the NoseCone start speed, though probably not critical
        $feedrate = $this->straight_feed_rate;
        
        # This SHOULD be OKAY.
        $this->generateXYCode($layer, $x_travel, $s_travel, $z_angle, $feedrate, $this->getCFAngle($layer));        
        

        
        
        /* Now we need to do the Cone */
        $this->addGcodeComment("START OF CONE");
        
        foreach ($this->nose_cone_points as $key => $value) {
           // Skip the first point - we don't have speed data and this mucks up movements.         
           if ($key > 1) {
               $cf_angle = $value['cf_angle'];
               $s_travel = $this->nose_cone_points[$key]['s_travel'];;
               $feedrate = $this->nose_cone_points[$key]['feedrate'];
               $z_angle  = $this->nose_cone_points[$key]['z_angle'];
               $x_travel = $this->nose_cone_points[$key]['x_travel'];
            
               // In the transitions we ALWAYS move at speed that will allow us to get to the desired CF ANGLE
               $x_travel = $x_travel;

               // Work out how far to rotate the z-axis
               $z_angle = $this->getSpindleDirection() * $z_angle;          

               $this->generateXYCode($layer, $x_travel, $s_travel, $z_angle, $feedrate, $this->getCFAngle($layer));  
               
           }
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
        $x_travel = ($nose_cone_cylinder_start - $this->getNoseConeStartX($layer) - $this->layer_properties[$layer]['lead_distance']);
       
        # The Angular distance to travel to ensure correct laydown angle
        $s_travel = abs($this->calculateYTravelDegrees($x_travel, $this->layers[$layer]['cf_angle']));
        
        
        # The optimum z angle for the cylinder sections
        $z_angle  = $this->getSpindleDirection() * $direction * $this->optimum_z_angle;  // work out how much further to rotate from current position to get to optimum angle.
        
        # TODO - This should be okay...we MAY want it to be similar to the NoseCone start speed, though probably not critical
        $feedrate = $this->straight_feed_rate;
        
        # This SHOULD be OKAY.
        $this->generateXYCode($layer, $x_travel, $s_travel, $z_angle, $feedrate, $this->getCFAngle($layer)); 

        
        
        
        
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
    public function getNumberOfPasses($layer) {
        
        $num_passes = ceil(360 / $this->idealCFAdvancementAngle($layer));
        
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

        
        // Get the wind "started"
        // Create all the passes
        for ($layer = 0; $layer < count($this->layers); $layer++) {
            // This does all the calculations the entire laydown of Carbon Fiber    
            $this->calculations($layer);
            
            // Print out # of passes
            $this->addGcodeComment("Number of passes: " . $this->getNumberOfPasses($layer));
            
            // Laydown the Carbon Fiber
            $this->current_pass = 0;
            $s_angle_starting_angle = $this->current_s;
            $num_passes = 10;
            for ($i = 1; $i <= $this->getNumberOfPasses($layer); $i++) {
               $this->addGcodeComment("Pass: " . $i . " of " . $this->getNumberOfPasses($layer));
               $this->generatePassCone($layer, $s_angle_starting_angle);
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
        
        $this->calcsCylinder($layer);
        
        $this->calcsNoseCone($layer);
    }    
    
    
    
    public function calcsCylinder($layer) {
        
        
        // Calculations for Cylindrical part of Nose Cone
        $cf_angle_y = $this->calculateCFYAngle($this->getMandrelRadius(), $this->eyeletDistance, $this->eyeletHeight);

        $v2 = $this->deriveVectorOriginMandrel($cf_angle_y);
        
        $v3 = $this->deriveVectorDispenserMandrel($cf_angle_y);
        
        $z_component = $this->deriveMaxVectorDispenserMandrel($v3, $this->layers[$layer]['cf_angle']);
        
        $this->layer_properties[$layer]['lead_distance'] = $z_component;
        $this->layer_properties[$layer]['transition_end_length'] = $z_component;
        
        // Work out 'optimum' angle for this
        $this->optimum_z_angle = $this->deriveOptimumCFAngle($v3, $z_component);
         
        // Generate transistion data for END of Pass
        $this->generateInPoints($this->layers[$layer]['transition_start_wind'], $this->transition_steps_in, $z_component, $v3);       
        
        // Generate transistion data for START of Pass
        $this->generateOutPoints($this->layers[$layer]['transition_end_wind'], $this->transition_steps_out, $z_component, $v3);
        
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
     */
    public function calcsNoseCone($layer) {
        
        
        // DEBUGGING
        $debug = 1;
        
        // PREV POINTS
        $x_pos_prev = 0;
        $s_angle_prev = 0;
        
        // Conversion contants
        $meters_to_mm = 1000;
        $seconds_to_minutes = 60;
                 
     
        if ($debug == 1) {
           print $this->getMandrelRadius()  .   "   " . $this->nose_cone_top_radius . "   " . $this->nose_cone_stop_x . "   " . $this->nose_cone_start_x . "<br/>";
        }
        
        // Angles
        $alpha     = atan(($this->getMandrelRadius() - $this->nose_cone_top_radius)/ ($this->nose_cone_stop_x - $this->nose_cone_start_x));
        $cot_a     = 1 / tan($alpha);
        
        // Hyponensue distanace from base to theoretical point of cone. We say Theoretical because in actual fact we have a truncated Nose Cone
        $cone_hyp = $this->getMandrelRadius() / Sin($alpha);
        
        // Theta
        $theta_a   = asin($this->nose_cone_cf_closest_approach_to_tip / $cone_hyp);
        
        // Ratio of circumferences of circles - 2d : 3d
        $k         = (2 * pi() * $cone_hyp)/ (2 * pi() * $this->getMandrelRadius());
        
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
        print "<th>X</th>";
        print "<th>Y</th>";
        print "<th>Z</th>";
        print "<th>Speed</th>";
        print "<th>Mandrel Angle (Deg)</th>";
        print "<th>Mandrel Rotation Speed (mm/min)</th>";
        print "<th>Resin Bath Speed (mm/min)</th>";    
        print "<th>Angle of CF wrt Mandrel Axis</th>";        
        print "<th>Lead Distance</th>";
        print "</tr>";        
}        
        
        $m              = 2 * (pi()/2 - $theta_a);
        $axial_distance = 0;
        $phi_rel        = 0;
        
        // Go through Time steps, and devise the Travel in X, Y, Z
        for ($i = 0; $i < $this->nose_cone_num_data_points; $i = $i + 1) {
            $theta = $theta_a  + $i * $m / ($this->nose_cone_num_data_points);
            $phi   = $theta * $k;
            
            // We get the INITIAL angles...
            if ($i == 0) {
                $start_theta = $theta;
                $start_phi   = $phi;
            }
            
            // 2-D space - where we are at.
            $radius_2d = $this->nose_cone_cf_closest_approach_to_tip / sin($theta);


            
            // We are only interested in RELATIVE angles.
            $theta_rel   = $theta - $start_theta;
            $phi_rel_old = $phi_rel;
            $phi_rel     = $phi   - $start_phi;
            
            
            // Calculate angle in degrees
            $s_angle = (180/pi()) * $phi_rel;
            
            // Calculate the Rotation m/sec
            $rotational_speed = ($phi_rel - $phi_rel_old)/$this->seconds_per_tick;
            
            
            // Convert to mm/min 
            $rotational_speed = $rotational_speed * $this->mandrel_speed_conversion * $meters_to_mm * $seconds_to_minutes;
            $rotation_speed_display = round($rotational_speed, 0);
            
            // Radius of cone at this point in time
            $radius_3d = $radius_2d / $k;
            
            // Distance from base of cone - in axial direction
            $axial_distance_old = $axial_distance;
            $axial_distance = ($cone_hyp - $this->nose_cone_cf_closest_approach_to_tip/Sin($theta)) * ($cot_a / $k);
            $axial_speed = ($axial_distance - $axial_distance_old) / $this->seconds_per_tick;

            
            $axial_speed = $axial_speed * $meters_to_mm * $seconds_to_minutes;
            $axial_speed_display = round ($axial_speed);            
            
            
            // Combine Axial and Rotation speed (ignore radial speed... it is small)
            $total_speed = sqrt($axial_speed * $axial_speed + $rotational_speed * $rotational_speed);
            $total_speed_display = round($total_speed, 0);
            
            
            // Derive some quantities required below to get vector.
            $gap = $this->mandrelRadius + $this->eyeletDistance - $radius_3d;
            $cf_angle_y = (pi()/180) * $this->calculateCFYAngle($radius_3d, $gap, $this->eyeletHeight);

          
            
            // Work out MANDREL (CF contact) <----> CF Dispenser VECTORY
            // We need to work this out, so we can determine the Z angle
            // DISTANCE RADIALLY
            $v[0] = $radius_3d * sin($cf_angle_y) - ($this->mandrelRadius + $this->eyeletDistance);
            
            // DISTANCE UP-DOWN
            $v[1] = $radius_3d * cos($cf_angle_y) - $this->eyeletHeight;
            
            // DISTANCE LONG AXIS
            if ($total_speed <> 0) {
               $angle_with_respect_to_mandrel_axis = acos($axial_speed / $total_speed);
            } else  {
               $angle_with_respect_to_mandrel_axis = pi()/2;    
            }
            
            $v[2] =  (($this->mandrelRadius + $this->eyeletDistance) - $radius_3d * sin($cf_angle_y))/ tan($angle_with_respect_to_mandrel_axis);
            $lead_distance = $v[2];
            
            // We do some rounding and convert to degrees for display.
            $angle_with_respect_to_mandrel_axis_display = round((180/pi()) * $angle_with_respect_to_mandrel_axis, 0);
                         
            // Put on to plane
            $v[0] = 0;
            // Get the Unit vector
            $v_unit = $this->vectorUnit($v);
                        
           
            // X - POS - Resin Bath
            $x_pos = ($lead_distance + ($cone_hyp - $this->nose_cone_cf_closest_approach_to_tip/Sin($theta)) * ($cot_a/$k));
            // Y - POS - Mandrel rotation
            $y_pos = round($phi_rel * $this->getMandrelRadius() * $meters_to_mm,0);
            // Z - POS - Presentation of the CF by dispenser head
            $z_pos = $this->sign($axial_speed) * round((180 / pi()) * acos($v_unit[1]),0);
            
            // We want the RELATIVE X 
            
            $this->nose_cone_points[$i]['x_travel'] = ($x_pos - $x_pos_prev);
            $this->nose_cone_points[$i]['s_travel'] = ($s_angle - $s_angle_prev);
            $this->nose_cone_points[$i]['z_angle']  = $z_pos;
            $this->nose_cone_points[$i]['feedrate'] = $total_speed_display;
            $x_pos_prev = $x_pos;
            $s_angle_prev = $s_angle;
            
            
            
if ($debug == 1) {            
            print "<tr>";
            print "<td>" . $x_pos . "</td>";
            print "<td>" . $y_pos . "</td>";
            print "<td>" . $z_pos . "</td>";
            print "<td>" . $total_speed_display . "</td>";
            print "<td>" . $s_angle . "</td>";
            print "<td>" . $rotation_speed_display . "</td>";
            print "<td>" . $axial_speed_display . "</td>";   
            print "<td>" . $angle_with_respect_to_mandrel_axis_display . "</td>";
            print "<td>" . round($lead_distance * $meters_to_mm, 0) . "</td>";
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



