<?php
require_once 'vendor/autoload.php';

use Formapro\TelegramBot\Bot;
use Formapro\TelegramBot\SendPhoto;
use Formapro\TelegramBot\Update;
use Formapro\TelegramBot\SendMessage;
use Formapro\TelegramBot\InlineKeyboardButton;
use Formapro\TelegramBot\InlineKeyboardMarkup;
use Formapro\TelegramBot\AnswerCallbackQuery;
use Formapro\TelegramBot\DeleteMessage;
use function GuzzleHttp\Psr7\str;

$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);
$update = Update::create($data);
//file_put_contents("data.txt",json_encode($data, JSON_PRETTY_PRINT),FILE_APPEND);



$bot = new Bot('1041036378:AAEklkqQVADfltXOkyyfbaV1coFx9W3fXPo');

function getTargetGroup($chatID){
   $targetGroupFileName = "targetGroup.json";
   $content =  file_get_contents($targetGroupFileName);
   $result = false;

   if($content===false) // no file existing yet
       $result = false;
   else {
       $targetGroup = json_decode($content, true);
       $result = $targetGroup[$chatID];
   }
   return $result;
}

function writeTargetGroup($chatID, $targetGroup){
    $targetGroupFileName = "targetGroup.json";
    $data = array($chatID => $targetGroup);
    $content =  file_get_contents($targetGroupFileName);
    if($content!==false) {// no file existing yet
        $content_decoded = json_decode($content, true);
    }
    $content_decoded[$chatID] = $targetGroup;
    file_put_contents($targetGroupFileName, json_encode($content_decoded),LOCK_EX);
}

if ($message = $update->getMessage()){

    $messageText = $message->getText();
    $chatID = $update->getMessage()->getChat()->getId();

    switch ($messageText){
        case "/start":
            $b1 = InlineKeyboardButton::withTextAsCallbackData('Eltern');
            $b2 = InlineKeyboardButton::withTextAsCallbackData('Lehrende');
            $b3 = InlineKeyboardButton::withTextAsCallbackData('Jugendarbeit');
            $b4 = InlineKeyboardButton::withTextAsCallbackData('Jugendliche');
            $b5 = InlineKeyboardButton::withTextAsCallbackData('Senioren');
            $b5 = InlineKeyboardButton::withTextAsCallbackData('Richard Lugner');
            $keyboard = new InlineKeyboardMarkup([[$b1], [$b2], [$b3], [$b4], [$b5]]);

            $sendMessage = new SendMessage($chatID, 'Hallo! Ich bin Nicole. Ich kenne mich sehr gut aus mit Fragen zum richtigen Umgang mit dem Internet. Damit ich dir besser helfen kann wähle bitte die Zielgruppe, der du dich am ehesten zugehörig fühlst:');
            $sendMessage->setReplyMarkup($keyboard);
            $bot->sendMessage($sendMessage);
            break;
        default:
            $role = getTargetGroup($chatID);
            $search_term = urlencode($messageText);
            $opts = array('http' =>
                array(
                    'method'  => 'GET',
                    'header'  => 'Content-type: application/json'
                )
            );
            $context = stream_context_create($opts);
            $result = file_get_contents("https://lemonchill.azurewebsites.net/search.php?search_term=$search_term&role=$role", false, $context);
            $resultJson = json_decode($result, true);
            $returnData = $resultJson['result'];
            foreach ($returnData as $item){
                $sendMessage = new SendMessage($chatID, $item);
                $bot->sendMessage($sendMessage);
            }
    }
}

if ($callbackQuery = $update->getCallbackQuery()) {
    $chatID = $callbackQuery->getMessage()->getChat()->getId();
    $callbackData = $callbackQuery->getData();
    $bot->answerCallbackQuery(new AnswerCallbackQuery($callbackQuery->getId()));

    switch ($callbackData){
        case "Eltern":
        case "Lehrende":
        case "Jugendarbeit":
        case "Jugendliche":
        case "Senioren":
            writeTargetGroup($chatID, $callbackData);
            $b1 = InlineKeyboardButton::withTextAsCallbackData('Frage stellen');
            $b2 = InlineKeyboardButton::withTextAsCallbackData('Themen anzeigen');
            $b3 = InlineKeyboardButton::withTextAsCallbackData('Im Moment nichts');
            $keyboard = new InlineKeyboardMarkup([[$b1], [$b2], [$b3]]);
            $sendMessage = new SendMessage($callbackQuery->getMessage()->getChat()->getId(), 'Ich kann dir anbieten eine Frage zu beantworten, oder dir verschiedene Themen vorschlagen. Was klingt besser?');
            $sendMessage->setReplyMarkup($keyboard);
            $bot->sendMessage(new SendMessage($callbackQuery->getMessage()->getChat()->getId(),'Alles klar! Ich merke mir diese Einstellung für zukünftige Fragen.'.PHP_EOL.'Wenn du deine Auswahl später ändern willst schicke mir einfach eine neue Nachricht mit /start'));
            $bot->sendMessage($sendMessage);
            $bot->deleteMessage(new DeleteMessage($callbackQuery->getMessage()->getChat()->getId(), $callbackQuery->getMessage()->getMessageId()));
            break;
        case "Frage stellen":
            $sendMessage = new SendMessage($callbackQuery->getMessage()->getChat()->getId(), 'Dann leg los! Stell mir eine Frage!');
            $bot->sendMessage($sendMessage);
            $bot->deleteMessage(new DeleteMessage($callbackQuery->getMessage()->getChat()->getId(), $callbackQuery->getMessage()->getMessageId()));
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
