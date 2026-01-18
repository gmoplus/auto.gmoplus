<?php
/*
 * jQuery File Upload Plugin PHP Example 5.2.6
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://creativecommons.org/licenses/MIT/
 */

$reefless->loadClass('Resize');
$reefless->loadClass('Crop');
$reefless->loadClass('Banners', null, 'banners');

class UploadHandler
{
    protected $options;

    public function __construct($options = null)
    {
        $this->options = [
            'script_url' => $_SERVER['PHP_SELF'],
            'banners_dir' => 'banners' . RL_DS,
            'upload_dir' => RL_FILES . 'banners' . RL_DS,
            'upload_url' => RL_FILES_URL . 'banners/',
            'dir_name' => null,
            'param_name' => 'files',
            'max_file_size' => null,
            'min_file_size' => 1,
            'accept_file_types' => '/^image\/(gif|jpeg|png)$/i',
            'accept_file_types_ie' => '/\.(gif|jpeg|png|jpg|jpe)$/i',
            'max_number_of_files' => null,
            'discard_aborted_uploads' => true,
            'image_versions' => [
                'thumbnail' => [
                    'prefix' => 'banner_',
                    'max_width' => (int) $_POST['box_width'],
                    'max_height' => (int) $_POST['box_height'],
                    'watermark' => false,
                ],
            ],
        ];

        if ($options) {
            $this->options = array_replace_recursive($this->options, $options);
        }
    }

    public function get()
    {
        $file_name = isset($_REQUEST['file']) ? basename(stripslashes($_REQUEST['file'])) : null;
        if ($file_name) {
            $info = $this->getFileObject($file_name);
        } else {
            $info = $this->getFileObjects();
        }

        header('Content-type: application/json');

        echo json_encode($info);
    }

    public function getFileObject($file_name)
    {
        $file_path = $this->options['upload_dir'] . $file_name;

        if (is_file($file_path) && $file_name[0] !== '.') {
            $file = new stdClass();
            $file->name = $file_name;
            $file->size = filesize($file_path);
            $file->url = $this->options['upload_url'] . rawurlencode($file->name);

            foreach ($this->options['image_versions'] as $version => $options) {
                if (is_file($options['upload_dir'] . $file_name)) {
                    $file->{$version . '_url'} = $options['upload_url'] . rawurlencode($file->name);
                }
            }

            $file->delete_url = $this->options['script_url'] . '?file=' . rawurlencode($file->name);
            $file->delete_url .= '&_method=DELETE';
            $file->delete_type = 'POST';

            return $file;
        }

        return null;
    }

    public function getFileObjects()
    {
        global $rlDb, $banner_id;

        $banners = $rlDb->fetch('*', ['ID' => $banner_id], "AND `Image` <> '' AND `Image` <> 'html'", null, 'banners');

        if (!$banners) {
            return [];
        }

        $controller = defined('REALM') && REALM == 'admin' ? 'admin' : 'account';
        $files = [];

        foreach ($banners as $banner) {
            $file = new stdClass();
            $file->banner_id = $banner['ID'];
            $file->name = $banner['Image'];
            $file->size = filesize(RL_FILES . $this->options['banners_dir'] . RL_DS . $banner['Image']);
            $file->thumbnail_url = $this->options['upload_url'] . '/' . $banner['Image'];
            $file->delete_url = RL_PLUGINS_URL . 'banners/upload/' . $controller . '.php';
            $file->delete_url .= '?file=' . $banner['Image'] . '&id=' . $banner_id . '&_method=DELETE';
            $file->delete_type = 'POST';

            $files[] = $file;
        }

        return $files;
    }

    public function post()
    {
        global $rlDb, $rlBanners, $config, $banner_id;

        if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
            $this->delete();
            return;
        }

        if (defined('REALM') && REALM == 'admin' && $banner_id == 0) {
            $insert = [
                'Box' => $_POST['banner_box'],
                'Type' => $_POST['banner_type'],
                'Status' => 'incomplete',
            ];
            $rlDb->insertOne($insert, 'banners');

            $banner_id = $rlDb->insertID();
            $_SESSION['banner_id'] = $_SESSION['add_banner_id'] = $banner_id;
        }

