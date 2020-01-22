<?php
require_once 'vendor/autoload.php';

use Formapro\TelegramBot\Bot;
use Formapro\TelegramBot\Update;
use Formapro\TelegramBot\SendMessage;
use Formapro\TelegramBot\InlineKeyboardButton;
use Formapro\TelegramBot\InlineKeyboardMarkup;
use function GuzzleHttp\Psr7\str;

$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

$update = Update::create($data);

$bot = new Bot('1007196355:AAHFvhwo89yJZympLncHzrMPgityuwIJrg4');

if ($update->getMessage()->getText()=="/start"){

    file_put_contents("test.txt",$update->getMessage()->getText());

    $b1 = new InlineKeyboardButton('Eltern');
    $b2 = new InlineKeyboardButton('Lehrende');
    $b3 = new InlineKeyboardButton('Jugendarbeit');
    $b4 = new InlineKeyboardButton('Jugendliche');
    $b5 = new InlineKeyboardButton('Senioren');
    $keyboard = new InlineKeyboardMarkup([[$b1, $b2, $b3]]);

    $sendMessage = new SendMessage($update->getMessage()->getChat()->getId(), 'Choose an option.');
    $sendMessage->setReplyMarkup($keyboard);
    $bot->sendMessage($sendMessage);
}

/*$bot->sendMessage(new SendMessage(
    $update->getMessage()->getChat()->getId(),
    'Hi there! What can I do?'
));*/


#Framework from: https://github.com/tg-bot-api/bot-api-base

$botKey = '1007196355:AAHFvhwo89yJZympLncHzrMPgityuwIJrg4';
$userId = '20598804';


/*try {
    $bot->send(\TgBotApi\BotApiBase\Method\SendMessageMethod::create($userId, 'Hi'));
} catch (\TgBotApi\BotApiBase\Exception\BadArgumentException $e) {
    echo $e;
} catch (\TgBotApi\BotApiBase\Exception\ResponseException $e) {
    echo $e;
}*/


?>