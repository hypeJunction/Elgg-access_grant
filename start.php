<?php

/**
 * Access grant
 *
 * @author Ismayil Khayredinov <info@hypejunction.com>
 * @copyright Copyright (c) 2015, Ismayil Khayredinov
 */
require_once __DIR__ . '/autoloader.php';

elgg_register_event_handler('init', 'system', 'access_grant_init');

/**
 * Initialize the plugin
 * @return void
 */
function access_grant_init() {

	elgg_register_plugin_hook_handler('get_sql', 'access', 'access_grant_get_sql');
}

/**
 * Populates access WHERE sql clauses
 *
 * @param string $hook   "get_sql"
 * @param string $type   "access"
 * @param array  $return Clauses
 * @param array  $params Hook params
 * @return array
 */
function access_grant_get_sql($hook, $type, $return, $params) {

	$ignore_access = elgg_extract('ignore_access', $params);
	if ($ignore_access) {
		return;
	}

	$user_guid = elgg_extract('user_guid', $params);
	if (!$user_guid) {
		return;
	}

	$prefix = elgg_get_config('dbprefix');
	$table_alias = $params['table_alias'] ? $params['table_alias'] . '.' : '';
	$guid_column = $params['guid_column'];

	if (strpos($table_alias, 'n_table') === 0) {
		// temp fix for https://github.com/Elgg/Elgg/pull/9290
		$guid_column = 'entity_guid';
	}

	$return['ors'][] = "(EXISTS(SELECT 1 FROM {$prefix}entity_relationships er_access_grant
					WHERE er_access_grant.guid_one = {$table_alias}{$guid_column}
						AND er_access_grant.relationship = 'access_grant'
						AND er_access_grant.guid_two = {$user_guid}))";

	return $return;
}