<?php

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
	header('Access-Control-Allow-Headers: token, Content-Type');
	header('Access-Control-Max-Age: 1728000');
	header('Content-Length: 0');
	header('Content-Type: text/plain');
	die();
}

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
	
// Only Run needs to be called to do all REST calls
// Currently supported methods are GET(Select), POST(insert), PUT(update), DELETE(Delete)
function Run($config, $mysqli = null) {
	try {
		$info = Array();
		
		// get the HTTP method, path and body of the request
		$method = $_SERVER['REQUEST_METHOD'];
		$input = getParameters($method);
		$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
		$key = null;

		// connect to or reuse the mysql database
		if ($mysqli == null)
		{ 
			$link = mysqli_connect($config["database"]["server"], $config["database"]["username"], $config["database"]["password"], $config["database"]["database"]); 
			if (mysqli_connect_errno()) {
				echo "Failed to connect to MySQL: " . mysqli_connect_error();
			}
		}
		else
		{ $link = $mysqli; }
			
		mysqli_set_charset($link,'utf8');
		
		// Get URL Parameters .../[Table]/[key]
		$table = getTable($config, $request);
		if (count($request) > 1) { 	$key = $request[1]; }
		if (count($request) > 2) { 	$info["subkey"] =  $request[2]; }
		// Place values into structure that can be passed to child functions
		$info["table"] = $table;
		$info["key"] = $key;
		$info["method"] = $method;
		$info["fields"] = $input;
		
		// Called method functionality
		switch ($method) {
			case 'GET': 
				echo returnGET($config, $link, $info); 
				break;
			case 'PUT':
				echo returnPUT($config, $link, $info); 
				break;
			case 'POST': 
				if (!isset($key)) {
					echo returnPOST($config, $link, $info); 
					break;
				} else {
					echo returnPOSTSearch($config, $link, $info); 
					break;
				}
			case 'DELETE':		
				if (isset($key)) {
					echo returnDELETE($config, $link, $info); 
					break;
				} else {
					echo returnDELETEWhere($config, $link, $info); 
					break;
				}	  
		}
		
		// close mysql connection
		mysqli_close($link);
	} catch (Exception $e) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
		die($e->getMessage());
	}
}

// Result for GET method
// Includes by ID and All select
// Pagination (limit=20&offset=40)
// url: /<tablename> returns all records
// url: /<tablename>/<id> returns data for id
// url: /<tablename>/<id>/count returns count for id
function returnGET($config, $mysqli, $info) {
	if ($config[$info["table"]]["select"] == false) {
		http_response_code(401);
		die('Error: Action not allowed');
	}
	if (array_key_exists("subkey",$info) && $info["subkey"] && $info["subkey"] != "count") {
		
		$table = $info["table"];
		if (!array_key_exists("subkeys",$config[$table])) {
			header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
			die("Action not allowed, child links not available");
		}
		$subkey =  $info["subkey"];
		if (!array_key_exists($subkey,$config[$table]["subkeys"])) {
			header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
			die("Action not allowed, sub table not available");
		}
		$tablename = getSubkeyTablename($config, $info["table"],$subkey);
		$key = $info["key"];
		$fieldlist = $config[$table]["subkeys"][$subkey]["select"];
	} else {
		$table = $info["table"];
		$tablename = getTablename($config, $info["table"]);
		$key = $info["key"];
		$fieldlist = $config[$table]["select"];
	}
	// Check for Pagination  limit=20&offset=40
	$limit = "";
	if (isset($_GET["offset"])) { $limit .= $_GET["offset"]; };
	if (isset($_GET["limit"])) { $limit .= (strlen($limit)>0?',':'').$_GET["limit"]; };
	$limit = (strlen($limit)>0?'Limit '.$limit:'');
	$where = ''; $sss = ''; $param = [];

	// If ["select"] is an array then this is a standard select from a table
	if (is_array($config[$table]["select"])) {
		if ((isset($info["subkey"]) && $info["subkey"] == "count") || $info["key"] == "count" ) {
			$fields = "count(1) as count";
		} else {
			$fields = implode(', ', $fieldlist);
		}
		$where = "";
		if ($key && !isset($subkey)) {
			if (is_numeric($key)) {
				$where = " WHERE `".$config[$table]["key"]."`=?";
				$sss = 's';
				array_push($param,$key);
			}
		}
		$sql = "select $fields from `$tablename` $where $limit";
	}	
	else
	// If ["select"] is not an array then assumed to be a select statement
	{
		$where = "";
		if (strpos($config[$table]["select"],"{".$config[$table]["key"]."}") != false) {
			$sql = str_replace("{".$config[$table]["key"]."}","?",$config[$table]["select"]." $where $limit");
			$sss = "s";
			array_push($param,$info["key"]); 
		} else {
			$sql = $config[$table]["select"]." $where $limit"; 
		}
		if ($info["key"] == "count" || (isset($info["subkey"]) && $info["subkey"] == "count" )) {
			$sql = "select count(1) as count from (".$sql.") t";
		}
	}
	$rowresult = PrepareExecSQL($mysqli,$sql,$sss,$param);
	
	if (isset($config[$info["table"]]["afterselect"]) && function_exists($config[$info["table"]]["afterselect"])) {
		$rowresult = call_user_func($config[$info["table"]]["afterselect"],$rowresult);
	}	
	$res = "";
	if (!$rowresult) { // in case of empty result set
		if (array_key_exists("selectarray", $config[$table]) && ($config[$table]["selectarray"] == true)) {
			$res = [];
		} else  { 
			$res = "";
		}
		return $res;
	}
	// To allow use of views also check if there are multiple rows returned to define the array 
	if (($rowresult) && (!$info["key"] || count($rowresult) > 1 || (isset($config[$table]["selectarray"])) && ($config[$table]["selectarray"] == true))) $res .= '[';
	for ($i=0;$i<count($rowresult);$i++) {
		$res .= ($i>0?',':'').json_encode($rowresult[$i]);
	}
	if (!$info["key"] || count($rowresult) > 1 || (isset($config[$table]["selectarray"]) && ($config[$table]["selectarray"] == true))) $res .= ']';
	return $res;
}