        $folderInfo = $rlBanners->makeBannerFolder($banner_id, $this->options);

        $this->options['upload_dir'] = $folderInfo['dir'];
        $this->options['upload_url'] = $folderInfo['url'];
        $this->options['dir_name'] = $folderInfo['dirName'];

        $upload = isset($_FILES[$this->options['param_name']]) ? $_FILES[$this->options['param_name']] : [
            'tmp_name' => null,
            'name' => null,
            'size' => null,
            'type' => null,
            'error' => null,
        ];

        $banner = $this->handleFileUpload(
            $upload['tmp_name'],
            isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : $upload['name'],
            isset($_SERVER['HTTP_X_FILE_SIZE']) ? $_SERVER['HTTP_X_FILE_SIZE'] : $upload['size'],
            isset($_SERVER['HTTP_X_FILE_TYPE']) ? $_SERVER['HTTP_X_FILE_TYPE'] : $upload['type'],
            $upload['error']
        );

        // add media to db
        $update = [
            'fields' => [
                'Image' => $folderInfo['dirName'] . $banner->thumbnail,
                'Type' => 'image',
            ],
            'where' => [
                'ID' => $banner_id,
            ],
        ];

        if (!defined('REALM') && !$config['banners_auto_approval']) {
            $update['fields']['Status'] = 'pending';
        }

        if ((defined('REALM') && REALM == 'admin') && $_POST['banner_box']) {
            $update['fields']['Box'] = $_POST['banner_box'];
        }

        $rlDb->updateOne($update, 'banners');
        $_SESSION['banner_id'] = $banner_id;

        $banner->banner_id = (int) $banner_id;
        $banner->thumbnail_url = $this->options['upload_url'] . $banner->thumbnail;

        header('Vary: Accept');

