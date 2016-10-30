<?php

/**
 * @file
 * Contains \Drupal\tvdb_import\Form\ComproCustomForm.
 */
 
namespace Drupal\tvdb_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tvdb_import\TVDBImport;
 
/**
 * Configure Credential for TVDB.
 */


class TVDBImportForm extends FormBase {

    public function getFormId() {
      return 'tvdb_import_admin_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        
      $form['tvdb_import']['import'] = array(
        '#type' => 'fieldgroup',
        '#title' => t('Add new serie'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
            'serie_id' => array(
              '#type' => 'number',
              '#title' => t('Series ID'),
              '#maxlength' => 20,
              '#required' => TRUE
            ),
            'title_ro' => array(
              '#type' => 'textfield',
              '#description' => 'Add romanian title if available.',
              '#title' => t('RO Title'),
              '#maxlength' => 255,
              '#required' => FALSE
            ),
            'description_ro' => array(
              '#type' => 'textarea',
              '#rows' => 10,
              '#title' => t('RO Description'),
              '#description' => 'Add romanian description if available.',
              '#maxlength' => 1020,
              '#required' => FALSE
            ),
            'serie_poster' => array(
              '#type' => 'textfield',
              '#description' => 'Add link to a custom image. Poster will be auto-imported from TVDB if left empty',
              '#title' => t('Serie Custom Poster Image'),
              '#maxlength' => 1020,
              '#required' => FALSE
            ),
            'serie_background' => array(
              '#type' => 'textfield',
              '#description' => 'Add link to a custom image for a series. Background image will be auto-imported from TVDB if left empty',
              '#title' => t('Serie Custom background Image'),
              '#maxlength' => 1020,
              '#required' => FALSE
            ),
      );
      $form['tvdb_import']['actions'] = array('#type' => 'actions');
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Add serie'),
        '#button_type' => 'primary',
      );
      return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $id = $form_state->getValue('serie_id');
      
        $custom_fields = array (
            'title_ro' => $form_state->getValue('title_ro'),
            'description_ro' => $form_state->getValue('description_ro'),
            'poster' => $form_state->getValue('serie_poster'),
            'background' => $form_state->getValue('serie_background'),
        );
        
        $TVDB = new TVDBImport;
        $TVDB->add_serie($id, $custom_fields);
        
    }
}