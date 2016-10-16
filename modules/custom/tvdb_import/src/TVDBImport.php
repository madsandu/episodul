<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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
    
    private function get_serie($id) {
        $url = $this->url . '/series/' . $id;
        $response = $this->curl_get($url, $this->token);
        return $response;
    }
    
    private function get_episode($id) {
        $url = $this->url . '/episodes/' . $id;
        $response = $this->curl_get($url, $this->token);
        return $response;
    }
    
    private function get_serie_episodes($id) {
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
    
    public function add_serie($id, $custom_fields) {
        $details = $this->process_serie($id, $custom_fields);
        if (!empty($details)) {
            $node = entity_create('node', $details);
            $node->save();
            $this->add_episodes($id);
            drupal_set_message(t('Successfully added @title', array('@title' => $details['title'])), 'status');
        }
    }
    
    public function add_episodes($id) {
        $data = $this->get_serie_episodes($id);
        $count = 0;
        foreach ($data as $value) {
            $episode = $this->get_episode($value);
            $details = $this->process_episode($episode->data, $id);
            if (!empty($details)) {
                $node = entity_create('node', $details);
                $node->save();
                $count++;
            }
        }
        drupal_set_message(t('Added @count episodes', array('@count' => $count)), 'status');
    }
    
    public function add_actors($id) {
        $data = $this->get_actors($id)->data;
        if (isset($data) && !empty($data) && !is_null($data)) {
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
    
    private function check_existing_serie($id) {
        $query = \Drupal::entityQuery('node')
            ->condition('type', 'serie', '=')
            ->condition('tvdb_id', $id, '=');
        return $query->count()->execute();
    }
    
    private function check_existing_episode($id) {
        $query = \Drupal::entityQuery('node')
            ->condition('type', 'episode', '=')
            ->condition('nid', $id, '=');
        return $query->count()->execute();
    }
    
    public function get_serie_node_id($id) {
        $query = \Drupal::entityQuery('node')
            ->condition('type', 'serie', '=')
            ->condition('tvdb_id', $id, '=');
        return $query->execute();
    }
    
    private function process_serie($id, $custom_fields) {
        
        $data = $this->get_serie($id)->data;
        
        // check if correct ID
        if (isset($data->id) && !empty($data->id)) {
            $actors = $this->add_actors($id);
            $images_fanart = $this->get_images($id, 'fanart')->data;
            $images_poster = $this->get_images($id, 'poster')->data;
            
            //check if series already exists
            if ($this->check_existing_serie($data->id) != 0) {
                drupal_set_message(t('@title already exists', array('@title' => $data->seriesName)), 'error');
                return;
            }
            
            $node = array(
                'type' => 'serie', 
                'uid' => 1,
                'status' => 1,
            );
            
            /*
             * Map Values
             */
            
            // id
            $node['tvdb_id'] = $data->id;
            // title
            if (isset($data->seriesName) && !empty($data->seriesName)) {
                $node['title'] = $data->seriesName;
            }
            // body
            if (isset($data->overview) && !empty($data->overview)) {
                $node['body'] = $data->overview;
            }
            // network, runtime, airsDayOfWeek, airsTime, rating, imdbId, lastUpdated, firstAired
            $basic_fields = array('network', 'runtime', 'airsDayOfWeek', 'airsTime', 'rating', 'imdbId', 'lastUpdated', 'firstAired');
            foreach ($basic_fields as $field) {
                if (isset($data->$field) && !empty($data->$field)) {
                  $node['tvdb_' . strtolower($field)] = $data->$field;
                }
            }
            //status 
            if (isset($data->status) && !empty($data->status)) {
                if($data->status == "Continuing") {
                    $node['tvdb_status'] = 1;
                }
                else {
                    $node['tvdb_status'] = 0;
                }
            }
            //aliases
            if (isset($data->aliases) && !empty($data->aliases)) {
                $node['tvdb_aliases'] = $this->process_multiple_field_values($data->aliases);
            }
            //genres
            if (isset($data->genre) && !empty($data->genre)) {
                $node['tvdb_genre'] = $this->process_multiple_taxonomy_terms($data->genre, 'genres');
            }
            //RO title, RO description
            if (!empty($custom_fields['title_ro'])) {
                 $node['tvdb_title_ro'] = $custom_fields['title_ro'];
            }
            if (!empty($custom_fields['description_ro'])) {
                 $node['tvdb_body_ro'] = $custom_fields['description_ro'];
            }
            //Actors
            if (isset($actors) && !empty($actors)) {
                 $node['tvdb_actors'] = $this->process_multiple_taxonomy_terms($actors, 'actors');
            }
            //Main Poster
            if (!empty($custom_fields['poster'])) {
                $node['tvdb_poster'] = $this->process_single_image($data->seriesName, $custom_fields['poster'], 'poster');
            } 
            elseif(!empty($images_poster)) {
                $node['tvdb_poster'] = $this->process_single_image($data->seriesName, $this->image_url . $images_poster[0]->fileName, 'poster');
            }
            //Main Background
            if (!empty($custom_fields['background'])) {
                $node['tvdb_background'] = $this->process_single_image($data->seriesName, $custom_fields['background'], 'fanart');
            } 
            elseif (!empty($images_fanart)) {
                $node['tvdb_background'] = $this->process_single_image($data->seriesName, $this->image_url . $images_fanart[0]->fileName, 'fanart');
            }
            //All Fanart
            if (!empty($images_fanart)) {
                $node['tvdb_fanart'] = $this->process_multiple_images($images_fanart, $data);
            }
            //All Posters
            if (!empty($images_poster)) {
                $node['tvdb_posters'] = $this->process_multiple_images($images_poster, $data);
            } 
            return $node;
        }
        else {
            drupal_set_message(t('Invalid serie ID'), 'error');
            return '';
        }
    }
    
    private function process_episode($data, $id) {
        if (isset($data->id) && !empty($data->id)) {
            if ($this->check_existing_episode($data->id) != 0) {
                drupal_set_message(t('Episode "@title" already exists', array('@title' => $data->episodeName)), 'error');
                return;
            }
            
            if(!empty($data->seriesId) && $this->check_existing_serie($data->seriesId) == 0) {
                drupal_set_message(t('Can\'t find parent serie for episode "@title"', array('@title' => $data->episodeName)), 'error');
                return;
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
            $node['field_episode_id'] = $data->id;
            // title
            if (isset($data->episodeName) && !empty($data->episodeName)) {
                $node['title'] = $data->episodeName;
            }
            // body
            if (isset($data->overview) && !empty($data->overview)) {
                $node['body'] = $data->overview;
            }
            // body
            if (isset($data->lastUpdated) && !empty($data->lastUpdated)) {
                $node['tvdb_lastupdated'] = $data->lastUpdated;
            }
            //seriesId
            if(isset($data->seriesId) && !empty($data->seriesId)) {
                $node['field_serie_id'] = $this->get_serie_node_id($data->seriesId);
            }
            // airedSeason, airedEpisodeNumber, firstAired
            $basic_fields = array('airedSeason', 'airedEpisodeNumber', 'firstAired');
            foreach ($basic_fields as $field) {
                if (isset($data->$field) && !empty($data->$field)) {
                  $node['field_' . strtolower($field)] = $data->$field;
                }
            }
            //directors, writters, gueststars
            $multiple_fields = array('directors', 'writers', 'guestStars');
            foreach ($multiple_fields as $field) {
                if (isset($data->$field) && !empty($data->$field)) {
                  $node['field_' . strtolower($field)] = $this->process_multiple_field_values($data->$field);
                }
            }
            //Cover
            if (isset($data->filename) && !empty($data->filename)) {
                $node['field_cover'] = $this->process_single_image($data->id, $this->image_url . $data->filename, 'episodes');
            }
            return $node;
        }
        else {
           drupal_set_message(t('Cannot find episodes'), 'error');
           return;
        }
    } 
    
    private function process_actor($actor) {
        if (isset($actor->id) && !empty($actor->id)) {
            if (taxonomy_term_load($actor->id)) {
                drupal_set_message(t('Actor "@name" already exists', array('@name' => $actor->name)), 'warning');
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
              $taxonomy['tvdb_author_image'] = $this->process_single_image($actor->name . '-' . $actor->id, $this->image_url . $actor->image, 'actors');
            }
            $term =  Term::create($taxonomy)->save();
        }
        else {
            drupal_set_message(t('Could not create actor "@name"', array('@name' => $data->name)), 'warning');
            return FALSE;
        }
    }
    
    public function process_single_image($name, $link, $type) {
        if ($type == 'actors' || $type == 'episodes') {
            $folder = 'public://images/' . $type . '/';
        } 
        else {
            $folder = 'public://images/series/' . $type . '/';
        }
        $node = $this->upload_image($name, $link, $folder);
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
    
    public function process_multiple_field_values($data) {
        $node = array();
        foreach ($data as $key => $value) {
            $node[] = $data[$key];
        }
        return $node;
    }
    
    public function process_multiple_taxonomy_terms($taxonomies, $type) {
        $node = array();
        $count = 0;
        foreach ($taxonomies as $value) {
            switch($type) {
                case 'genres':
                    $genre = array_keys(taxonomy_term_load_multiple_by_name($value, $type))[0];
                    if (is_null($genre)) {
                        $this->add_genre($value);
                        $genre = array_keys(taxonomy_term_load_multiple_by_name($value, $type))[0];
                        $count++;
                    }
                    $node[] = $genre;
                    break;
                case 'actors':
                    $node[] = $value;
                    $count++;
                    break;
            }
        }
        drupal_set_message(t('Added @number @type', array('@number' => $count, '@type' => $type)), 'status');
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
            drupal_set_message(t('Could not save image from @path', array('@path' => $path)), 'error');
        }
    }
}