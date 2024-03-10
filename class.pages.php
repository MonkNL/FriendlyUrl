<?php
class Pages{

	private array $pages = [];
	private static $instance;
	private $request;
	private $currentPage = null;
	private $hooks 			= [];
	private $modules 		= [];

	public function __construct() {
		self::$instance 	= $this;
		$this->modules_autoload();

		$this->request  	= substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),1);
		$this->currentPage = $this->getPageByRegex($this->request);

	}
	private function getRequest(){
		return $this->request;
	}

	private function modules_autoload(){ // auto load all route modules, dir and constructor need to have the same name
		foreach(glob('modules/**/') as $dir){
				if(file_exists($dir.basename($dir).'.php')){
						include_once($dir.basename($dir).'.php');
						$this->modules[] = basename($dir);
				}
		}
	 
		return  $this->modules;
	}
	public function __destruct(){
		try{
		
			if(!($page = $this->getPageByRegex($this->request,true))){
					throw new Exception('No route found');
			}
			if(is_array($page->capability) && !call_user_func_array([CurrentUser::get(),'hasAuthorization'],$page->capability)){
				$this->doHook('before_html');
				echo 'U heeft geen rechten tot deze pagina';
				$this->doHook('after_html');
				exit;
			}

				//$this->get_variables();
				$this->doHook('before_html');
				$this->runCallback($page->callback);
				$this->doHook('after_html');
		}catch(Exception $e){
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
		//echo json_encode($e);
		//exit;

		echo json_encode([
				'success' 				=> false,
				'error'   				=> $e->getMessage(),
				'code'						=> $e->getCode(),
				'file'						=> basename($e->getFile()),
				'line'						=> $e->getLine(),
				'trace'						=> $e->getTrace(),
				'execution_time' 	=> round(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],3),
				]);
				exit;
	}
	private function runCallback(callable $callback) : void{
		
		$data = call_user_func($callback,[]);
	}
	private function doHook($hook,... $args) : void{
		if(empty($this->hooks[$hook])){
			return;
		}
		usort($this->hooks[$hook], function ($a, $b) {return $a['priority'] <=> $b['priority'];});
		foreach($this->hooks[$hook] as $current_action){
			call_user_func_array($current_action['callback'],$args);
		}
	}
	private function addAction(string $hook,callable $callback,$priority = 10,$args = []) : void{
		$this->hooks[$hook][] = ['callback'=>$callback,'priority'=>$priority,'args'=>$args];
	}

	private function getPageByRegex($needle){
		$pageRegex = array_column($this->pages,'regex');
		foreach($pageRegex as  $k => $regex){ 
			if(preg_match($regex,$needle,$matches) != false){
					$this->pages[$k]->setRegexVariables($matches);
					return  $this->pages[$k];
			}
		}
		return false;
	}

	private function getPageBySlug($needle){
		$pageSlugs = array_column($this->pages,'pageSlug');
		$key = array_search($needle,$pageSlugs);
		if(is_int($key) || is_string($key)){
			return $this->pages[$key];
		}

		return false;
		
	}

	public static function getInstance(){
		if (is_null( self::$instance )){  new self(); }
		self::$instance->currentPage =self::$instance->getPageByRegex(self::$instance->request);
		return self::$instance;
	}
	private function addPage(string $pageTitle, string $menuTitle, array|string $capability, string $pageSlug, callable $callback = null, int|float $priority= null, bool $inMenu = false,string $parentSlug =''){
		$this->pages[] = new Page($pageTitle,$menuTitle,$capability, $pageSlug, $callback,$priority,$inMenu,$parentSlug);
	}

	private function addSubPage( string $parentSlug, string $pageTitle, string $menuTitle, array|string $capability, string $pageSlug, callable $callback = null, int|float $priority = null,bool $inMenu = false){

		//$parent->addSubPage(new page($pageTitle,$menuTitle,$capability,$pageSlug,$callback,$priority,$inMenu,$parentSlug));
	}
	private function getPages(){
		//$menuItems = array_filter($this->page,fn($v) => $v['inMenu']==true);
		usort($this->pages, function ($a, $b) {return $a->priority <=> $b->priority;});
		return $this->pages;
	}
	private function getsubPages($slug){
		//print_r(array_column($this->pages,'parentSlug'));
	}
	
	private function addMenuPage(){
		$args = func_get_args(); $args['inMenu'] = true;
		return call_user_func_array([$this,'addPage'],$args);
	}
	private function addSubmenuPage(){
		$args = func_get_args();$args['inMenu'] = true;
		return call_user_func_array([$this,'addSubPage'],$args);
	}

	private $traceBack;
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
			if(is_array($page->capability) && !call_user_func_array([CurrentUser::get(),'hasAuthorization'],$page->capability)){
				continue;
			}
			$childPages	= [];
			if(!empty($page->pageSlug)){ 
				$childPages = $this->getMenu($page->pageSlug,$page );
				usort($childPages, function ($a, $b) {return $a->priority <=> $b->priority;});
			}
			$page->childPages = $childPages;
			$pages[] = $page;
			

		}
		usort($pages, function ($a, $b) {return $a->priority <=> $b->priority;});
		return $pages;
	}
	private function getParent($slug = ''){

		if($slug == ''){
			return false;
		}
		if(($parent = $this->getPageBySlug($slug)) == false){
			return false;
		}
		return $parent;
	}
	private function breadcrumbs($slug = ''){
		$breadCrumbsArray = [];
		if($slug == ''){
			return false;
		}
		do{
			$parent = $this->getParent($slug); 
			$slug = $parent->parentSlug;
			array_unshift($breadCrumbsArray,$parent);
	

		
		}while($this->getParent($slug) != false);
		return $breadCrumbsArray;
	}
	private function currentPage(){
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
	static function add_page(string $pageTitle, string $menuTitle, array|string $capability, string $pageSlug, callable $callback = null, int|float $priority = null,bool $inMenu = false ){
		return call_user_func_array([self::getInstance(),'addPage'],[$pageTitle, $menuTitle, $capability,$pageSlug, $callback, $priority, $inMenu]);
	}
	static function add_sub_page( string $parentSlug, string $pageTitle, string $menuTitle, array|string $capability, string $pageSlug, callable $callback = null, int|float $priority= null,bool $inMenu = false){
		return call_user_func_array([self::getInstance(),'addPage'],[$pageTitle, $menuTitle, $capability,$pageSlug, $callback, $priority, $inMenu,$parentSlug]);
	}
	static function get_pages(){	
		return call_user_func_array([self::getInstance(),'getPages'],func_get_args());
	}
	static function get_subpages($parentSlug){
		return call_user_func_array([self::getInstance(),'getsubPages'],func_get_args());
	}
	static function add_menu_page(string $pageTitle, string $menuTitle, array|string $capability, string $pageSlug, callable $callback = null, int|float $priority = null ){
		return call_user_func_array([self::getInstance(),'addPage'],[$pageTitle, $menuTitle, $capability,$pageSlug, $callback, $priority, $inMenu = true]);
	}
	static function add_submenu_page( string $parentSlug, string $pageTitle, string $menuTitle, array|string $capability, string $pageSlug, callable $callback = null, int|float $priority= null ){
		return call_user_func_array([self::getInstance(),'addPage'],[$pageTitle, $menuTitle, $capability,$pageSlug, $callback, $priority, $inMenu = true,$parentSlug]);
	}
	static function get_menu(){	
		return call_user_func_array([self::getInstance(),'getMenu'],func_get_args());
	}
	static function do_hook(string $hook,... $args){
		return call_user_func_array([self::getInstance(),'doHook'],func_get_args());
	}
	static function add_action(string $hook,callable $callback,int $priority = 10,int $acceptedArguments  = 0){
		return call_user_func_array([self::getInstance(),'addAction'],func_get_args());
	}
}

function do_hook(string $hook,... $args):void{
	Pages::do_hook($hook,$args);
}
function add_action(string $hook,callable $callback,int $priority = 10,int $acceptedArguments  = 0):void{
	Pages::add_action($hook,$callback,$priority,$acceptedArguments);
}
