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

// how to setup webhook
// https://api.telegram.org/bot1085962320:AAGPUP2NnIV0mlUBGRMQiFoaT33fEHrXCBI/setWebhook?url=https://lemonchill.azurewebsites.net/
$bot = new Bot('1085962320:AAGPUP2NnIV0mlUBGRMQiFoaT33fEHrXCBI');

function getTargetGroup($chatID)
{
    $targetGroupFileName = "targetGroup.json";
    $content = file_get_contents($targetGroupFileName);
    $result = false;
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

if ($message = $update->getMessage()) {

    $messageText = $message->getText();
    $chatID = $update->getMessage()->getChat()->getId();

    switch ($messageText) {
        case "/start":
            writeTargetGroup($chatID, 'Eltern');
            $sendMessage = new SendMessage($chatID, 'Hallo! Ich bin Nicole.' . PHP_EOL . 'Ich kenne mich sehr gut aus mit Fragen zum richtigen Umgang mit dem Internet. Damit ich dir besser helfen kann wähle bitte die Zielgruppe, der du dich am ehesten zugehörig fühlst:');
        case "/zielgruppe":
            $b1 = InlineKeyboardButton::withTextAsCallbackData('Eltern');
            $b2 = InlineKeyboardButton::withTextAsCallbackData('Lehrende');
            $b3 = InlineKeyboardButton::withTextAsCallbackData('Jugendarbeit');
            $b4 = InlineKeyboardButton::withTextAsCallbackData('Jugendliche');
            $b5 = InlineKeyboardButton::withTextAsCallbackData('Senioren');
            $keyboard = new InlineKeyboardMarkup([[$b1], [$b2], [$b3], [$b4], [$b5]]);
            if (!isset($sendMessage))
                $sendMessage = new SendMessage($chatID, 'Zielgruppe wählen:');
            $sendMessage->setReplyMarkup($keyboard);
            $bot->sendMessage($sendMessage);
            break;
        case "/help":
        case "?":
            $sendMessage = new SendMessage($chatID, '/start startet den Bot' . PHP_EOL . '/help oder ? listed die Hilfe auf' . PHP_EOL . '/themen listet verschiedene Themen auf'. PHP_EOL .'/zielgruppe ermöglicht die Zielgruppe zu wechseln');
            $bot->sendMessage($sendMessage);
            break;
        case "/themen":
            $b1 = InlineKeyboardButton::withTextAsCallbackData('Handy und Tablet');
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
            $b12 = InlineKeyboardButton::withTextAsCallbackData('Viren, Spam und Co');
            $keyboard = new InlineKeyboardMarkup([[$b1], [$b2], [$b3], [$b4], [$b5], [$b6], [$b7], [$b8], [$b9], [$b10], [$b11], [$b12]]);
            $sendMessage = new SendMessage($chatID, 'Ich habe folgende Themen zur Auswahl:');
            $sendMessage->setReplyMarkup($keyboard);
            sleep(1);
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
            if (count($returnData)===0){
                $sendMessage = new SendMessage($chatID, "Leider konnte ich unter dem von dir gewählten Suchbegriff keine Ergebnisse für deine Zielgruppe finden. Bitte wähle einen anderen Suchbegriff...");
                $bot->sendMessage($sendMessage);
            } else {
                $bot->sendMessage(new SendMessage($chatID, 'Ich habe intensiv recherchiert und folgende Ergebnisse gefunden:'));
                foreach ($returnData as $item){
                    $sendMessage = new SendMessage($chatID, $item);
                    $bot->sendMessage($sendMessage);
                }}
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
            $bot->sendMessage(new SendMessage($callbackQuery->getMessage()->getChat()->getId(), 'Alles klar! Ich merke mir diese Einstellung ('.$callbackData.') für zukünftige Fragen.' . PHP_EOL . 'Wenn du deine Auswahl später ändern willst schicke mir einfach eine neue Nachricht mit /start'));
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
            $b1 = InlineKeyboardButton::withTextAsCallbackData('Handy und Tablet');
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
            $b12 = InlineKeyboardButton::withTextAsCallbackData('Viren, Spam und Co');
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
        case "Handy und Tablet":
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
        case "Viren, Spam und Co":
            $bot->deleteMessage(new DeleteMessage($callbackQuery->getMessage()->getChat()->getId(), $callbackQuery->getMessage()->getMessageId()));
            sleep(1);
            $role = getTargetGroup($chatID);
            $search_term = urlencode($messageText);
            $filter = urlencode($callbackData);
            $opts = array('http' =>
                array(
                    'method'  => 'GET',
                    'header'  => 'Content-type: application/json'
                )
            );
            $context = stream_context_create($opts);
            $result = file_get_contents("https://lemonchill.azurewebsites.net/search.php?search_term=*&role=$role&filter=$filter", false, $context);
            file_put_contents("log.txt", json_encode($result, PRETTY_));
            $resultJson = json_decode($result, true);
            $returnData = $resultJson['result'];
            if (count($returnData)===0){
                $sendMessage = new SendMessage($chatID, "Leider konnte ich unter dem von dir gewählten Suchbegriff keine Ergebnisse für deine Zielgruppe finden. Bitte wähle einen anderen Suchbegriff...");
                $bot->sendMessage($sendMessage);
            } else {
                $bot->sendMessage(new SendMessage($chatID, 'Das habe ich zum Thema '.$callbackData.' gefunden:'));
                foreach ($returnData as $item){
                    $sendMessage = new SendMessage($chatID, $item);
                    $bot->sendMessage($sendMessage);
                }}
            break;
        default:
            $bot->answerCallbackQuery(new AnswerCallbackQuery($callbackQuery->getId()));
    }
}

?>
