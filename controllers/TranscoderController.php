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

class TranscoderController extends BaseController
{

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     * @access protected
     */
    protected $allowAnonymous = array('actionDownloadFile',
        );

    /**
     * Force the download of a given $url.  We do it this way to prevent people from downloading
     * things that are outside of the server root.
     */
    public function actionDownloadFile()
    {
        $url = urldecode(craft()->request->getParam('url'));
        $filepath = parse_url($url, PHP_URL_PATH);
        $filepath = $_SERVER['DOCUMENT_ROOT'] . $filepath;
        $content = IOHelper::getFileContents($filepath);
        craft()->request->sendFile($filepath, $content, array('forceDownload' => true), true);
    } /* -- actionDownloadFile */

}