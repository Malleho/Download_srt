

<html>
<head>
<style type="text/css">
	br {
		margin: 0px 0px;
	}
	
	a.ok {
		font-family : Arial, Helvetica, sans-serif;
		font-size : 12px;
		margin-left : 20px;
		color: rgb(77, 166, 25);
	}
	
	a.end {
		font-family : Arial, Helvetica, sans-serif;
		font-size : 20px;
		font-weight:bold;
		color: rgb(77, 166, 25);
	}
	
	a.show {
		font-family : Arial, Helvetica, sans-serif;
		font-size : 12px;
		margin-left : 20px;
	}	
	
	a.error {
		font-weight:bold;
		color: red;
	}
	
	a.good {
		font-weight:bold;
		color: rgb(77, 166, 25);
	}
	
	p.log {
		margin-left : 20px;
		font-size : 10px;
		color: rgb(84, 84, 84);
	}
</style>
</head>
<body>
<font size="3" face="Arial">
Subtitle engine <br /><br />

<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
require_once("includes/addic7ed.wget.class.php");

// UNIQUE INSTANCE OF WGET
$bd = new Addic7ed_wget();

function scandirs($path) {
	$reponses = array();

    if ($handle = opendir($path)) { 
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") { # not . or ..
                if (!is_dir($path . "/" . $file)) { # is a file  (dont use is_file function due to error with file > 2GB)
                    if  (stripos($file, ".mkv") || stripos($file, ".avi")) { # is a video container
                    	array_push($reponses, $file);
                    }
                } else { # is a subdirectory
                    scandirs($path . "/" . $file);
                }
            }
        }
        closedir($handle);
	flush(); # Doesn't seem to work on the popcorn?
    }
    //ON FERME LES CONNEXIONS POUR EVITER LES ERREURS

    // ON TRAITE NOTRE LISTE DE RESULTAT
    while (count($reponses)>0) {
        $file = array_shift($reponses);
        downloadsubs($path,$file);
    }
	
	echo "</br><a class=\"end\">No more file to scan (end)</a></br>";
}

#strip the extension and check if there's a srt file
function checksubs($path, $file) {
    $srt_exist = file_exists(substr($path . "/" . $file, 0, -4) . ".srt");
	echo "-----------------------------------------------------------------------------------------------------------</br>";
    echo "Checking sub for $file</br>";
	if ($srt_exist) {
		echo "<a class=\"ok\">Srt file already exist in directory (end of process for this file)</a></br>";
		echo "-----------------------------------------------------------------------------------------------------------</br>";
	}
    return ($srt_exist);	
}

function extractshowname($file) {
	return substr($file, 0, strlen($file)-4-7); #Removing file extension (ex: .mkv) & Removing expression ' S..E..'
}

function extractseason($file) {
    preg_match("/S?(\d+)[e|x](\d+)/i", $file, $reg); #catches S??E?? and ??x??
    if ($reg) {
        return $reg[1];
    } else {
        preg_match("/(\d\d?)(\d\d)/i", preg_replace("/(20\d\d)|(19\d\d)/", "", $file), $reg2); #catches ???? but not 20?? or 19??
	if ($reg2) {
            return $reg2[1];
        } else {
            return 0;
        }
    }
}

function extractepisode($file) {
    preg_match("/S?(\d+)[e|x](\d+)/i", $file, $reg);
    if ($reg) {
        return $reg[2];
    } else {
        preg_match("/(\d\d?)(\d\d)/i", preg_replace("/(20\d\d)|(19\d\d)/", "", $file), $reg2);
	if ($reg2) {
            return $reg2[2];
        } else {
            return 0;
        }
    }    
}

function getsubsbyname($name, $season, $episode, $lang = "French", &$showid) {
	echo "<a class=\"show\"><b>Searching SRT file on Addic7ed for " . $name . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Episode: S" . $season . "E" . $episode . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Language: " . $lang ."</b></a></br>";
	
	/* FETCHING SHOW ID */
	global $bd;
	$showid = $bd->getShowIdByName($name);
	
	if ($showid) {
		/* FETCHING SHOW SUBTITLES */
		$allsubs = $bd->getAllSubsFor($showid, $season, $episode, $lang);
		if ($allsubs) {
			return $allsubs;
		} else {
			return false;
		}
	} else {
		echo "Unknown show: $name \n <br />";
	}
}

function downloadsubs($path, $file) {
	if (!checksubs($path, $file)) {
		$showid = "";
		$showname = extractshowname($file);
		$season = extractseason($file);
		$episode = extractepisode($file);
		
 	    $allsubs = getsubsbyname($showname, $season, $episode, "French", $showid);
		
		if ($allsubs) {
        	$url = selectsubversion($allsubs, $file, "French");
			
			//$bd = new Addic7ed_wget();
			global $bd;
			$referer = "/season/$showid/".intval($season);
			if ($url) $bd->downloadfile("http://www.addic7ed.com$url", $path, $file, $referer, $showname);
		} 
		else {
			echo "<a class=\"show error\">No subtitles available...</a></br>";
		}
	}
}

