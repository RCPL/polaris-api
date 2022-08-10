<?php

namespace RCPL\Polaris\Utility;

use Laminas\Stdlib\ArrayUtils;
use Laminas\Stdlib\Parameters as LaminasParameters;

class Parameters extends LaminasParameters {

  /**
   * @param Parameters|array $parameters
   *
   * @return Parameters;
   */
  public function merge($parameters) {
    $params = is_a($parameters, 'Parameters') ? $parameters->toArray() : $parameters;
    $this->exchangeArray(ArrayUtils::merge($this->toArray(), $parameters));
    return $this;
  }

  /**
   * Creates a new instance of this object with no reference.
   *
   * @return Parameters;
   */
  public function clone() {
    return new static($this->getArrayCopy());
  }

}
