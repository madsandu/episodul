<?php

/**
 * @file
 * Contains \Drupal\tvdb_import\Form\ComproCustomForm.
 */
 
namespace Drupal\tvdb_import\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

use Drupal\tvdb_import\TVDBLogin;
 
/**
 * Get current status of TVDB Connection and Refresh Token 
 */


class TVDBStatusForm extends ConfigFormBase {
    
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->TVDB = new TVDBLogin;
    parent::__construct($config_factory);
  }

  public function getFormId() {
    return 'tvdb_status_admin_form';
  }

  protected function getEditableConfigNames() {
    return ['config.tvdb_status'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    
    // Get current token
    $token = $this->TVDB->get_current_token();

    // Check the current status
    if($this->TVDB->check_status()) {
      $status = 'Connected';
    } 
    else {
      $status = 'Offline';
    }

    // create form
    $form['tvdb_status'] = array(
      'status' => array(
        '#type' => 'textfield',
        '#title' => t('Status'),
        '#required' => FALSE,
        '#attributes' => array('readonly' => 'readonly'),
        '#maxlength' => 256,
        '#default_value' => $status
      ),
      'token' => array(
        '#type' => 'textarea',
        '#title' => t('Token'),
        '#required' => FALSE,
        '#attributes' => array('readonly' => 'readonly'),
        '#maxlength' => 768,
        '#default_value' => $token
      )
    );

    // add "Refresh Token" submit handler
    $form['tvdb_status']['actions'] = array('#type' => 'actions');
    $form['tvdb_status']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Refresh Token'),
      '#button_type' => 'primary'
    );
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {        
    parent::submitForm($form, $form_state);

    $new_token = $this->TVDB->refresh_token();
    $this->config('config.tvdb_status')
      ->set('token', $new_token)
      ->save();
  }
}