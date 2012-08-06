<?php
/**
 * Minds Wall Plugin
 *
 * @author Mark Harding (Kramnorth)
 *
 * @package Wall
 */

elgg_register_event_handler('init', 'system', 'wall_init');

function wall_init() {
	
	elgg_extend_view('css/elgg', 'wall/css');
	
	elgg_register_widget_type('wall', elgg_echo('wall:title'), elgg_echo('wall:info'));
	
	elgg_register_page_handler('wall', 'wall_page_handler');
	
	$wall_js = elgg_get_simplecache_url('js', 'wall');
	elgg_register_simplecache_view('js/wall');
	elgg_register_js('elgg.wall', $wall_js, 'footer');
	
	// Register a URL handler for thewire posts
	elgg_register_entity_url_handler('object', 'wallpost', 'wall_url');
	
	// remove edit and access
	elgg_register_plugin_hook_handler('register', 'menu:entity', 'wall_setup_entity_menu_items');
	
	// Register actions
	$action_base = elgg_get_plugins_path() . 'wall/actions';
	elgg_register_action("wall/add", "$action_base/add.php");
	elgg_register_action("wall/delete", "$action_base/delete.php");
}

/**
 * Wall page handler
 *
 * @param array $page Array of page elements
 * @return bool
 */
function wall_page_handler($page) {

	$pages = dirname(__FILE__) . '/pages/wall';

	switch ($page[0]) {
		case 'owner':
			//@todo if they have the widget disabled, don't allow this.
			$owner_name = elgg_extract(1, $page);
			$owner = get_user_by_username($owner_name);
			set_input('page_owner_guid', $owner->guid);
			$history = elgg_extract(2, $page);
			$username = elgg_extract(3, $page);

			if ($history && $username) {
				set_input('history_username', $username);
			}

			include "$pages/owner.php";
			break;

		case 'view':
			$guid = elgg_extract(1, $page);
			set_input('guid', $guid);
			include "$pages/view.php";
			break;

		default:
			$username = elgg_extract(0, $page);
			$owner = get_user_by_username($username);
			set_input('page_owner_guid', $owner->guid);
			include "$pages/owner.php";
			return true;
	}
	return true;
}

/**
 * Override the url for a wall post
 * 
 * @param ElggObject $thewirepost Wire post object
 */
function wall_url($wallpost) {
	global $CONFIG;
	return $CONFIG->url . 'wall/view/' . $wallpost->guid;
}

/**
 * Sets up the entity menu for wall
 *
 * Removes edit and access.
 *
 * @param string $hook   Hook name
 * @param string $type   Hook type
 * @param array  $value  Array of menu items
 * @param array  $params Array with the entity
 * @return array
 */
function wall_setup_entity_menu_items($hook, $type, $value, $params) {
	$handler = elgg_extract('handler', $params, false);
	if ($handler != 'wall') {
		return $value;
	}

	foreach ($value as $index => $item) {
		$name = $item->getName();
		//if ($name == 'access' || $name == 'edit') {
			unset($value[$index]);
		//}
	}

	$entity = $params['entity'];
	
	$options = array(
		'name' => 'delete',
		'text' => elgg_view_icon('delete'),
		'href' => "action/wall/delete?guid=" . $entity->guid,
		'class' => 'elgg-requires-confirmation',
		'is_action' => true,
		'priority' => 9000,
	);
	$value[] = ElggMenuItem::factory($options);

	return $value;
}
