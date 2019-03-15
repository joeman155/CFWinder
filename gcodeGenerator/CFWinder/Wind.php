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
    private $mandrelRadius;                // Meters
    private $cf_angle;                     // Degrees
    private $length_multiplier;            // Multiples of length the tube is going to be.
    private $wind_angle_per_pass;          // Degrees - the offset from the starting point
    private $cf_width;                     // Width of fiber in Meters
    
    private $useful_tube_length;           // Length of tube not in Transition section
    
    
    private $max_feed_rate = 14000;           // Maximum feed rate (mm/min)
    private $min_feed_rate = 500;            // Minimum feed rate - used at ends
    
    // Three parameters below are depreciated.
//    private $transition_feed_rate = 4000;     // Feed rate we use between max and min feed rate    
//    private $min_feed_rate_distance = 0.0075; // How far we travel at the min speed
//    private $transition_feed_rate_distance = 0.02; // How far we travel in transition
    
    
    private $total_x_transition_distance;     // Total X distance travelled during transition moves (meters)
    private $total_y_transition_distance;     // Total Y "distance" travelled during transition moves (degrees)
    
    private $transition_arc_factor;           // This is ratio of radius of arc of transition to the radius of the mandrel
    private $transition_radius;               // Radius of the curve. (meters)
    private $transition_length_factor;        // How much we multiple the length (with no transition) to get length of tube that will accomodate transition
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
    private $current_y;                     // Units are degrees - this is the spindle
    private $current_pass;                  // The Pass we are on.
     
    private $start_x;
    private $start_y;
    private $gcodes;                        // Array of codes
    public  $sig_figures;                   // Number of digits after decimal point
    public  $cf_weight_per_meter;           // 1 meter of Carbon Fiber weighs 0.8grams
    
    public  $wind_time;                     // Time required to wind.
    

    public function __construct($useful_tube_length, $mandrelRadius, $cf_angle, $wind_angle_per_pass, $cf_width,
                                $length_multiplier, $start_x=0, $start_y=0) {
        $this->cf_angle = $cf_angle;
        $this->mandrelRadius = $mandrelRadius;
        $this->wind_angle_per_pass = $wind_angle_per_pass;
        $this->cf_width = $cf_width;
        $this->length_multiplier = $length_multiplier;
        
        $this->start_x = $start_x;
        $this->start_y = $start_y;
        
        $this->current_x = $start_x;
        $this->current_y = $start_y;
        
        $this->gcodes = array();
        
        
        $this->cf_weight_per_meter = 0.8;
        $this->sig_figures = 4;
        $this->wind_time = 0;
        
        $this->useful_tube_length = $useful_tube_length;        
        
        $this->transition_feed_rate = 4000;
        $this->transition_length_factor = 1.2;   // Will make this configurable later.
        //  $this->transition_radius = 180 * $this->mandrelRadius /(2 * (90 - $this->cf_angle));        
        $this->transition_arc_factor = $this->calculateFFactor();
        $this->transition_radius = $this->transition_arc_factor * $this->mandrelRadius;
        $this->total_x_transition_distance = $this->calculateTotalXTransitionDistance();
        $this->total_y_transition_distance = $this->calculateTotalYTransitionDistance();
        
        
        
        
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
        // return $this->getTubeLength() - $this->total_x_transition_distance;
        // return $this->getUsefulTubeLength() - $this->total_x_transition_distance;
        return $this->getUsefulTubeLength();
    }
    
    
    public function getTransitionLengthFactor() {
             return $this->transition_length_factor;
    }    
    
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
        // return  ($this->length_multiplier * pi() * $this->wind_angle_per_pass / 180) * $this->mandrelRadius / tan(pi() * $this->cf_angle/180);
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
    public function calculateTrainSpeed($feedRate) {
        return sqrt($feedRate * $feedRate/(1 + pow($this->wind_angle_per_pass / (1000 * $this->getTubeLength()),2)));
    }
    
    public function calculateSpindleSpeed($feedRate) {
        return $this->calculateTrainSpeed($feedRate) * $this->wind_angle_per_pass/(1000 * $this->getTubeLength());
    }
    */
    
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
        $pass_length = 2 * $this->getTransitionLength() + $this->getStraightLength(); 
        
        return $this->calculatePassesToCoverMandrel() * $pass_length * 2;
    }    
    
    public function calculateCFMetersOnePass() {
        return $this->getUsefulTubeLength() / cos(pi() * $this->cf_angle/180);  // Changed to Useful Tube Length
    }
    
    /* 
     * We choose ceil to ensure that we don't have gaps. I'd rather a "LITTLE" bit of overlap, rather than
     * gaps!
     */
    public function calculatePassesToCoverMandrel() {
        return ceil(($this->calculateCFLengthRequiredOneLayer()/2) / $this->calculateCFMetersOnePass());
    }
    
    /*
     * Given the X travel to take place, we want to calculate how far to rotate the Spindle (y-axis)
     * 
     */
    public function calculateYTravel($x_travel, $cf_angle) {
        
        if (isset($cf_angle)) {
            $cf_angle_use = $cf_angle;
        } else {
            $cf_angle_use = $this->cf_angle;
        }
       
       $y_travel = abs($x_travel) * 180 * tan($cf_angle_use * pi()/180)/(pi() * $this->mandrelRadius);
       
       return $y_travel;    
    }
    
    /*
     * Given the X travel to take place, we want to calculate how far to rotate the Spindle (y-axis)
     * 
     */
    public function calculateYTravelDegrees($y_travel_distance) {
       $y_travel = (180/pi()) * ($y_travel_distance / $this->mandrelRadius);
       
       return $y_travel;    
    }
    
    
    public function addTime($wind_time) {
        $this->wind_time = $this->wind_time + $wind_time;
    }


    public function getTime() {
        return $this->wind_time;
    }

    /*
    public function calculateTotalXTransitionDistance() {
       $total_x_travel = 0;
       foreach ($this->transition_schedule as $key => $value) {           
            $cf_angle = $value['angle'];
            $total_travel = $value['distance'];
 
            $x_travel = round($total_travel * cos($cf_angle * pi()/180), 4);
            $total_x_travel = $total_x_travel + $x_travel;
        }
        
        // We multiple by two because we have TWO transitions per pass.
        return 2 * $total_x_travel;
    }
     *
     */
    
    public function calculateTotalXTransitionDistance() {
       $d = $this->transition_radius * (1 - sin(pi() * $this->cf_angle/180));
       
        // We multiple by two because we have TWO transitions per pass.
        return 2 * $d;
    }    
    
    
    public function calculateFFactor() {
        // We split this into to ... numerator and demoninator
        
        $numerator = ($this->mandrelRadius * $this->wind_angle_per_pass * pi()/(180 * Tan(pi() * $this->cf_angle/180))) - $this->getUsefulTubeLength();   // Changed to Useful Tube Length
        
//        print "Mandrel Radius: " . $this->mandrelRadius . "<br/>";
//        print "wind_angle_per_pass " . $this->wind_angle_per_pass . "<br />";
//        print "cf angle = " . $this->cf_angle . "<br />";
//        print "LEngth: " . $this->getTubeLength() . "<br />";
        
               
//        print "Numerator: " . $numerator . "<br />";
        $demoninator =  (cos(pi() * $this->cf_angle/180)/tan(pi()*$this->cf_angle/180) + sin(pi()*$this->cf_angle/180) - 1);
        
        
        $mult = 1 / (2 * $this->mandrelRadius );
//        print "Demoninator: " . $demoninator . "<br />";
        
//        print "mult: " . $mult . "<br />";
        
        return $mult * $numerator/$demoninator;
    }
    
    /* 
     * Returns the total total # of degrees for each pass from both transitions(one at each end)
     */
    public function calculateTotalYTransitionDistance() {
        
        
       $d_linear = $this->transition_radius * cos(pi() * $this->cf_angle/180);
       
       $d_angle = (180/pi()) * $d_linear/$this->mandrelRadius;
       
        // We multiple by two because we have TWO transitions per pass.
        return 2 * $d_angle;
        
    }    
    
    /*
    public function calculateTotalYTransitionDistance() {
       $total_y_travel = 0;
       foreach ($this->transition_schedule as $key => $value) {           
            $cf_angle = $value['angle'];
            $total_travel = $value['distance'];
 
            $y_travel = round($total_travel * sin($cf_angle * pi()/180), 4);
            $total_y_travel = $total_y_travel + $y_travel;
        }
        
        // Calculate the Angle...because that is ultimately what we are interested in
        $y_angle = (180/pi()) * ($total_y_travel / $this->mandrelRadius);
        
        // We multiple by two because we have TWO transitions per pass.
        return 2 * $y_angle;
    }    
     */
    
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
        if ($this->current_pass > 1 && $this->current_pass % 2 == 1) {
            $feedrate = $this->min_feed_rate;
            $y_travel = $this->actualCFAdvancementAngle();
            $this->generateYCode($y_travel, $feedrate);
        }
        
        
        // Do the Transition
        $transition_direction = -1 * $direction;  // Direction of the Arc
        $x_center_pos = $this->current_x + $this->transition_radius;
        $y_center_pos = $this->current_y;
        $y_travel_distance = $this->transition_radius * cos($this->cf_angle * (pi()/180));
        $x_travel = $direction * $this->transition_radius * (1 - sin($this->cf_angle * (pi()/180)));
        $y_travel = $this->calculateYTravelDegrees($y_travel_distance);
        $this->generateTransitionCode($transition_direction, $x_center_pos, $y_center_pos, $x_travel, $y_travel, $this->transition_feed_rate);
        
        /*
        foreach (array_reverse($this->transition_schedule) as $key => $value) {
           // print "Processing Transition: " . $value['angle'] . "<br />";
            $cf_angle = $value['angle'];
            $total_travel = $direction * $value['distance'];
            $feedrate = $value['feedrate'];
            $x_travel = $total_travel * cos($cf_angle * pi()/180);
            $y_travel = $total_travel * sin($cf_angle * pi()/180);
            
            $y_travel = abs($this->calculateYTravelDegrees($y_travel));
          //   print "X_travel, cf_angle, y_angle = " . round($x_travel,4)*1000 . ", " . $cf_angle . ", " . round($y_travel,1) . "<br />";
            $this->generateXYCode($x_travel, $y_travel, $feedrate);
        }
          */     
        
        
        # Max Speed
        $x_travel = $direction * ($this->getUsefulTubeLength() - $this->total_x_transition_distance);   // Changed to Useful Tube Length
        $feedrate = $this->max_feed_rate;
        $y_travel = $this->calculateYTravel($x_travel);
        $this->generateXYCode($x_travel, $y_travel, $feedrate);
        // print "X_Travel: " . $x_travel . ", Feedrate: " . $feedrate . ", Y_Travel: " . $y_travel . "<br />";
        

        
        // Do the Transition
        $transition_direction = 1 * $direction;  // Direction of the Arc
        $x_center_pos = $this->current_x + $this->transition_radius;
        $y_center_pos = $this->current_y;
        $y_travel_distance = $this->transition_radius * cos($this->cf_angle * (pi()/180));
        $x_travel = $direction * $this->transition_radius * (1 - sin($this->cf_angle * (pi()/180)));
        $y_travel = $this->calculateYTravelDegrees($y_travel_distance);
        $this->generateTransitionCode($transition_direction, $x_center_pos, $y_center_pos, $x_travel, $y_travel, $this->transition_feed_rate);

        
        /*
        foreach ($this->transition_schedule as $key => $value) {
           // print "Processing Transition: " . $value['angle'] . "<br />";
            $cf_angle = $value['angle'];
            $total_travel = $direction * $value['distance'];
            $feedrate = $value['feedrate'];
            $x_travel = $total_travel * cos($cf_angle * pi()/180);
            $y_travel = abs($total_travel * sin($cf_angle * pi()/180));
            
            $y_travel = abs($this->calculateYTravelDegrees($y_travel));
           //  print "X_travel, cf_angle, y_angle = " . round($x_travel,4)*1000 . ", " . $cf_angle . ", " . round($y_travel,1) . "<br />";
            $this->generateXYCode($x_travel, $y_travel, $feedrate);
        }  
         *
         */
         
        
    }
    
    public function calculateXSpeed($feedrate, $x_travel) {
        $x_speed = abs($feedrate/ sqrt(1 + pow($this->cf_angle/$x_travel, 2)))/60;
        
        return $x_speed;
    }
    
    
    /*
     * Generate the code for the Arc
     * 
     */
    public function generateTransitionCode($transition_direction, $x_center_pos, $y_center_pos, $x_travel, $y_travel, $feedrate) {
        
        $this->current_x = $this->current_x + $x_travel;
        $this->current_y = $this->current_y + $y_travel;
        
        // Work out what operation we are using based on CW or CCW motion
        if ($transition_direction == -1) {
            $g_code_oper = "G3";
        } else {
            $g_code_oper = "G2";
        }
        
        $code_text = $g_code_oper . " X" . 1000 * round($this->current_x, $this->sig_figures) . " Y" . round($this->current_y, $this->sig_figures) . " J" . 1000 * round($x_center_pos, $this->sig_figures) . " J" . round($y_center_pos, $this->sig_figures) . " F" . $feedrate;
        
        array_push($this->gcodes, $code_text);
    }
    
    /*
     * Calculate the Tangential velocity
     * 
     * V = w r
     */
    public function calculateYSpeed($feedrate) {
        $rotation_speed = (pi()/180) * $feedrate / 60;   // Rotational rate in Radians/second
        
        $y_speed = abs($rotation_speed * $this->mandrelRadius);
        
        return $y_speed;
    }

    
    public function generateXYCode($x_travel, $y_travel, $feedrate) {
        // Calculate new positions
        $this->current_x = $this->current_x + $x_travel;
        $this->current_y = $this->current_y + $y_travel;
        
        if ($this->current_x < 0) {
            $this->current_x = 0;
        }
        
        // Calculate the time to do this maneuver
        $wind_time = abs($x_travel) / $this->calculateXSpeed($feedrate, $x_travel);
        
        // print "xyWind Time: " . $wind_time . "<br/>";
        // Add to the total time 
        $this->addTime($wind_time);
        
        $code_text = "G1 F" . $feedrate . " X" . 1000 * round($this->current_x, $this->sig_figures) . " Y" . round($this->current_y, $this->sig_figures);
        array_push($this->gcodes, $code_text);
    }
    
    public function generateYCode($y_travel, $feedrate) { 
        // Calculate new positions
        $this->current_y = $this->current_y + $y_travel;

        // Calculate the time to do this maneuver
        $y_travel_meters = (pi()/180) * $y_travel * $this->mandrelRadius;
        $wind_time = abs($y_travel_meters) / $this->calculateYSpeed($feedrate, $y_travel);
        
        // print "yWind Time: " . $wind_time . "<br/>";
        // Add to the total time 
        $this->addTime($wind_time);
        
        $code_text = "G1 F" . $feedrate . " Y" . round($this->current_y, $this->sig_figures);
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
        array_push($this->gcodes, "G1 F6000 Y" . $this->start_y);
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
