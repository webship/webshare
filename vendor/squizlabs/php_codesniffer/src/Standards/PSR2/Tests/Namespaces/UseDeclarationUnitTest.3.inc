<?php

namespace bug;

class Bug {

  public function __construct() {
    $b = 1;
    $a = function () use ($b) {
      echo $b;
    };
  }

}
