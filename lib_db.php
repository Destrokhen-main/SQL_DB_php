<?php

/*
register_shutdown_function(function(){
	if (error_get_last()) {
		var_export(error_get_last());
	}
	});
*/

function get_col($col){
	$c = "";
	if($col != "*") {
		$p = explode(",",$col);
		if(count($p) == 1) {
			for($i = 0;$i != count($p);$i++)
				if($i == 0)
					$c .= $p[$i];
				else
					$c .= ", `".$p[$i]."`";
		} else {
			$c = $col;
		}
	} else {
		$c = "*";
	}
	return $c;
}

function message_user($number){
	switch($number){
		case 963 :
			// empty list data base 
			echo "<!>ERROR<!> You need to populate the array with the database";
			break;
		case 962 :
			echo "<!>ERROR<!> data base not found";
			break;
		case 900:
			echo "<!>WARNING<!> Can't found row";
			break;
		case 899:
			echo "<!>WARNING<!> One or more records could not be deleted";
			break;
		case 898:
			echo "<!>ERROR<!> One of the identifiers cannot be edited";
		case 551:
			echo "<!>ERROR<!> Insert parametr uncorrect";
			break;
		case 552:
			echo "<!>ERROR<!> can't insert row";
			break;
		case 404:
			echo "<!>ERROR<!> request error";
			break;
	}
}

function get_id($name){
	global $ardb;
	global $debag;
	
	if (count($ardb) == 1){
		return 0;
	} else if(count($ardb) > 1) {
		for($i = 0;$i != count($ardb);$i++){
			if($name == $ardb[$i]['name'])
				return $i;
		}
		return -1;
	} else {
		if($debag)
			message_user(963);
	}
}

function get_param_e ($array_id) {
	$s = "";
	
	for($i = 0;$i != count($array_id);$i++){
		if($i == 0)
			$s = $array_id[$i]['key']." = ".$array_id[$i]['value'];
		else
			$s = ", ".$array_id[$i]['key']." = ".$array_id[$i]['value'];
	}
}

// coneecnt all bd in array
function connect_all() {
	global $ardb;
	global $debag;
	
	if (count($ardb) > 1) { 
		for($i = 0; $i != count($ardb);$i++){
			$data_base_v = new mysqli($ardb[$i]['settings']['server'],
				$ardb[$i]['settings']['username'],
				$ardb[$i]['settings']['password'],
				$ardb[$i]['settings']['database'],
				$ardb[$i]['settings']['port']);
		
		    // Проверяем подключение
		    if ($data_base_v->connect_error) {
		        die("Connect error: " . $data_base_v->connect_error);
		    }
			
		    mysqli_query($data_base_v,"SET NAMES utf8");
		    $ardb[$i]['value_db'] = $data_base_v;
		} 
	} else if (count($ardb) == 1) {
		$data_base_v = new mysqli($ardb[0]['settings']['server'],
			$ardb[0]['settings']['username'],
			$ardb[0]['settings']['password'],
			$ardb[0]['settings']['database'],
			$ardb[0]['settings']['port']);
	
	    // Проверяем подключение
	    if ($data_base_v->connect_error) {
	        die("Connect error: " . $data_base_v->connect_error);
	    }
		
	    mysqli_query($data_base_v,"SET NAMES utf8");
	    $ardb[0]['value_db'] = $data_base_v;
	} else {
		if($debag)
			message_user(963);
	}
}

// connect db for name
function connect_one($name) {
	global $ardb;
	global $debag;
	
	$id = get_id($name);
	if($id != -1) {
		$data_base_v = new mysqli($ardb[$id]['settings']['server'],
			$ardb[$id]['settings']['username'],
			$ardb[$id]['settings']['password'],
			$ardb[$id]['settings']['database'],
			$ardb[$id]['settings']['port']);
	
	    // Проверяем подключение
	    if ($data_base_v->connect_error) {
	        die("Connect error: " . $data_base_v->connect_error);
	    }
		
	    mysqli_query($data_base_v,"SET NAMES utf8");
	    $ardb[$id]['value_db'] = $data_base_v;
	} else {
		if($debag)
			message_user(962);
	}
}

