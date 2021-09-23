<?php

namespace RCPL\Polaris\Entity;

use RCPL\Polaris\Client;
use RCPL\Polaris\Controller\Bibliography as Controller;
use RCPL\Polaris\Request;

class Bibliography extends EntityBase {

  private $id;

  /**
   * {@inheritdoc}
   */
  public function __construct(Controller $controller, int $id, array $data = [], $result = NULL) {
    parent::__construct($controller);
    $this->id = $id;
    $this->setData($data);
    $this->result = $result;
  }

  private function setData(array $data = []) {
    foreach ($data as $row) {
      $this->data[$row->ElementID][$row->Occurence] = $row;
    }
    $this->data = array_map(function($value) {
      return count($value) == 1 ? reset($value) : $value;
    }, $this->data);
  }

  private function map($name = NULL) {
    $map = [
      'title' => ['id' => 35, 'name' => 'Title'],
      'author' => ['id' => 18, 'name' => 'Author'],
      'format' => ['id' => 17],
      'publisher' => ['id' => 2, 'name' => 'Publisher'],
      'pubdate' => ['name' => 'PublicationDate'],
      'description' => ['id' => 3, 'name' => 'Description'],
      'contents' => ['id' => 29, 'name' => 'Contents'],
      'isbn' => ['id' => 6, 'name' => 'ISBN'],
      'oclc' => ['name' => 'OCLC'],
      'upc' => ['name' => 'UPC'],
      'summary' => ['id' => 9, 'name' => 'Summary'],
      'series' => ['id' => 19, 'name' => 'Series'],
      'itemsAvailable' => ['id' => 16],
      'itemsTotal' => ['id' => 7],
      'requests' => ['id' => 8],
      'weblink' => ['name' => 'WebLink'],
      'website' => ['id' => 33],
      'thumbnaillink' => ['name' => 'ThumbnailLink'],
      'subjects' => ['id' => 20],
      'edition' => ['name' => 'Edition'],
      'primaryTypeOfMaterial' => ['name' => 'PrimaryTypeOfMaterial'],
      'targetAudience' => ['name' => 'TargetAudience'],
      'otherAuthors' => ['id' => 21],
      'notes' => ['id' => 28, 'name' => 'Notes']
    ];

    return $name ? $map[$name] : $map;
  }

  protected function concatDataElement($id, $glue = ', ') {
    $data = $this->getDataElement($id);

    if (is_array($data)) {
      return implode($glue, $data);
    }

    return $data;
  }

  protected function getDataElement($id) {
    if (isset($this->data[$id])) {
      $data = $this->data[$id];

      if (is_array($data)) {
        return array_map(function($item) {
          return $item->Value;
        }, $data);
      }

      return $data->Value;
    }

    return  '';
  }

  protected function getResultElement($name) {
    return isset($this->result->{$name}) ? $this->result->{$name} : '';
  }

  protected function getProperty($name) {
    $map = $this->map();

    $property = $map[$name];
    $data = '';

    if ($this->result && isset($property['name'])) {
      $data = $this->getResultElement($property['name']);
    }
    if (!$data && isset($property['id'])) {
      $data = $this->getDataElement($property['id']);
    }

    return $data;
  }

  protected function getValues($props) {
    $values = [];

    foreach ($props as $prop) {
      $values[$prop] = $this->getProperty($prop);
    }
    // Fix for website URLs that contain text. Change them into links.
    if (isset($values['website']) && !empty($values['website']) && !is_array($values['website'])) {
      preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $values['website'], $matches);
      $values['website'] = '<a href="' . $matches[0][0] . '">' . $matches[0][0] . '</a>';
    }

    return $values;
  }

  public function holdings($query = []) {
    $endpoint = 'bib/' . $this->id . '/holdings';
    return $this->client->request()
      ->staff()
      ->public()
      ->path($endpoint)
      ->query($query)
      ->get()
      ->send();
  }

  public function teaser() {
    $values = [
      'controlNumber' => $this->id,
    ];
    $props = [
      'itemsAvailable',
      'itemsTotal',
      'requests',
      'title',
      'author',
      'publisher',
      'pubdate',
      'description',
      'contents',
      'format',
      'isbn',
      'oclc',
      'upc',
      'summary',
      'series',
      'weblink',
      'website',
      'thumbnaillink',
      'edition',
      'primaryTypeOfMaterial',
      'targetAudience',
      'otherAuthors',
      'notes'
    ];

    return array_merge($values, $this->getValues($props));
  }

  public function holds() {
    $values = $this->teaser();
    $holds = $this->map('requests');
    $values['requests'] = $this->concatDataElement($holds['id']);

    return $values;
  }

  public function detail() {
    $values = $this->teaser();

    $isbn = $this->map('isbn');
    $subj = $this->map('subjects');

    $values['subjects'] = $this->concatDataElement($subj['id'], '|');
    $values['holdings'] = $this->holdings();

    return $values;
  }
}
