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
class Histogram extends Graidle
{
	function drawHisto()
	{
		for($m=$t=0;$t<count($this->value);$t++)
		{
			if($this->type[$t]=='b')
			{
				$cc=$this->color[$t];
				list($name,$red,$green,$blue)=explode(',',$cc);
				$cc=imagecolorallocatealpha($this->im,$red,$green,$blue,12);
				$cb=imagecolorallocatealpha($this->im,round($red/2),round($green/2),round($blue/2),90);
				for($x=$i=0 ; $i<count($this->value[$t]) ; $i++){
					if($x==0){
						$x1=($this->s+(($this->larg+1)*$m)+($this->larg/2));
						$x2=($x1+$this->larg);

						$y1=$this->h-$this->b;
						$y2=$this->h-$this->b;

						if($this->mnvs<=0){
							if($this->value[$t][$i]>0){
								$y1=$this->h-$this->b-($this->value[$t][$i]*$this->mul)-(abs($this->mn+$this->scarmin)*$this->mul);
								$y2-=(abs($this->mn+$this->scarmin)*$this->mul);
 								$x+=$x2;
								imagefilledrectangle($this->im,$x1,$y1,$x2,$y2,$cc);
								imagerectangle($this->im,$x1,$y1,$x2,$y2,$cb);
							}
							else{
								$y1-=(abs($this->mn+$this->scarmin)*$this->mul);
								$y2=$this->h-$this->b-($this->value[$t][$i]*$this->mul)-(abs($this->mn+$this->scarmin)*$this->mul);
								$x+=$x2;
								imagefilledrectangle($this->im,$x1,$y1,$x2,$y2,$cc);
								imagerectangle($this->im,$x1,$y1,$x2,$y2,$cb);
							}
						}
						else{
							if($this->value[$t][$i]>0){
								$y1=$this->h-$this->b-($this->value[$t][$i]*$this->mul)-($this->mnvs*$this->mul);
								$y2=$this->h-$this->b+($this->mnvs*$this->mul);
								$x+=$x2;
								imagefilledrectangle($this->im,$x1,$y1,$x2,$y2,$cc);
								imagerectangle($this->im,$x1,$y1,$x2,$y2,$cb);
							}
							else	$x+=($this->s+(($this->larg)*$m)+$this->larg/2)+($this->larg);
						}
					}
					else{
						$x+=$this->disbar+$this->larg;

						if($this->mnvs<=0){
							$y1=$this->h-$this->b-($this->value[$t][$i]*$this->mul)-(abs($this->mn+$this->scarmin)*$this->mul);
							$y2=$this->h-$this->b-(abs($this->mn+$this->scarmin)*$this->mul);
							$x1=$x-$this->larg;
							$x2=$x;
							if($this->value[$t][$i]>0){
								imagefilledrectangle($this->im,$x1,$y1,$x2,$y2,$cc);
								imagerectangle($this->im,$x1,$y1,$x2,$y2,$cb);
							}
							else{
								imagefilledrectangle($this->im,$x1,$y2,$x2,$y1,$cc);
								imagerectangle($this->im,$x1,$y1,$x2,$y2,$cb);
							}
						}
						else{
							$y1=($this->h-$this->b)-(($this->value[$t][$i]-$this->mnvs)*$this->mul);
							$y2=$this->h-$this->b+($this->mnvs*$this->mul);
							if($this->value[$t][$i]>0)	imagefilledrectangle($this->im,$x-$this->larg,$y1,$x,$y2,$cc);
						}
					}
				}
			$m++;
			}
		}
	}
}
?>