function returnPUT($config, $mysqli, $info) {
	if ($config[$info["table"]]["update"] == false) {
		http_response_code(403);
		die('Error: Action not allowed');
	}
	if (isset($config[$info["table"]]["beforeupdate"]) && function_exists($config[$info["table"]]["beforeupdate"])) {
		$info = call_user_func($config[$info["table"]]["beforeupdate"],$info);
	}
	$table = $info["table"];
	$tablename = getTablename($config, $info["table"]);
	$key = $info["key"];

	$struct = getSetValues($config, $mysqli, $info);
	$set = $struct['set'];
	$where = "";
	if ($key) { 
		if (is_numeric($key)) {
			$where = " WHERE `".$config[$table]["key"]."`=?"; 
			$struct['sss'] .= 's'; 
			array_push($struct['params'],$key); 
		}		
	}

	$sql = "update `$tablename` set $set  $where"; 

	$result = PrepareExecSQL($mysqli,$sql,$struct['sss'], $struct['params']); 
	if (isset($config[$info["table"]]["afterupdate"]) && function_exists($config[$info["table"]]["afterupdate"])) {
		call_user_func($config[$info["table"]]["afterupdate"],$result,$info);
	}	
	http_response_code(200);
	return $result['cnt'];
}

function returnPOSTSearch($config, $mysqli, $info) {
	if ($config[$info["table"]]["create"] == false) {
		http_response_code(403);
		die('Error: Action not allowed');
	}
	$table = $info["table"];
	$tablename = getTablename($config, $info["table"]);
	$key = $info["key"];

	// User is doing a search
	$struct = getSearchValues($config, $mysqli, $info);
	$limit = "";
	
	if ((isset($info["subkey"])) && ($info["subkey"] == "count")) {
		$fields = "count(1) as count";
	} else {
		if (isset($_GET["offset"])) { $limit .= $_GET["offset"]; };
		if (isset($_GET["limit"])) { $limit .= (strlen($limit)>0?',':'').$_GET["limit"]; };
		$limit = (strlen($limit)>0?'Limit '.$limit:'');
		$fields = implode(', ', $config[$table]["select"]);
	}
	$where = "WHERE ".$struct["where"]; $sss = $struct["sss"]; $param = $struct["params"];
	$sql = "select $fields from `$tablename` $where $limit"; 
	$rowresult = PrepareExecSQL($mysqli,$sql,$sss,$param);		
	if (isset($config[$info["table"]]["afterselect"]) && function_exists($config[$info["table"]]["afterselect"])) {
		$rowresult = call_user_func($config[$info["table"]]["afterselect"],$rowresult);
	}	
	$res = '[';
	for ($i=0;$i<count($rowresult);$i++) {
		$res .= ($i>0?',':'').json_encode($rowresult[$i]);
	}
	$res .= ']';
	return $res;	
}

