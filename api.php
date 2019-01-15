<?php
// Based on https://www.leaseweb.com/labs/2015/10/creating-a-simple-rest-api-in-php/
include_once "apicore.php";

$config = Array(
    "messages" => Array(
					"tablename" => "messages",
					"key" => "chatid",
					"select" => Array("chatid","username","message","createddate"),
					"update" => Array("message"),
					"delete" => false,
					"create" => Array("chatid","username","message")
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
