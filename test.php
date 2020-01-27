<?php

$opts = array('http' =>
	    array(
		'method'  => 'GET',
		'header'  => 'Content-type: application/json',
	    )
	);
	$context = stream_context_create($opts);
	$result = file_get_contents('https://lemonchill.azurewebsites.net/search.php?search_term=facebook&role=jugendliche', false, $context);
	echo $result;
?>
