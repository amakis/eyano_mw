<?php
namespace App\Application\Libs;

use Symfony\Component\Yaml\Yaml;
use GuzzleHttp\Client;

class ZkConnector{

    protected $username;
    protected $pwd;
    protected $url;

    protected $token;

    function __construct($config)
    {
        $this->username = $config['username'];
        $this->pwd = $config['pwd'];
        $this->url = $config['url'];

        $this->authenticate();
        //print_r($this->config);
    }

    function authenticate(){
        
        $client = new Client([
            'base_uri' => $this->url,
        ]);
        try {
            $response = $client->request('POST', '/jwt-api-token-auth/', [
                'json' => [
                    'username' => $this->username,
                    'password' => $this->pwd
                ],
                'headers' => [
                    'Content-Type'     => 'application/json'
                ]
            ]);

            if($response->getStatusCode() == 200){

                $body = $response->getBody();
                $arr_body = json_decode($body);
                $this->token =  $arr_body->token;
                //print_r($this->zkToken); 
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            print_r($e->getMessage());
        }
    }

    function getEmployees(){
        
        $client = new Client([
            'base_uri' => $this->url,
        ]);
        try {
            $response = $client->request('GET', '/personnel/api/employees/?page_size=1000', [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'JWT '.$this->token
                ]
            ]);

            if($response->getStatusCode() == 200){

                $body = $response->getBody();
                $arr_body = json_decode($body);

                return $arr_body; 
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            print_r($e->getMessage());
        }
    }

}