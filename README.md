# CFWinder
** This is my gift to others to help them. I am still available - for paid Professional Engineering advice, 
   if your endeavour is for Financial gain. Otherwise, kindly refer to me somewhere in your notes, if you are blogging **
   
   
!!! Wishing you the very besy of success in your proejct. !!!
   
   

** WARNING **
Unfortunately there are no instructions for getting this up and running....but can give a quick overview...to get you started.


** The software and hardware required **
This project used LinuxCNC to control stepper motors. This is just a specialised version of Linux
with LinuxCNC installed...that does all the HARD work of controlling stepper motors. You still need to 
do some hard work, just not as much.  (Who wants to re-invent the wheel?)

Go get some good Stepper motors from OMC.  https://www.omc-stepperonline.com/  ... good PSU..... well spec'd Drivers...
and follow already well described tutorials on hooking these up.

NOTE: With LinuxCNC I used an oldish PC with an old style Parallel Port. IT works REALLY well!



** Folders in this code **
NOTE: There are three branches. master...for tubes..... to for noseccones.....use the NG variant for NOSECones.

> linucnc folder - has some configurations for the setup I had....PURELY LinuxCNC
    just some configurations
	
> gcodeGenerator - PHP web application that you installed on the SAME machine as linuxcnc with Apache + PHP. 
    You go to a web page...enter in some values...and it generates the GCode to do the job.
    A lot of patience and time required to figure this out...but less time than it would be to start from scratch.  
	Alternatively, start your own app... and pull out bits you like from here...
	
> 3-D_Designs
    A collection of unverified designs of parts used in my project.
    Use them as a starting point if you wish.







** Key Design **
- Three Axis - absolutely necessary   (Axle, left-right and tilt of CF)
- Go for L-XXX belts (don't try XL, even though it theoretically should)
- Have a heavy sturdy, flat bench
- Vibrations are your enemy
- The idler needs to be very well designed - using bearings. Has to be well balanced
- Get your gears professionaly drilled on a lathe.  (Unbalanced parts are your enemy).
- 8020.net is a good place to get the elements of your design.
- With your Mandrel, Aluminum worked out the best...but you NEED TO PREPARE IT VERY CAREFULLY. It takes a LOT OF TIME...Painful, but it pays off.
  It needs to be VERY VERY smooth....and a suitable former (not too tight!!!! needs to be put on to Mandrel)
  Yes....you will need dry-Ice to remove it....
  YEs.... ask someone to help remove the CF tube....
  Yes.... you shouldn't wait any longer than necessary before removing CF tube...it shrinks and you need to reduce all risks of not being able to remove it.
  
Unfortunately there is a lot that needs to be learnt on the job. But don't be put off. It is incredibly rewarding.


Joe