        if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-type: application/json');
        } else {
            header('Content-type: text/plain');
        }

        echo json_encode([$banner]);
    }

    public function delete()
    {
        global $rlDb, $reefless, $banner_id;

        $id = (int) $_REQUEST['id'];
        $success = ($id == $banner_id);
        $banner = $rlDb->getRow("SELECT `Image`, `Status` FROM `{db_prefix}banners` WHERE `ID` = {$banner_id}");

        if ($success && '' != $banner['Image']) {
            $slugDir = explode('/', $banner['Image'])[0];
            $reefless->deleteDirectory(RL_FILES . $this->options['banners_dir'] . $slugDir . "/b{$banner_id}");

            $setStatus = ('active' == $banner['Status']) ? ", `Status` = 'approval'" : '';
            $rlDb->query("UPDATE `{db_prefix}banners` SET `Image` = '' {$setStatus} WHERE `ID` = {$banner_id}");
        }

        header('Content-type: application/json');

        echo json_encode($success);
    }

    public function handleFileUpload($uploaded_file, $name, $size, $type, $error)
    {
        global $banner_id;

        $uf_info = getimagesize($uploaded_file);
        if (!$uf_info || !preg_match('/^image\/(gif|jpe?g|png)$/i', $uf_info['mime'])) {
            die("Filetype not allowed");
        }

        $ext = array_reverse(explode('.', $name));
        $ext = strtolower($ext[0]);

        $file = new stdClass();
        $file->name = 'tmp_' . time() . mt_rand() . '.' . $ext;
        $file->size = intval($size);
        $file->type = $type;
        $error = $this->hasError($uploaded_file, $file, $error);
        $controller = defined('REALM') && REALM == 'admin' ? 'admin' : 'account';

        if (!$error && $file->name) {
            $file_path = $this->options['upload_dir'] . $file->name;
            $append_file = is_file($file_path) && $file->size > filesize($file_path);
            clearstatcache();
            if ($uploaded_file && is_uploaded_file($uploaded_file)) {
                // multipart/form-data uploads (POST method uploads)
                if ($append_file) {
                    file_put_contents($file_path, fopen($uploaded_file, 'r'), FILE_APPEND);
                } else {
                    move_uploaded_file($uploaded_file, $file_path);
                }
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents($file_path, fopen('php://input', 'r'), $append_file ? FILE_APPEND : 0);
            }

            $file_size = filesize($file_path);

            if ($file_size === $file->size) {
                $file->url = $this->options['upload_url'] . rawurlencode($file->name);

                foreach ($this->options['image_versions'] as $version => $options) {
                    $new = $options['prefix'] . time() . mt_rand() . '.' . $ext;
                    if ($version == 'thumbnail') {
                        $file->delete_filename = $new;
                    }

                    if ($this->createScaledImage($file->name, $new, $options)) {
                        $file->{$version} = $new;
                    }
                }
            } else {
                if ($this->options['discard_aborted_uploads']) {
                    unlink($file_path);
                    $file->error = 'abort';
                }
            }

            // force remove original file
            unlink($file_path);

            $file->size = $file_size;
            $file->delete_url = RL_PLUGINS_URL . 'banners/upload/' . $controller . '.php';
            $file->delete_url .= '?file=' . $this->options['dir_name'] . rawurlencode($file->delete_filename);
            $file->delete_url .= '&id=' . $banner_id . '&_method=DELETE';
            $file->delete_type = 'POST';
        } else {
            $file->error = $error;
        }

        return $file;
    }

    public function hasError($uploaded_file, $file, $error)
    {
        if ($error) {
            return $error;
        } elseif (!preg_match($this->options['accept_file_types'], $file->type)) {
            if (!preg_match($this->options['accept_file_types_ie'], $file->name)) {
                return 'acceptFileTypes';
            }
        }

        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            $file_size = filesize($uploaded_file);
        } else {
            $file_size = $_SERVER['CONTENT_LENGTH'];
        }

        if ($this->options['max_file_size']
            && ($file_size > $this->options['max_file_size'] || $file->size > $this->options['max_file_size'])
        ) {
            return 'maxFileSize';
        } elseif ($this->options['min_file_size'] && $file_size < $this->options['min_file_size']) {
            return 'minFileSize';
        } elseif (is_int($this->options['max_number_of_files'])
            && (count($this->getFileObjects()) >= $this->options['max_number_of_files'])
        ) {
            return 'maxNumberOfFiles';
        }

        return $error;
    }

    public function createScaledImage($file_name, $new_file_name, $options)
    {
        global $rlResize, $rlCrop, $config, $rlBanners;

        $file_path = $this->options['upload_dir'] . $file_name;
        $new_file_path = $this->options['upload_dir'] . $new_file_name;
        list($width, $height) = getimagesize($file_path);

        if ($rlBanners->isAnimatedGif($file_path)
            || ($options['width'] == $width && $options['height'] == $height)
        ) {
            copy($file_path, $new_file_path);
        } else {
            $rlCrop->loadImage($file_path);
            $rlCrop->cropBySize($options['max_width'], $options['max_height'], ccCENTER);
            $rlCrop->saveImage($new_file_path, $config['img_quality']);
            $rlCrop->flushImages();

            $rlResize->resize(
                $new_file_path,
                $new_file_path,
                'C',
                [$options['max_width'],
                $options['max_height']],
                true,
                $options['watermark']
            );
        }

        return true;
    }
}

$uploadHandler = new UploadHandler();

header('Pragma: no-cache');
header('Cache-Control: private, no-cache');
header('Content-Disposition: inline; filename="files.json"');
header('X-Content-Type-Options: nosniff');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'HEAD':
    case 'GET':
        $uploadHandler->get();
        break;
    case 'POST':
        $uploadHandler->post();
        break;
    case 'DELETE':
        $uploadHandler->delete();
        break;
    case 'OPTIONS':
        break;
    default:
        header('HTTP/1.0 405 Method Not Allowed');
}
