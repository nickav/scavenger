<?php

namespace nickaversano\Scavenger\Tests;

require_once 'bootstrap.php';

use nickaversano\Scavenger\Scavenger;

$r = Scavenger::get('http://www.youtube.com/watch?v=mJ_fkw5j-t0');

//$r = Scavenger::parse('<meta name="description" content="hello" /><meta property="og:title" content="My Title Article" />');

var_dump($r);