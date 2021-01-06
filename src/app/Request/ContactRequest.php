<?php

namespace app\Request;

use helpers\DocumentChecker\DocumentChecker;
use libs\Model;
use libs\DataFilter;
use libs\DataValidator;
use libs\Router;

trait ContactRequest
{
    public $contact = null;

    public $fields = array(
        'id', 'name', 'email', 'phone', 'document', 'birth_date', 'picture', 'address', 'house_number', 'address_complement', 'zip_code', 'district', 'city', 'state', 'country', 'created', 'modified'
    );

    public $sanitized_contact = null;

    public function sanitize()
    {
        $contact = $_POST;
        $this->contact = DataFilter::cleanArray($contact);
        $this->contact['image_base64'] = isset($this->contact['picture_base64']) ? $this->contact['picture_base64'] : null;
        $this->contact['remove_picture'] = isset($this->contact['remove_picture']) ? $this->contact['remove_picture'] : null;
        unset($_POST);

        $this->sanitized_contact = DataFilter::cleanArray($this->contact, $this->fields);
        $this->sanitized_contact['document'] = (string) preg_replace('/[^0-9]/', '', $this->contact['document']);

        return $this->sanitized_contact;
    }

    public function validate()
    {
        if (empty($this->contact) && empty($this->sanitized_contact)) {
            $this->sanitize();
        }

        $return = array();
        if (!isset($this->sanitized_contact['name']) || empty($this->sanitized_contact['name'])) {
            $return[] = 'O nome não pode estar em branco. wow';
        }

        if (!isset($this->sanitized_contact['email']) || empty($this->sanitized_contact['email'])) {
            $return[] = 'O email não pode estar em branco.';
        } elseif (isset($this->sanitized_contact['email']) && !filter_var($this->sanitized_contact['email'], FILTER_VALIDATE_EMAIL)) {
            $return[] = 'Digite um email válido.';
        } elseif (($checkContactEmail = $this->Contact->contact(array('email' => $this->sanitized_contact['email']))) && $checkContactEmail->id != $this->sanitized_contact['id']) {
            $return[] = 'Este email já está cadastrado.';
        }

        if (!isset($this->sanitized_contact['document']) || empty($this->sanitized_contact['document'])) {
            $return[] = 'O CPF ou CNPJ não pode estar em branco.';
        } else {
            $document = new DocumentChecker($this->sanitized_contact['document']);
            $validateDoc = $document->validate();
            if ($validateDoc !== true) {
                $return[] = 'Digite um CPF ou CNPJ válido.';
            } elseif (($checkClientDoc = $this->Contact->contact(array('document' => $this->sanitized_contact['document']))) && $checkClientDoc->id != $this->sanitized_contact['id']) {
                $return[] = 'Este '.$document->check_cpf_cnpj().' já está cadastrado.';
            }
        }

        if (!isset($this->sanitized_contact['birth_date']) || empty($this->sanitized_contact['birth_date'])) {
            $return[] = 'A data de nascimento não pode estar em branco.';
        } elseif (isset($this->sanitized_contact['birth_date']) && !empty($this->sanitized_contact['birth_date'])) {
            if (!DataValidator::isDateOrHour($this->sanitized_contact['birth_date'], 'd/m/Y')) {
                $return[] = 'Por favor, informe uma data de nascimento válida.';
            } else {
                $this->sanitized_contact['birth_date'] = DataFilter::dateOrHour($this->sanitized_contact['birth_date'], 'Y-m-d', 'd/m/Y');
            }
        }

        if (!isset($this->sanitized_contact['zip_code']) || empty($this->sanitized_contact['zip_code'])) {
            $return[] = 'O CEP não pode estar em branco.';
        }

        if (!isset($this->sanitized_contact['address']) || empty($this->sanitized_contact['address'])) {
            $return[] = 'O endereço não pode estar em branco.';
        }

        if (!isset($this->sanitized_contact['house_number']) || empty($this->sanitized_contact['house_number'])) {
            $return[] = 'O número não pode estar em branco.';
        }

        if (!isset($this->sanitized_contact['district']) || empty($this->sanitized_contact['district'])) {
            $return[] = 'O bairro não pode estar em branco.';
        }

        if (!isset($this->sanitized_contact['city']) || empty($this->sanitized_contact['city'])) {
            $return[] = 'A cidade não pode estar em branco.';
        }

        if (!isset($this->sanitized_contact['state']) || empty($this->sanitized_contact['state'])) {
            $return[] = 'O estado não pode estar em branco.';
        }

        if (!isset($this->sanitized_contact['country']) || empty($this->sanitized_contact['country'])) {
            $return[] = 'O país não pode estar em branco.';
        }

        $this->contact['has_upload_image'] = false;
        if (empty($return) && !empty($this->contact['image_base64'])) {
            $upload_dir = WWW_ROOT.'pictures'.DS.'contacts'.DS;

            list($type, $image) = explode(';', $this->contact['image_base64']);
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
                        $this->contact['has_upload_image'] = true;
                        $this->sanitized_contact['picture'] = Router::normalize('/pictures/contacts/'.$name_image);
                    }
                }
            }

            if (!$this->contact['has_upload_image']) {
                $return[] = 'Ocorreu algum erro no upload da imagem. Por favor, tente novamente.';
            }
        }

        if ($this->contact['remove_picture'] == 'delete') {
            $this->sanitized_contact['picture'] = null;
        }

        return $return;
    }
}
