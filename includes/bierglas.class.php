<?php
///////////////////////////////////////////
// bierdopje.com API wrapper for PHP v 1 //
// license: GNU GPL 3                    //
// created by Thyone & Pimmetje          //
///////////////////////////////////////////

class Bierglas {
	protected $api_url = "http://api.bierdopje.com/"; //if URL ever changes, update from Google Code or change it here
	protected $api_key = "4B11A785B6465ADA"; //enter your API-key here
	private $timeout = 10;

	protected function fetch_data($command, $data, $as_xml = true){
		$url = $this->api_url.$this->api_key."/".$command."/".$data;
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
		$data = curl_exec($ch);
		curl_close($ch);
		if($as_xml){
			return $this->data_to_xml($data);
		} else {
			return $data;
		}
	}
	protected function is_valid_response($data){
		if($data->response->status == "false" or $data == ""){ //too bad that it's not a REAL false which is in the response ;)
			return false;
		} else {
			return true;
		}
	}
	protected function data_to_xml($data){
		return new SimpleXMLElement($data);
	}
	public function getShowById($id){
		if(assert(is_numeric($id))){
			$xml = $this->fetch_data("getshowbyid", $id);
			$tmp = new stdClass();
			if($this->is_valid_response($xml)){
				return $xml->response;
			} else {
				return false;
			}
		}
	}
	public function getEpisodeById($id){
		if(assert(is_numeric($id))){
			$xml = $this->fetch_data("getepisodebyid", $id);
			$tmp = new stdClass();
			if($this->is_valid_response($xml)){
				return $xml->response;
			} else {
				return false;
			}
		}
	}
	public function getShowByTVDBId($id){
		if(assert(is_numeric($id))){
			$xml = $this->fetch_data("getshowbytvdbid", $id);
			$tmp = new stdClass();
			if($this->is_valid_response($xml)){
				return $xml->response;
			} else {
				return false;
			}
		}
	}
	public function findShowByName($name, $limit = 9999){
		$xml = $this->fetch_data("findshowbyname", $name);
		if($this->is_valid_response($xml)){
			$tmp = new stdClass();
			$i = 0;
			if($xml->response->result){
				foreach($xml->response->result as $res){
					$tmp->$i = $res;
					$i++;
					if($i == $limit){
						return $tmp;
					}
				}
			} elseif($xml->response->results){
				foreach($xml->response->results->genre as $res){
					$tmp->$i = $res;
					$i++;
					if($i == $limit){
						return $tmp;
					}
				}
			}
			return $tmp;
		} else {
			return false;
		}
	}
	public function getAllEpisodesForShow($id, $limit = 9999){
		if(assert(is_numeric($id))){
			$xml = $this->fetch_data("getallepisodesforshow", $id);
			$tmp = new stdClass();
			$i = 0;
			if($xml->response->result){
				foreach($xml->response->result as $res){
					$tmp->$i = $res;
					$i++;
					if($i == $limit){
						return $tmp;
					}
				}
			} elseif($xml->response->results){
				foreach($xml->response->results->result as $res){
					$tmp->$i = $res;
					$i++;
					if($i == $limit){
						return $tmp;
					}
				}
			}
			return $tmp;
		}
	}
	public function getAllSubsForEpisode($id, $lang = "nl"){
		if(assert(is_numeric($id))){
			$xml = $this->fetch_data("getallsubsforepisode", $id."/".strtolower($lang));
			if($this->is_valid_response($xml)){
				$tmp = new stdClass();
				$i = 0;
				if($xml->response->result){
					foreach($xml->response->result as $res){
						$tmp->$i = $res;
						$i++;
					}
				} elseif($xml->response->results){
					foreach($xml->response->results->result as $res){
						$tmp->$i = $res;
						$i++;
					}
				}
				return $tmp;
			} else {

			}
		}
	}
	public function getAllSubsFor($id, $season, $episode, $lang = "nl"){
		//if(assert(is_numeric($id))){
			$xml = $this->fetch_data("getallsubsfor", $id."/".$season."/".$episode."/".strtolower($lang));
			if($this->is_valid_response($xml)){
				$tmp = new stdClass();
				$i = 0;
				if($xml->response->result){
					foreach($xml->response->result as $res){
						$tmp->$i = $res;
						$i++;
					}
				} elseif($xml->response->results){
					foreach($xml->response->results->result as $res){
						$tmp->$i = $res;
						$i++;
					}
				}
				if ($i > 0) {
				return $tmp; } else { return false;}
			} else {
return false;
			}
		//}
	}
	public function getShowByName($showname){
		$xml = $this->fetch_data("getshowbyname", urlencode($showname));
		if($this->is_valid_response($xml)){
			return($xml->response);
		} else {
			return false;
		}
	}
}

?>
