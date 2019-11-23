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
                        <input type="text" name="eyeletDistance" value="0.02" />
                    </td>
                </tr>                           
                <tr>
                    <td>
                        Height of eyelet above Mandrel center-line (meters)
                    </td>
                    <td>
                        <input type="text" name="eyeletHeight" value="0.010" />
                    </td>
                </tr>                              
                <tr>
                    <td>Straight Feed Rate (mm/min)</td>
                    <td><input type="text" name="straight_feed_rate" value="8000" /></td>
                </tr>
                <tr>
                    <td>Transition Feed Rate (mm/min)</td>
                    <td><input type="text" name="transition_feed_rate" value="8000" /></td>
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
                    <td><input type="text" name="cf_width" value="0.0048" /></td>
                </tr> 
                <tr>
                    <td>Horizontal starting position (meters)</td>
                    <td><input type="text" name="start_x" value="0.0" /></td>
                </tr>              
                <tr>
                    <td>Horizontal starting position of Nose Cone Base (meters)</td>
                    <td><input type="text" name="nose_cone_start_x" value="0.265" /></td>
                </tr>      
                <tr>
                    <td>Horizontal ending position of Nose Cone Top (meters)</td>
                    <td><input type="text" name="nose_cone_stop_x" value="0.575" /></td>
                </tr>   
                <tr>
                    <td>Radius of Nose Cone Top (meters)</td>
                    <td><input type="text" name="nose_cone_top_radius" value="0.012" /></td>
                </tr> 
                <tr>
                    <td>CF Closest approach to "tip" (meters)</td>
                    <td><input type="text" name="nose_cone_cf_closest_approach_to_tip" value="0.16" /></td>
                </tr>                 
                <tr>
                    <td colspan="2"><b>Layer properties</b></td>
                </tr>
                <tr>
                    <td>Number of layers</td>
                    <td><input type="text" name="number_of_layers" value="3" /></td>
                </tr>        
                <tr>
                    <td>Transition Start Wind (degrees)</td>
                    <td><input type="text" name="cylinder_transition_start_wind" value="90" /></td>
                </tr>    
                <tr>
                    <td>Transition End Wind (degrees)</td>
                    <td><input type="text" name="cylinder_transition_end_wind" value="360" /></td>
                </tr>     
                <tr>
                    <td># of Adjacent Winds</td>
                    <td><input type="text" name="nose_cone_num_adjacent_tows" value="2" /></td>
                </tr>                   
            </table>
            
            

            <br />
            <H3>A few notes on Nose Cone Definition</H3>
            <ul>
                <li>Nose cone is truncated...i.e. we don't go to a tip....there is a min radius</li>
                <li>Nose Cone larger radius assumed to be equal to the radius of the "Mandrel Radius"</li>
                <li>Nose Cone is built with large radius to LEFT (near X = 0) and smaller radius to the right</li>
                <li>Nose Cone Base start is where the diameter starts to reduce. i.e. Ignore the fact the NoseCone PART extends a little to the left to allow a coupler</li>
                <li>The ending position is NOT the "tip" of the nose cone, it is the point at which the diameter of the nosecone part is NOT going to be less. i.e. truncated nose coneys</li>
                <li>The radius of Nose Cone Top is the minimum radius of the nose cone. </li>
            </ul>

            <br />
            <br />
            <input type="submit" value="Generate GCode" name="generate_gcode" />
        </form>
    </body>
</html>
