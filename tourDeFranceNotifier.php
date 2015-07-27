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
const SLACK_TOKEN      = 'XXXXXXXXXXXXXXXXXXXXXXXXXX';
const SLACK_CHANNEL    = '#tour-de-france';
const SLACK_BOT_NAME   = 'Tour de France';
const SLACK_BOT_AVATAR = 'http://i.imgur.com/WsjMcFw.png';

const PROXY         = false;
// If a proxy authentification is needed, set PROXY_USERPWD to "user:password"
const PROXY_USERPWD = false;

// Set the language for updates. Available: de, en, es, fr
const LANG = 'fr';

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
  )
);

/**
 * Below this line, you should modify at your own risk
 */

function getUrl($url)
{
  $ch = curl_init($url);
  $options = array(
    CURLOPT_HEADER => 0,
    CURLOPT_TIMEOUT => 3,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_FOLLOWLOCATION => 1,
    CURLOPT_SSL_VERIFYPEER => false,
  );

  if (PROXY)
  {
    $options[CURLOPT_PROXY] = PROXY;
  }

  if (PROXY_USERPWD)
  {
    $options[CURLOPT_PROXYUSERPWD] = PROXY_USERPWD;
  }

  curl_setopt_array($ch, $options);

  $response = curl_exec($ch);
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  if (200 !== $httpcode)
  {
    curl_close($ch);
    return false;
  }

  if ($response !== false)
  {
    curl_close($ch);
    return $response;
  }

  var_dump(curl_error($ch));
  curl_close($ch);
  die();
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

  if ($pretty)
  {
    $slackUrl .= '&unfurl_links=1&parse=full&pretty=1';
  }

  if ($attachments_text)
  {
    $slackUrl .= '&attachments='.urlencode('[{"text": "'.$attachments_text.'"}]');
  }

  var_dump(getUrl($slackUrl));
}

$stageMaps = 'http://tdf2015.webgeoservices.com/mapviewers/%d/?format=embed&language=%s';
$stageMapsTable = array(
  '0100' => array(489, 492, 486), // FR, other langs, 3rd param
  '0200' => array(496, 499, 493),
  '0300' => array(503, 506, 500),
  '0400' => array(510, 513, 507),
  '0500' => array(517, 520, 514),
  '0600' => array(524, 527, 521),
  '0700' => array(531, 534, 528),
  '0800' => array(538, 541, 535),
  '0900' => array(561, 562, 544),
  '1000' => array(568, 569, 563),
  '1100' => array(575, 576, 570),
  '1200' => array(548, 549, 542),
  '1300' => array(555, 556, 550),
  '1400' => array(582, 583, 577),
  '1500' => array(589, 590, 584),
  '1600' => array(596, 597, 591),
  '1700' => array(603, 604, 598),
  '1800' => array(610, 611, 605),
  '1900' => array(617, 618, 612),
  '2000' => array(624, 625, 619),
  '2100' => array(631, 632, 626),
);

$appState = json_decode(getUrl('http://www.letour.fr/useradgents/2015/json/appState.json'), true);

if (!isset($appState['stage']))
{
  die('appState is not good');
}

$stageNum = $appState['stage'];

if (!$stageNum)
{
  die('No stageNum ?');
}

$dbFile = './tourDeFranceDB.json';

$db = json_decode(file_get_contents($dbFile), true);
$response = json_decode(getUrl('http://www.letour.fr/useradgents/2015/json/livenews'.$stageNum.'_'.LANG.'.json'), true);

if (!$response)
{
  die('Feed not ready');
}

if ($stageNum != $db['current_stage'])
{
  $db['current_stage'] = $stageNum;
  $db['last_update'] = -1;
}

if (!isset($response['d']) || empty($response['d']))
{
  die('d is not good');
}

