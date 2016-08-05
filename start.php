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
	
	if (elgg_is_active_plugin('elgg_solr')) {
		elgg_register_plugin_hook_handler('elgg_solr:index', 'all', 'access_grant_solr_index');
		elgg_register_plugin_hook_handler('elgg_solr:access', 'all', 'access_grant_solr_access_query');
	}
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

use Solarium\QueryType\Update\Query\Document\DocumentInterface;

/**
 * Index access grants
 *
 * @param string            $hook   "elgg_solr:index"
 * @param string            $type   "object"|"group"|"user"
 * @param DocumentInterface $return Solr document
 * @param array             $params Hook params
 * @return DocumentInterface
 */
function access_grant_solr_index($hook, $type, $return, $params) {

	if (!$return) {
		return;
	}

	$entity = elgg_extract('entity', $params);
	if (!elgg_instanceof($entity)) {
		return;
	}

	$access_grants = [];
	$access_grants_batch = new ElggBatch('elgg_get_entities_from_relationship', [
		'type' => 'user',
		'relationship' => 'access_grant',
		'relationship_guid' => $entity->guid,
		'limit' => 0,
		'callback' => false,
	]);

	foreach ($access_grants_batch as $user) {
		$access_grants[] = $user->guid;
	}

	$return->access_grants_is = $access_grants;
	
	return $return;
}

/**
 * Update solr access query to include access grants
 * 
 * @param string $hook   "elgg_solr:access"
 * @param string $type   "entities"
 * @param array  $return Queries
 * @param array  $params Hook params
 * @return array
 */
function access_grant_solr_access_query($hook, $type, $return, $params) {

	$user_guid = elgg_extract('user_guid', $params);
	if (!$user_guid) {
		return;
	}
	
	$return['ors']['access_granted'] = "access_grants_is:($user_guid)";
	return $return;
}