<!DOCTYPE html>
<!--
License to Joseph Turner

GPL - FREE IN EVERY SENSE. 

USE AT YOUR OWN RISK.
--> 
<html>
    <head>
        <meta charset="UTF-8">
        <title>CFWinder Tube Landing Page</title>
    </head>
    <body>
        <form action="process.php">
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
                    <td>
                        Mandrel Radius (meters)
                    </td>
                    <td>
                        <input type="text" name="mandrelRadius" value="0.0381" />
                    </td>
                </tr>
                <tr>
                    <td>
                       Distance from mandrel surface to eyelet (meters)
                    </td>
                    <td>
                        <input type="text" name="eyeletDistance" value="0.33" />
                    </td>
                </tr>                           
            <tr>
                <td>Carbon Fiber Laydown Angle (degrees)</td>
                <td><input type="text" name="cf_angle" value="45" /></td>
            </tr>          
            <tr>
                <td>Additional Wind each end (degrees)</td>
                <td><input type="text" name="extra_spindle_turn" value="0" /></td>
            </tr>         
            
            <tr>
                <td>Transition End Wind Angle (degrees)</td>
                <td><input type="text" name="transition_end_wind" value="360" /></td>
            </tr>               
            <tr>
                <td>Straight Feed Rate (mm/min)</td>
                <td><input type="text" name="straight_feed_rate" value="3000" /></td>
            </tr>
            <tr>
                <td>Transition Feed Rate (mm/min)</td>
                <td><input type="text" name="transition_feed_rate" value="3000" /></td>
            </tr>         
            <tr>
                <td>Spindle Rotation Direction</td>
                <td>
                    <select  name="spindle_direction">
                        <option value="1">Clockwise</option>
                        <option selected value="-1">Anti-Clockwise</option>
                    </select>
                </td>
            </tr>              
            <tr>
                <td>Carbon Fiber width (meters)</td>
                <td><input type="text" name="cf_width" value="0.007" /></td>
            </tr>
            <tr>
                <td>Wind angle per pass (The number of rotations for the straight part ONLY)</td>
                <td><input type="text" name="wind_angle_per_pass" value="540" /></td>
            </tr>      
            <tr>
                <td>Horizontal starting position (meters)</td>
                <td><input type="text" name="start_x" value="0.1" /></td>
            </tr>                 
            </table>
            
            <input type="submit" value="Generate GCode" name="generate_gcode" />
        </form>
    </body>
</html>
