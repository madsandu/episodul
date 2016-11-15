<?php

namespace Drupal\tvdb_import;
use Drupal\tvdb_import\TVDBConnect;

class TVDBLogin extends TVDBConnect {
    
  private function get_credentials() {
    $username = $this->config->get('username');
    $user_key = $this->config->get('user_key');
    $api_key = $this->config->get('api_key');

    $login_details = array(
      'apikey' => $api_key,
      'username' => $username,
      'userkey' =>  $user_key
    );
    return json_encode($login_details);
  }

  private function get_token() {
    $url = $this->config->get('api_url') . '/login';
    $credentials = $this->get_credentials();
    $response = $this->curl_post($url, $credentials);
    if (is_null($response) || empty($response->token)) {
      drupal_set_message(t('Oops! No response from TVDB'), 'error');
      return;
    }
    else {
      $token = $response->token;
      return $token;
    }
  }

  public function get_current_token() {
    $config = \Drupal::service('config.factory')->getEditable('config.tvdb_status');
    $token = $config->get('token');
    return $token;
  }

  public function refresh_token() {
    $new_token = $this->get_token();
    return $new_token;
  }

  public function check_status() {
    $id = '176941';
    $url = $this->config->get('api_url') . '/series/' . $id;
    $token = $this->get_current_token();
    $response = $this->curl_get($url, $token);
    if(isset($response->data) && !empty($response->data)) {
      return TRUE;
    } 
    elseif (isset($response->Error) && !empty($response->Error)) {
      //drupal_set_message('TVDB Error: @error', array('@error' => $response->Error), 'error');
      return FALSE;
    }
    else {
      return FALSE;
    }
  }
}
