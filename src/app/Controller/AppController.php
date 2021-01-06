<?php

namespace app\Controller;

use libs\Controller;

class AppController extends Controller {

	const flash = array(
		'element' => 'alert',
		'key' => 'flash',
		'params' => array('class' => 'alert alert-info alert-dismissible fade in show')
	);
	const classAlert = array(
		'success' => 'alert alert-success alert-dismissible fade in show',
		'warning' => 'alert alert-warning alert-dismissible fade in show',
		'info' => 'alert alert-info alert-dismissible fade in show',
		'danger' => 'alert alert-danger alert-dismissible fade in show'
	);
    public $model = array('User');
    public $components = array(
        'AuthComponent' => array(
            'authError' => 'Você não tem permissão para acessar esta página',
            'authenticate' => array(
                'Form' => array(
                    'fields' => array(
                        'username' => 'email',
                    ),
                    'conditions' => array('status' => 'active'),
					'messages' => array(
						'error_user' => 'Usuário não encontrado ou não autorizado.',
						'error_password' => 'Senha inválida.',
						'success' => 'Sessão iniciada com sucesso. Bem vindo, {{name}}!'
					),
					'success' => array(
						'return' => true,
						'replace' => true,
						'field' => 'name'
					),
                    'passwordHasher' => 'blowfish'
                )
            ),
            'authorize' => 'Controller',
            'loginAction' => array(
                'controller' => 'users',
                'action' => 'login'
            ),
            'logoutAction' => array(
                'controller' => 'users',
                'action' => 'logout'
            ),
            'loginRedirect' => array(
                'controller' => 'dashboard',
                'action' => 'index'
            ),
            'flash' => self::flash,
			'classAlert' => self::classAlert,
        ),
        'Pagination helpers\Pagination\Pagination',
        'SessionHandler'
    );
    public $settings = array(
        'items_per_page' => 5
    );

    public function isAuthorized($user = null) {
		return (bool) $this->AuthComponent->loggedIn();
    }

    public function beforeFilter ()
    {
        $this->Pagination->setRpp($this->settings['items_per_page']);

		$this->Pagination->setClasses(array(
			'ul' => array('pagination'),
			'li' => array('page-item'),
			'a' => array('page-link')
		));
        $this->Pagination->setKey(null);
        $this->Pagination->setPrevious('&laquo;<span class="d-none d-sm-inline"> Anterior</span>');
        $this->Pagination->setPreviousLabel('Anterior');
        $this->Pagination->setNext('<span class="d-none d-sm-inline">Próxima </span>&raquo;');
        $this->Pagination->setNextLabel('Próxima');

        $this->logged = $this->User->user(array('id' => $this->SessionHandler->read('Auth.User.id')));

        if ($this->logged) {
            $this->logged->picture = empty($this->logged->picture) ? '/pictures/users/default.jpg' : $this->logged->picture;
        }

        $this->set('logged', $this->logged);
        $this->set('site_title', 'Biofarm - Agenda de Contatos');
        return parent::beforeFilter();
    }

    public function beforeRender ()
    {
        return parent::beforeRender();
    }
}