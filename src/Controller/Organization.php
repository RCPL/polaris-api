<?php

namespace RCPL\Polaris\Controller;

class Organization extends ControllerBase {

  protected $organizations;

  /**
   * @deprecated
   *
   * Use getAll() or getByType()
   */
  public function get($type = 'all') {
    return $this->client->createRequest()
      ->public()
      ->path('organizations/' . $type)
      ->get()
      ->send();
  }

  /**
   * @return mixed
   */
  public function getAll($type = 'all') {
    if (!isset($this->organizations)) {
      $this->organizations = $this->client->createRequest()
        ->public()
        ->path('organizations/' . $type)
        ->simple('OrganizationsGetRows')
        ->get()
        ->send();
    }
    return $this->organizations;
  }

  protected function getByKey($key, $name) {
    foreach ($this->getAll() as $org) {
      if ($org->{$key} == $name) {
        return $org;
      }
    }
    return FALSE;
  }

  public function getByAbbr($abbr) {
    return $this->getByKey('Abbreviation', $abbr);
  }

  public function getByDisplayName($name) {
    return $this->getByKey('DisplayName', $name);
  }
  public function getByName($name) {
    return $this->getByKey('Name', $name);
  }

  public function getByCode($code) {
    return $this->getByKey('OrganizationCodeID', $code);
  }

  public function getById($id) {
    return $this->getByKey('OrganizationID', $id);
  }

  public function getPickupBranches() {
    return $this->client->createRequest()
      ->public()
      ->path('pickupbranches')
      ->simple('PickupBranchesRows')
      ->get()
      ->send();
  }

}
