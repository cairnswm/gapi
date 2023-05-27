<?php
include_once "config.php";
include_once "apicore.php";

$config = array(
	"database" => $config,
	"country" => array(
		"key" => "code",
		"select" => array("code", "name", "continent", "region", "surfacearea", "indepyear", "population", "lifeexpectancy",
						 "gnp", "gnpold", "localname", "governmentform", "headofstate", "capital", "code2","comment"),
		"create" => array("code", "name", "region", "surfacearea", "indepyear", "population", "lifeexpectancy",
						"gnp", "gnpold", "localname", "governmentform", "headofstate", "capital", "code2","comment"),
		"update" => array("name", "region", "surfacearea", "indepyear", "population", "lifeexpectancy",
						"gnp", "gnpold", "localname", "governmentform", "headofstate", "capital", "code2","comment"),
		"delete" => true,
		"beforeselect" => "beforeSelectCountry",
		"afterselect" => "afterSelectCountry",
		"beforeupdate" => "beforeUpdateCountry",
		"beforeinsert" => "beforeInsertCountry",
		"beforedelete" => "beforeDeleteCountry",
		"subkeys" => array(
			"city" => array(
				"key" => "countrycode",
				"select" => array("id", "name", "countrycode", "district", "population"),
			)
		)
	),
	"language" => array(
		"tablename" => "countrylanguage",
		"key" => "countrycode",
		"select" => array("countrycode", "language", "isofficial", "percentage"),
		"update" => array("countrycode", "language", "isofficial", "percentage"),
		"delete" => true,
		"create" => array("countrycode", "language", "isofficial", "percentage")
	),
	"city" => array(
		"key" => "id",
		"select" => array("id", "name", "countrycode", "district", "population"),
		"update" => array("name", "countrycode", "district", "population"),
		"delete" => false,
		"create" => array("name", "countrycode", "district", "population"),
		"beforeupdate" => "beforeProfileUpdate",
	),
	"citypercountry" => array(
		"tablename" => "city",
		"key" => "countrycode",
		"select" => array("id", "name", "countrycode", "district", "population"),
		"update" => false,
		"delete" => false,
		"create" => false,
	),
	"continent" => array(
		"key" => "continent",
		"select" => "SELECT DISTINCT continent FROM country",
		"subkeys" => array(
			"country" => array(
				"key" => "continent",
				"select" => array("code", "name"),
			)
		)
	)
);

Run($config);

function beforeSelectCountry($config, $info)
{
	// use before select to set default values to limit rows based on tenant values
	global$defaultwhere, $defaultparams, $defaultsss;
	// $defaultwhere = "continent=?";
	// $defaultsss = "s";
	// $defaultparams = ["Middle Earth"];
	$info["where"] = "continent=?";
	$info["wheresss"] = "s";
	$info["whereparams"] = array("Middle Earth");
	// set the default where clause values
	// Allows for support Tenancy queries etc
	return $info;
}
// Define the before and after methods
function afterSelectCountry($results)
{
	// Result set returned and can be modified
	$results[0]["message"] = "After Select";
	return $results;
}

function beforeUpdateCountry($info)
{
	// use before update to set additional criteria before update
	$info["set"] = array("editedby");
	$info["sss"] = "s";
	$info["params"] = array("william");
	$info["where"] = "continent=?";
	$info["wheresss"] = "s";
	$info["whereparams"] = array("Middle Earth");
	return $info;
}

function beforeInsertCountry($info)
{
	// use before insert to set default values
	$info["set"] = array("editedby","continent");
	$info["sss"] = "ss";
	$info["params"] = array("william","Middle Earth");
	return $info;
}

function beforeDeleteCountry($info)
{
	// var_dump($info);
	// use beforedelete to set additional criteria before delete
	$info["where"] = "editedby=? and continent=?";
	$info["wheresss"] = "ss";
	$info["whereparams"] = array("william","Middle Earth");
	return $info;
}

?>