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
 * Configure Credential for TVDB.
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
      $token = $this->TVDB->get_current_token();
      
      if($this->TVDB->check_status()) {
        $status = 'Connected';
      } 
      else {
        $status = 'Not connected';
      }
      
      $form['tvdb_status'] = array(
        '#type' => 'fieldgroup',
        '#title' => t('Current status'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
            'status' => array(
              '#type' => 'textfield',
              '#title' => t('Status'),
              '#required' => FALSE,
              '#attributes' => array('readonly' => 'readonly'),
              '#maxlength' => 256,
              '#default_value' => $status
            ),
            'token' => array(
              '#type' => 'textfield',
              '#title' => t('Token'),
              '#required' => FALSE,
              '#attributes' => array('readonly' => 'readonly'),
              '#maxlength' => 768,
              '#default_value' => $token
            )
      );
      
      $form['tvdb_status']['actions'] = array('#type' => 'actions');
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Refresh Token'),
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