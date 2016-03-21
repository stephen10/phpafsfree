<?php
//
// image.php
//
// VERSION
//   0.5, 2009-08-03
//
// DESCRIPTION
//   Generate a graphic representation of the disk utilization on
//   an AFS fileserver using PHP and GD.
//
// NOTE
//   REQUIRES PHP and GD to function
//
// AUTHOR
//   Stephen Joyce
//
// COPYRIGHT
//   Copyright (C) 2006-2016  Stephen Joyce 
//
//   This program is free software; you can redistribute it and/or modify
//   it under the terms of the GNU General Public License as published by
//   the Free Software Foundation; either version 2 of the License, or
//   (at your option) any later version.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of the GNU General Public License
//   along with this program; if not, write to the Free Software
//   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
///////////////////////////////////////////////////////////////////////////////

// You can change these to configure how the graph looks
$barwidth=32;
$separation=30;
$margin=30;
$reservepercent=0.10;
$dropshadowpx=4;
$imgHeight=850;

$vicesize=9000; // in GB

$ticlimit=$vicesize; // change this if you don't want tics all the way up
$ticincrement=100; // in GB

$scalefactor=$vicesize/($imgHeight-50)*1000000; 

// change these to false disable reporting of percent-used and reporting
// on volume totals. Querying for this information can be *SLOW*.
$showpercent=true;
$showvolinfo=false;

$server = $_SERVER['SERVER_NAME'];
$uri = $_SERVER['REQUEST_URI'];
$fileself = $_SERVER['PHP_SELF'];
$extension = ".php";
$splitter = explode("/",$uri);
$size_of = sizeof($splitter);
$file = $splitter[$size_of - 1];
$directory = $splitter[$size_of - 2];
$file = str_replace($extension,"", $file);

# Try standard locations looking for vos. Adjust as needed
if (file_exists("/usr/bin/vos")) {
  $vos = "/usr/bin/vos";
} else if (file_exists("/usr/sbin/vos")) {
  $vos = "/usr/sbin/vos";
} else if (file_exists("/usr/afsws/etc/vos")) {
  $vos = "/usr/afsws/etc/vos";
} else if (file_exists("/usr/local/bin/vos")) {
  $vos = "/usr/local/bin/vos";
} else if (file_exists("/usr/local/sbin/vos")) {
  $vos = "/usr/local/sbin/vos";
} else {
  echo ("Could not find vos");
  exit;
}

if (!$fh = popen ("$vos partinfo $file -noauth", "r")) {
	echo ("Could not fork vos");
}

$a=0;
while (!feof($fh)) {
	$line = fgets ($fh, 1024);
	$line = preg_replace('/\/vicep/', '', $line);
		$a++;
	if (preg_match('/Free/',$line)) {
		$words = explode(" ",$line);
		$parts[$a]=$words[4];
		$parts[$a]=str_replace(":","",$parts[$a]);
		$free[$a]=$words[5];
		$total[$a]=$words[11];
		//echo ("$parts[$a], $free[$a], $total[$a]<br><br>");
	}
}
pclose($fh);
if (empty($parts)) {
  #no parts found
  $imgWidth = 200;
  header ("Content-type: image/png");

 $image=imagecreate($imgWidth, $imgHeight);
  $colorGraphBackground=imagecolorallocate($image,0xd3,0xd3,0xd3); // Grey
  #$colorBackColor=imagecolorallocate($image,255,255,255); // White
  $colorTextColor=imagecolorallocate($image,255,051,000); // Dark Blue
 #imageline($image, 0 ,10,$imgWidth-4,10,10);

 imagestring($image,5,0,0, "$file", $colorTextColor);
 imagestring($image,2,0,10+15*1, "No partitions found", $colorTextColor);

 imagepng($image);
 imagedestroy($image);
 exit;
}

$words="";
$busyflag=$offlineflag="false";

if ($showvolinfo == "true" ) {
  for ($a=1; $a<=sizeof($parts); $a++) {
    if (!$fh = popen ("$vos listvol $file $parts[$a] -noauth", "r")) {
      echo ("Could not fork vos");
    }
    while (!feof($fh)) {
      $line = fgets ($fh, 1024);
      $line = trim($line, "\x00..\x1F");
      if (strpos($line,"Total volumes") === false ) {
        //echo ("skipping $line<br>");
        //echo ("strpos was " . strpos($line,"Total volumes") . "<br>");
      } else {
        //echo ("found line $line<br>");
        $words = explode(" ",$line);
        $online[$a]=$words[3];
        $offline[$a]=$words[8];
        $busy[$a]=$words[12];
// uncomment for testing colors
// $busy[1]=5;
// $offline[1]=5;
        if ($busy[$a] != 0 ) {
          $busyflag="true";
        }
        if ($offline[$a] != 0 ) {
          $offlineflag="true";
        }
      }
    }
    pclose($fh);
  }
}

$imgWidth=(sizeof($parts))*$barwidth + (sizeof($parts)+1)*$separation+$dropshadowpx+$margin;
if ($imgWidth < 200 ) { $imgWidth = 200; }

header ("Content-type: image/png");

$image=imagecreate($imgWidth, $imgHeight);

// Graph colors
if ($showvolinfo == "true" ) {
  if ($offlineflag=="true") {
    // offline background -- ugly red
    $colorGraphBackground=imagecolorallocate($image,255,050,100);
  } else if ($busyflag == "true" ) {
    // busy background -- ugly brownish yellow
    $colorGraphBackground=imagecolorallocate($image,205,180,5);
  } else {
    $colorGraphBackground=imagecolorallocate($image,0xd3,0xd3,0xd3); // Grey
  }
} else {
  $colorGraphBackground=imagecolorallocate($image,0xd3,0xd3,0xd3); // Grey
}

