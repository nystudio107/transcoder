<?php
/**
 * Transcoder plugin for Craft CMS
 *
 * Transcode videos to various formats, and provide thumbnails of the video
 *
 * @author    nystudio107
 * @copyright Copyright (c) 2016 nystudio107
 * @link      http://nystudio107.com
 * @package   Transcoder
 * @since     1.0.0
 */

namespace Craft;

class TranscoderService extends BaseApplicationComponent
{

    /**
     * Returns a URL to the transcoded video or "" if it doesn't exist (at which time it will create it)
     * By default, the video is never resized, and the format is always .mp4
     *
     * @param  string   $filepath       path to the original video -OR- an AssetFileModel
     * @param  array    $videoOptions   array of options for the video
     * @return string                   URL of the transcoded video or ""
     */
    public function getVideoUrl($filepath, $videoOptions)
    {

        $result = "";

/* -- If we're passed an AssetFileModel, extract the path from it */

        if (is_object($filepath))
        {
            $asset = $filepath;
            $source = $asset->getSource();

            if ($source->type != 'Local')
            {
                throw new Exception(Craft::t('Paths not available for non-local asset sources'));
            }

            $sourcePath = $source->settings['path'];
            $folderPath = $asset->getFolder()->path;

            $filepath = $sourcePath . $folderPath . $asset->filename;
        }

        if (file_exists($filepath))
        {
            $path_parts = pathinfo($filepath);
            $destVideoFile = $path_parts['filename'];
            $destVideoPath = craft()->config->get("transcoderPath", "transcoder");

/* -- Default options for transcoded videos */

            $defaultOptions = array(
                "bitRate" => "800k",
                "frameRate" => 15,
                );

/* -- Coalesce the passed in $videoOptions with the $defaultOptions */

            $videoOptions = array_merge($defaultOptions, $videoOptions);

/* -- Build the basic command for ffmpeg */

            $ffmpegCmd = craft()->config->get("ffmpegPath", "transcoder")
                . ' -i ' . escapeshellarg($filepath)
                . ' -vcodec libx264'
                . ' -vprofile high'
                . ' -preset slow'
                . ' -crf 22'
                . ' -c:a copy'
                . ' -bufsize 1000k'
                . ' -threads 0';

/* -- Set the framerate if desired */

            if ($videoOptions['frameRate'])
            {
                $ffmpegCmd .= ' -r ' . $videoOptions['frameRate'];
                $destVideoFile .= '_' . $videoOptions['frameRate'] . 'fps';
            }

/* -- Set the bitrate if desired */

            if ($videoOptions['bitRate'])
            {
                $ffmpegCmd .= ' -b:v ' . $videoOptions['bitRate'] . ' -maxrate ' . $videoOptions['bitRate'];
                $destVideoFile .= '_' . $videoOptions['bitRate'] . 'bps';
            }

/* -- Create the directory if it isn't there already */

            if (!file_exists($destVideoPath))
                mkdir($destVideoPath);

/* -- Assemble the destination path and final ffmpeg command */

            $destVideoFile .= '.mp4';
            $destVideoPath = $destVideoPath . $destVideoFile;
            $ffmpegCmd .= ' -f mp4 -y ' . escapeshellarg($destVideoPath) . ' >/dev/null 2>/dev/null & echo $!';

/* -- Make sure there isn't a lockfile for this video already */

            $oldpid = false;
            $lockfile = sys_get_temp_dir() . '/' . $destVideoFile . "lock";
            $oldpid = @file_get_contents($lockfile);
            if ($oldpid !== false)
            {
                exec("ps $oldpid", $ProcessState);
                if (count($ProcessState) >= 2)
                    return $result;
                unlink($lockfile);
            }

/* -- If the video file already exists and hasn't been modified, return it.  Otherwise, start it transcoding */

            if (file_exists($destVideoPath) && (filemtime($destVideoPath) >= filemtime($filepath)))
                $result = craft()->config->get("transcoderUrl", "transcoder") . $destVideoFile;
            else
            {

/* -- Kick off the transcoding */

                $pid = shell_exec($ffmpegCmd);
                TranscoderPlugin::log($ffmpegCmd, LogLevel::Info, false);

/* -- Create a lockfile in tmp */

                file_put_contents($lockfile, $pid);
            }
        }
        return $result;
    } /* -- getVideoUrl */

