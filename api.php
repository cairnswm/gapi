<?php
// Based on https://www.leaseweb.com/labs/2015/10/creating-a-simple-rest-api-in-php/

$config = Array(
  "messages" => Array(
                        "select" => Array("chatid","username","message","createddate"),
						"update" => Array("message"),
						"delete" => false,
						"create" => Array("chatid","username","message")
                     ),
  "user" => Array(
                        "select" => Array("id","username","fullname"),
						"update" => Array("fullname")
                     )
);

function Run($config, $mysqli = null)
{
	// get the HTTP method, path and body of the request
	$method = $_SERVER['REQUEST_METHOD'];
	$input = getParameters($method);
	$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
	//var_dump($input);
	//var_dump($request);
	
	// connect to or reuse the mysql database
	if ($mysqli == null)
	{ 	$link = mysqli_connect('localhost', 'root', '', 'eskomemp'); }
	else
	{ $link = $mysqli; }
	 	
	mysqli_set_charset($link,'utf8');
	 
	$table = getTable($config, $request);
	$key = array_shift($request)+0;

	//var_dump($request);
	//echo $table;
	//echo $key;
	 
	if (isset($input))
	{
		// escape the columns and values from the input object
		$columns = preg_replace('/[^a-z0-9_]+/i','',array_keys($input));
		$values = array_map(function ($value) use ($link) {
		  if ($value===null) return null;
		  return mysqli_real_escape_string($link,(string)$value);
		},array_values($input));
		 
		// build the SET part of the SQL command
		$set = '';
		for ($i=0;$i<count($columns);$i++) {
			// Decide which fieldset to use
			$fieldlist = $config[$table]["create"]; 
			if ($method == "PUT") { $fieldlist = $config[$table]["update"]; }
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
	 
	// create SQL based on HTTP method
	switch ($method) {
	  case 'GET':
		$fields = implode(', ', $config[$table]["select"]);
		$sql = "select $fields from `$table`".($key?" WHERE id=$key":''); break;
	  case 'PUT':
		$sql = "update `$table` set $set where id=$key"; break;
	  case 'POST':
		$sql = "insert into `$table` set $set"; break;
	  case 'DELETE':
		$sql = "delete `$table` where id=$key"; break;
	}
	 
	// excecute SQL statement
	//echo $sql;
	$result = mysqli_query($link,$sql);
	 
	// die if SQL statement failed
	if (!$result) {
	  http_response_code(404);
	  die('Error: '.mysqli_error());
	}
	 
	// print results, insert id or affected row count
	if ($method == 'GET') {
	  if (!$key) echo '[';
	  for ($i=0;$i<mysqli_num_rows($result);$i++) {
		echo ($i>0?',':'').json_encode(mysqli_fetch_object($result));
	  }
	  if (!$key) echo ']';
	} elseif ($method == 'POST') {
	  echo mysqli_insert_id($link);
	} else {
	  echo mysqli_affected_rows($link);
	}
	 
	// close mysql connection
	mysqli_close($link);
}
 
function getParameters($method)
{
	// PUT variables set in php://input - not the Content-Type must be correct
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

Run($config);
