<?php

namespace Drupal\tvdb_import;

use Drupal\tvdb_import\TVDBConnect;
use Drupal\taxonomy\Entity\Term;

class TVDBImport extends TVDBConnect {

  var $image_url = 'https://thetvdb.com/banners/';

  public function __construct() {
    $this->TVDB = new TVDBLogin;
    $this->url = $this->TVDB->config->get('api_url');
    $this->token = $this->TVDB->get_current_token();
  }

  public function get_serie($id) {
    $url = $this->url . '/series/' . $id;
    $response = $this->curl_get($url, $this->token);
    return $response;
  }

  public function get_episode($id) {
    $url = $this->url . '/episodes/' . $id;
    $response = $this->curl_get($url, $this->token);
    return $response;
  }

  public function get_serie_episodes($id) {
    $url = $this->url . '/series/' . $id . '/episodes';
    $response = $this->curl_get($url, $this->token);
    $episodes = array();
    foreach ($response->data as $episode) {
      $episodes[] = $episode->id;
    }
    return $episodes;
  }

  private function get_images($id, $type) {
    $url = $this->url . '/series/' . $id . '/images/query?keyType=' . $type;
    $response = $this->curl_get($url, $this->token);
    return $response;
  }

  private function get_actors($id) {
    $url = $this->url . '/series/' . $id . '/actors';
    $response = $this->curl_get($url, $this->token);
    return $response;
  }

  public function add_serie($id, $data, $custom_fields, $context) {

    $context = $this->process_serie($id, $data, $custom_fields, $context);
    
    //If all processes ended, create the node
    if ($context['sandbox']['progress'] == $context['sandbox']['max']) {
      //get the node
      $details = $context['sandbox']['node'];
      
      // set up results for creating finish message
      if (isset($details['title']) && !empty($details['title'])) {
        $context['results']['serie'] = $details['title'];
      }
      if (isset($details['tvdb_genre']) && !empty($details['tvdb_genre'])) {
        $context['results']['genres'] = count($details['tvdb_genre']);
      }
      if (isset($details['tvdb_actors']) && !empty($details['tvdb_actors'])) {
        $context['results']['actors'] = count($details['tvdb_actors']);
      }
      if (isset($details['tvdb_posters']) && !empty($details['tvdb_posters'])) {
        $context['results']['posters'] = count($details['tvdb_posters']);
      }
      if (isset($details['tvdb_fanart']) && !empty($details['tvdb_fanart'])) {
        $context['results']['fanart'] = count($details['tvdb_fanart']);
      }

      // save node 
      $node = entity_create('node', $details);
      $node->save();
    }
    
    return $context;
  }

  public function add_episodes($id, $data, $context) {
    
    //create batches of 5
    $batch = range($context['sandbox']['progress'], $context['sandbox']['progress'] + 4);
    foreach ($batch as $value) {
      //get episode details
      $episode = $this->get_episode($data[$value]);
      if(isset($episode) && !empty($episode)) {
        // process the episde
        $details = $this->process_episode($episode->data, $id);
        if (!empty($details)) {
          //save episode
          $node = entity_create('node', $details);
          $node->save();
        }
      }
      // increase progress    
      $context['sandbox']['progress']++;
    }
    
    return $context;
  }

  public function add_actors($id) {
    $data = $this->get_actors($id)->data;
    if (isset($data) && !empty($data)) {
      usort($data, function($a, $b) {
        return $a->sortOrder - $b->sortOrder;
      });
      $actors = array();
      foreach ($data as $actor) {
        $this->process_actor($actor);
        $actors[] = $actor->id;
      }
      return $actors;
    }
    else {
      return '';
    }
  }

  public function add_genre($genre) {
    $taxonomy = array(
      'name' => $genre,
      'vid' => 'genres'
    );
    Term::create($taxonomy)->save();
  }
  
