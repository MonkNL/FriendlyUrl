<?php
function addPage(
	string 			$title,
	string 			$menuTitle,
	array|string	$capability,
	string 			$slug,
					$callback = null,
					$argumentsCallback = null,
					$arguments = null,
	?int 			$priority = null,
	bool 			$inMenu = false,
	string 			$parentSlug = ''
) {
	return FriendlyURL\PageManager::addPage(
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
function addSubPage(
	string 			$parentSlug,
	string 			$title,
	string 			$menuTitle,
	array|string 	$capability,
	string 			$slug,
					$callback 			= null,
					$argumentsCallback 	= null,
					$arguments 			= null,
	?int	 			$priority 			= null,
	bool 			$inMenu 			= false
) {

	return addPage(
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
function addMenuPage(
	string 			$title, 
	string 			$menuTitle, 
	array|string 	$capability, 
	string 			$slug, 
					$callback 			= null,
					$argumentsCallback 	= null,
					$arguments 			= null,
	?int 			$priority = null,
	string 			$parentSlug = ''
){
	return addPage(
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
function addSubmenuPage(
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
	return addPage(
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
?>