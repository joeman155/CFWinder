<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<?php
require "Wind.php";

$mandrelRadius       = $_REQUEST['mandrelRadius'];
$cf_width            = $_REQUEST['cf_width'];
$cf_angle            = $_REQUEST['cf_angle'];
$length_multiplier   = $_REQUEST['length_multiplier'];
$wind_angle_per_pass = $_REQUEST['wind_angle_per_pass'];
$start_x             = $_REQUEST['start_x'];
    
$wind = new Wind($mandrelRadius, $cf_angle, $wind_angle_per_pass, $cf_width, $length_multiplier, $start_x);

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
                <td>Calculated Tube Length</td>
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
                <td>Meters of CF required for single layer</td>
                <td><?=round($wind->calculateCFLengthRequiredOneLayer(), $wind->sig_figures)?></td>
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
                <td>Time to wind one layer</td>
                <td><?=round($wind->getTime(), $wind->sig_figures)?> seconds</td>
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
