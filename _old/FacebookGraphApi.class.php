<?php

class FacebookGraphApi {
	protected $params = array();

	// http://developers.facebook.com/docs/authentication/
	protected function __construct($access_token = NULL) {
		$this->params['access_token'] = $access_token;
	}
	
	static public function fromAccessToken($access_token) {
		return new static($access_token);
	}
	
	static public function fromSecret($client_id, $client_secret) {
		return new static(static::oauth_access_token($client_id, $client_secret));
	}
	
	static public function oauth_access_token($client_id, $client_secret, $cache = false) {
		$result = file_get_contents(sprintf(
			'https://graph.facebook.com/oauth/access_token?grant_type=%s&client_id=%s&client_secret=%s',
			urlencode('client_credentials'), urlencode($client_id), urlencode($client_secret)
		));
		parse_str($result, $info);

		return $info['access_token'];
	}

	/**
	 * User will be redirected to redirect_uri + a "code" parameter with the access_token.
	 * 
	 * @param  $client_id     Application ID
	 * @param  $redirect_uri  URL to be redirected after the user accepted or declined the authorization request.
	 * @param  $scope         A list of permissions separated by comma.
	 * @param  $display       Format of the page. One of: ('page', 'popup', 'wap', 'touch')
	 *
	 * @see http://developers.facebook.com/docs/authentication/permissions
	 */
	static public function oauth_authorize_url($client_id, $redirect_uri, $scope = NULL, $display = NULL) {
		$info = array(
			'client_id' => $client_id,
			'redirect_uri' => $redirect_uri,
		);
		if ($info['scope'] !== NULL) $info['scope'] = $scope;
		if ($info['display'] !== NULL) {
			/*
			page  - Display a full-page authorization screen (the default)
			popup - Display a compact dialog optimized for web popup windows
			wap   - Display a WAP / mobile-optimized version of the dialog
			touch - Display an iPhone/Android/smartphone-optimized version of the dialog
			*/
			$info['display'] = $display;
		}

		return sprintf('https://graph.facebook.com/oauth/authorize?%s', http_build_query($info));
	}
	
	static public function oauth_exchange_sessions() {
		throw(new Exception("To implement"));
	}

	protected function _clone_set($params) {
		$that = clone $this;
		foreach ($params as $k => $v) $that->params[$k] = $v;
		return $that;
	}
	
	public function limit_offset($limit = NULL, $offset = NULL) {
		return $this->_clone_set(array(
			'limit' => $limit,
			'offset' => $offset,
		));
	}
	
	public function until_since($until = NULL, $since = NULL) {
		return $this->_clone_set(array(
			'until' => $until,
			'since' => $since,
		));
	}
	
	public function type($type = NULL) {
		return $this->_clone_set(array('type' => $type));
	}
	
	public function fields($fields = NULL) {
		return $this->_clone_set(array('fields' => $fields));
	}
	
	public function query($q = NULL) {
		return $this->_clone_set(array('q' => $q));
	}
	
	protected function build_query($extra_params = array(), $add_question_mark = true) {
		$r = http_build_query($this->params + $extra_params);
		if ($add_question_mark) $r = ('?' . $r);
		return $r;
	}

	/**
	 * @param  $id               ID type
	 * @param  $connection_type  Type of the connection of NULL to obtain the object itself
	 */
	public function request($id, $connection_type = NULL) {
		$result = JSON::decode(file_get_contents($this->request_url($id, $connection_type)), true);
		return $result;
	}

	public function request_url($id, $connection_type = NULL) {
		$path = '';
		$path .= urlencode($id);
		if ($connection_type !== NULL) $path .= "/" . urlencode($connection_type);
		$path .= $this->build_query();
		return 'https://graph.facebook.com/' . $path;
	}
	
	public function request_search($q, $type) {
		return $this->query($q)->type($type)->request('search');
	}
}