<?php
namespace FriendlyURL;

class PageManager {

	/**
	 * @var array List of registered pages.
	 */
	private array $pages = [];
	/**
	 * @var self|null Singleton instance.
	 */
	private static ?self $instance = null;
	
	/**
	 * @var bool Flag to track if pages have been run.
	 */
	private bool $ran = false;
	
	/**
	 * @var string|null Current request URI.
	 */
	private $request;
	
	/**
	 * @var object|null Current page object.
	 */
	private $currentPage = null;
	
	/**
	 * @var array List of loaded modules.
	 */
	private $modules = [];
	
	/**
	 * @var callable|null Callback function for capability checks.
	 */
	private $capabilityCallback = null;
	
	/**
	 * Private constructor to enforce the singleton pattern.
	 *
	 * @throws \Exception If another instance is attempted.
	 */
	private function __construct() {
		if (self::$instance !== null) {
			throw new \Exception("Cannot create multiple instances of PageManager. Use PageManager::getInstance()");
		}
		self::$instance = $this;
		$this->modules_autoload();
		$this->request = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 1);
		$this->currentPage = $this->getPageByRegex($this->request);
	}
	
	/**
	 * Prevents unserialization of the singleton instance.
	 */
	public function __wakeup() {}
	
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
	
	/**
	 * Retrieves the current request URI.
	 *
	 * @return string The request URI.
	 */
	private function getRequest(): string {
		return $this->request;
	}
	
	/**
	 * Auto-loads all route modules. Each directory and file must have the same name.
	 */
	private function modules_autoload(): void {
		foreach (glob('modules/**/') as $dir) {
			$module = basename($dir);
			if (file_exists($dir . $module . '.php')) {
				include_once($dir . $module . '.php');
				$this->modules[] = $module;
			}
		}
	}
	
	/**
	 * Destructor to ensure pages are run before destruction.
	 */
	function __destruct() {
		if (!$this->ran) {
			//$this->runPages();
		}
	}
	
	/**
	 * Runs registered pages.
	 */
	private function runPages() {
		$this->ran = true;
		try {
			if (!($page = $this->getPageByRegex($this->request))) {
				throw new \Exception('404');
			}
			if ($page->getArguments()) {
				$arguments = $page->getArguments();
			}
			if (is_callable($page->getArgumentsCallback())) {
				$arguments = call_user_func($page->getArgumentsCallback());
			}
			$this->runCallback($page->getCallback(), $arguments ?? []);
		} catch (\Exception $e) {
			$this->return_error($e);
		}
	}
	
	/**
	 * Displays an error in JSON format.
	 *
	 * @param \Exception $e The exception to display.
	 */
	private function return_error($e) {
		echo '<pre>' . json_encode([
				'success' => false,
				'error' => $e->getMessage(),
				'code' => $e->getCode(),
				'file' => basename($e->getFile()),
				'line' => $e->getLine(),
				'trace' => $e->getTrace(),
			], JSON_PRETTY_PRINT) . '</pre>';
	}
	
	/**
	 * Executes a callback function with the provided arguments.
	 *
	 * @param callable|array $callback The callback function to execute.
	 * @param array $arguments The arguments to pass to the callback.
	 */
	private function runCallback(callable|array $callback, $arguments = []) {
		call_user_func($callback, $arguments);
	}
	
	/**
	 * Retrieves a page by matching a regex pattern.
	 *
	 * @param string|null $needle The request URI to match against.
	 * @return bool|object The matched page or false if none found.
	 */
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
			preg_match($page->getRegex(), $needle, $matches);
			$page->setRegexVariables($matches);
			return $page;
		}
		return false;
	}
	
	/**
	 * Retrieves a page by its slug.
	 *
	 * @param string $needle The slug to search for.
	 * @return bool|object The matched page or false if none found.
	 */
	private function getPageBySlug($needle): bool | object {
		foreach ($this->pages as $page) {
			if ($page->getSlug() === $needle) {
				return $page;
			}
		}
		return false;
	}
	
	/**
	 * Registers a new page.
	 *
	 * @param Page $page The page object to register.
	 * @return Page The registered page.
	 */
	private function registerPage(Page $page) {
		$this->pages[] = $page;
		return $page;
	}
		
	/**
	 * Retrieves pages, optionally filtered by parent slug.
	 *
	 * @param string|null $parentSlug The parent slug to filter by.
	 * @return array The filtered list of pages.
	 */
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
		return self::getInstance()->currentPage();
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

		return self::addPage(
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
		);
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
		return self::addPage(
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
		);
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
		return self::addPage(
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
		);
	}
	static function checkCapability($capability){
		if(is_callable(self::getInstance()->capabilityCallback)){
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



