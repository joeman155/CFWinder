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


// Per layer parameters
$enable_layer          = $_REQUEST['enable_layer'];
$cf_angle              = $_REQUEST['cf_angle'];
$wind_angle_per_pass   = $_REQUEST['wind_angle_per_pass'];
$extra_spindle_turn    = $_REQUEST['extra_spindle_turn'];
$transition_end_wind   = $_REQUEST['transition_end_wind'];
$transition_start_wind = $_REQUEST['transition_start_wind'];


// Parse layers information and create an array
for ($i = 0; $i < 5; $i++) {
    // print "Layer " . $i . " : " . $enable_layer[$i] . "<br/>";
    $index = $enable_layer[$i];
    if (isset($index) && $index >= 0) {
       $layers[$i]['cf_angle']              = $cf_angle[$index];
       $layers[$i]['wind_angle_per_pass']   = $wind_angle_per_pass[$index];
       $layers[$i]['extra_spindle_turn']    = $extra_spindle_turn[$index];
       $layers[$i]['transition_start_wind'] = $transition_start_wind[$index];
       $layers[$i]['transition_end_wind']   = $transition_end_wind[$index];
    }
}
    
 // print("<pre>".print_r($layer)."</pre>");

$wind = new Wind($mandrelRadius, $eyeletDistance, $eyeletHeight, $cf_width, $transition_feed_rate, $straight_feed_rate, 
                 $spindle_direction, $start_x, $start_y, $start_z,
                 $layers, $nose_cone_start_x,  $nose_cone_stop_x, $nose_cone_top_radius);

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
                    <th>Wind angle per<br /> pass (deg)</th>
                    <th>Additional Wind<br /> each end (deg)</th>
                    <th>Transition Start<br /> Wind Angle (deg)</th>
                    <th>Transition End<br /> Wind Angle (deg)</th>
                    <th># of passes to cover mandrel</th>
                    <th>Total Length (mm)</th>
                    <th>Useful Length (mm)</th>
                    <th>Start of Usable Tube (mm)</th>
                    <th>Lead Distance (mm)</th>
                    <th>~ Length CF (mm)</th>
                </tr>          
                <?
                for ($layer = 0; $layer < count($wind->getLayers()); $layer++) {
                ?>
                <tr>
                    <td>Layer <?=$layer?></td>
                    <td><?=$wind->getLayers()[$layer]['cf_angle']?></td>
                    <td><?=$wind->getLayers()[$layer]['wind_angle_per_pass']?></td>
                    <td><?=$wind->getLayers()[$layer]['extra_spindle_turn']?></td>
                    <td><?=$wind->getLayers()[$layer]['transition_start_wind']?></td>
                    <td><?=$wind->getLayers()[$layer]['transition_end_wind']?></td>
                    <td><?=$wind->calculatePassesToCoverMandrel($layer)?></td>
                    <td><?=round(1000 * $wind->getTotalTubeLength($layer), 1)?></td>
                    <td><?=round(1000 * $wind->getTubeLength($layer), 1)?></td>
                    <td><?=round(1000 * ($wind->getTubeStart($layer) - $wind->getLeadDistance($layer)), 1)?></td>
                    <td><?=round(1000 * $wind->getLeadDistance($layer), 1)?></td>
                    <td><?=round(1000 * $wind->getLength($layer), 1)?></td>
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
