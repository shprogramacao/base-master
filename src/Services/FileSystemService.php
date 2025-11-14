<?php

namespace AndersonScherdovski\Base\Services;

use Illuminate\Support\Facades\Storage;

class FileSystemService
{
    /*
     * Upload file FTP
     *
     * @param $file
     * @param $nameDir
     *
     * @return false|string
     * @throws \Exception
     */
    public static function uploadFile($file, $nameDir)
    {
        $hash = bin2hex(random_bytes(6));
        $extension = $file->extension();
        $path = $nameDir . '/' . time() . $hash . '.' . $extension;
        $file->storeAs('public', $path);
        return $path;
    }

    public static function uploadBase64File($base64, $nameDir, $oldImage = null)
    {
        $name = time() . random_int(1, 33424234234234) .  '.' . FileSystemService::getMimeType(mime_content_type($base64));
        $safeName = $nameDir . '/' . $name;
        Storage::put('public/' . $safeName, file_get_contents($base64));

        if ($oldImage) {
            Storage::delete('public/' . $nameDir . '/' . $oldImage);
        }

        return $name;
    }

    static function getMimeType($mimeType)
    {

        $mimes = array(
            'text/plain' => 'txt',
            'text/html' => 'htm',
            'text/html' => 'html',
            'text/html' => 'php',
            'text/css' => 'css',
            'application/javascript' => 'js',
            'application/json' => 'json',
            'application/xml' => 'xml',
            'application/x-shockwave-flash' => 'swf',
            'video/x-flv' => 'flv',

            // images
            'image/png' => 'png',
            'image/jpeg' => 'jpe',
            'jimage/jpeg' => 'peg',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/vnd' => 'ico',
            'timage/tiff' => 'iff',
            'image/tiff' => 'tif',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'application/zip' => 'zip',
            'application/x-rar-compressed' => 'rar',
            'application/x-msdownload' => 'exe',
            'application/x-msdownload' => 'msi',
            'application/vnd.ms-cab-compressed' => 'cab',

            // audio/video
            'audio/mpeg' => 'mp3',
            'video/quicktime' => 'qt',
            'video/quicktime' => 'mov',
            'video/quicktime' => 'mov',

            // adobe
            'image/vnd.adobe.photoshop' => 'psd',
            'application/pdf' => 'pdf',
            'application/postscript' => 'ai',
            'application/postscript' => 'eps',
            'application/postscript' => 'ps',

            // ms office
            'application/msword' => 'doc',
            'application/rtf' => 'rtf',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/msword' => 'docx',


            // open office
            'application/octet-stream' => 'ogg',
            'application/vnd.oasis.opendocument.text' => 'odt',
            'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
        );

        if (isset($mimes[$mimeType])) {
            return $mimes[$mimeType];
        } else {
            return 'application/octet-stream';
        }
    }

    public static function uploadBase64FileMimetype($base64, $nameDir, $fileName, $mimetype)
    {
        if ($base64) {
            $arrayDir = explode('/', $nameDir);
            $dirToSave = '';
            foreach ($arrayDir as $dir) {
                $dirToSave .= $dir;
                if ($dir !== '..') {
                    if (!file_exists($dirToSave)) {
                        mkdir("$dirToSave", 0700);
                    }
                }
            }
            $extension = explode('/', $mimetype)[1];
            $base64 = 'data:' . $mimetype . ';base64,' . $base64;

            file_put_contents("$nameDir/$fileName.$extension", file_get_contents($base64));
            return "$fileName.$extension";
        }
    }

    /**
     * Remove file FTP
     *
     * @param string $name
     */
    public static function removeFile($name)
    {
        Storage::delete($name);
    }
}
