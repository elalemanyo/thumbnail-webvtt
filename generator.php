<?php
/**
 * Create the thumbnails for the Video
 * $opts:
 *     -i: The input file to be used.
 *     -n: The output name to be used. (default video name)
 *     -o: The output directory where the thumbnails and vtt file will be saved
 *     -t: The time span (in seconds) between each thumbnail (default, {$params['timespan']})
 *     -w: The max width of the thumbnail (default, {$params['thumbWidth']})
 *     -v: Verbose - don't coalesce the thumbnails into one image (boolean)
 *     -p: Generate poster image from a random frame in the video (boolean)
 *     -d: Delete any previous thumbnails that match before generating new images (boolean)
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

    define('EX_USAGE', 64);
    define('EX_NOINPUT', 66);
    define('EX_UNAVAILABLE', 69);
    define('EX_CANTCREAT', 73);

    // process input parameters
    $params['input'] = escapeshellarg($opts['i']);
    if (isset($opts['o'])) {
        $params['output'] = realpath($opts['o']);
    }
    if (isset($opts['t']) && (int)$opts['t']) {
        $params['timespan'] = $opts['t'];
    }
    if (isset($opts['w']) && (int)$opts['w']) {
        $params['thumbWidth'] = $opts['w'];
    }

    // sanity checks
    if (!is_readable($opts['i'])) {
        if (filter_var($opts['i'], FILTER_VALIDATE_URL)) {
            if (checkurl($opts['i']) === false) {
                echo "Cannot read the url file '{$opts['i']}'";
                exit(EX_NOINPUT);
            }
        }
        else {
            echo "Cannot read the input file '{$opts['i']}'";
            exit(EX_NOINPUT);
        }
    }
    if (!is_writable($params['output'])) {
        echo $params['output'];
        //echo "Cannot write to output directory '{$opts['o']}'";
        exit(EX_CANTCREAT);
    }
    if (!file_exists($params['output'] . '/thumbnails')) {
        if (!mkdir($params['output'] . '/thumbnails')) {
            echo "Could not create thumbnail output directory '{$params['output']}/thumbnails'";
            exit(EX_CANTCREAT);
        }
    }

    $details = shell_exec(sprintf($commands['details'], $params['input']));
    if ($details === null || !preg_match('/^(?:\s+)?ffmpeg version ([^\s,]*)/i', $details)) {
        echo 'Cannot find ffmpeg - try specifying the path in the $params variable';
        exit(EX_UNAVAILABLE);
    }

    // determine some values we need
    $time = $tbr = [];
    preg_match('/Duration: ((\d+):(\d+):(\d+))\.\d+, start: ([^,]*)/is', $details, $time);
    preg_match('/\b(\d+(?:\.\d+)?) tbr\b/', $details, $tbr);
    $duration = ($time[2] * 3600) + ($time[3] * 60) + $time[4];
    $start = $time[5];
    $tbr = $tbr[1];

    $name = (isset($opts['n']))? $opts['n'] : strtolower(substr(basename($opts['i']), 0, strrpos(basename($opts['i']), '.')));

    // generate random poster if required
    if (isset($opts['p']) && $opts['p'] === TRUE) {
        shell_exec(sprintf($commands['poster'], rand(1, $duration - 1), $opts['i'], $params['output'], $name));
    }

    // generate all thumbnail images
    $filter = function($current, $key, $iterator) use ($name) {
        return (
            $current->isFile()
            && preg_match("!{$name}-\\d{4}\\.jpg$!", $current->getFilename())
        );
    };

    if (isset($opts['d']) && $opts['d'] === TRUE) {
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
        echo "Could not find any thumbnails matching '{$params['output']}/thumbnails/{$name}-\\d{4}.jpg'";
        exit(EX_NOINPUT);
    }
    sort($files, SORT_NATURAL);

    // create coalesce image if needs be
    if (isset($opts['v']) && $opts['v'] === FALSE) {
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
        if (isset($opts['v']) && $opts['v'] !== FALSE) {
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
    if (isset($opts['v']) && $opts['v'] === FALSE) {
        imagejpeg($coalesce, "{$params['output']}/{$name}.jpg", 75);
        for ($s = 0, $f = 0; $f < $total; $f++) {
            unlink($files[$f]);
        }
        rmdir($params['output'] . '/thumbnails');
    }

    file_put_contents("{$params['output']}/{$name}.vtt", $vtt);
    echo "Process completed. Check the output directory '{$params['output']}' for VTT file and images";
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

    return ($data !== false && $httpcode === 200 && in_array($contentType, $video_contentTypes))? true : false;
}
