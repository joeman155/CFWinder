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
$length_multiplier   = $_REQUEST['length_multiplier'];
$wind_angle_per_pass = $_REQUEST['wind_angle_per_pass'];
$start_x             = $_REQUEST['start_x'];
    
$wind = new Wind($useful_tube_length, $mandrelRadius, $cf_angle, $wind_angle_per_pass, $cf_width, $length_multiplier, $start_x);

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
                <td>Useful Tube Length</td>
                <td><?=round($wind->getUsefulTubeLength(), $wind->sig_figures)?> meters</td>
            </tr>                
            <tr>
                <td>Mandrel Circumference</td>
                <td><?=round($wind->getMandrelCircumference(), $wind->sig_figures)?> meters</td>
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
                <td><?=$wind->getWindAnglePerPass()?> meters</td>
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
                <td><?=round($wind->getTubeLength(), $wind->sig_figures)?> meters</td>
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
                <td>Transition Y distance (degrees)</td>
                <td><?=round($wind->getTotalYTransitionDistance(), $wind->sig_figures)?> degrees</td>
            </tr>       
            <tr>
                <td>Transition Length Factor (dimensionless)</td>
                <td><?=round($wind->getTransitionLengthFactor(), $wind->sig_figures)?></td>
            </tr>      
            <tr>
                <td>Transition Arc Factor (dimensionless)</td>
                <td><?=round($wind->getTransitionArcFactor(), $wind->sig_figures)?></td>
            </tr>    
            <tr>
                <td>Transition Arc Length (meters)</td>
                <td><?=round($wind->getTransitionLength(), $wind->sig_figures)?> meters</td>
            </tr>             
            <tr>
                <td>Transition Radius (meters)</td>
                <td><?=round($wind->getTransitionRadius(), $wind->sig_figures)?> meters</td>
            </tr>            
            <tr>
                <td>Straight Section - X Distance (meters)</td>
                <td><?=round($wind->getStraightLength(), $wind->sig_figures)?> meters</td>
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
        
        $wind->printGCodesToFile($file);
        
        $wind->printGCodesToFile();
        ?>
    </body> 
</html>
