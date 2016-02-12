<?php
$opts = [
  // 'input' => 'http://www.medien-tube.de/images/media/34958SD.mp4', // long
  // 'input' => 'http://www.medien-tube.de/images/media/34955.mp4',   // short
  'input'    => 'http://www.medien-tube.de/images/media/34658.mp4',   // shorter
  'name'     => 'video',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Test | Thumbnail & associated WebVTT file generator</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://content.jwplatform.com/libraries/iGrCEbaN.js"></script>
  <style type="text/css">
    #video {
      margin: 50px auto 0 auto;
    }
  </style>
</head>
<body>
  <div id="video"></div>
  <script>
    var playerInstance = jwplayer("video");
    playerInstance.setup({
      file: "<?=$opts['input']?>",
      image: "<?=$opts['name']?>-poster.jpg",
      width: "50%",
      aspectratio: "16:9",
      tracks: [{
        file: "<?=$opts['name']?>.vtt",
        kind: "thumbnails"
      }]
    });
  </script>
  </body>
  </html>

