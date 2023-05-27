<?php
include_once "config.php";
include_once "apicore.php";

$config = array(
	"database" => $config,
	"country" => array(
		"key" => "code",
		"select" => array("code", "name", "continent", "region", "surfacearea", "indepyear", "population", "lifeexpectancy",
						 "gnp", "gnpold", "localname", "governmentform", "headofstate", "capital", "code2"),
		"create" => array("code", "name", "continent", "region", "surfacearea", "indepyear", "population", "lifeexpectancy",
						"gnp", "gnpold", "localname", "governmentform", "headofstate", "capital", "code2"),
		"update" => array("code", "name", "continent", "region", "surfacearea", "indepyear", "population", "lifeexpectancy",
						"gnp", "gnpold", "localname", "governmentform", "headofstate", "capital", "code2"),
		"delete" => true,
		"beforeselect" => "beforeSelect",
		"afterselect" => "afterSelect",
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

function beforeSelect($config, $info)
{
	global$defaultwhere, $defaultparams, $defaultsss;
	$defaultwhere = "1=?";
	$defaultsss = "s";
	$defaultparams = ["1"];
	// set the default where clause values
	// Allows for support Tenancy queries etc
	return $config;
}
// Define the before and after methods
function afterSelect($results)
{
	// Result set returned and can be modified
	$results[0]["message"] = "After Select";
	return $results;
}

function beforeProfileUpdate($info)
{
	$fields = $info["fields"];
	if (isset($fields["gender"])) {
		if (!($fields["gender"] == "F" || $fields["gender"] == "M")) {
			throw new Exception("Gender may only be M or F");
		}
	}
	return $info;
}
?>