<?php

include_once "apicore.php";

function afterSelect($results) {
	var_dump($results);
	$results[0]["message"] = "After Select";
	return $results;
}

$config = Array(
	"database" => Array("server" => 'localhost', 
						"username" => 'justdance', 
						"password" => 'justdance', 
						"database" => 'justdance'),
	"wall" => Array(
					"key" => "id",
					"select" => Array("id","friendid","name","gender","status","avatar","message"),
					"afterselect" => "afterSelect"
	),
    "friendrating" => Array(
					"tablename" => "friendrating",
					"key" => "id",
					"select" => Array("id","name","icon"),
					"update" => Array("name","icon"),
					"delete" => true,
					"create" => Array("id","name","icon")
				),
    "profile" => Array(
					"key" => "id",
					"select" => Array("id","name","area","gender","status","avatar","message","tagline"),
					"update" => Array("name","area","gender","status","avatar","message","tagline"),
					"delete" => false,
					"create" => Array("id","name","area","gender","status","message","tagline")
				),
	"friends" => Array(
					"tablename" => "friends",
					"key" => "id",
					"select" => Array("id","profileid","friendid","rating"),
					"update" => Array("rating"),
					"delete" => false,
					"create" => Array("id","profileid","friendid","rating"),
	)
);

Run($config);
?>
