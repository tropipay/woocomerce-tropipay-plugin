<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$tropipay_opt = array();

$tropipay_opt['url']['production'] = 'https://www.tropipay.com';
$tropipay_opt['url']['develop'] = 'https://sandbox.tropipay.me';
//$tropipay_opt['url']['develop'] = 'https://www.buengolpe.com/pruebamodulo';
$tropipay_opt['url']['local'] = 'http://localhost:3001';
$tropipay_opt['url']['default'] = $tropipay_opt['url']['develop'];

$tropipay_opt['login']['default'] = '/api/v3/access/token';
$tropipay_opt['paylink']['default'] = '/api/v3/paymentcards';
$tropipay_opt['loadpayments']['default'] = '/api/loadPayments';
$tropipay_opt['profile']['default'] = '/api/v3/users/profile';
$tropipay_opt['accounts']['default'] = '/api/v3/accounts';

return $tropipay_opt;
