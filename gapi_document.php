<?php

function gapi_swagger($config, $mysqli, $info)
{
	$options = [];
	optionsHeader($config, $mysqli, $info, $options);
	
	return json_encode($options);
}

function optionsHeader($config, $mysqli, $info, &$options)
{
	$options["swagger"] = "2.0";
	$options["host"] = gethostname();
	$options["basePath"] = "/".basename(dirname(__FILE__))."/api.php";
	$options["consumes"] = array("application/json");
}


?>