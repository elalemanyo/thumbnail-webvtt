<?php
include 'generator.php';

$opts = [
  'library'    => 'avconv',
  'videotypes' => array('video/mp4', 'video/x-flv'),
  // 'input'   => 'http://www.medien-tube.de/images/media/34958SD.mp4', //long
  // 'input'   => 'http://www.medien-tube.de/images/media/34955.mp4',   //short
  // 'input'   => 'http://www.medien-tube.de/images/media/34658.mp4',   //shorter
  'input'      => 'http://www.medien-tube.de/images/media/5601.flv',   //flv
  'name'       => 'video',
  'output'     => '.',
  'timespan'   => '10',
  'width'      => 120,
  'verbose'    => FALSE,
  'poster'     => TRUE,
  'delete'     => TRUE
];

try {
  $var = createthumbnail($opts);
} catch(ThumbnailWebVttException $e) {
  $var = false;
  echo $e->getMessage();
}

var_dump($var);
