# Thumbnail & WebVTT file generator
Generate tooltip thumbnail images for videos & associated WebVTT files for use with JWPlayer.

### Requirements

* ffmpeg or avconv

* php 5.4+

### Installation
Just include `generator.php` in your code.

### Usage

```
  Options:
    -library: ffmpeg or avconv
    -videotypes: Accepted video content types. Default: array('video/mp4') (Array)
    -input: The input file to be used.
    -name: The output name to be used. (default video name)
    -ooutput: The output directory where the thumbnails and vtt file will be saved
    -timespan: The time span (in seconds) between each thumbnail (default, {$params['timespan']})
    -width: The max width of the thumbnail (default, {$params['thumbWidth']})
    -verbose: Verbose - don't coalesce the thumbnails into one image (boolean)
    -poster: Generate poster image from a random frame in the video (boolean)
    -delete: Delete any previous thumbnails that match before generating new images (boolean)
```

### JW Player config
Just with thumbnails:
```
<div id="video"></div>
<script>
  var playerInstance = jwplayer("video");
  playerInstance.setup({
    file: "video.mp4",
    image: "video-poster.jpg",
    width: "50%",
    aspectratio: "16:9",
    tracks: [{
      file: "video.vtt",
      kind: "thumbnails"
    }]
  });
</script>
```

Thumbnails and chapters:
```
<div id="video"></div>
<script>
  var playerInstance = jwplayer("video");
  playerInstance.setup({
    file: "video.mp4",
    image: "video-poster.jpg",
    width: "50%",
    aspectratio: "16:9",
    tracks: [{
      file: "video.vtt",
      kind: "thumbnails"
    },
    {
      file: "video-chapters.vtt",
      kind: "chapters"
    }]
  });
</script>
```

# Credits

* @[amnuts](https://github.com/amnuts) for the [jwplayer-thumbnail-preview-generator](https://github.com/amnuts/jwplayer-thumbnail-preview-generator).
