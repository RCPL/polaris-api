<?php

namespace RCPL\Polaris\Controller;

use RCPL\Polaris\Client;

class Bibliography extends ControllerBase {

  private $id;

  public function __construct(Client $client, $id = NULL) {
    $this->id = $id;
    parent::__construct($client);
  }
  public function get($id) {
    return new static($this->client, $id);
  }

  /**
   * @param string $text
   *   Text to search for (required)
   * @param array $params
   *   Search parameters, i.e. 'sortby'
   * @param string $qualifier_name
   *   KW|TI|AU|SU|NOTE|PUB|GENRE|SE|ISBN|ISSN|LCCN|PN|LC|DD|LOCAL|SUDOC|CODEN|STRN|CN|BC
   */
  public function search($text, array $params = [], $qualifier_name = 'keyword/au') {
    $endpoint = 'search/bibs/' . $qualifier_name;
    $params['q'] = $this->encode($text);
    return $this->client->request()
      ->public()
      ->path($endpoint)
      ->query($params)
      ->get()
      ->simplify('BibSearchRows')
      ->send();
  }

  public function holdings() {
    $endpoint = 'bib/' . $this->id . '/holdings';
    return $this->request($endpoint);
  }

  /**
   * TODO: Refactor this to be in a parent class.
   */
  private function request($endpoint, array $query = []) {
    return $this->client->request()
      ->staff()
      ->public()
      ->path($endpoint)
      ->query($query)
      ->get()
      ->send();
  }
}
