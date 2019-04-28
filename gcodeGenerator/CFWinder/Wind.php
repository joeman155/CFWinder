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
    private $cf_angle;                        // Angle at which we wind the Carbon Fiber - Degrees
    private $wind_angle_per_pass;             // Degrees - the offset from the starting point
    private $cf_width;                        // Width of fiber in Meters
    private $extra_spindle_turn;              // Extra angle we turn the tube when we get to the end.
    private $straight_feed_rate;              // Rate of laydown of CF during straight sections.
    private $spindle_direction;               // Direction the spindle spins. Clockwise is default = +1
    private $transition_end_wind;             // How much wind mandrel at end of pass.
    private $transition_start_wind;           // Meters we start off into wind

    
    // Self-imposed limits
    private $max_feed_rate = 14000;           // Maximum feed rate (mm/min)
    private $min_feed_rate = 500;             // Minimum feed rate - used at ends
    private $transition_feed_rate;            // Feed rate of the transition

    
    
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
    
    // Holds the angle required and specific states
    private $transition_in_schedule;
    private $transition_out_schedule;
    
    // Hold the Moves required
    private $transition_in_move;
    private $transition_out_move;
    
    
    /*
    private $transition_schedule = [
            0 => ['feedrate'  => 1000,
                  'cf_angle'  => 77.9,
                  's_angle'   => 6.5,
                  'z_angle'   => 12.06],
            1 => ['feedrate'  => 1001,
                  'cf_angle'  => 70.3,
                  's_angle'   => 12,
                  'z_angle'   => 19.72],
            2 => ['feedrate'  => 1002,
                  'cf_angle'  => 62.6,
                  's_angle'   => 19.75,
                  'z_angle'   => 23.377],
            3 => ['feedrate'  => 1003,
                  'cf_angle'  => 54.6,
                  's_angle'   => 33.75,
                  'z_angle'   => 35.44],
            4 => ['feedrate'  => 1004,
                  'cf_angle'  => 50.14,
                  's_angle'   => 49.25,
                  'z_angle'   => 39.9],
            5 => ['feedrate'  => 1005,
                  'cf_angle'  => 47.3,
                  's_angle'   => 71.5,
                  'z_angle'   => 42.8],
            6 => ['feedrate'  => 1006,
                  'cf_angle'  => 46.2,
                  's_angle'   => 90,
                  'z_angle'   => 43.83]        
    ];
    */
    
    private $transition_schedule = [
            0 => ['feedrate'  => 1000,
                  'cf_angle'  => 77.9,
                  's_angle'   => 6.5,
                  'z_angle'   => 12.06,
                  'x_travel'  => 0.00433],
            1 => ['feedrate'  => 1001,
                  'cf_angle'  => 70.3,
                  's_angle'   => 5.5,
                  'z_angle'   => 7.656,
                  'x_travel'  => 0.0037],
            2 => ['feedrate'  => 1002,
                  'cf_angle'  => 62.6,
                  's_angle'   => 7.75,
                  'z_angle'   => 7.657,
                  'x_travel'  => 0.0052],
            3 => ['feedrate'  => 1003,
                  'cf_angle'  => 54.6,
                  's_angle'   => 14,
                  'z_angle'   => 8.064,
                  'x_travel'  => 0.00933],
            4 => ['feedrate'  => 1004,
                  'cf_angle'  => 50.14,
                  's_angle'   => 15.5,
                  'z_angle'   => 4.464,
                  'x_travel'  => 0.01033],
            5 => ['feedrate'  => 1005,
                  'cf_angle'  => 47.3,
                  's_angle'   => 22.25,
                  'z_angle'   => 2.8836,
                  'x_travel'  => 0.01483],
            6 => ['feedrate'  => 1006,
                  'cf_angle'  => 46.2,
                  's_angle'   => 18.5,
                  'z_angle'   => 2.22114,
                  'x_travel'  => 0.01233]        
    ];