function returnPOST($config, $mysqli, $info) {
	if ($config[$info["table"]]["create"] == false) {
		http_response_code(403);
		die('Error: Action not allowed');
	}	
	if (isset($config[$info["table"]]["beforeinsert"]) && function_exists($config[$info["table"]]["beforeinsert"])) {
		$info = call_user_func($config[$info["table"]]["beforeinsert"],$info);
	}
	$table = $info["table"];
	$tablename = getTablename($config, $info["table"]);
	$key = $info["key"];

	$struct = getSetValues($config, $mysqli, $info);
	$set = $struct['set'];
	$sql = "insert into `$tablename` set $set"; 
	$table = $info["table"];

	$result = PrepareExecSQL($mysqli,$sql,$struct['sss'], $struct['params']); 
	if (isset($config[$info["table"]]["afterinsert"]) && function_exists($config[$info["table"]]["afterinsert"])) {
		$result = call_user_func($config[$info["table"]]["afterinsert"],$result,$info);
	}	
	//return $result['cnt'];
	http_response_code(200);
	return $result;
}

function returnDELETE($config, $mysqli, $info) {
	if ($config[$info["table"]]["delete"] == false) {
		http_response_code(403);
		die('Error: Action not allowed');
	}
	if (isset($config[$info["table"]]["beforedelete"]) && function_exists($config[$info["table"]]["beforedelete"])) {
		$info = call_user_func($config[$info["table"]]["beforedelete"],$info);
	}
	$table = $info["table"];
	$tablename = getTablename($config, $info["table"]);
	$key = $info["key"];

	$sql = "Delete from `$tablename` where id=?"; 

	$result = PrepareExecSQL($mysqli,$sql,'s',[$key]); 
	if (isset($config[$info["table"]]["afterdelete"]) && function_exists($config[$info["table"]]["afterdelete"])) {
		$result = call_user_func($config[$info["table"]]["afterdelete"],$result,$info);
	}
	return $result['cnt'];
}

function returnDELETEWhere($config, $mysqli, $info) {
	if ($config[$info["table"]]["delete"] == false) {
		http_response_code(403);
		die('Error: Action not allowed');
	}
	
	$table = $info["table"];
	$tablename = getTablename($config, $info["table"]);

	// User is doing a search
	$struct = getSearchValues($config, $mysqli, $info);
	
	$where = "WHERE ".$struct["where"]; $sss = $struct["sss"]; $param = $struct["params"];
	$sql = "DELETE from `$tablename` $where"; 
	//echo $sql;
	$result = PrepareExecSQL($mysqli,$sql,$sss,$param);	
	if (isset($config[$info["table"]]["afterdelete"]) && function_exists($config[$info["table"]]["afterdelete"])) {
		$result = call_user_func($config[$info["table"]]["afterdelete"],$result,$info);
	}
	return $result; 
}

function returnSWAGGER($config, $mysqli, $info) {
	if (isset($config[$info["table"]]["options"]) && ($config[$info["table"]]["options"] == false)) {
		http_response_code(403);
		die('Error: Action not allowed');
	}
	$table = $info["table"];
	$tablename = getTablename($config, $info["table"]);
	
	$options = "";
	include_once "gapi_document.php";
	$options = gapi_swagger($config, $mysqli, $info);
	return $options;
}

function ExecSQL($link,$sql) {
	$result = mysqli_query($link,$sql);
	 
	// die if SQL statement failed
	if (!$result) {
	  http_response_code(404);
	  die('Error: '.mysqli_error($link));
	}
	return $result;
}

// https://stackoverflow.com/questions/24363755/mysqli-bind-results-to-an-array
function db_query($dbconn, $sql, $params_types, $params) { // pack dynamic number of remaining arguments into array
	// GET QUERY TYPE
	$query_type = strtoupper(substr(trim($sql), 0, 4));
	//echo $sql;
  
	$stmt = mysqli_stmt_init($dbconn);
	if ( mysqli_stmt_prepare($stmt, $sql) ) {
		if ($params_types != "") {
		  mysqli_stmt_bind_param($stmt, $params_types, ...$params); // unpack
		}
		mysqli_stmt_execute($stmt);

		if ( 'SELE' == $query_type || '(SEL' == $query_type ) {
			$result = mysqli_stmt_result_metadata($stmt);
			list($columns, $columns_vars) = array(array(), array());
			while ( $field = mysqli_fetch_field($result) ) {
				$columns[] = $field->name;
				$columns_vars[] = &${$field->name};
			}
			call_user_func_array('mysqli_stmt_bind_result', array_merge(array($stmt), $columns_vars));
			$return_array = array();
			while ( mysqli_stmt_fetch($stmt) ) {
				$row = array();
				foreach ( $columns as $col ) {
				$row[$col] = ${$col};
				}
				$return_array[] = $row;
			}

			return $return_array;
		} // end query_type SELECT

		else if ( 'INSE' == $query_type ) {
			return mysqli_insert_id($dbconn);
		}
		return 1;
	}
}
  
function PrepareExecSQL($link, $sql, $pars = '', $params = [])   {	
	$result = db_query($link, $sql, $pars, $params);
	return $result;
}

