<?php

$OPT['url']['production'] = 'https://www.tropipay.com';
$OPT['url']['develop'] = 'https://tropipay-dev.herokuapp.com';
$OPT['url']['local'] = 'http://localhost:3001';
$OPT['url']['default'] = $OPT['url']['develop'];

$OPT['login']['default'] = '/api/v2/access/token';
$OPT['paylink']['default'] = '/api/v2/paymentcards';

return $OPT;
