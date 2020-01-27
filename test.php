 <!DOCTYPE html>
<html>
<body>

<h1>My First Heading</h1>
<p>My first paragraph.</p>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo 'test';
exit;
$opts = array('http' =>
	    array(
		'method'  => 'GET',
		'header'  => 'Content-type: application/json'
	    )
	);
	$context = stream_context_create($opts);
	$result = file_get_contents('search.php?search_term=facebook&role=jugendliche', false, $context);
	echo $result;
	echo 'test';
?>

</body>
</html> 

