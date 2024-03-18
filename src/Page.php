<?php
namespace FriendlyURL;
Class Page{
	public 	$pageTitle, 
		$menuTitle, 
		$capability,  
		$pageSlug 		= '',
		$callback 		= null,
		$priority 		= 10,
		$inMenu 		= false,
		$subPages 		= [],
		$parentSlug 	= null,
		$regexVariables = [],
		$regex			='',
		$parent 		= null;	 
	
	public function __construct(
			string 			$pageTitle, 
			string 			$menuTitle, 
			array|string 	$capability, 
			string 			$pageSlug, 
			?callable 		$callback = null, 
			?int 			$priority = 10,
			bool 			$inMenu = false,
			?string 		$parentSlug = null
		) {
		$this->setTitle($pageTitle); 
		$this->setmenuTitle($menuTitle); 
		$this->setcapability($capability); 
		$this->setSlug($pageSlug);
		$this->setCallback($callback); 
		$this->setPriority($priority);
		$this->setInMenu($inMenu);
		$this->setParentSlug($parentSlug);
	}
	public setTitle(string $title): void{
		$this->title = $title;
	}
	public setMenuTitle(string $title): void{
		$this->menuTitle = $title;
	}
	public setCapability(string|array $capability): void{
		$this->capability = $capability;
	}
	public setSlug(string $slug): void{
		$this->slug = $slug;
		$slug 				= (substr($pageSlug,-1)=='/'?substr($pageSlug,0,-1):$pageSlug);
		$regex		= '/^'.str_replace('/','\/',$slug).'\/?$/'; // ^ = start, $ = end, \/? = 0 or 1 /
		$this->setRegex($regex);
	}
	private setRegex(string $regex) :void{
		$this->regex = $regex;
	}
	public setCallback(callable $callback){
		$this->callback = $callback;
	}
	public setPriority(?int $priority){
		$this->priority		= $priority;
	}
	public setInMenu(){
		$this->inMenu		= $inMenu;
	}
	public setparentSlug(string $slug):void{
		$this->parentSlug 	= $parentSlug;
	}
	public function setRegexvariables($matches){
		$this->regexVariables = $matches;
	}
	public function regexSprintF(){
		return preg_replace('/(\(.*\))/U', '%s', $this->pageSlug);
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
