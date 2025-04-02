<?php

$OPT['url']['production'] = 'https://www.tropipay.com';
$OPT['url']['develop'] = 'https://tropipay-dev.herokuapp.com';
//$OPT['url']['develop'] = 'https://www.buengolpe.com/pruebamodulo';
$OPT['url']['local'] = 'http://localhost:3001';
$OPT['url']['default'] = $OPT['url']['develop'];

$OPT['login']['default'] = '/api/v3/access/token';
$OPT['paylink']['default'] = '/api/v3/paymentcards';
$OPT['loadpayments']['default'] = '/api/loadPayments';
$OPT['profile']['default'] = '/api/v3/users/profile';

return $OPT;
