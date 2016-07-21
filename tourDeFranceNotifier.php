<?php

/**
 * Tour de France Bot for Slack.
 *
 * It uses the unofficial letour.fr json API (the one used for their mobile app iOS/Android).
 * It will post a message :
 *   - when a stage will start (with info about it + map)
 *   - every telegrams from the feed (could be too verbose..)
 *
 * You will need a token from Slack.
 * Jump at https://api.slack.com/ under the "Authentication" part and you will find your token.
 *
 * @author j0k <jeremy.benoist@gmail.com>
 * @license MIT
 */

/**
 * All the configuration are just below
 */

// Slack stuff
const SLACK_TOKEN      = 'XXXXXXXX';
const SLACK_CHANNEL    = '#tour-de-france';
const SLACK_BOT_NAME   = 'Tour de France';
const SLACK_BOT_AVATAR = 'http://i.imgur.com/WsjMcFw.png';

const PROXY         = false;
// If a proxy authentification is needed, set PROXY_USERPWD to "user:password"
const PROXY_USERPWD = false;

// Set the language for updates. Available: de, en, es, fr
const LANG = 'en';
const DB = '/tmp/tourDeFranceDB.json';

const YEAR = 2016;

const OUTPUT = 'slack'; //[ debug (print to console by not send to slack) | slack ]
const DEBUG_MESSAGE_START = '-1'; // reset the "last message" so that there is something to show on every run

/**
 * Below this line, you should modify at your own risk
 */
$language = array(
  'fr' => array(
    'Type',
    'Distance',
    'Heure de départ',
    'Étape du jour',
    'La carte interactive',
    'Profil de l\'étape',
  ),
  'en' => array(
    'Type',
    'Distance',
    'Starting date',
    'Today stage',
    'Interactive map',
    'Stage profile',
  ),
);

function getUnpaddedStageNumber($stageNum) {
  return preg_replace('/^0/', '', $stageNum);
}

function generateProfileImageUrl($stageNum) {
  return 'http://www.letour.fr/PHOTOS/TDF/'.YEAR.'/'.getUnpaddedStageNumber($stageNum).'/PROFIL.png';
}

function generateMapImageUrl($stageNum) {
  return 'http://www.letour.fr/PHOTOS/TDF/'.YEAR.'/'.getUnpaddedStageNumber($stageNum).'/CARTE.jpg'; 
}

function getUrl($url) {

  $ch = curl_init($url);
  $options = array(
    CURLOPT_HEADER => 0,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_FOLLOWLOCATION => 1,
    CURLOPT_SSL_VERIFYPEER => false,
  );

  if (PROXY) {
    $options[CURLOPT_PROXY] = PROXY;
  }

  if (PROXY_USERPWD) {
    $options[CURLOPT_PROXYUSERPWD] = PROXY_USERPWD;
  }

  curl_setopt_array($ch, $options);

  $response = curl_exec($ch);

  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  if (200 !== $httpcode) {
    curl_close($ch);
    return false;
  }

  if ($response !== false) {
    curl_close($ch);
    return $response;
  }

  var_dump(curl_error($ch));
  curl_close($ch);
  die();
}

function postMessage($title, $attachments_text = '', $emoji = '', $pretty = true) {
  if ($emoji != '') {
    $title = $emoji . ' ' . $title;
  }

  if ( OUTPUT == 'debug' ) {
    printf($title . "\n");
  } elseif ( OUTPUT == 'slack') {
    postToSlack($title, $attachments_text, $pretty);
  }
}

function postToSlack($text, $attachments_text = '', $pretty = true)
{
  $attachments_text = str_replace("\n", "", $attachments_text);
  $attachments_text = str_replace('"', '\"', $attachments_text);

  $slackUrl = 'https://slack.com/api/chat.postMessage?token='.SLACK_TOKEN.
    '&channel='.urlencode(SLACK_CHANNEL).
    '&username='.urlencode(SLACK_BOT_NAME).
    '&icon_url='.SLACK_BOT_AVATAR.
    '&text='.urlencode($text);

  if ($pretty) {
    $slackUrl .= '&unfurl_links=1&parse=full&pretty=1';
  }

  if ($attachments_text) {
    $slackUrl .= '&attachments='.urlencode('[{"text": "'.$attachments_text.'"}]');
    //$slackUrl .= '&attachments='.urlencode('[{"text": "'.'"}]');
  }

  var_dump(getUrl($slackUrl));

}

function doWeCareAboutThisEvent($eventId){
	$ignored_events = array(
		42, // Microphone, interviews
		43, // Diverse stats
		44, // History
		45, // Birthdays!
	);
	
	if (in_array($eventId, $ignored_events)){
		return False;
	} else {
		return True;
	}
}