    /**
     * Returns a URL to a video thumbnail
     *
     * @param  string   $filepath           path to the original video or an AssetFileModel
     * @param  array    $thumbnailOptions   array of options for the thumbnail
     * @return string                       URL of the video thumbnail
     */
    public function getVideoThumbnailUrl($filepath, $thumbnailOptions)
    {

        $result = "";

/* -- If we're passed an AssetFileModel, extract the path from it */

        if (is_object($filepath))
        {
            $asset = $filepath;
            $source = $asset->getSource();

            if ($source->type != 'Local')
            {
                throw new Exception(Craft::t('Paths not available for non-local asset sources'));
            }

            $sourcePath = $source->settings['path'];
            $folderPath = $asset->getFolder()->path;

            $filepath = $sourcePath . $folderPath . $asset->filename;
        }

        if (file_exists($filepath))
        {
            $path_parts = pathinfo($filepath);
            $destThumbnailFile = $path_parts['filename'];
            $destThumbnailPath = craft()->config->get("transcoderPath", "transcoder");

/* -- Default options for video thumbnails */

            $defaultOptions = array(
                "width" => 200,
                "height" => 100,
                "timeInSecs" => 10,
                );

/* -- Coalesce the passed in $thumbnailOptions with the $defaultOptions */

            $thumbnailOptions = array_merge($defaultOptions, $thumbnailOptions);

/* -- Build the basic command for ffmpeg */

            $ffmpegCmd = craft()->config->get("ffmpegPath", "transcoder")
                . ' -i ' . escapeshellarg($filepath)
                . ' -vcodec mjpeg'
                . ' -vframes 1';

/* -- Set the width & height if desired */

            if ($thumbnailOptions['width'] && $thumbnailOptions['height'])
            {
                $ffmpegCmd .= ' -vf "scale='
                    . $thumbnailOptions['width'] . ':' . $thumbnailOptions['height']
                    . ', unsharp=5:5:1.0:5:5:0.0"';
                $destThumbnailFile .= '_' . $thumbnailOptions['width'] . 'x' . $thumbnailOptions['height'];
            }

/* -- Set the timecode to get the thumbnail from if desired */

            if ($thumbnailOptions['timeInSecs'])
            {
                $timecode = gmdate("H:i:s", $thumbnailOptions['timeInSecs']);
                $ffmpegCmd .= ' -ss ' . $timecode . '.00';
                $destThumbnailFile .= '_' . $thumbnailOptions['timeInSecs'] . 's';
            }

/* -- Create the directory if it isn't there already */

            if (!file_exists($destThumbnailPath))
                mkdir($destThumbnailPath);

/* -- Assemble the destination path and final ffmpeg command */

            $destThumbnailFile .= '.jpg';
            $destThumbnailPath = $destThumbnailPath . $destThumbnailFile;
            $ffmpegCmd .= ' -f image2 -y ' . escapeshellarg($destThumbnailPath) . ' >/dev/null 2>/dev/null';

/* -- If the thumbnail file already exists, return it.  Otherwise, generate it and return it */

            if (file_exists($destThumbnailPath))
                $result = craft()->config->get("transcoderUrl", "transcoder") . $destThumbnailFile;
            else
            {
                $pid = shell_exec($ffmpegCmd);
                TranscoderPlugin::log($ffmpegCmd, LogLevel::Info, false);
                $result = craft()->config->get("transcoderUrl", "transcoder") . $destThumbnailFile;
            }
        }
        return $result;
    } /* -- getVideoThumbnailUrl */

}