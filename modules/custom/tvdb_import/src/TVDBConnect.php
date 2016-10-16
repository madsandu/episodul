<?php

namespace Drupal\tvdb_import;

class TVDBConnect {
    
    public function __construct() {
        $this->config = $this->get_settings_config();
    }
    
    public function get_settings_config() {
        return \Drupal::service('config.factory')->getEditable('config.tvdb_settings');
    }
    
    public function curl_post($url, $content) {
        $curl_header = array('Accept: application/json','Content-Type: application/json');
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");  
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_header);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        $json_result = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $response = json_decode($json_result);
        curl_close($curl);
        
        $result = $this->curl_validate($status, $response);
        return $result;
    }
    
    public function curl_get($url, $token, $language = 'en') {
        $curl_header = array('Accept: application/json','Content-Type: application/json');
        $curl_header[] = 'Authorization: Bearer ' . $token;
        $curl_header[] = 'Accept-Language:' . $language;
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");  
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_header);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        $json_result = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $response = json_decode($json_result);
        curl_close($curl);
        
        $result = $this->curl_validate($status, $response);
        return $result;
    }
    
    public function curl_validate($status, $response) {
        if ($status == 200) {
          return $response;
        } 
        else if (isset($result->error)) {
          drupal_set_message(t('Status: ') . $status . ' - ' . $response->error, 'error');
        }
        else {
          drupal_set_message('Something went wrong... Check TVDB status', 'error');
        }
    }
}