$colorDropShadow=imagecolorallocate($image,100,100,100); // Dark Grey
$colorScale=imagecolorallocate($image,100,100,100); // Grey2

$colorBarBackground=imagecolorallocate($image,204,255,255); // Pale Blue

$colorBarUsed=imagecolorallocate($image,000,051,255); // Bright Blue
$colorTextColor=imagecolorallocate($image,054,057,128); // Dark Blue

$colorReserve=imagecolorallocate($image,255,255,102);
$colorReserve=imagecolorallocate($image,255,153,000); // Orange
$colorReserve=imagecolorallocate($image,0xff,0xe3,0x03); // Yellow

$colorAngryReserve=imagecolorallocate($image,255,051,000); // Red

$colorWarning=imagecolorallocate($image,255,051,204);
$colorOverlap=imagecolorallocate($image,153,000,102);
$colorOverlap=imagecolorallocate($image,102,000,102); // Magenta

$colorBusy=imagecolorallocate($image,0xff,0xff,0xff); // White
$colorOffline=imagecolorallocate($image,0xff,0xff,0xff); // White

// tic marks. Comment this loop to remove
$bottom = $imgHeight - $margin;
for ($tic=0;$tic<=$ticlimit;$tic=$tic+$ticincrement) {
  $ticpx=$bottom - ($tic*1000000/$scalefactor);
  imageline($image, 0 ,$ticpx,$imgWidth-$margin-4,$ticpx,$colorScale);
  imagestring($image,2,$imgWidth-$margin, $ticpx-6, "$tic G", $colorTextColor);
}

for ($a=1; $a<=sizeof($parts); $a++) {
	$totalsize=$total[$a];
	$usedsize=$totalsize-$free[$a];

	$right=($barwidth+$separation)*$a;
	$left=$right-$barwidth;
	$barheight=($totalsize/$scalefactor);
	$usedheight=($usedsize/$scalefactor);
	$bottom = $imgHeight - $margin;

	// entire amount
	imagefilledrectangle($image, $left, $bottom-$barheight, $right, $bottom, $colorBarBackground);

	// reserve amount
	$reserve=$barheight*$reservepercent;
	imagefilledrectangle($image, $left, $bottom-$barheight, $right, $bottom-$barheight+$reserve, $colorReserve);

	// used amount
	$level = $bottom - ($totalsize/$scalefactor)*($usedsize/$totalsize);
	if (($usedsize/$totalsize) < 1 - $reservepercent) {
		imagefilledrectangle($image, $left, $level, $right, $bottom, $colorBarUsed);
	} else {
		imagefilledrectangle($image, $left, $bottom-$barheight, $right, $bottom-$barheight+$reserve, $colorAngryReserve);
		//imagefilledrectangle($image, $left, $level, $right, $bottom, $colorWarning);
		imagefilledrectangle($image, $left, $level, $right, $bottom, $colorBarUsed);
		imagefilledrectangle($image, $left, $level, $right, $bottom-$barheight+$reserve, $colorOverlap);
	}

	// print the percent used.
	if ($showpercent == 'true' ) {
	if ($bottom - $level > 15 ) {
	  // Put it inside the bar as long as it's at least 15 px tall (for gd font size 2).
	  imagestring($image,2,$left+2,$level, round($usedsize/$totalsize*100,1) ."%", $colorReserve);
	} else {
	  // Put it outside any bar that's under 15 px. tall.
	  imagestring($image,2,$left+2,$level-15, round($usedsize/$totalsize*100,1) ."%", $colorTextColor);
	}
	}

	// drop shadow
	if ($dropshadowpx <> 0 ) {
		imagefilledrectangle($image, $right+1, $bottom-$barheight+$dropshadowpx, $right+$dropshadowpx, $bottom+$dropshadowpx, $colorDropShadow);
		imagefilledrectangle($image, $left+$dropshadowpx, $bottom+1, $right+$dropshadowpx, $bottom+$dropshadowpx, $colorDropShadow);
	}
	//
	imagestring($image,2,$left+$barwidth/2, $bottom+10, "$parts[$a]", $colorTextColor);
}

if ($showvolinfo == "true" ) {
  $vollines=1; // index counter for where to type on the graph
  for ($a=1; $a<=sizeof($parts); $a++) {
    if (($offline[$a] == 0 ) && ($busy[$a] == 0 )) {
      imagestring($image,2,0,10+15*$vollines, "/vicep$parts[$a]: Vols Online: $online[$a]", $colorTextColor);
      $vollines++;
    } else if ($offline[$a] == 0) {
      imagestring($image,2,0,10+15*$vollines, "/vicep$parts[$a]: Vols Online: $online[$a]", $colorTextColor);
      $vollines++;
        imagestring($image,2,0,10+15*$vollines, "                Busy: $busy[$a]",$colorBusy);
        $vollines++;
    } else {
      if ($busy[$a] == 0 ) {
        imagestring($image,2,0,10+15*$vollines, "/vicep$parts[$a]: Vols Online: $online[$a]", $colorTextColor);
	$vollines++;
	imagestring($image,2,0,10+15*$vollines, "             Offline: $offline[$a]", $colorOffline);
	$vollines++;
      } else {
        //both
        imagestring($image,2,0,10+15*$vollines, "/vicep$parts[$a]: Vols Online: $online[$a]",$colorTextColor);
	$vollines++;
	imagestring($image,2,0,10+15*$vollines, "                Busy: $busy[$a]",$colorBusy);
	$vollines++;
	imagestring($image,2,0,10+15*$vollines, "             Offline: $offline[$a]", $colorOffline);
	$vollines++;
      }
    }
  }
}

imagestring($image,5,0,0, "$file", $colorTextColor);

imagepng($image);
imagedestroy($image);
?>
