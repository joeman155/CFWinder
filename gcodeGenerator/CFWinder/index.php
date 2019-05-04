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
        <H1>General Parameters</H1>
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
                        <input type="text" name="eyeletDistance" value="0.007" />
                    </td>
                </tr>                           
                <tr>
                    <td>
                        Height of eyelet above Mandrel center-line (meters)
                    </td>
                    <td>
                        <input type="text" name="eyeletHeight" value="0.014" />
                    </td>
                </tr>                              
                <tr>
                    <td>Straight Feed Rate (mm/min)</td>
                    <td><input type="text" name="straight_feed_rate" value="6000" /></td>
                </tr>
                <tr>
                    <td>Transition Feed Rate (mm/min)</td>
                    <td><input type="text" name="transition_feed_rate" value="6000" /></td>
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
                    <td><input type="text" name="cf_width" value="0.006" /></td>
                </tr> 
                <tr>
                    <td>Horizontal starting position (meters)</td>
                    <td><input type="text" name="start_x" value="0.1" /></td>
                </tr>                 
            </table>

            <br />


            <table>
                <tr>
                    <th>Enable layer</th>
                    <th>Carbon Fiber Laydown Angle (deg)</th>
                    <th>Wind angle per pass </th>
                    <th>Additional Wind each end (deg)</th>
                    <th>Transition Start Wind Angle (deg)</th>
                    <th>Transition End Wind Angle (deg)</th>

                </tr>          
                <?
                for ($i = 0; $i < 5; $i++) {
                ?>
                <tr>
                    
                    <td>Enable Layer <?=$i?><input type="checkbox" name="enable_layer[]" value="<?=$i?>"/></td>
                    <td><input type="text" name="cf_angle[]" value="45" /></td>
                    <td><input type="text" name="wind_angle_per_pass[]" value="540" /></td>
                    <td><input type="text" name="extra_spindle_turn[]" value="120" /></td>
                    <td><input type="text" name="transition_start_wind[]" value="90" /></td>
                    <td><input type="text" name="transition_end_wind[]" value="90" /></td>
                </tr>         
                <?
                }
                ?>
            </table>
            <br />
            <br />
            <input type="submit" value="Generate GCode" name="generate_gcode" />
        </form>
    </body>
</html>
