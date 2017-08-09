<?php

	class lineage {
		
		//array that stores data making up the family tree
		private $tree = array();

		private $cousin_prefixes = array(1 => "first", "second", "third", "fourth", "fifth", "sixth", "seventh", "eighth", "ninth");
		private $cousin_suffixes = array("", "once removed", "twice removed", "thrice removed", "4 times removed", "5 times removed", "6 times removed", "7 times removed", "8 times removed", "9 times removed");

		function __construct($input, $type = 'array') {
			switch($type) {
				case 'array':
					$this->tree = $input;
					break;
				case 'xml':
					$this->parse_xml($input);
					break;
				case 'csv':
					$this->parse_csv($input);
					break;
				case 'json':
					$this->parse_json($input);
					break;
				default:
					echo "error: type does not exist";
					break;
			}
			return TRUE;
		}

		/* Parsing Functions */

		private function parse_xml($xml) {
			$parser = new SimpleXMLElement($xml);
			$i = 1;
			//parse each element of XML into tree
			foreach($parser->mem as $mem) {
				$this->tree[(int)$mem['id']] = array("id"=>(int) $mem['id'], "parent"=>(int) $mem['parent'], "gender"=>(int) $mem['gender'], "name"=>(string) $mem);
				$i++;
			}
			return TRUE;
		}

		private function parse_csv($csv) {
			$this->tree = array();
			//seperate lines in file
		    $data = str_getcsv($csv, "\n");
		    //assumes CSV file has a header row, and unsets it from the tree
			unset($data[0]);

		    //parses each line in CSV into the tree
			foreach($data as &$row) {
				$row = str_getcsv($row, ",");
				$this->tree[$row[0]] = array("id" => $row[0], "parent" => $row[1], "gender" => $row[2], "name" => $row[3]);
			} 

			return TRUE;
		}

		private function parse_json($json) {
			$data = json_decode($json, TRUE);

			//for every item, set id as array key
			foreach ($data as $row) {
				$this->tree[$row["id"]] = $row;
			}

			return TRUE;
		}

		private function gen($a) {
			$gen = 1;
			while($a != 1) {
				$a = $this->tree[$a]["parent"];
				$gen++;
			}
			return $gen;
		}

		private function gendif($a, $b) {
			$dif = $this->gen($a) - $this->gen($b);
			return abs($dif);
		}

		private function gen_prefix($g, $grand = FALSE) {
			if($g == 1) {
				return "";
			}
			if($g == 2) {
				return ($grand == FALSE) ? "grand" : "great ";
			}
			if($g > 2) {
				$return = ($grand == FALSE) ? "grand" : "great ";
				for($i = 1; $i <= $g - 2; $i++) {
					$return = "great ".$return;
				}
				return $return;
			}
		}

		private function gen_anc($a, $gen) {
			if($this->gen($a) <= $gen) {
				return $a;
			} 
			while($this->gen($a) != $gen) {
				$a = $this->tree[$a]["parent"];
			}
			return $a;
		}

		private function common($a, $b) {
			if($a == $b) {
				return 0;
			}

			$ga = $this->gen($a);
			$gb = $this->gen($b);

			while($a != $b) {
				if($ga < $gb) {
					$b = $this->tree[$b]["parent"];
					$gb = $gb - 1;
				} elseif($ga > $gb) {
					$a = $this->tree[$a]["parent"];
					$ga = $ga - 1;
				} elseif($ga == $gb) {
					$a = $this->tree[$a]["parent"];
					$b = $this->tree[$b]["parent"];
				}

				if($a == 1 or $b == 1) {
					return 1;
				}
			}
			return $a;
		}

		public function findRelation($a, $b) {
			if($this->tree[$a]["parent"] == $this->tree[$b]["parent"]) {
				return ($a != $b ? "sibling" : "self");
			}
			if($a == $this->common($a, $b)) {
				return $this->gen_prefix($this->gendif($a, $b))."parent";
			}
			if($b == $this->common($a, $b)) {
				return $this->gen_prefix($this->gendif($a, $b))."child";
			}
			if($this->tree[$a]["parent"] == $this->tree[$this->gen_anc($b, $this->gen($a))]["parent"]) {
				return $this->gen_prefix($this->gendif($a, $b))."aunt/uncle";
			}
			if($this->tree[$b]["parent"] == $this->tree[$this->gen_anc($a, $this->gen($b))]["parent"]) {
				return $this->gen_prefix($this->gendif($a, $b))."niece/nephew";
			}
			if(2 <= $this->gen($a) - $this->gen($this->common($a, $b))) {
				if($this->gen($a) <= $this->gen($b)) {
					$deg = ($this->gen($a) - $this->gen($this->common($a, $b))) - 1;
				} else {
					$deg = ($this->gen($b) - $this->gen($this->common($a, $b))) - 1;
				}
			}
			return $this->cousin_prefixes[$deg]." cousin ".$this->cousin_suffixes[$this->gendif($a, $b)];
		}
	}

?>