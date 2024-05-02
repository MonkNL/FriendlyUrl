<?php
namespace FriendlyURL;
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
