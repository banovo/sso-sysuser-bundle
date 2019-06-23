<?php

namespace Banovo\SSOSysuserBundle;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use GuzzleHttp\Client;

class Sysuser
{

    private $config = [];
    private $access_token = false;
    private $refresh_token = false;
    private $exp_timestamp = 0;

    public function __construct($banovo_sso_sysuser_config) {
        $this->config = $banovo_sso_sysuser_config;
    }

    public function getToken($credential_scope) {

        if ($this->access_token && $this->isExpired()){
            $this->invalidateSession();
        }

        if(!$this->access_token){
            $this->fetchToken($this->getCredentials($credential_scope));
        }

        $this->isExpired();

        return "Bearer $this->access_token";
    }

    private function isExpired()
    {

        if(time() >= $this->exp_timestamp - $this->config['sso_configuration']['token_expires_deduct_seconds']) {
            return true;
        }

        return false;
    }

    private function getCredentials($credentials_scope) {

        if(!array_key_exists($credentials_scope, $this->config['sysusers_configuration'])){
            throw new Exception("Credentials Scope not configured in sysusers_configuration");
        }

        $credentials = [];
        $credentials["username"] = key($this->config['sysusers_configuration'][$credentials_scope]);
        $credentials["password"] = current($this->config['sysusers_configuration'][$credentials_scope]);

        return $credentials;
    }

    private function fetchToken($credentials) {

        $client = new Client();

        $response = $client->request('POST', $this->config['sso_configuration']['token_endpoint'],
            [
                'form_params' => [
                    "grant_type" =>  "password",
                    "client_id"     =>  $this->config['sso_configuration']['client_id'],
                    "client_secret"  =>  $this->config['sso_configuration']['client_secret'],
                    "username"   =>  $credentials['username'],
                    "password"   =>  $credentials['password'],
                ]
            ]
        );

        $code = $response->getStatusCode();
        $reason = $response->getReasonPhrase();

        if (200 != $code){
            throw new \Exception($reason, $code);
        }

        $body = json_decode($response->getBody());

        # Don't touch this!
        if(!in_array('APP_SYSTEM', json_decode(base64_decode(explode('.', $body->access_token)[1]))->groups)){
            throw new \Exception("Configured sysuser is no member of APP_SYSTEM");
        };

        $this->exp_timestamp = json_decode(base64_decode(explode('.', $body->access_token)[1]))->exp;
        $this->access_token = $body->access_token;
        $this->refresh_token = $body->refresh_token;

    }

    private function invalidateSession()
    {

        if (!$this->refresh_token){
            return;
        }

        $client = new Client();
        $response = $client->request('POST', $this->config['sso_configuration']['logout_endpoint'],
            [
                'form_params' => [
                    "client_id"     =>  $this->config['sso_configuration']['client_id'],
                    "client_secret" =>  $this->config['sso_configuration']['client_secret'],
                    "refresh_token" =>  $this->refresh_token,
                ]
            ]
        );

        if(204 != $response->getStatusCode()){
            throw new \Exception($reason, $code);
        }

        $this->access_token = false;
        $this->refresh_token = false;

    }

    public function __destruct()
    {
        $this->invalidateSession();
    }

}
