# Thumbnail & WebVTT file generator
Generate tooltip thumbnail images for videos & associated WebVTT files for use with JWPlayer.

### Requirements

* FFmpeg

* PHP 5.4+

### Installation
Just include `generator.php` in your code.

### Usage

```
  Options:
    -i: The input file to be used.
    -n: The output name to be used. (default video name)
    -o: The output directory where the thumbnails and vtt file will be saved
    -t: The time span (in seconds) between each thumbnail (default, {$params['timespan']})
    -w: The max width of the thumbnail (default, {$params['thumbWidth']})
    -v: Verbose - don't coalesce the thumbnails into one image (boolean)
    -p: Generate poster image from a random frame in the video (boolean)
    -d: Delete any previous thumbnails that match before generating new images (boolean)
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
