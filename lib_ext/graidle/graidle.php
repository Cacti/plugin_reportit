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

class Graidle
{
	var $legend=FALSE;			#flag per visualizzare la legenda
	var $dim_quad=12;			#dimensione quadrato di riferimento legenda
	var $spacing=6;				#spaziatura voci legenda

	var $font_color="#000000";	#Colore del Carattere
	var $bg_color="#FFFFFF";	#Colore dello Sfondo
	var $axis_color="#C0C0C0";	#Colore degli Assi

	var $value=array();
	var $type=array();

	var $cvl=0;					#variabile Current Value
	var $mx=0;					#variabile del massimo
	var $mn=0;					#variabile del minimo
	var $cn=0;					#variabile del numero massimo di valori in una serie
	var $scarmax=1;
	var $scarmin=-1;

	function Graidle($title=NULL,$mass=NULL,$mnvs=NULL){
		$this->title=$title;
		$this->mass=$mass;
		$this->mnvs=$mnvs;
		$this->fontMono=FALSE;

		/* added for non-multicolored legend of pie chart */
		$this->multicolorText = false;

		/* added for Dynamic Fontsize */
		$this->dynFontSize = false;

		graidle::setFont("./Vera.ttf");
		graidle::setFontBD("./VeraBd.ttf");
		graidle::setFontLegend("./Vera.ttf");
		$this->width=NULL;
		$this->height=NULL;
		$this->xAxis=NULL;
		$this->yAxis=NULL;
		$this->vlx=NULL;
		$this->legend=NULL;
		$this->filled=NULL;
		$this->sx=0;
		$this->sy=0;
		$this->mx=1;														# changed A.Braun 28/Jan/2008 Bug#00003
		$this->mn=0;
		$this->larg=10;
		$this->cnt=0;
		isset($this->title) ? $this->a=$this->font_big*2 : $this->a=10;		# distance to the top
		$this->b=10;														# distance to the bottom
		$this->s=10;														# distance to the left side
		$this->d=20;														# distande to the right side
		$this->LegStrLen=64;
		$this->LegendAlign="right";
		include_once("graidle_color.ext.php");
		TableOfColors::color();
	}

