<?php
namespace FriendlyURL;
Class Page{
	private 	$title, 
		$menuTitle, 
		$capability,  
		$slug 				= '',
		$callback 			= null,
		$argumentsCallback 	= null,
		$arguments 			= null,
		$priority 			= 10,
		$inMenu 			= false,
		$subPages 			= [],
		$parentSlug 		= null,
		$regexVariables 	= [],
		$regex				='',
		$parent 			= null;	 
	
	public function __construct(
			string 			$title, 
			string 			$menuTitle, 
			array|string 	$capability, 
			string 			$slug, 
							$callback = null, 
							$argumentsCallback 	= null,
							$arguments 			= null,
			?int 			$priority = 10,
			bool 			$inMenu = false,
			?string 		$parentSlug = null
		) {
		$this->setTitle($title); 
		$this->setmenuTitle($menuTitle); 
		$this->setcapability($capability); 
		$this->setSlug($slug);
		$this->setCallback($callback); 
		$this->setArgumentsCallback($argumentsCallback);
		$this->setArguments($arguments);
		$this->setPriority($priority);
		$this->setInMenu($inMenu);
		$this->setParentSlug($parentSlug);
	}
	public function setTitle(string $title): void{
		$this->title = $title;
	}
	public function getTitle():string{
		return $this->title;
	}
	public function setMenuTitle(string $title): void{
		$this->menuTitle = $title;
	}
	public function getMenuTitle():string{
		return $this->menuTitle;
	}
	public function setCapability(string|array $capability): void{
		$this->capability = $capability;
	}
	public function getCapability(): array|string{
		return $this->capability;
	}
	public function setSlug(string $slug): void{
		$this->slug = $slug;
		$slug 		= (substr($slug,-1)=='/'?substr($slug,0,-1):$slug);
		$regex		= '/^'.str_replace('/','\/',$slug).'\/?$/'; // ^ = start, $ = end, \/? = 0 or 1 /
		$this->setRegex($regex);
	}
	public function getSlug(): string{
		return $this->slug;
	}
	private function setRegex(string $regex): void{
		$this->regex = $regex;
	}
	public function getRegex(): string {
		return $this->regex;
	}
	public function setCallback($callback){
		$this->callback = $callback;
	}
	public function getCallback(){
		return $this->callback;
	}
	public function setArgumentsCallback($argumentsCallback){
		$this->argumentsCallback = $argumentsCallback;
	}
	public function getArgumentsCallback(){
		return $this->argumentsCallback;
	}
	public function setArguments($arguments){
		$this->arguments = $arguments;
	}
	public function getArguments(){
		return $this->arguments;
	}
	public function setPriority(?int $priority){
		$this->priority		= $priority;
	}
	public function getPriority() :?int{
		return $this->priority;
	}
	public function setInMenu(bool $inMenu){
		$this->inMenu		= $inMenu;
	}
	public function getInMenu(): bool{
		return $this->inMenu;
	}
	public function setparentSlug(?string $slug):void{
		$this->parentSlug 	= $slug;
	}
	public function getparentSlug():?string{
		return $this->parentSlug;
	}
	public function setRegexvariables($matches){
		$this->regexVariables = $matches;
	}
	public function getRegexvariables(){
		return $this->regexVariables;
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
