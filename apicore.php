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

const UNPROCESSABLEENTITY = ' 422 Unprocessable Entity';
const ACTIONNOTALLOWED = "Error: Action not allowed";

// Only Run needs to be called to do all REST calls
// Currently supported methods are GET(Select), POST(insert), PUT(update), DELETE(Delete)
function Run($config, $mysqli = null)
{
	try {
		$info = array();

		// get the HTTP method, path and body of the request
		$method = $_SERVER['REQUEST_METHOD'];
		$input = getParameters($method);
		$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));
		$key = null;

		// connect to or reuse the mysql database
		if ($mysqli == null) {
			$link = mysqli_connect($config["database"]["server"], $config["database"]["username"], $config["database"]["password"], $config["database"]["database"]);
			if (mysqli_connect_errno()) {
				echo "Failed to connect to MySQL: " . mysqli_connect_error();
			}
		} else {
			$link = $mysqli;
		}

		mysqli_set_charset($link, 'utf8');

		// Get URL Parameters .../[Table]/[key]
		$table = getTable($config, $request);
		if (count($request) > 1) {
			$key = $request[1];
		}
		if (count($request) > 2) {
			$info["subkey"] = $request[2];
		}
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
			default:
				die("Method not supported");
		}

		// close mysql connection
		mysqli_close($link);
	} catch (Exception $e) {
		header($_SERVER['SERVER_PROTOCOL'] . UNPROCESSABLEENTITY, true, 422);
		die($e->getMessage());
	}
}

$defaultwhere = "";
$defaultsss = "";
$defaultparams = [];
$defaultorder = "";

// Result for GET method
// Includes by ID and All select
// Pagination (limit=20&offset=40)
// url: /<tablename> returns all records
// url: /<tablename>/<id> returns data for id
// url: /<tablename>/<id>/count returns count for id
function returnGET($config, $mysqli, $info)
{
	global $defaultwhere, $defaultsss, $defaultparams, $defaultorder;

	if (isset($config[$info["table"]]["order"])) {
		$defaultorder = $config[$info["table"]]["order"];
	}

	if ($config[$info["table"]]["select"] == false) {
		http_response_code(401);
		die(ACTIONNOTALLOWED);
	}
	if (array_key_exists("subkey", $info) && $info["subkey"] && $info["subkey"] != "count") {

		$table = $info["table"];
		if (!array_key_exists("subkeys", $config[$table])) {
			header($_SERVER['SERVER_PROTOCOL'] . UNPROCESSABLEENTITY, true, 422);
			die("Action not allowed, child links not available");
		}
		$subkey = $info["key"];
		var_dump($info);
		echo "Subkey:", $subkey;
		var_dump($config[$table]["subkeys"]);
		if (!array_key_exists($subkey, $config[$table]["subkeys"])) {
			header($_SERVER['SERVER_PROTOCOL'] . UNPROCESSABLEENTITY, true, 422);
			die("Action not allowed, sub table not available");
		}
		$tablename = getSubkeyTablename($config, $info["table"], $subkey);
		$tablekey = getSubkeyKey($config, $info["table"], $subkey);
		$tconfig = $config[$table]["subkeys"][$subkey];
		$key = $info["key"];
		$fieldlist = $config[$table]["subkeys"][$subkey]["select"];
	} else {
		$table = $info["table"];
		$tablename = getTablename($config, $info["table"]);
		$tablekey = getKey($config, $info["table"]);
		$key = $info["key"];
		$tconfig = $config[$table];
		$fieldlist = $config[$table]["select"];
	}
	// Check for Pagination  limit=20&offset=40
	$limit = "";
	if (isset($_GET["offset"])) {
		$limit .= $_GET["offset"];
	}
	if (isset($_GET["limit"])) {
		$limit .= (strlen($limit) > 0 ? ',' : '') . $_GET["limit"];
	};
	$limit = (strlen($limit) > 0 ? 'Limit ' . $limit : '');

	if (isset($config[$info["table"]]["beforeselect"]) && function_exists($config[$info["table"]]["beforeselect"])) {
		$info = call_user_func($config[$info["table"]]["beforeselect"], $tconfig, $info);
	}
	if (isset($info["where"])) {
		$defaultwhere = $info["where"];
		$defaultsss = $info["wheresss"];
		$defaultparams = $info['whereparams'];
	}
	if (isset($info["order"])) {
		$defaultorder = $info['order'];
	}
	$where = $defaultwhere;
	$sss = $defaultsss;
	$param = $defaultparams;
	$order = $defaultorder;

	if (is_array($tconfig["select"])) {
		// If ["select"] is an array then this is a standard select from a table
		// use {<key>"} to replace with key value
		if ((isset($info["subkey"]) && $info["subkey"] == "count") || $info["key"] == "count") {
			$fields = "count(1) as count";
		} else {
			$fields = implode(', ', $fieldlist);
		}
		if ($key) {
			if (strlen(($where) > 0)) {
				$where .= " and ";
			}
			$where .= "`" . $tablekey . "`=?";
			$sss .= 's';
			array_push($param, $key);
		}
		if (strlen($where) > 0) {
			$where = "WHERE " . $where;
		}

		if (strlen($order) > 0) {
			$order = "ORDER BY " . $order;
		}
		$sql = "select $fields from `$tablename` $where $limit $order";
	} else {
		// If ["select"] is not an array then assumed to be a select statement
		if (strpos($config[$table]["select"], "{" . $config[$table]["key"] . "}") != false) {
			$sql = str_replace("{" . $config[$table]["key"] . "}", "?", $config[$table]["select"] . " $where $limit");
			$sss = "s";
			array_push($param, $info["key"]);
		} else {
			$sql = $config[$table]["select"] . " $where $limit";
		}
		if ($info["key"] == "count" || (isset($info["subkey"]) && $info["subkey"] == "count")) {
			$sql = "select count(1) as count from (" . $sql . ") t";
		}
	}

	// echo $sql,"==========================";
	// echo $defaultorder,"==========================";
	// echo json_encode($param);
	// echo "==========================";

	$rowresult = PrepareExecSQL($sql, $sss, $param);

	if ($rowresult == null) {
		http_response_code(200);
		die("[]");
	}

	if (!isset($key)) {
		if (!is_array($rowresult)) {
			$rowresult = array($rowresult);
		}
	}

	if (isset($config[$table]["afterselect"]) && function_exists($config[$table]["afterselect"])) {
		$rowresult = call_user_func($config[$info["table"]]["afterselect"], $rowresult);
	}

	$res = json_encode($rowresult);
	return $res;
}