	function create(){
		if(in_array("b",$this->type)||in_array("l",$this->type))
		{
			for($bar=$i=0;$i < count($this->type);$i++)
				if($this->type[$i]=='b')	$bar+=1;

			$this->disbar=$this->larg*$bar;
			$this->ld=$this->larg+$this->disbar;	# variabile di comodo #

			if((in_array("l",$this->type))&&($this->disbar==0))
			{
				$this->disbar=2*$this->larg;
				$this->ld=$this->disbar;	# variabile di comodo #
			}

			if(!isset($this->mass))	$this->mass=$this->mx;
			if(!isset($this->mnvs))	$this->mnvs=$this->mn;
			if(isset($this->name))	graidle::setLegend($this->name);

			/* define the divisor for the subdivision of the x-axis */
			if(!isset($this->dvx)){
				if($this->mass<=1)							$this->dvx=round($this->mass/5,1);
				else if(($this->mass>1)&&($this->mass<10))	$this->dvx=1;
				else										$this->dvx=round($this->mass/10);
			}
if($this->dvx == 0) $this->dvx = 0.2;

			/* define default anti-alysing factor */
			if(!isset($this->AA))	$this->AA=2;

			if($this->mx>0){
				if($this->mass==$this->mx)		$this->scarmax=1;						#considerare se mettere zero o un valore o rimettere dvx
				else							$this->scarmax=$this->mass-$this->mx;
			}

			$this->scarmin=$this->mn;
			if($this->mn<0){
				if($this->mnvs>0 || !isset($this->mnvs))				$this->scarmin=0;
				else if($this->mnvs>$this->mn||$this->mnvs<$this->mn)	$this->scarmin=$this->mnvs-$this->mn;
				else 													$this->scarmin=-1;
			}

			if($this->mn == 0 & $this->mx ==0)			$this->y_flag=3;
			elseif(strlen($this->mn)>strlen($this->mx))	$this->y_flag=strlen($this->mn);
			else										$this->y_flag=strlen($this->mx);

			$this->s+= ($this->mass != 0) 	? $this->font_small*(graidle::stringLen($this->mass))
											: $this->font_small*(graidle::stringLen('0.2'));

			if(!isset($this->w))
			{
				$this->w=($this->ld*$this->cnt)+$this->s+$this->d;

				if($this->w<640)
				{
					while($this->w<640)
					{
						$this->larg+=0.01;
						$this->disbar=($this->larg)*$bar;
						$this->ld=$this->larg+$this->disbar;
						$this->w=round($this->ld*$this->cnt)+$this->s+$this->d;
					}
				}
				else
				{
					while($this->w>641)
					{
						$this->larg-=0.01;
						$this->disbar=($this->larg*$bar);
						$this->ld=$this->larg+$this->disbar;
						$this->w=($this->ld*$this->cnt)+$this->s+$this->d;
					}
				}
			}
			else
			{
				while( (($this->ld*$this->cnt)+$this->s+$this->d) >= $this->w)
				{
					$this->larg-=0.01;
					$this->disbar=$this->larg;
					$this->ld=$this->larg+$this->disbar;
				}

				while( (($this->ld*$this->cnt)+$this->s+$this->d) <= $this->w)
				{
					$this->larg+=0.01;
					$this->disbar=$this->larg;
					$this->ld=$this->larg+$this->disbar;
				}
			}
			if(!isset($this->h))	$this->h=round((3/4)*$this->w);

			if(isset($this->xAxis)) $this->b+=4*$this->font_small;		#added by A.Braun 07/april/08 2->4
			else $this->b+=2*$this->font_small;

			if($this->mnvs>0&&$this->mass>0)	$this->mul=($this->h-$this->a-$this->b)/($this->mass-$this->mnvs);
			else 								$this->mul=($this->h-$this->a-$this->b)/(($this->mass+$this->scarmax)+(abs($this->mn)-$this->scarmin));

			$this->div=$this->dvx*$this->mul;
			$this->im=imagecreatetruecolor($this->w,$this->h);

			$rgb=TableOfColors::hex2rgb($this->axis_color);
			$this->axis_color=imagecolorallocate($this->im,$rgb[0],$rgb[1],$rgb[2]);

			$rgb=TableOfColors::hex2rgb($this->font_color);
			$this->font_color=imagecolorallocate($this->im,$rgb[0],$rgb[1],$rgb[2]);

			$rgb=TableOfColors::hex2rgb($this->bg_color);
			$this->bg_color=imagecolorallocate($this->im,$rgb[0],$rgb[1],$rgb[2]);
			imagefilltoborder($this->im,1,1,1,$this->bg_color);

			if(isset($this->legend) || isset($this->name))	graidle::legend();

			graidle::title($this->title,$this->xAxis,$this->yAxis);
			graidle::gradAxis($this->sx,$this->sy);

			if(in_array("b",$this->type)){
				include_once("graidle_histo.ext.php");							#changed A.Braun 28/Jan/2008 Bug#00002
				histogram::drawHisto();
			}

			if(in_array("l",$this->type)){
				include_once("graidle_line.ext.php");							#changed A.Braun 28/Jan/2008 Bug#00002
				line::drawLine();
			}
			graidle::drawAxis();

		}
		else if(in_array("hb",$this->type))
		{
			for($bar=$i=0;$i < count($this->type);$i++)
				if($this->type[$i]=='hb')	$bar+=1;

			$this->disbar=$this->larg*$bar;

			if(isset($this->name))	graidle::setLegend($this->name);
			if(!isset($this->mass))	$this->mass=$this->mx;
			if(!isset($this->mnvs))	$this->mnvs=$this->mn;

			if(!isset($this->dvx))
			{
				if($this->mass<=1)							$this->dvx=round($this->mass/5,1);
				else if(($this->mass>1)&&($this->mass<10))	$this->dvx=1;
				else										$this->dvx=round($this->mass/10);
			}

			if(!isset($this->AA))		$this->AA=4;

			$this->b+=5*$this->font_small;
			$this->d+=round(graidle::StringLen($this->mass)*($this->font_small/4));

			if(isset($this->vlx))
			{
				for($maxlen=$i=0;$i<=count($this->vlx);$i++)
				{
					if(isset($this->vlx[$i]))
					{
						$curlen=(graidle::stringlen($this->vlx[$i])*$this->font_small);
						if($maxlen<$curlen)
							$maxlen=$curlen;
					}
				}
				$this->s+=$maxlen+10;
			}
			else	$this->s+=$this->font_small*4;

			if(isset($this->yAxis))	$this->s+=2*$this->font_small;				# changed by A.Braun 29/Jan/2009 Bug#00004

			if(strlen($this->mn)>strlen($this->mx))	$this->y_flag=strlen($this->mn);	# added by A.Braun 29/Jan/2009 Bug#00004
			else									$this->y_flag=strlen($this->mx);	# added by A.Braun 29/Jan/2009 Bug#00004


			$this->ld=$this->larg+$this->disbar;	# variabile di comodo #

			if(!isset($this->h))
			{
				$this->h=($this->ld*$this->cnt)+$this->a+$this->b;

				if($this->h<500)
				{
					while($this->h<500)
					{
						$this->larg+=0.01;
						$this->disbar=($this->larg)*$bar;
						$this->ld=$this->larg+$this->disbar;
						$this->h=round($this->ld*$this->cnt)+$this->a+$this->b;
					}
				}
				else
				{
					while($this->h>501)
					{
						$this->larg-=0.01;
						$this->disbar=($this->larg*$bar);
						$this->ld=$this->larg+$this->disbar;
						$this->h=($this->ld*$this->cnt)+$this->a+$this->b;
					}
				}
			}
			else
			{
				while( (($this->ld*$this->cnt)+$this->a+$this->b) <= $this->h)
				{
					$this->larg+=0.01;
					$this->disbar=($this->larg)*$bar;
					$this->ld=$this->larg+$this->disbar;
				}

				while( (($this->ld*$this->cnt)+$this->a+$this->b) >= $this->h)
				{
					$this->larg-=0.01;
					$this->disbar=($this->larg)*$bar;
					$this->ld=$this->larg+$this->disbar;
				}
			}

			if(!isset($this->w))	$this->w=round((4/5)*$this->h);

			if($this->mnvs>0&&$this->mass>0)	$this->mul=($this->w-$this->s-$this->d)/($this->mass-$this->mnvs);
			else								$this->mul=($this->w-$this->s-$this->d)/(($this->mass)+abs($this->mnvs));

			$this->im=imagecreatetruecolor($this->w,$this->h);

			$rgb=TableOfColors::hex2rgb($this->axis_color);
			$this->axis_color=imagecolorallocate($this->im,$rgb[0],$rgb[1],$rgb[2]);

			$rgb=TableOfColors::hex2rgb($this->font_color);
			$this->font_color=imagecolorallocate($this->im,$rgb[0],$rgb[1],$rgb[2]);

			$rgb=TableOfColors::hex2rgb($this->bg_color);
			$this->bg_color=imagecolorallocate($this->im,$rgb[0],$rgb[1],$rgb[2]);

			imagefilltoborder($this->im,1,1,1,$this->bg_color);

			if(isset($this->legend) || isset($this->name))	graidle::legend();

			include_once("graidle_horizhisto.ext.php");							#changed A.Braun 28/Jan/2008 Bug#00002
			HorizHistogram::gradAxis($this->sx,$this->sy);
			HorizHistogram::drawHorizHisto();
			HorizHistogram::drawAxis();

			if(isset($this->xAxis)) $this->b-=$this->font_small;		#added by A.Braun 09/april/08
			graidle::title($this->title,$this->xAxis,$this->yAxis);
		}
		else if(in_array("p",$this->type))
		{
			include_once("graidle_pie.ext.php");								#changed A.Braun 28/Jan/2008 Bug#00002

			for($this->pie=$i=0;$i < count($this->type);$i++)
				if($this->type[$i]=='p')	$this->pie+=1;

			if(!isset($this->incl))	$this->incl=55;
			if(!isset($this->AA))	$this->AA=4;
			if(!isset($this->w))	$this->w=500;
			if(!isset($this->h))	$this->h=500;

			$this->tre_d=0;
			if($this->incl<90)		$this->tre_d=round(($this->incl)/5);

			$this->radius=$this->w;

			$e=sin(deg2rad($this->incl));
			$rapp=pow($e,2);
			$a=$this->radius;
			$b=$a*$rapp;

			while( $a >= ($this->w-$this->s-$this->d)){
				$a-=1;
				$this->radius=$a;
				$b=$a*$rapp;
			}

			while( ($b*$this->pie) > $this->h-($this->a)-($this->pie*$this->b)-($this->pie*$this->tre_d)){
				$b-=1;
				$a=$b/$rapp;
				$this->radius=$a;
			}

			$this->im=imagecreatetruecolor($this->w,$this->h);	#<----CREO L'IMMAGINE PER IL GRAFICO A TORTA

			$rgb=TableOfColors::hex2rgb($this->bg_color);
			$this->bg_color=imagecolorallocate($this->im,$rgb[0],$rgb[1],$rgb[2]);
			imagefilltoborder($this->im,1,1,1,$this->bg_color);	#<---- Creo lo sfondo

			$rgb=TableOfColors::hex2rgb($this->font_color);
			$this->font_color=imagecolorallocate($this->im,$rgb[0],$rgb[1],$rgb[2]);

			if(isset($this->legend))	graidle::legend();
			graidle::title($this->title);
			pie::drawPie($a,$b);
		}
		else if(in_array("s",$this->type))
		{
			include_once("graidle_spider.ext.php");								#changed A.Braun 28/Jan/2008 Bug#00002

			if(!isset($this->mass))		$this->mass=$this->mx;
			if(!isset($this->filled))	$this->filled=1;
			if(!isset($this->AA))		$this->AA=4;
			if(!isset($this->w))
					if(isset($this->h))	$this->w=round($this->h*(5/4));
						else			$this->w=500;
			if(!isset($this->h))		$this->h=round($this->w*(4/5));
			if(isset($this->name))		graidle::setLegend($this->name);

			if(!isset($this->dvx)){
				if(($this->mass/10)<1)	$this->dvx=round($this->mass/5,1);
				else					$this->dvx=round($this->mass/10);
			}

			$this->radius=$this->w-$this->s-$this->d;

			while($this->radius >= ($this->h-$this->a-$this->b))
				$this->radius-=1;

			$this->radius=round($this->radius/2);

			$this->im=imagecreatetruecolor($this->w,$this->h);	#<----CREO L'IMMAGINE PER IL GRAFICO A TORTA

			$rgb=TableOfColors::hex2rgb($this->bg_color);
			$this->bg_color=imagecolorallocate($this->im,$rgb[0],$rgb[1],$rgb[2]);
			imagefilltoborder($this->im,1,1,1,$this->bg_color);	#<---- Creo lo sfondo

			$rgb=TableOfColors::hex2rgb($this->font_color);
			$this->font_color=imagecolorallocate($this->im,$rgb[0],$rgb[1],$rgb[2]);

			$rgb=TableOfColors::hex2rgb($this->axis_color);
			$this->axis_color=imagecolorallocate($this->im,$rgb[0],$rgb[1],$rgb[2]);

			if(isset($this->legend))	graidle::legend();
			graidle::title($this->title);

			spider::drawSpider();
		}
	}
	function carry(){
		header("Content-type: image/png");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Cache-Control: max-age=0");
		header("Pragma: no-cache");

		imagepng($this->im);
		imagedestroy($this->im);
	}
	function carry2file($patch=NULL,$fname=NULL){
		if(!isset($fname))	$fname=rand(0,9999);
		if(!isset($patch))	$patch="./tmp/";

		is_dir($patch)		or die("<pre><b>GRAIDLE ERROR:</b> Patch ($patch) not is a Directory.</pre>");
		is_writable($patch)	or die("<pre><b>GRAIDLE ERROR:</b> Directory ($patch) not is Writable.</pre>");

		$fname=trim($fname).".png";
		$patch=trim($patch);

		if($patch{strlen($patch)-1}!="/")	$patch.="/";

		imagepng($this->im,$patch.$fname);
		echo"<img src=\"$patch$fname\">";
		imagedestroy($this->im);
	}
	function title($title=NULL,$xAxis=NULL,$yAxis=NULL){
		#all positions have been redefined by A.Braun
		if($xAxis!="" || $xAxis!=NULL)	imagefttext($this->im , $this->font_small , 0 , ($this->w-$this->d)-0.75*(graidle::stringlen($xAxis)*$this->font_small) , $this->h-$this->b+3.5*$this->font_small  , $this->font_color , $this->fontBd , $xAxis);
		if($yAxis!="" || $yAxis!=NULL)	imagefttext($this->im , $this->font_small , 90 , $this->s-($this->y_flag*0.75*$this->font_small), $this->a+0.85*(graidle::stringlen($yAxis)*$this->font_small), $this->font_color , $this->fontBd , $yAxis);
		if($title!="" || $title!=NULL)	imagefttext($this->im , $this->font_big , 0 , ($this->w/2)+20-((graidle::stringlen($title)/2*($this->font_big))) , 1.5*$this->font_big , $this->font_color , $this->fontBd , $title);
	}
	function drawAxis(){
		Graidle::imagelinethick($this->im , $this->s , $this->a , $this->s , $this->h-$this->b , $this->axis_color,2);

		$n=1;
		if(!isset($this->vlx))
			for($i=1;$i<=$this->cnt;$i++)
				$this->vlx[$i]=$i;

		else	$n=0;

		for($i=$this->s ; $i<=($this->w-$this->d) ; $i+=$this->disbar+$this->larg , $n++ ){
			if(isset($this->vlx[$n])){
				imageline($this->im , $i , $this->h-$this->b+2 , $i , $this->h-$this->b , $this->axis_color);
				imagefttext($this->im , $this->font_small , 0 , $i+(($this->larg+$this->disbar)/2)-(($this->font_small*strlen($this->vlx[$n]))/2) , $this->h-$this->b+$this->font_small+5 , $this->font_color , $this->font , $this->vlx[$n]);
			}
		}

		if($this->mnvs<=0){
			Graidle::imagelinethick($this->im , $this->s , $lu=$this->h-$this->b-(abs($this->mn+$this->scarmin)*$this->mul) , $this->w-$this->d,$lu,$this->axis_color,2);
		}
	}
	function gradAxis($sy=NULL,$sx=NULL){
		$c=imagecolorallocatealpha($this->im,255,255,255,127);
		$style=array($c,$this->axis_color);
		imagesetstyle ($this->im, $style);

		if($this->mn == 0 & $this->mx == 0)			$y_flag=3;
		elseif(strlen($this->mn)>strlen($this->mx))	$y_flag=strlen($this->mn);
		else										$y_flag=strlen($this->mx);

		#Asse x griglia secondaria e tacche
		if($sx)
			for($i=$this->s;$i<=($this->w-$this->d);$i+=$this->disbar+$this->larg)
				imageline($this->im, $i , $this->a , $i , $this->h-$this->b , IMG_COLOR_STYLED);
		#Asse Y
		if($this->mnvs<=0)	$zero=$this->h-$this->b-round(abs(($this->mn+$this->scarmin)*$this->mul));
		else				$zero=$this->b;

		#Up zero
		imagefttext($this->im,$this->font_small,0,$this->s-(imagefontwidth($this->font_small)*strlen(0)),($zero)+($this->font_small/2),$this->font_color,$this->font,0);

		$n=round($this->dvx*$this->mul);
$prec = ($this->mn == 0 & $this->mx == 0)? 1 : 0;
//echo "$n,$this->dvx, $this->mul, $zero, $this->a";

/* First Distance */
		$dist = round($n/$this->mul,$prec);
		$num = 0;
		while($zero-$n >= $this->a)
		{
			$num++;
			imageline($this->im, $this->s-2 , $zero-$n , $this->s , $zero-$n ,$this->axis_color);
			if($sy)	imageline($this->im, $this->s , $zero-$n , $this->w-$this->d , $zero-$n , IMG_COLOR_STYLED);

			$v=round($n/$this->mul,$prec);
			$v = $num*$dist;
//echo "$n,$this->mul,$v<br>";
			imagefttext($this->im,$this->font_small,0,$this->s-(($this->font_small)*graidle::stringlen($v)),($zero-$n)+($this->font_small/2),$this->font_color,$this->font, $v);
			$n+=round($this->dvx*$this->mul);
		}
//$v = 1;
		#Under zero
		for($n=round($this->dvx*$this->mul) ; $zero+$n <= $this->h-$this->b ; $n+=$this->dvx*$this->mul)
		{
			imageline($this->im, $this->s-2 , $zero+$n , $this->s , $zero+$n ,$this->axis_color);
			if($sy)	imageline($this->im, $this->s , $zero+$n , $this->w-$this->d , $zero+$n , IMG_COLOR_STYLED);
			$v=round($n/$this->mul);
			imagefttext($this->im,$this->font_small,0,$this->s-(($this->font_small)*(graidle::stringlen($v))+5),($zero+$n)+($this->font_small/2),$this->font_color,$this->font,-$v);
		}
	}
	function legend(){
		$cla1=imagecolorallocatealpha($this->im,0,0,0,70);
		$cla2=imagecolorallocatealpha($this->im,0,0,0,100);
		$cla=imagecolorallocatealpha($this->im,0,0,0,110);
		$black=imagecolorallocatealpha($this->im,0,0,0,0);

		$sp_mez=$this->spacing/2;

		if(($this->LegendAlign=="right")||($this->LegendAlign=="left"))
		{
			$x1=$this->w-$this->spacing-$this->dim_quad-$this->spch;
			$x2=$this->w-1;
			$y1=$this->a;
			$y2=($this->a)+($this->dim_quad+$this->spacing)*(count($this->legend));

			if($this->LegendAlign=="left")
			{
				$x1=0;
				$x2=$this->spacing+$this->dim_quad+$this->spch;
				$y1=$this->a;
				$y2=($this->a)+($this->dim_quad+$this->spacing)*(count($this->legend));
			}
			imagefilledrectangle($this->im, $x1 , $y1 , $x2 , $y2 , $cla);
			imagerectangle($this->im, $x1 , $y1 , $x2 , $y2 , $cla);

			for($x1+=$sp_mez,$y1+=$sp_mez,$s=1,$i=0;$i < count($this->legend);$i++,$s++,$y1+=$this->spacing)
			{
				$c=$this->color[$i];
				list($name,$red,$green,$blue)=explode(',',$c);
				$rgb=imagecolorallocatealpha($this->im,$red,$green,$blue,12);
				imagefilledrectangle($this->im , $x1 , $y1 , $x1+$this->dim_quad , $y1+=$this->dim_quad , $rgb);
				$rgb=imagecolorallocatealpha($this->im,$red/2,$green/2,$blue/2,80);
				imagerectangle($this->im , $x1 , $y1-$this->dim_quad , $x1+$this->dim_quad , $y1 , $rgb);
				$str=(string)($this->legend[$i]);
				imagefttext($this->im , $this->font_legend , 0 , $x1+$this->dim_quad+4 , $y1-($this->dim_quad/2)+($this->font_legend/2), $this->font_color , $this->fontLeg , $str);
				imageline($this->im,$x1-($sp_mez),$y1+$sp_mez,$x2,$y1+$sp_mez,$cla);
			}
		}
		else if(($this->LegendAlign=="top")||($this->LegendAlign=="bottom"))
		{
			$CellSpace=ceil($this->dim_quad+$this->spch);
			if($this->nrow!=count($this->legend))
			{
				for($s=1,$wleg=$CellSpace ; $this->w-$this->d-$this->s > $CellSpace*$s ; $s++)
					$wleg=round($CellSpace*$s);

				if($wleg>$CellSpace*count($this->legend))	$wleg=$CellSpace*count($this->legend);
			}
			else	$wleg=$CellSpace;

			$padding=ceil(($this->w-$this->d-$this->s)-$wleg)/2;

			$sx=round($this->s+$padding);
			$dx=round($this->w-$this->d-$padding);
			$up=$this->h-$this->spacerow-5;
			$down=$this->h-5;

			if($this->LegendAlign=="top"){
				$up=$this->a-$this->spacerow-5;
				$down=$this->a-5;
			}

			$rowsize=round($this->spacerow/$this->nrow);

			imagefilledrectangle($this->im, $sx , $up , $dx , $down , $cla);
			imagerectangle($this->im, $sx-1 , $up-1 , $dx+1 , $down+1 , $cla1);
			imagerectangle($this->im, $sx-2 , $up-2 , $dx+2 , $down+2 , $cla2);

			for($row=1,$s=0;$s < count($this->legend);$s++)
			{
				$c=$this->color[$s];
				list($name,$red,$green,$blue)=explode(',',$c);
				$rgb=imagecolorallocate($this->im,$red,$green,$blue);
				$rgbA=imagecolorallocatealpha($this->im,$red/2,$green/2,$blue/2,80);
				$str=(string)($this->legend[$s]);

				if(!$s){
					$x1=$sx;
					$y1=$up+round($rowsize*$s);
					$x2=$x1+$CellSpace;
					$y2=$y1+$rowsize;
				}

				imagefilledrectangle($this->im, $x1 , $y1 , $x2 , $y2 , $rgbA);
				imagerectangle($this->im, $x1 , $y1 , $x2 , $y2 , $rgbA);

				imagefttext($this->im , $this->font_legend , 0 , $x1+$this->dim_quad+$this->spacing , $y1+($rowsize/2)+($this->font_small/2), $this->font_color , $this->fontLeg , $str);

				imagefilledrectangle($this->im , $x1+$sp_mez , $y1+$sp_mez , $x1+$this->dim_quad+$sp_mez , $y1+$this->dim_quad+$sp_mez , $rgb);
				imagerectangle($this->im , $x1+$sp_mez , $y1+$sp_mez , $x1+$this->dim_quad+$sp_mez , $y1+$this->dim_quad+$sp_mez , $rgbA);

				$x1=$x2;
				$x2+=$CellSpace;

				if($x1>=($dx)){
					$row+=1;
					$x1=$sx;
					$x2=$x1+$CellSpace;
					$y1=$y2;
					$y2+=$rowsize;
				}
			}
		}
	}
	function imagelinethick($image, $x1, $y1, $x2, $y2, $color, $thick = 1){
		if ($thick == 1) {
			return imageline($image, $x1, $y1, $x2, $y2, $color);
		}
		$t = $thick / 2 - 0.5;
		if ($x1 == $x2 || $y1 == $y2) {
			return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
		}
		$k = ($y2 - $y1) / ($x2 - $x1);
		$a = $t / sqrt(1 + pow($k, 2));
		$points = array(
			round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
			round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
			round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
			round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
		);
		imagefilledpolygon($image, $points, 4, $color);
		return imagepolygon($image, $points, 4, $color);
	}
	function stringLen($str){
		$str=(string)($str);

		if($this->fontMono==FALSE){
			for($len=$s=0;$s<strlen($str);$s++){
				$ascii=ord($str{$s});
				if($ascii==33||$ascii==39||$ascii==44||$ascii==46)		$len+=(0.3);
				else if(($ascii<126&&$ascii>96)||($ascii<48&&$ascii>31))$len+=(0.7);
				else if($ascii<91&&$ascii>64) 							$len+=(1.25);
				else													$len+=1;
			}
			return ceil($len);
		}
		else	return strlen($str);
	}
	function setValue($value,$type,$name=NULL,$color=NULL){
		array_push($this->value,$value);
		array_push($this->type,$type);

		if(isset($name))
		{
			if($type=="p"){
				if(!isset($this->PieTitle))	$this->PieTitle=array();
				array_push($this->PieTitle,$name);
			}
			else{
				if(!isset($this->name))	$this->name=array();
				array_push($this->name,$name);
			}
		}
		if(isset($color))
		{
			$color=(string)($color);

			while(current($this->color))
			{
				$currcl=(string)(current($this->color));

				if(preg_match("/".$color."/i",$currcl))
				{
					$tmp=$this->color[$this->cvl];
					$this->color[$this->cvl]=current($this->color);
					$this->color[key($this->color)]=$tmp;
					end($this->color);
				}
				else	next($this->color);
			}
			reset($this->color);
		}

		if(max($this->value[$this->cvl])>$this->mx)		$this->mx=max($this->value[$this->cvl]);
		if(min($this->value[$this->cvl])<$this->mn)		$this->mn=min($this->value[$this->cvl]);
		if(count($this->value[$this->cvl])>$this->cnt)	$this->cnt=count($this->value[$this->cvl]);

		$this->cvl+=1;
	}
	function setHeight($height){
		if(is_numeric($height)) {
			$this->h=$height;
			/* added for Dynamic Fontsize */
			if($this->dynFontSize && isset($this->w))graidle::autoFontSize($height, $this->w);
		}
	}
	function setWidth($width){
		if(is_numeric($width)) {
			$this->w=$width;
			/* added for Dynamic Fontsize */
			if($this->dynFontSize && isset($this->h))graidle::autoFontSize($this->w, $width);
		}
	}
	function setFont($font,$size=8){
		$this->font=$font;
		/* added for Dynamic Fontsize */
		if(!$this->dynFontSize){
			graidle::setFontSmallSize($size);
			graidle::setFontBigSize($size*2);
		}
	}
	function setFontBD($fontbd,$size=8){
		$this->fontBd=$fontbd;
		/* added for Dynamic Fontsize */
		if(!$this->dynFontSize){
			graidle::setFontSmallSize($size);
			graidle::setFontBigSize($size*2);
		}
	}
	function setFontLegend($fontleg,$size=8){
		$this->fontLeg=$fontleg;
		/* added for Dynamic Fontsize */
		if(!$this->dynFontSize)graidle::setFontLegSize($size);
	}
	function setFontSmallSize($size){
		if($size>0&&$size<72)	$this->font_small=(int)$size;
		else					die("<b>Graidle Error:</b> setFontSmallSize(int) size value must be between 1 and 72</br>");
	}
	function setFontBigSize($size){
		if($size>0&&$size<72)	$this->font_big=(int)$size;
		else					die("<b>Graidle Error:</b> setFontBigSize(int) size value must be between 1 and 72</br>");
	}
	function setFontLegSize($size){
		if($size>0&&$size<72)	$this->font_legend=(int)$size;
		else					die("<b>Graidle Error:</b> setFontLegSize(int) size value must be between 1 and 72</br>");
	}
	function setFontMono(){
		$this->fontMono=TRUE;
	}
	function setBgCl($HEXcolor){
		$this->bg_color=$HEXcolor;
	}
	function setFontCl($HEXcolor){
		$this->font_color=$HEXcolor;
	}
	function setAxisCl($HEXcolor){
		$this->axis_color=$HEXcolor;
	}
	function setSecondaryAxis($sx,$sy){
		if($sx)	$this->sx=1;
		if($sy)	$this->sy=1;
	}
	function setXtitle($xAxis){
		$this->xAxis=$xAxis;
	}
	function setYtitle($yAxis){
		$this->yAxis=$yAxis;
	}
	function setXValue($vlx){
		$this->vlx=$vlx;
	}
	function setInclination($incl){
		$this->incl=$incl;
	}
	function setAA($AA){
		$this->AA=$AA;
	}
	function setLegend($legend,$align=NULL){
		if(!isset($this->legend))	$this->legend=array();

		$this->legend=array_merge($this->legend,$legend);

		$spch=$this->font_legend*4;	#spazio per i caratteri della legenda

		for($i=0;$i < count($this->legend);$i++)
		{
			if(strlen($this->legend[$i])>$this->LegStrLen)	$this->legend[$i]=substr($this->legend[$i],0,$this->LegStrLen)."...";

			$tmpsp=graidle::stringlen($this->legend[$i])*($this->font_legend);
			if($spch<$tmpsp)	$spch=$tmpsp;
		}

		if(isset($this->w))	$this->nrow=ceil((($spch+$this->dim_quad+$this->spacing)*count($this->legend))/($this->w-$this->s-$this->d));
		else				$this->nrow=count($this->legend);

		$this->spacerow=ceil($this->nrow*($this->dim_quad+$this->spacing));
		$this->spch=$spch;

		$this->LegendAlign=strtolower($align);

		switch($this->LegendAlign)
		{
			case "left":	$this->s+=$this->spacing+$this->dim_quad+$this->spch;break;
			case "top":		$this->a+=$this->spacerow;break;
			case "bottom":	$this->b+=$this->nrow*($this->dim_quad+$this->spacing);break;break;
			default:		$this->LegendAlign="right";$this->d+=$this->spacing+$this->dim_quad+$this->spch;break;
		}
	}
	function setLegMaxLen($strlen){
		$this->LegStrLen=(int) $strlen;
	}
	function setExtLegend($type=0){
		switch($type){
			case 1 :	$this->ExtLeg=1;break;	#Only Percent;
			case 2 :	$this->ExtLeg=2;break;	#Both Value and Percent;
			case 3 :	$this->ExtLeg=3;break;	#added by A. Braun -> only show the id!
			default:	$this->ExtLeg=0;break;	#Only Value;
		}
	}
	function setFilled($filled=1){
		if($filled==1)	$this->filled=1;
		else			$this->filled=0;
	}
	function setFontSmall($font_small){
		if(is_numeric($font_small))	$this->font_small=$font_small;
	}
	function setFontBig($font_big){
		if(is_numeric($font_big))	$this->font_big=$font_big;
	}
	function setDivision($div){
		if(is_numeric($div))	$this->dvx=$div;
	}
	function setColor($color,$position=NULL){
		if(!is_array($color))	$color=array($color);

		if(!isset($position))			$color=array_reverse($color);
		elseif(!is_array($position))	$position=array($position);

		while($cl=current($color))
		{
			$strcl=NULL;

			if($cl{0}=="#"){
				$rgb=TableOfColors::hex2rgb($cl);
				$strcl="colore,".$rgb[0].",".$rgb[1].",".$rgb[2];
			}
			elseif(strpos($cl,",")){
				$strcl="colore,".$cl;
			}

			if($strcl){
				if(isset($position) && (current($position)+1))	$this->color[current($position)]=$strcl;
				else											array_unshift($this->color,$strcl);
			}
			next($position);
			next($color);
		}
	}
	function setMulticolor(){
		$this->multicolor=1;
	}
	/* new function for Dynamic Fontsize */
	function setdynFontSize(){
		$this->dynFontSize = true;
	}
	/* new function for Dynamic Fontsize */
	function autoFontSize($width, $height){
		if($width != 0 && $height != 0) {
			$lowest_value = ($width >= $height)? $height : $width;
			$fs_index = round($lowest_value/($lowest_value*0.05+24));
			graidle::setFontSmallSize($fs_index);
			graidle::setFontBigSize(round($fs_index*1.4));
			graidle::setFontLegSize($fs_index);
		}
	}

	/* new function to disable colored text in pie charts */
	function setTextColored(){
		$this->multicolorText = true;
	}

}
?>