# CFWinder
Unfortunately there are no instructions for getting this up and running....but can give a quick overview.


1. This project used LinuxCNC to control stepper motors. This is just a specialised version of Linux
with LinuxCNC installed...that does all the HARD work of controlling stepper motors. You still need to 
do some hard work, just not as much.



linucnc folder - has some configurations for the setup I had....PURELY LinuxCNC
    just some configurations
	
gcodeGenerator - PHP web application that you installed on the SAME machine as linuxcnc with Apache + PHP. 
    You go to a web page...enter in some values...and it generates the GCode to do the job.
    A lot of patience and time required to figure this out...but less time than it would be to start from scratch.  
	Alternatively, start your own app... and pull out bits you like from here...
	
	NOTE: There are three branchess. master...for tubes..... to for noseccones.....use the NG variant for NOSECones.