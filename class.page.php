<?php
Class page{
	public 	$pageTitle, 
		$menuTitle, 
		$capability, 
		$pageSlug = '',
		$callback = null,
		$priority = 10,
		$inMenu = false,
		$subPages = [],
		$parentSlug = null,
		$regexVariables = [],
		$childPages = [],
		$regex='',
		$parent = null;	 
	
	public function __construct(string $pageTitle, string $menuTitle, array|string $capability, string $pageSlug, callable $callback = null, int|float $priority= null,bool $inMenu = false,$parentSlug = null) {
		$this->pageTitle	= $pageTitle; 
		$this->menuTitle	= $menuTitle; 
		$this->capability	= $capability; 
		$this->pageSlug		= $pageSlug;
		$this->callback		= $callback; 
		$this->priority		= $priority;
		$this->inMenu		= $inMenu;
		$this->parentSlug 	= $parentSlug;
		$this->subPages		= [];
		$slug 			= (substr($pageSlug,-1)=='/'?substr($pageSlug,0,-1):$pageSlug);
		$this->regex		= '/^'.str_replace('/','\/',$slug).'\/?$/'; // ^ = start, $ = end, \/? = 0 or 1 /
	}
	public function setRegexvariables($matches){
		$this->regexVariables = $matches;
	}
	public function regexSprintF(){
		return preg_replace('/(\(.*\))/U', '%s', $this->pageSlug);
	}
	public function addSubPage(page $page){
		$this->subPages[] = $page;
		usort($this->subPages, fn($a,$b) => $a->priority <=> $b->priority);
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