function get_sort($sort){
	$p_sort = "";
	if($sort != false){
		$str = explode(":",$sort);
		switch(mb_strtolower(trim($str[0]))){
			case "up":
					$p_sort = " ORDER BY ".trim($str[1])." DESC";
				break;
			case "down":
					$p_sort = " ORDER BY ".trim($str[1]);
				break;
			case "group":
					$p_sort = " GROUP BY ".trim($str[1]);
				break;
		}
	}
	return $p_sort;
}

//delete one id
//$name,$table,$id
function dor($a) {
	global $ardb;
	global $debag;

	$name 	= isset($a['n']) ? $a['n'] : "";
	$table	= $a['t'];
	$id_ 	= $a['i'];

	$id = get_id($name);
	
	if($id != -1){
		if(mysqli_query($ardb[$id]['value_db'], "DELETE FROM `.".$table.".` WHERE id = '".$id_."'")) {
			return true;
		} else {
			if($debag)
				message_user(900);
			return false;
		}
	} else {
		if($debag)
			message_user(962);
		return false;
	}
}

// delete more id
// $name,$table,$array_id
function dmr($a) {
	global $ardb;
	global $debag;

	$name 		= isset($a['n']) ? $a['n'] : "";
	$table		= $a['t'];
	$array_id	= $a['a'];

	$id = get_id($name);
	
	if($id != -1) {
		$ch = true;
		for($i = 0;$i != count($array_id);$i++){
			if(!mysqli_query($ardb[$id]['value_db'],"DELETE FROM `".$table."` WHERE id = '".$array_id[$i]."'")){
				$ch = false;
			}
		}
		
		if($ch == false) {
			if($debag)
				message_user(899);
			return false;
		} else {
			return true;
		}
	} else {
		if($debag)
			message_user(962);
		return false;
	}
}

// edit --------
// edit one request
// $name,$table,$array_id,$param
function eor($a){
	global $ardb;
	global $debag;
	
	$name 		= isset($a['n']) ? $a['n'] : "";
	$table		= $a['t'];
	$array_id	= $a['e'];
	$param		= $a['p'];

	$id = get_id($name);
	
	if($id != -1){
		$edit = get_param_e($array_id);
		
		$c = false; 
		
		if(strlen($param) > 0)
			$c = true;
		else
			$param = " WHERE ".$param;
		
		if(!mysqli_query($ardb[$id]['value_db'],"UPDATE `".$table."` SET ".$edit." ".$param)){
			if($debag)
				message_user(898);
			return false;
		} else {
			return true;
		}
	} else {
		if($debag)
			message_user(962);
		return false;
	}
}

// edit more request
// $name,$table,$id_request
function emr($a) {
	global $ardb;
	global $debag;
	
	$name 		= isset($a['n']) ? $a['n'] : "";
	$table 		= $a['t'];
	$id_request	= $a['ae'];

	$id = get_id($name);
	if($id != -1){
		$k = false;
		for($i = 0;$i != count($id_request);$i++){
			$edit	= get_param_e($id_request[$i]['ep']);
			
			$param	= $id_request[$i]['pe']; 
			
			if(strlen($param) > 0)
				$param = " WHERE ".$param;
			
			if(!mysqli_query($ardb[$id]['value_db'], "UPDATE `".$table."` SET ".$edit." ".$param)){
				if($debag)
					message_user(898);
				$k = true;
			}
		}
		
		if(!$k)
			return true;
		else
			return false;
		
	} else {
		if($debag)
			message_user(962);
	}
}
// -------------


//get----
// get one request
function gor($a){
	global $ardb;
	global $debag;

	$name 		= isset($a['n']) ? $a['n'] : "";
	$colom		= isset($a['c']) ? $a['c'] : "*";
	$param		= $a['p'];
	$table		= $a['t'];
	
	$id = get_id($name);
	$c = ($colom != "*") ? get_col($colom) : "*";

	if($id != -1) {
		$request = mysqli_query($ardb[$id]['value_db'],"SELECT ".$c." FROM `".$table."` WHERE ".$param);
		if(mysqli_num_rows($request) == 0) {
			if($debag)
				message_user(900);
			return false;
		} else {
			return mysqli_fetch_array($request);
		}
	} else {
		if($debag)
			message_user(962);
		return false;
	}
}

