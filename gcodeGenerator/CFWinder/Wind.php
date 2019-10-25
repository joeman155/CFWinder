<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Wind
 *
 * @author joema
 */
class Wind {
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
    private $transition_steps_in = 15;           // How many steps to break the transition into.
    private $transition_steps_out = 15;        // How many steps to break the transition into...for OUT part of pass.
    
    // Self-imposed limits
    private $max_feed_rate = 14000;           // Maximum feed rate (mm/min)
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
                                $layers) {
        
        
        
        // Basic dimensions of set-up        
        $this->eyeletDistance      = $eyeletDistance;
        $this->eyeletHeight        = $eyeletHeight;
        $this->mandrelRadius       = $mandrelRadius;
        $this->cf_width            = $cf_width;
        $this->spindle_direction   = $spindle_direction;
        
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
        // print "For Layer : " . $layer . ", the suface area is " . $this->calculateSurfaceArea($layer) . " and the CF width is " . $this->cf_width . "<br/>"; // JOE
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
     */
    public function startTransitionXDistance($layer)
    {
      // return $this->calculateXTravelMeters($this->layers[$layer]['transition_start_wind'], $this->getCFAngle($layer));
      // return $this->layers[$layer]['transition_start_length'];
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
        // print "Length: " . $len . "<br/>";
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
        // print "For layer " . $layer . ", " . $this->calculateCFLengthRequiredOneLayer($layer) . "<br/>";  // JOE
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
    public function calculateCFYAngle() {
        $error = 1/10000;
        for ($i=0; $i< 90; $i=$i+0.1) {
            $lhs = $this->mandrelRadius;
            $rhs = sin(pi() * $i / 180) * ($this->mandrelRadius + $this->eyeletDistance) + $this->eyeletHeight * cos(pi() * $i / 180);
           
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
    
    
     /*
      *  Create the actual movements required for the IN transition.
      * 
      * We also calculate the final X position after this transition
      */
     public function createInMoveTransitionSchedule($layer) {
         
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

       return $zpos;      
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

        // Create all the passes
        for ($layer = 0; $layer < count($this->layers); $layer++) {
            $this->calculations($layer);
          
           $this->current_pass = 0;
           for ($i = 1; $i <= $this->calculatePassesToCoverMandrel($layer) * 2; $i++) {
              $this->generatePass($layer);
           }
           
           
           // User Interation between layers.
           array_push($this->gcodes, "M1");
           
           // We want overlap between layers, so we advance by 1/2 distance for ensuring NO overlap.
           $feedrate = $this->transition_feed_rate + 91;
           $s_travel = $this->actualCFAdvancementAngle($layer)/2;  // Divisor of 2 is key here...
           $z_angle = 0;                                           // Should be at end...so z_angle should already be ZERO.
           $this->generateYCode($layer, $s_travel, $z_angle, $feedrate);           

        
        }
        
        
       // Rotation - basically make it spin a long time while moving Carriage back and forward with hot air-gun going
       // First we put the hot air-gun into position
       $x_pos =  $this->start_x + 0.170;
       array_push($this->gcodes, "G1 F6000 X" . 1000 * $x_pos);
       $this->current_x = $x_pos;  // We know the gun starting point is 170mm ahead of everything. So we don't heat the Motor!!
       
       // $this->current_s = 0;
       // We wait for user to turn on Hot Air Gun, Cut the CF ... No more tow winding...
       // ... and then press 'S' key to resume...
       array_push($this->gcodes, "M1");  
       
       
       // Each travel = about 20 seconds. i.e. 6000 mm/min
       $feedrate = 6000;
       
       // The length of the piece we are winding.
       $x_travel = ($this->max_x - $this->start_x);
      
       // $x_travel_trail = 0.17;    // 2 Meters
       $s_travel = 1800; // 1800 degrees (i.e. 5 revolutions)
       $z_angle = 0;     // No need to move this.
       $cf_angle = 45;   // Middle of the range Angle
       for ($i = 0; $i < 720; $i++) {
           $this->generateXYCode(null, $x_travel, $s_travel, $z_angle, $feedrate, $cf_angle);
           $this->generateXYCode(null, -1 * $x_travel, $s_travel, $z_angle, $feedrate, $cf_angle);
       }
        
       // Generate Post 
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

    public function calculations($layer) {
        
        $cf_angle_y = $this->calculateCFYAngle();
        // print "CF Angle X: " . $cf_angle_y . "<br/>";

        $v2 = $this->deriveVectorOriginMandrel($cf_angle_y);
        // print "v2 = (" . $v2[0] . ", " . $v2[1] . ", " . $v2[2] . ")" . "<br/>";
        
        $v3 = $this->deriveVectorDispenserMandrel($cf_angle_y);
        // print "v3 = (" . $v3[0] . ", " . $v3[1] . ", " . $v3[2] . ")" . "<br/>";
        
        $z_component = $this->deriveMaxVectorDispenserMandrel($v3, $this->layers[$layer]['cf_angle']);
        // print "Z component: " . $z_component . "<br />";
        
        // $this->layers[$layer]['lead_distance']         = $z_component;
        // $this->layers[$layer]['transition_end_length'] = $this->layers[$layer]['lead_distance'];
        $this->layer_properties[$layer]['lead_distance'] = $z_component;
        $this->layer_properties[$layer]['transition_end_length'] = $z_component;
        
        
        
        
        // Work out 'optimum' angle for this
        $this->optimum_z_angle = $this->deriveOptimumCFAngle($v3, $z_component);
        // print "Optimum z Angle: " . $this->optimum_z_angle . "<br/>"; 
         
        
        // Generate transistion data for END of Pass
        // print "IN <br />";
        $this->generateInPoints($this->layers[$layer]['transition_start_wind'], $this->transition_steps_in, $z_component, $v3);
        // print("<pre>".print_r($this->transition_in_schedule,true)."</pre>");
        
        
        // Generate transistion data for START of Pass
        // print "OUT <br />";
        $this->generateOutPoints($this->layers[$layer]['transition_end_wind'], $this->transition_steps_out, $z_component, $v3);
        // print("<pre>".print_r($this->transition_out_schedule,true)."</pre>");        
        
        
        
        // Generate array of moves to GET the transitions done
        $this->createInMoveTransitionSchedule($layer);
        $this->createOutMoveTransitionSchedule();
        
        
        // print("<pre>".print_r($this->transition_in_move,true)."</pre>");
        
        
    }    
}


