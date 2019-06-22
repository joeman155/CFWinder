<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * A distributedMass is some cylindrical shaped mass that sits inside the rocket.
 * It could be a parachute, or it could be a motor case
 * 
 * Assumptions: Mass is distributed evenly.
 * 
 *
 * @author joeman
 */
class distributedMass {
    
    private $g = 9.81;         // Acceleration ms-2
    private $name;
    private $mass;
    private $length;
    private $start_distance;   // Distance from AFT end of the rocket
    private $drag_coefficient; // Drag co-efficient. This is set to zero for components that do not contribute to drag
    
    
    public function __construct($name, $mass, $length, $start_distance, $drag_coefficient = 0) {
            
        $this->name           = $name;
        $this->mass           = $mass;
        $this->length         = $length;
        $this->start_distance = $start_distance;
        $this->drag_coefficient = $drag_coefficient;
        
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getMass() {
        return $this->mass;
    }
    
    public function isActivated($x) {
        if ($x < $this->start_distance) {
           return false;
        }  else {
           return true;
        }
    }
    
    // Because the mass is a continous mass acting over a certain distance, we need to find
    // out the fraction of the mass that we need to consider. 
    //
    public function massFraction($x) {
       if ($x > $this->start_distance + $this->length) {
           return $this->mass;
       } else if ($x < $this->start_distance) {
           return 0;
       } else {
           $mass = $this->mass * ($x - $this->start_distance) / $this->length;
           return $mass;
       }
    }
    
    // We calculate the drag that this component contributes
    //
    public function getDragForce($x, $density, $reference_area, $velocity) {
        $drag = 0;
        $drag_total = 0.5 * $density * $reference_area * $this->drag_coefficient * $velocity * $velocity;
       if ($x > $this->start_distance + $this->length) {
           $drag = $drag_total;
       } else if ($x < $this->start_distance) {
           return 0;
       } else {
           $fraction = ($x - $this->start_distance) / $this->length;
           $drag = $fraction * $drag_total;
       }
       
       return $drag;
    }    
}
