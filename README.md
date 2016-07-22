# Tour de France Slack Bot

TourDeFranceBot will notify a Slack channel/group for every stage during the Tour de France 2016.

It uses the unofficial letour.fr json API (the one used for their mobile app iOS/Android).

It will post a message :
  - when a stage will start (with info about it + map)
  - every telegrams from the feed (could be too verbose..)

### Preview

Here is a preview of the Le Touquet-Paris-Plage - Lille Métropole stage (from 2014).

![tourdefrance-slack-bot sample1](http://i.imgur.com/XV0SCTW.png)

----

![tourdefrance-slack-bot sample2](http://i.imgur.com/JPi6eXo.png)

### Requirements

  - PHP >= 5.3
  - You need a token from Slack:
    - Jump at https://api.slack.com/docs/oauth-test-tokens (you have to login)
    - and you will find your token.

### Installation

  - Clone this repo
  - Set up a cron to run every minute:

  ````
  * * * * * cd /path/to/folder && php tourDeFranceNotifier.php >> tourDeFranceNotifier.log
  ````

### Translation

Everything can be posted in 4 differents languages (de, en, es, fr). Spanish & Deutsch need to be done.

### Side notes

The code is ugly but it works ©
