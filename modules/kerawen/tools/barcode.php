<?php
/**
* 2014 KerAwen
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/AFL-3.0
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to contact@kerawen.com so we can send you a copy immediately.
*
* @author    KerAwen <contact@kerawen.com>
* @copyright 2014 KerAwen
* @license   http://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
*/

/* */
function barcode39($text, $height = 100, $thin = 2, $thick = 6, $text_size = 10)
{
	static $map = array(
		'0' => '000110100',
		'1' => '100100001',
		'2' => '001100001',
		'3' => '101100000',
		'4' => '000110001',
		'5' => '100110000',
		'6' => '001110000',
		'7' => '000100101',
		'8' => '100100100',
		'9' => '001100100',
		'A' => '100001001',
		'B' => '001001001',
		'C' => '101001000',
		'D' => '000011001',
		'E' => '100011000',
		'F' => '001011000',
		'G' => '000001101',
		'H' => '100001100',
		'I' => '001001100',
		'J' => '000011100',
		'K' => '100000011',
		'L' => '001000011',
		'M' => '101000010',
		'N' => '000010011',
		'O' => '100010010',
		'P' => '001010010',
		'Q' => '000000111',
		'R' => '100000110',
		'S' => '001000110',
		'T' => '000010110',
		'U' => '110000001',
		'V' => '011000001',
		'W' => '111000000',
		'X' => '010010001',
		'Y' => '110010000',
		'Z' => '011010000',
		' ' => '011000100',
		'$' => '010101000',
		'%' => '000101010',
		'*' => '010010100',
		'+' => '010001010',
		'-' => '010000101',
		'.' => '110000100',
		'/' => '010100010'
	);

	$text = Tools::strtoupper($text);

	$code = '*'.$text.'*';
	$cl = Tools::strlen($code);
	$cw = 6 * $thin + 3 * $thick + $thin;
	$width = 2 * $thick + $cl * $cw - $thin;

	$text = '* '.$text.' *';
	$tl = Tools::strlen($text);
	$th = $text_size ? imagefontheight($text_size) + 2 : 0;
	$tw = $text_size ? imagefontwidth($text_size) * $tl : 0;

	$img = imagecreate($width, $height + $th);
	$black = imagecolorallocate($img, 0, 0, 0);
	$white = imagecolorallocate($img, 255, 255, 255);
	imagefill($img, 0, 0, $white);

	$x = $thick;
	for ($i = 0; $i < $cl; $i++)
	{
		$char = $code[$i];
		for ($j = 0; $j < 9; $j++)
		{
			$w = $map[$char][$j] ? $thick : $thin;
			if (! ($j % 2))
				imagefilledrectangle($img, $x, 0, $x + $w - 1, $height, $black);
			$x += $w;
		}
		$x += $thin;
	}

	if ($text_size)
		imagestring($img, $text_size, ($width - $tw) / 2, $height + 1, $text, $black);

	return $img;
}
