<?php

declare(strict_types=1);

require('search/Parser.php');

#get request object
$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case 'GET':
    case 'get':
        $request = &$_GET;
        break;
    case 'POST':
    case 'post':
        $request = &$_POST;
        break;
    default:
        error_log("No valid HTTP-operation!");
        exit;
}

#search TODO: implement (build correct strings) react to roles
$term = '*';
if (isset($request["search_term"])) {
    $term = urlencode($request["search_term"]);
} 
$url = "https://www.saferinternet.at/suche/?tx_solr[q]=%searchstring%%group%";
$role = '';
if (isset($request["role"])) {
    switch (strtolower($request["role"])) {
        case "eltern":
	    $role = '/2/16/';
	    break;
        case "lehrende":
	    $role = '/2/17/';
            break;
        case "jugendliche":
	    $role = '/2/20/';
            break;
        case "senioren":
	    $role = '/2/18/';
            break;
        case "jugendarbeit":
	    $role = '/2/19/';
	    break;
    }
    $role = 'tx_solr[filter][0]=category:'.$role;
}
$url = str_replace('%searchstring%', $term, $url);
$url = str_replace('%group%', $role, $url);

#parse site
$my_parser = new Parser($url);
$my_parser->init();
$my_parser->parseText();
$search_results = $my_parser->getData();

#build response object
$result_array = [];
$result_array["request"] = $request;
$result_array["status"] = "success"; #necessary for activeChat.ai TODO: evaluate
$result_array["url"] = $url; #TODO remove (testing only)
$counter =0;
$result_array['result'] = '';
while ($search_results[$counter] !== null && $counter < 5){
    $result_array["result"] .= "<a href='" . $search_results[$counter]["link"] . "'>" . $search_results[$counter]["heading"] . "</a><br>"; #TODO: change when moving to PHP-Bot (ActiveChat bot just accepts plain text)
    $counter++;
}
#return data
header('content-type: application/json');
$json_data = json_encode($result_array, JSON_PRETTY_PRINT);
echo $json_data;
