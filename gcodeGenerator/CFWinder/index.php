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
                <td>Carbon Fiber Laydown Angle (degrees)</td>
                <td><input type="text" name="cf_angle" value="30" /></td>
            </tr>     
            <tr>
                <td>Carbon Fiber width (meters)</td>
                <td><input type="text" name="cf_width" value="0.005" /></td>
            </tr>
            <tr>
                <td>Wind angle per pass (offset from the starting angle)</td>
                <td><input type="text" name="wind_angle_per_pass" value="180" /></td>
            </tr>      
            <tr>
                <td>Horizontal starting position (meters)</td>
                <td><input type="text" name="start_x" value="0.315" /></td>
            </tr>                 
            </table>
            
            <input type="submit" value="Generate GCode" name="generate_gcode" />
        </form>
    </body>
</html>