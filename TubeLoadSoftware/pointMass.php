<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * pointMass - A mass that is concentrated sufficiently that we can treat it as a point mass.
 *
 * @author joeman
 */
class pointMass {
    
    private $name;
    private $mass;
    private $position;

    public function __construct($name, $mass, $position) {
         
        $this-> name       = $name;
        $this->mass        = $mass;
        $this->position    = $position;        
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getMass() {
        return $this->mass;
    }
    
    public function isActivated($x) {
        if ($x < $this->position) {
           return false;
        }  else {
           return true;
        }
    }    
        
}
