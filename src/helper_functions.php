<?php
function add_page(
	string 			$title,
	string 			$menuTitle,
	array|string	$capability,
	string 			$slug,
	callable 		$callback = null,
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
	return call_user_func_array([Pages::getInstance(), 'addPage'], $arguments);
	return call_user_func_array([FriendlyURL\Pages::getInstance(), 'addPage'], $arguments);
}
class Pages{

	private array $pages 		= [];
	private static $instance 	= null;
	private $request;
	private $currentPage 		= null;
	private $modules 			= [];
	private $capabilityCallback = null;
	private function __construct() {
		$this->modules_autoload();

		$this->request  	= substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),1);
		$this->currentPage	= $this->getPageByRegex($this->request);

	}
	public static function getInstance(): self{
        if (null === self::$instance) {
			echo __FUNCTION__;
            self::$instance = new self();
        }
        return self::$instance;
    }
		
	private function getRequest(): string{
		return $this->request;
	}

	private function modules_autoload(): void{ // auto load all route modules, dir and constructor need to have the same name
		foreach(glob('modules/**/') as $dir){
			if(file_exists($dir.basename($dir).'.php')){
				include_once($dir.basename($dir).'.php');
				$this->modules[] = basename($dir);
			}
		}
	}
	public function __destruct(){
		try{
		
			if(!($page = $this->getPageByRegex($this->request))){
				throw new \Exception('404');
				print_r(debug_traceback());
			}
			$this->runCallback($page->callback);
		}catch(\Exception $e){
				$this->return_error($e);
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
		$pageRegex = array_column($this->pages,'regex');
		foreach($pageRegex as  $k => $regex){ 
			if(preg_match($regex,$needle,$matches) != false){
					$this->pages[$k]->setRegexVariables($matches);
					return  $this->pages[$k];
			}
		}
		return false;
	}

	private function getPageBySlug($needle): bool | object{
		$slugs 	= array_column($this->pages,'slug');
		$key 		= array_search($needle,$slugs);
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
		callable 		$callback 	= null,
		array|string 	$capability,
		?string 		$menuTitle 	= null,
		int|float 		$priority 	= null,
		bool 			$inMenu 	= false,
		?string 		$parentSlug = null
	) {
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
	private function getsubPages($slug){
		//print_r(array_column($this->pages,'parentSlug'));
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
	private function currentPage(): object{
		return $this->currentPage;
	}
	static function getBreadCrumbs($slug){
		return call_user_func_array([self::getInstance(),'breadCrumbs'],func_get_args());
	}
	static function request(){
		return call_user_func_array([self::getInstance(),'getRequest'],[]);
	}
	static function current(){
		return call_user_func_array([self::getInstance(),'currentPage'],[]);
	}
	static function add_page(
		string 			$title,
		string 			$menuTitle,
		array|string	$capability,
		string 			$slug,
		callable 		$callback = null,
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
		callable 		$callback = null,
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
		?callable 		$callback = null, 
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
		?callable 		$callback = null, 
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
Class Page{
	public 	$title, 
		$menuTitle, 
		$capability,  
		$slug 			= '',
		$callback 		= null,
		$priority 		= 10,
		$inMenu 		= false,
		$subPages 		= [],
		$parentSlug 	= null,
		$regexVariables = [],
		$regex			='',
		$parent 		= null;	 
	
	public function __construct(
			string 			$title, 
			string 			$menuTitle, 
			array|string 	$capability, 
			string 			$slug, 
			?callable 		$callback = null, 
			?int 			$priority = 10,
			bool 			$inMenu = false,
			?string 		$parentSlug = null
		) {
		$this->setTitle($title); 
		$this->setmenuTitle($menuTitle); 
		$this->setcapability($capability); 
		$this->setSlug($slug);
		$this->setCallback($callback); 
		$this->setPriority($priority);
		$this->setInMenu($inMenu);
		$this->setParentSlug($parentSlug);
	}
	public function setTitle(string $title): void{
		$this->title = $title;
	}
	public function setMenuTitle(string $title): void{
		$this->menuTitle = $title;
	}
	public function setCapability(string|array $capability): void{
		$this->capability = $capability;
	}
	public function setSlug(string $slug): void{
		$this->slug = $slug;
		$slug 		= (substr($slug,-1)=='/'?substr($slug,0,-1):$slug);
		$regex		= '/^'.str_replace('/','\/',$slug).'\/?$/'; // ^ = start, $ = end, \/? = 0 or 1 /
		$this->setRegex($regex);
	}
	private function setRegex(string $regex) :void{
		$this->regex = $regex;
	}
	public function setCallback(callable $callback){
		$this->callback = $callback;
	}
	public function setPriority(?int $priority){
		$this->priority		= $priority;
	}
	public function setInMenu(bool $inMenu){
		$this->inMenu		= $inMenu;
	}
	public function setparentSlug(?string $slug):void{
		$this->parentSlug 	= $slug;
	}
	public function setRegexvariables($matches){
		$this->regexVariables = $matches;
	}
	public function regexSprintF(){
		return preg_replace('/(\(.*\))/U', '%s', $this->slug);
	}
	public function getSubPages(){
		return $this->childPages;
	}
	public function getSubMenu(){
		$subMenu = array_filter($this->childPages,fn($a) => $a->inMenu==true);
		return $subMenu;
	}
	public function hasSubPages() :bool{
		return !empty($this->getSubMenu());
	}
}




?>