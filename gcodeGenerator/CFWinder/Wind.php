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
    private $cf_angle;                        // Angle at which we wind the Carbon Fiber - Degrees
    private $wind_angle_per_pass;             // Degrees - the offset from the starting point
    private $cf_width;                        // Width of fiber in Meters
    private $extra_spindle_turn;              // Extra angle we turn the tube when we get to the end.
    private $useful_tube_length;              // Length of tube not in Transition section
    private $straight_feed_rate;              // Rate of laydown of CF during straight sections.
    private $spindle_direction;               // Direction the spindle spins. Clockwise is default = +1
    
    // Self-imposed limits
    private $max_feed_rate = 14000;           // Maximum feed rate (mm/min)
    private $min_feed_rate = 500;             // Minimum feed rate - used at ends
       
    
    // The four fields below are all CALCULATED...they depend upon length of tube,  cf_angle and wind_angle_per pass
    private $total_x_transition_distance;     // Total X distance travelled during transition moves (meters)
    private $total_y_transition_distance;     // Total Y "distance" travelled during transition moves (degrees)
    private $transition_arc_factor;           // This is ratio of radius of arc of transition to the radius of the mandrel
    private $transition_radius;               // Radius of the curve. (meters)
    
    
    
    private $transition_feed_rate;            // Feed rate of the transition

    
    
 
    /*
    private $transition_schedule = [
            0 => ['feedrate' => 12000,
                  'angle'    => 30,
                  'distance' => 0.005],        
            1 => ['feedrate' => 10000,
                  'angle'    => 45,
                  'distance' => 0.0075],
            2 => ['feedrate' => 8000,
                  'angle'    => 60,
                  'distance' => 0.010],
            3 => ['feedrate' => 6000,
                  'angle'    => 75,
                  'distance' => 0.015],
            4 => ['feedrate' => 6000,
                  'angle'    => 82.5,
                  'distance' => 0.03],        
            5 => ['feedrate' => 6000,
                  'angle'    => 90,
                  'distance' => 0.0120842]
    ];
     */

    
    /* Values in this array are 'picked out of the air' 
     * I do however try to have greater distances as the angle increases
     * I also want the TOTAL y angle at end of one pass to be multiple of 180 degrees.
     */
    
    /*
     * 
     * This is depreciated in preference for G2 and G3 commands
     * 
    private $transition_schedule = [
            0 => ['feedrate' => 12000,
                  'angle'    => 30,
                  'distance' => 0.005],        
            1 => ['feedrate' => 10000,
                  'angle'    => 45,
                  'distance' => 0.0075],
            2 => ['feedrate' => 8000,
                  'angle'    => 60,
                  'distance' => 0.010],
            3 => ['feedrate' => 6000,
                  'angle'    => 75,
                  'distance' => 0.015],
            4 => ['feedrate' => 6000,
                  'angle'    => 82.5,
                  'distance' => 0.03],        
            5 => ['feedrate' => 6000,
                  'angle'    => 90,
                  'distance' => 0.13177888]
    ];    

    
    */
    
    
    /*
    private $transition_schedule = [
            0 => ['feedrate' => 500,
                  'angle'    => 30,
                  'distance' => 0.008660254],        
            1 => ['feedrate' => 4000,
                  'angle'    => 30,
                  'distance' => 0.023094]
    ];    
    */
    
    private $current_x;                     // Units are mm - This is the train
    private $current_s;                     // Units are degrees - this is the spindle  (s is for spindle)
    private $current_pass;                  // The Pass we are on.
     
    private $start_x;                       // Starting position of Train - meters    
    private $start_s;                       // Starting position of Spindle - Degrees   (s is for spindle)
    private $gcodes;                        // Array of codes
    public  $sig_figures;                   // Number of digits after decimal point
    public  $cf_weight_per_meter;           // 1 meter of Carbon Fiber weighs 0.8grams
    
    public  $wind_time;                     // Time required to wind.
    

    public function __construct($useful_tube_length, $mandrelRadius, $cf_angle, $wind_angle_per_pass, $cf_width,
                                $extra_spindle_turn, $transition_feed_rate, $straight_feed_rate, $spindle_direction,
                                $start_x=0, $start_s=0) {
        
        
        
        $this->cf_angle            = $cf_angle;
        $this->mandrelRadius       = $mandrelRadius;
        $this->wind_angle_per_pass = $wind_angle_per_pass;
        $this->cf_width            = $cf_width;
        $this->extra_spindle_turn  = $extra_spindle_turn;
        $this->straight_feed_rate  = $straight_feed_rate;
        $this->spindle_direction   = $spindle_direction;
        
        
        $this->start_x = $start_x;
        $this->start_s = $start_s;
        
        $this->current_x = $start_x;
        $this->current_s = $start_s;
        
        $this->gcodes = array();
        
        
        $this->cf_weight_per_meter = 0.8;
        $this->sig_figures = 5;
        $this->wind_time = 0;
        
        $this->useful_tube_length = $useful_tube_length;        
        
        $this->transition_feed_rate = $transition_feed_rate;
        $this->transition_arc_factor = $this->calculateFFactor();
        $this->transition_radius = $this->transition_arc_factor * $this->mandrelRadius;
        $this->total_x_transition_distance = $this->calculateTotalXTransitionDistance();
        $this->total_y_transition_distance = $this->calculateTotalYTransitionDistance();

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
    
    public function getTransitionArcFactor() {
             return $this->transition_arc_factor;
    }    
    
    public function getTransitionRadius() {
             return $this->transition_radius;
    }  
    
    
    /*
     * The center point is the center of the arc(circle) that we must identify and code in G-Code
     * There are two parts
     * - x
     * - y
     * 
     * The y = same as current y,
     * X = radius of arc
     * 
     * We return the X component in meters
     */
    public function getTransitionXPoint() {
        return $this->getTransitionRadius();
    }
    
    public function getStraightLength() {
        return $this->getUsefulTubeLength();
    }
    

    /*
     * Returns length of transition in meters
     */
    public function getTransitionLength() {
             return (pi()/180) * (90 - $this->cf_angle) * $this->getTransitionRadius();
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
    
    
    /* 
     * This is the entire tube length, including the transition sections
     */
    public function getTubeLength() {
        return  $this->useful_tube_length + $this->total_x_transition_distance;
    }
    
   /* 
     * This is tube without the transitions - the useful section.
     */
    public function getUsefulTubeLength() {
        return  $this->useful_tube_length;
    }

    
    public function calculateArcRadius() {
        return $this->transition_arc_factor * $this->mandrelRadius;
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
        /*
        $pass_length = 2 * $this->getTransitionLength() + $this->getStraightLength(); 
        
        
        if ($this->extra_spindle_turn > 0) {
            $extra_spindle_turn_distance = (180/pi()) * $this->extra_spindle_turn * $this->mandrelRadius;
        } else {
            $extra_spindle_turn_distance = 0;
        }
        
        return $this->calculatePassesToCoverMandrel() * ($pass_length * 2 + $extra_spindle_turn_distance);
         *
         */
        
        
        // We multiple by 2 (two) at beginning because we actually do TWO layers...one from left and one from right.
        return 2 * $this->calculatePassesToCoverMandrel() * $this->calculateCFMetersOnePass();
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
        
        return 2 * $this->getTransitionLength() + $this->getStraightLength() + $extra_spindle_turn_distance; 
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
    
    
    public function addTime($wind_time) {
        $this->wind_time = $this->wind_time + $wind_time;
    }


    public function getTime() {
        return $this->wind_time;
    }

    
    public function calculateTotalXTransitionDistance() {
       $d = $this->transition_radius * (1 - sin(pi() * $this->cf_angle/180));
       
        // We multiple by two because we have TWO transitions per pass.
        return 2 * $d;
    }    
    
    
    public function calculateFFactor() {
        // We split this into to ... numerator and demoninator and multiplier - to make it easier to "Read"        
        $numerator = ($this->mandrelRadius * $this->wind_angle_per_pass * pi()/(180 * Tan(pi() * $this->cf_angle/180))) - $this->getUsefulTubeLength();   // Changed to Useful Tube Length
       
        $demoninator =  (cos(pi() * $this->cf_angle/180)/tan(pi()*$this->cf_angle/180) + sin(pi()*$this->cf_angle/180) - 1);
        
        $mult = 1 / (2 * $this->mandrelRadius );

        return $mult * $numerator/$demoninator;
    }
    
    
    /* 
     * Returns the total total # of degrees for each pass from both transitions(one at each end)
     */
    public function calculateTotalYTransitionDistance() {
        
       // Calculate  (in meters) the y-distance (meters)
       $y_linear = $this->transition_radius * cos(pi() * $this->cf_angle/180);
       
       // Convert this to angle
       $s_angle = (180/pi()) * $y_linear/$this->mandrelRadius;
       
        // We multiple by two because we have TWO transitions per pass.
        return 2 * $s_angle;
        
    }    
    
    
    public function getTotalXTransitionDistance() {
        return $this->total_x_transition_distance;
    }
    
    public function getTotalYTransitionDistance() {
        return $this->total_y_transition_distance;
    }    
    
    public function generatePass() {
        
        $this->current_pass++;
        
        if ($this->current_pass %2 == 0) {
            $direction = -1;
        } else
        {
            $direction = +1;
        }
        


        // Only advance if on 3,5,7... pass. i.e. not on first pass or even pass
        // We use transision feed rates.
        if ($this->current_pass > 1 && $this->current_pass % 2 == 1) {
            $feedrate = $this->transition_feed_rate;
            $s_travel = $this->actualCFAdvancementAngle() + $this->extra_spindle_turn;
            $this->generateYCode($s_travel, $feedrate);
        } elseif ($this->current_pass > 1 && $this->current_pass % 2 == 0 && $this->extra_spindle_turn > 0) {
            // Add the SPIN at the end (if one is requested). Purpose of this is to try and maintain tension in the CF.
            $feedrate = $this->transition_feed_rate;
            $s_travel = $this->extra_spindle_turn;
            $this->generateYCode($s_travel, $feedrate);            
        }
        

        
        // Do the Transition
        $transition_direction = -1 * $direction * $this->getSpindleDirection();  // Direction of the Arc
        $x_center_pos = $direction * $this->getTransitionXPoint();
        $s_center_pos = $this->calculateYTravelDegrees(0);
        $y_travel_distance = $this->transition_radius * cos($this->cf_angle * (pi()/180));
        $x_travel = $direction * $this->transition_radius * (1 - sin($this->cf_angle * (pi()/180)));
        $s_travel = $this->calculateYTravelDegrees($y_travel_distance);
        $this->generateTransitionCode($transition_direction, $x_center_pos, $s_center_pos, $x_travel, $s_travel, $this->transition_feed_rate);
         
        
        
        # Max Speed
        $x_travel = $direction * ($this->getUsefulTubeLength() - $this->total_x_transition_distance);   // Changed to Useful Tube Length
        $feedrate = $this->straight_feed_rate;
        $s_travel = $this->calculateYTravel($x_travel);
        $this->generateXYCode($x_travel, $s_travel, $feedrate);
        // print "X_Travel: " . $x_travel . ", Feedrate: " . $feedrate . ", Y_Travel: " . $y_travel . "<br />";
        

          
        // Do the Transition
        $transition_direction = 1 * $direction * $this->getSpindleDirection();  // Direction of the Arc
        // The Relative Center Pos are different for the second transition in each pass. We are starting from the other end.
        $x_center_pos = -$direction * $this->transition_radius * sin($this->cf_angle * pi()/180);   // This is relative to CURRENT point
        $s_center_pos = $this->calculateYTravelDegrees($this->transition_radius * cos($this->cf_angle * pi()/180));                 // This is relative to CURRENT point.
        $y_travel_distance = $this->transition_radius * cos($this->cf_angle * (pi()/180));
        $x_travel = $direction * $this->transition_radius * (1 - sin($this->cf_angle * (pi()/180)));
        $s_travel = $this->calculateYTravelDegrees($y_travel_distance);
        $this->generateTransitionCode($transition_direction, $x_center_pos, $s_center_pos, $x_travel, $s_travel, $this->transition_feed_rate);
        
        
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
     * Generate the code for the Arc
     * 
     * $transition_direction - direction of the arc in Z axis
     * $x_center_pos         - position relative to CURRENT x position - in meters
     * $s_center_pos         - position relative to CURRENT s position - in degrees
     * $x_travel             - Distance to travel in X direction - in meters
     * $s_travel             - Distance to travel in S direction - in degrees
     * $feedrate             - The feedrate!
     * 
     */
    public function generateTransitionCode($transition_direction, $x_center_pos, $s_center_pos, $x_travel, $s_travel, $feedrate) {
        
        $this->current_x = $this->current_x + $x_travel;
        $this->current_s = $this->current_s + $s_travel;
        
        // Work out what operation we are using based on CW or CCW motion
        if ($transition_direction == -1) {
            $g_code_oper = "G2";
        } else {
            $g_code_oper = "G3";
        }
        
        $code_text = $g_code_oper . " X" . $this->generateXPosValue($this->current_x) . " Y" . $this->generateYPosValue($this->current_s) . " I" . $this->generateXPosValue($x_center_pos) . " J" . $this->generateYPosValue($s_center_pos) . " F" . $feedrate;
        
        array_push($this->gcodes, $code_text);
        
        // Calculate the time to do this maneuver
        $wind_time = (1000 * $this->getTransitionLength()) / ($feedrate/60);
        
        // print "trWind Time: " . $wind_time . "<br/>";
        // Add to the total time 
        $this->addTime($wind_time);        
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

    
    public function generateXYCode($x_travel, $s_travel, $feedrate) {
        // Calculate new positions
        $this->current_x = $this->current_x + $x_travel;
        $this->current_s = $this->current_s + $s_travel;
        
        if ($this->current_x < 0) {
            $this->current_x = 0;
        }
        
        // Calculate the time to do this maneuver
        $wind_time = abs($x_travel) / $this->calculateXSpeed($feedrate, $x_travel);
        
        // print "xyWind Time: " . $wind_time . "<br/>";
        // Add to the total time 
        $this->addTime($wind_time);
        
        $code_text = "G1 F" . $feedrate . " X" . $this->generateXPosValue($this->current_x) . " Y" . $this->generateYPosValue($this->current_s);
        array_push($this->gcodes, $code_text);
    }
    
    public function generateYCode($s_travel, $feedrate) { 
        // Calculate new positions
        $this->current_s = $this->current_s + $s_travel;

        // Calculate the time to do this maneuver
        // $y_travel_meters = (pi()/180) * $s_travel * $this->mandrelRadius;
        // $wind_time = abs($y_travel_meters) / $this->calculateYSpeed($feedrate, $s_travel);
        $wind_time = $s_travel / ($feedrate/60);
        
        // Add to the total time 
        // print "yWind Time: " . $wind_time . "<br/>";
        $this->addTime($wind_time);
        
        $code_text = "G1 F" . $feedrate . " Y" . $this->generateYPosValue($this->current_s);
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
    
}
