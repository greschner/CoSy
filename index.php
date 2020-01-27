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

function getTargetGroup($chatID)
{
    $targetGroupFileName = "targetGroup.json";
    $content = file_get_contents($targetGroupFileName);
    if ($content === false) // no file existing yet
        $result = false;
    else {
        $targetGroup = json_decode($content, true);
        $result = $targetGroup[$chatID];
    }
    return $result;
}

function writeTargetGroup($chatID, $targetGroup)
{
    $targetGroupFileName = "targetGroup.json";
    $content = file_get_contents($targetGroupFileName);
    if ($content !== false) {// no file existing yet
        $content_decoded = json_decode($content, true);
    }
    $content_decoded[$chatID] = $targetGroup;
    file_put_contents($targetGroupFileName, json_encode($content_decoded), LOCK_EX);
}

function performSearch($bot, $chatID, $searchTerm, $filter)
{
    $role = getTargetGroup($chatID);
    $searchTerm_enc = urlencode($searchTerm);
    $opts = array('http' =>
        array(
            'method' => 'GET',
            'header' => 'Content-type: application/json'
        )
    );
    $context = stream_context_create($opts);
    if ($filter === false)
        $fn = "https://lemonchill.azurewebsites.net/search.php?search_term=$searchTerm_enc&role=$role";
    else
        $fn = "https://lemonchill.azurewebsites.net/search.php?search_term=$searchTerm_enc&role=$role&filter=$filter";

    $result = file_get_contents($fn, false, $context);
    $resultJson = json_decode($result, true);
    $returnData = $resultJson['result'];
    if (count($returnData) === 0) {
        $sendMessage = new SendMessage($chatID, "Leider konnte ich unter dem von dir gewählten Suchbegriff keine Ergebnisse für deine Zielgruppe finden. Bitte wähle einen anderen Suchbegriff...");
        $bot->sendMessage($sendMessage);
    } else {
        foreach ($returnData as $item) {
            $sendMessage = new SendMessage($chatID, $item);
            $bot->sendMessage($sendMessage);
        }
    }
}

if ($message = $update->getMessage()) {

    $messageText = $message->getText();
    $chatID = $update->getMessage()->getChat()->getId();

    switch ($messageText) {
        case "/start":
            $b1 = InlineKeyboardButton::withTextAsCallbackData('Eltern');
            $b2 = InlineKeyboardButton::withTextAsCallbackData('Lehrende');
            $b3 = InlineKeyboardButton::withTextAsCallbackData('Jugendarbeit');
            $b4 = InlineKeyboardButton::withTextAsCallbackData('Jugendliche');
            $b5 = InlineKeyboardButton::withTextAsCallbackData('Senioren');
            $keyboard = new InlineKeyboardMarkup([[$b1], [$b2], [$b3], [$b4], [$b5]]);

            $sendMessage = new SendMessage($chatID, 'Hallo! Ich bin Nicole.' . PHP_EOL . 'Ich kenne mich sehr gut aus mit Fragen zum richtigen Umgang mit dem Internet. Damit ich dir besser helfen kann wähle bitte die Zielgruppe, der du dich am ehesten zugehörig fühlst:');
            $sendMessage->setReplyMarkup($keyboard);
            $bot->sendMessage($sendMessage);
            break;
        default:
            performSearch($bot, $chatID, $messageText, false);
    }
}

