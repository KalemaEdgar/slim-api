<?php

namespace Chatter\Middleware;

class ImageRemoveExif
{
    public function __invoke($request, $response, $next)
    {
        
        $files = $request->getUploadedFiles();
        $newFile = $files['file'];
        $newFileType = $newFile->getClientMediaType();
        $uploadedFileName = $newFile->getClientFileName();
        $newFile->moveTo("assets/images/raw/$uploadedFileName");
        $pngFile = 'assets/images/' . substr($uploadedFileName, 0, -4) . '.png';

        // Check if the image is a jpeg (jpeg may have exif data), convert it to a png
        if ('image/jpeg' == $newFileType) {
            $_img = imagecreatefromjpeg('assets/images/raw/' . $uploadedFileName);
            imagepng($_img, $pngFile);
        }

        $request = $request->withAttribute('png_filename', $pngFile);
        $response = $next($request, $response);

        return $response;

    }
}

