<!DOCTYPE html>
<!-- // BRANCH: Nosecone -->
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<?php
require "Wind.php";


$mandrelRadius         = $_REQUEST['mandrelRadius'];
$cf_width              = $_REQUEST['cf_width'];
$start_x               = $_REQUEST['start_x'];
$transition_feed_rate  = $_REQUEST['transition_feed_rate'];
$straight_feed_rate    = $_REQUEST['straight_feed_rate'];
$spindle_direction     = $_REQUEST['spindle_direction'];
$eyeletDistance        = $_REQUEST['eyeletDistance'];
$eyeletHeight          = $_REQUEST['eyeletHeight'];

$start_y = 0;
$start_z = 0;

// Nosecone specific parameters
$nose_cone_start_x     = $_REQUEST['nose_cone_start_x'];
$nose_cone_stop_x      = $_REQUEST['nose_cone_stop_x'];
$nose_cone_top_radius  = $_REQUEST['nose_cone_top_radius'];
$nose_cone_cf_closest_approach_to_tip = $_REQUEST['nose_cone_cf_closest_approach_to_tip'];
$nose_cone_num_adjacent_tows          = $_REQUEST['nose_cone_num_adjacent_tows'];
$turn_around_splits    = $_REQUEST['turn_around_splits'];

// Common Layer properties
$cylinder_transition_end_wind    = $_REQUEST['cylinder_transition_end_wind'];
$cylinder_transition_start_wind = $_REQUEST['cylinder_transition_start_wind'];
$number_of_layers      = $_REQUEST['number_of_layers'];


$wind = new Wind($mandrelRadius, $eyeletDistance, $eyeletHeight, $cf_width, $transition_feed_rate, $straight_feed_rate, 
                 $spindle_direction, $start_x, $start_y, $start_z,
                 $number_of_layers, $cylinder_transition_start_wind, $cylinder_transition_end_wind, 
                 $nose_cone_start_x,  $nose_cone_stop_x, $nose_cone_top_radius, $nose_cone_cf_closest_approach_to_tip,
                 $nose_cone_num_adjacent_tows, $turn_around_splits);

$wind->generateGCodes();
?>
<html>
    <head>
        <meta charset="UTF-8">
        <title>CF Winder G-Code Generator</title>
    </head>
    <body>
        <h1> Inputted Parameters</h1>
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
                <td>Largest Angle of Delivery Head</td>
                <td><?=round($wind->getOptimumZAngle(), $wind->sig_figures)?> degrees</td>
            </tr>             
            <tr>
                <td>Carbon Fiber width</td>
                <td><?=$wind->getCFWidth()?> meters</td>
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
        
        
        <h1> Layers</h1>
            <table border="1">
                <tr>
                    <th>Layer #</th>
                    <th>Laydown Angle (deg)</th>
                    <th>Transition Start<br /> Wind Angle (deg)</th>
                    <th>Transition End<br /> Wind Angle (deg)</th>
                    <th>Number of adjacent laydowns of CF</th>
                    <th># of passes to cover mandrel</th>
                    <th>Total Length (mm)</th>
                    <th>Useful Length (mm)</th>
                    <th>Start of Usable Tube (mm)</th>
                    <th>Lead Distance (mm)</th>
                    <th>~ Length CF (mm)</th>
                </tr>          
                <?
                for ($layer = 0; $layer < $wind->getNumberOfLayers(); $layer++) {
                ?>
                <tr>
                    <td>Layer <?=$layer?></td>
                    <td><?=$wind->getNoseConeCFAngle()?></td>
                    <td><?=$wind->getTransitionStartWind()?></td>
                    <td><?=$wind->getTransitionEndWind()?></td>
                    <td><?=$wind->getNumAdjacentLaydowns()?></td>
                    <td>TODO</td>
                    <td>TODO</td>
                    <td>TODO</td>
                    <td>TODO</td>
                    <td>TODO</td>
                    <td>TODO</td>
                </tr>         
                <?
                }
                ?>
            </table>        
        
        <h1> Calculated Properties</h1>
        <table>
            
            <tr>
                <td>Winding Time</td>
                <td><?=round($wind->getTime(), 0)?> seconds</td>
            </tr>    
        </table>
        
        
        
<br/><br/>
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
