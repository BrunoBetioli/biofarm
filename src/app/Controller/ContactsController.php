<?php
use app\Controller\AppController;
use app\Request\ContactRequest;
use libs\DataFilter;

class ContactsController extends AppController
{
    use ContactRequest;

    public function beforeFilter ()
    {
        parent::beforeFilter();
    }

    public function isAuthorized($user = null)
    {
        return parent::isAuthorized($user);
    }

    public function index()
    {
        $page = isset($this->request['params']['page']) && $this->request['params']['page'] >= 1 ? (int) $this->request['params']['page'] : 1;
        $arrConditions = array();

        if (isset($this->request['params']['query']['name'])) {
            $name = DataFilter::cleanString($this->request['params']['query']['name']);
            if (!empty($name)) {
                $arrConditions['where'] = ' WHERE (c.name LIKE "%'.$name.'%" OR c.email LIKE "%'.$name.'%")';
            }
        }

        $countContacts = $this->Contact->countContacts($arrConditions);

        $totalContacts = $countContacts[0]->count;

        $arrConditions['offset'] = ($page * $this->settings['items_per_page']) - $this->settings['items_per_page'];
        $arrConditions['count'] = $this->settings['items_per_page'];

        $contacts = $this->Contact->contacts($arrConditions);

        $totalPages = ((int) ceil($totalContacts / $this->settings['items_per_page']));
        $pagination = null;
        $error_msg = null;

        if ($totalPages >= 1) {
			if ($page <= $totalPages) {
				$this->Pagination->setCurrent($page);
				$this->Pagination->setTotal($totalContacts);
				$pagination = $this->Pagination->parse();
			} else {
				$error_msg = 'Esta página não existe. Por favor, volte para a lista de contatos.';
			}
        } elseif (empty($contacts)) {
			$error_msg = 'Não há contatos'.(empty($this->requestFields) ? ' cadastrados' : ' nesta pesquisa').'.';
		}

		$default_get_fields = array(
			'name' => ''
		);

        $this->set('get_fields', (!empty($this->requestFields) ? $this->requestFields : $default_get_fields));
        $this->set('page', $page);
        $this->set('totalPages', $totalPages);
        $this->set('totalContacts', $totalContacts);
        $this->set('error_msg', $error_msg);
        $this->set('pagination', $pagination);
        $this->set('titlePage', 'Lista de contatos');
        $this->set('contacts', $contacts);
        $this->set('sessionReturn', $this->SessionHandler->flash());
    }

    public function contact()
    {
        $id = isset($this->request['params']['id']) ? (int) $this->request['params']['id'] : 0;

        if ($id == 0) {
            $contact = $this->Contact->emptyContact();
            $breadcrumb = 'Novo contato';
        } else {
            $contact = $this->Contact->contact(array('id' => $id));
            if (empty($contact)) {
                $this->View->view = '404';
                $msg = 'Contato não encontrado.';
                $link = array('controller' => 'contacts');
                $this->set('msg', $msg);
                $this->set('link', $link);
                return;
            }
            $contact->picture_base64 = $contact->remove_picture = null;
            $breadcrumb = 'Alterar '.$contact->name;
        }
        if ($this->request['request'] == 'post' && !empty($this->requestFields)) {
            $classAlert = array('class' => self::classAlert['danger']);
            $urlRedirect = $_SERVER['REQUEST_URI'];
            $error = true;

            $validation_contact = $return = $this->validate();

            if (empty($validation_contact)) {
                $save_contact = $this->Contact->saveContact($this->sanitized_contact);
                if ($save_contact !== false) {
                    if ($id > 0 && ($this->contact['has_upload_image'] || $this->contact['remove_picture'] == 'delete')) {
                        if (!empty($contact->picture)) {
                            unlink(WWW_ROOT.$contact->picture);
                        }
                    }

                    $urlRedirect = array(
                        'controller' => 'contacts'
                    );

                    $return = $save_contact == 0 ? 'Nenhum dado do contato foi alterado.' : 'Contato salvo com sucesso.';
                    $classAlert = array('class' => self::classAlert[($save_contact == 0 ? 'info' : 'success')]);
                    $error = false;
                } else {
                    $return = 'Ocorreu um erro ao salvar o contato. Tente novamente.';
                }
            }

            if ($error === true) {
                $this->SessionHandler->write('Return.Contact', $this->contact);
            }
            $this->SessionHandler->setFlash($return, 'alert', $classAlert);
            $this->redirect(null, $urlRedirect);
        }

        if ($this->SessionHandler->check('Return.Contact')) {
            $tmpPicture = $contact->picture;
            $contact = (object) $this->SessionHandler->read('Return.Contact');
            $contact->picture = $tmpPicture;
            $this->SessionHandler->delete('Return.Contact');
        }

        $this->set('breadcrumb', $breadcrumb);
        $this->set('titlePage', $breadcrumb);
        $this->set('class_btn_remove_picture', (empty($contact->picture) && empty($contact->picture_base64) ? 'd-none' : null));
        $this->set('class_crop_msg_container', (empty($contact->picture) && empty($contact->picture_base64) ? null : 'd-none'));
        $this->set('contact', $contact);
        $this->set('sessionReturn', $this->SessionHandler->flash());
    }

    public function delete()
    {
        $id = isset($this->request['params']['id']) ? (int) $this->request['params']['id'] : 0;
        $this->View->layout = 'ajax';
        if ($this->request['request'] == 'post' || $this->request['request'] == 'ajax') {
            $arrReturn = array(
                'return' => 'Você tentou remover um contato inexistente.',
                'classAlert' => array('class' => self::classAlert['success']),
                'success' => false
            );

            if ($id > 0) {
                $contact = $this->Contact->contact(array('id' => $id));
                if (!empty($contact)) {
                    if (!empty($contact->picture)) {
                        unlink(WWW_ROOT.$contact->picture);
                    }
                    $delete = $this->Contact->deleteContact($id);
                    if ($delete === true || $delete > 0) {
                        $arrReturn = array(
                            'return' => 'Contato removido com sucesso.',
                            'classAlert' => array('class' => self::classAlert['success']),
                            'success' => true
                        );
                    }
                }

            }

            $this->set('sessionReturn', $arrReturn['return']);
            $this->set('success', ($arrReturn['success'] === true ? 'true' : 'false'));
            if ($arrReturn['success'] === true) {
                $this->SessionHandler->setFlash($arrReturn['return'], 'alert', $arrReturn['classAlert']);
            }
        }
    }
}