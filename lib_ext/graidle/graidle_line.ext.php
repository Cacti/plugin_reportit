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
class Line extends Graidle
{
	function drawLine(){
		$AA=$this->AA;
		if($this->filled){
			$thick=1.5*$AA;
			$sig=($thick*3);
			$point=array();
		}
		else{
			$thick=1.5*$AA;
			$sig=($thick*3);
		}
		$Htmp=$this->h*$AA;
		$Wtmp=($this->w-$this->s-$this->d)*$AA;
		for($t=0;$t<count($this->value);$t++)
		{
			if($this->type[$t]=='l')
			{
				$img_tmp = imagecreatetruecolor($Wtmp,$Htmp);
				$trasp=imagecolorallocatealpha($img_tmp,0,0,0,127);

				imagefill($img_tmp,0,0,$trasp);

				$cc=$this->color[$t];
				list($name,$red,$green,$blue)=explode(',',$cc);
				$cc=imagecolorallocate($this->im,$red,$green,$blue);
				$rgbA=imagecolorallocatealpha($this->im,$red,$green,$blue,60);

				$zero=(($this->h-$this->b)-(abs($this->mn+$this->scarmin)*$this->mul))*$AA;

				for($x=0, $x1=(($this->larg+$this->disbar)/2)*$AA ,$i=0,$i1=1;$i1<count($this->value[$t]);$i++,$i1++,$x1=$x2)
				{
					if($x==0)
					{
						$x2=$x1+($this->disbar+$this->larg)*$AA;

						if($this->mnvs<=0)
						{
							$y1=(($this->h-$this->b)-($this->value[$t][$i]*$this->mul)-(abs($this->mnvs)*$this->mul))*$AA;
							$y2=(($this->h-$this->b)-($this->value[$t][$i1]*$this->mul)-(abs($this->mnvs)*$this->mul))*$AA;

							if($this->value[$t][$i]>=0)	Graidle::imagelinethick($img_tmp, $x1 , $y1 , $x2 , $y2 , $cc , $thick);
							else						Graidle::imagelinethick($img_tmp, $x1 , $y1 , $x2 , $y2 , $cc , $thick);

							imagefilledellipse($img_tmp,$x1,$y1,$sig,$sig,$cc);
							imagefilledellipse($img_tmp,$x2,$y2,$sig,$sig,$cc);
						}
						else
						{
							$y1=(($this->h-$this->b)-(($this->value[$t][$i]-$this->mnvs)*$this->mul));
							$y2=(($this->h-$this->b)+($this->mnvs*$this->mul)-(($this->value[$t][$i1])*$this->mul));

							if($this->value[$t][$i]>=0){
								Graidle::imagelinethick($img_tmp, $x1 , $y1 , $x2 , $y2,$cc);

								imagefilledellipse($img_tmp,$x1,$y1,$sig,$sig,$cc);
								imagefilledellipse($img_tmp,$x1,$y2,$sig,$sig,$cc);
							}
						}

						if($this->filled){
							$point=array($x1,$zero,$x2-1,$zero,$x2-1,$y2,$x1,$y1);
							imagefilledpolygon($img_tmp,$point,4,$rgbA);
							reset($point);
						}
					}
				}
				imagecopyresampled($this->im,$img_tmp, $this->s , 0 , 0 , 0 ,$Wtmp/$AA,$Htmp/$AA,$Wtmp,$Htmp);
				if(isset($rgb))		imagecolordeallocate($img_tmp,$rgb);
				if(isset($trasp))	imagecolordeallocate($img_tmp,$trasp);
				imagedestroy($img_tmp);
			}
		}
	}
}?>