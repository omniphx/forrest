<?php namespace Omniphx\Forrest\RequestServices;

use Omniphx\Forrest\Interfaces\RequestInterface;

class CurlClient implements RequestInterface {

    /**
     * [getRequest description]
     * @param  string $resource [description]
     * @param  string $version  [description]
     * @return object $response [description]
     */
    public function getRequest($url, $header=null){
        
        $curl = curl_init();

        if($header == null){
            $header = array();
        }

        curl_setopt_array($curl, array(
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_FAILONERROR => 1,
            CURLOPT_HTTPHEADER => $header
        ));
        
        $response = curl_exec($curl);

        if(!curl_exec($curl)){
            die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
        }

        curl_close($curl);
        
        // $this->log->info($response);
        
        $response = json_decode($response, true);

        return $response;
    }

    /**
     * [postRequest description]
     * @param  string $resource [description]
     * @param  array $postfields [description]
     * @return object $response [description]
     */
    public function postRequest($url, $postfields){
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_FAILONERROR => 1,
            CURLOPT_POST => count($postfields),
            CURLOPT_POSTFIELDS => $postfields
        ));
        
        $response = curl_exec($curl);

        if(!$response){
            die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
        }

        curl_close($curl);

        // $log = new Writer;
        // $this->log->info($response);
        
        $response = json_decode($response, true);

        return $response;
    }

}