  public function add_network($network) {
    $taxonomy = array(
      'name' => $network,
      'vid' => 'networks'
    );
    Term::create($taxonomy)->save();
  }

  public function check_existing_serie($id) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'serie', '=')
      ->condition('tvdb_id', $id, '=');
    $count = $query->count()->execute();
    if ($count > 0) {
      return TRUE;
    }
    return FALSE;
  }

  public function check_existing_episode($id) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'episode', '=')
      ->condition('tvdb_episode_id', $id, '=');
    $count = $query->count()->execute();
    if ($count > 0) {
      return TRUE;
    }
    return FALSE;
  }

  public function get_serie_node_id($id) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'serie', '=')
      ->condition('tvdb_id', $id, '=');
    return $query->execute();
  }

  private function process_serie($id, $data, $custom_fields, $context) {
    
    // check if correct ID
    if (isset($data->id) && !empty($data->id)) {
      
      // get images
      $images_poster = $this->get_images($id, 'poster')->data;
      $images_fanart = $this->get_images($id, 'fanart')->data;
      
      // STEP 1 - Start importing serie, 10% progress
      if ($context['sandbox']['progress'] == 0) {
        $context['sandbox']['progress'] = 10;
        $context['message'] = t('Adding details...');
 
        $context['sandbox']['node'] = array(
          'type' => 'serie', 
          'uid' => 1,
          'status' => 1,
        );

        // id
        $context['sandbox']['node']['tvdb_id'] = $data->id;
        // title
        if (isset($data->seriesName) && !empty($data->seriesName)) {
          $context['sandbox']['node']['title'] = $data->seriesName;
        }
        // body
        if (isset($data->overview) && !empty($data->overview)) {
          $context['sandbox']['node']['body'] = $data->overview;
        }
        // runtime, airsDayOfWeek, airsTime, rating, imdbId, lastUpdated, firstAired
        $basic_fields = array('runtime', 'airsDayOfWeek', 'airsTime', 'rating', 'imdbId', 'lastUpdated', 'firstAired');
        foreach ($basic_fields as $field) {
          if (isset($data->$field) && !empty($data->$field)) {
            $context['sandbox']['node']['tvdb_' . strtolower($field)] = $data->$field;
          }
        }
        //status 
        if (isset($data->status) && !empty($data->status)) {
          if($data->status == "Continuing") {
            $context['sandbox']['node']['tvdb_status'] = 1;
          }
          else {
            $context['sandbox']['node']['tvdb_status'] = 0;
          }
        }
        //aliases
        if (isset($data->aliases) && !empty($data->aliases)) {
          $context['sandbox']['node']['tvdb_aliases'] = $this->process_multiple_field_values($data->aliases);
        }
        
        //RO title, RO description
        if (!empty($custom_fields['title_ro'])) {
          $context['sandbox']['node']['tvdb_title_ro'] = $custom_fields['title_ro'];
        }
        if (!empty($custom_fields['description_ro'])) {
          $context['sandbox']['node']['tvdb_body_ro'] = $custom_fields['description_ro'];
        }
        
        //Main Poster
        if (!empty($custom_fields['poster'])) {
          $context['sandbox']['node']['tvdb_poster'] = $this->process_single_image($data->seriesName, $custom_fields['poster'], 'poster');
        } 
        elseif(!empty($images_poster)) {
          $context['sandbox']['node']['tvdb_poster'] = $this->process_single_image($data->seriesName, $this->image_url . $images_poster[0]->fileName, 'poster');
        }
        
        //Main Background
        if (!empty($custom_fields['background'])) {
          $context['sandbox']['node']['tvdb_background'] = $this->process_single_image($data->seriesName, $custom_fields['background'], 'fanart');
        } 
        elseif (!empty($images_fanart)) {
          $context['sandbox']['node']['tvdb_background'] = $this->process_single_image($data->seriesName, $this->image_url . $images_fanart[0]->fileName, 'fanart');
        }
        
        //end batch
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
        return $context;
      }
      
      // STEP 2 - Start adding genres, 20% progress
      if ($context['sandbox']['progress'] == 10) {
        $context['sandbox']['progress'] = 20;
        $context['message'] = t('Adding genres...');
        if (isset($data->genre) && !empty($data->genre)) {
          $context['sandbox']['node']['tvdb_genre'] = $this->process_multiple_taxonomy_terms($data->genre, 'genres');
        }
        
        //end batch
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
        return $context;
      }
      
      // STEP 2 - Start adding network, 30% progress
      if ($context['sandbox']['progress'] == 20) {
        $context['sandbox']['progress'] = 30;
        $context['message'] = t('Adding network...');
        if (isset($data->network) && !empty($data->network)) {
          $context['sandbox']['node']['tvdb_network'] = $this->process_network($data->network);
        }
        
        //end batch
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
        return $context;
      }
      
      // STEP 3 - Start adding actors, 50% progress
      if ($context['sandbox']['progress'] == 30) {
          $actors = $this->add_actors($id);
          
          $context['sandbox']['progress'] = 50;
          $context['message'] = t('Adding actors...');
          
          if (isset($actors) && !empty($actors)) {
            $context['sandbox']['node']['tvdb_actors'] = $this->process_multiple_taxonomy_terms($actors, 'actors');
          }
          
          //end batch
          $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
          return $context;
      }
      
      // STEP 4 - Start adding fanart, 75% progress
      if ($context['sandbox']['progress'] == 50) {
        $context['sandbox']['progress'] = 75;
        $context['message'] = t('Adding fanart...');
            
        
        if (!empty($images_fanart)) {
          $context['sandbox']['node']['tvdb_fanart'] = $this->process_multiple_images($images_fanart, $data);
        }
        
        //end batch
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
        return $context;
      }
      
      // STEP 5 - Start adding posters, 99% progress
      if ($context['sandbox']['progress'] == 75) {
        $context['sandbox']['progress'] = 99;
        $context['message'] = t('Adding posters...');
        
        if (!empty($images_poster)) {
          $context['sandbox']['node']['tvdb_posters'] = $this->process_multiple_images($images_poster, $data);
        }
        
        //end batch
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
        return $context;
      }
      
      // Finish serie import
      if ($context['sandbox']['progress'] == 99) {
        $context['sandbox']['progress'] = 100;
        $context['message'] = t('Creating node...');
        return $context;
      }
    }
  }

  public function process_episode($data, $id) {
    if (isset($data->id) && !empty($data->id)) {
      if ($this->check_existing_episode($data->id) || !$this->check_existing_serie($data->seriesId)) {
        return '';
      }
      
      $node = array(
        'type' => 'episode', 
        'uid' => 1,
        'status' => 1,
      );

      /*
       * Map Values
       */

      // id
      $node['tvdb_episode_id'] = $data->id;
      // title
      if (isset($data->episodeName) && !empty($data->episodeName)) {
        $node['title'] = $data->episodeName;
      } 
      else {
        $node['title'] = t('Episode @number', array('@number' => $data->airedEpisodeNumber));
      }
      // body
      if (isset($data->overview) && !empty($data->overview)) {
        $node['body'] = $data->overview;
      }
      // lastUpdated
      if (isset($data->lastUpdated) && !empty($data->lastUpdated)) {
        $node['tvdb_lastupdated'] = $data->lastUpdated;
      }
      //seriesId
      if(isset($data->seriesId) && !empty($data->seriesId)) {
        $node['tvdb_serie_id'] = $this->get_serie_node_id($data->seriesId);
      }
      // airedSeason, airedEpisodeNumber, firstAired
      $basic_fields = array('airedSeason', 'airedEpisodeNumber', 'firstAired');
      foreach ($basic_fields as $field) {
        if (isset($data->$field) && !empty($data->$field)) {
          $node['tvdb_' . strtolower($field)] = $data->$field;
        }
      }
      //directors, writters, gueststars
      $multiple_fields = array('directors', 'writers', 'guestStars');
      foreach ($multiple_fields as $field) {
        if (isset($data->$field) && !empty($data->$field)) {
          $node['tvdb_' . strtolower($field)] = $this->process_multiple_field_values($data->$field);
        }
      }
      //Cover
      if (isset($data->filename) && !empty($data->filename)) {
        $node['tvdb_cover'] = $this->process_single_image($data->episodeName, $this->image_url . $data->filename, 'episodes');
      }
      return $node;
    }
  } 

  private function process_actor($actor) {
    if (isset($actor->id) && !empty($actor->id)) {
      if (taxonomy_term_load($actor->id)) {
        return FALSE;
      }
      $taxonomy = array(
        'name' => $actor->name,
        'tid' => $actor->id,
        'vid' => 'actors'
      );
      if (isset($actor->seriesId) && !empty($actor->seriesId)) {
        $taxonomy['tvdb_serie_id'] = $actor->seriesId;
      }

      if (isset($actor->role) && !empty($actor->role)) {
        $taxonomy['tvdb_role'] = $actor->role;
      }

      if (isset($actor->image) && !empty($actor->image)) {
        $taxonomy['tvdb_author_image'] = $this->process_single_image($actor->name, $this->image_url . $actor->image, 'actors');
      }
      $term =  Term::create($taxonomy)->save();
    }
    else {
      return FALSE;
    }
  }
  
  private function process_network($value) {
    $network = array_keys(taxonomy_term_load_multiple_by_name($value, 'networks'));
    if (is_null($network) || empty($network)) {
      $this->add_network($value);
      $network = array_keys(taxonomy_term_load_multiple_by_name($value, 'networks'));
    }
    return $network[0];
  }

  public function process_single_image($name, $link, $type) {
    if ($type == 'actors' || $type == 'episodes') {
      if (strlen($name) > 30) {
        $name = substr($name, 0, 25);
      }
      $folder = 'public://images/' . $type . '/';
    }
    else {
      $folder = 'public://images/series/' . $type . '/';
    }
    $node = $this->upload_image($name, $link, $folder);
    return $node;
  }

  public function process_multiple_field_values($data) {
    $node = array();
    foreach ($data as $key => $value) {
      $node[] = $data[$key];
    }
    return $node;
  }

  public function process_multiple_taxonomy_terms($taxonomies, $type) {
    $node = array();
    foreach ($taxonomies as $value) {
      switch($type) {
        case 'genres':
          $genre = array_keys(taxonomy_term_load_multiple_by_name($value, $type));
          if (is_null($genre) || empty($genre)) {
            $this->add_genre($value);
            $genre = array_keys(taxonomy_term_load_multiple_by_name($value, $type));
          }
          $node[] = $genre[0];
          break;
        case 'actors':
          $node[] = $value;
          break;
      }
    }
    return $node;
  }
  
  public function process_multiple_images($data, $serie) {
    $name = $serie->seriesName;
    $node = array();
    foreach ($data as $value) {
      $path = $value->fileName;
      $folder = 'public://images/series/' . $value->keyType . '/';
      $node[] = $this->upload_image($name, $this->image_url . $path, $folder);
    }
    return $node;
  }

  public function upload_image($name, $path, $folder) {
    $info = pathinfo($path);
    $name = str_replace(' ', '-', $name) . '-' . $info['filename'];
    $image = file_get_contents($path);
    if ($image != FALSE) {
      $file = file_save_data($image, $folder . $name . '.' . $info['extension'], FILE_EXISTS_RENAME);
      if ($file != FALSE) {
        $image_field = array (
          'target_id' => $file->id(),
        );
        return $image_field;
      }
    }
    else {
      return array();
    }
  }
}