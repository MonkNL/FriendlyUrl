<?php
namespace FriendlyURL;

class Pages {

	private array $pages 		= [];
	private static $instance;
	private static $autoloaded;
	private static $classChecks	= [];
	private $request;
	private $currentPage 		= null;
	private $modules 			= [];
	private $capabilityCallback = null;
	

	protected 		function __construct() {}
	public 			function __wakeup(){}
	
	public static 	function getInstance() {
		
        if (!isset(self::$instance)) {
            self::$instance 	= new static();
			return self::$instance;
        }
		if(!in_array('autoloaded',self::$classChecks)){
			self::$classChecks[] =  'autoloaded';
			self::$instance->modules_autoload();
			self::$instance->request  	= substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),1);
			self::$instance->currentPage	= self::$instance->getPageByRegex(self::$instance->request);
		}
        return self::$instance;
    }
	private 		function getRequest(): string{
		return $this->request;
	}

	private 		function modules_autoload(): void{ // auto load all route modules, dir and constructor need to have the same name
		foreach(glob('modules/**/') as $dir){
	
			$module = basename($dir);
			if(file_exists($dir.$module.'.php')){
				include_once($dir.$module.'.php');
				$this->modules[] = $module;
				
			}
		}
	}
	function __destruct(){
		if(!in_array('ran',self::$classChecks)){
			$this->runPages();
		}
	}
	private function runPages(){
		self::$classChecks[] =  'ran';
		try{
		
			if(!($page = $this->getPageByRegex($this->request))){
				throw new \Exception('404');

			}
			$this->runCallback($page->getCallback());
		}catch(\Exception $e){
				$this->return_error($e);
				print_r($this);
		}
	}
	private function return_error($e){
		$e->getMessage();                 // Exception message
		$e->getCode();                    // User-defined Exception code
		$e->getFile();                    // Source filename
		$e->getLine();                    // Source line
		$e->getTrace();                   // An array of the backtrace()
		$e->getTraceAsString(); 

		echo json_encode([
				'success' 				=> false,
				'error'   				=> $e->getMessage(),
				'code'					=> $e->getCode(),
				'file'					=> basename($e->getFile()),
				'line'					=> $e->getLine(),
				'trace'					=> $e->getTrace(),
				]);
	}
	private function runCallback(callable $callback){
		$data = call_user_func($callback,[]);
	}


	private function getPageByRegex($needle): bool | object{
		foreach($this->pages as  $page){ 
			$regex = $page->getRegex();
			if(preg_match($regex,$needle,$matches) != false){
					$page->setRegexVariables($matches);
					return  $page;
			}
		}
		return false;
	}

	private function getPageBySlug($needle): bool | object{
		//$slugs 	= array_column($this->pages,'slug');
		$slugs = array_map(function($page){return $page->getSlug();},$this->pages);
		$key 	= array_search($needle,$slugs);
		if(is_int($key) || is_string($key)){
			return $this->pages[$key];
		}
		return false;
	}
	private function checkCapability($capablility){
		if(empty($capablility)){
			return true;
		}
		if(!is_callable($this->capabilityCallback)){
			return true;
		}
		$answer = call_user_func($this->capabilityCallback,$capablility);
		return is_bool($answer)?$answer:true; 
	}
	public function addPage(
		string 			$title,
		string 			$slug,
				 		$callback 	= null,
		array|string 	$capability,
		?string 		$menuTitle 	= null,
		int|float 		$priority 	= null,
		bool 			$inMenu 	= false,
		?string 		$parentSlug = null
	) {
		echo $callback;
		$this->pages[] = new Page(
			title: 			$title,
			menuTitle: 		$menuTitle,
			capability: 	$capability,
			slug: 			$slug,
			callback: 		$callback,
			priority: 		$priority,
			inMenu: 		$inMenu,
			parentSlug: 	$parentSlug
		);
	}
	private function getPages(){
		usort($this->pages, function ($a, $b) {return $a->priority <=> $b->priority;});
		return $this->pages;
	}
	private function getMenu($parentSlug = '',$parent = []){
		$user = CurrentUser::Get();
		$matches = array_keys(array_column($this->pages,'parentSlug'),$parentSlug);
		if(empty($matches)){
			return [];
		}
		$pages = [];
		foreach($matches as $key){
			$page 			= $this->pages[$key];
			if($page->inMenu == false){
				continue;
			}
			if(is_array($page->capability) && $this->checkCapability($page->capability)){
				continue;
			}
			$childPages	= [];
			if(!empty($page->slug)){ 
				$childPages = $this->getMenu($page->slug,$page );
				usort($childPages, function ($a, $b) {return $a->priority <=> $b->priority;});
			}
			$page->childPages = $childPages;
			$pages[] = $page;
			

		}
		usort($pages, function ($a, $b) {return $a->priority <=> $b->priority;});
		return $pages;
	}
	private function getParent(?string $slug = null): bool | array{
		if(is_null($slug)){
			return false;
		}
		if(($parent = $this->getPageBySlug($slug)) == false){
			return false;
		}
		return $parent;
	}
	private function breadcrumbs(?string $slug = null): bool | array{
		$breadCrumbsArray = [];
		if(is_null($slug)){
			return false;
		}
		do{
			$parent = $this->getParent($slug); 
			$slug = $parent->parentSlug;
			array_unshift($breadCrumbsArray,$parent);
		}while($this->getParent($slug) != false);
		return $breadCrumbsArray;
	}
	private function currentPage(): bool | object{
		$this->currentPage = $this->getPageByRegex($this->request);
		return $this->currentPage;
	}
	static function getBreadCrumbs($slug){
		return call_user_func_array([self::getInstance(),'breadCrumbs'],func_get_args());
	}
	static function request(){
		return call_user_func_array([self::getInstance(),'getRequest'],[]);
	}
	static function current(): bool | object{
		return call_user_func_array([self::getInstance(),'currentPage'],[]);
	}
	static function run(){
		return call_user_func_array([self::getInstance(),'runPages'],[]);
	}
	static function add_page(
		string 			$title,
		string 			$menuTitle,
		array|string	$capability,
		string 			$slug,
						$callback = null,
		int|float 		$priority = null,
		bool 			$inMenu = false
	) {
		$arguments = [
			'title' 		=> $title,
			'menuTitle' 	=> $menuTitle,
			'capability' 	=> $capability,
			'slug' 			=> $slug,
			'callback' 		=> $callback,
			'priority' 		=> $priority,
			'inMenu' 		=> $inMenu,
		];
	
		return call_user_func_array([self::getInstance(), 'addPage'], $arguments);
	}
	static function add_sub_page(
		string 			$parentSlug,
		string 			$title,
		string 			$menuTitle,
		array|string 	$capability,
		string 			$slug,
						$callback = null,
		int|float 		$priority = null,
		bool 			$inMenu = false
	) {
		$arguments = [
			'title' 		=> $title,
			'menuTitle' 	=> $menuTitle,
			'capability' 	=> $capability,
			'slug' 			=> $slug,
			'callback' 		=> $callback,
			'priority' 		=> $priority,
			'inMenu' 		=> $inMenu,
			'parentSlug' 	=> $parentSlug,
		];
	
		return call_user_func_array([self::getInstance(), 'addPage'], $arguments);
	}
	
	static function get_pages(){	
		return call_user_func_array([self::getInstance(),'getPages'],func_get_args());
	}
	static function get_subpages($parentSlug){
		return call_user_func_array([self::getInstance(),'getsubPages'],func_get_args());
	}
	static function add_menu_page(
		string 			$title, 
		string 			$menuTitle, 
		array|string 	$capability, 
		string 			$slug, 
				 		$callback = null, 
		?int 			$priority = null 
	){
		$arguments = [
			'title' 		=> $title,
			'menuTitle' 	=> $menuTitle,
			'capability' 	=> $capability,
			'slug' 			=> $slug,
			'callback' 		=> $callback,
			'priority' 		=> $priority,
			'inMenu' 		=> true,
		];
	
		return call_user_func_array([self::getInstance(), 'addPage'], $arguments);
	}
	static function add_submenu_page(
		string 			$parentSlug, 
		string 			$title, 
		string 			$menuTitle, 
		array|string 	$capability, 
		string 			$slug, 
						$callback = null, 
		?int 			$priority= null
	){
		$arguments = [
			'parentSlug'	=> $parentSlug,
			'title' 		=> $title,
			'menuTitle' 	=> $menuTitle,
			'capability' 	=> $capability,
			'slug' 			=> $slug,
			'callback' 		=> $callback,
			'priority' 		=> $priority,
			'inMenu' 		=> true,
		];
	
		return call_user_func_array([self::getInstance(), 'addPage'], $arguments);
	}
	static function get_menu(){	
		return call_user_func_array([self::getInstance(),'getMenu'],func_get_args());
	}
	
}



