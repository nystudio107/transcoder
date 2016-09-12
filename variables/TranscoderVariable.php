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

class TranscoderVariable
{
    /**
     * Returns a URL to the transcoded video or "" if it doesn't exist (at which time it will create it)
     * By default, the video is never resized, and the format is always .mp4
     *
     * @param  string   $filepath       path to the original video
     * @param  array    $videoOptions   array of options for the video
     * @return string                   URL of the transcoded video or ""
     */
    public function getVideoUrl($filepath, $videoOptions)
    {
        $result = craft()->transcoder->getVideoUrl($filepath, $videoOptions);
        return $result;
    } /* -- getVideoUrl */

    /**
     * Returns a URL to a video thumbnail
     *
     * @param  string   $filepath           path to the original video
     * @param  array    $thumbnailOptions   array of options for the thumbnail
     * @return string                       URL of the video thumbnail
     */
    public function getVideoThumbnailUrl($filepath, $thumbnailOptions)
    {
        $result = craft()->transcoder->getVideoThumbnailUrl($filepath, $thumbnailOptions);
        return $result;
    } /* -- getVideoThumbnailUrl */

    /**
     * Get a download URL
     *
     * @param  string $url to the asset to download
     * @return string the download URL
     */
    function getDownloadUrl($url)
    {
        $result = "";
        $filepath = parse_url($url, PHP_URL_PATH);
        $filepath = $_SERVER['DOCUMENT_ROOT'] . $filepath;
        if (file_exists($filepath))
        {
            $urlParams = array(
                'url' => urlencode($url),
                );
            $result = UrlHelper::getActionUrl('transcoder/downloadFile', $urlParams);
        }
        return $result;
    } /* -- getDownloadUrl */

}