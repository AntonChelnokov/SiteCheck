<?php

class SiteCheck {

	/**
	*  URL array
	*
	* @var array
	*/
	public $url = [];

	/**
	*  Default User Agent to Curl connection
	*
	* @var string
	*/
	public $default_user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

	/**
	*  Default language
	*
	* @var string
	*/
	public $default_lang = 'ru';

	/**
	*  Default encoding
	*
	* @var string
	*/
	public $default_encode = 'UTF-8';

	/**
	*  DOM tree
	*
	* @var object
	*/
	public $page_body = '';

	/**
	*  Script errors
	*
	* @var array
	*/
	public $errors = [];

	/**
	*  Translate array
	*
	* @var array
	*/
	public $error_translate = [
		'ru' => [
				0 => 'Некорректный URL!',
				1 => 'Нет поддержки библиотеки CURL!',
				2 => 'Версия PHP не подходит :(',
				3 => 'Ошибка CURL запроса'
		]
	];


	/**
	*  Curl result
	*
	* @var array
	*/
	public $scan_result = [];



	public function __construct($domain) {
		$this->init($domain);
	}

	/**
	*  Init method check required parameters
	*
	*  @param string $domain Init url string
	*/
	private function init($domain){
		if (version_compare(PHP_VERSION, '5.5.0', '<')) {
			$this->setError(2);
		}

		if (!filter_var($domain, FILTER_VALIDATE_URL))
		{
			$this->setError(0);
		}

		if (!function_exists('curl_init')){
			$this->setError(1);
		}

		$this->url = parse_url($domain);
	}

	/**
	*  Get the URL array (full or partial)
	*
	*  @param array $component
	*  @return array
	*/
	public function getParseUrl(array $component=[]){
		if (count($component) > 0)
		{
			$url_array = [];
			$url_keys = array_keys($this->url);
			foreach ($component as $c){
				if (in_array($c,$url_keys))
				{
					$url_array[$c] = $this->url[$c];
				}
			}
			return $url_array;
		}else{
			return $this->url;
		}
	}

	/**
	*  Get full URL
	*  @return string
	*/
	public function getUrl(){
		$url = $this->url['scheme']."://".$this->url['host'];
		if (isset($this->url['path']))
		{
			$url .= $this->url['path'];
		}
		if (isset($this->url['query']))
		{
			$url .= "?".$this->url['query'];
		}
		return $url;
	}

	/**
	*  Scan site.
	*  Get site info - dns,html output,site headers (code,redirect etc.)
	*/
	public function scan(){
		$this->scan_result['dns'] = dns_get_record($this->url['host']);
		$curl = curl_init($this->getUrl());

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($curl, CURLOPT_USERAGENT , $this->default_user_agent);
		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($curl);

		if (!curl_errno($curl)) {
			$this->scan_result['curl_info'] = curl_getinfo($curl);
			//$this->page_body = $response;
			$this->page_body = new DOMDocument();
			@$this->page_body->loadHTML($response);
		}else{
			echo curl_errno($curl);
			$this->setError(3);
		}
		curl_close($curl);
	}

	//===============================================\\
	//			    				USER METHODS								 \\
	//===============================================\\

	/**
	*  Get full html tree
	*  @return object
	*/
	public function getDomTree(){
		return simplexml_import_dom($this->page_body);
	}

	/**
	*  Get favicon link
	*  @param string $target type link (self - html tree, yandex - yandex.ru link, google - google.com link)
	*  @return string favicon link
	*/
	public function getFaviconLink($target='self'){
		switch ($target) {
			case 'self':
			default:
				$url = $this->url['scheme']."://".$this->url['host'];
				$u = (string) $this->getDomTree()->xpath('//link[@rel="shortcut icon"]')[0]['href'];
				return $url.$u;
			break;

			case 'yandex':
				return 'http://favicon.yandex.net/favicon/'.$this->url['host'];
			break;

			case 'google':
				return 'http://www.google.com/s2/favicons?domain='.$this->url['host'];
			break;
		}
	}

	/**
	*  How many tags $tag in html tree
	*  @param string $tag Tag name for example h1
	*  @return int tag count
	*/
	public function howManyTags($tag) {
		$tags = $this->page_body->getElementsByTagName($tag);
		return count($tags);
	}

	/**
	*  Has html tree tag meta[name='description']
	*  @return bool
	*/
	public function hasDescription(){
		if ($this->getDomTree()->xpath('//meta[@name="description"]')){
			return true;
		}else{
			return false;
		}
	}

