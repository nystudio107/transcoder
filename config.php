<?php
/**
 * Transcoder Configuration
 *
 * Completely optional configuration settings for Transcoder if you want to customize some
 * of its more esoteric behavior, or just want specific control over things.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'transcoder.php' and make
 * your changes there.
 */

return array(

/**
 * The path to the ffmpeg binary
 */
    "ffmpegPath" => "/usr/bin/ffmpeg",

/**
 * The path where the transcoded videos are stored
 */

    "transcoderPath" => $_SERVER['DOCUMENT_ROOT'] . "/transcoder/",

/**
 * The URL where the transcoded videos are stored
 */

    "transcoderUrl" => "/transcoder/",
);