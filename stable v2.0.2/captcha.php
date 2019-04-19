<?php
/*=======================================
|	This script was developed by alex Roosso .
|	Title: Protect Form (Capcha)
|	Version: 2.0.2
|	Release: Feb 2007
|	Homepage: http://www.black-web.ru
|	Copyright (c) 2007 alex Roosso.
|	All rights reserved.
========================================*/


$path = "captchabg";  //Name folder backgrounds
$num = 6;	//How many symbols in code


function RndCode($ns) 
{
	$strSymbols = "abcdqwerty1234567890ABCDEFGHIJKLMNPQARSTUVWXYZ";
	$Code = "";
	$i = 0;
	mt_srand((double)microtime() * 1000000);
	while ($i < $ns) 
	{
		$Code .= $strSymbols[mt_rand(0, strlen($strSymbols) - 1)];
		$i++;
	}
	return $Code;
}

$string = RndCode($num);

$bgcolor = array();
$bgcolor = array(255,255,255);

$bg_array = array();
$bg_array = glob($path."/*.{gif,jpg,png}", GLOB_BRACE);
$file_name = $bg_array[mt_rand(0,count($bg_array)-1)];
$size = getimagesize($file_name);
//$size[0] = 180; $size[1] = 50;

$capcha = imagecreatetruecolor($size[0], $size[1]);
$bg = imagecolorallocate($capcha,$bgcolor[0],$bgcolor[1],$bgcolor[2]);
imagefilledrectangle($capcha, 1, 1, $size[0]-2, $size[1]-2, $bg);
if($size['mime'] == "image/jpeg") $img = imagecreatefromjpeg($file_name);
elseif($size['mime'] == "image/png") $img = imagecreatefrompng($file_name);
elseif($size['mime'] == "image/gif") $img = imagecreatefromgif($file_name);
imagecopyresampled($capcha, $img, 1, 1, 1, 1, $size[0]-2, $size[1]-2, $size[0]-2, $size[1]-2);

//Letters
$str_w = $size[0] - 20;
$str_h = $size[1];
$one = $str_w / $num;
$xs = 5;
for($l=0;$l<=$num-1;$l++)
{ 
	$letter_img = imagecreate($one,$str_h);
	$color = imagecolorallocate($letter_img, 255, 255, 255);
	imagefilledrectangle($letter_img, 0, 0, $one, $str_h, $color);
	imagecolortransparent($letter_img,$color);
	$r = mt_rand(0,150);
	$g = mt_rand(0,150);
	$b = mt_rand(0,150);
	$color2 = imagecolorallocatealpha($letter_img, $r, $g, $b, 70);
	$radius = mt_rand(1,3);
	$x = mt_rand(1,10);
	$y = mt_rand(1,20);
	imagefilledellipse($letter_img, $x, $y, $radius, $radius, $color2);
	$letter = substr($string,$l,1);
	imagestring($letter_img, 5, 0, 0, $letter, $color2);
	imagecopyresized($capcha, $letter_img, mt_rand($xs,$xs+10), mt_rand(5,10), 0, 0, $one*3, $str_h*($one/3), $one, $str_w);
	$xs += $one;
	imagedestroy($letter_img);
}

//Circle
for($i=0;$i<=mt_rand(3,$num);$i++)
{
	$r = mt_rand(120,255);
	$g = mt_rand(140,255);
	$b = mt_rand(160,255);
	$color = imagecolorallocatealpha($capcha, $r, $g, $b, mt_rand(60,75));
	$radius = mt_rand(10,$size[1]-10);
	$x = mt_rand(1,$size[0]-1);
	$y = mt_rand(1,$size[1]-1);
	imagefilledellipse($capcha, $x, $y, $radius, $radius, $color);
}

//Polygons
for($i=0;$i<=mt_rand(1,$num-2);$i++)
{
	$r = mt_rand(0,120);
	$g = mt_rand(0,140);
	$b = mt_rand(0,160);
	$color = imagecolorallocatealpha($capcha, $r, $g, $b, 20);
	$points = array();
	$p = mt_rand(3,10);
	for($s=0;$s<=$p;$s++)
	{
		$points[] = mt_rand(1,$size[0]-2);
		$points[] = mt_rand(1,$size[1]-2);
	}
	imagepolygon($capcha, $points, $p, $color);
}


header("Content-type: image/jpeg");
imagejpeg($capcha);
imagedestroy($capcha);


?>