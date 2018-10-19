<?php

namespace RCPL\Polaris\Entity;

use RCPL\Polaris\Client;
use RCPL\Polaris\Controller\HoldRequest as Controller;

class HoldRequest extends EntityBase {

  /**
   * Hold request ID.
   *
   * @return int|bool
   */
  public function id() {
    return !empty($this->data['HoldRequestID']) ? $this->data['HoldRequestID'] : FALSE;
  }

  /**
   * @param string $int
   *   PHP Date Interval format.
   * @param array $values
   * @throws \Exception
   */
  public function suspendUntil($int = 'P1D', array $values = []) {
    $date = new \DateTime();
    $date->add(new \DateInterval($int));
    $this->suspend($date, $values);
  }

  public function activate(array $values = []) {
    $date = new \DateTime();
    // Any time in the future is ok, so we set this to 1 second.
    $date->add(new \DateInterval('PT1S'));
    return $this->changeStatus('active', $date, $values);
  }

  public function suspend(\DateTime $date, array $values = []) {
    return $this->changeStatus('inactive', $date, $values);
  }

  public function cancel() {
    $endpoint = $this->url() . '/' . $this->id() . '/cancelled';
    return $this->client->request()
      ->public()
      ->put()
      ->query(['wsid' => $this->client->params->get('WORKSTATION_ID'), 'userid' => 1])
      ->path($endpoint)
      ->staff()
      ->send();
  }

  public function updatePickupBranch($branch_id) {
    $endpoint = $this->url() . '/' . $this->id() . '/pickupbranch';
    return $this->client->request()
      ->public()
      ->put()
      ->query([
        'wsid' => $this->client->params->get('WORKSTATION_ID'),
        'userid' => 1,
        'pickupbranchid' => $branch_id
      ])
      ->path($endpoint)
      ->staff()
      ->send();
  }

  /**
   * Helper function to either suspend or activate.
   *
   * @param $status
   *   Either active/inactive.
   * @param \DateTime $date
   * @param array $values
   * @return mixed
   */
  private function changeStatus($status, \DateTime $date, array $values = []) {
    $endpoint = $this->url() . '/' . $this->id() . '/' . $status;
    return $this->client->request()
      ->public()
      ->staff()
      ->config([
        'json' => [
          // TODO: Make configurable.
          'UserID' => 1,
          'ActivationDate' => $date->format(\DateTime::ISO8601),
        ]
      ])
      ->path($endpoint)
      ->put()
      ->send();
  }

  /**
   * Save a new hold request.
   *
   * @return mixed|void
   */
  public function save() {
    if (!empty($this->id())) {
      return;
    }
    return $this->client->request()
      ->config(['json' => $this->data])
      ->public()
      ->post()
      ->path('holdrequest')
      ->staff()
      ->send();
  }

  /**
   * Respond to an existing hold request.
   *
   * @param $values
   *  Hold request response values.
   * @return mixed|void
   */
  public function respond($data) {
    if (!isset($data['RequestGUID'])) {
      return;
    }
    $data = array_merge($this->data, $data);

    return $this->client->request()
      ->config(['json' => $data])
      ->public()
      ->put()
      ->path('holdrequest/' . $data['RequestGUID'])
      ->staff()
      ->send();
  }
}
