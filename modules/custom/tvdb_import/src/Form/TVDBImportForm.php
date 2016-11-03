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

    // Serie ID
    $form['tvdb_import']['serie_id'] = array(
        '#type' => 'number',
        '#title' => t('ID'),
        '#maxlength' => 20,
        '#required' => TRUE
    );

    // Custom fields: RO Title, RO Description, Custom Poster and Custom Background
    $form['tvdb_import']['custom'] = array(
      '#type' => 'details',
      '#title' => t('Custom'),
      '#description' => strtoupper(t('Romanian translations and custom images.')),
      '#open' => FALSE,
        'title_ro' => array(
          '#type' => 'textfield',
          '#description' => t('Add Romanian title if available.'),
          '#title' => t('RO Title'),
          '#maxlength' => 255,
          '#required' => FALSE
        ),
        'description_ro' => array(
          '#type' => 'textarea',
          '#rows' => 10,
          '#title' => t('RO Description'),
          '#description' => t('Add Romanian description if available.'),
          '#maxlength' => 1020,
          '#required' => FALSE
        ),
        'serie_poster' => array(
          '#type' => 'url',
          '#description' => '<p><strong>' . t('Allowed formats: JPG, JPEG, PNG, GIF.') . '</strong></p><p><i>' . t('Poster will be auto-imported from TVDB if left empty.') . '</i></p>',
          '#title' => t('Poster Image'),
          '#maxlength' => 1020,
          '#required' => FALSE
        ),
        'serie_background' => array(
          '#type' => 'url',
          '#description' => '<p><strong>' . t('Allowed formats: JPG, JPEG, PNG, GIF.') . '</strong></p><p><i>' . t('Background will be auto-imported from TVDB if left empty.') . '</i></p>',
          '#title' => t('Background Image'),
          '#maxlength' => 1020,
          '#required' => FALSE
        ),
    );

    // Add submit Handler
    $form['tvdb_import']['actions'] = array('#type' => 'actions');
    $form['tvdb_import']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Add serie'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $TVDB = new TVDBImport;

    $id = $form_state->getValue('serie_id');
    $poster = $form_state->getValue('serie_poster');
    $background = $form_state->getValue('serie_background');

    $response = $TVDB->get_serie($id);
    $data = $response->data;

    //check if there is a response
    if (is_null($response) || empty($response)) {
      $form_state->setErrorByName('serie_id', $this->t('TVDB Error: Could not get a response'));
    }
    //check for errors
    else if (isset($response->Error) && !empty($response->Error)) {
      $form_state->setErrorByName('serie_id', t('TVDB Error: ') . $response->Error);
    }
    //check if serie already exists
    else if ($TVDB->check_existing_serie($id) != 0) {
      //$form_state->setErrorByName('serie_id', $this->t('"@title" already exists', array('@title' => $data->seriesName)));
    }
    else {
      $form_state->setValue('data', $data);
    }

    //check if poster and background are valid images
    if (isset($poster) && !empty($poster) &&  !$this->is_image($poster)) {
      $form_state->setErrorByName('serie_poster', t('Poster is not an valid image'));
    }
    if (isset($background) && !empty($background) && !$this->is_image($background)) {
      $form_state->setErrorByName('serie_background', t('Background is not an valid image'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

      // get serie id
      $id = $form_state->getValue('serie_id');
      $data = $form_state->getValue('data');
      
      //get custom fields
      $custom_fields = array (
          'title_ro' => $form_state->getValue('title_ro'),
          'description_ro' => $form_state->getValue('description_ro'),
          'poster' => $form_state->getValue('serie_poster'),
          'background' => $form_state->getValue('serie_background'),
      );
      
      //setting up batch
      $batch = array(
        'title' => t('Importing @serie', array('@serie' => $data->seriesName)),
        'operations' => array(
          array(
            'Drupal\tvdb_import\Form\TVDBImportForm::form_progress_add_serie',
              array($id, $custom_fields)
          ),
          array(
            'Drupal\tvdb_import\Form\TVDBImportForm::form_progress_add_episodes',
            array($id)
          )
        ),
        'init_message' => t('Getting ready...'),
        'progress_message' => t('Operation @current out of @total.'),
        'error_message' => t('TVDB Import has encountered an error.'),
        'finished' => 'Drupal\tvdb_import\Form\TVDBImportForm::form_progress_end',
        'file' => drupal_get_path('module', 'tvdb_import') . '/src/TVDBImport',
      );
//      $TVDB = new TVDBImport;
//      $TVDB->add_serie($id, $custom_fields, '');
      //start import
      batch_set($batch);
  }

  /*
   *  operation to add serie
   */
  public static function form_progress_add_serie($id, $custom_fields, &$context) {
    $TVDB = new TVDBImport;
    $TVDB->add_serie($id, $custom_fields, $context);
  }
  
  /*
   *  operation to add episodes
   */
  public static function form_progress_add_episodes($id, &$context) {
    $TVDB = new TVDBImport;
    $TVDB->add_episodes($id, $context);
  }
  
  /*
   *  end batch process and set messages
   */
  public static function form_progress_end($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results['episodes']),
        'Added one episode.', 'Added @count episodes.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

  /*
   *  Check if the url in custom fields is an image
   */
  private function is_image($url) {
    $url_headers=get_headers($url, 1);
    if(isset($url_headers['Content-Type'])){
      $type=strtolower($url_headers['Content-Type']);
      $valid_image_type=array();
      $valid_image_type['image/png']='';
      $valid_image_type['image/jpg']='';
      $valid_image_type['image/jpeg']='';
      $valid_image_type['image/gif']='';

      if(isset($valid_image_type[$type])){
        return TRUE;
      }
    }
    return FALSE;
  }
}