<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<?php
require "Wind.php";

$wind = new Wind(0.0381, 30, 180, 0.005);
?>
<html>
    <head>
        <meta charset="UTF-8">
        <title>CF Winder G-Code Generator</title>
    </head>
    <body>
        <?php
        $feedRate = 1000;
        // put your code here
        print "Mandrel Circumference is : " . $wind->getMandrelCircumference() . "<br />";

        print "Tube Length: "; 
        print $wind->getTubeLength() . " meters." . "<br />";
        print "Train speed at " . $feedRate . " is: " . $wind->calculateTrainSpeed($feedRate) . " mm/min" . "<br />";
        print "Spindle speed at Feedrate: " . $feedRate . " is " . $wind->calculateSpindleSpeed($feedRate) . " degrees/min" . "<br />";
        
        print "Number of passes (in one direction): " . $wind->calculatePassesToCoverMandrel() . "<br />";
        
        print "Ideal Advance of CF Thread after integer rotations: " . $wind->idealCFAdvancement() . "<br />";
        print "Actual Advance of CF Thread after integer rotations: " . $wind->actualCFAdvancement() . "<br />";
        print "Actual Advance ANGLE of Mandrel after integer rotations: " . $wind->actualCFAdvancementAngle(). " degrees" . "<br />";
        
        $wind->generatePass();
        
        ?>
    </body> 
</html>
