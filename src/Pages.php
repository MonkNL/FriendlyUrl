<?php
namespace FriendlyURL;

class PageManager {

	private array $pages 		= [];
	private static ?self $instance = null;
	private bool $ran = false;
	private $request;
	private $currentPage 		= null;
	private $modules 			= [];
	private $capabilityCallback = null;
	

	/**
     * Private constructor to enforce singleton pattern.
     */
    private function __construct() {
		$this->modules_autoload();
		$this->request  	= substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),1);
		$this->currentPage	= $this->getPageByRegex($this->request);
	}
	public 			function __wakeup(){}
	
	/**
     * Returns the singleton instance.
     *
     * @return self The singleton instance.
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
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
		if(!$this->ran){
			$this->runPages();
		}
	}
	private function runPages(){
		$this->ran =  true;
		try{
		
			if(!($page = $this->getPageByRegex($this->request))){
				throw new \Exception('404');

			}
			if($page->getArguments()){
				$arguments = $page->getArguments();
			}
			if(is_callable($page->getArgumentsCallback())){
				$arguments = call_user_func($page->getArgumentsCallback());
			}
			$this->runCallback($page->getCallback(),$arguments??[]);
		}catch(\Exception $e){
				$this->return_error($e);
		}
	}
	private function return_error($e){
		echo json_encode([
				'success' 				=> false,
				'error'   				=> $e->getMessage(),
				'code'					=> $e->getCode(),
				'file'					=> basename($e->getFile()),
				'line'					=> $e->getLine(),
				'trace'					=> $e->getTrace(),
				],JSON_PRETTY_PRINT);
	}
	private function runCallback(callable|array $callback,$arguments = []){
		$data = call_user_func($callback,$arguments);
	}


	/*private function getPageByRegex(?string $needle = null): bool | object{
		if (empty($this->pages)) {
			return false;
		}
		$needle = $needle??$this->request;
		foreach($this->pages as  $page){ 
			$regex = $page->getRegex();
			if(preg_match($regex,$needle,$matches)){
				$page->setRegexVariables($matches);
				return  $page;
			}
		}
		return false;
	}*/
	private function getPageByRegex(?string $needle = null): bool | object {
		if (empty($this->pages)) {
			return false;
		}
		$needle = $needle ?? $this->request;
		$matchedPage = array_filter($this->pages, function ($page) use ($needle) {
			return preg_match($page->getRegex(), $needle, $matches);
		});
	
		if (!empty($matchedPage)) {
			$page = reset($matchedPage);
			$page->setRegexVariables($matches);
			return $page;
		}
		return false;
	}
	private function getPageBySlug($needle): bool | object {
		foreach ($this->pages as $page) {
			if ($page->getSlug() === $needle) {
				return $page;
			}
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
				 		$callback 			= null,
						$argumentsCallback 	= null,
						$arguments 			= null,
		array|string 	$capability 		= '',
		?string 		$menuTitle 			= null,
		int|float 		$priority 			= null,
		bool 			$inMenu 			= false,
		?string 		$parentSlug 		= null
	) {
		return $this->registerPage(new Page(
			title: 				$title,
			menuTitle: 			$menuTitle,
			capability: 		$capability,
			slug: 				$slug,
			callback: 			$callback,
			argumentsCallback: 	$argumentsCallback,
			arguments:			$arguments,
			priority: 			$priority,
			inMenu: 			$inMenu,
			parentSlug: 		$parentSlug
		));
	}
	private function registerPage(Page $page){
		$this->pages[] = $page;
		return $page;
	}
	public static function getPages(?string $parentSlug = null) {
		$instance = self::getInstance();
		$pages = $instance->pages;
	
		if ($parentSlug) {
			$pages = array_filter($pages, fn($page) => $page->parentSlug === $parentSlug);
		}
	
		usort($pages, function ($a, $b) {
			return $a->priority <=> $b->priority;
		});
	
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
	private function breadcrumbs(?string $slug = null,int $maxdept = 3): bool | array {
		if (is_null($slug)) {
			return false;
		}
	
		$breadCrumbsArray = [];
		$dept 	= 0;
		while ($parent = $this->getParent($slug)) {
			if($dept++ > $maxdept){ 	break;	}
			array_unshift($breadCrumbsArray, $parent);
			$slug = $parent->parentSlug;
		}
	
		return $breadCrumbsArray;
	}
	/*private function breadcrumbs(?string $slug = null): bool | array{
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
	}*/
	private function currentPage(): bool | object{
		$this->currentPage = $this->getPageByRegex($this->request);
		return $this->currentPage;
	}
	static function getBreadCrumbs(?string $slug = null){
		return self::getInstance()->breadCrumbs($slug);
	}
	static function request(){
		return self::getInstance()->request;
	}
	static function current(): bool | object{
		return self::getInstance()->getPageByRegex();
	}
	static function run(){
		return self::getInstance()->runPages();
	}
	static function addPage(
		string 			$title,
		string 			$menuTitle,
		array|string	$capability,
		string 			$slug,
						$callback = null,
						$argumentsCallback = null,
						$arguments = null,
		int|float 		$priority = null,
		bool 			$inMenu = false,
		string 			$parentSlug = ''
	) {
		return self::getInstance()->registerPage(new Page(
			title: 				$title,
			menuTitle: 			$menuTitle,
			capability: 		$capability,
			slug: 				$slug,
			callback: 			$callback,
			argumentsCallback: 	$argumentsCallback,
			arguments:			$arguments,
			priority: 			$priority,
			inMenu: 			$inMenu,
			parentSlug: 		$parentSlug
		));
	}
	static function addSubPage(
		string 			$parentSlug,
		string 			$title,
		string 			$menuTitle,
		array|string 	$capability,
		string 			$slug,
						$callback 			= null,
						$argumentsCallback 	= null,
						$arguments 			= null,
		int|float 		$priority 			= null,
		bool 			$inMenu 			= false
	) {

		return self::getInstance()->registerPage(new Page(
			title: 				$title,
			menuTitle: 			$menuTitle,
			capability: 		$capability,
			slug: 				$slug,
			callback: 			$callback,
			argumentsCallback: 	$argumentsCallback,
			arguments:			$arguments,
			priority: 			$priority,
			inMenu: 			$inMenu,
			parentSlug: 		$parentSlug
		));
	}

	static function getSubPages(string $parentSlug){
		return self::getPages($parentSlug);
	}
	static function addMenuPage(
		string 			$title, 
		string 			$menuTitle, 
		array|string 	$capability, 
		string 			$slug, 
						$callback 			= null,
						$argumentsCallback 	= null,
						$arguments 			= null,
		?int 			$priority = null 
	){
		return self::getInstance()->registerPage(new Page(
			title: 				$title,
			menuTitle: 			$menuTitle,
			capability: 		$capability,
			slug: 				$slug,
			callback: 			$callback,
			argumentsCallback: 	$argumentsCallback,
			arguments:			$arguments,
			priority: 			$priority,
			inMenu: 			true,
			parentSlug: 		$parentSlug
		));
	}
	static function addSubmenuPage(
		string 			$parentSlug, 
		string 			$title, 
		string 			$menuTitle, 
		array|string 	$capability, 
		string 			$slug, 
						$callback 			= null,
						$argumentsCallback 	= null,
						$arguments 			= null,
		?int 			$priority= null
	){
		return self::getInstance()->registerPage(new Page(
			title: 				$title,
			menuTitle: 			$menuTitle,
			capability: 		$capability,
			slug: 				$slug,
			callback: 			$callback,
			argumentsCallback: 	$argumentsCallback,
			arguments:			$arguments,
			priority: 			$priority,
			inMenu: 			true,
			parentSlug: 		$parentSlug
		));
	}
	static function checkCapability($capability){
		if(callable(self::getInstance()->capabilityCallback)){
			call_user_func_array((array)self::getInstance()->capabilityCallback,$capability);
		}
		return true;
	}
	static function getMenu($parentSlug = '') {    
		$pages 		= self::getPages('');
		$matches 	= array_filter($pages, fn($page) => $page->parentSlug === $parentSlug);
	
		if (empty($pages)) {
			return [];
		}
	
		$menu = [];
		foreach ($pages as $page) {
			if (!$page->inMenu) {
				continue;
			}
			if (is_array($page->capability) && !self::checkCapability($page->capability)) {
				continue;
			}
			$subMenu = [];
			if (!empty($page->slug)) { 
				$subMenu = self::get_menu($page->slug);
			}
			$page->childPages = $subMenu;
			$menu[] = $page;
		}
		return $menu;
	}
	
}



