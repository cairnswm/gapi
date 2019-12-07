<?php

include_once "apicore.php";

$config = Array(
	"database" => Array("server" => 'localhost', 
						"username" => 'justdance', 
						"password" => 'justdance', 
						"database" => 'justdance'),
    "friendrating" => Array(
					"tablename" => "friendrating",
					"key" => "id",
					"select" => Array("id","name","icon"),
					"update" => Array("name","icon"),
					"delete" => true,
					"create" => Array("id","name","icon")
				),
    "user" => Array(
					"key" => "id",
					"select" => Array("id","username","fullname"),
					"update" => Array("fullname"),
					"delete" => false,
					"create" => false
				),
	"chats" => Array(
					"tablename" => "chat",
					"key" => "id",
					"select" => Array("id","name"),
					"update" => false,
					"delete" => false,
					"create" => false
	)
);

Run($config);
?>
