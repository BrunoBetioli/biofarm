<?php
use app\Controller\AppController;
use app\Request\UserRequest;
use libs\DataFilter;

class UsersController extends AppController
{
    use UserRequest;

    public $statusUser = array(
        'active' => 'Ativo',
        'inactive' => 'Inativo'
    );

    public function beforeFilter ()
    {
        parent::beforeFilter();
    }

    public function isAuthorized($user = null)
    {
        return parent::isAuthorized($user);
    }

    public function login()
    {
        if ($this->AuthComponent->loggedIn()) {
            $this->redirect(null, $this->AuthComponent->redirectUrl());
        }

        if ($this->request['request'] == 'post' && !empty($this->requestFields)) {
            if ($this->AuthComponent->login()) {
                $this->redirect(null, $this->AuthComponent->redirectUrl());
            }
        }

        $this->View->layout = 'login';
        $this->set('sessionReturn', $this->SessionHandler->flash());
    }

    public function logout()
    {
        $this->redirect(null, $this->AuthComponent->logout());
    }

    public function profile()
    {
        $this->View->view = 'user';
        $this->user(true);
    }

    public function index()
    {
        $page = isset($this->request['params']['page']) && $this->request['params']['page'] >= 1 ? (int) $this->request['params']['page'] : 1;
        $arrConditions = array();

        if (isset($this->request['params']['query']['name'])) {
            $name = DataFilter::cleanString($this->request['params']['query']['name']);
            if (!empty($name)) {
                $arrConditions['where'] = ' WHERE (u.name LIKE "%'.$name.'%" OR u.email LIKE "%'.$name.'%")';
            }
        }

        $countUsers = $this->User->countUsers($arrConditions);

        $totalUsers = $countUsers[0]->count;

        $arrConditions['offset'] = ($page * $this->settings['items_per_page']) - $this->settings['items_per_page'];
        $arrConditions['count'] = $this->settings['items_per_page'];

        $users = $this->User->users($arrConditions);

        $totalPages = ((int) ceil($totalUsers / $this->settings['items_per_page']));
        $pagination = null;
        $error_msg = null;

        if ($totalPages >= 1) {
			if ($page <= $totalPages) {
				$this->Pagination->setCurrent($page);
				$this->Pagination->setTotal($totalUsers);
				$pagination = $this->Pagination->parse();
			} else {
				$error_msg = 'Esta página não existe. Por favor, volte para a lista de usuários.';
			}
        } elseif (empty($users)) {
			$error_msg = 'Não há usuários'.(empty($this->requestFields) ? ' cadastrados' : ' nesta pesquisa').'.';
		}

		$default_get_fields = array(
			'name' => ''
		);

        $this->set('get_fields', (!empty($this->requestFields) ? $this->requestFields : $default_get_fields));
        $this->set('page', $page);
        $this->set('totalPages', $totalPages);
        $this->set('totalUsers', $totalUsers);
        $this->set('error_msg', $error_msg);
        $this->set('pagination', $pagination);
        $this->set('arrStatus', $this->statusUser);
        $this->set('titlePage', 'Lista de usuários');
        $this->set('users', $users);
        $this->set('sessionReturn', $this->SessionHandler->flash());
    }

    public function user($profile = false)
    {
        $id = $profile === true ? (int) $this->SessionHandler->read('Auth.User.id') : (isset($this->request['params']['id']) ? (int) $this->request['params']['id'] : 0);

        if ($id == 0) {
            $user = $this->User->emptyUser();
            $breadcrumb = 'Novo usuário';
        } else {
            $user = $this->User->user(array('id' => $id));
            if (empty($user)) {
                $this->View->view = '404';
                $msg = 'Usuário não encontrado.';
                $link = array('controller' => 'users');
                $this->set('msg', $msg);
                $this->set('link', $link);
                return;
            }
            $user->picture_base64 = $user->remove_picture = null;
            $breadcrumb = 'Alterar '.$user->name;
        }

        if ($this->request['request'] == 'post' && !empty($this->requestFields)) {
            $classAlert = array('class' => self::classAlert['danger']);
            $urlRedirect = $_SERVER['REQUEST_URI'];
            $error = true;

            $validation_user = $return = $this->validate();

            if (empty($validation_user)) {
                $save_user = $this->User->saveUser($this->sanitized_user);
                if ($save_user !== false) {
                    if ($id > 0 && ($this->user['has_upload_image'] || $this->user['remove_picture'] == 'delete')) {
                        if (!empty($user->picture)) {
                            unlink(WWW_ROOT.$user->picture);
                        }
                    }

                    $urlRedirect = array(
                        'controller' => 'users'
                    );

                    $profile_or_user = ($this->request['action'] == 'user') ? 'usuário' : 'perfil';
                    $return = $save_user == 0 ? 'Nenhum dado do '.$profile_or_user.' foi alterado.' : ucfirst($profile_or_user).' salvo com sucesso.';
                    $classAlert = array('class' => self::classAlert[($save_user == 0 ? 'info' : 'success')]);
                    $error = false;
                } else {
                    $return = 'Ocorreu um erro ao salvar o usuário. Tente novamente.';
                }
            }

            if ($error === true) {
                $this->SessionHandler->write('Return.User', $this->user);
            }
            $this->SessionHandler->setFlash($return, 'alert', $classAlert);
            $this->redirect(null, $urlRedirect);
        }

        if ($this->SessionHandler->check('Return.User')) {
            $tmpPicture = $user->picture;
            $user = (object) $this->SessionHandler->read('Return.User');
            $user->picture = $tmpPicture;
            $this->SessionHandler->delete('Return.User');
        }

        $this->set('arrStatus', $this->statusUser);
        $this->set('breadcrumb', $breadcrumb);
        $this->set('titlePage', $breadcrumb);
        $this->set('class_btn_remove_picture', (empty($user->picture) && empty($user->picture_base64) ? 'd-none' : null));
        $this->set('class_crop_msg_container', (empty($user->picture) && empty($user->picture_base64) ? null : 'd-none'));
        $this->set('is_profile', $this->request['action'] === 'profile');
        $this->set('sessionReturn', $this->SessionHandler->flash());
        $this->set('user', $user);
    }

    public function delete()
    {
        $id = isset($this->request['params']['id']) ? (int) $this->request['params']['id'] : 0;
        $this->View->layout = 'ajax';
        if ($this->request['request'] == 'post' || $this->request['request'] == 'ajax') {
            $arrReturn = array(
                'return' => 'Você tentou remover um usuário inexistente.',
                'classAlert' => array('class' => self::classAlert['danger']),
                'success' => false
            );

            if ($id > 0) {
                $user = $this->User->user(array('id' => $id));
                if (!empty($user)) {
                    if ($id == $this->logged->id) {                        
                        $arrReturn = array(
                            'return' => 'Você não pode remover seu próprio usuário.',
                            'classAlert' => array('class' => self::classAlert['danger']),
                            'success' => false
                        );
                    } else {
                        if (!empty($user->picture)) {
                            unlink(WWW_ROOT.$user->picture);
                        }
                        $delete = $this->User->deleteUser($id);
                        if ($delete === true || $delete > 0) {
                            if ($id == $this->logged->id) {
                                
                            }
                            $arrReturn = array(
                                'return' => 'Usuário removido com sucesso.',
                                'classAlert' => array('class' => self::classAlert['success']),
                                'success' => true
                            );
                        }
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