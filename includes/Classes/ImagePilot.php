<?php
namespace IMGPLTMain\Classes;

class ImagePilot
{
    public function listMedia()
    {
        $query_images_args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
        );
        $uploads = wp_upload_dir();
        $dir_path = $uploads['basedir'];

        $query_images = new \WP_Query($query_images_args);

        $images = array();
        foreach ($query_images->posts as $image) {
            $id = $image->ID;
            $path = get_attached_file($id);
            $size = intval(filesize($path) / 1024);
            $fileName = basename($path);

            $relative_path = str_replace($dir_path, '', $path);
            $relative_path = str_replace($fileName, '', $relative_path);

            $isSquooshed = get_post_meta($id, "is_compressed", true);
            $image->size = $size;
            $image->is_compressed = $isSquooshed;
            $image->url = $uploads['baseurl'].$relative_path.$fileName;
            $image->relativePath = $relative_path;
            $image->fileName = $fileName;
            $images[] = $image;
        }
        echo json_encode($images);
        die();
    }

    public function listFiles()
    {
        $uploads_dir = wp_upload_dir(); // get the Wordpress uploads directory path
        $dir_path = $uploads_dir['basedir']; // get the directory path
        $dir_url = $uploads_dir['baseurl'];
        $image_extensions = $this->getOptions()['imageFormats']; // define an array of image extensions
        $image_files = array(); // initialize an empty array to store the image files
        
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir_path)); // create a recursive iterator to traverse all subdirectories
        foreach ($iterator as $file) { // loop through each file in the directory
            $extension = pathinfo($file, PATHINFO_EXTENSION); // get the file extension
            if (in_array(strtolower($extension), $image_extensions)) { // check if the file extension is in the array
                $relative_path = str_replace($dir_path, '', $file->getPathname());
                $relative_path = str_replace( $file->getFilename(),'',$relative_path);
                $imgObj = ['fileUrl' => $dir_url . $relative_path.$file->getFilename(),'url' => $dir_url . $relative_path.$file->getFilename(), 'fileName' => $file->getFilename(),'post_title' => $file->getFilename(), 'filePath' => $file->getPathname(), 'size' => (int)($file->getSize() / (1024)), 'canOverride' => $file->isWritable(), 'relativePath' => $relative_path,'ID'=>0];
                $image_files[] = $imgObj; // add the file to the array
            }
        }

        wp_send_json_success($image_files);
        wp_die();
    }

    public function saveImage()
    {

        $id = intval($_REQUEST['img_id']);
        $should_override = boolval($_REQUEST['override']);
        $id = intval($id);
        $prev_path = get_attached_file($id);

        $path = $this->uploadFile();

        if ($path != "") {

            if ($should_override && $prev_path != "") {
                wp_delete_file($prev_path);
            }

            update_attached_file($id, $path);
            wp_generate_attachment_metadata($id, $path);
            add_post_meta($id, "is_compressed", true);
            wp_send_json_success($path);
        } else {
            wp_send_json_error("Upload failed");
        }

        wp_die();
    }

    public function saveFile()
    {
        $relative_path = sanitize_text_field($_REQUEST['relativePath']);
        
        $target_dir = wp_upload_dir()['basedir'] .$relative_path;
        $file_name = sanitize_text_field($_FILES['image']['name']);
        $target_file = $target_dir . basename($file_name);

        // Upload the file to the target directory using wp_upload_bits()
        $upload = wp_upload_bits($file_name, null, file_get_contents($_FILES['image']['tmp_name']), date('Y/m'));

        // Move the uploaded file to the target directory
        if ($upload['error'] === false) {
            if (!file_exists($target_dir)) {
                wp_mkdir_p($target_dir);
            }
            // if(file_exists($target_file)){
            //     unlink($target_file);
            // }
            $moved = rename($upload['file'], $target_file);
            if ($moved) {
                if(isset($_REQUEST['img_id'])){
                    $id = intval($_REQUEST['img_id']);
                    wp_generate_attachment_metadata($id,  $target_file);
                    add_post_meta($id, "is_compressed", true);
                }
                wp_send_json_success($target_file);
            } else {
                // The file could not be moved to the target directory
                wp_send_json_error($target_file);
            }
        } else {
            // There was an error uploading the file
            wp_send_json_error($upload['error']);
        }

    }

    function processFile(){
        
    }

    public function uploadFile()
    {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded_file = wp_handle_upload($_FILES['image'], array('test_form' => false));
            if ($uploaded_file && !isset($uploaded_file['error'])) {

                return $uploaded_file['file'];

            } else {
                return "";
            }
        } else {
            return "";
        }
    }

    public function updateSettings()
    {

        $data = array();

        $data['maxImageSize'] = intval($_REQUEST['settings']['maxImageSize']);
        $data['maxHeight'] = intval($_REQUEST['settings']['maxHeight']);
        $data['maxWidth'] = intval($_REQUEST['settings']['maxWidth']);
        $data['overrideOriginal'] = filter_var($_REQUEST['settings']['overrideOriginal'], FILTER_VALIDATE_BOOLEAN);
        $data['regThumb'] = filter_var($_REQUEST['settings']['regThumb'], FILTER_VALIDATE_BOOLEAN);
        $data['keepRes'] = filter_var($_REQUEST['settings']['keepRes'], FILTER_VALIDATE_BOOLEAN);
        $data['preventWPScaleDown'] = filter_var($_REQUEST['settings']['preventWPScaleDown'], FILTER_VALIDATE_BOOLEAN);
        $data['imageFormats'] = $_REQUEST['settings']['imageFormats'];

        update_option('imagepilot_settings', $data);
        wp_send_json_success($data);
        wp_die();
    }

    function getOptions()
    {
        $default = array();
        $default['maxImageSize'] = 2;
        $default['maxHeight'] = 10000;
        $default['maxWidth'] = 10000;
        $default['overrideOriginal'] = true;
        $default['regThumb'] = true;
        $default['keepRes'] = true;
        $default['preventWPScaleDown'] = true;
        $default['imageFormats'] = ["jpg","jpeg","png"];
        $data = get_option('imagepilot_settings', $default);
        return $data;
    }

    function getSettings()
    {
        $data = $this->getOptions();
        wp_send_json_success($data);
        wp_die();
    }


}