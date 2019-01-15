<?php
function Run($config, $mysqli = null)
{
	$info = Array();
	// get the HTTP method, path and body of the request
	$method = $_SERVER['REQUEST_METHOD'];
	$input = getParameters($method);
	$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
	$key = null;

	// connect to or reuse the mysql database
	if ($mysqli == null)
	{ $link = mysqli_connect('localhost', 'root', '', 'eskomemp'); }
	else
	{ $link = $mysqli; }
	 	
	mysqli_set_charset($link,'utf8');
	 
	$table = getTable($config, $request);
	if (count($request) > 1) { 	$key = $request[1]; }
	$info["table"] = $table;
	$info["key"] = $key;
	$info["method"] = $method;
	$info["input"] = $input;

	
	 
	// create SQL based on HTTP method
	switch ($method) {
	  case 'GET':
		echo returnGET($config, $link, $info); break;
	  case 'PUT':
		echo returnPUT($config, $link, $info); break;
	  case 'POST':
	    echo returnPOST($config, $link, $info); break;
	  case 'DELETE':
		echo returnDELETE($config, $link, $info); break;
	}
	 
	// close mysql connection
	mysqli_close($link);
}

function ExecSQL($link,$sql)
{
	$result = mysqli_query($link,$sql);
	 
	// die if SQL statement failed
	if (!$result) {
	  http_response_code(404);
	  die('Error: '.mysqli_error($link));
	}
	return $result;
}

function getTablename($config,$table)
{
	if (isset($config[$table]["tablename"]))
	{ return $config[$table]["tablename"]; }
    return $table;
}
function getTableKey($config,$table)
{
	if (isset($config[$table]["key"]))
	{ return $config[$table]["key"]; }
    return "id";
}

function returnGET($config, $mysqli, $info)
{
	if ($config[$info["table"]]["select"] == false)
	{
		http_response_code(401);
		die('Error: Action not allowed');
	}
	//echo "returnGet";
	$table = $info["table"];
	$tablename = getTablename($config, $info["table"]);
	$key = $info["key"];
	// Check for Pagination  limit=20&offset=40
	$limit = "";
	//var_dump($_GET);
	if (isset($_GET["offset"])) { $limit .= $_GET["offset"]; };
	if (isset($_GET["limit"])) { $limit .= (strlen($limit)>0?',':'').$_GET["limit"]; };
	$limit = (strlen($limit)>0?'Limit '.$limit:'');
	
	$fields = implode(', ', $config[$table]["select"]);
	$sql = "select $fields from `".$tablename."`".($key?" WHERE ".$config[$table]["key"]."=$key ":" $limit"); 
	//echo $sql;
	$result = ExecSQL($mysqli,$sql);
	$res = "";
	if (!$info["key"]) $res .= '[';
	  for ($i=0;$i<mysqli_num_rows($result);$i++) {
		$res .= ($i>0?',':'').json_encode(mysqli_fetch_object($result));
	  }
	  if (!$info["key"]) $res .= ']';
	return $res;	
}

function returnPUT($config, $mysqli, $info)
{
	if ($config[$info["table"]]["update"] == false)
	{
		http_response_code(403);
		die('Error: Action not allowed');
	}
	$table = $info["table"];
	$tablename = getTablename($config, $info["table"]);
	$key = $info["key"];

	$set = getSetValues($config, $mysqli, $info);
	$sql = "update `$tablename` set $set where id=$key"; 

	$result = ExecSQL($mysqli,$sql); 
	return mysqli_affected_rows($mysqli);
}

function returnPOST($config, $mysqli, $info)
{
	if ($config[$info["table"]]["create"] == false)
	{
		http_response_code(403);
		die('Error: Action not allowed');
	}
	$table = $info["table"];
	$tablename = getTablename($config, $info["table"]);
	$key = $info["key"];

	$set = getSetValues($config, $mysqli, $info);
	$sql = "insert into `$table` set $set"; 

	$result = ExecSQL($mysqli,$sql); 
	return mysqli_insert_id($mysqli);
}

function returnDELETE($config, $mysqli, $info)
{
	if ($config[$info["table"]]["delete"] == false)
	{
		http_response_code(403);
		die('Error: Action not allowed');
	}
	$table = $info["table"];
	$tablename = getTablename($config, $info["table"]);
	$key = $info["key"];

	$set = getSetValues($config, $mysqli, $info);
	$sql = "Delete from `$tablename` where id=$key"; 

	$result = ExecSQL($mysqli,$sql); 
	return mysqli_affected_rows($mysqli);
}

 
function getParameters($method)
{
	// PUT variables set in php://input - note the Content-Type must be correct (x-www-form-urlencoded)
	// POST variables in $_POST
	$input = null;
	// Load Parameters into generic variable
	switch ($method) {
	case 'PUT':
	    $put_data = file_get_contents('php://input');
		parse_str($put_data, $post_vars);
		$input = $post_vars; break;
    case 'POST':
		$input = $_POST; break;
	}
	return $input;
}

function getSetValues($config, $mysqli, $info)
{
	$input = $info["input"];
	$table = $info["table"];
	$method = $info["method"];
	$set = '';
	
	if (isset($input))
	{
		// escape the columns and values from the input object
		$columns = preg_replace('/[^a-z0-9_]+/i','',array_keys($input));
		$values = array_map(function ($value) use ($mysqli) {
		  if ($value===null) return null;
		  return mysqli_real_escape_string($mysqli,(string)$value);
		},array_values($input));		
					
		// Decide which fieldset to use
		$fieldlist = $config[$table]["create"]; 
		if ($method == "PUT") { $fieldlist = $config[$table]["update"];  }

		// build the SET part of the SQL command
		for ($i=0;$i<count($columns);$i++) {
			if (in_array($columns[$i],$fieldlist))
			{
				$set.=(strlen($set)>0?',':'').'`'.$columns[$i].'`=';
				$set.=($values[$i]===null?'NULL':'"'.$values[$i].'"');
			}
		}
		
		if ($set == '')
		{
			http_response_code(304);
			die();
		}
	}

	return $set;
}

function getTable($config, $request)
{
	// retrieve the table and key from the path
	$table = preg_replace('/[^a-z0-9_]+/i','',array_shift($request));
	// Verify table may be accessed
	if (!isset($config[$table]))
	{
		http_response_code(404);
		die('Table does not exist');
	}
	return $table;
}
?>