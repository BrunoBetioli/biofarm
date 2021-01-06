<?php

namespace libs;

class SessionHandler {

/**
 * Get / Set the userAgent
 *
 * @param string $userAgent Set the userAgent
 * @return void
 */
	public function userAgent($userAgent = null) {
		return Session::userAgent($userAgent);
	}

/**
 * Used to write a value to a session key.
 *
 * In your controller: $this->Session->write('Controller.sessKey', 'session value');
 *
 * @param string $name The name of the key your are setting in the session.
 * 							This should be in a Controller.key format for better organizing
 * @param string $value The value you want to store in a session.
 * @return boolean Success
 */
	public function write($name, $value = null) {
		return Session::write($name, $value);
	}

/**
 * Used to read a session values set in a controller for a key or return values for all keys.
 *
 * In your view: `$this->Session->read('Controller.sessKey');`
 * Calling the method without a param will return all session vars
 *
 * @param string $name the name of the session key you want to read
 * @return mixed values from the session vars
 */
	public function read($name = null) {
		return Session::read($name);
	}

/**
 * Wrapper for SessionComponent::del();
 *
 * In your controller: $this->Session->delete('Controller.sessKey');
 *
 * @param string $name the name of the session key you want to delete
 * @return boolean true is session variable is set and can be deleted, false is variable was not set.
 */
	public function delete($name) {
		return Session::delete($name);
	}

/**
 * Used to check is a session key has been set
 *
 * In your view: `$this->Session->check('Controller.sessKey');`
 *
 * @param string $name
 * @return boolean
 */
	public function check($name) {
		return Session::check($name);
	}

/**
 * Returns last error encountered in a session
 *
 * In your view: `$this->Session->error();`
 *
 * @return string last error
 */
	public function error() {
		return Session::error();
	}

/**
 * Used to set a session variable that can be used to output messages in the view.
 *
 * In your controller: $this->Session->setFlash('This has been saved');
 *
 * Additional params below can be passed to customize the output, or the Message.[key].
 * You can also set additional parameters when rendering flash messages. See SessionHelper::flash()
 * for more information on how to do that.
 *
 * @param string $message Message to be flashed
 * @param string $element Element to wrap flash message in.
 * @param array $params Parameters to be sent to layout as view variables
 * @param string $key Message key, default is 'flash'
 * @return void
 */
	public function setFlash($message, $element = 'default', $params = array(), $key = 'flash') {
		Session::write('Message.' . $key, compact('message', 'element', 'params'));
	}

/**
 * Used to render the message set in Controller::Session::setFlash()
 *
 * In your view: $this->Session->flash('somekey');
 * Will default to flash if no param is passed
 *
 * You can pass additional information into the flash message generation. This allows you
 * to consolidate all the parameters for a given type of flash message into the view.
 *
 * {{{
 * echo $this->Session->flash('flash', array('params' => array('class' => 'new-flash')));
 * }}}
 *
 * The above would generate a flash message with a custom class name. Using $attrs['params'] you
 * can pass additional data into the element rendering that will be made available as local variables
 * when the element is rendered:
 *
 * {{{
 * echo $this->Session->flash('flash', array('params' => array('name' => $user['User']['name'])));
 * }}}
 *
 * This would pass the current user's name into the flash message, so you could create personalized
 * messages without the controller needing access to that data.
 *
 * Lastly you can choose the element that is rendered when creating the flash message. Using
 * custom elements allows you to fully customize how flash messages are generated.
 *
 * {{{
 * echo $this->Session->flash('flash', array('element' => 'my_custom_element'));
 * }}}
 *
 * If you want to use an element from a plugin for rendering your flash message you can do that using the
 * plugin param:
 *
 * {{{
 * echo $this->Session->flash('flash', array(
 *		'element' => 'my_custom_element',
 *		'params' => array('plugin' => 'my_plugin')
 * ));
 * }}}
 *
 * @param string $key The [Message.]key you are rendering in the view.
 * @param array $attrs Additional attributes to use for the creation of this flash message.
 *    Supports the 'params', and 'element' keys that are used in the helper.
 * @return string
 */
	public function flash($key = 'flash', $attrs = array()) {
		$out = false;

		if (Session::check('Message.' . $key)) {
			$flash = Session::read('Message.' . $key);
			$message = $flash['message'];
			unset($flash['message']);

			if (!empty($attrs)) {
				$flash = array_merge($flash, $attrs);
			}

			if ($flash['element'] === 'alert') {
				$class = 'message';
				if (!empty($flash['params']['class'])) {
					$class = $flash['params']['class'];
				}
                if (is_array($message)) {
                    $out = null;
                    foreach($message as $msg) {
                        $out .= "<div class='" . $class . "'>" . $msg . " <a href='#' class='close' data-dismiss='alert'>×</a></div>";
                    }
                } else {
                    $out = "<div id='". $key . "Message' class='" . $class . "'>" . $message . " <a href='#' class='close' data-dismiss='alert'>×</a></div>";
                }
			} elseif (!$flash['element']) {
				$out = $message;
			}
			Session::delete('Message.' . $key);
		}
		return $out;
	}

/**
 * Used to renew a session id
 *
 * In your controller: $this->Session->renew();
 *
 * @return void
 */
	public function renew() {
		return Session::renew();
	}

/**
 * Used to destroy sessions
 *
 * In your controller: $this->Session->destroy();
 *
 * @return void
 */
	public function destroy() {
		return Session::destroy();
	}

/**
 * Get/Set the session id.
 *
 * When fetching the session id, the session will be started
 * if it has not already been started. When setting the session id,
 * the session will not be started.
 *
 * @param string $id Id to use (optional)
 * @return string The current session id.
 */
	public function id($id = null) {
		if (empty($id)) {
			Session::start();
		}
		return Session::id($id);
	}

/**
 * Used to check is a session is valid in a view
 *
 * @return boolean
 */
	public function valid() {
		return Session::valid();
	}

/**
 * Returns a bool, whether or not the session has been started.
 *
 * @return boolean
 */
	public function started() {
		return Session::started();
	}

}
