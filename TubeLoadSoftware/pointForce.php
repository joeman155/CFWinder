<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * pointForce - A force that acts at a specific point.
 *
 * @author joema
 */
class pointForce {
    private $name;
    private $force;
    private $position;

    public function __construct($name, $force, $position) {
        $this->name        = $name;
        $this->force       = $force;
        $this->position    = $position;        
    }
    
    
    public function isActivated($x) {
        if ($x < $this->position) {
           return false;
        }  else {
           return true;
        }
    }    
    
    public function getForce() {
        return $this->force; 
    }
    
    public function getName() {
        return $this->name;
    }
}
