<?php

namespace nickaversano\Scavenger\Tests;

require_once 'bootstrap.php';

use nickaversano\Scavenger\Scavenger;

//$r = Scavenger::get('http://www.youtube.com/watch?v=mJ_fkw5j-t0');
//$r = Scavenger::get('http://www.nickaversano.com/');

$r = Scavenger::get('http://www.washingtonpost.com/entertainment/theater_dance/amy-purdys-bionic-grace-on-dancing-with-the-stars/2014/04/10/e4575b48-bdd7-11e3-bcec-b71ee10e9bc3_story.html');


var_dump($r);

