<?php
include 'generator.php';

$opts = [
  //'i'  => 'http://www.medien-tube.de/images/media/34958SD.mp4', //long
  // 'i' => 'http://www.medien-tube.de/images/media/34955.mp4',   //short
  'i'    => 'http://www.medien-tube.de/images/media/34658.mp4',   //shorter
  'n'    => 'video',
  'o'    => '.',
  't'    => '10',
  'w'    => 120,
  'v'    => FALSE,
  'p'    => TRUE,
  'd'    => TRUE
];

try {
  $var = createthumbnail($opts);
} catch(ThumbnailWebVttException $e) {
  $var = false;
  echo $e->getMessage();
}

var_dump($var);
