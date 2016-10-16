<?php

/**
 * @file
 * Contains \Drupal\tvdb_import\Form\ComproCustomForm.
 */
 
namespace Drupal\tvdb_import\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
 
/**
 * Configure Credential for TVDB.
 */
class TVDBSettingsForm extends ConfigFormBase {
    public function __construct(ConfigFactoryInterface $config_factory) {
      parent::__construct($config_factory);
    }

    public function getFormId() {
      return 'tvdb_settings_admin_form';
    }

    protected function getEditableConfigNames() {
      return ['config.tvdb_settings'];
    }

    public function buildForm(array $form, FormStateInterface $form_state) {      
      $config = $this->config('config.tvdb_settings');
      $form['tvdb_settings']['credentials'] = array(
        '#type' => 'fieldgroup',
        '#title' => t('Credentials'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        'api_url' => array(
          '#type' => 'textfield',
          '#title' => t('URL'),
          '#maxlength' => 255,
          '#required' => TRUE,
          '#default_value' => $config->get('api_url')
        ),
        'api_key' => array(
          '#type' => 'textfield',
          '#title' => t('API Key'),
          '#maxlength' => 255,
          '#required' => TRUE,
          '#default_value' => $config->get('api_key')
        ),
        'username' => array(
          '#type' => 'textfield',
          '#title' => t('Username'),
          '#maxlength' => 255,
          '#required' => TRUE,
          '#default_value' => $config->get('username')
        ),
        'user_key' => array(
          '#type' => 'textfield',
          '#title' => t('User Key'),
          '#maxlength' => 255,
          '#required' => TRUE,
          '#default_value' => $config->get('user_key')
        ),
      );
      return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
      parent::submitForm($form, $form_state);
      $this->config('config.tvdb_settings')
        ->set('api_url', $form_state->getValue('api_url'))
        ->set('api_key', $form_state->getValue('api_key'))
        ->set('username', $form_state->getValue('username'))
        ->set('user_key', $form_state->getValue('user_key'))
        ->save();
    }
}