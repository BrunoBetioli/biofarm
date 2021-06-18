<?php

namespace app\Request;

use app\Libraries\DataFilter;
use libs\Model;
use libs\Router;
use libs\Auth\BlowfishPasswordHasher;

trait UserRequest
{
    public $user = null;

    public $fields = array(
        'id', 'name', 'email', 'password', 'status', 'picture', 'created', 'modified'
    );

    public $sanitized_user = null;

    public function sanitize()
    {
        $user = $_POST;
        $this->user = DataFilter::cleanArray($user);
        $this->user['image_base64'] = isset($this->user['picture_base64']) ? $this->user['picture_base64'] : null;
        $this->user['remove_picture'] = isset($this->user['remove_picture']) ? $this->user['remove_picture'] : null;
        unset($_POST);

        $this->sanitized_user = DataFilter::cleanArray($this->user, $this->fields);

        return $this->sanitized_user;
    }

    public function validate()
    {
        if (empty($this->user) && empty($this->sanitized_user)) {
            $this->sanitize();
        }

        $this->sanitized_user['password'] = isset($this->sanitized_user['password']) ? $this->sanitized_user['password'] : null;
        $this->user['confirm_password'] = isset($this->user['confirm_password']) ? $this->user['confirm_password'] : null;

        $return = array();
        if (!isset($this->sanitized_user['name']) || empty($this->sanitized_user['name'])) {
            $return[] = 'O nome não pode estar em branco.';
        }

        if (!isset($this->sanitized_user['email']) || empty($this->sanitized_user['email'])) {
            $return[] = 'O email não pode estar em branco.';
        } elseif (isset($this->sanitized_user['email']) && !filter_var($this->sanitized_user['email'], FILTER_VALIDATE_EMAIL)) {
            $return[] = 'Digite um email válido.';
        } elseif (($checkUserEmail = $this->User->user(array('email' => $this->sanitized_user['email']))) && $checkUserEmail->id != $this->sanitized_user['id']) {
            $return[] = 'Este email já está cadastrado.';
        }

        if (empty($this->sanitized_user['password']) && $this->sanitized_user['id'] == 0) {
            $return[] = 'Por favor, digite uma senha.';
        } elseif ($this->sanitized_user['password'] != $this->user['confirm_password']) {
            $return[] = 'Por favor, confirme a senha corretamente.';
        }

        if (!isset($this->sanitized_user['status']) || !in_array($this->sanitized_user['status'], array('active', 'inactive'))) {
            $return[] = 'Por favor, escolha um status de usuário válido.';
        }

        $this->user['has_upload_image'] = false;
        if (empty($return) && !empty($this->user['image_base64'])) {
            $upload_dir = WWW_ROOT.'pictures'.DS.'users'.DS;

            list($type, $image) = explode(';', $this->user['image_base64']);
            list(, $image)      = explode(',', $image);
            $image = base64_decode($image);
            list(, $type) = explode(':',$type);
            list(,$ext) = explode('/',$type);

            if (!empty($image) && !empty($type) && !empty($ext)) {
                $name_image = preg_replace('/(\.|,)/i', '-', microtime(true)).'.'.$ext;
                $upload_image = $upload_dir.$name_image;
                file_put_contents($upload_image, $image);

                if (file_exists($upload_image)) {
                    if (filesize($upload_image) <= 0) {
                        unlink($upload_image);
                    } else {
                        $this->user['has_upload_image'] = true;
                        $this->sanitized_user['picture'] = Router::normalize('/pictures/users/'.$name_image);
                    }
                }
            }

            if (!$this->user['has_upload_image']) {
                $return[] = 'Ocorreu algum erro no upload da imagem. Por favor, tente novamente.';
            }
        }

        if ($this->user['remove_picture'] == 'delete') {
            $this->sanitized_user['picture'] = null;
        }

        return $return;
    }
}