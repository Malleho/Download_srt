<?php
///////////////////////////////////////////
// bierdopje.com API wrapper for PHP v 1 //
// license: GNU GPL 3                    //
///////////////////////////////////////////
require_once("bierglas.class.php");

class Bierglas_wget extends Bierglas {
	private $wgetlog = "/tmp/wgetlog";
	private $wgettempdir = "/tmp/";
	protected function fetch_data($command, $data, $as_xml = true){
		$url = $this->api_url.$this->api_key.'/'.$command.'/'.$data;
		//Use unix wget to download the pages store it in /tmp/dl.html
		$file = $this->wgettempdir.microtime(true).".html";
		$run = "wget -o$this->wgetlog -O$file $url";
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
}

?>