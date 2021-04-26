<?php
use WebPConvert\WebPConvert;

Event::listen('evolution.OnWebPagePrerender', function ($params) {


    $documentOutput = evo()->documentOutput; ;

    $FS = \Helpers\FS::getInstance();
    $cacheFolder = 'assets/cache/webp/';
    if (!$FS->checkDir(MODX_BASE_PATH . $cacheFolder)) {
        $FS->makeDir(MODX_BASE_PATH . $cacheFolder, 0775);
    }
    $cacheFolderHtAccessFile = MODX_BASE_PATH.$cacheFolder.'.htaccess';
    if(!$FS->checkFile($cacheFolderHtAccessFile)){
        file_put_contents($cacheFolderHtAccessFile,'
        IndexIgnore */*
        <Files *.php>
            Order Deny,Allow
            Deny from all
        </Files>
        ');
    }

    //$modx->logEvent(1,2,$_SERVER['HTTP_USER_AGENT'].' - '.$_GET['q'],$_GET['test'].' - webp');
    $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $iosDevice = array('iphone', 'ipod', 'ipad', 'macintosh', 'mac os', 'Edge', 'MSIE');

    $isGoogleBot = stripos($userAgent, 'Chrome-Lighthouse') !== false;

    if ($isGoogleBot === false) {
        foreach ($iosDevice as $val) {
            if (stripos($userAgent, $val) !== false) {
                return $documentOutput;
            }
        }
    }

    function webpReplace($matches)
    {
        $FS = \Helpers\FS::getInstance();
        $cacheFolder = 'assets/cache/webp/';


        $originalImage = trim($matches['image']);

        if (!in_array($FS->takeFileExt($originalImage), ['jpg', 'jpeg', 'png'])) {
            return $matches[0];
        }
        $originalImage = $FS->relativePath($originalImage);


        if ($originalImage && file_exists(MODX_BASE_PATH . $originalImage)) {

            $destination = $cacheFolder . str_replace(['assets/images/', 'assets/galleries/', 'assets/cache/images/'], '', $originalImage) . '.webp';
            $destinationFull = MODX_BASE_PATH . $destination;


            if (!file_exists($destinationFull)) {
                $image = MODX_BASE_PATH . $originalImage;

                try {
                    WebPConvert::convert($image, $destinationFull);
                    $newImage = $destination;
                } catch (Exception $e) {
                    return $matches[0];
                }
            } else {
                $newImage = $destination;
            }

            return str_replace($matches['image'], $newImage, $matches[0]);
        }
        return $matches[0];
    }


    $documentOutput = preg_replace_callback('~<source[^>]+srcset=["\'](?<image>[^\'">]+)["\'][^>]*>~i', 'webpReplace', $documentOutput);
    $documentOutput = preg_replace_callback('~<img[^>]+src=["\'](?<image>[^\'">]+)["\'][^>]*>~i', 'webpReplace', $documentOutput);
    $documentOutput = preg_replace_callback('~<img[^>]+data-src=["\'](?<image>[^\'">]+)["\'][^>]*>~i', 'webpReplace', $documentOutput);
    $documentOutput = preg_replace_callback('~<img[^>]+data-lazy=["\'](?<image>[^\'">]+)["\'][^>]*>~i', 'webpReplace', $documentOutput);
    $documentOutput = preg_replace_callback('~\bbackground(-image)?\s*:(.*?)\(\s*(\'|")?(?<image>.*?)\3?\s*\)~i', 'webpReplace', $documentOutput);

    evo()->documentOutput = $documentOutput;
});
