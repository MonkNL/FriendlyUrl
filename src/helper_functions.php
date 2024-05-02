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

	return call_user_func_array([FriendlyURL\Pages::getInstance(), 'addPage'], $arguments);
}
?>