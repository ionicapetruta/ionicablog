<?php

class ImageHandler
{
    // The folder in which to save images
    public $save_dir;
    public $max_dims;

    // Sets the $save_dir on instantiation
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
     * @return string the path to the resized uploaded file
     */
    public function processUploadedImage($file, $rename = true)
    {
        // Separate the uploaded file array
        list($name, $type, $tmp, $err, $size) = array_values($file);
        // If an error occurred, throw an exception
        if ($err != UPLOAD_ERR_OK) {
            throw new Exception('An error occurred with the upload!');
            exit;
        }
        // Check that the directory exists
        $this->checkSaveDir();
        // Rename the file if the flag is set to TRUE
        if ($rename === true) {
            // Retrieve information about the image
            $img_ext = $this->getImageExtension($type);
            $name = $this->renameFile($img_ext);
            // Create the full path to the image for saving
            $filepath = $this->save_dir . $name;
            // Store the absolute path to move the image
            $absolute = $_SERVER['DOCUMENT_ROOT'] . $filepath;
            // Save the image
            if (!move_uploaded_file($tmp, $absolute)) {
                throw new Exception("Couldn't save the uploaded file!");
            }

            return $filepath;
            // Finish processing
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
        /*
        * Returns the current timestamp and a random number
        * to avoid duplicate filenames
        */
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
        // Determines the path to check
        $path = $_SERVER['DOCUMENT_ROOT'] . $this->save_dir;
        // Checks if the directory exists
        if (!is_dir($path)) {
            // Creates the directory
            if (!mkdir($path, 0777, true)) {
                // On failure, throws an error
                throw new Exception("Can't create the directory!");
            }
            // Create the directory
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
        // Assemble the necessary variables for processing
        list($src_w, $src_h) = getimagesize($img);
        list($max_w, $max_h) = $this->max_dims;
        // Check that the image is bigger than the maximum dimensions
        if ($src_w > $max_w || $src_h > $max_h) {
            // Determine the scale to which the image will be resized
            $s = min($max_w / $src_w, $max_h / $src_h);
        } else {
            /*
            * If the image is smaller than the max dimensions, keep
            * its dimensions by multiplying by 1
            */
            $s = 1;
        }
        // Get the new dimensions
        $new_w = round($src_w * $s);
        $new_h = round($src_h * $s);

        // Return the new dimensions
        return array($new_w, $new_h, $src_w, $src_h);
        // Get new image dimensions
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
}

?>