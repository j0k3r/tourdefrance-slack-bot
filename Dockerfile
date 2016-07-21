FROM php

ADD . /opt

WORKDIR /opt

CMD while true;do php tourDeFranceNotifier.php;sleep 20;done
