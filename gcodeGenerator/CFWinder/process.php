<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<?php
require "Wind.php";


$useful_tube_length  = $_REQUEST['useful_tube_length'];
$mandrelRadius       = $_REQUEST['mandrelRadius'];
$cf_width            = $_REQUEST['cf_width'];
$cf_angle            = $_REQUEST['cf_angle'];
$wind_angle_per_pass = $_REQUEST['wind_angle_per_pass'];
$extra_spindle_turn  = $_REQUEST['extra_spindle_turn'];
$start_x             = $_REQUEST['start_x'];
$transition_feed_rate  = $_REQUEST['transition_feed_rate'];
$straight_feed_rate  = $_REQUEST['straight_feed_rate'];
$spindle_direction   = $_REQUEST['spindle_direction'];
$eyeletDistance      = $_REQUEST['eyeletDistance'];
$eyeletHeight        = $_REQUEST['eyeletHeight'];
$transition_end_wind = $_REQUEST['transition_end_wind'];
$transition_start_wind = $_REQUEST['transition_start_wind'];


    
$wind = new Wind($mandrelRadius, $eyeletDistance, $eyeletHeight, $cf_angle, $wind_angle_per_pass, $cf_width, $extra_spindle_turn, 
                 $transition_feed_rate, $straight_feed_rate, $spindle_direction, $transition_start_wind, $transition_end_wind, $start_x);

$wind->generateGCodes();
?>
<html>
    <head>
        <meta charset="UTF-8">
        <title>CF Winder G-Code Generator</title>
    </head>
    <body>
        <h1> Inputed Parameters</h1>
        <table>
            <tr>
                <th>
                    Parameter
                </th>
                <th>
                    Value
                </th>
            </tr>   
            <tr>
                <td>Mandrel Radius</td>
                <td><?=round($wind->getMandrelRadius(), $wind->sig_figures)?> meters</td>
            </tr>              
            <tr>
                <td>Eyelet Distance</td>
                <td><?=round($wind->getEyeletDistance(), $wind->sig_figures)?> meters</td>
            </tr>        
            <tr>
                <td>Eyelet Height</td>
                <td><?=round($wind->getEyeletHeight(), $wind->sig_figures)?> meters</td>
            </tr>     
            <tr>
                <td>Transition Start Wind</td>
                <td><?=round($wind->getTransitionStartWind(), $wind->sig_figures)?> degrees</td>
            </tr>     
            <tr>
                <td>Transition End Wind</td>
                <td><?=round($wind->getTransitionEndWind(), $wind->sig_figures)?> degrees</td>
            </tr>              
            <tr>
                <td>Carbon Fiber Laydown Angle</td>
                <td><?=$wind->getCFAngle()?> degrees</td>
            </tr> 
            <tr>
                <td>Carbon Fiber width</td>
                <td><?=$wind->getCFWidth()?> meters</td>
            </tr>
            <tr>
                <td>Wind angle per pass (offset from the starting angle)</td>
                <td><?=$wind->getWindAnglePerPass()?> degrees</td>
            </tr>  
            <tr>
                <td>Spindle Direction)</td>
                <td><?=$wind->getSpindleDirection()?> (+1 = CW, -1 = CCW)</td>
            </tr>                 
            <tr>
                <td>Transition Feed Rate</td>
                <td><?=round($wind->getTransitionFeedRate() , $wind->sig_figures)?></td>
            </tr>            
            <tr>
                <td>Straight Feed Rate</td>
                <td><?=round($wind->getStraightFeedRate() , $wind->sig_figures)?></td>
            </tr>              
        </table>
        
        
        <h1> Calculated Properties</h1>
        <table>
            <tr>
                <th>
                    Property
                </th>
                <th>
                    Value
                </th>
            </tr>
            <tr>
                <td>Total Tube Length</td>
                <td><?=round($wind->getTotalTubeLength(), $wind->sig_figures)?> meters</td>
            </tr>
            <tr>
                <td>Ideal Advance of CF Thread after integer rotations</td>
                <td><?=round($wind->idealCFAdvancement(), $wind->sig_figures)?></td>
            </tr>
            <tr> 
                <td>Actual Advance of CF Thread after integer rotations</td>
                <td><?=round($wind->actualCFAdvancement(), $wind->sig_figures)?></td>
            </tr>
            <tr>
                <td>Actual Advance ANGLE of Mandrel after integer rotations</td>
                <td><?=round($wind->actualCFAdvancementAngle(), $wind->sig_figures)?></td>
            </tr>               
            <tr>
                <td>APPROX Length of CF required for ONE pass (left to right only)</td>
                <td><?=round($wind->calculateCFMetersOnePass(), $wind->sig_figures)?> meters</td>
            </tr>            
            <tr>
                <td>Actual Meters of CF required for single layer</td>
                <td><?=round($wind->calculateActualCFLengthRequiredOneLayer(), $wind->sig_figures)?></td>
            </tr>
            <tr>
                <td># of Passes required to make one layer
                    <br/> (In one direction)</td>
                <td><?=round($wind->calculatePassesToCoverMandrel(), $wind->sig_figures)?></td>
            </tr>
            <tr>
                <td>Weight of Carbon Fiber used (12k)</td>
                <td><?=round($wind->getCFWeight(), $wind->sig_figures)?> grams</td>
            </tr>     
            <tr>
                <td>Transition X distance (meters)</td>
                <td><?=round($wind->getTotalXTransitionDistance(), $wind->sig_figures)?> meters</td>
            </tr>  
            <tr>
                <td>Straight Section - X Distance (meters)</td>
                <td><?=round($wind->calculateStraightXLength(), $wind->sig_figures)?> meters</td>
            </tr>              
            
            <tr>
                <td>Time to wind one layer</td>
                <td><?=round($wind->getTime(), $wind->sig_figures)?> seconds (<?=round($wind->getTime()/60, 1)?> minutes)</td>
            </tr>               
        </table>
        
        
        

        <input type="button" value="Return to Input Page" name="return_to_input_page" onClick="location.href='index.php'"/>
        <?php
        
        
        
        print "<h1>Codes</h1>";
        print "# of codes: ";
        print $wind->getGcodesCount() . "<br />";
        $wind->printGCodes();
        
        $file  = "/tmp/gcode.ngc";
        $wind->printGCodesToFile($file);
        
        $wind->printGCodesToFile();
        ?>
    </body> 
</html>