function getEmojiForEventType($eventType) {
  $emojiLookup = array(
    0   => '', // rien
    1   => ':hospital:', //accident
    2   => ':wrench:', //crevaison
    3   => ':pushpin:', //point échappée
    4   => ':collision:', //chute
    5   => '',
    6   => ':point_right:',
    7   => ':bar_chart:', // classement par équipe
    8   => ':checkered_flag:', // sprint intermédiaire
    9   => ':stopwatch:', // moyenne km/h
    10  => ':mount_fuji:', // sommet
    11  => ':mount_fuji:', // sommet
    12  => ':mount_fuji:', // sommet
    13  => ':mount_fuji:', // sommet
    14  => ':mount_fuji:', // sommet
    15  => ':dash:', // accélération / attaque
    16  => '',
    17  => ':busts_in_silhouette:', // point sur le peleton
    18  => ':wrench:', // arrêt mécanique
    19  => ':stopwatch:', // point km/h
    20  => ':stopwatch:', // gap
    21  => ':point_right:', // point sur les derniers kilomètres
    22  => ':muscle:', // point sur l'homme de tête
    23  => ':muscle:', // point sur les hommes de tête
    24  => ':muscle:', // point sur les hommes de tête
    25  => ':mountain_bicyclist:', // ascension
    26  => ':no_good:', // abandon
    27  => '',
    28  => '',
    29  => '',
    30  => '',
    31  => ':bar_chart:', // le top 5
    32  => ':checkered_flag:', // victoire
    33  => ':metal:', // prix Antargaz de la combativité
    34  => ':fire:', // Sous la Flamme Rouge
    35  => ':bicyclist:', // maillot à pois
    36  => ':bicyclist:', // maillot vert
    37  => ':bicyclist:', // maillot blanc
    38  => ':bicyclist:', // maillot jaune
    39  => ':metal:', // prix de la combativité
    40  => ':checkered_flag:', // départ
    41  => ':newspaper:', // lu dans la presse
    42  => ':microphone:', // interview
    43  => ':point_up:', // diverse stats
    44  => ':book:', // point historique
    45  => ':birthday:', // anniversaire
    46  => ':construction:', // Changement de vélo
    48  => ''
  );

  return $emojiLookup[$eventType];
}

function generateDistanceString($km) {
  $miles = round($km * 0.62137);
  return $km . 'km / ' . $miles . 'mi';
}

function main() {
  global $language;

  print("running\n");

  $appState = json_decode(getUrl('http://www.letour.fr/useradgents/'.YEAR.'/json/appState.json'), true);

  if (!isset($appState['stage'])) {
    die('appState is not good');
  }

  $stageNum = $appState['stage'];

  if (!$stageNum) {
    die('No stageNum ?');
  }

  $dbFile = DB;

  $db = json_decode(file_get_contents($dbFile), true);
  $response = json_decode(getUrl('http://www.letour.fr/useradgents/'.YEAR.'/json/livenews'.$stageNum.'_'.LANG.'.json'), true);

  if (!$response) {
    die('Feed not ready');
  }

  if ($stageNum != $db['current_stage']) {
    $db['current_stage'] = $stageNum;
    $db['last_update'] = -1;
  } 

  if (!isset($response['d']) || empty($response['d'])) {
    die('d is not good');
  }

  foreach ($response['d'] as $key => $post) {
    if ($post['s'] > $db['last_update']) {
      // on first key we post some stats about the stage
      if (0 == $key) {
        $route = json_decode(getUrl('http://www.letour.fr/useradgents/'.YEAR.'/json/route.'.$appState['jsonVersions']['route'].'.json'), true);

        if (isset($route[$stageNum])) {
          $extra = $language[LANG][0].': '.$route[$stageNum]['type'].", ".$language[LANG][1].': ' . generateDistanceString($route[$stageNum]['distance']);

          postMessage(':zap: '.$language[LANG][3].': *'.$route[$stageNum]['start'].' - '.$route[$stageNum]['finish'].'*', $extra);
        }

        postMessage(':earth_africa: '.$language[LANG][4].': '.generateMapImageUrl($stageNum), '', false);
        postMessage(':chart_with_upwards_trend: '.$language[LANG][5].': '.generateProfileImageUrl($stageNum));
      } 

      $db['last_update'] = $post['s'];

      $emoji = '';
      
      if (isset($post['e'])) {
        $emoji = getEmojiForEventType($post['e']);
      }

      $h = sprintf('%02d', floor($post['s']/3600));
      $m = sprintf('%02d', floor(($post['s'] - 3600*$h) / 60));

      $date = $h.':'.$m;
      
      if ('fr' == LANG) {
        $date = $h.'h'.$m;
      }
      if (doWeCareAboutThisEvent($post['e'])){
	      postMessage($post['t'].' – _'.$date.'_', $post['b'], $emoji);
	  }
    }
  }

  if (OUTPUT == 'debug') {
    $db['last_update'] = DEBUG_MESSAGE_START;
  }

  file_put_contents($dbFile, json_encode($db));
}

if ($argv && $argv[0] && realpath($argv[0]) === __FILE__) {
  main();
}
