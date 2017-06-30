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
class spider extends graidle{
	function drawSpider(){
		$AA=$this->AA;

		$Htmp=round(($this->radius*2)*$AA);
		$Wtmp=$Htmp;

		$x0=round($Wtmp/2);
		$y0=round($Htmp/2);

		$mul=0.95;
		if(isset($this->vlx))	$mul-=0.1;	#changed by A.Braun 06/April/2008

		$this->radius=round($this->radius*$mul);

		$this->mul=$this->radius/$this->mass;

		$img_tmp = imagecreatetruecolor($Wtmp,$Htmp);
		if(function_exists('imageantialias')) imageantialias($img_tmp,1);
		$trasp=imagecolorallocatealpha($img_tmp,0,0,0,127);
		imagefill($img_tmp,0,0,$trasp);
		$black=imagecolorallocate($img_tmp,0,0,0);

		for($cntval=$i=0;$i < count($this->type);$i++){
			if($this->type[$i]=='s'){
				if($cntval<count($this->value[$i]))	$cntval=count($this->value[$i]);
			}
		}
		$angleval=round(360/$cntval);

		for($i=0,$angle=deg2rad(-90);$i<$cntval;$i++)
		{
			$x1=round($x0+(($this->radius*cos($angle))*$AA));
			$y1=round($y0+(($this->radius*sin($angle))*$AA));

			graidle::imagelinethick($img_tmp,$x0,$y0,$x1,$y1,$this->axis_color,$AA);

			if(isset($this->vlx)){

				if($angle>=deg2rad(-90) && $angle<deg2rad(0))			imagefttext($img_tmp,$this->font_small*$AA,0,$x1+4,$y1-4,$this->font_color,$this->font,$this->vlx[$i]);
				else if($angle>=deg2rad(0) && $angle<deg2rad(90))		imagefttext($img_tmp,$this->font_small*$AA,0,$x1+4,$y1+$this->font_small*$AA,$this->font_color,$this->font,$this->vlx[$i]);
				else if($angle>=deg2rad(90) && $angle<deg2rad(180))		imagefttext($img_tmp,$this->font_small*$AA,0,$x1+4-(strlen($this->vlx[$i])*($this->font_small*$AA)),$y1+$this->font_small*$AA,$this->font_color,$this->font,$this->vlx[$i]);
				else if($angle>=deg2rad(180) && $angle<deg2rad(270))	imagefttext($img_tmp,$this->font_small*$AA,0,$x1+4-(strlen($this->vlx[$i])*($this->font_small*$AA)),$y1,$this->font_color,$this->font,$this->vlx[$i]);
			}
			for($v=$s=0;$s<=$this->radius+1;$s+=$this->dvx*$this->mul,$v+=$this->dvx)
			{
				imagearc($img_tmp,$x0,$y0,($this->radius-$s)*2*$AA,($this->radius-$s)*2*$AA,0,360,$this->axis_color);
				imagefttext($img_tmp,$this->font_small*$AA,0,$x0+4,($y0+4+$this->font_small*$AA)-$s*$AA,$this->font_color,$this->font,$v);
			}
			$angle+=deg2rad($angleval);
		}
		for($s=0;$s < count($this->value);$s++)
		{
			for($point=array(),$i=0,$d=1,$angle=deg2rad(-90);$i<$cntval;$i++,$d++)
			{
				if($d==$cntval){
					$val1=$this->value[$s][$i];
					$val2=$this->value[$s][0];
				}
				else{
					$val1=$this->value[$s][$i];
					$val2=$this->value[$s][$d];
				}
				$x2=round($x0+((($this->mul*$val1)*cos($angle))*$AA));
				$y2=round($y0+((($this->mul*$val1)*sin($angle))*$AA));

				array_push($point,$x2,$y2);
				$angle+=deg2rad($angleval);
			}
			$c=$this->color[$s];
			list($name,$red,$green,$blue)=explode(',',$c);

			if(isset($this->filled)&&$this->filled==1){
				$rgbA=imagecolorallocatealpha($this->im,$red,$green,$blue,80);
				imagefilledpolygon($img_tmp,$point,$cntval,$rgbA);
				imagepolygon($img_tmp,$point,$cntval,$black);
			}
			else{
				$rgb=imagecolorallocate($this->im,$red,$green,$blue);
				imagepolygon($img_tmp,$point,$cntval,$rgb);
			}
		}
		$OrizAlign=((($this->w+$this->s-$this->d))/2)-(($Wtmp/$AA)/2);

		imagecopyresampled($this->im,$img_tmp, $OrizAlign , 5+$this->a,0,0,$Wtmp/$AA,$Htmp/$AA,$Wtmp,$Htmp);
		if(isset($rgb))		imagecolordeallocate($img_tmp,$rgb);
		if(isset($rgbA))	imagecolordeallocate($img_tmp,$rgbA);
		if(function_exists('imageantialias')) imageantialias($img_tmp,0);
		imagedestroy($img_tmp);
	}
}?>