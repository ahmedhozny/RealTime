# RealTime

<p align="center">
    <img src="icon.png" width="150px" height="150px">
</p>

[![](https://poggit.pmmp.io/shield.state/RealTime)](https://poggit.pmmp.io/p/RealTime)
[![](https://poggit.pmmp.io/shield.api/RealTime)](https://poggit.pmmp.io/p/RealTime)

[![](https://poggit.pmmp.io/shield.dl.total/RealTime)](https://poggit.pmmp.io/p/RealTime)
[![](https://poggit.pmmp.io/shield.dl/RealTime)](https://poggit.pmmp.io/p/RealTime)


## Description
A PMMP plugin that cycles in-game time according to real-life factors.

## Configuration
`version` 		<i>NEVER CHANGE THIS VALUE</i>

`enable_time_sync` 	<i>[true,false]</i> If true, vanilla time will run with respect to real-life factors

`worlds` 		<i>["myworld","broken_nether","TheLOLlevel"]</i> The worlds you would like the plugin to do its job on. Separate the world names with a comma. Leave the brackets empty to include all the worlds

`location_dependant` 	If true, vanilla day will cycle as a real day somewhere on earth. Enter your location details in location.yml

`auto_locate` 		If true, the server will verify the location details everytime it starts up using geoPlugin.com

`noon_time` 		The time the sun is at its peak. Used if `location_dependant` is set to false. Don't remove the double quotes "hh:mm:ss". Example: "12:05"

`broadcast_interval` 	Time interval in minutes at which a message with the time is broadcasted

`broadcast_mode` 	<i>[message,tip,popup,title]</i> The way players will receive the message

## Commands
`/realtime` Alias: (`/rt`)

`/realtime current` 	returns the current time. '/realtime' alone could be used instead.

`/realtime disable` 	disables game time from synchronising with real-life factors.

`/realtime enable` 	enables game time synchronising with real-life factors.

`/realtime help` 	provides a list of available commands.

`/realtime reload`	updates the settings to correspond to the files.

`/realtime status` 	returns status about the sun position and current day.

## Additional information
- If you're facing timezone issues (the time on your machine is not the same as your terminal). Open up SERVER DIRECTORY/bin/php/php.ini . Add, on a new line, the following setting `date.timezone= "YOUR TIMEZONE"`. A list of all supported timezone shall be found here: www.php.net/manual/en/timezones.php . In my case `date.timezone= "Africa/Cairo"`
- The results you going to observe are NOT completely accurate. This is to decline bad impact on the overall performance. We'll work on better accuracy, with performance into consideration, on the next updates.

## Credits
Icon by: Aleksandr Reva (https://www.iconfinder.com/icons/1034355/day_night_date_moon_sun_icon)

## Contribution
Have ideas or found an issue? Feel free to contribute with us.
Links:

- [Open an issue or leave a suggestion](https://github.com/photoncodes/RealTime/issues)
- [Add something new or fix a bug](https://github.com/photoncodes/RealTime/pulls)

## Licensing information
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU Lesser General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Lesser General Public License for more details.

	You should have received a copy of the GNU Lesser General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
	

