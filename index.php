<?php
require_once 'vendor/autoload.php';

$botKey = '1007196355:AAHFvhwo89yJZympLncHzrMPgityuwIJrg4';

$requestFactory = new Http\Factory\Guzzle\RequestFactory();
$streamFactory = new Http\Factory\Guzzle\StreamFactory();
$client = new Http\Adapter\Guzzle6\Client();

$apiClient = new \TgBotApi\BotApiBase\ApiClient($requestFactory, $streamFactory, $client);
$bot = new \TgBotApi\BotApiBase\BotApi($botKey, $apiClient, new \TgBotApi\BotApiBase\BotApiNormalizer());

$userId = 'SICosyBot';

try {
    $bot->send(\TgBotApi\BotApiBase\Method\SendMessageMethod::create($userId, 'Hi'));
} catch (\TgBotApi\BotApiBase\Exception\BadArgumentException $e) {
} catch (\TgBotApi\BotApiBase\Exception\ResponseException $e) {
}


?>