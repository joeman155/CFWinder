<?php
require "Rocket.php";
require "distributedMass.php";
require "pointMass.php";
require "pointForce.php";
?>
<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>Tube Load Software</title>
    </head>
    <body>
        <?php
        
        
        $x_step = 0.025;
        $diameter = 0.08;              // Outer diameter of Airframe
        $burnout_mass = 8;             // Approx Weight (kg) of rocket at burnout (~1.6 seconds)
        $length = 2.5;                 // Length of rocket
        $cg_position = 1;              // Position of CP from tail
        $cp_position = 0.8;            // Position of CG from Tail
        $speed = 720;                  // Speed of rocket at this instant
        $wind_gust_speed = 10;         // Sudden wind gust
        $burnout_thrust  = 4000;       // M3700WT motor @ approx 1.6 seconds into burn in OR.
        $axial_acceleration = 425;     // Taken from L3 OR sim for M3700 at 1.6 seconds, peak acceleration.
        $air_density = 1.2;            // Air density at about 500m   
        $lateral_acceleration = 200;   // m/s/s
        $rotation_acceleration = -160;   // rad/s/s
        
        $rocket = new Rocket($diameter, $burnout_mass, $length, $cg_position, $cp_position,
                             $speed, $wind_gust_speed, $air_density, $burnout_thrust,
                             $axial_acceleration, $lateral_acceleration, $rotation_acceleration, $x_step);
        
        
        // Define Lift forces
        $thrust = new pointForce("Fin Lift", $rocket->getBurnOutThrust(), 0.0);  // Force of 1000 N, 0.1 from AFT end (CP of fins)
        $rocket->addAxialForce($thrust);

        
        /* LATERAL FORCES */
        
        // Fin Lift forces
        $lift_fin = new pointForce("Fin Lift", 1000, 0.1);  // Force of 1000 N, 0.1 from AFT end (CP of fins)
        $rocket->addLift($lift_fin);
        
        // Nose Cone
        $lift_nosecone = new pointForce("Nose Cone", 1000, 1.9);  // Force of 1000 N, 0.1 from AFT end (CP of fins)
        $rocket->addLift($lift_nosecone);         
        
        
        /* DEFINE DISTRIBUTED MASSES */
        
        // Body Tube
        $drag_coefficient = 0.125;   // Mostly friction drag for Body Tube (not pressure drag)
        $mass = 1.644;               // Mass from Open Rocket
        $length = 1.6;               // Length of this component
        $body_tube_mass = new distributedMass("Body Tube", $mass, $length, 0, $drag_coefficient);
        $rocket->addDistributedMass($body_tube_mass);
        
        
        // Payload Tube
        $drag_coefficient = 0.125;   // Mostly friction drag for Body Tube (not pressure drag)
        $mass = 0.411;               // Mass from Open Rocket
        $length = 0.4;               // Length of this component
        $start  = 1.65;              // From Open Rocket
        $body_tube_mass = new distributedMass("Payload Tube", $mass, $length, 0, $drag_coefficient);
        $rocket->addDistributedMass($body_tube_mass);
        
        
        // Fins
        $drag_coefficient = 0.125;   // Mostly friction drag for Body Tube (not pressure drag)
        $mass = 0.543;               // Mass from Open Rocket
        $length = 0.4;               // Length of this component
        $start  = 0.18;              // From Open Rocket (Root = 28cm, tip = 8cm)... averaged 8 + (28 - 8)/2
        $fins = new distributedMass("Fins", $mass, $length, 0, $drag_coefficient);
        $rocket->addDistributedMass($fins);        
        
        
        // Drogue Parachute contribution
        $drogue_mass        = 0.170;  // From Open rocket
        $drogue_pack_length = 0.215;  // From Open rocket
        $drogue_start       = 1.28;   // Starts 1.28 meters from AFT
        $axial_force_position = 1.6;  // Ultimately this acts on the rocket tube at top
        $drogue_chute_mass = new distributedMass("Drogue Chute", $drogue_mass, $drogue_pack_length, $drogue_start, 0, $axial_force_position);
        $rocket->addDistributedMass($drogue_chute_mass);
        
        
        // Main Parachute contribution
        $main_mass        = 0.960;   // From Open Rocket
        $main_pack_length = 0.255;   // From Open Rocket
        $main_start       = 1.82;    // From Open Rocket
        $axial_force_position = 1.6; // Ultimately this acts on the rocket tube at top
        $main_chute_mass = new distributedMass("Main Chute", $main_mass, $main_pack_length, $main_start, 0, $axial_force_position);
        $rocket->addDistributedMass($main_chute_mass);
        
        // Motor casing (inside)
        //  For Axial - acting on rear of rocket, where thrust is applied. 
        //  For Shear - acting on air-frame of rocket
        //
        $mass  = 3;          // From Open Rocket  (difference between burn out mass and rocket without any motor)
        $length = 0.803;     // Length of motor with reload
        $start = 0;          // From Open Rocket  (in center of coupler tube that it resides in)
        $axial_force_position = 0; // The axial inertia component acts at the AFT end of the rocket
        $motor_case = new distributedMass("Motor Case", $mass, $length, $position, 0, $axial_force_position);
        $rocket->addDistributedMass($motor_case); 
        
        
        /* POINT MASSES */
        
        // Avionics Bay and Electronics and Batteries
        $mass = 0.250;      // From Open Rocket
        $position = 1.625;  // From Open Rocket  (in center of coupler tube that it resides in)
        $bay = new pointMass("Avionics Bay", $mass, $position);
        $rocket->addPointMass($bay);
        
        
        // Avionics Bay and Electronics and Batteries
        $mass = 0.2150;      // From Open Rocket
        $position = 1.625;  // From Open Rocket  (in center of coupler tube that it resides in)
        $gopro = new pointMass("Go Pro", $mass, $position);
        $rocket->addPointMass($gopro);

        
        // Spot Messanger
        $mass = 0.114;      // From Open Rocket
        $position = 2.05;   // From Open Rocket  (in center of coupler tube that it resides in)
        $spot = new pointMass("Spot Messenger", $mass, $position);
        $rocket->addPointMass($spot);

        
        // Stability Mass
        $mass = 0.10;       // From Open Rocket
        $position = 2.15;   // From Open Rocket  (in center of coupler tube that it resides in)
        $stability_mass = new pointMass("Stability Mass", $mass, $position);
        $rocket->addPointMass($stability_mass);        
        
        
        // Nose Cone - treated as a point load on the end of the Payload Bay.
        $mass = 0.250;     // From Open Rocket
        $position = 2.05;  // TOp of Payload Tube (from OR)
        $nose_cone = new pointMass("Nose Cone", $mass, $position);
        $rocket->addPointMass($nose_cone);
        
        
        // WE ARE NEGLECTING NOSE CONE DRAG.
        // I don't think it has any bearing as we know the maximum loads will be at back
        
        
        
        print "Number of axial forces on rocket:       " . count($rocket->getAxialForces()) . "<br />";
        print "Number of transverse forces on rocket:       " . count($rocket->getLiftForces()) . "<br />";
        print "Number of point masses on rocket: " . count($rocket->getPointMasses()) . "<br />";        
        print "Number of distributed masses:     " . count($rocket->getDistributedMasses()) . "<br />";
        
        print "<h2>Max Q Conditions</h2><br/>";
        print "Rocket Speed: " . $rocket->getSpeed() . " m/s" . "<br />";
        print "Burnout Mass: " . $rocket->getBurnOutMass() . " kg" . "<br />";
        print "Acceleration: " . $rocket->getAxialAcceleration() .  " m/s/s" .  "<br />";
        
        print "<h2>Rocket Components and their mass</h2>";
        print "<table>";
        print "<tr>";
        print    "<th>Component</th>";
        print    "<th>Mass</th>";
        print "</tr>";
        
        // - Point Masses        (Inertial)
        $mass_tally = 0;
        foreach ($rocket->getPointMasses() as $k=>$pointMass) {
            print "<tr>";
            print "<td>" . $pointMass->getName() . "</td><td>" . $pointMass->getMass() . "</td>";          
            print "</tr>";
            
            $mass_tally = $mass_tally + $pointMass->getMass();
        }
        
        // Distributed Masses  (Inertial)
        foreach ($rocket->getDistributedMasses() as $k=>$distributedMass) {
            print "<tr>";
            print "<td>" . $distributedMass->getName() . "</td><td>" . $distributedMass->getMass() . "</td>";          
            print "</tr>";            
            
            $mass_tally = $mass_tally + $distributedMass->getMass();
        }
        
        print "</table>";
        
        print "Total Mass of Components: " . $mass_tally . "<br/>";
        
        
        
        print "<h2>Calculate Shear loadings</h2><br />";
        for ($x = 0; $x <= $rocket->getLength()+0.00001; $x = $x + $x_step) {
            
            print "x : " . $x . " --- "; 
            
            $shear_force = $rocket->getShear($x);
            
            print $shear_force . "<br />";
            
        }
        
        
        print "<h2>Calculate Axial loadings</h2><br />";
        print "<table>";
        print "<tr><th>X Pos</th><th>Axial Force (N)</th></tr>";
        for ($x = 0; $x <= $rocket->getLength()+0.00001; $x = $x + $x_step) {
            
            print "<tr>";
            
            print "<td>" . $x . "</td>";
            
            
            $axial_loading = $rocket->getAxialForce($x);
            
            print "<td>" . $axial_loading . "</td>";
            
            print "</tr>";
            
        }        
        print "</table>";
        ?>
    </body>
</html>
