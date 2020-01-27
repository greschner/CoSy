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
$url = "https://www.saferinternet.at/suche/?tx_solr[q]=%searchstring%%group%%filter%";
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
    $role = '&tx_solr[filter][0]=category:' . $role;
}
$filter = '';
if (isset($request["filter"])) {
    switch (strtolower($request["filter"])) {
        case "Handy & Tablet":
            $filter = '/1/4/';
            break;
        case "Digitale Spiele":
            $filter = '/1/5/';
            break;
        case "Soziale Netzwerke":
            $filter = '/1/6/';
            break;
        case "Problematische Inhalte":
            $filter = '/1/7/';
            break;
        case "Informationskompetenz":
            $filter = '/1/8/';
            break;
        case "Selbstdarstellung":
            $filter = '/1/9/';
            break;
        case "Datenschutz":
            $filter = '/1/10/';
            break;
        case "Cyber-Mobbing":
            $filter = '/1/11/';
            break;
        case "Internet-Betrug":
            $filter = '/1/12/';
            break;
        case "Online-Shopping":
            $filter = '/1/13/';
            break;
        case "Urheberrechte":
            $filter = '/1/14/';
            break;
        case "Viren, Spam & Co":
            $filter = '/1/15/';
            break;
    }
    $filter = '&tx_solr[filter][1]=category:' . $filter;
}

$url = str_replace('%searchstring%', $term, $url);
$url = str_replace('%group%', $role, $url);
$url = str_replace('%filter%', $filter, $url);

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
$counter = 0;
$result_array['result'] = '';
while ($search_results[$counter] !== null && $counter < 5) {
    $result_array["result"] .= "<a href='" . $search_results[$counter]["link"] . "'>" . $search_results[$counter]["heading"] . "</a><br>"; #TODO: change when moving to PHP-Bot (ActiveChat bot just accepts plain text)
    $counter++;
}
#return data
header('content-type: application/json');
$json_data = json_encode($result_array, JSON_PRETTY_PRINT);
echo $json_data;
