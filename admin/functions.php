<?php

function createThumbnail($source, $destination, $width, $height)
{
    if (!file_exists($source)) {
        return false; // Source file doesn't exist
    }

    $imageInfo = getimagesize($source);
    if ($imageInfo === false) {
        return false; // Not a valid image
    }

    list($w, $h, $type) = $imageInfo;

    $ratio = $w / $h;
    if ($width / $height > $ratio) {
        $newWidth = $height * $ratio;
        $newHeight = $height;
    } else {
        $newHeight = $width / $ratio;
        $newWidth = $width;
    }

    $new = imagecreatetruecolor($newWidth, $newHeight);

    // Initial image creation based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $img = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_GIF:
            $img = imagecreatefromgif($source);
            break;
        case IMAGETYPE_PNG:
            $img = imagecreatefrompng($source);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagecreatefromwebp')) {
                $img = imagecreatefromwebp($source);
            } else {
                return false; // WebP not supported
            }
            break;
        default:
            return false; // Unsupported type
    }

    if (!$img) {
        return false;
    }

    // Preserve transparency where possible
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF || $type == IMAGETYPE_WEBP) {
        imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
        imagealphablending($new, false);
        imagesavealpha($new, true);
    }

    // Resize
    imagecopyresampled($new, $img, 0, 0, 0, 0, $newWidth, $newHeight, $w, $h);

    // Save result
    $result = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($new, $destination, 85);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($new, $destination);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($new, $destination);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagewebp')) {
                $result = imagewebp($new, $destination, 85);
            } else {
                $result = false; // WebP not supported
            }
            break;
    }

    imagedestroy($new);
    imagedestroy($img);

    return $result;
}

function generateUniqueFilename($extension)
{
    return uniqid('img_', true) . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
}
