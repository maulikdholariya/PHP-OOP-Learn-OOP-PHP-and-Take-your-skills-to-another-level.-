<?php

class User extends Db_object {

    protected static $db_table = "users";
    protected static $db_table_fields = array('username', 'password', 'first_name', 'last_name', 'user_image');
    public $id;
    public $username;
    public $password;
    public $first_name;
    public $last_name;
    public $user_image;
    public $image_placeholder = "http://placehold.it/400x400&text=image";

    public $tmp_path;
    public $upload_directory = "images";
    public $errors = array();
    public $upload_errors_array = array(

        UPLOAD_ERR_OK => "There is no error",
        UPLOAD_ERR_INI_SIZE => "uploaded file size exceed max file size",
        UPLOAD_ERR_FORM_SIZE => "uploaded file exceeds max file size directive",
        UPLOAD_ERR_PARTIAL => "only partially uploaded",
        UPLOAD_ERR_NO_FILE => "no file was uploaded",
        UPLOAD_ERR_NO_TMP_DIR => "missing a temp folder",
        UPLOAD_ERR_CANT_WRITE => "failed to write file to disk",
        UPLOAD_ERR_EXTENSION => "a php extension stopped the file upload",

    );

    public function set_file($file) {

        if (empty($file) || !$file || !is_array($file)) {

            $this->errors[] = "There was no file uploaded here";
            return false;
        } elseif ($file['error'] != 0) {

            $this->errors[] = $this->upload_errors_array[$file['error']];
            return false;
        } else {

            $this->user_image = basename($file['name']);
            $this->tmp_path = $file['tmp_name'];
            $this->type = $file['type'];
            $this->size = $file['size'];

        }

    }

    public function picture_path() {

        return $this->upload_directory . DS . $this->user_image;
    }

    public function upload_photo() {

        if (!empty($this->errors)) {

            return false;

        }
        if (empty($this->user_image) || empty($this->tmp_path)) {

            $this->errors[] = "the file was not available";
            return false;
        }

        $target_path = SITE_ROOT . DS . 'admin' . DS . $this->upload_directory . DS . $this->user_image;

        if (file_exists($target_path)) {
            $this->errors[] = "The file {$this->user_image} already exists";
            return false;

        }

        if (move_uploaded_file($this->tmp_path, $target_path)) {

            unset($this->tmp_path);
            return true;

        } else {

            $this->errors[] = "The file directory probably does not have permission";

            return false;
        }

    }

    public function image_path_and_placeholder() {

        return empty($this->user_image) ? $this->image_placeholder : $this->upload_directory . DS . $this->user_image;
    }

    public static function verify_user($username, $password) {

        global $database;

        $username = $database->escape_string($username);
        $password = $database->escape_string($password);

        $the_result_array = static::find_by_query("SELECT * FROM users WHERE username='{$username}' AND  password='{$password}' LIMIT 1");

        return !empty($the_result_array) ? array_shift($the_result_array) : false;

    }

    public function ajax_save_user_image($user_image, $user_id) {

        global $database;

        $user_image = $database->escape_string($user_image);
        $user_id = $database->escape_string($user_id);

        $this->user_image = $user_image;
        $this->id = $user_id;

        $sql = "UPDATE " . self::$db_table . " SET user_image = '{$this->user_image}' ";
        $sql .= " WHERE id = {$this->id} ";
        $update_image = $database->query($sql);

        echo $this->image_path_and_placeholder();
    }

    public function delete_photo() {

        if ($this->delete()) {

            $target_path = SITE_ROOT . DS . 'admin' . DS . $this->upload_directory.DS.$this->user_image;

            return unlink($target_path) ? true : false;
        } else {

            return false;
        }

    }
} // END Class USER

?>