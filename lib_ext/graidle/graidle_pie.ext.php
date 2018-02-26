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
class Pie extends Graidle
{
	function drawPie($a,$b)
	{
		$incl=$this->incl;
		$AA=$this->AA;
		$tre_d=$this->tre_d*$AA;
		$radius=$this->radius;

		$Htmp=($tre_d+(($b*2)*$AA)/2);
		$Wtmp=(($a*2)*$AA)/2;

		$mul=0.95;
		$black=imagecolorallocate($this->im,0,0,0);

		if(isset($this->PieTitle))	$mul-=0.10;

		if(isset($this->ExtLeg)){
			switch($this->ExtLeg){
				case 0: case 1: $mul-=0.15;break;
				case 2:	$mul-=0.25;break;
			}
		}

		for($t=0,$n=0;$t<count($this->value);$t++)
		{
			if($this->type[$t]=='p')
			{
				$pie_tmp = imagecreatetruecolor($Wtmp,$Htmp);
				imagefilledrectangle($pie_tmp,0,0,$Wtmp,$Htmp,$this->bg_color);

				$cx=$Wtmp/2;
				$cy=($Htmp/2)-$tre_d/2;

				$tot=array_sum($this->value[$t]);

				/* Workaround */											# added by A.Braun 11/april/2008
				if ($tot == 0) continue;
				$nx_deg = 0;
				foreach($this->value[$t] as $key => $value) {
					if($value == 0) {
						unset($this->value[$t][$key]);
						continue;
					}else {
						$nx_deg += (360*$value)/$tot;
						$deg[$key] = $nx_deg;
					}
				}
//print_r($deg);
				array_unshift($deg,0);

				for($y1=$tre_d;$y1>=0;$y1--)
				{
					for($i=0,$s=1;$i<count($this->value[$t]);$i++,$s++)
					{
						$c=$this->color[$i];
						list($name,$red,$green,$blue)=explode(',',$c);

						$x=$cx;
						$y=$cy+$y1;
						$h=($a*$mul)*$AA;
						$w=($b*$mul)*$AA;

						if($y1==0)
						{
							$mid_deg=($deg[$i]+$deg[$s])/2;
							$rgb=imagecolorallocate($this->im,$red,$green,$blue);
							imagefilledarc($pie_tmp,$x,$y,$h,$w,$deg[$i],$deg[$s],$rgb,IMG_ARC_PIE);

							$rgb = ($this->multicolorText)? $rgb : imagecolorallocate($this->im,0,0,0);		#added by A.Braun 09/april/08

							if(isset($this->ExtLeg))
							{
								switch($this->ExtLeg)
								{
									case 0:	$legval=$this->value[$t][$i];$lngt=strlen($legval);break;
									case 1: $legval=round(($this->value[$t][$i]/$tot)*100,1)."%";$lngt=strlen($legval);break;
									case 2:	$legval=$this->value[$t][$i]." (".round(($this->value[$t][$i]/$tot)*100,1)."%)";$lngt=strlen($legval);break;
									case 3: $legval=$i;$lngt=strlen($legval);break;
								}

								if($mid_deg<=90)						imagefttext($pie_tmp,$this->font_small*$AA,0,$x+($tre_d)+(($h/2)*cos(deg2rad($mid_deg))),$y+($tre_d)+($this->font_small*$AA)+(($w/2)*sin(deg2rad($mid_deg))),$rgb,$this->font,$legval);
								else if($mid_deg>90&&$mid_deg<=180)		imagefttext($pie_tmp,$this->font_small*$AA,0,$x-($lngt*($this->font_small*$AA))+(($h/2)*cos(deg2rad($mid_deg))),$y+($tre_d)+($this->font_small*$AA)+(($w/2)*sin(deg2rad($mid_deg))),$rgb,$this->font,$legval);
								else if($mid_deg>180&&$mid_deg<=270)	imagefttext($pie_tmp,$this->font_small*$AA,0,$x-($lngt*($this->font_small*$AA))+(($h/2)*cos(deg2rad($mid_deg))),$y+(($w/2)*sin(deg2rad($mid_deg))),$rgb,$this->font,$legval);
								else if($mid_deg>270)					imagefttext($pie_tmp,$this->font_small*$AA,0,$x+(($h/2)*cos(deg2rad($mid_deg))),$y+(($w/2)*sin(deg2rad($mid_deg))),$rgb,$this->font,$legval);
							}
						}
						else if($incl!=90)
						{
							$rgb=imagecolorallocate($this->im,$red/2,$green/2,$blue/2);
							imagefilledarc($pie_tmp,$x,$y,$h,$w,$deg[$i],$deg[$s],$rgb,IMG_ARC_NOFILL);
						}
					}
				}
				if(isset($this->PieTitle[$t]))	imagefttext($pie_tmp,$this->font_small*$AA,0,$this->font_small*$AA*1.5,$this->font_small*$AA*1.5,$this->font_color,$this->fontBd,$this->PieTitle[$t]);

				$OrizAlign=((($this->w+$this->s-$this->d))/2)-(($Wtmp/$AA)/2);

				imagecopyresampled($this->im,$pie_tmp, $OrizAlign , 5+$this->a+($Htmp/$AA)*$n,0,0,$Wtmp/$AA,$Htmp/$AA,$Wtmp,$Htmp);
				if(isset($rgb))		imagecolordeallocate($pie_tmp,$rgb);
				if(isset($trasp))	imagecolordeallocate($pie_tmp,$trasp);
				if(isset($black))	imagecolordeallocate($this->im,$black);
				imagedestroy($pie_tmp);
				reset($deg);
				$nx_deg=0;
				$n++;
			}
		}
	}
}
?>