/* Reading the different subversion from webpage */
function selectsubversion(&$subs, $file, $lang) {
    $season = extractseason($file);
	$episode = extractepisode($file);

	$i=0;
	$bestsub=-1;
	
	/* LISTING SUB */	
	foreach ($subs as $cpt => $sub) {
		echo "<a class=\"show\">Version ".$cpt." : ".$sub->version.", Quality: ".$sub->quality.", Language: ".$sub->language.", Status: ".$sub->completion.", Link: ".$sub->link."</a></br>";
		
		if ((strcmp($sub->language, $lang)==0) and (strcmp($sub->completion, "Completed")==0)) {
			if ($bestsub == -1) $bestsub = $cpt;
			if ((strcmp($sub->quality, "HD") != 0) and ($sub->quality =  "HD")) $bestsub = $cpt;
		}
	}
	
	/*
	//echo "Test (".$subs[$compteur]->language." VS $lang) = ".strcmp($subs[$compteur]->language, $lang);

	*/

	if ($bestsub == -1) {
		echo "<a class=\"show error\">No SRT available for this episode</a></br>";
		echo "-----------------------------------------------------------------------------------------------------------</br>";
		return false;
	}
	else {
		echo "<a class=\"show good\">SRT Founded ($bestsub): " . $subs[$bestsub]->version ." ".$subs[$bestsub]->quality." (".$subs[$bestsub]->language.")</a></br>";
		return $subs[$bestsub]->link;
	}
	
}

/* Reading diffrent traduction for a same sub version */
function selectsub(&$allsubs, $file, $lang, $str_start_sub, &$subs, &$subNumber) {
	
	$cpt = $subNumber-1;
	
	//Finding end of sub version
	$str_sub_end = strpos($allsubs,"<td colspan=\"3\" class=\"newsClaro\"", $str_start_sub+1);
	if (!$str_sub_end) $str_sub_end = strlen($allsubs);

	$episodeSub = substr($allsubs,$str_start_sub,$str_sub_end-$str_start_sub);
	$str_sub_end = $str_sub_end-$str_start_sub;
	$str_start_sub = 0;
	
	//echo "</br>	".strlen($episodeSub)." vs " . strlen($allsubs). "</br>";
	//echo $episodeSub."</br></br></br></br></br></br></br></br></br>";
	
	$preg = "/(<td width=\"41%\" class=\"language\">\s?)((.|\n)*?)(<img title=\"Corrected\" src=\"http:\/\/www.addic7ed.com\/images\/bullet_go.png\" width=\"20\" height=\"20\" \/>)?(<img src=\"\/images\/icons\/invisible.gif\" width=\"20\" height=\"20\" \/>)?    <\/td>\s\s\s\s\s<td width=\"17%\">\s\s\s\s((.|\n)*?)<\/td>\s\s\s\s\s<td><img src=\"\/images\/download.png\" width=16\" height=\"16\" \/>\s<a href=\"((.|\n)*?)\"((.|\n)*?)(<\/tr>)/";
	while (preg_match ($preg, $episodeSub, $matchesSub, PREG_OFFSET_CAPTURE, $str_start_sub)) {
		if (!$matchesSub) {
			if ($cpt > $subNumber) $subNumber = $cpt;
			return;
		}
		
		if ($matchesSub) 	$str_start_sub = $matchesSub[0][1]+1;
		if ($str_start_sub >= $str_sub_end) {
			if ($cpt > $subNumber) $subNumber = $cpt;
			return;
		}
		
		//echo "Version $cpt </br>";
		//print_r($matchesSub);
		
		//echo "</br></br></br>";
		
		$cpt++;
		if ($cpt > $subNumber) {
			$subs[$cpt] = new Addic7ed_Sub();
			$subs[$cpt]->version =  $subs[$subNumber]->version;
			$subs[$cpt]->quality =  $subs[$subNumber]->quality;
		}
		$pos = strpos($matchesSub[2][0],"<");
		if (!$pos) $subs[$cpt]->language = $matchesSub[2][0];
		else $subs[$cpt]->language =  substr($matchesSub[2][0],0,$pos);
		$subs[$cpt]->link =  $matchesSub[8][0];
		$subs[$cpt]->completion =  trim($matchesSub[6][0]);
		
		//echo "founded </br>";
	}
	if ($cpt > $subNumber) $subNumber = $cpt;
}

# start the scan
scandirs("/share/Download/Expanded");

?>
</font>
</body>
</html>