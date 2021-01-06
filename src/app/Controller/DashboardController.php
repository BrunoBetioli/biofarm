<?php
use app\Controller\AppController;

class DashboardController extends AppController
{
    public $model = array('Contact', 'User');
    public $statusUser = array(
        'active' => 'Ativo',
        'inactive' => 'Inativo'
    );

    public function __construct ()
    {
    }

    public function beforeFilter ()
    {
        parent::beforeFilter();
    }

    public function index()
    {

        $countUsers = $this->User->countUsers();
        $users = $this->User->users(array(
            'count' => 5,
            'orderBy' => 'u.created DESC'
        ));

        $countContacts = $this->Contact->countContacts();
        $contacts = $this->Contact->contacts(array(
            'count' => 5,
            'orderBy' => 'c.created DESC'
        ));

		if (empty($users)) {
			$error_msg_users = 'Não há usuários cadastrados.';
			$this->set('error_msg_users', $error_msg_users);
		}

		if (empty($contacts)) {
			$error_msg_contacts = 'Não há contatos cadastrados.';
			$this->set('error_msg_contacts', $error_msg_contacts);
		}

        $this->set('countUsers', $countUsers[0]->count);
        $this->set('countContacts', $countContacts[0]->count);
        $this->set('status', $this->statusUser);
        $this->set('users', $users);
        $this->set('contacts', $contacts);
        $this->set('sessionReturn', $this->SessionHandler->flash());
    }
}