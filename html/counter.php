<?php

function counter() {
  static $a = 0;
  $a++;
  return $a;
}
