<?php

return [
    'chrome_path' => env('CHROME_PATH', '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'),
    'ffmpeg_path' => env('FFMPEG_PATH', match (PHP_OS_FAMILY) {
        'Darwin' => '/opt/homebrew/bin/ffmpeg',
        default => '/usr/bin/ffmpeg',
    }),
];
