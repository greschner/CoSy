<?php
require_once 'vendor/autoload.php';

use Formapro\TelegramBot\Bot;
use Formapro\TelegramBot\SendPhoto;
use Formapro\TelegramBot\Update;
use Formapro\TelegramBot\SendMessage;
use Formapro\TelegramBot\InlineKeyboardButton;
use Formapro\TelegramBot\InlineKeyboardMarkup;
use Formapro\TelegramBot\AnswerCallbackQuery;
use function GuzzleHttp\Psr7\str;

$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);
file_put_contents("data.txt",json_encode($data, JSON_PRETTY_PRINT),FILE_APPEND);

$update = Update::create($data);

$bot = new Bot('1007196355:AAHFvhwo89yJZympLncHzrMPgityuwIJrg4');

if ($message = $update->getMessage()){

    if ($message->getText()=="/start"){
        $b1 = InlineKeyboardButton::withTextAsCallbackData('Eltern');
        $b2 = InlineKeyboardButton::withTextAsCallbackData('Lehrende');
        $b3 = InlineKeyboardButton::withTextAsCallbackData('Jugendarbeit');
        $b4 = InlineKeyboardButton::withTextAsCallbackData('Jugendliche');
        $b5 = InlineKeyboardButton::withTextAsCallbackData('Senioren');
        $b5 = InlineKeyboardButton::withTextAsCallbackData('Richard Lugner');
        $keyboard = new InlineKeyboardMarkup([[$b1], [$b2], [$b3], [$b4], [$b5]]);

        $sendMessage = new SendMessage($update->getMessage()->getChat()->getId(), 'Hallo! Ich bin deine Mama. Ich kenne mich sehr gut aus mit Fragen zum richtigen Umgang mit dem Internet. Damit ich dir besser helfen kann wähle bitte die Zielgruppe, der du dich am ehesten zugehörig fühlst:');
        $sendMessage->setReplyMarkup($keyboard);
        $bot->sendMessage($sendMessage);
    }
}

if ($callbackQuery = $update->getCallbackQuery()) {

    switch ($callbackQuery->getData()){
        case "Eltern":
            $bot->answerCallbackQuery(new AnswerCallbackQuery($callbackQuery->getId()));
            $b1 = InlineKeyboardButton::withTextAsCallbackData('Frage stellen');
            $b2 = InlineKeyboardButton::withTextAsCallbackData('Themen anzeigen');
            $b3 = InlineKeyboardButton::withTextAsCallbackData('Im Moment nichts');
            $keyboard = new InlineKeyboardMarkup([[$b1], [$b2], [$b3]]);
            $sendMessage = new SendMessage($callbackQuery->getMessage()->getChat()->getId(), 'Ich kann dir anbieten eine Frage zu beantworten, oder dir verschiedene Themen vorschlagen. Was klingt besser?');
            $sendMessage->setReplyMarkup($keyboard);
            $bot->sendMessage(new SendMessage($callbackQuery->getMessage()->getChat()->getId(),'Toll. Das hat so weit gut funktioniert. Wenn du deine Auswahl später ändern willst schicke mir einfach eine neue Nachricht mit /start'));
            $bot->sendMessage($sendMessage);
            break;
        case "Lehrende":
            $bot->answerCallbackQuery(new AnswerCallbackQuery($callbackQuery->getId()));
            break;
        case "Jugendarbeit":
            $bot->answerCallbackQuery(new AnswerCallbackQuery($callbackQuery->getId()));
            break;
        case "Jugendliche":
            $bot->answerCallbackQuery(new AnswerCallbackQuery($callbackQuery->getId()));
            break;
        case "Richard Lugner":
            $bot->answerCallbackQuery(new AnswerCallbackQuery($callbackQuery->getId()));
            $sendPhoto = new SendPhoto(
                $callbackQuery->getMessage()->getChat()->getId(),
                file_get_contents('lugner.jpeg') // or just $picture if it's url
            );
            $sendPhoto->setCaption('I bims Richard Lugner');
            $bot->sendPhoto($sendPhoto);
            break;
        default:
            $bot->answerCallbackQuery(new AnswerCallbackQuery($callbackQuery->getId()));
    }

    file_put_contents("query.txt",$callbackQuery->getMessage()->getText());

}

?>