<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Rocket
 *
 * @author joeman
 */
class Rocket {
    
    private $x_step;
    private $length;
    private $cg_position;
    private $cp_position;
    
    // Array of al the 
    private $axialForces;
    private $pointForces;
    private $pointMasses;
    private $distributedMasses;
    private $dragComponents;
    
    // Flight characteristics at MaxQ
    private $burnout_thrust;
    private $axial_acceleration;
    private $speed;
    private $angle_of_attack;
    private $air_density;
    private $burnout_mass;
    private $wind_gust_speed;
    private $lateral_acceleration;
    private $rotational_acceleration;
    
    
    public function __construct($diameter, $burnout_mass, $length, $cg_position, $cp_position,
                                $speed, $wind_gust_speed, $air_density, $burnout_thrust, $axial_acceleration,
                                $lateral_acceleration, $rotational_acceleration, $x_step) {
            
        $this->burnout_mass      = $burnout_mass;
        $this->length            = $length;
        $this->cg_position       = $cg_position;
        $this->cp_position       = $cp_position;
        $this->reference_area    = pi() * $diameter * $diameter / 4;
        
        $this->burnout_thrust    = $burnout_thrust;
        $this->speed             = $speed;
        $this->wind_gust_speed   = $wind_gust_speed;
        $this->air_density       = $air_density;
        $this->axial_acceleration   = $axial_acceleration;
        $this->lateral_acceleration = $lateral_acceleration;
        $this->rotational_acceleration = $rotational_acceleration;
        $this->angle_of_attack   = atan($this->wind_gust_speed/$this->speed);
        
        $this->x_step            = $x_step;
        
        $this->axialForces       = array();   // Thrust
        $this->pointForces       = array();   // Lift (transverse)
        $this->pointMasses       = array();
        $this->distributedMasses = array();
        $this->dragComponents    = array();
        
    }
    
    public function getLateralAcceleration() {
        return $this->lateral_acceleration;
    }
    
    public function getBurnOutThrust() {
        return $this->burnout_thrust;
    }
    
    public function getAxialAcceleration() {
        return $this->axial_acceleration;
    }
    
    public function getLiftForces() {
        return $this->pointForces;
    }
    
    public function getAxialForces() {
        return $this->axialForces;
    }

    
    public function getPointMasses() {
        return $this->pointMasses;
    }

    public function getDistributedMasses() {
        return $this->distributedMasses;
    }    
    
    public function getLength() {
         return $this->length;
    }
    
    public function getSpeed() {
        return $this->speed;
    }
    
    public function getBurnOutMass() {
        return $this->burnout_mass;
    }
    
    public function getWindGustSpeed() {
        return $this->wind_gust_speed;
    }
    
    public function addLift($lift) {
        array_push($this->pointForces, $lift);
    }
    
    public function addAxialForce($force) {
        array_push($this->axialForces, $force);
    }
        
    
    public function addPointMass($pointMass) {
        array_push($this->pointMasses, $pointMass);
    }    
    
    public function addDistributedMass($distributedMass) {
        array_push($this->distributedMasses, $distributedMass);
    }        
    
    public function getShear($x) {
         $shear = 0;
        // We break this into three "loads"
        // - Point Loads
        // - Point Masses
        // - Distributed Masses
        
        // We go through each of these, one at a time.
        foreach ($this->pointForces as $k=>$pointForce) {
            // print "Checking Force: " . $pointForce->getName() . "<br/>";
            if ($pointForce->isActivated ($x)) {
                // print "Force is ACTIVATED! <br/>";
                $shear = $shear + $pointForce->getForce();
            } else {
                // print "Force not activated at $x <br />";
            }
            
        }
        
        // We go through each of these, one at a time.
        foreach ($this->pointMasses as $k=>$pointMass) {
            
            if ($pointMass->isActivated ($x)) {
                $shear = $shear - $pointMass->getMass() * $this->getLateralAcceleration();
                
            }            
        }
        
        // We go through each of these, one at a time.
        foreach ($this->distributedMasses as $k=>$distributedMass) {
            if ($distributedMass->isActivated ($x)) {
                // Lateral component
                $shear = $shear - $distributedMass->massFraction($x) * $this->getLateralAcceleration();
                
                // Rotational component
                // print "rotational: " . $this->rotational_acceleration . "<br />";
                $shear = $shear - $this->rotational_acceleration * $distributedMass->massFraction($x) * ($this->cg_position - ($x + $this->x_step / 2));
                
            }                 
        }

        
        return $shear;
    }
    
    
    
    
    public function getAxialForce($x) {
         $axial = 0;
        // We break this into three "loads"
        // - Axial forces        (THRUST)
        // - Distributed Masses  (Drag)
        // - Point Masses        (Inertial)
        // - Distributed Masses  (Inertial)
        
         
        // Axial forces (excluding drag and interia)
        foreach ($this->axialForces as $k=>$axialForce) {
            // print "Checking Force: " . $pointForce->getName() . "<br/>";
            if ($axialForce->isActivated ($x)) {
                // print "Force is ACTIVATED! <br/>";
                $axial = $axial - $axialForce->getForce();
            } else {
                // print "Force not activated at $x <br />";
            }   
        }
        
         
        // - Distributed Masses  (Drag)
        foreach ($this->distributedMasses as $k=>$distributedMass) {
            // print "Checking Force: " . $pointForce->getName() . "<br/>";
            if ($distributedMass->isActivated ($x)) {
                // print "Force is ACTIVATED! <br/>";
                $axial = $axial + $distributedMass->getDragForce($x, $this->air_density, $this->reference_area, $this->speed);
            } else {
                // print "Force not activated at $x <br />";
            }   
        }
        
        
        // - Point Masses        (Inertial)
        foreach ($this->pointMasses as $k=>$pointMass) {
            // print $pointMass->getName() . " at x = " . $x . "<br />";
            if ($pointMass->isActivated ($x)) {
                $axial = $axial + $pointMass->getMass() * $this->getAxialAcceleration();                
            }            
        }
        
        // Distributed Masses  (Inertial)
        foreach ($this->distributedMasses as $k=>$distributedMass) {
            if ($distributedMass->isActivated ($x)) {
                // Lateral component
                $axial = $axial + $distributedMass->massFraction($x) * $this->getAxialAcceleration();                
            }                 
        }

        
        return $axial;
    }    
}
