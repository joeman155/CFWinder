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
    private $wind_angle_per_pass;          // Degrees - the offset from the starting point
    private $cf_width;                     // Width of fiber in Meters
    
    
    private $max_feed_rate = 12000;          // Maximum feed rate (mm/min)
    private $transition_feed_rate = 3000;   // Feed rate we use between max and min feed rate
    private $min_feed_rate = 500;           // Minimum feed rate - used at ends
    private $min_feed_rate_distance = 0.0075; // How far we travel at the min speed
    private $transition_feed_rate_distance = 0.015; // How far we travel in transition
    
    
    private $current_x;                     // Units are mm - This is the train
    private $current_y;                     // Units are degrees - this is the spindle
    private $current_pass;                  // The Pass we are on.
     
    private $start_x;
    private $start_y;
    private $gcodes;                        // Array of codes
    public $sig_figures = 4;               // Number of digits after decimal point
    

    public function __construct($mandrelRadius, $cf_angle, $wind_angle_per_pass, $cf_width,
                                $start_x=0, $start_y=0) {
        $this->cf_angle = $cf_angle;
        $this->mandrelRadius = $mandrelRadius;
        $this->wind_angle_per_pass = $wind_angle_per_pass;
        $this->cf_width = $cf_width;
        
        $this->start_x = $start_x;
        $this->start_y = $start_y;
        
        $this->current_x = $start_x;
        $this->current_y = $start_y;
        
        $this->gcodes = array();
        
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
    
    public function getTubeLength() {
        return (pi() * $this->wind_angle_per_pass / 180) * $this->mandrelRadius / tan(pi() * $this->cf_angle/180);
    }
    
    public function calculateTrainSpeed($feedRate) {
        return sqrt($feedRate * $feedRate/(1 + pow($this->wind_angle_per_pass / (1000 * $this->getTubeLength()),2)));
    }
    
    public function calculateSpindleSpeed($feedRate) {
        return $this->calculateTrainSpeed($feedRate) * $this->wind_angle_per_pass/(1000 * $this->getTubeLength());
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
        return pi() * 2 * $this->mandrelRadius * $this->getTubeLength();
    }
    
    public function calculateCFLengthRequired() {
        return $this->calculateSurfaceArea()/ $this->cf_width;
    }
    
    public function calcualteCFMetersOnePass() {
        return $this->getTubeLength() / cos(pi() * $this->cf_angle/180);
    }
    
    /* 
     * We choose ceil to ensure that we don't have gaps. I'd rather a "LITTLE" bit of overlap, rather than
     * gaps!
     */
    public function calculatePassesToCoverMandrel() {
        return ceil($this->calculateCFLengthRequired() / $this->calcualteCFMetersOnePass());
    }
    
    /*
     * Given the X travel to take place, we want to calculate how far to rotate the Spindle (y-axis)
     * 
     */
    public function calculateYTravel($x_travel) {
       $y_travel = abs($x_travel) * 180 * tan($this->cf_angle * pi()/180)/(pi() * $this->mandrelRadius);
       
       return $y_travel;    
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
        
        
        # Min speed
        $x_travel = $direction * $this->min_feed_rate_distance;
        $feedrate = $this->min_feed_rate;
        $y_travel = $this->calculateYTravel($x_travel);
        $this->generateXYCode($x_travel, $y_travel, $feedrate);
        # print "X_Travel: " . $x_travel . ", Feedrate: " . $feedrate . ", Y_Travel: " . $y_travel . "<br />";
        
        
        # Transition Speed
        $x_travel = $direction * $this->transition_feed_rate_distance;
        $feedrate = $this->transition_feed_rate;
        $y_travel = $this->calculateYTravel($x_travel);
        $this->generateXYCode($x_travel, $y_travel, $feedrate);
        // print "X_Travel: " . $x_travel . ", Feedrate: " . $feedrate . ", Y_Travel: " . $y_travel . "<br />";
        
        # Max Speed
        $x_travel = $direction * ($this->getTubeLength() - 2 * ($this->min_feed_rate_distance + $this->transition_feed_rate_distance));
        $feedrate = $this->max_feed_rate;
        $y_travel = $this->calculateYTravel($x_travel);
        $this->generateXYCode($x_travel, $y_travel, $feedrate);
        // print "X_Travel: " . $x_travel . ", Feedrate: " . $feedrate . ", Y_Travel: " . $y_travel . "<br />";
        
        # Transition Speed
        $x_travel = $direction * $this->transition_feed_rate_distance;
        $feedrate = $this->transition_feed_rate;
        $y_travel = $this->calculateYTravel($x_travel);
        $this->generateXYCode($x_travel, $y_travel, $feedrate);
        // print "X_Travel: " . $x_travel . ", Feedrate: " . $feedrate . ", Y_Travel: " . $y_travel . "<br />";
        
        # Min Speed
        $x_travel = $direction * $this->min_feed_rate_distance;
        $y_travel = $this->calculateYTravel($x_travel);
        $feedrate = $this->min_feed_rate;
        $this->generateXYCode($x_travel, $y_travel, $feedrate);
        // print "X_Travel: " . $x_travel . ", Feedrate: " . $feedrate . ", Y_Travel: " . $y_travel . "<br />";
    }
    
    public function generateXYCode($x_travel, $y_travel, $feedrate) {
        // Calculate new positions
        $this->current_x = $this->current_x + $x_travel;
        $this->current_y = $this->current_y + $y_travel;
        
        if ($this->current_x < 0) {
            $this->current_x = 0;
        }
        
        $code_text = "G1 F" . $feedrate . " X" . 1000 * round($this->current_x, $this->sig_figures) . " Y" . round($this->current_y, $this->sig_figures);
        array_push($this->gcodes, $code_text);
    }
    
    public function generateYCode($y_travel, $feedrate) { 
        // Calculate new positions
        $this->current_y = $this->current_y + $y_travel;
        
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
