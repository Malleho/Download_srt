#!/mnt/syb8634/server/php5-cgi

<html>
<body bgcolor="#000040">
<font size="5" face="Georgia, Arial" color="#aaaaaa">
Subtitle engine <br /><br />

<?php
ini_set('display_errors',1);

error_reporting(E_ALL);

require_once("includes/bierglas.wget.class.php"); 

function scandirs($path) {
    if ($handle = opendir($path)) { 
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") { # not . or ..
                if (is_file($path . "/" . $file)) { # is a file
                    if  (stripos($file, ".mkv") || stripos($file, ".avi")) { # is a video container
                    	downloadsubs($path, $file);
                    }
                } else { # is a subdirectory
                    scandirs($path . "/" . $file);
                }
            }
        }
        closedir($handle);
	flush(); # Doesn't seem to work on the popcorn?
    }
}

#strip the extension and check if there's a srt file
function checksubs($path, $file) {
    return (file_exists(substr($path . "/" . $file, 0, -4) . ".srt"));
}

function extractshowname($path) {
    $epath = explode( "/", $path);
	return preg_replace("/ - season \d+$/i", "", $epath[3]); #replace " - season ??" with ""
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

function getsubsbyname($name, $season, $episode, $lang = "nl") {
	$bd = new Bierglas_wget();
	$showid = $bd->getShowByName($name)->showid;
	if ($showid) {
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
		echo "<b>Searching subtitles for: " . $file . "</b>\n <br />";
 	    $allsubs = getsubsbyname(extractshowname($path), extractseason($file),extractepisode($file));
       		
		if ($allsubs) {
        		$bestsub = selectsub($allsubs, $file);
			$url = $bestsub['downloadlink'];
			#echo  $url . "\n <br />";
			if (downloadfile($url, $path, $file)) {
				echo "Downloaded " . $bestsub['filename'] . "\n <br /><br />";
			} else {
				echo "Error downloading subtitles for $file \n <br />";
			}
			
		/*} else { #search for english subs
			$allsubs = getsubsbyname(extractshowname($path), extractseason($file),extractepisode($file), "en");
			
			if ($allsubs) {
        			$bestsub = selectsub($allsubs, $file);
				echo $bestsub['downloadlink'] . "\n <br />";
			} else {
				echo "No subtitles available...\n <br /><br />";
			} */
		} else {
			echo "No subtitles available... \n <br /><br />";
		}
	}
}
function downloadfile($url, $path, $filename) {
	$run = "wget -o/share/tmp/wgetlog.log -O/share/tmp/test.srt $url";
	system($run, $output);
	
	return copy("/share/tmp/test.srt", "$path/" . substr($filename,0,-4) . ".srt");
	
}

function selectsub($allsubs, $file) {
	$i=0;
	$bestsub=0;
	$bestscore=0;
        foreach ($allsubs as $sub) {
		if (stripos((string) $sub->filename, substr($file,0,-4))!==false) {
			if (strpos((string) $sub->filename, "V2")) {
				if (strpos((string) $sub->filename, "V3")) {
					$bestsub= $i;
					break;
				} else {
					if ($bestscore < 2) {
						$bestscore = 2;
						$bestsub=$i;
					}
				}
			} else {
				if ($bestscore < 1) {
					$bestscore = 1;
					$bestsub=$i;
				}
			}
		}					
		$i++;
	}
	$blaa = get_object_vars($allsubs);
	$blaa = get_object_vars($blaa[$bestsub]);
	return $blaa;
	
}

# start the scan
scandirs("/share/Tv");

?>
</font>
</body>
</html>