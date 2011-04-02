<?php

/**
 * UGC Helper.
 * Makes UGC assets easier to deal with in your application.
 * Note: This helper does not deploy assets! It assumes that assets have been
 * deployed prior to the location configured.
 *
 * @copyright 2010, {@link http://goredmonster.com Paul Redmond}. All Rights Reserved.
 * @package ugc
 * @subpackage ugc.helpers
 * @author Paul Redmond <paulrredmond@gmail.com>
 */
class UgcHelper extends AppHelper {
	
	/**
	 * Helpers that this helper will use.
	 */
	public $helpers = array('Html');
	
	/**
	 * Stores defaults for UGC configuration.
	 */
	protected $defaults = array(
		'servers' => false,
		'protocol' => 'http',
		'force' => false
	);
	
	/**
	 * By default CDN is disabled unless server settings are configured.
	 * @access protected
	 */
	protected $disabled = false;
	
	/**
	 * Configuration settings from the Controllers::$helpers array.
	 * Merged with UgcHelper::$default during instantiation.
	 * 
	 * @access protected
	 */
	protected $settings = false;
	
	/**
	 * Array of servers configured.
	 * @access protected
	 */
	protected $servers = array();
	
	/**
	 * Port of the request.
	 * 
	 * @access protected
	 */
	protected $port = 80;
	
	/**
	 * Http protocol for the request. Set to https if needed.
	 */
	protected $httpProtocol = 'https';
	
	public function __construct($options=null) {
		
		parent::__construct($options);
		
		$this->settings = array_merge($this->defaults, (array) $options);
		$config = (array) Configure::read('UGC');
		if(false !== $servers = $this->settings['servers']) {
			$this->servers = (array) $servers;
		}
		$this->disabled = (bool) $config['disabled'];
		$this->port = getenv('SERVER_PORT');
		$this->httpProtocol = $this->port === 443 ? 'https' : 'http';
	}
	
	/**
	 * afterRender callback
	 * If configured, will force scripts
	 * stored in the view object to use configured UGC if
	 * a protocol is not set in the url.
	 */
	public function afterRender()
	{
		//$view = ClassRegistry::getObject('view');
		//$scripts = $view->__scripts;
	}

	
	/**
	 * Convenience method for serving css from the UGC pool.
	 */
	public function css($path, $rel = null, $attributes=array(), $inline=false, $protocol=false) {
		$out = '';

		# Only concerned with assets that don't have the full URL.
		# Might add a check to see if the host matches the environment.
		if( $this->isUgcEnabled() === false || $this->hasProtocol($path) ) {
			return $this->Html->css($path, $rel, $attributes, $inline);
		}
		
		if ($path[0] !== '/') {
			$path = CSS_URL . $path;
		}
		
		if (strpos($path, '?') === false && substr($path, -4) !== '.css') {
			$path .= '.css';
		}
		
		$url = $this->url($path, 'css', $protocol);
		
		if($rel == 'import') {
			$out = sprintf($this->Html->tags['style'], $this->_parseAttributes($attributes, null, '', ' '), '@import url(' . $url . ');');
		} else {
			$rel = $rel === null ? 'stylesheet' : $rel;
			$out = sprintf($this->Html->tags['css'], $rel, $url, $this->_parseAttributes($attributes, null, '', ' '));
		}
		
		$out = $this->output($out);

		if($inline) {
			return $out;
		} else {
			$view = ClassRegistry::getObject('view');
			$view->addScript($out);
		}
	}
	
	
	public function js() {
		
	}
	
	
	public function image() {
	
	}
	
	
	public function asset($type) {
	
	}
	
	/**
	 * Get a UGC url. Target specific asset types if desired.
	 */
	public function url($path, $type='default', $protocol=false) {
		if ($this->hasProtocol($path)) {
			return $path;
		}
		
		// Override $protocol parameter if this is https or it's set to false.
		if ($this->isHttps() || !$protocol) {
			$protocol = $this->httpProtocol;
		}

		$protocol = ($protocol == '//') ? '' : $protocol . ':';
		$path = ltrim($path, '/');
		$path = str_replace('//', '/', $path); // Clean up double slashes in path.
		return sprintf('%s//%s/%s', $protocol, $this->getServer($type), $path);
	}
	
	public function getHttpProtocol() {
		return $this->httpProtocol;
	}
	
	public function isHttps() {
		return ($this->httpProtocol === 'https');
	}
	
	private function hasProtocol($path) {
		$protocol = false;
		
		if (strpos($path, '://') !== false) {
			$protocol = true;
		}	
		return $protocol;
	}
	
	/**
	 * Determine if UGC is configured.
	 */
	public function isUgcEnabled() {
		return (!empty($this->servers) && !$this->disabled);
	}
	
	
	/**
	 * Get the server hostname.
	 * Right now it's very hacked.
	 * @todo Add server rotation if multiple servers are provided.
	 */
	private function getServer($type=false) {
		if(isset($this->servers[$type])) {
			return $this->servers[$type][0];
		}
		return $this->servers['default'][0];
	}
}