foreach ($response['d'] as $key => $post)
{
  if ($post['s'] > $db['last_update'])
  {
    // on first key we post some stats about the stage
    if (0 == $key)
    {
      $url = sprintf(
        $stageMaps,
        $stageMapsTable[$stageNum][LANG == 'fr' ? 0 : 1],
        LANG
      );

      $route = json_decode(getUrl('http://www.letour.fr/useradgents/2015/json/route.'.$appState['jsonVersions']['route'].'.json'), true);

      if (isset($route[$stageNum]))
      {
        $extra = $language[LANG][0].': '.$route[$stageNum]['type'].", ".$language[LANG][1].': '.$route[$stageNum]['distance']." km";

        postToSlack(':zap: '.$language[LANG][3].': *'.$route[$stageNum]['start'].' - '.$route[$stageNum]['finish'].'*', $extra);
      }

      // this link will show up a green ugly image, so we don't "prettify" the url (3rd parameters)
      postToSlack(':earth_africa: '.$language[LANG][4].': '.$url, '', false);
      postToSlack(':chart_with_upwards_trend: '.$language[LANG][5].': http://www.letour.fr/useradgents/2015/profiles/'.$stageNum.'.jpg');
    }

    $db['last_update'] = $post['s'];

    $event = '';
    if (isset($post['e']))
    {
      switch ($post['e'])
      {
        // rien ...
        case 0:
          $event = '';
          break;
        // accident
        case 1:
          $event = ':hospital:';
          break;
        // crevaison
        case 2:
          $event = ':hocho:';
          break;
        // point échappée
        case 3:
          $event = ':pushpin:';
          break;
        // chute
        case 4:
          $event = ':collision:';
          break;
        case 5:
          $event = '';
          break;
        case 6:
          $event = ':point_right:';
          break;
        // classement par équipe
        case 7:
          $event = ':bar_chart:';
          break;
        // sprint intermédiaire
        case 8:
          $event = ':point_right:';
          break;
        // moyenne km/h
        case 9:
          $event = ':signal_strength:';
          break;
        // sommet
        case 10:
          $event = ':mount_fuji:';
          break;
        // sommet
        case 11:
          $event = ':mount_fuji:';
          break;
        // sommet
        case 12:
          $event = ':mount_fuji:';
          break;
        // sommet
        case 13:
          $event = ':mount_fuji:';
          break;
        // sommet
        case 14:
          $event = ':mount_fuji:';
          break;
        // accélération / attaque
        case 15:
          $event = ':dash:';
          break;
        case 16:
          $event = '';
          break;
        // point sur le peleton
        case 17:
          $event = ':busts_in_silhouette:';
          break;
        // arrêt mécanique
        case 18:
          $event = ':wrench:';
          break;
        // point km/h
        case 19:
          $event = ':signal_strength:';
          break;
        // point sur un écart
        case 20:
          $event = ':point_right:';
          break;
        // point sur les derniers kilomètres
        case 21:
          $event = ':point_right:';
          break;
        // point sur l'homme de tête
        case 22:
          $event = ':muscle:';
          break;
        // point sur les hommes de tête
        case 23:
          $event = ':muscle:';
          break;
        // point sur les hommes de tête
        case 24:
          $event = ':muscle:';
          break;
        // ascension
        case 25:
          $event = ':mountain_bicyclist:';
          break;
        // abandon
        case 26:
          $event = ':no_good:';
          break;
        case 27:
          $event = '';
          break;
        case 28:
          $event = '';
          break;
        case 29:
          $event = '';
          break;
        case 30:
          $event = '';
          break;
        // le top 5
        case 31:
          $event = ':bar_chart:';
          break;
        // victoire
        case 32:
          $event = ':dart:';
          break;
        // prix Antargaz de la combativité
        case 33:
          $event = ':metal:';
          break;
        // Sous la Flamme Rouge
        case 34:
          $event = ':fire:';
          break;
        // maillot à pois
        case 35:
          $event = ':bicyclist:';
          break;
        // maillot vert
        case 36:
          $event = ':bicyclist:';
          break;
        // maillot blanc
        case 37:
          $event = ':bicyclist:';
          break;
        // maillot jaune
        case 38:
          $event = ':bicyclist:';
          break;
        // prix de la combativité
        case 39:
          $event = ':metal:';
          break;
        // départ
        case 40:
          $event = ':checkered_flag:';
          break;
        // lu dans la presse
        case 41:
          $event = ':newspaper:';
          break;
        // interview
        case 42:
          $event = ':microphone:';
          break;
        // diverse stats
        case 43:
          $event = ':point_up:';
          break;
        // point historique
        case 44:
          $event = ':book:';
          break;
        // anniversaire
        case 45:
          $event = ':birthday:';
          break;
        // Changement de vélo
        case 46:
          $event = ':construction:';
          break;
        case 48:
          $event = '';
          break;
      }

      // in case of unknown event
      if ('' == $event)
      {
        var_dump($post['e']);
      }
    }

    // extra space for emoji
    $event .= $event ? ' ' : '';

    $h = sprintf('%02d', floor($post['s']/3600));
    $m = sprintf('%02d', floor(($post['s'] - 3600*$h) / 60));

    $date = $h.':'.$m;
    if ('fr' == LANG)
    {
      $date = $h.'h'.$m;
    }

    postToSlack($event.''.$post['t'].' – _'.$date.'_', $post['b']);
  }
}

file_put_contents($dbFile, json_encode($db));
