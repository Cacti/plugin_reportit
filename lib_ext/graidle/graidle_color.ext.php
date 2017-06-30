<?php
/*
 +----------------------------------------------------------------------+
 | Copyright (C) 2007 Alessio Glorioso                                  |
 |                                                                      |
 | This program is free software. You can redistribute it               |
 | and/or modify it under the terms of the GNU General Public License   |
 | as published by the Free Software Foundation; either version 2       |
 | of the License, or (at your option) any later version.               |
 |                                                                      |
 | This program is distributed in the hope that it will be useful, but  |
 | WITHOUT ANY WARRANTY; without even the implied warranty of           |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
 | GNU General Public License for more details.                         |
 +----------------------------------------------------------------------+
 | Graidle v0.5	http://graidle.sourceforge.net                          |
 +----------------------------------------------------------------------+
*/
class TableOfColors extends Graidle
{
		function Color(){
			$this->color = array(
			"green,127,180,57",
			"blue,26,131,192",
			"red,237,24,70",
			"yellow,255,234,130",
			"magenta,245,152,170",
			"cyan,113,206,234",
			"gray,169,171,159",
			"gold,255, 215, 0",
			"pink,255,20,147",
			"lavender,230, 230, 250",
			"orchid,218,112,214",
			"olive,128, 128, 0",
			"orange,255,165,0",
			"azure,200,215,245",
			"coral,255,127,80",
			"beige,245,245,220",
			"brown,165,42,42",
			"lime,0,255,0",
			"fuchsia,255, 0, 255",
			"silver,192,192,192",
			"indigo,75, 0, 130",
			"khaki,240, 230, 140",
			"maroon,128, 0, 0",
			"turquoise,64,224,208",
			"peru,205,133,63",
			"plum,221,160,221",
			"purple,128,0,128",
			"salmon,250,128,114",
			"sienna,160,82,45",
			"violet,238,130,238",
			"tan,210,180,140",
			"wheat,245,222,179",
			"teal,0,128,128",
			"thistle,216,191,216",
			"turquoise,64,224,208",
			"white,255,255,255",
			"black,0,0,0");
		}
		function hex2rgb($HEXcolor){
			if($HEXcolor{0}=='#')	$HEXcolor=substr($HEXcolor,1);
			$HEXcolor=substr($HEXcolor,0,6);
			for($i=$p=0;$i < strlen($HEXcolor)/2;$i++,$p+=2)	$rgb[$i]=hexdec(substr($HEXcolor,$p,2));
			return $rgb;
		}
}?>