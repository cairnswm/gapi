<?php

include_once "apicore.php";

$config = Array(
    "messages" => Array(
					"tablename" => "messages",
					"key" => "chatid",
					"select" => Array("chatid","username","message","createddate"),
					"update" => Array("message"),
					"delete" => true,
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
