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
if (isset($request["search_term"])) {
    $term = $request["search_term"];
} else {
    $term = "*";
}
$url = "https://www.saferinternet.at/suche/?tx_solr%5Bq%5D=";
if (isset($request["role"])) {
    switch ($request["role"]) {
        case "Eltern":
            //break;
        case "Lehrende":
            //break;
        case "Jugendliche":
            //break;
        case "Senioren":
            break;
        case "Jugendarbeit":
            $url = "https://www.saferinternet.at/suche/?tx_solr%5Bq%5D=snapchat&tx_solr%5Bfilter%5D%5B0%5D=category%3A%2F2%2F19%2F";
            break;
        default:
            $url = "https://www.saferinternet.at/suche/?L=0&id=59&tx_solr%5Bq%5D=" . $term;
    }
} else {
    $url = "https://www.saferinternet.at/suche/?L=0&id=59&tx_solr%5Bq%5D=" . $term;
}

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
$result_array["result"] = $search_results;

#return data
header('content-type: application/json');
$json_data = json_encode($result_array, JSON_PRETTY_PRINT);
echo $json_data;