function returnPUT($config, $mysqli, $info)
{
	if ($config[$info["table"]]["update"] == false) {
		http_response_code(403);
		die(ACTIONNOTALLOWED);
	}
	if (isset($config[$info["table"]]["beforeupdate"]) && function_exists($config[$info["table"]]["beforeupdate"])) {
		$info = call_user_func($config[$info["table"]]["beforeupdate"], $info);
	}
	$tablename = getTablename($config, $info["table"]);
	$key = $info["key"];
	if (!isset($key)) {
		http_response_code(200);
		die('{"error":"No key"}');
	}

	$struct = getSetValues($config, $mysqli, $info);
	$where = "";
	$wheresss = "";
	$paramwhere = [];

	$set = "";
	foreach ($struct['set'] as $value) {
		if (strlen($set) > 0) {
			$set .= ",";
		}
		$set .= " `" . $value . "`=?";
	}

	if (isset($info["where"])) {
		$where = $info["where"];
		$wheresss = $info["wheresss"];
		$paramwhere = $info['whereparams'];
	} else {
		$where = "";
	}
	if ($key) {
		if (strlen(($where) > 0)) {
			$where .= " and ";
		}
		$where .= " `" . $config[$info["table"]]["key"] . "`=?";
		$wheresss .= 's';
		array_push($paramwhere, $key);
	}

	$sql = "update `$tablename` set $set WHERE $where";
	$param = array_merge($struct["params"], $paramwhere);
	$sss = $struct["sss"] . $wheresss;

	$result = PrepareExecSQL($sql, $sss, $param);
	if (isset($config[$info["table"]]["afterupdate"]) && function_exists($config[$info["table"]]["afterupdate"])) {
		call_user_func($config[$info["table"]]["afterupdate"], $result, $info);
	}
	http_response_code(200);
	return $result;
}

