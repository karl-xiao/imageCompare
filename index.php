<?php

/**
 * Created by PhpStorm.
 * User: xqc
 * Date: 2018/3/5
 * Time: 下午1:41
 */
require 'grafika/src/autoloader.php';
use Grafika\Grafika;

class index {
    private static $obj;
    private static $service;

    public static function getInstance() {
        return self::$obj = new self();
    }

    public static function cropImage($src, $newSrc, $offset = 60, $width = 110, $height = 100) {
        self::$service = Grafika::createEditor();
        self::$service->open($image, $src);
        self::$service->crop($image, $width, $height, 'top-right', 0, $offset);
        self::$service->save($image, $newSrc);
        self::$service->free($image);
        return $newSrc;
    }

    public static function compareImage($image1, $image2) {
        self::$service = Grafika::createEditor();
        $ret           = self::$service->compare($image1, $image2);
        return $ret ?: 100;
    }

    public static function compareImageByColor($image1, $image2) {
        $color1 = self::getColor($image1);
        $color2 = self::getColor($image2);

        $ret = self::match($color1, $color2);
        return $ret;
    }

    public static function compareDirImages($dir) {
        $handle = opendir($dir);
        if (!$handle) {
            return null;
        } else {
            $result = array();
            while (($fl = readdir($handle)) !== false) {
                $temp = $dir . DIRECTORY_SEPARATOR . $fl;
                //如果不加  $fl!='.' && $fl != '..'  则会造成把$dir的父级目录也读取出来
                if (is_dir($temp) && $fl != '.' && $fl != '..') {
                    self::compareDirImages($temp);
                } else {
                    if ($fl != '.' && $fl != '..') {
                        $fileInfo     = pathinfo($temp);
                        $cropTempFile = $fileInfo['dirname'] . "/crop/" . $fileInfo['filename'] . "-crop.jpg";

                        //获取图片大小并裁出合适尺寸
                        $imageSize = getimagesize($temp);
                        if ($imageSize['1'] == 1920) {
                            self::cropImage($temp, $cropTempFile, 60, 140, 120);
                            $result[] = array(
                                'image' => $temp,
                                'data'  => array(
                                    'qq-qun' => self::compareImageByColor($cropTempFile, 'qq-qun-st.jpg'),
                                    'qq-ren' => self::compareImageByColor($cropTempFile, 'qq-ren-st.jpg'),
                                    'wx-qun' => self::compareImageByColor($cropTempFile, 'weixin-qun-st.jpg'),
                                    'wx-ren' => self::compareImageByColor($cropTempFile, 'weixin-ren-st.jpg')
                                )
                            );
                        } else {
                            self::cropImage($temp, $cropTempFile, 60, 90, 70);
                            $result[] = array(
                                'image' => $temp,
                                'data'  => array(
                                    'qq-qun' => self::compareImageByColor($cropTempFile, 'qq-qun-st-small.jpg'),
                                    'qq-ren' => self::compareImageByColor($cropTempFile, 'qq-ren-st-small.jpg'),
                                    'wx-qun' => self::compareImageByColor($cropTempFile, 'weixin-qun-st-small.jpg'),
                                    'wx-ren' => self::compareImageByColor($cropTempFile, 'weixin-ren-st-small.jpg')
                                )
                            );
                        }

                        unlink($cropTempFile);
                    }
                }
            }
            return $result;
        }
    }

    public static function getColor($file, $gray = 0, $contrast = 0) {
        $canvas_w = 16;
        $canvas_h = 16;
        $ims      = imagecreatefromjpeg($file);
        $newIm    = imagecreatetruecolor($canvas_w, $canvas_h);
        imagecopyresampled($newIm, $ims, 0, 0, 0, 0, $canvas_w, $canvas_h, imagesx($ims), imagesy($ims));
        if ($gray) {
            imagefilter($newIm, IMG_FILTER_GRAYSCALE);
        }

        if ($contrast) {
            imagefilter($newIm, IMG_FILTER_CONTRAST, $contrast);
        }
        $rgb = array();
        for ($x = 0; $x < $canvas_w; $x++) {
            for ($y = 0; $y < $canvas_h; $y++) {
                $rgb[] = imagecolorat($newIm, $x, $y);
            }
        }
        return $rgb;
    }

    public static function match($match, $match2, $rate = 25) {
        foreach ($match2 as $key => $rgb) {
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            $r2 = ($match[$key] >> 16) & 0xFF;
            $g2 = ($match[$key] >> 8) & 0xFF;
            $b2 = $match[$key] & 0xFF;

            if (abs($r - $r2) < $rate && abs($g - $g2) < $rate && abs($b - $b2) < $rate) {
                $match[$key]  = 1;
                $match2[$key] = 1;
            } else {
                $match[$key]  = 'a';
                $match2[$key] = 'b';
            }
        }
        similar_text(implode('', $match), implode('', $match2), $num);
        return $num;
    }
}

$service = index::getInstance();
$ret     = $service::compareDirImages('temp');
//var_dump($ret);
//$ret     = $service::compareImage('weixin-qun-st.jpg', 'temp/crop/Screenshot_2018-03-06-15-02-14-crop.png');
var_dump($ret);
exit();

//foreach ($data as $item) {
//    $service::cropImage($item['src'], 'crop/' . $item['new']);
//}
//
//$compare = $service::compareImage('crop/ios-qun-crop.jpg', 'crop/an-qun-crop.jpg');
//var_dump($compare);
//die;
