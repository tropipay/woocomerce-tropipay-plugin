<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once __DIR__ . "/HttpClient.php";

class TropiPay 
{
    public $srv;
    public $opt;
    public $token;
    public $clientId;
    public $clientSecret;
    public $enviroment;

    public function __construct($clientId, $clientSecret, $enviroment = 'production'){
        $this->srv = new Tropipay_Http_Client(); 
        $this->opt = include __DIR__ . "/configure.php";
        $this->token = null;

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->enviroment = $enviroment;
    }

    public function get($key) {
        if(isset($this->opt[$key][$this->enviroment])) {
            return $this->opt[$key][$this->enviroment];
        }else if (isset($this->opt[$key]['default'])){
            return $this->opt[$key]['default'];
        }
        return null;
    }

    public function log($data, $type='error'){
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log("TropiPay SDK [$type] => " . json_encode($data));
    }

    public function login($clientId, $clientSecret) {
        $url = $this->get('url') . $this->get('login');
        $res = $this->srv->tpost($url, array(
            "grant_type"=>"client_credentials",
            "client_id"=> $clientId,
            "client_secret"=> $clientSecret,
            "scope"=>"ALLOW_GET_PROFILE_DATA ALLOW_PAYMENT_IN ALLOW_EXTERNAL_CHARGE ALLOW_GET_BALANCE"
        ));
        if(!$res) return null;
        if(!$res['error'] && isset($res['data']['access_token'])){
            $this->token = $res['data']['access_token'];
        }
        if($res['error']) {
            $this->log($res['error']);
        }
        if(isset($res['data']['error'])) {
            $this->log($res['data']['error']);
        }   
        return $this->token;
    }

    public function getAccounts($type = 1) {
        if(!$this->token) {
            $this->login($this->clientId, $this->clientSecret);
        }
        if(!$this->token) {
            return array(
                'error' => 'No se pudo iniciar sesión en TropiPay. Las credenciales de API (Client ID / Client Secret) introducidas son inválidas.',
                'data'  => null
            );
        }
        $url = $this->get('url') . $this->get('accounts');
        $res = $this->srv->get($url, array("type" => $type), array(
            "content-type: application/json",
            "authorization: Bearer " . $this->token
        ));
        if($res && $res['error']){
            $this->log($res['error']);
        }
        return $res;
    }

    public function getProfile() {
        if(!$this->token) {
            $this->login($this->clientId, $this->clientSecret);
        }
        if(!$this->token) {
            return array(
                'error' => 'No se pudo iniciar sesión en TropiPay. Las credenciales de API (Client ID / Client Secret) introducidas son inválidas.',
                'data'  => null
            );
        }
        $url = $this->get('url') . $this->get('profile');
        $res = $this->srv->get($url, array(), array(
            "content-type: application/json",
            "authorization: Bearer " . $this->token
        ));
        if($res && $res['error']){
            $this->log($res['error']);
        }
        return $res;
    }

    public function createPaylink($data){
        if(!$this->token) {
            $this->login($this->clientId, $this->clientSecret);
        }
        $url = $this->get('url') . $this->get('paylink');
        $res = $this->srv->tpost($url, $data, array(
            "content-type: application/json",
            "authorization: Bearer " . $this->token
        ));
        if($res && $res['error']){
            $this->log($res['error']);
        }
        return $res;
    }

    public function loadPayments($data) {
        $url = $this->get('url') . $this->get('loadpayments');
        $res = $this->srv->get($url . '/' . $data,array());
        if($res && $res['error']){
            $this->log($res['error']);
        }
        return $res;
    }
    
}