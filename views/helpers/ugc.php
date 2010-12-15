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
	 * Stores defaults for UGC configuration.
	 */
	protected $defaults = array(
		'servers' => false,
		'protocol' => 'http',
		'force' => false
	);
	
	protected $settings = false;
	
	public $helpers = array('Html');
	
	protected $servers = array();
	
	public function __construct($options=null) {
		
		parent::__construct($options);
		
		$this->settings = array_merge($this->defaults, (array) Configure::read('UGC'));
		
		if(false !== $servers = $this->settings['servers']) {
			$this->servers = (array) $servers;
		}
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
	public function css($path, $rel = 'stylesheet', $attributes=array(), $inline=false, $protocol=false) {
		$out = '';
		
		# Only concerned with assets that don't have the full URL.
		# Might add a check to see if the host matches the environment.
		if( $this->isUgcEnabled() === false || $this->hasProtocol($path) ) {
			return $this->Html->css($path, $rel, $attributes, $inline);
		}
		
		$url = $this->url($path, 'css', $protocol);
		
		if($rel == 'import') {
			$out = sprintf($this->Html->tags['style'], $this->_parseAttributes($attributes, null, '', ' '), '@import url(' . $url . ');');
		} else {
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
		
		if(!$protocol) {
			$protocol = $this->settings['protocol'];
		}
		
		$protocol = ($protocol == '//') ? '' : $protocol . ':';
		
		return sprintf('%s//%s/%s', $protocol, $this->getServer($type), $path);
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
		return !empty($this->servers);
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