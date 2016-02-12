<?php
/**
 * Create the thumbnails for the Video
 * $opts:
 *     -input: The input file to be used.
 *     -name: The output name to be used. (default video name)
 *     -output: The output directory where the thumbnails and vtt file will be saved
 *     -timespan: The time span (in seconds) between each thumbnail (default, {$params['timespan']})
 *     -width: The max width of the thumbnail (default, {$params['thumbWidth']})
 *     -verbose: Verbose - don't coalesce the thumbnails into one image (boolean)
 *     -poster: Generate poster image from a random frame in the video (boolean)
 *     -delete: Delete any previous thumbnails that match before generating new images (boolean)
 *
 */
function createthumbnail($opts) {
    $params = [
        'ffmpeg'      => 'ffmpeg', // the ffmpeg command - full path if needs be
        'input'       => null,     // input video file - specified as command line parameter
        'output'      => __DIR__,  // The output directory
        'timespan'    => 10,       // seconds between each thumbnail
        'thumbWidth'  => 120,      // thumbnail width
        'spriteWidth' => 10        // number of thumbnails per row in sprite sheet
    ];

    $commands = [
        'details' => $params['ffmpeg'] . ' -i %s 2>&1',
        'poster'  => $params['ffmpeg'] . ' -ss %d -i %s -y -vframes 1 "%s/%s-poster.jpg" 2>&1',
        'thumbs'  => $params['ffmpeg'] . ' -ss %0.04f -i %s -y -an -sn -vsync 0 -q:v 5 -threads 1 '
            . '-vf scale=%d:-1,select="not(mod(n\\,%d))" "%s/thumbnails/%s-%%04d.jpg" 2>&1'
    ];

    // process input parameters
    $params['input'] = escapeshellarg($opts['input']);
    if (isset($opts['output'])) {
        $params['output'] = realpath($opts['output']);
    }
    if (isset($opts['timespan']) && (int)$opts['timespan']) {
        $params['timespan'] = $opts['timespan'];
    }
    if (isset($opts['width']) && (int)$opts['width']) {
        $params['thumbWidth'] = $opts['width'];
    }

    // sanity checks
    if (!is_readable($opts['input'])) {
        if (filter_var($opts['input'], FILTER_VALIDATE_URL)) {
            if (checkurl($opts['input']) === false) {
                throw new ThumbnailWebVttException("Cannot read the url file '{$opts['input']}'");
            }
        }
        else {
            throw new ThumbnailWebVttException("Cannot read the input file '{$opts['input']}'");
        }
    }
    if (!is_writable($params['output'])) {
        throw new ThumbnailWebVttException("Cannot write to output directory '{$opts['output']}'");
    }
    if (!file_exists($params['output'] . '/thumbnails')) {
        if (!mkdir($params['output'] . '/thumbnails')) {
            throw new ThumbnailWebVttException("Could not create thumbnail output directory '{$params['output']}/thumbnails'");
        }
    }

    $details = shell_exec(sprintf($commands['details'], $params['input']));
    if ($details === null || !preg_match('/^(?:\s+)?ffmpeg version ([^\s,]*)/i', $details)) {
        throw new ThumbnailWebVttException('Cannot find ffmpeg - try specifying the path in the $params variable');
    }

    // determine some values we need
    $time = $tbr = [];
    preg_match('/Duration: ((\d+):(\d+):(\d+))\.\d+, start: ([^,]*)/is', $details, $time);
    preg_match('/\b(\d+(?:\.\d+)?) tbr\b/', $details, $tbr);
    $duration = ($time[2] * 3600) + ($time[3] * 60) + $time[4];
    $start = $time[5];
    $tbr = $tbr[1];

    $name = (isset($opts['name']))? $opts['name'] : strtolower(substr(basename($opts['input']), 0, strrpos(basename($opts['input']), '.')));

    // generate random poster if required
    if (isset($opts['poster']) && $opts['poster'] === TRUE) {
        shell_exec(sprintf($commands['poster'], rand(1, $duration - 1), $opts['input'], $params['output'], $name));
    }

    // generate all thumbnail images
    $filter = function($current, $key, $iterator) use ($name) {
        return (
            $current->isFile()
            && preg_match("!{$name}-\\d{4}\\.jpg$!", $current->getFilename())
        );
    };

    if (isset($opts['delete']) && $opts['delete'] === TRUE) {
        $files = new CallbackFilterIterator(
            new FilesystemIterator("{$params['output']}/thumbnails"), $filter
        );
        foreach ($files as $f) {
            unlink($f);
        }
    }
    shell_exec(sprintf($commands['thumbs'],
        $start + .0001, $params['input'], $params['thumbWidth'],
        $params['timespan'] * $tbr, $params['output'], $name
    ));
    $files = array_values(iterator_to_array(
        new CallbackFilterIterator(
            new FilesystemIterator("{$params['output']}/thumbnails"), $filter
        )
    ));
    if (!($total = count($files))) {
        throw new ThumbnailWebVttException("Could not find any thumbnails matching '{$params['output']}/thumbnails/{$name}-\\d{4}.jpg'");
    }
    sort($files, SORT_NATURAL);

    // create coalesce image if needs be
    if (isset($opts['verbose']) && $opts['verbose'] === FALSE) {
        $thumbsAcross = min($total, $params['spriteWidth']);
        $sizes = getimagesize($files[0]);
        $rows = ceil($total/$thumbsAcross);
        $w = $sizes[0] * $thumbsAcross;
        $h = $sizes[1] * $rows;
        $coalesce = imagecreatetruecolor($w, $h);
    }

    // generate vtt file, merge thumbnails if needs be
    $vtt = "WEBVTT\n\n";
    for ($rx = $ry = $s = $f = 0; $f < $total; $f++) {
        $t1 = sprintf('%02d:%02d:%02d.000', ($s / 3600), ($s / 60 % 60), $s % 60);
        $s += $params['timespan'];
        $t2 = sprintf('%02d:%02d:%02d.000', ($s / 3600), ($s / 60 % 60), $s % 60);
        if (isset($opts['verbose']) && $opts['verbose'] !== FALSE) {
            $vtt .= "{$t1} --> {$t2}\nthumbnails/" . basename($files[$f]);
        } else {
            if ($f && !($f % $thumbsAcross)) {
                $rx = 0;
                ++$ry;
            }
            imagecopymerge($coalesce, imagecreatefromjpeg($files[$f]), $rx * $sizes[0], $ry * $sizes[1], 0, 0, $sizes[0], $sizes[1], 100);
            $vtt .= sprintf("%s --> %s\n{$name}.jpg#xywh=%d,%d,%d,%d", $t1, $t2, $rx++ * $sizes[0], $ry * $sizes[1],  $sizes[0], $sizes[1]);
        }
        $vtt .= "\n\n";
    }

    // tidy up
    if (isset($opts['verbose']) && $opts['verbose'] === FALSE) {
        imagejpeg($coalesce, "{$params['output']}/{$name}.jpg", 75);
        for ($s = 0, $f = 0; $f < $total; $f++) {
            unlink($files[$f]);
        }
        rmdir($params['output'] . '/thumbnails');
    }

    file_put_contents("{$params['output']}/{$name}.vtt", $vtt);
    return $params['output'] . '/' . $name .'.vtt';
}


/**
 * Check url & Content-Type (eg. 'video/mp4')
 *
 */
function checkurl($url) {
    $video_contentTypes = array('video/mp4');

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $data = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    return ($data !== false && $httpcode === 200 && in_array($contentType, $video_contentTypes));
}

/**
 * ThumbnailWebVttException
 *
 */
class ThumbnailWebVttException extends Exception {}