// get more request
// $name,$col,$table,$param,$sort
function gmr($a){
	global $ardb;
	global $debag;

	$name 	= isset($a['n']) ? $a['n'] : "";
	$colom 	= isset($a['c']) ? $a['c'] : "*";
	$table	= $a['t'];
	$param	= isset($a['p']) ? $a['p'] : "";
	$sort	= isset($a['s']) ? $a['s'] : false;

	$id = get_id($name);
	
	$c = get_col($colom);
	
	$p_sort = get_sort($sort);

	if($id != -1) {
		
		if (strlen($param) > 0)
			$param = " WHERE ".$param;
		
		$request = mysqli_query($ardb[$id]['value_db'], "SELECT ".$c." FROM `".$table."` ".$param.$p_sort);
		
		if(count($request) > 0) {
			$a = [];
			
			while ($r = $request -> fetch_array()){
				array_push($a,$r);
			}
			return $a;
		} else {
			if($debag)
				message_user(900);
			return false;
		}
		
	} else {
		if($debag)
			message_user(962);
		return false;
	}
}

//-------
// name:ivan,age:17
function parse_insert_data($str) {
	$out = [];
	$out['input'] = "";
	$out['value'] = "";

	$path_str = explode('@,',$str);
	
	for($i = 0;$i != count($path_str);$i++){
		$z = explode("@=",$path_str[$i]);
		if(count($z) == 2){
			if($i == 0)
				$out['input'] .= "`".trim($z[0])."`";
			else
				$out['input'] .= ", `".trim($z[0])."`";

			if($i == 0)
				$out['value'] .= "`".trim($z[1])."`";
			else
				$out['value'] .= ",`".trim($z[1])."`";
		} else {
			return false;
		}
	}
	return $out;
}

function aor($a){
	global $ardb;
	global $debag;

	$name 			= isset($a['n']) ? $a['n'] : "";
	$table 			= $a['t'];
	$ret			= isset($a['r']) ? true : false;
	$ar = parse_insert_data($a['p']);

	if($ar != false) {
		$id = get_id($name);
		if($id != -1) {
			$request = mysqli_query($ardb[$id]['value_db'],"INSERT INTO `".$table."` (".$ar['input'].") VALUES (".$ar['value'].")");

			if($request) {
				if($ret == true) {
					return ["status"=>true,"id"=>mysqli_insert_id($ardb[$id]['value_db'])];
				}
				return true;
			} else {
				if($debag) {
					message_user(552);
				}
				if($ret == true){
					return ["status"=>false];
				}
				return false;
			}
		} else {
			if($debag) {
				message_user(962);
			}
			return false;
		}
	} else {
		if($debag) {
			message_user(551);
		}
		return false;
	}
}

function amr($a){
	global $ardb;
	global $debag;

	$name	= isset($a['n']) ? $a['n'] : "";
	$table	= $a['t'];
	$input 	= $a['in'];
	$value 	= $a['v'];
	$ret	= isset($a['r']) ? true : false;

	if(count($value[0]) == count($input)){
		$id = get_id($name);
		if($id != -1) {
			$total = [];
			$total['status'] 	= true;
			$total['id']		= [];
			$z = 0;
			for($i = 0;$i != count($value);$i++){
				$request = mysqli_query($ardb[$id]['value_db'],"INSERT INTO `".$table."` (".$input.") VALUES (".$value[$i].")");
				if($request) {
					if($ret == true)
						array_push($total['id'],mysqli_insert_id($ardb[$id]['value_db']));
				} else {
					if($debag) {
						message_user(552);
					}
					$total['status'] = false;
					if($ret == true)
						return $total;
					else
						return false;
				}
			}
			if($ret == true)
				return $total;
			else
				return true;
			
		}
	} else {
		if($debag) {
			message_user(551);
		}
		return false;
	}

}
//settings

/*
insert into $ardb
[
				"name"		=> "udb",
				"value_db"	=> null,
				"settings"	=> [
									"server"	=> "localhost",
									"username"	=> "",
									"password"	=> "",
									"database"	=> "",
									"port"		=> 3306,
								]
]
*/

function sql($o) {
	global $ardb;
	global $debag;

	$name 	= isset($o['n']) ? $o['n'] : "";
	$sql 	= $o['sq'];

	$id = get_id($name);

	if($id != -1) {
		$request = mysqli_query($ardb[$id]['value_db'],$sql);
		if($request){
			return $request;
		} else {
			if($debag)
				message_user(404);
			return false;
		}
	} else {
		if($debag) {
			message_user(551);
		}
		return false;
	}
}


$debag = true;

$ardb = [
		];
//--------

?>