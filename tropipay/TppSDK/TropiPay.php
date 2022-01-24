<?php
include __DIR__ . "/HttpClient.php";

class TropiPay 
{
    public function __construct($clientId, $clientSecret, $enviroment = 'production'){
        $this->srv = new HttpClient(); 
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
        echo "[$type] => " . print_r($data, true) . " | ";
    }

    public function login($clientId, $clientSecret) {
        $url = $this->get('url') . $this->get('login');
        $res = $this->srv->tpost($url, array(
            "grant_type"=>"client_credentials",
            "client_id"=> $clientId,
            "client_secret"=> $clientSecret,
            "scope"=>"ALLOW_GET_PROFILE_DATA ALLOW_PAYMENT_IN ALLOW_EXTERNAL_CHARGE"
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
    
}