function returnPOSTSearch($config, $mysqli, $info)
{
	if (!isset($config[$info["table"]]["create"]) || $config[$info["table"]]["create"] == false) {
		http_response_code(403);
		die(ACTIONNOTALLOWED);
	}
	$table = $info["table"];
	$tablename = getTablename($config, $info["table"]);

	// User is doing a search
	$struct = getSearchValues($config, $mysqli, $info);
	$limit = "";

	if ((isset($info["subkey"])) && ($info["subkey"] == "count")) {
		$fields = "count(1) as count";
	} else {
		if (isset($_GET["offset"])) {
			$limit .= $_GET["offset"];
		}
		if (isset($_GET["limit"])) {
			$limit .= (strlen($limit) > 0 ? ',' : '') . $_GET["limit"];
		}
		$limit = (strlen($limit) > 0 ? 'Limit ' . $limit : '');
		$fields = implode(', ', $config[$table]["select"]);
	}
	$where = "WHERE " . $struct["where"];
	$sss = $struct["sss"];
	$param = $struct["params"];
	$sql = "select $fields from `$tablename` $where $limit";
	$rowresult = PrepareExecSQL($mysqli, $sql, $sss, $param);
	if (isset($config[$info["table"]]["afterselect"]) && function_exists($config[$info["table"]]["afterselect"])) {
		$rowresult = call_user_func($config[$info["table"]]["afterselect"], $rowresult);
	}
	$res = '[';
	for ($i = 0; $i < count($rowresult); $i++) {
		$res .= ($i > 0 ? ',' : '') . json_encode($rowresult[$i]);
	}
	$res .= ']';
	return $res;
}

function returnPOST($config, $mysqli, $info)
{
	if ($config[$info["table"]]["create"] == false) {
		http_response_code(403);
		die(ACTIONNOTALLOWED);
	}
	if (isset($config[$info["table"]]["beforeinsert"]) && function_exists($config[$info["table"]]["beforeinsert"])) {
		$info = call_user_func($config[$info["table"]]["beforeinsert"], $info);
		// echo json_encode($info);
	}
	$table = $info["table"];
	$tablename = getTablename($config, $info["table"]);
	$key = $info["key"];

	$struct = getSetValues($config, $mysqli, $info);
	$set = "";
	foreach ($struct['set'] as $value) {
		if (strlen($set) > 0) {
			$set .= ",";
		}
		$set .= " `" . $value . "`=?";
	}

	$sql = "insert into `$tablename` set $set";
	$table = $info["table"];

	// echo $sql."\n";

	$result = PrepareExecSQL($sql, $struct['sss'], $struct['params']);
	if (isset($config[$info["table"]]["afterinsert"]) && function_exists($config[$info["table"]]["afterinsert"])) {
		try {
			$result = call_user_func($config[$info["table"]]["afterinsert"], $result, $info);
		} catch (Exception $e) {
			die($e->getMessage());
		}
	}
	http_response_code(200);
	return $result;
}

function returnDELETE($config, $mysqli, $info)
{
	if ($config[$info["table"]]["delete"] == false) {
		http_response_code(403);
		die(ACTIONNOTALLOWED);
	}
	if (isset($config[$info["table"]]["beforedelete"]) && function_exists($config[$info["table"]]["beforedelete"])) {
		$info = call_user_func($config[$info["table"]]["beforedelete"], $info);
	}
	$tablename = getTablename($config, $info["table"]);
	$key = $info["key"];

	if (isset($info["where"])) {
		$where = $info["where"];
		$sss = $info["wheresss"];
		$param = $info['whereparams'];
	} else {
		$where = "";
		$sss = "";
		$param = [];
	}
	if ($key) {
		if (strlen(($where) > 0)) {
			$where .= " and ";
		}
		$where .= " `" . $config[$info["table"]]["key"] . "`=?";
		$sss .= 's';
		array_push($param, $key);
	}

	$sql = "delete from `$tablename` WHERE $where";

	$result = PrepareExecSQL($mysqli, $sql, $sss, $param);
	if (isset($config[$info["table"]]["afterupdate"]) && function_exists($config[$info["table"]]["afterupdate"])) {
		call_user_func($config[$info["table"]]["afterupdate"], $result, $info);
	}
	http_response_code(200);
	return $result;
}

function returnDELETEWhere($config, $mysqli, $info)
{
	if ($config[$info["table"]]["delete"] == false) {
		http_response_code(403);
		die(ACTIONNOTALLOWED);
	}

	$tablename = getTablename($config, $info["table"]);

	// User is doing a search
	$struct = getSearchValues($config, $mysqli, $info);

	$where = "WHERE " . $struct["where"];
	$sss = $struct["sss"];
	$param = $struct["params"];
	$sql = "DELETE from `$tablename` $where";
	//echo $sql;
	$result = PrepareExecSQL($mysqli, $sql, $sss, $param);
	if (isset($config[$info["table"]]["afterdelete"]) && function_exists($config[$info["table"]]["afterdelete"])) {
		$result = call_user_func($config[$info["table"]]["afterdelete"], $result, $info);
	}
	return $result;
}

function ExecSQL($link, $sql)
{
	$result = mysqli_query($link, $sql);

	// die if SQL statement failed
	if (!$result) {
		http_response_code(404);
		die('Error: ' . mysqli_error($link));
	}
	return $result;
}

