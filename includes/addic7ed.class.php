<?php
///////////////////////////////////////////
// bierdopje.com API wrapper for PHP v 1 //
// license: GNU GPL 3                    //
// created by Thyone & Pimmetje          //
///////////////////////////////////////////

class Addic7ed_Sub {
	public $version;
	public $quality;
	public $specificity;  //Unused
	public $completion;
	public $language;
	public $link;
	//DEPRECATED public $start;
}

class Addic7ed {
	protected $api_url = "http://www.addic7ed.com"; //if URL ever changes, update from Google Code or change it here
	protected $login = "login";
	protected $password = "password"; 
	private $timeout = 10;
	protected $debug = false;


	protected function data_to_xml($data){
		return new SimpleXMLElement($data);
	}


	/*
	* Function build to extract & Read a specific show page (
	* example Show page : http://www.addic7ed.com/show/1075)
	* exampel Season show page : http://www.addic7ed.com/season/1075/1
	*/
	public function getAllSubsFor($id, $season, $episode, $lang = "nl"){

		$html = $this->fetch_data("season", $id."/".intval($season), false);
		
		// Extracting HTML Code with Show ID
		
		// PREG FOR ADDIC7ED BEFORE 08/2012	
		// $preg = "#<a href='/serie/(\\w+)[A-Z0-9._%-]*/" . intval($season) . "/" . intval($episode) . "/(\\w+)#";	
		//echo "---------------------------</br></br></br>";
		//print_r($matches);
		// DEBUG MODE
		//if ($this->debug) Echo "<br> REGEX (Show) - Expression : ". htmlentities($preg) . "";
		// CALCULATING START, END OF EPISODE TABLE
		//$str_start = strpos($html, $matches[0][0]);
		//$str_end = strpos($html, "/table", $str_start);
		//if ($this->debug) Echo "<br> REGEX (Show) - Result : " . $matches[0][0] . " Table start: " .$str_start . ", Table end: " . $str_end ."</br>";
		//$matches = substr($html, $str_start, $str_end-$str_start);
		//if ($this->debug) Echo "<br> REGEX (Show) - Extract : " . htmlentities($matches) . "</br>";
		

		// PREG FOR ADDIC7ED AFTER 08/2012
		$preg = "#<tr class=\"epeven completed\"><td>" . intval($season) . "</td><td>" . intval($episode) . "</td><td>((.|\n)*?)</td><td>((.|\n)*?)</td><td class=\"c\">((.|\n)*?)</td><td class=\"c\">((.|\n)*?)</td><td class=\"c\">((.|\n)*?)</td><td class=\"c\">((.|\n)*?)</td><td class=\"c\">((.|\n)*?)</td><td class=\"c\"><a href=\"((.|\n)*?)\">Download</a></td>#";
		preg_match_all ($preg, $html, $matches);
		
		// DEBUG
		if ($this->debug) print_r($matches);

		$item;
		$value;
		
		//CLASSEMENT DES RESULTATS
		$subs = array();
		$compteur = 0;

		foreach ($matches[3] as $item => $value) {
			$compteur++;

			$subs[$compteur] = new Addic7ed_Sub();
			$subs[$compteur]->version =  $matches[5][$item];
			$subs[$compteur]->quality =  $matches[1][0];
			
			if (strlen($matches[9][$item])>0) $subs[$compteur]->specificity =  true;   //Hearing impaired
			else $subs[$compteur]->specificity =  false;   //Hearing impaired
			
			$subs[$compteur]->completion =  $matches[7][$item];
			$subs[$compteur]->language =  $value;
			$subs[$compteur]->link =  $matches[15][$item];

			if (strlen($matches[13][$item])>0) $subs[$compteur]->quality =  "HD";
			else $subs[$compteur]->quality =  "";
	
		}

	
		//if ($this->debug) Echo "<br> REGEX (Show) - Extract : " . htmlentities($matches) . "</br>";
		
		return $subs;
	}

	
	
	/*
	 * Function build to extract Show ID from the page http://www.addic7ed.com/shows.php
	 */
	public function getShowIdByName($showname){
	
		// Fetching Web Page
		$html = $this->fetch_data("shows.php", "", false);
		
		// Extracting HTML Code with Show ID
		// ESCAPEMENT CHAR BEFORE BRACKET
		$preg = "/option value=\"(\\d+)\" >(" . str_replace(array("(",")"),array("\(","\)"),$showname) . ")/i";
		preg_match_all ($preg, $html, $matches);
		
		// DEBUG MODE
		if ($this->debug) Echo "<br> REGEX (ShowID) - Expression : ". htmlentities($preg) . "";
		if ($this->debug) Echo "<br> REGEX (ShowID) - Result : </br>";
		if ($this->debug) print_r($matches);
		if ($this->debug) Echo "<br> REGEX (ShowID) - Filtered ID : " . $matches[1][0] . "</br>";
		
		if ($matches) {
			return $matches[1][0];
		}
		else {
			return false;
		}
	}

   
	// CONSTRUCTEUR
	function Addic7ed() { 
		// Fetching Web Page
		$html = $this->fetch_data("login.php", "", false);
		//if ($this->debug) Echo "INPUT LOGIN PAGE RESPONSE : ". $html;

		$html = $this->fetch_data("dologin.php", "", false, "username=$this->login&password=$this->password&remember=true&Submit=Log+in");
		//if ($this->debug) Echo "LOGIN RESPONSE : ". $html;

		$preg = "#<a class=\"button white\" href=\"/logout.php\">Logout</a>#";
		if (preg_match($preg, $html)) echo "<a class=\"show good\">LOGON SUCCEED</a></br>";
		else echo "<a class=\"show error\">LOGON FAILED</a></br>";
	}
}

?>