// Get tablename from config if defined
// Allows api name to be different from tablename
function getTablename($config,$table) {
	if (isset($config[$table]["tablename"]))
	{ return $config[$table]["tablename"]; }
    return $table;
}
function getSubkeyTablename($config,$table,$subkey) {
	if (isset($config[$table]["subkeys"][$subkey]["tablename"]))
	{ return $config[$table]["subkeys"][$subkey]["tablename"]; }
    return $subkey;
}

// Default key is "id" unless defined differently in config
function getTableKey($config,$table) {
	if (isset($config[$table]["key"]))
	{ return $config[$table]["key"]; }
    return "id";
}

// Load POST/PUT parameters into common structure 
function getParameters($method) {
	// PUT variables set in php://input - note the Content-Type must be correct (x-www-form-urlencoded)
	// POST variables in $_POST
	$input = null;
	$contenttype = "application/json";
	if (isset($_SERVER["CONTENT_TYPE"])) {
		$contenttype = $_SERVER["CONTENT_TYPE"];
	} 
	// Load Parameters into generic variable
	switch ($method) {
	case 'PUT':
	case 'DELETE':
		$put_data = file_get_contents('php://input');		
		if ($contenttype == "application/json") {
			$post_vars = json_decode($put_data, true);
			$input = $post_vars;
		} else {
			parse_str($put_data, $post_vars);
		}
		$input = $post_vars; break;
    case 'POST':		
		// if application json content
		if ($contenttype == "application/json") {
			$put_data = file_get_contents('php://input');
			//parse_str($put_data, $post_vars);
			$post_vars = json_decode($put_data, true);
			$input = $post_vars;
		}
		else // if form data
		{
			$input = $_POST;
		}
		 break;
	}
	return $input;
}

// Change incoming variables into a Set statement for mysql
// Field = "Value"
// repeated.
// NOTE insert format using set values https://dev.mysql.com/doc/refman/5.6/en/insert.html
function getSetValues($config, $mysqli, $info) {
	$input = $info["fields"];
	$table = $info["table"];
	$method = $info["method"];
	$set = ''; 
	$pars = '';
	$params = [];
	
	if (isset($input)) {
		echo "<hr/>INPUT: ";
		// escape the columns and values from the input object
		$columns = preg_replace('/[^a-z0-9_]+/i','',array_keys($input));
		$values = array_map(function ($value) use ($mysqli) {
		  if ($value===null) return null;
		  return mysqli_real_escape_string($mysqli,(string)$value);
		},array_values($input));		
					
		// Decide which fieldset to use
		if (isset($config[$table]["create"])) {
			$fieldlist = $config[$table]["create"]; 
		}
		if ($method == "PUT") { $fieldlist = $config[$table]["update"];  }

		// build the SET part of the SQL command
		for ($i=0;$i<count($columns);$i++) {
			if (in_array($columns[$i],$fieldlist)) {
				$set.=(strlen($set)>0?',':'').'`'.$columns[$i].'`=?';
				$pars .= 's';
				array_push($params,$values[$i]);
			}
		}
		 
		if ($set == '') {
			//http_response_code(304);
			die("No values");
		}
	}
	return ['set' => $set, 'sss' => $pars, 'params' => $params];
}

// extract the search values from the POST Data
function getSearchValues($config, $mysqli, $info) {
	$input = $info["fields"];
	$table = $info["table"];
	$method = $info["method"];
	$where = ''; 
	$pars = '';
	$params = [];
	
	if (isset($input)) {
		// escape the columns and values from the input object
		$columns = preg_replace('/[^a-z0-9_]+/i','',array_keys($input));
		$values = array_map(function ($value) use ($mysqli) {
		  if ($value===null) return null;
		  return mysqli_real_escape_string($mysqli,(string)$value);
		},array_values($input));		
		
		// Extract each set of values into arrays
		$colarr = explode(',',$values[array_search('field', $columns)]);
		$oparr = explode(',',$values[array_search('op', $columns)]);
		$valarr = explode(',',$values[array_search('value', $columns)]);

		// Build the fields
		for ($i=0; $i<count($colarr); $i++) {
			$where.=(strlen($where)>0?' and ':'').'`'.trim($colarr[$i]).'`'.trim($oparr[$i]).'?';
			$pars .= 's';
			array_push($params,trim($valarr[$i]));
		}
		
		if ($where == '') { // If no where clause then error
			http_response_code(304);
			die();
		}
	}

	return ['where' => $where, 'sss' => $pars, 'params' => $params];
}

// Read [table] parameter from urldecode
// .../api.php/[table]/...
function getTable($config, $request) {
	// retrieve the table and key from the path
	$table = preg_replace('/[^a-z0-9_]+/i','',array_shift($request));
	// Verify table may be accessed
	if (!isset($config[$table])) {
		http_response_code(404);
		die('Table does not exist');
	}
	return $table;
}
?>