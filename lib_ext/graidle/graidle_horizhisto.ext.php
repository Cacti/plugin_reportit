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
class HorizHistogram extends Graidle
{
	function drawHorizHisto()
	{
		for($m=$t=0;$t<count($this->value);$t++)
		{
			if($this->type[$t]=='hb')
			{
				if(isset($this->ExtLeg)&&($this->ExtLeg==2||$this->ExtLeg==1))
					for($sum=$s=0;$s<count($this->value[$t]);$s++)
						$sum+=abs($this->value[$t][$s]);

				$cc=$this->color[$t];
				list($name,$red,$green,$blue)=explode(',',$cc);
				$cc=imagecolorallocatealpha($this->im,$red,$green,$blue,25);
				$cb=imagecolorallocatealpha($this->im,round($red/2),round($green/2),round($blue/2),50);
				$strExtVal=NULL;

				for($y=$this->a+($this->larg/2),$valuelen=2,$i=0;$i<count($this->value[$t]);$i++,$y+=$this->ld,$valuelen=2)
				{
					$y2=$y+($this->larg*$m);
					$y1=$y2+$this->larg;
					$x2=$this->s+abs($this->mnvs*$this->mul);
					$x1=$x2+($this->value[$t][$i]*$this->mul);

					if(isset($this->ExtLeg)){
						switch($this->ExtLeg){
							case 0:	$strExtVal=$this->value[$t][$i]; break;
							case 1: $strExtVal=round(($this->value[$t][$i]/$sum)*100,1)."% ";break;
							case 2:	$strExtVal=$this->value[$t][$i]." (".round(($this->value[$t][$i]/$sum)*100,1)."%)";break;
						}
					}

					if($this->mnvs<=0){
						if($this->value[$t][$i]>0){
							imagefilledrectangle($this->im,$x1,$y1,$x2,$y2,$cc);
							$valuelen=-(graidle::stringlen($strExtVal)*$this->font_small);
						}
						else	imagefilledrectangle($this->im,$x1,$y2,$x2,$y1,$cc);
						imagerectangle($this->im,$x1,$y1,$x2,$y2,$cb);
						if(isset($this->ExtLeg))
						{
							if(abs($valuelen)<($x1-$x2))	imagettftext($this->im,$this->font_small,0,$x1+$valuelen,$y2+($this->larg/2)+($this->font_small/2),$cb,$this->font,$strExtVal);
							else							imagettftext($this->im,$this->font_small,0,$x1+($this->font_small/2),$y2+($this->larg/2)+($this->font_small/2),$cb,$this->font,$strExtVal);
						}
					}
					else{
						if($this->value[$t][$i]>$this->mnvs){
							$x1=$this->s;
							$x2=($this->value[$t][$i]*$this->mul);
							$valuelen=-(graidle::stringlen($strExtVal)*$this->font_small);

							imagefilledrectangle($this->im,$x1,$y2,$x2,$y1,$cc);
							imagerectangle($this->im,$x1,$y2,$x2,$y1,$cb);
							if(isset($this->ExtLeg))
							{
								imagettftext($this->im,$this->font_small,0,$x2+$valuelen,$y2+($this->larg/2)+($this->font_small/2),$cb,$this->font,$strExtVal);

							}
						}
					}
					if(isset($this->multicolor)&&$this->multicolor==1){
						$cc=next($this->color);
						list($name,$red,$green,$blue)=explode(',',$cc);
						$cc=imagecolorallocatealpha($this->im,$red,$green,$blue,25);
						$cb=imagecolorallocatealpha($this->im,round($red/2),round($green/2),round($blue/2),50);
					}
				}
			}
			$m++;
		}
	}
	function gradAxis($sy=NULL,$sx=NULL)
	{
		$c=imagecolorallocatealpha($this->im,255,255,255,127);
		$bg=imagecolorallocatealpha($this->im,0,0,0,120);
		$style=array($c,$this->axis_color);
		imagesetstyle ($this->im, $style);

		if($this->mnvs<=0)	$zero=$this->s+abs($this->mnvs*$this->mul);
		else				$zero=$this->s;

		for($x=$zero-$this->dvx*$this->mul,$n=-$this->dvx ; $x > $this->s ;$n-=$this->dvx,$x-=$this->dvx*$this->mul)
		{
			$x1=$x-round((count($n)*$this->font_small/2));
			$y1=$this->h-$this->b+$this->font_small+4;
			$y2=$this->h-$this->b;

			imageline($this->im,$x,$y2,$x,$y2-2,$this->axis_color);
			if($sx)	imageline($this->im,$x,$y2,$x,$this->a,IMG_COLOR_STYLED);
			imagefttext($this->im,$this->font_small,0,$x1,$y1,$this->font_color,$this->font,$n);
		}

		if($this->mnvs>0)	$n=$this->mnvs;
		else				$n=0;

		for($x=$zero ; $x <= $this->w-$this->d+1 ; $n+=$this->dvx,$x+=$this->dvx*$this->mul)
		{
			$x1=$x-round((graidle::stringLen($n)*$this->font_small)/2);
			$y1=$this->h-$this->b+$this->font_small*2;
			$y2=$this->h-$this->b;

			imageline($this->im,$x,$y2,$x,$y2-2,$this->axis_color);
			if($sx)	imageline($this->im,$x,$y2,$x,$this->a,IMG_COLOR_STYLED);
			imagefttext($this->im,$this->font_small,0,$x1,$y1,$this->font_color,$this->font,$n);
		}
		if($sy)
			for($i=$this->a ; $i<$this->h-$this->b-1 ; $i+=$this->ld*2)
				imagefilledrectangle($this->im,$this->s,$i+1,$this->w-($this->d),$i+$this->ld+1,$bg);	# changed by A.Braun 09/april/2008
	}
	function drawAxis()
	{
		$c=imagecolorallocatealpha($this->im,255,255,255,127);
		$style=array($c,$this->axis_color);
		imagesetstyle ($this->im, $style);

		if(!isset($this->vlx))
		{
			for($i=1;$i<=$this->cnt;$i++)
				$this->vlx[$i]=$i;
		}

		Graidle::imagelinethick($this->im,$this->s,$this->h-$this->b,$this->w-$this->d,$this->h-$this->b,$this->axis_color,2);
		$this->vlx=array_reverse($this->vlx);
		$y=$this->s;
		if($this->mnvs<=0){
			Graidle::imagelinethick($this->im,$this->s+abs($this->mnvs*$this->mul),$this->a,$this->s+abs($this->mnvs*$this->mul),$this->h-$this->b,$this->axis_color,2);
			$y+=abs($this->mnvs*$this->mul);
		}
		reset($this->vlx);
		$padding=10;
		if(isset($this->spch)&&$this->LegendAlign=="left")	$padding+=$this->spacing+$this->dim_quad+$this->spch;
		for($i=$this->h-$this->b ; $i>=$this->a ; $i-=($this->ld))
		{
			imageline($this->im,$y,$i,$y-3,$i,$this->axis_color);
			imagefttext($this->im , $this->font_small , 0 , $this->s-0.75*$this->font_small-0.75*($this->font_small*strlen(current($this->vlx))), $i-($this->ld/2)+($this->font_small/2) , $this->font_color , $this->font , current($this->vlx));		#changed by A.Braun 09/april/2008
			next($this->vlx);
		}
	}
}
?>