// s_angle is the change each time ... Total of s_angle is 90 degrees 
// z_angle is the change each time
// x_travel is the absolute position.
// cf_angle is the ABSOLUTE angle
    

    public function __construct($mandrelRadius, $eyeletDistance, $eyeletHeight, $cf_angle, $wind_angle_per_pass, $cf_width,
                                $extra_spindle_turn, $transition_feed_rate, $straight_feed_rate, $spindle_direction, 
                                $transition_start_wind, $transition_end_wind, $start_x=0,  $start_s=0, $start_z=0) {
        
        
        
        $this->cf_angle            = $cf_angle;
        $this->eyeletDistance      = $eyeletDistance;
        $this->eyeletHeight        = $eyeletHeight;
        $this->mandrelRadius       = $mandrelRadius;
        $this->wind_angle_per_pass = $wind_angle_per_pass;
        $this->cf_width            = $cf_width;
        $this->extra_spindle_turn  = $extra_spindle_turn;
        $this->straight_feed_rate  = $straight_feed_rate;
        $this->spindle_direction   = $spindle_direction;
        $this->transition_end_wind = $transition_end_wind;
        $this->transition_start_wind = $transition_start_wind;
        
        
        $this->start_x = $start_x;
        $this->start_s = $start_s;
        $this->start_z = $start_z;
        
        $this->current_x = $start_x;
        $this->current_s = $start_s;
        $this->current_z = $start_z;
        
        $this->gcodes = array();
        
        
        $this->cf_weight_per_meter = 0.8;
        $this->sig_figures = 5;
        $this->wind_time = 0;
        
        
        $this->transition_feed_rate = $transition_feed_rate;

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
       
    
    public function getCFWeight() {
             return $this->cf_weight_per_meter * $this->calculateActualCFLengthRequiredOneLayer();
    }  
    
    public function getWindAnglePerPass() {
             return $this->wind_angle_per_pass;
    }
    
    public function getCFAngle() {
             return $this->cf_angle;
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
     * getTotalTubeLength 
     * 
     * Get the total tube length
     */
    public function getTotalTubeLength() {
        return $this->getTubeLength() + $this->startTransitionXDistance();
    }
    
    
    /* 
     * This is length of tube EXCEPT for the beginning transition
     */
    public function getTubeLength() {
        return ($this->wind_angle_per_pass * pi()/180) * $this->mandrelRadius/ tan(pi() * $this->cf_angle/180);
    }
    
    
   /* 
     * This is tube without the transitions - the useful section.
     */
    public function getUsefulTubeLength() {
        return  $this->getTubeLength() - $this->getTotalXTransitionDistance();
    }

    
    /*
     * Return total length of transitions
     * 
     * It is approximately equal to the lead distance
     * +
     * distance for the START transition.
     * 
     */
    public function getTotalXTransitionDistance() {
        $length = $this->startTransitionXDistance() + $this->calculateLeadDistance();  
        return $length;
    }
    
    
    /*
     * This is the distance we need to advance (meters) tangentially to ensure
     * there is no gap and no overlap of the CF fiber.
     */
    public function idealCFAdvancement() {
        return $this->cf_width /cos($this->cf_angle * pi()/180);
    }
    
    public function actualCFAdvancement() {
        return $this->getMandrelCircumference() / $this->calculatePassesToCoverMandrel();
    }    
    
    public function actualCFAdvancementAngle() {
        return round(360 * $this->actualCFAdvancement() /$this->getMandrelCircumference(),3);
    }   
 
    
    public function calculateSurfaceArea() {
        return pi() * 2 * $this->mandrelRadius * $this->getUsefulTubeLength();  // Changed to useful length
    }
    
    /*
     * This is length of CF for one pass.
     * NOTE, we need go X times in one direction and X times in the other direction. So...one could 
     *       say we actually do two layers in ONE pass.
     * 
     * NOTE: This does NOT take into account the transitional areas, where CF is unfortunately wasted
     */
    public function calculateCFLengthRequiredOneLayer() {
        return 2 * $this->calculateSurfaceArea()/ $this->cf_width;
    }
    
    public function calculateActualCFLengthRequiredOneLayer() {
        // PER PASS
        
        // We multiple by 2 (two) at beginning because we actually do TWO layers...one from left and one from right.
        return 2 * $this->calculatePassesToCoverMandrel() * $this->calculateCFMetersOnePass();
    }    

    
    /*
     * calculate startTransitionXDistance()
     * 
     * Calculate how far we move the carriage for the START transition
     */
    public function startTransitionXDistance()
    {
      return $this->calculateXTravelMeters($this->transition_start_wind);
    }
    
    /* 
     * calculateStraightXLength 
     * 
     * Calcualte the length of tube with NO transitions
     * 
     */
    public function calculateStraightXLength()
    {
        return $this->getTubeLength() - $this->calculateLeadDistance();
    }
    
    /* 
     * Calculate approximate distance the CF dispenser is in front of the point on mandrel 
     * where it makes contact
     */
    public function calculateLeadDistance()
    {
        // X axis - from mandrel to carriage
        // Y Axis - Mandrel Center-line
        // Z Axis - UP/DOWN
        
        // Based on distance of 5mm gap between the CF Dispenser and height of 10mm above X-Z plane
        // We calculated (on paper) the angle (x axis) to be 53 degrees
        $cf_mandrel_angle_y = 53 * pi() / 180;
        
        // We calculate the horizontal distance from the CF dispenser to the Mandrel,
        // where the CF makes contact with the Mandrel. This is the X Axis
        $x_distance = $this->mandrelRadius * (1 - sin($cf_mandrel_angle_y)) + $this->eyeletDistance;
        
        $y_lead = $x_distance / tan($cf_mandrel_angle_y);
        
        return $y_lead;
    }

    
    /* 
     * This calcualtes CF for just one pass from left to right. It includes transitions 
     */
    public function calculateCFMetersOnePass() {
        
        if ($this->extra_spindle_turn > 0) {
            $extra_spindle_turn_distance = (pi()/180) * $this->extra_spindle_turn * $this->mandrelRadius;
        } else {
            $extra_spindle_turn_distance = 0;
        }
        
        $begin_transition_length = 0;   // TODO
        $end_transition_length   = 0;   // TODO
        
        return  $begin_transition_length + $this->calculateStraightXLength() + $end_transition_length + $extra_spindle_turn_distance; 
    }
    
    
    /* 
     * This calcualtes CF for just the straight section (not including transition)
     */
    public function calculateCFMetersOnePassStraight() {
        return $this->getUsefulTubeLength() / cos(pi() * $this->cf_angle/180);  // Changed to Useful Tube Length
    }
    
    /* 
     * We choose ceil to ensure that we don't have gaps. I'd rather a "LITTLE" bit of overlap, rather than
     * gaps!
     * 
     * NOTE: This is ultimately to cover the "useful" length of the tube...not the transition area
     *       For the transition area, we expect the CF to be slightly thicker.
     * 
     */
    public function calculatePassesToCoverMandrel() {
        return ceil(($this->calculateCFLengthRequiredOneLayer()/2) / $this->calculateCFMetersOnePassStraight());
    }
    
    /*
     * Given the X travel to take place, we want to calculate how far to rotate the Spindle (y-axis)
     * 
     * Results is in degrees
     * 
     */
    public function calculateYTravel($x_travel, $cf_angle) {
        
        if (isset($cf_angle)) {
            $cf_angle_use = $cf_angle;
        } else {
            $cf_angle_use = $this->cf_angle;
        }
       
       $s_travel = abs($x_travel) * 180 * tan($cf_angle_use * pi()/180)/(pi() * $this->mandrelRadius);
       
       return $s_travel;    
    }
    
    /*
     * Given the Y travel distance (m) to take place, we want to calculate how far to rotate the Spindle (y-axis)
     * 
     * Result returned is in Degrees
     * 
     */
    public function calculateYTravelDegrees($y_travel_distance) {
       $s_travel = (180/pi()) * ($y_travel_distance / $this->mandrelRadius);
       
       return $s_travel;    
    }
    
    /*
     * Given the Y travel distance (degrees) to take place, we want to calculate how far to move the carriage
     * 
     * Result returned is in meters
     * 
     */
    public function calculateXTravelMeters($s_angle) {
       return ($s_angle * pi()/180) * $this->mandrelRadius/ tan(pi() * $this->cf_angle/180);
    }
    
    
    public function addTime($wind_time) {
        $this->wind_time = $this->wind_time + $wind_time;
    }


    public function getTime() {
        return $this->wind_time;
    }

        
    /*
     * Work out the angle (x-axis) that the CF makes....to be a tangent with the Mandrel
     */
    public function calculateCFXAngle() {
        $error = 1/10000;
        for ($i=0; $i< 90; $i=$i+0.1) {
            $lhs = $this->mandrelRadius;
            $rhs = sin(pi() * $i / 180) * ($this->mandrelRadius + $this->eyeletDistance) + $this->eyeletHeight * cos(pi() * $i / 180);
           // print "Angle: " . $i . ", compare " . $lhs . " with " . $rhs . "<br />";
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
     */
    public function deriveMaxVectorDispenserMandrel($v2) {
        
        $error = 0.25;
        // Start from 0 and go to 1 meter (1mm at a time)
        for ($i=0; $i< 1; $i=$i+0.0001) {
            
            $vector[0] = $v2[0];
            $vector[1] = $v2[1];
            $vector[2] = $i;
            
            $len = $this->vectorLength($vector);

            // print "before Vector = " . $vector[0] . " , " . $vector[1] . ", " . $vector[2] . "<br/>";
            
            
            $vector[0] = $vector[0]/$len;
            $vector[1] = $vector[1]/$len;
            $vector[2] = $vector[2]/$len;
            
            // print "after Vector = " . $vector[0] . " , " . $vector[1] . ", " . $vector[2] . "<br/>";
            
            $angle = acos($vector[2]) * 180 / pi();
            
            // print "scaled z = " . $vector[2] . " - Comparing $angle with " . $this->cf_angle . "<br/>";
            
            if (abs($angle - $this->cf_angle) < $error) {
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
     
        /*
        print "Mandrel Radius: " . $this->mandrelRadius . "<br/>";
        print "X distance:     " . $x_distance . "<br />";
        print "Start lead distance: " . $start_lead_distance . "<br />";
        
         * 
         */
        
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
        
        for ($i = 0; $i <= $steps; $i++) {
            $y = $i * $step_size;
            $z = $this->leadOutDistance($start_lead_distance, $y * pi()/180, $cf_distance);
            
            $cf_vector[0] = $cf_distance;
            $cf_vector[1] = $v3[1];
            $cf_vector[2] = $z;
                     
//            print "Mandrel Angle: " . $y . ", cf_vector = (" . $cf_vector[0] . ", " . $cf_vector[1] . ", " . $cf_vector[2] . ")" . "<br/>";

            $cf_vector = $this->vectorUnit($cf_vector);
            
//            print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; UNIT Mandrel Angle: " . $y . ", cf_vector = (" . $cf_vector[0] . ", " . $cf_vector[1] . ", " . $cf_vector[2] . ")" . "<br/>";
            
            // Work out the angle by which we rotate the filament to ensure the filament width is not compromised
            $new_vector[0] = 0;
            $new_vector[1] = $cf_vector[1];
            $new_vector[2] = $cf_vector[2];
            $new_vector    = $this->vectorUnit($new_vector);
            $z_axis_angle = acos($new_vector[1]) * 180 / pi();
 //           print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; UNIT Mandrel Angle: " . $y . ", $new_vector = (" . $new_vector[0] . ", " . $new_vector[1] . ", " . $new_vector[2] . ")" . "<br/>";
 //           print "z_axis_angle: " . $z_axis_angle . "<br />";                       
//            print "<br />";
            
            
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
      */
     public function createInMoveTransitionSchedule() {
         $move_len = count($this->transition_in_schedule);
         for ($i = 1; $i < $move_len; $i++) {
             $feedrate = $this->transition_in_schedule[$i]['feedrate'];  // Use the END feedrate
             $s_angle  = $this->transition_in_schedule[$i]['s_angle'] - $this->transition_in_schedule[$i-1]['s_angle'];  // Use the difference in angle
             $z_angle  = $this->transition_in_schedule[$i]['z_angle'] - $this->transition_in_schedule[$i-1]['z_angle'];   // Use the END z-angle
             $cf_angle = $this->transition_in_schedule[$i]['cf_angle'];  // USe the END cf_angle
             
             $x_travel = $this->calculateXTravelMeters($s_angle);
             
             
             $this->transition_in_move[$i-1]['feedrate'] = $feedrate;
             $this->transition_in_move[$i-1]['s_angle']  = $s_angle;
             $this->transition_in_move[$i-1]['z_angle']  = $z_angle;
             $this->transition_in_move[$i-1]['cf_angle'] = $cf_angle;
             $this->transition_in_move[$i-1]['x_travel'] = $x_travel;
         }
     }
    
     
     /*
      *  Create the actual movements required for the OUT transition.
      */
     public function createOutMoveTransitionSchedule() {

         $move_len = count($this->transition_in_schedule);
         for ($i = 1; $i < $move_len; $i++) {
             $feedrate = $this->transition_out_schedule[$i]['feedrate'];  // Use the END feedrate
             $s_angle  = $this->transition_out_schedule[$i]['s_angle'] - $this->transition_out_schedule[$i-1]['s_angle'];  // Use the difference in angle
             $z_angle  = $this->transition_out_schedule[$i]['z_angle'] - $this->transition_out_schedule[$i-1]['z_angle'];;   // Use the END z-angle
             $cf_angle = $this->transition_out_schedule[$i]['cf_angle'];  // USe the END cf_angle
             
             
             $this->transition_out_move[$i-1]['feedrate'] = $feedrate;
             $this->transition_out_move[$i-1]['s_angle']  = $s_angle;
             $this->transition_out_move[$i-1]['z_angle']  = $z_angle;
             $this->transition_out_move[$i-1]['cf_angle'] = $cf_angle;
             $this->transition_out_move[$i-1]['x_travel'] = 0;
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
        
        for ($i = 0; $i <= $steps; $i++) {
            $y = $i * $step_size;
            $z = $this->leadInDistance($start_lead_distance, $y * pi()/180, $cf_distance);
            
            $cf_vector[0] = $cf_distance;
            $cf_vector[1] = $v3[1];
            $cf_vector[2] = $z;
                     
//            print "Mandrel Angle: " . $y . ", cf_vector = (" . $cf_vector[0] . ", " . $cf_vector[1] . ", " . $cf_vector[2] . ")" . "<br/>";

            $cf_vector = $this->vectorUnit($cf_vector);
            
//            print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; UNIT Mandrel Angle: " . $y . ", cf_vector = (" . $cf_vector[0] . ", " . $cf_vector[1] . ", " . $cf_vector[2] . ")" . "<br/>";
            
            // Work out the angle by which we rotate the filament to ensure the filament width is not compromised
            $new_vector[0] = 0;
            $new_vector[1] = $cf_vector[1];
            $new_vector[2] = $cf_vector[2];
            $new_vector    = $this->vectorUnit($new_vector);
            $z_axis_angle  = acos($new_vector[1]) * 180 / pi();
//            print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; UNIT POINTING Angle: " . $y . ", $new_vector = (" . $new_vector[0] . ", " . $new_vector[1] . ", " . $new_vector[2] . ")" . "<br/>";
//            print "z_axis_angle: " . $z_axis_angle . "<br />";                      
//            print "<br />";
            
            
            // Work out angle of filament with resepect to the Mandrel axis which is the Z axis (cf_angle)
            $cf_angle = acos($cf_vector[2]) * 180 / pi();
            
            
            $this->transition_in_schedule[$i]['feedrate'] = $this->transition_feed_rate;
            $this->transition_in_schedule[$i]['s_angle']  = $y;
            $this->transition_in_schedule[$i]['z_angle']  = $z_axis_angle;
            $this->transition_in_schedule[$i]['cf_angle'] = $cf_angle;
  
                    
        }
    
    }

    
    
    public function generatePass() {
        
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
            $s_travel = $this->actualCFAdvancementAngle() + $this->extra_spindle_turn;
            $z_travel = 0 - $this->current_z;
            $this->generateYCode($s_travel, $z_travel, $feedrate);
        } elseif ($this->current_pass > 1 && $this->current_pass % 2 == 0 && $this->extra_spindle_turn > 0) {
            // Add the SPIN at the end (if one is requested). Purpose of this is to try and maintain tension in the CF.
            $feedrate = $this->transition_feed_rate + 98;
            $s_travel = $this->extra_spindle_turn;
            $z_travel = 0 - $this->current_z;
            $this->generateYCode($s_travel, $z_travel, $feedrate); 
        }
        
/*        
        // Do a transition - still go for 45 degrees, but we need to do this, so we can start to turn the Z-axis in time.
        $x_travel = $direction * $this->calculateXTravelMeters ($this->transition_start_wind);
        $s_travel = $this->transition_start_wind;
        $z_travel = $direction * $this->getSpindleDirection() * $this->cf_angle;
        $feedrate = $this->transition_feed_rate + 97;
        $this->generateXYCode($x_travel, $s_travel, $z_travel, $feedrate);     
*/
        
        // Do the Transition
        foreach ($this->transition_in_move as $key => $value) {
            $cf_angle = $value['cf_angle'];
            $s_angle =  $value['s_angle'];
            $feedrate = $value['feedrate'];
            $z_angle  = $value['z_angle'];
            $x_travel = $value['x_travel'];
            
            // The s_travel == s_angle
            $s_travel = $s_angle;

            // In the transitions we ALWAYS move at speed that will allow us to get to the desired CF ANGLE
            $x_travel = $direction * $x_travel;

            // Work out how far to rotate the z-axis
            $z_travel = $this->getSpindleDirection() * $direction * $z_angle;


            // print "x_travel, s_angle, z_angle= " . $x_travel . ", " . $s_angle . ", " . $z_angle . "<br />";
            

            $this->generateXYCode($x_travel, $s_travel, $z_travel, $feedrate);            
        }
        
        
        
        /*
        $lead_distance = 0;
        foreach ($this->transition_schedule as $key => $value) {

            if (isset($cf_angle)) {
                $cf_angle_prev = $cf_angle;
            } else {
                $cf_angle_prev = 90;
            }


            
            $y_travel_prev = $y_travel;
            $cf_angle = $value['cf_angle'];
            $s_angle =  $value['s_angle'];
            $feedrate = $value['feedrate'];
            $z_angle  = $value['z_angle'];
            $x_travel = $value['x_travel'];
            // $feedrate = $this->transition_feed_rate;


            // The s_travel == s_angle
            $s_travel = $s_angle;

            // Because we already lead it, we don't need to move as far horizontally
            $x_travel = $direction * $x_travel;
             
            // Calculate the distance in meters that the y-axis moves
            $y_travel = $this->mandrelRadius * ($s_angle * pi()/180);

            // Work out how far to rotate the z-axis
            $z_travel = $this->getSpindleDirection() * $direction * $z_angle;


            // print "x_travel, s_angle, z_angle= " . $x_travel . ", " . $s_angle . ", " . $z_angle . "<br />";
            

            $this->generateXYCode($x_travel, $s_travel, $z_travel, $feedrate);
        }
        */
        
        
        
        
        # Max Speed
        $x_travel = $direction * ($this->getTubeLength() - $this->calculateLeadDistance());
        $s_travel = $this->wind_angle_per_pass;
        $z_travel = 0;  // No change to Z axis
        $feedrate = $this->straight_feed_rate;
        $this->generateXYCode($x_travel, $s_travel, $z_travel, $feedrate);
   
        

        // Do a transition - i.e. no x-movement, only Y and Z
        foreach ($this->transition_out_move as $key => $value) {
            $cf_angle = $value['cf_angle'];
            $s_angle =  $value['s_angle'];
            $feedrate = $value['feedrate'];
            $z_angle  = $value['z_angle'];
            
            // The s_travel == s_angle
            $s_travel = $s_angle;

            // Work out how far to rotate the z-axis
            $z_travel = $this->getSpindleDirection() * $direction * $z_angle;

            $this->generateYCode($s_travel, $z_travel, $feedrate);            
        }

        
        /*
        $s_travel = $this->transition_end_wind;
        $z_travel = -$direction * $this->getSpindleDirection() * $this->cf_angle;
        $feedrate = $this->transition_feed_rate + 97;
        $this->generateYCode($s_travel, $z_travel, $feedrate);        
         * 
         */

    }
    
    
    
    
    public function calculateXSpeed($feedrate, $x_travel) {
        $x_speed = abs($feedrate/ sqrt(1 + pow($this->cf_angle/$x_travel, 2)))/60;
        
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
    public function generateXYCode($x_travel, $s_travel, $z_travel, $feedrate) {
                
        // Calculate new positions
        $this->current_x = $this->current_x + $x_travel;
        $this->current_s = $this->current_s + $s_travel;
        $this->current_z = $this->current_z + $z_travel;
        
        if ($this->current_x < 0) {
            $this->current_x = 0;
        }
        
        // Calculate the time to do this maneuver
        $wind_time = abs($x_travel) / $this->calculateXSpeed($feedrate, $x_travel);
        
        // print "xyWind Time: " . $wind_time . "<br/>";
        // Add to the total time 
        $this->addTime($wind_time);
        
        $code_text = "G1 F" . $feedrate . " X" . $this->generateXPosValue($this->current_x) . " Y" . $this->generateYPosValue($this->current_s) . " Z" . $this->generateZPosValue($this->current_z);
        array_push($this->gcodes, $code_text);
    }
    
    
    public function generateYCode($s_travel, $z_travel, $feedrate) { 
        // Calculate new positions
        $this->current_s = $this->current_s + $s_travel;
        $this->current_z = $this->current_z + $z_travel;

        // Calculate the time to do this maneuver
        // $y_travel_meters = (pi()/180) * $s_travel * $this->mandrelRadius;
        // $wind_time = abs($y_travel_meters) / $this->calculateYSpeed($feedrate, $s_travel);
        $wind_time = $s_travel / ($feedrate/60);
        
        // Add to the total time 
        // print "yWind Time: " . $wind_time . "<br/>";
        $this->addTime($wind_time);
        
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
       // Prepration
       $this->calculations();
        
       // Generate Pre-Amble
        array_push($this->gcodes, "G21");
        array_push($this->gcodes, "G64 P0.01");
        array_push($this->gcodes, "M1");
        array_push($this->gcodes, "G1 F6000 X" . 1000 * $this->start_x);
        array_push($this->gcodes, "G1 F6000 Y" . $this->start_s);
        array_push($this->gcodes, "G1 F6000 Z" . $this->start_z);        
        array_push($this->gcodes, "M1");


        for ($i = 1; $i <= $this->calculatePassesToCoverMandrel() * 2; $i++) {
          $this->generatePass();
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

    public function calculations() {
        
        $cf_angle_x = $this->calculateCFXAngle();
        print "CF Angle X: " . $cf_angle_x . "<br/>";

        $v2 = $this->deriveVectorOriginMandrel($cf_angle_x);
        print "v2 = (" . $v2[0] . ", " . $v2[1] . ", " . $v2[2] . ")" . "<br/>";
        
        $v3 = $this->deriveVectorDispenserMandrel($cf_angle_x);
        print "v3 = (" . $v3[0] . ", " . $v3[1] . ", " . $v3[2] . ")" . "<br/>";
        
        $z_component = $this->deriveMaxVectorDispenserMandrel($v3);
        print "Z component: " . $z_component . "<br />";
        
        
        // Generate transistion data for END of Pass
        print "IN <br />";
        $this->generateInPoints($this->transition_start_wind, 10, $z_component, $v3);
        print("<pre>".print_r($this->transition_in_schedule,true)."</pre>");
        
        
        // Generate transistion data for START of Pass
        print "OUT <br />";
        $this->generateOutPoints($this->transition_end_wind, 10, $z_component, $v3);
        print("<pre>".print_r($this->transition_out_schedule,true)."</pre>");        
        
        
        
        // Generate array of moves to GET the transitions done
        $this->createInMoveTransitionSchedule();
        $this->createOutMoveTransitionSchedule();
        
    }    
}


