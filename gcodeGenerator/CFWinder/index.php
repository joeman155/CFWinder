<!DOCTYPE html>
<!--
// BRANCH: Nosecone

License to Joseph Turner

GPL - FREE IN EVERY SENSE. 

USE AT YOUR OWN RISK.
--> 
<html>
    <head>
        <meta charset="UTF-8">
        <title>CFWinder Tube Landing Page - Nosecone</title>
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
                <tr>
                    <td>Horizontal starting position of NoseConeBase (meters)</td>
                    <td><input type="text" name="nose_cone_start_x" value="0.2" /></td>
                </tr>     
                <tr>
                    <td>Horizontal ending position of NoseConeTop (meters)</td>
                    <td><input type="text" name="nose_cone_stop_x" value="0.5" /></td>
                </tr>   
                <tr>
                    <td>Radius of NoseConeTop (meters)</td>
                    <td><input type="text" name="nose_cone_top_radius" value="0.01" /></td>
                </tr>                  
            </table>

            <br />
            <H3>A few notes on Nose Cone</H3>
            <ul>
                <li>Nose Cone larger radius is assumed to be on LHS and is assumed to be equal to the radius of the "Mandrel Radius"</li>
                <li>Nose Cone Base start is where the diameter starts to reduce. i.e. Ignore the fact the NoseCone PART extends a little to the left to allow a coupler</li>
                <li>The ending position is NOT the "tip" of the nose cone, it is the point at which the diameter of the nosecone part is NOT going to be less. i.e. truncated nose coneys</li>
                <li>The radius of NoseConeTop is the minimum radius of the nose cone. </li>
            </ul>

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