// Get tablename from config if defined
// Allows api name to be different from tablename
function getTablename($config, $table)
{
	if (isset($config[$table]["tablename"])) {
		return $config[$table]["tablename"];
	}
	return $table;
}
function getKey($config, $table)
{
	if (isset($config[$table]["key"])) {
		return $config[$table]["key"];
	}
	return $table;
}
function getSubkeyTablename($config, $table, $subkey)
{
	if (isset($config[$table]["subkeys"][$subkey]["tablename"])) {
		return $config[$table]["subkeys"][$subkey]["tablename"];
	}
	return $subkey;
}
function getSubkeyKey($config, $table, $subkey)
{
	if (isset($config[$table]["subkeys"][$subkey]["key"])) {
		return $config[$table]["subkeys"][$subkey]["key"];
	}
	return $subkey;
}

// Default key is "id" unless defined differently in config
function getTableKey($config, $table)
{
	if (isset($config[$table]["key"])) {
		return $config[$table]["key"];
	}
	return "id";
}

// Load POST/PUT parameters into common structure
function getParameters($method)
{
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
				$postvars = json_decode($put_data, true);
			} else {
				parse_str($put_data, $postvars);
			}
			$input = $postvars;
			break;
		case 'POST':
			// if application json content
			if ($contenttype == "application/json") {
				$put_data = file_get_contents('php://input');
				//parse_str($put_data, $postvars);
				$postvars = json_decode($put_data, true);
				$input = $postvars;
			} else // if form data
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
function getSetValues($config, $mysqli, $info)
{
	$input = $info["fields"];
	$table = $info["table"];
	$method = $info["method"];
	if (isset($info["set"])) {
		$set = $info["set"];
		$pars = $info["sss"];
		$params = $info["params"];
	} else {
		$set = [];
		$pars = '';
		$params = [];
	}

	if (isset($input)) {
		// escape the columns and values from the input object
		$columns = preg_replace('/[^a-z0-9_]+/i', '', array_keys($input));
		$values = array_map(function ($value) use ($mysqli) {
			if ($value === null)
				return null;
			return mysqli_real_escape_string($mysqli, (string) $value);
		}, array_values($input));

		// Decide which fieldset to use
		if (isset($config[$table]["create"])) {
			$fieldlist = $config[$table]["create"];
		}
		if ($method == "PUT") {
			$fieldlist = $config[$table]["update"];
		}

		// build the SET part of the SQL command
		for ($i = 0; $i < count($columns); $i++) {
			if (in_array($columns[$i], $fieldlist)) {
				array_push($set, $columns[$i]);
				$pars .= 's';
				array_push($params, $values[$i]);
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
function getSearchValues($config, $mysqli, $info)
{
	$input = $info["fields"];
	$where = '';
	$pars = '';
	$params = [];

	if (!array_key_exists("field", $input)) {
		$input["field"] = [];
	}
	if (!array_key_exists("op", $input)) {
		$input["op"] = [];
	}
	if (!array_key_exists("value", $input)) {
		$input["value"] = [];
	}

	if (isset($input)) {

		// Extract each set of values into arrays
		$colarr = $input["field"];
		$oparr = $input["op"];
		$valarr = $input["value"];

		// Build the fields
		for ($i = 0; $i < count($colarr); $i++) {
			if (is_array($valarr[$i])) {
				$whereps = "";
				for ($j = 0; $j < count($valarr[$i]); $j++) {
					$whereps .= (strlen($whereps) > 0 ? ' , ' : '') . '?';
					$pars .= 's';
					array_push($params, $valarr[$i][$j]);
				}
				$where .= (strlen($where) > 0 ? ' and ' : '') . '`' . trim($colarr[$i]) . '`' . trim($oparr[$i]) . ' (' . $whereps . ')';
			} else {
				$where .= (strlen($where) > 0 ? ' and ' : '') . '`' . trim($colarr[$i]) . '`' . trim($oparr[$i]) . '?';
				$pars .= 's';
				array_push($params, $valarr[$i]);
			}
		}

		if ($where == '') { // If no where clause then error
			http_response_code(304);
			die("no search fields");
		}
	}

	return ['where' => $where, 'sss' => $pars, 'params' => $params];
}

// Read [table] parameter from urldecode
// .../api.php/[table]/...
function getTable($config, $request)
{
	// retrieve the table and key from the path
	$table = preg_replace('/[^a-z0-9_]+/i', '', array_shift($request));
	// Verify table may be accessed
	if (!isset($config[$table])) {
		http_response_code(404);
		die('Table does not exist');
	}
	return $table;
}
?>