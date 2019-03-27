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
    private $cf_angle;                        // Angle at which we wind the Carbon Fiber - Degrees
    private $wind_angle_per_pass;             // Degrees - the offset from the starting point
    private $cf_width;                        // Width of fiber in Meters
    private $extra_spindle_turn;              // Extra angle we turn the tube when we get to the end.
    private $straight_feed_rate;              // Rate of laydown of CF during straight sections.
    private $spindle_direction;               // Direction the spindle spins. Clockwise is default = +1
    private $transition_end_wind;             // Transition at end.
    
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
    

    /*
    private $transition_schedule = [
            0 => ['feedrate' => 1000,
                  'cf_angle'    => 45,
                  's_angle' => 18],
            1 => ['feedrate' => 1001,
                  'cf_angle'    => 60,
                  's_angle' => 18],
            2 => ['feedrate' => 1002,
                  'cf_angle'    => 75,
                  's_angle' => 18],
            3 => ['feedrate' => 1003,
                  'cf_angle'    => 82.5,
                  's_angle' => 18],
            4 => ['feedrate' => 1004,
                  'cf_angle'    => 90,
                  's_angle' => 18]
    ];
    */
    

    
    // WORKING 45 degree Schedule
    private $transition_schedule = [
            0 => ['feedrate' => 1000,
                  'cf_angle'    => 45,
                  's_angle' => 18],
            1 => ['feedrate' => 1001,
                  'cf_angle'    => 50,
                  's_angle' => 18],
            2 => ['feedrate' => 1002,
                  'cf_angle'    => 55,
                  's_angle' => 18],
            3 => ['feedrate' => 1003,
                  'cf_angle'    => 60,
                  's_angle' => 18],
            4 => ['feedrate' => 1004,
                  'cf_angle'    => 65,
                  's_angle' => 18],
            5 => ['feedrate' => 1005,
                  'cf_angle'    => 70,
                  's_angle' => 18],
            6 => ['feedrate' => 1006,
                  'cf_angle'    => 75,
                  's_angle' => 18],
            7 => ['feedrate' => 1007,
                  'cf_angle'    => 80,
                  's_angle' => 18],
            8 => ['feedrate' => 1008,
                  'cf_angle'    => 85,
                  's_angle' => 18],
            9 => ['feedrate' => 1009,
                  'cf_angle'    => 90,
                  's_angle' => 18]        
    ];

    
    /*
        // 30 Degree Schedule
        private $transition_schedule = [
            0 => ['feedrate' => 1000,
                  'cf_angle'    => 30,
                  's_angle' => 18],
            1 => ['feedrate' => 1001,
                  'cf_angle'    => 36.6667,
                  's_angle' => 18],
            2 => ['feedrate' => 1002,
                  'cf_angle'    => 43.3333,
                  's_angle' => 18],
            3 => ['feedrate' => 1003,
                  'cf_angle'    => 50,
                  's_angle' => 18],
            4 => ['feedrate' => 1004,
                  'cf_angle'    => 56.6667,
                  's_angle' => 18],
            5 => ['feedrate' => 1005,
                  'cf_angle'    => 63.3333,
                  's_angle' => 18],
            6 => ['feedrate' => 1006,
                  'cf_angle'    => 70,
                  's_angle' => 18],
            7 => ['feedrate' => 1007,
                  'cf_angle'    => 76.6667,
                  's_angle' => 18],
            8 => ['feedrate' => 1008,
                  'cf_angle'    => 83.3333,
                  's_angle' => 18],
            9 => ['feedrate' => 1009,
                  'cf_angle'    => 90,
                  's_angle' => 18]        
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
    

    public function __construct($mandrelRadius, $eyeletDistance, $cf_angle, $wind_angle_per_pass, $cf_width,
                                $extra_spindle_turn, $transition_feed_rate, $straight_feed_rate, $spindle_direction, $transition_end_wind,
                                $start_x=0,  $start_s=0) {
        
        
        
        $this->cf_angle            = $cf_angle;
        $this->eyeletDistance      = $eyeletDistance;
        $this->mandrelRadius       = $mandrelRadius;
        $this->wind_angle_per_pass = $wind_angle_per_pass;
        $this->cf_width            = $cf_width;
        $this->extra_spindle_turn  = $extra_spindle_turn;
        $this->straight_feed_rate  = $straight_feed_rate;
        $this->spindle_direction   = $spindle_direction;
        $this->transition_end_wind = $transition_end_wind;
        
        
        $this->start_x = $start_x;
        $this->start_s = $start_s;
        
        $this->current_x = $start_x;
        $this->current_s = $start_s;
        
        $this->gcodes = array();
        
        
        $this->cf_weight_per_meter = 0.8;
        $this->sig_figures = 5;
        $this->wind_time = 0;
        
        
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
    
    
    /* 
     * The horizontal component (x-axis) of the CF 
     * 
     */
    public function calculateStraightXLength() {
        return ($this->wind_angle_per_pass * 3.14159265/180) * $this->mandrelRadius/ tan(pi() * $this->cf_angle/180);
    }
    
    
    /*
     * The actual length of CF required to do the straight component
     */
    public function calculateStraightLength() {
        return ($this->wind_angle_per_pass * 3.14159265/180) * $this->mandrelRadius/ sin(pi() * $this->cf_angle/180);
    }

    /*
     * Returns length of transition in meters - this is the beginning transition and the end transition
     */
    public function getTransitionLength() {
        
        $x_travel = 0;
        
        // Calculate x travel (on spindle) for initial transition (start of each pass)
            foreach (array_reverse($this->transition_schedule) as $key => $value) {
            $cf_angle = $value['cf_angle'];
            $s_angle =  $value['s_angle'];
            $feedrate = $value['feedrate'];
            
            // The s_travel == s_ange
            $s_travel = $s_angle;
            
            // Calculate the distance in meters that the y-axis moves
            $y_travel = $this->mandrelRadius * ($s_angle * pi()/180);
            
            // Based on the angle we want AND the X travel ratio, work out how far to move the train.
            $x_travel = $x_travel + abs($y_travel / tan($cf_angle * pi()/180));
            
        }
        
        // Now add travel at end (end of each pass)
        $x_travel = $x_travel + $this->eyeletDistance/tan(pi() * $this->cf_angle / 180);
        
      return $x_travel;
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
    
    public function calculateXTravelRatio() {
        return ($this->eyeletDistance + $this->mandrelRadius)/$this->mandrelRadius;
    }
    
    
    /* 
     * This is the entire tube length, including the transition sections
     */
    public function getTubeLength() {
        return  $this->getUsefulTubeLength() + $this->total_x_transition_distance;
    }
    
   /* 
     * This is tube without the transitions - the useful section.
     */
    public function getUsefulTubeLength() {
        return  $this->calculateStraightXLength();
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
        
        
        
        $begin_transition_length = 0;   // TODO
        $end_transition_length = 0;     // TODO
        
        return  $begin_transition_length + $this->calculateStraightLength() + $end_transition_length + $extra_spindle_turn_distance; 
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

    
   
    
    public function calculateFFactor() {
        // We split this into to ... numerator and demoninator and multiplier - to make it easier to "Read"        
        $numerator = ($this->mandrelRadius * $this->wind_angle_per_pass * pi()/(180 * Tan(pi() * $this->cf_angle/180))) - $this->getUsefulTubeLength();   // Changed to Useful Tube Length
       
        $demoninator =  (cos(pi() * $this->cf_angle/180)/tan(pi()*$this->cf_angle/180) + sin(pi()*$this->cf_angle/180) - 1);
        
        $mult = 1 / (2 * $this->mandrelRadius );

        return $mult * $numerator/$demoninator;
    }
    
    
    /* 
     * Returns the total total meters of movement in BOTH transitions
     * 
     */
    public function calculateTotalXTransitionDistance() {
        
        $x_travel = 0;
        
        // Calculate x travel (on spindle) for initial transition (start of each pass)
            foreach (array_reverse($this->transition_schedule) as $key => $value) {
            $cf_angle = $value['cf_angle'];
            $s_angle =  $value['s_angle'];
            $feedrate = $value['feedrate'];
            
            // The s_travel == s_ange
            $s_travel = $s_angle;
            
            // Calculate the distance in meters that the y-axis moves
            $y_travel = $this->mandrelRadius * ($s_angle * pi()/180);
            
            // Based on the angle we want AND the X travel ratio, work out how far to move the train.
            $x_travel = $x_travel + abs($y_travel / tan($cf_angle * pi()/180));
            
        }
        
        // Now add travel at end (end of each pass)
        $x_travel = $x_travel + $this->eyeletDistance/tan(pi() * $this->cf_angle / 180);
        
      return $x_travel;
        
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
            $feedrate = $this->transition_feed_rate + 99;
            $s_travel = $this->actualCFAdvancementAngle() + $this->extra_spindle_turn;
            $this->generateYCode($s_travel, $feedrate);
        } elseif ($this->current_pass > 1 && $this->current_pass % 2 == 0 && $this->extra_spindle_turn > 0) {
            // Add the SPIN at the end (if one is requested). Purpose of this is to try and maintain tension in the CF.
            $feedrate = $this->transition_feed_rate + 98;
            $s_travel = $this->extra_spindle_turn;
            $this->generateYCode($s_travel, $feedrate); 
        }
        

        
        
        // Do the Transition
        $lead_distance = 0;
        foreach (array_reverse($this->transition_schedule) as $key => $value) {
           // print "Processing Transition: " . $value['angle'] . "<br />";
            $cf_angle_prev = $cf_angle;
            $y_travel_prev = $y_travel;
            $cf_angle = $value['cf_angle'];
            $s_angle =  $value['s_angle'];
            // $feedrate = $value['feedrate'];
            $feedrate = $this->transition_feed_rate;
            
        //    print "cf_angle: " . $cf_angle . "<br />";
        //    print "s_angle: " . $s_angle . "<br />";
        //    print "feedrate: " . $feedrate . "<br />";
            
            // The s_travel == s_ange
            $s_travel = $s_angle;
            
            // Calculate the distance in meters that the y-axis moves
            $y_travel = $this->mandrelRadius * ($s_angle * pi()/180);
            
            // Based on the angle we want AND the X travel ratio, work out how far to move the train.
            $x_travel_unscaled = abs($y_travel / tan($cf_angle * pi()/180));
            $x_travel = $direction * $x_travel_unscaled * $this->calculateXTravelRatio();
            
            // We are already leading the point at which tow hits axis...we work out what this is
            $lead_distance = $x_travel - $x_travel_unscaled;
            
            
            // Because we already lead it, we don't need to move as far horizontally
            $x_travel = $direction * ($x_travel - $lead_distance);
            
            
            // print "x_travel, y_travel = " . round($x_travel,6)*1000 . ", "  . 1000 * round($y_travel,6) . "<br />";
            $this->generateXYCode($x_travel, $s_travel, $feedrate);
        }

        
        /*
        // ARC MOVE
        $transition_direction = -1 * $direction * $this->getSpindleDirection();  // Direction of the Arc
        $x_center_pos = $direction * $this->getTransitionXPoint();
        $s_center_pos = $this->calculateYTravelDegrees(0);
        $y_travel_distance = $this->transition_radius * cos($this->cf_angle * (pi()/180));
        $x_travel = $direction * $this->transition_radius * (1 - sin($this->cf_angle * (pi()/180)));
        $s_travel = $this->calculateYTravelDegrees($y_travel_distance);
        $this->generateTransitionCode($transition_direction, $x_center_pos, $s_center_pos, $x_travel, $s_travel, $this->transition_feed_rate);
        */ 
        
        
        # Max Speed
        // $x_travel = $direction * ($this->getUsefulTubeLength() - $this->total_x_transition_distance);   // Changed to Useful Tube Length
        // $feedrate = $this->straight_feed_rate;
        // Based on the x travel (along mandrel surface), find out how far to move the spindle (angle)
        // $s_travel = $this->calculateYTravel($x_travel);
        // Multiple by factor to take into account distance eyelet is from mandrel
        // $x_travel = $this->calculateXTravelRatio() * $x_travel;
        // $this->generateXYCode($x_travel, $s_travel, $feedrate);
        // print "X_Travel: " . $x_travel . ", Feedrate: " . $feedrate . ", Y_Travel: " . $y_travel . "<br />";
        
        
        # Max Speed
        $s_travel = $this->wind_angle_per_pass;
        $feedrate = $this->straight_feed_rate;
        $x_travel = $direction * $this->calculateStraightXLength();
        $this->generateXYCode($x_travel, $s_travel, $feedrate);

          
        // Do the Transition 
        // $distance_in_front = $this->eyeletDistance / tan($this->cf_angle * pi()/180);
        // Do this by rotating 360 degrees
        $s_travel = $this->transition_end_wind;
        $feedrate = $this->transition_feed_rate + 97;
        $this->generateYCode($s_travel, $feedrate);        
        
        
        /*
        foreach ($this->transition_schedule as $key => $value) {
           // print "Processing Transition: " . $value['angle'] . "<br />";
            $cf_angle = $value['cf_angle'];
            $s_angle =  $value['s_angle'];
            $feedrate = $value['feedrate'];
            
            // The s_travel == s_ange
            $s_travel = $s_angle;
            
            // Calculate the distance in meters that the y-axis moves
            $y_travel = $this->mandrelRadius * ($s_angle * pi()/180);
            
            // Based on the angle we want AND the X travel ratio, work out how far to move the train.
            $x_travel = $direction * abs($this->calculateXTravelRatio() * $y_travel / tan($cf_angle * pi()/180));

            
            // print "X_travel, cf_angle, s_travel = " . round($x_travel,4)*1000 . ", " . $cf_angle . ", " . round($s_travel,1) . "<br />";
            $this->generateXYCode($x_travel, $s_travel, $feedrate);
        }
         * 
         */
/*        
        foreach ($this->transition_schedule as $key => $value) {
           // print "Processing Transition: " . $value['angle'] . "<br />";
            $cf_angle = $value['angle'];
            $total_travel = $direction * $value['distance'];
            $feedrate = $value['feedrate'];
            $x_travel = $total_travel * cos($cf_angle * pi()/180);
            $y_travel = abs($total_travel * sin($cf_angle * pi()/180));

            $s_travel = abs($this->calculateYTravelDegrees($y_travel));
           //  print "X_travel, cf_angle, y_angle = " . round($x_travel,4)*1000 . ", " . $cf_angle . ", " . round($y_travel,1) . "<br />";
            $this->generateXYCode($x_travel, $s_travel, $feedrate);
        }
*/

/*        
        // ARC MOVE
        $transition_direction = 1 * $direction * $this->getSpindleDirection();  // Direction of the Arc
        // The Relative Center Pos are different for the second transition in each pass. We are starting from the other end.
        $x_center_pos = -$direction * $this->transition_radius * sin($this->cf_angle * pi()/180);   // This is relative to CURRENT point
        $s_center_pos = $this->calculateYTravelDegrees($this->transition_radius * cos($this->cf_angle * pi()/180));                 // This is relative to CURRENT point.
        $y_travel_distance = $this->transition_radius * cos($this->cf_angle * (pi()/180));
        $x_travel = $direction * $this->transition_radius * (1 - sin($this->cf_angle * (pi()/180)));
        $s_travel = $this->calculateYTravelDegrees($y_travel_distance);
        $this->generateTransitionCode($transition_direction, $x_center_pos, $s_center_pos, $x_travel, $s_travel, $this->transition_feed_rate);
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