	/**
	*  Get meta[name='description'] content value
	*  @return string
	*/
	public function getDescriptionText(){
		return (string) $this->getDomTree()->xpath('//meta[@name="description"]')[0]['content'];
	}

	/**
	*  meta[name='description'] content length
	*  @return int
	*/
	public function getDescriptionLength(){
		return mb_strlen($this->getDescriptionText(), $this->default_encode);
	}

	/**
	*  Has html tree tag meta[name='keywords']
	*  @return bool
	*/
	public function hasKeywords(){
		if ($this->getDomTree()->xpath('//meta[@name="keywords"]')){
			return true;
		}else{
			return false;
		}
	}

	/**
	*  Get meta[name='keywords'] content value
	*  @return string
	*/
	public function getKeywordsText(){
		return (string) $this->getDomTree()->xpath('//meta[@name="keywords"]')[0]['content'];
	}

	/**
	*  meta[name='keywords'] content length
	*  @return int
	*/
	public function getKeywordsLength(){
		return mb_strlen($this->getKeywordsText(), $this->default_encode);
	}

	/**
	*  To split a meta[name='keywords'] into words
	*  @param string $delimiter word separator
	*  @return array
	*/
	public function explodeKeywords($delimiter=','){
		$kw = $this->getKeywordsText();
		$ct = mb_convert_encoding($kw, $this->default_encode , mb_detect_encoding($kw));
		$ex = explode($delimiter, $ct);
		$ex = array_map("trim", $ex);
		return $ex;
	}

	/**
	*  Get all ID attributes on the page
	*  @return array
	*/
	public function getAllId(){
		$xid = $this->getDomTree()->xpath("//*[@id !='']");
		$ids = [];
		foreach($xid as $k=>$item){
			$ids[] = (string) $item[id];
		}
		return $ids;
	}

	/**
	*  Get all duplicate ID attributes on the page
	*  @return array
	*/
	public function getDublicateId(){
		$all = $this->getAllId();
		$diff = [];
		foreach(array_count_values($all) as $k=>$idc){
			if ($idc > 1){
				$diff[$k] = $idc;
			}
		}
		return $diff;
	}

	/**
	*  Get page title
	*  @return string
	*/
	public function getPageTitle() {
		return $this->page_body->getElementsByTagName('title')->item(0)->nodeValue;
	}

	/**
	*  Get page title length
	*  @return int
	*/
	public function getPageTitleLength(){
		return mb_strlen($this->getPageTitle(),$this->default_encode);
	}

	/**
	*  Get all links with attributes
	*  @return array
	*/
	public function getAllLinks(){
		$links_array = [];
		foreach( $this->page_body->getElementsByTagName('a') as $k => $item ){
			$links_array[] = $this->getAllAttrs($item);
		}
		return $links_array;
	}

	/**
	*  Get all tag attributes
	*  @param object $item
	*  @return array
	*/
	public function getAllAttrs($item){
		if ($item->hasAttributes())
		{
			$arr = [];
			foreach ($item->attributes as $attr) {
				if ($attr->nodeName == 'class')
				{
					foreach (explode(" ",$attr->nodeValue) as $class)
					{
						if (trim($class) != ""){
							$arr[$attr->nodeName][] = $class;
						}
					}
					continue;
				}
		    $arr[$attr->nodeName] = $attr->nodeValue;
		  }
			return $arr;
		}else{
			return 0;
		}
	}

	//===============================================\\
	//										END												 \\
	//===============================================\\

	/**
	*  Get dns
	*  @param array $dns for example MX
	*  @return array
	*/
	public function getDNS(array $dns=[]){
		if (count($dns) > 0){
			$dns_array = [];
			foreach ($this->scan_result['dns'] as $k=>$ns){
				if (in_array($ns['type'], $dns)){
					$dns_array[$k] = $ns;
				}
			}
			return $dns_array;
		}else{
			return $this->scan_result['dns'];
		}
	}

	/**
	*  Get errors
	*  @return array
	*/
	public function getErrors(){
		return $this->errors;
	}

	/**
	*  Get errors with description
	*  @return array
	*/
	public function getErrorsWithDesc(){
		$err = [];
		foreach($this->errors as $k=>$val) {
			$err[$k]['code'] = $val;
			$err[$k]['desc'] = $this->error_translate[$this->default_lang][$val];
		}
		return $err;
	}

	/**
	*  Add error
	*/
	public function setError(int $error_code){
		$this->errors[] = $error_code;
	}



}
