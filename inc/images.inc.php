<?php

class ImageHandler
{
    public $save_dir;
    public $max_dims;
    /**
     * The construct.
     *
     * @param string $save_dir
     *      Sets the $save_dir on instantiation.
     * @param array $max_dims
     */
    public function __construct($save_dir, $max_dims = array(350, 240))
    {
        $this->save_dir = $save_dir;
        $this->max_dims = $max_dims;
    }

    /**
     * Resizes/resamples an image uploaded via a web form
     *
     * @param array $upload the array contained in $_FILES
     * @param bool $rename whether or not the image should be renamed
     * @param bool $rename whether or not the image should be renamed
     * @return string the path to the resized uploaded file
     */
    public function processUploadedImage($file, $rename = true)
    {
        list($name, $type, $tmp, $err, $size) = array_values($file);
        if ($err != UPLOAD_ERR_OK) {
            throw new Exception('An error occurred with the upload!');
            exit;
        }
        $this->doImageResize($tmp);
        if ($rename === true) {
            $img_ext = $this->getImageExtension($type);
            $name = $this->renameFile($img_ext);
            $filepath = $this->save_dir . $name;
            $absolute = $_SERVER['DOCUMENT_ROOT'] . $filepath;
            if (!move_uploaded_file($tmp, $absolute)) {
                throw new Exception("Couldn't save the uploaded file!");
            }

            return $filepath;
        }
    }

    /**
     * Generates a unique name for a file
     *
     * Uses the current timestamp and a randomly generated number
     * to create a unique name to be used for an uploaded file.
     * This helps prevent a new file upload from overwriting an
     * existing file with the same name.
     *
     * @param string $ext the file extension for the upload
     * @return string the new filename
     */
    private function renameFile($ext)
    {
        return time() . '_' . mt_rand(1000, 9999) . $ext;
    }

    /**
     * Determines the filetype and extension of an image
     *
     * @param string $type the MIME type of the image
     * @return string the extension to be used with the file
     */
    private function getImageExtension($type)
    {
        switch ($type) {
            case 'image/gif':
                return '.gif';
            case 'image/jpeg':
            case 'image/pjpeg':
                return '.jpg';
            case 'image/png':
                return '.png';
            default:
                throw new Exception('File type is not recognized!');
        }
    }

    /**
     * Ensures that the save directory exists
     *
     * Checks for the existence of the supplied save directory,
     * and creates the directory if it doesn't exist. Creation is
     * recursive.
     *
     * @param void
     * @return void
     */
    private
    function checkSaveDir()
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . $this->save_dir;
        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                throw new Exception("Can't create the directory!");
            }
        }
    }

    /**
     * Determines new dimensions for an image
     *
     * @param string $img the path to the upload
     * @return array the new and original image dimensions
     */
    private function getNewDims($img)
    {
        list($src_w, $src_h) = getimagesize($img);
        list($max_w, $max_h) = $this->max_dims;
        if ($src_w > $max_w || $src_h > $max_h) {
            $s = min($max_w / $src_w, $max_h / $src_h);
        } else {
            $s = 1;
        }
        $new_w = round($src_w * $s);
        $new_h = round($src_h * $s);

        return array($new_w, $new_h, $src_w, $src_h);
    }

    /**
     * Determines how to process images
     *
     * Uses the MIME type of the provided image to determine
     * what image handling functions should be used. This
     * increases the perfomance of the script versus using
     * imagecreatefromstring().
     *
     * @param string $img the path to the upload
     * @return array the image type-specific functions
     */
    private function getImageFunctions($img)
    {
        $info = getimagesize($img);
        switch ($info['mime']) {
            case 'image/jpeg':
            case 'image/pjpeg':
                return array('imagecreatefromjpeg', 'imagejpeg');
                break;
            case 'image/gif':
                return array('imagecreatefromgif', 'imagegif');
                break;
            case 'image/png':
                return array('imagecreatefrompng', 'imagepng');
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * Generates a resampled and resized image
     *
     * Creates and saves a new image based on the new dimensions
     * and image type-specific functions determined by other
     * class methods.
     *
     * @param array $img the path to the upload
     * @return void
     */
    private function doImageResize($img)
    {
        $d = $this->getNewDims($img);
        $funcs = $this->getImageFunctions($img);
        $src_img = $funcs[0]($img);
        $new_img = imagecreatetruecolor($d[0], $d[1]);
        if (imagecopyresampled(
            $new_img,
            $src_img,
            0,
            0,
            0,
            0,
            $d[0],
            $d[1],
            $d[2],
            $d[3]
        )
        ) {
            imagedestroy($src_img);
            if ($new_img && $funcs[1]($new_img, $img)) {
                imagedestroy($new_img);
            } else {
                throw new Exception('Failed to save the new image!');
            }
        } else {
            throw new Exception('Could not resample the image!');
        }
    }
}
?>