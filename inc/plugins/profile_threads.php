<?php
/**
 * Categorized Threads on Profile
 * Author: Shinka
 * Copyright 2017 Shinka, All Rights Reserved
 *
 * License: http://www.mybb.com/about/license
 *
 */

// Disallow direct access to this file for security reasons
if (!defined('IN_MYBB')) {
	die('Direct initialization of this file is not allowed.');
}

$plugins->add_hook('datahandler_post_insert_thread', 'profile_threads_insert');
$plugins->add_hook('datahandler_post_update_thread', 'profile_threads_update');
$plugins->add_hook('newthread_end', 'profile_threads_newthread');
$plugins->add_hook('editpost_end', 'profile_threads_newthread');
$plugins->add_hook('member_profile_end', 'profile_threads_member_profile');

function profile_threads_info() {
	global $lang;
	$lang->load('profile_threads');
	return array(
		'name'			=> $lang->profile_threads_name,
		'description'	=> $lang->profile_threads_desc,
		'website'		=> 'https://github.com/kalynrobinson/profile_threads',
		'author'		=> 'Shinka',
		'authorsite'	=> 'https://github.com/kalynrobinson/profile_threads',
		'version'		=> '1.0.0',
		'guid' 			=> '',
		'codename'		=> 'profile_threads',
		'compatibility' => '18'
	);
}

