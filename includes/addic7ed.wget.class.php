<?php
///////////////////////////////////////////
// bierdopje.com API wrapper for PHP v 1 //
// license: GNU GPL 3                    //
///////////////////////////////////////////
require_once("addic7ed.class.php"); 

class Addic7ed_wget extends Addic7ed {

	private $wgetlog = "/share/Apps/AppInit/tmp/wgetlog";        //USEFULL FOR DEBUG
	private $wgetcookie = "/share/Apps/AppInit/tmp/cookie.txt";  //REQUIRED FOR THIS WEBSITE (COOKING SESSION REQUIRED)
	private $wgettempdir = "/share/Apps/AppInit/tmp/";           //OLD STUFF FROM BIERGLAS KEEP IT TO RUN
	private $showpath = "/share/Video/Series/";           //OLD STUFF FROM BIERGLAS KEEP IT TO RUN
	
	/*
	* Function used to fetching site pages
	*/
	protected function fetch_data($command, $data, $as_xml = true, $post = null){
	
		if (strlen($data)>0) {
			$url = $this->api_url.'/'.$command.'/'.$data;
		}
		Else {
			$url = $this->api_url.'/'.$command;
		}
		if (strlen($post)>0) $post = "--post-data='$post'";    //POST COMMAND FOR WGET

		// DEBUG MODE
		//if ($this->debug) echo "<br> Fetch Data : " . $url . "</br>";
		
		//Use unix wget to download the pages store it in /tmp/dl.html

		$file = $this->wgettempdir.microtime(true).".html";
		$run = "wget $post --cookies=on --save-cookies $this->wgetcookie --keep-session-cookies -o$this->wgetlog -O$file $url";

		if ($this->debug) echo "WGET: $run</br>";
		system($run);
		$data = file_get_contents($file);
		unlink($file);

		//If there is a error return nothing
		if($data === false) {return;}
		if($as_xml){
			return $this->data_to_xml($data);
		} else {
			return $data;
		}
	}
	
	/*
	* Similar to Fetch_Data but specific to download SRT File
	* NB: With addic7ed cookie session is required to be able to download srt
	*/
	public function downloadfile($url, $path, $filename, $referer, $showname) {
		//Name of SRT FILE
		$outputfile = substr($filename,0,strlen($filename)-4).".srt";
		
		//Tricking server with show webpage on referer
		$referer = $this->api_url . $referer;
		
		echo "</br><a class=\"show\"><b>Downloading \"$outputfile\" from $url</b></a></br>";
				
		//               reuse of cookie session                                                                    referer url         debugging          outuput            url to download from         
		$cmd = "wget -c --load-cookies $this->wgetcookie --save-cookies $this->wgetcookie --keep-session-cookies --referer=$referer -o$this->wgetlog -O \"$path/$outputfile\" $url";
		system($cmd);

		$wgetlog = file_get_contents($this->wgetlog);

		if (stripos($wgetlog, "text/srt") == false) {
			echo "<a class=\"show error\">Error while downloading srt (file will be deleted)</a>";
			unlink($this->wgetlog);
		}
		
		echo "<p class=\"log\">".str_replace(array("\r", "\r\n", "\n"),'</br>',$wgetlog)."</p>";
		echo "</br><a class=\"show\"><b>Moving Episode & SRT file to the final location \"" . $this->showpath . $showname ."\"</b></a></br>";

		// MOVING FILES
		if (!rename("$path/$filename", $this->showpath . $showname ."/" . $filename)) echo "Unable to move $path/$filename to ".$this->showpath . $showname ."/" . $filename.".";
		if (!rename("$path/$outputfile", $this->showpath . $showname ."/" . $outputfile)) echo "Unable to move $path/$outputfile to ".$this->showpath . $showname ."/" . $outputfile.".";
		
		echo "-----------------------------------------------------------------------------------------------------------</br>";
    
	}
}

?>