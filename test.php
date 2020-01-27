<!DOCTYPE html>
<html>
<body>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$opts = array('http' =>
    array(
        'method' => 'GET',
        'header' => 'Content-type: application/json'
    )
);
$context = stream_context_create($opts);

$role = "jugendliche";
$search_term = "facebook";

$result = file_get_contents("https://lemonchill.azurewebsites.net/search.php?search_term=$search_term&role=$role", false, $context);
echo $result;
echo 'test';
?>

</body>
</html> 