function profile_threads_install() {
	global $mybb, $db, $lang;
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

	find_replace_templatesets(
		'newthread',
		'#{\$posticons}#',
		'{\$posticons}{$profile_threads_row}'
	);

	find_replace_templatesets(
		'member_profile',
		'#{\$footer}#',
		'{$profile_threads_profile}{\$footer}'
	);

	// Create category column on threads table.
	if (!$db->field_exists("category", "threads")) {
		$db->add_column("threads", "category", "varchar(255)");
	}

	$row_template = '<tr>
        <td class="trow2" width="20%"><strong>{$lang->profile_threads_row_label}</strong></td>
        <td class="trow2"><select name="profile_category">
			<option value="">{$lang->profile_threads_blank_option}</option>
			$profile_threads_options
		</select></td>
	</tr>';
	$options_template = '<option value="{$category}"{$selected}>{$category}</option>';
	$profile_template = '<br/>
	<table border="0" cellspacing="0" cellpadding="5" class="tborder tfixed">
		<colgroup>
			<col style="width: 30%;">
		</colgroup>
		<tbody>
			<tr>
				<td colspan="3" class="thead"><strong>{$lang->profile_threads_table_header}</strong></td>
			</tr>
			$profile_threads
		</tbody>
	</table>';
	$profile_row_template = '<tr>
		<td class="trow1">{$profile_thread[\'date\']}</td>
		<td class="trow1">{$profile_thread[\'category\']}</td>
		<td class="trow1"><a href="{$profile_thread[\'url\']}">{$profile_thread[\'subject\']}</a></td>
	</tr>';
	$profile_row_none_template = '<tr>
		<td colspan="3" class="trow1">{$lang->profile_threads_none}</td>
	</tr>';

	$templates = array(
		array(
			"tid" => NULL,
			"title" => 'profile_threads_row',
			"template" => $db->escape_string($row_template),
			"sid" => "-1",
			"version" => $mybb->version + 1,
			"dateline" => time(),
		),
		array(
			"tid" => NULL,
			"title" => 'profile_threads_options',
			"template" => $db->escape_string($options_template),
			"sid" => "-1",
			"version" => $mybb->version + 1,
			"dateline" => time(),
		),
		array(
			"tid" => NULL,
			"title" => 'profile_threads_profile',
			"template" => $db->escape_string($profile_template),
			"sid" => "-1",
			"version" => $mybb->version + 1,
			"dateline" => time(),
		),
		array(
			"tid" => NULL,
			"title" => 'profile_threads_profile_row',
			"template" => $db->escape_string($profile_row_template),
			"sid" => "-1",
			"version" => $mybb->version + 1,
			"dateline" => time(),
		),
		array(
			"tid" => NULL,
			"title" => 'profile_threads_profile_row_none',
			"template" => $db->escape_string($profile_row_none_template),
			"sid" => "-1",
			"version" => $mybb->version + 1,
			"dateline" => time(),
		)
	);

	foreach ($templates as $template) $db->insert_query("templates", $template);

	$db->insert_query("templates", $temp);

	// Create settings.
	$setting_group = array(
		'name' => 'profile_threads',
		'title' => $lang->profile_threads_settings_title,
		'description' => $lang->profile_threads_settings_desc,
		'disporder' => 5,
		'isdefault' => 0
	);

	$gid = $db->insert_query("settinggroups", $setting_group);

	$setting_array = array(
		'profile_threads_categories' => array(
			'title' => $lang->profile_threads_categories_title,
			'description' => $lang->profile_threads_categories_desc,
			'optionscode' => 'textarea',
			'value' => 'Category 1\nCategory 2',
			'disporder' => 1
		),
			'profile_threads_forums' => array(
			'title' => $lang->profile_threads_forums_title,
			'description' => $lang->profile_threads_forums_desc,
			'optionscode' => "forumselect",
			'disporder' => 2
		),
		'profile_threads_usergroups' => array(
			'title' => $lang->profile_threads_usergroups_title,
			'description' => $lang->profile_threads_usergroups_desc,
			'optionscode' => 'groupselect',
			'disporder' => 3
		)
	);

	foreach($setting_array as $name => $setting) {
		$setting['name'] = $name;
		$setting['gid'] = $gid;

		$db->insert_query('settings', $setting);
	}

	rebuild_settings();
}

function profile_threads_is_installed() {
	global $mybb;

	return isset($mybb->settings['profile_threads_categories']);
}

function profile_threads_is_activated() {
	global $mybb;

	return isset($mybb->settinggroups['profile_threads']);
}

function profile_threads_uninstall() {
	global $db;
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

	if ($db->field_exists("category", "threads")) $db->drop_column('threads', 'category');
	$db->delete_query("templates", "title LIKE 'profile_threads_%'");
	$db->delete_query('settings', "name LIKE 'profile_threads_%'");
	$db->delete_query('settinggroups', "name = 'profile_threads'");

	find_replace_templatesets(
		'newthread',
		'#{\$profile_threads_row}#',
		''
	);

	find_replace_templatesets(
		'member_profile',
		'#{\$profile_threads_profile}#',
		''
	);

	rebuild_settings();
}

function profile_threads_insert($thread) {
    global $mybb;

	$thread->thread_insert_data['category'] = $mybb->input['profile_category'];
}

function profile_threads_update($thread) {
    global $mybb;

	$thread->thread_update_data['category'] = $mybb->input['profile_category'];
}

function profile_threads_has_permission($usergroups) {
	global $mybb, $fid;

	$allowed = true;

	// Not all groups are allowed
	if ($mybb->settings['profile_threads_usergroups'] != -1) {
		$user_groups = explode(',', $usergroups);
		$allowed_groups = explode(',', $mybb->settings['profile_threads_usergroups']);
		$allowed = count(array_intersect($user_groups, $allowed_groups)) > 0;
	}

	// Not all forums are allowed
	if ($fid && $mybb->settings['profile_threads_forums'] != -1) {
		$allowed_forums = explode(',', $mybb->settings['profile_threads_forums']);
		$forum = $fid;
		$allowed = in_array($forum, $allowed_forums);
	}

	return $allowed;
}

function profile_threads_newthread() {
	global $templates, $mybb, $lang, $thread, $pid, $profile_threads_options, $profile_threads_row;

	// Check if editing post and if post is first in thread
	if ($thread && $thread['firstpost'] != $pid) return;

	$usergroups = $mybb->user['displaygroup'];
	if ($mybb->user['additionalgroups']) $usergroups .= ',' . $mybb->user['additionalgroups'];
	if (!profile_threads_has_permission($usergroups)) return;

	$lang->load('profile_threads');

	$categories = explode("\n", $mybb->settings['profile_threads_categories']);

	foreach ($categories as $category) {
		// rtrim because a newline is mysteriously added between now and eval().
		$category = rtrim($category);
		if ($thread && $thread['category'] == $category) $selected = 'selected';
		eval('$profile_threads_options  .= "' . $templates->get('profile_threads_options') . '";');
	}

	eval('$profile_threads_row  = "' . $templates->get('profile_threads_row') . '";');
}

function profile_threads_member_profile() {
	global $templates, $mybb, $db, $lang, $profile_threads_profile, $memprofile;

	$usergroups = $memprofile['displaygroup'];
	if ($memprofile['additionalgroups']) $usergroups .= ',' . $memprofile['additionalgroups'];
	if (!profile_threads_has_permission($usergroups)) return;

	$lang->load('profile_threads');

	$uid = $mybb->user['uid'];
	$query = $db->simple_select('threads', 'dateline, category, subject, tid', "uid={$uid} AND category IS NOT NULL AND category!=''");
	
	if ($query->num_rows == 0) {
		eval('$profile_threads = "' . $templates->get('profile_threads_profile_row_none') . '";');
	}

	while ($profile_thread = $db->fetch_array($query)) {
		$profile_thread['date'] = date($mybb->settings['dateformat'], $profile_thread['dateline']);
		$profile_thread['url'] = $mybb->settings['bburl'] . '/showthread.php?tid=' . $profile_thread['tid'];
		eval('$profile_threads .= "' . $templates->get('profile_threads_profile_row') . '";');
	}

	eval('$profile_threads_profile = "' . $templates->get('profile_threads_profile') . '";');
}