<?php

namespace RCPL\Polaris\Controller;

use RCPL\Polaris\Client;
//use RCPL\Polaris\Entity\TitleList as Entity;
use RCPL\Polaris\Entity\Patron as Patron;

/**
 * Actions for creating/removing hold requests for customers.
 */
class TitleList extends ControllerBase {

  private $controllerData = [];

  private $patron;

  public function __construct(Client $client) {
    parent::__construct($client);
  }

  public function init(Patron $patron) {
    $instance = new static($this->client);
    return $instance->setPatron($patron);
  }

  public function setPatron(Patron $patron) {
    $this->patron = $patron;
    return $this;
  }

  public function url() {
    return $this->patron->url();
  }

  /**
   * Gets a list of all of a particular customer's lists.
   */
  public function getLists() {
    return $this->client->request()
      ->staff()
      ->public()
      ->get()
      ->path($this->url() . '/patronaccountgettitlelists')
      ->simple('PatronAccountTitleListsRows')
      ->send();
  }

  /**
   * Creates a list with the given name
   */
  public function create($list_name) {
    $endpoint = $this->url() . '/patronaccountcreatetitlelist';
    $config = [
      'json' => [
        'RecordStoreName' => $list_name,
      ],
    ];
    return $this->client->request()
      ->public()
      ->staff()
      ->path($endpoint)
      ->config($config)
      ->post()
      ->send();
  }

  /**
   * Deletes a list.
   */
  public function delete($list_id) {
    $endpoint = $this->url() . '/patronaccountdeletetitlelist';
    $query = [
      'list' => $list_id
    ];
    return $this->client->request()
      ->public()
      ->staff()
      ->path($endpoint)
      ->query($query)
      ->delete()
      ->send();
  }

  /**
   * Gets all of the titles in a particular list.
   */
  public function getTitles($list_id) {
    $endpoint = $this->url() . '/patrontitlelistgettitles';
    $query = [
      'list' => $list_id
    ];
    return $this->client->request()
      ->public()
      ->staff()
      ->path($endpoint)
      ->query($query)
      ->get()
      ->simple('PatronTitleListTitleRows')
      ->send();
  }

  /**
   * Adds the specified bib record to the specified list in the patron account.
   */
  public function addTitle($list_id, $item_id) {
    $endpoint = $this->url() . '/patrontitlelistaddtitle';
    $config = [
      'json' => [
        'RecordStoreID' => $list_id,
        'LocalControlNumber' => $item_id
      ],
    ];
    return $this->client->request()
      ->public()
      ->staff()
      ->path($endpoint)
      ->config($config)
      ->post()
      ->send();
  }

  /**
   * Deletes a single specified bibliographic record from the specified list
   * in the patron account.
   */
  function deleteTitle($list_id, $position_id) {
    $endpoint = $this->url() . '/patrontitlelistdeletetitle';
    $query = [
      'list' => $list_id,
      'position' => $position_id
    ];
    return $this->client->request()
      ->public()
      ->staff()
      ->path($endpoint)
      ->query($query)
      ->delete()
      ->send();
  }

  /**
   * Removes all the records from a given list, but leaves the empty list
   * in the patron account.
   */
  function deleteAllTitles($list_id) {
    $endpoint = $this->url() . '/patrontitlelistdeletealltitles';
    $query = [
      'list' => $list_id
    ];
    return $this->client->request()
      ->public()
      ->staff()
      ->path($endpoint)
      ->query($query)
      ->delete()
      ->send();
  }

  /**
   * Moves a specified bibliographic record from one title list to another;
   * both title lists are in the same patron account.
   */
  function moveTitle($list_id_from, $position_id, $list_id_to) {
    $endpoint = $this->url() . '/patrontitlelistmovetitle';
    $config = [
      'json' => [
        'FromRecordStoreID' => $list_id_from,
        'FromPosition' => $position_id,
        'ToRecordStoreID' => $list_id_to
      ],
    ];
    return $this->client->request()
      ->public()
      ->staff()
      ->path($endpoint)
      ->config($config)
      ->post()
      ->send();
  }

  /**
   * Copy a title from one list to another in a patron account.
   */
  function copyTitle($list_id_from, $position_id, $list_id_to) {
    $endpoint = $this->url() . '/patrontitlelistcopytitle';
    $config = [
      'json' => [
        'FromRecordStoreID' => $list_id_from,
        'FromPosition' => $position_id,
        'ToRecordStoreID' => $list_id_to
      ],
    ];
    return $this->client->request()
      ->public()
      ->staff()
      ->path($endpoint)
      ->config($config)
      ->post()
      ->send();
  }

  /**
   * Copy all titles from one list to another list in the patron account.
   */
  function copyAllTitles($list_id_from, $list_id_to) {
    $endpoint = $this->url() . '/patrontitlelistcopyalltitles';
    $config = [
      'json' => [
        'FromRecordStoreID' => $list_id_from,
        'ToRecordStoreID' => $list_id_to
      ],
    ];
    return $this->client->request()
      ->public()
      ->staff()
      ->path($endpoint)
      ->config($config)
      ->post()
      ->send();
  }

}