if ($callbackQuery = $update->getCallbackQuery()) {
    $chatID = $callbackQuery->getMessage()->getChat()->getId();
    $callbackData = $callbackQuery->getData();
    $bot->answerCallbackQuery(new AnswerCallbackQuery($callbackQuery->getId()));

    switch ($callbackData) {
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
            $bot->deleteMessage(new DeleteMessage($callbackQuery->getMessage()->getChat()->getId(), $callbackQuery->getMessage()->getMessageId()));
            sleep(1);
            $bot->sendMessage(new SendMessage($callbackQuery->getMessage()->getChat()->getId(), 'Alles klar! Ich merke mir diese Einstellung für zukünftige Fragen.' . PHP_EOL . 'Wenn du deine Auswahl später ändern willst schicke mir einfach eine neue Nachricht mit /start'));
            sleep(1);
            $bot->sendMessage($sendMessage);

            break;
        case "Frage stellen":
            $sendMessage = new SendMessage($callbackQuery->getMessage()->getChat()->getId(), 'Dann leg los! Stell mir eine Frage!');
            $bot->deleteMessage(new DeleteMessage($callbackQuery->getMessage()->getChat()->getId(), $callbackQuery->getMessage()->getMessageId()));
            sleep(1);
            $bot->sendMessage($sendMessage);
            break;
        case "Themen anzeigen":
            $b1 = InlineKeyboardButton::withTextAsCallbackData('Handy & Tablet');
            $b2 = InlineKeyboardButton::withTextAsCallbackData('Digitale Spiele');
            $b3 = InlineKeyboardButton::withTextAsCallbackData('Problematische Inhalte');
            $b4 = InlineKeyboardButton::withTextAsCallbackData('Datenschutz');
            $b5 = InlineKeyboardButton::withTextAsCallbackData('Soziale Netzwerke');
            $b6 = InlineKeyboardButton::withTextAsCallbackData('Informationskompetenz');
            $b7 = InlineKeyboardButton::withTextAsCallbackData('Urheberrechte');
            $b8 = InlineKeyboardButton::withTextAsCallbackData('Cyber Mobbing');
            $b9 = InlineKeyboardButton::withTextAsCallbackData('Internet Betrug');
            $b10 = InlineKeyboardButton::withTextAsCallbackData('Online-Shopping');
            $b11 = InlineKeyboardButton::withTextAsCallbackData('Selbstdarstellung');
            $b12 = InlineKeyboardButton::withTextAsCallbackData('Viren, Spam & Co');
            $keyboard = new InlineKeyboardMarkup([[$b1], [$b2], [$b3], [$b4], [$b5], [$b6], [$b7], [$b8], [$b9], [$b10], [$b11], [$b12]]);
            $sendMessage = new SendMessage($callbackQuery->getMessage()->getChat()->getId(), 'Ich habe folgende Themen zur Auswahl:');
            $sendMessage->setReplyMarkup($keyboard);
            $bot->deleteMessage(new DeleteMessage($callbackQuery->getMessage()->getChat()->getId(), $callbackQuery->getMessage()->getMessageId()));
            sleep(1);
            $bot->sendMessage($sendMessage);
            break;
        case "Im Moment nichts":
            $sendMessage = new SendMessage($callbackQuery->getMessage()->getChat()->getId(), 'Kein Problem! Lass mich es wissen, wenn du was brauchst.');
            $bot->deleteMessage(new DeleteMessage($callbackQuery->getMessage()->getChat()->getId(), $callbackQuery->getMessage()->getMessageId()));
            sleep(1);
            $bot->sendMessage($sendMessage);
            break;
        case "Handy & Tablet":
        case "Digitale Spiele":
        case "Problematische Inhalte":
        case "Datenschutz":
        case "Soziale Netzwerke":
        case "Informationskompetenz":
        case "Urheberrechte":
        case "Cyber Mobbing":
        case "Internet Betrug":
        case "Online-Shopping":
        case "Selbstdarstellung":
        case "Viren, Spam & Co":
            $bot->deleteMessage(new DeleteMessage($callbackQuery->getMessage()->getChat()->getId(), $callbackQuery->getMessage()->getMessageId()));
            sleep(1);
            performSearch($bot, $chatID, '*', $callbackData);
            break;
        default:
            $bot->answerCallbackQuery(new AnswerCallbackQuery($callbackQuery->getId()));
    }
    //file_put_contents("query.txt", $callbackQuery->getMessage()->getText());
}

?>
