<?php
/**
 * @package ActiveHelper Live Help
 * @Version 3.5.0 
 * @Autor ActiveHelper Inc
 */

if (!defined('ACTIVEHELPER_LIVEHELP'))
	die('Hi there! I\'m just a plugin, not much I can do when called directly.');
    
function activeHelper_liveHelp_agents()
{
	global $wpdb, $activeHelper_liveHelp;
            
	$actions = array(
		'list' => 'activeHelper_liveHelp_agentsList',
		'edit' => 'activeHelper_liveHelp_agentsRegister',
		'register' => 'activeHelper_liveHelp_agentsRegister',
		'settings' => 'activeHelper_liveHelp_agentsSettings',
		'info' => 'activeHelper_liveHelp_agentsInfo'
	);
	if (!empty($_REQUEST['action']) && isset($actions[$_REQUEST['action']]))
		return $actions[$_REQUEST['action']]();

	return $actions['list']();
}

function activeHelper_liveHelp_agentsPost()
{
	$actions = array(
		'delete' => 'activeHelper_liveHelp_agentsDeletePost',
		'edit' => 'activeHelper_liveHelp_agentsEditPost',
		'settings' => 'activeHelper_liveHelp_agentsSettingsPost',
		'register' => 'activeHelper_liveHelp_agentsRegisterPost'
	);

	if (!empty($_REQUEST['action']) && isset($actions[$_REQUEST['action']]))
		return $actions[$_REQUEST['action']]();
}

function activeHelper_liveHelp_agentsDeletePost()
{
	global $wpdb, $activeHelper_liveHelp;

	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

	if (empty($_REQUEST['id']))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '&miss');
		exit;
	}

	$agent = $wpdb->get_row("
		SELECT username AS agent_username, firstname AS agent_firstname, lastname AS agent_lastname,
			email AS agent_email, department AS agent_department, privilege AS agent_privilege, 
			status AS agent_status, answers AS agent_answers
		FROM {$wpdb->prefix}livehelp_users
		WHERE id = '{$_REQUEST['id']}'
		LIMIT 1
	", ARRAY_A);

	if (empty($agent))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_agent') . '&miss');
		exit;
	}

	// delete domain relationships
	$wpdb->query("
		DELETE FROM {$wpdb->prefix}livehelp_users
		WHERE id = '{$_REQUEST['id']}'
	");
	$wpdb->query("
		DELETE FROM {$wpdb->prefix}livehelp_users
		WHERE id = '{$_REQUEST['id']}'
	");

	$wpdb->query("
		DELETE FROM {$wpdb->prefix}livehelp_users
		WHERE id = '{$_REQUEST['id']}'
	");

	wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_agent') . '&delete');
	exit;
}

function activeHelper_liveHelp_agentsList()
{
	global $wpdb, $activeHelper_liveHelp;

	$agentsList = $wpdb->get_results("
		SELECT id AS ID, username AS agent_username, status AS agent_status,
			department AS agent_department, email AS agent_email, answers AS agent_answers
		FROM {$wpdb->prefix}livehelp_users
		ORDER BY id
	", ARRAY_A);

	echo '
<div class="wrap">
	<h2 style="padding-right: 0;">
		LiveHelp » ' . __('Agents', 'activehelper_livehelp') . '
		<a class="button add-new-h2" href="admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '&amp;action=register">' . __('add new', 'activehelper_livehelp') . '</a>
	</h2>';

	if (isset($_GET['register']))
		echo '
	<div class="updated below-h2" id="message">
		<p>' . sprintf(__('The %s was successfully registered.', 'activehelper_livehelp'), __('agent', 'activehelper_livehelp')) . '</p>
	</div>';

	if (isset($_GET['update']))
		echo '
	<div class="updated below-h2" id="message">
		<p>' . sprintf(__('The %s was successfully updated.', 'activehelper_livehelp'), __('agent', 'activehelper_livehelp')) . '</p>
	</div>';

	if (isset($_GET['delete']))
		echo '
	<div class="error below-h2" id="message">
		<p>' . sprintf(__('The %s was deleted permanently.', 'activehelper_livehelp'), __('agent', 'activehelper_livehelp')) . '</p>
	</div>';

	if (isset($_GET['miss']))
		echo '
	<div class="error below-h2" id="message">
		<p>' . sprintf(__('The %s was not found.', 'activehelper_livehelp'), __('agent', 'activehelper_livehelp')) . '</p>
	</div>';

	echo '
	<div class="metabox-holder" style="padding-bottom: 10px;">
		<table cellspacing="0" class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th style="width: 50px" class="manage-column" scope="col">
						' . __('ID', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Username', 'activehelper_livehelp') . '</th>
					<th style="width: 200px" class="manage-column" scope="col">
						' . __('Email', 'activehelper_livehelp') . '</th>
					<th style="width: 165px" class="manage-column" scope="col">
						' . __('Department', 'activehelper_livehelp') . '</th>
					<th style="width: 100px" class="manage-column" scope="col">
						' . __('Status', 'activehelper_livehelp') . '</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th class="manage-column" scope="col">
						' . __('ID', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Username', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Email', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Department', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Status', 'activehelper_livehelp') . '</th>
				</tr>
			</tfoot>
			<tbody id="the-list">';

			if (empty($agentsList))
				echo '
				<tr valign="top" class="format-default">
					<td class="colspanchange" colspan="5"><p style="margin: 0; padding: .8ex; color: #888;">
						' . sprintf(__('No %s found.', 'activehelper_livehelp'), __('agents', 'activehelper_livehelp')) . '
					</p></td>
				</tr>';
			else
			{
				$alternate = false;
				foreach ($agentsList as $agent)
				{
					echo '
				<tr valign="top" class="' . ($alternate ? 'alternate' : '') . ' format-default">
					<td>
						' . $agent['ID'] . '
					</td>
					<td class="post-title page-title column-title" style="vertical-align: middle;">
						<strong><a href="admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '&amp;action=edit&amp;id=' . $agent['ID'] . '" class="row-title">
							' . $agent['agent_username'] . '</a></strong>
						<div class="row-actions">
							<span class="edit"><a href="admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '&amp;action=edit&amp;id=' . $agent['ID'] . '">
								' . __('Edit', 'activehelper_livehelp') . '</a> | </span>
							<span class="edit"><a href="admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '&amp;action=settings&amp;id=' . $agent['ID'] . '">
								' . __('Settings', 'activehelper_livehelp') . '</a> | </span>
							<span class="edit"><a href="admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '&amp;action=info&amp;id=' . $agent['ID'] . '">
								' . __('Client info', 'activehelper_livehelp') . '</a> | </span>
							<span class="trash"><a href="admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '&amp;action=delete&amp;id=' . $agent['ID'] . '" class="submitdelete" onclick="return window.confirm(\'' . __('Are you sure you want to delete this item permanently?', 'activehelper_livehelp') . '\');">
								' . __('Delete', 'activehelper_livehelp') . '</a></span>
						</div>
					</td>
					<td>
						' . $agent['agent_email'] . '
					</td>
					<td>
						' . $agent['agent_department'] . '
					</td>
					<td>
						' . ($agent['agent_status'] == 1 ? __('Enable', 'activehelper_livehelp') : __('Disable', 'activehelper_livehelp')) . '
					</td>
				</tr>';

					$alternate = !$alternate;
				}
			}

			echo '
			</tbody>
		</table>
	</div>
</div>';
}

function activeHelper_liveHelp_agentsRegisterPost()
{
	global $wpdb, $activeHelper_liveHelp;

	$_POST['agent_username'] = !empty($_POST['agent_username']) ? (string) $_POST['agent_username'] : '';
	$_POST['agent_password'] = !empty($_POST['agent_password']) ? (string) $_POST['agent_password'] : '';
	$_POST['agent_firstname'] = !empty($_POST['agent_firstname']) ? (string) $_POST['agent_firstname'] : '';
	$_POST['agent_lastname'] = !empty($_POST['agent_lastname']) ? (string) $_POST['agent_lastname'] : '';
	$_POST['agent_email'] = !empty($_POST['agent_email']) ? (string) $_POST['agent_email'] : '';
	$_POST['agent_department'] = !empty($_POST['agent_department']) ? (string) $_POST['agent_department'] : '';
	$_POST['agent_status'] = !empty($_POST['agent_status']) ? 1 : 0;
	$_POST['agent_privilege'] = !empty($_POST['agent_privilege']) ? 1 : 0;
	$_POST['agent_answers'] = !empty($_POST['agent_answers']) ? (int) $_POST['agent_answers'] : 1;
	
	$_POST['agent_domains'] = !empty($_POST['agent_domains']) ? (array) $_POST['agent_domains'] : array();

	$errors = array();
	$activeHelper_liveHelp['errors'] = &$errors;

	while (isset($_POST['submit']))
	{
		unset($_POST['submit']);

		if (empty($_POST['agent_username']))
			$errors['agent_username'] = sprintf(__('You must insert a %s', 'activehelper_livehelp'), __('user name', 'activehelper_livehelp')); // error

		if (empty($_POST['agent_email']))
			$errors['agent_email'] = sprintf(__('You must insert an %s', 'activehelper_livehelp'), __('email', 'activehelper_livehelp')); // error

		// errors ...
		if (!empty($errors))
			break;

		if ($agent['agent_username'] != $_POST['agent_username'])
		{
			$agent_exists = $wpdb->get_var("
				SELECT id
				FROM {$wpdb->prefix}livehelp_users
				WHERE username = '{$_POST['agent_username']}'
				LIMIT 1
			");

			if (!empty($agent_exists))
				$errors['agent_username'] = __('The username is already in use', 'activehelper_livehelp'); // error
		}

		// errors ...
		if (!empty($errors))
			break;

		$_POST['agent_password'] = !empty($_POST['agent_password']) ? md5($_POST['agent_password']) : '';

		$wpdb->query("
			INSERT INTO {$wpdb->prefix}livehelp_users
				(username, password, firstname, lastname, email, department, privilege, status, answers)
			VALUES
				('{$_POST['agent_username']}', '{$_POST['agent_password']}',
					'{$_POST['agent_firstname']}', '{$_POST['agent_lastname']}',
					'{$_POST['agent_email']}', '{$_POST['agent_department']}',
					'{$_POST['agent_privilege']}', '{$_POST['agent_status']}',
					'{$_POST['agent_answers']}')
		");

		$agent_ID = $wpdb->get_var("
			SELECT MAX(id)
			FROM {$wpdb->prefix}livehelp_users
		");
		foreach ($_POST['agent_domains'] as $domain_ID => $domain_status)
		{
			if (empty($domain_status))
				continue;

			$wpdb->query("
				INSERT INTO {$wpdb->prefix}livehelp_domain_user
					(id_domain, id_user, status)
				VALUES
					('{$domain_ID}', '{$agent_ID}', '1')
			");

			$relation_ID = $wpdb->get_var("
				SELECT MAX(id_domain_user)
				FROM {$wpdb->prefix}livehelp_domain_user
			");

			$wpdb->query("
				INSERT INTO {$wpdb->prefix}livehelp_sa_domain_user_role
					(id_domain_user, id_role)
				VALUES
					('{$relation_ID}', '1')
			");
		}

		// Duplicate agent folder with id 0 for this new agent.
		activeHelper_liveHelp_filesDuplicate($activeHelper_liveHelp['agentsDir'] . '/0',
			$activeHelper_liveHelp['agentsDir'] . '/' . $agent_ID);

		$agent_picture = '';
		while (!empty($_FILES['agent_picture']['tmp_name']))
		{
			$image = activeHelper_liveHelp_imagesUpload($activeHelper_liveHelp['agentsDir'] . '/' . $agent_ID, 'a' . $agent_ID, $_FILES['agent_picture']);
			unset($_FILES['agent_picture']);

			if ($image === false)
				break;

			$agent_picture = $image;
		}

		$wpdb->query("
			UPDATE {$wpdb->prefix}livehelp_users
			SET photo = '{$agent_picture}'
			WHERE id = '{$agent_ID}'
		");

		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '&action=info&id=' . $agent_ID);
		exit;
	}
}

function activeHelper_liveHelp_agentsEditPost()
{
	global $wpdb, $activeHelper_liveHelp;

	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
	$_POST['agent_username'] = !empty($_POST['agent_username']) ? (string) $_POST['agent_username'] : '';
	$_POST['agent_password'] = !empty($_POST['agent_password']) ? (string) $_POST['agent_password'] : '';
	$_POST['agent_firstname'] = !empty($_POST['agent_firstname']) ? (string) $_POST['agent_firstname'] : '';
	$_POST['agent_lastname'] = !empty($_POST['agent_lastname']) ? (string) $_POST['agent_lastname'] : '';
	$_POST['agent_email'] = !empty($_POST['agent_email']) ? (string) $_POST['agent_email'] : '';
	$_POST['agent_department'] = !empty($_POST['agent_department']) ? (string) $_POST['agent_department'] : '';
	$_POST['agent_status'] = !empty($_POST['agent_status']) ? 1 : 0;
	$_POST['agent_privilege'] = !empty($_POST['agent_privilege']) ? 1 : 0;
	$_POST['agent_answers'] = !empty($_POST['agent_answers']) ? (int) $_POST['agent_answers'] : 1;

	$_POST['agent_domains'] = !empty($_POST['agent_domains']) ? (array) $_POST['agent_domains'] : array();

	if (empty($_REQUEST['id']))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '&miss');
		exit;
	}

	$errors = array();
	$activeHelper_liveHelp['errors'] = &$errors;

	$agent = $wpdb->get_row("
		SELECT username AS agent_username, firstname AS agent_firstname, lastname AS agent_lastname,
			email AS agent_email, department AS agent_department, privilege AS agent_privilege, 
			status AS agent_status, photo AS agent_picture, answers AS agent_answers
		FROM {$wpdb->prefix}livehelp_users
		WHERE id = '{$_REQUEST['id']}'
		LIMIT 1
	", ARRAY_A);

	if (empty($agent))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_agent') . '&miss');
		exit;
	}

	$agents_domains = $wpdb->get_results("
		SELECT id_domain, status
		FROM {$wpdb->prefix}livehelp_domain_user
		WHERE id_user = '{$_REQUEST['id']}'
	", ARRAY_A);

	$_POST['agent_domains_old'] = array();
	foreach ($agents_domains as $agents_domain)
		if ($agents_domain['status'] == 1)
			$_POST['agent_domains_old'][$agents_domain['id_domain']] = 1;

	if (!isset($_POST['submit']))
	{
		$_POST = array_merge($_POST, $agent);
		$_POST['agent_domains'] = $_POST['agent_domains_old'];
	}
	$_POST['agent_picture'] = $agent['agent_picture'];

	while (isset($_POST['submit']))
	{
		unset($_POST['submit']);

		if (empty($_POST['agent_username']))
			$errors['agent_username'] = sprintf(__('You must insert a %s', 'activehelper_livehelp'), __('user name', 'activehelper_livehelp')); // error

		if (empty($_POST['agent_email']))
			$errors['agent_email'] = sprintf(__('You must insert an %s', 'activehelper_livehelp'), __('email', 'activehelper_livehelp')); // error

		// errors ...
		if (!empty($errors))
			break;

		if ($agent['agent_username'] != $_POST['agent_username'])
		{
			$agent_exists = $wpdb->get_var("
				SELECT id
				FROM {$wpdb->prefix}livehelp_users
				WHERE username = '{$_POST['agent_username']}'
				LIMIT 1
			");

			if (!empty($agent_exists))
				$errors['agent_username'] = __('The username is already in use', 'activehelper_livehelp'); // error
		}

		// errors ...
		if (!empty($errors))
			break;

		$_POST['agent_password'] = !empty($_POST['agent_password']) ? "'" . md5($_POST['agent_password']) . "'" : 'password';

		$agent_picture = '';
		while (!empty($_FILES['agent_picture']['tmp_name']))
		{
			if (!empty($_POST['agent_picture']))
				activeHelper_liveHelp_imagesDelete($activeHelper_liveHelp['agentsDir'] . '/' . $_REQUEST['id'], $_POST['agent_picture']);

			$image = activeHelper_liveHelp_imagesUpload($activeHelper_liveHelp['agentsDir'] . '/' . $_REQUEST['id'], 'a' . $_REQUEST['id'], $_FILES['agent_picture']);
			unset($_FILES['agent_picture']);

			if ($image === false)
				break;

			$agent_picture = $image;
		}

		$wpdb->query("
			UPDATE {$wpdb->prefix}livehelp_users
			SET
				username = '{$_POST['agent_username']}',
				password = {$_POST['agent_password']},
				firstname = '{$_POST['agent_firstname']}',
				lastname = '{$_POST['agent_lastname']}',
				email = '{$_POST['agent_email']}',
				department = '{$_POST['agent_department']}',
				privilege = '{$_POST['agent_privilege']}',
				answers = '{$_POST['agent_answers']}',
				status = '{$_POST['agent_status']}'" . (!empty($agent_picture) ? ",
				photo = '{$agent_picture}'" : "") . "
			WHERE id = '{$_REQUEST['id']}'
			LIMIT 1
		");

		$_POST['agent_domains_change'] = array();
		foreach ($_POST['agent_domains'] as $domain_ID => $domain_status)
			if (isset($_POST['agent_domains_old'][$domain_ID]) && empty($domain_status))
				$_POST['agent_domains_change'][$domain_ID] = 0;
			else if (!isset($_POST['agent_domains_old'][$domain_ID]) && !empty($domain_status))
				$_POST['agent_domains_change'][$domain_ID] = 1;

		foreach ($_POST['agent_domains_change'] as $domain_ID => $domain_status)
			if (!empty($domain_status))
			{
				$wpdb->query("
					INSERT INTO {$wpdb->prefix}livehelp_domain_user
						(id_domain, id_user, status)
					VALUES
						('{$domain_ID}', '{$_REQUEST['id']}', '1')
				");

				$relation_ID = $wpdb->get_var("
					SELECT MAX(id_domain_user)
					FROM {$wpdb->prefix}livehelp_domain_user
				");
				$wpdb->query("
					INSERT INTO {$wpdb->prefix}livehelp_sa_domain_user_role
						(id_domain_user, id_role)
					VALUES
						('{$relation_ID}', '1')
				");
			}
			else
			{
				$relation_ID = $wpdb->get_var("
					SELECT id_domain_user
					FROM {$wpdb->prefix}livehelp_domain_user
					WHERE id_domain = '{$domain_ID}'
						AND id_user = '{$_REQUEST['id']}'
					LIMIT 1
				");

				$wpdb->query("
					DELETE FROM {$wpdb->prefix}livehelp_sa_domain_user_role
					WHERE id_domain_user = '{$relation_ID}'
					LIMIT 1
				");
				$wpdb->query("
					DELETE FROM {$wpdb->prefix}livehelp_domain_user
					WHERE id_domain = '{$domain_ID}'
						AND id_user = '{$_REQUEST['id']}'
					LIMIT 1
				");
			}

		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '&update');
		exit;
	}
}

function activeHelper_liveHelp_agentsRegister()
{
	global $wpdb, $activeHelper_liveHelp;

	if (!empty($activeHelper_liveHelp['errors']))
		$errors = $activeHelper_liveHelp['errors'];

	$domainsList = $wpdb->get_results("
		SELECT id_domain AS ID, name AS domain_name, status AS domain_status,
			configuration AS domain_global_configuration
		FROM {$wpdb->prefix}livehelp_domains
		ORDER BY id_domain
	", ARRAY_A);

	$tabindex = 1;

	echo '
<div class="wrap">
	<div id="icon-edit" class="icon32 icon32-posts-post"><br /></div>
	<h2>
		LiveHelp » ' . __('Agents', 'activehelper_livehelp') . (!empty($_REQUEST['id']) ? ' <span style="font-size: 70%;">(' . $_POST['agent_username'] . ')</span>' : '') . ' » ' . (!empty($_REQUEST['id']) ? __('Edit', 'activehelper_livehelp') : __('Add new', 'activehelper_livehelp')) . '
	</h2>
	<form action="admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '&amp;action=' . (!empty($_REQUEST['id']) ? 'edit': 'register') . '" method="post" accept-charset="utf-8" id="activeHelper_liveHelp_form" enctype="multipart/form-data">
		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<div class="inner-sidebar"><div class="meta-box-sortables ui-sortable">
				<div id="submitdiv" class="postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;"><span style="cursor: default;">
						' . (!empty($_REQUEST['id']) ? __('Update', 'activehelper_livehelp') : __('Add new', 'activehelper_livehelp')) . '</span></h3>
					<div class="inside"><div class="submitbox">
						<div id="major-publishing-actions" style="padding: 1ex;">
							<div id="delete-action">
								<a class="submitdelete deletion" href="admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '">' . __('Cancel', 'activehelper_livehelp') . '</a>
							</div>
							<div id="publishing-action">
								<input name="submit" value="' . (!empty($_REQUEST['id']) ? __('Update', 'activehelper_livehelp') : __('Add new', 'activehelper_livehelp')) . '" type="submit" accesskey="p" tabindex="999" class="button-primary">
							</div>
							<div class="clear"></div>
						</div>
						<div class="clear"></div>
					</div></div>
				</div>
			</div></div>
			<div id="post-body"><div id="post-body-content"><div class="meta-box-sortables ui-sortable">
				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">
						' . __('Username', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<input maxlength="255" type="text" id="agent_username" value="' . $_POST['agent_username'] . '" tabindex="' . $tabindex++ . '" size="30" name="agent_username" style="width: 98%">' . (isset($errors['agent_username']) ? '
						<p style="color: #f00;">' . __('Error', 'activehelper_livehelp') . ': <code style="background-color: #FAF0F0;">' . $errors['agent_username'] . '</code></p>' : '') . '
					</div>
				</div>
				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">
						' . __('Password', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<input maxlength="255" type="text" id="agent_password" value="' . $_POST['agent_password'] . '" tabindex="' . $tabindex++ . '" size="30" name="agent_password" style="width: 98%">' . (isset($errors['agent_password']) ? '
						<p style="color: #f00;">' . __('Error', 'activehelper_livehelp') . ': <code style="background-color: #FAF0F0;">' . $errors['agent_password'] . '</code></p>' : '') . '
					</div>
				</div>
				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">
						' . __('Email', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<input maxlength="255" type="text" id="agent_email" value="' . $_POST['agent_email'] . '" tabindex="' . $tabindex++ . '" size="30" name="agent_email" style="width: 98%">' . (isset($errors['agent_email']) ? '
						<p style="color: #f00;">' . __('Error', 'activehelper_livehelp') . ': <code style="background-color: #FAF0F0;">' . $errors['agent_email'] . '</code></p>' : '') . '
					</div>
				</div>
				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">
						' . __('Information', 'activehelper_livehelp') . '</h3>
					<div class="inside" style="display: none;"><div id="postcustomstuff" style="padding: .6ex 0;">
						<table><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="agent_firstname">' . __('First name', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['agent_firstname'] . '" id="agent_firstname" name="agent_firstname" />' . (isset($errors['agent_firstname']) ? '
							<p style="color: #f00;">' . __('Error', 'activehelper_livehelp') . ': <code style="background-color: #FAF0F0;">' . $errors['agent_firstname'] . '</code></p>' : '') . '
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="agent_lastname">' . __('Last name', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['agent_lastname'] . '" id="agent_lastname" name="agent_lastname" />' . (isset($errors['agent_lastname']) ? '
							<p style="color: #f00;">' . __('Error', 'activehelper_livehelp') . ': <code style="background-color: #FAF0F0;">' . $errors['agent_lastname'] . '</code></p>' : '') . '
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="agent_department">' . __('Department', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['agent_department'] . '" id="agent_department" name="agent_department" />' . (isset($errors['agent_department']) ? '
							<p style="color: #f00;">' . __('Error', 'activehelper_livehelp') . ': <code style="background-color: #FAF0F0;">' . $errors['agent_department'] . '</code></p>' : '') . '
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Status', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input style="float: left;  margin: 0 .5ex 0 0; width: auto;"" tabindex="' . $tabindex++ . '" type="radio" name="agent_status" ' . (!empty($_POST['agent_status']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="display: block; margin: 0 .5ex 0 0; float: left; line-height: 15px;">
								<input style="float: left;  margin: 0 .5ex 0 0; width: auto;"" tabindex="' . $tabindex++ . '" type="radio" name="agent_status" ' . (empty($_POST['agent_status']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Domain privilege', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input style="float: left;  margin: 0 .5ex 0 0; width: auto;"" tabindex="' . $tabindex++ . '" type="radio" name="agent_privilege" ' . (!empty($_POST['agent_privilege']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="display: block; margin: 0 .5ex 0 0; float: left; line-height: 15px;">
								<input style="float: left;  margin: 0 .5ex 0 0; width: auto;"" tabindex="' . $tabindex++ . '" type="radio" name="agent_privilege" ' . (empty($_POST['agent_privilege']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="agent_department">' . __('Status indicator type', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
            
              <p style="margin: 0.5em 1em;">' . __('Use "Domain" for the global status indicator, if you select "Agent" remember that this Live Chat operator only will be available for the tracking module with the agent ID.') . '</p>
							
							<select tabindex="' . $tabindex++ . '"  style="width: 150px;" id="agent_answers" name="agent_answers">
								<option value="1" ' . ( $_POST['agent_answers'] == '1' ? 'selected="selected"' : '' ) . '>' . __( 'Domain', 'activehelper_livehelp' ) . '</option>
								<option value="2" ' . ( $_POST['agent_answers'] == '2' ? 'selected="selected"' : '' ) . '>' . __( 'Agent', 'activehelper_livehelp' ) . '</option>
							</select>

						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Picture', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">' . (!empty($_POST['agent_picture']) ? '
							<div style="float: right; padding: .5ex 1ex .5ex 1ex;">
								<img style="margin: 4px 2px; border: 1px solid #ccc; background: #fff; padding: 2px;" src="' . $activeHelper_liveHelp['agentsUrl'] . '/' . $_REQUEST['id'] . '/' . $_POST['agent_picture'] . '" alt="" />
							</div>' : '') . '
							<input type="file" tabindex="' . $tabindex++ . '" style="width: auto;" size="35" name="agent_picture">
						</td></tr></tbody></table>
					</div></div>
				</div>
				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">' . __('Active domains', 'activehelper_livehelp') . '</h3>
					<div class="inside"><div id="postcustomstuff" style="padding: .6ex 0;">';

	$first = true;
	foreach ($domainsList as $domain)
	{
		echo '
						<table ' . ($first ? '' : 'style="margin-top: 1.5ex;"') . '><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . $domain['domain_name'] . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input style="float: left; margin: 0 .5ex 0 0; width: auto;" tabindex="' . $tabindex++ . '" type="radio" name="agent_domains[' . $domain['ID'] . ']" ' . (!empty($_POST['agent_domains'][$domain['ID']]) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="float: left; display: block; margin: 0 .5ex 0 0; width: auto; line-height: 15px;">
								<input style="float: left;  margin: 0 .5ex 0 0; width: auto;" tabindex="' . $tabindex++ . '" type="radio" name="agent_domains[' . $domain['ID'] . ']" ' . (empty($_POST['agent_domains'][$domain['ID']]) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>';

		$first = false;
	}

	echo '
					</div></div>
				</div>
			</div></div></div>
			<br />
		</div>';

	if (!empty($_REQUEST['id']))
		echo '
		<input type="hidden" name="id" value="' . $_REQUEST['id'] . '" />';

	echo '
	</form>
	<script type="text/javascript">
		jQuery(document).ready(function($){
			$(".meta-box-sortables .postbox").each(function(){
				var postbox = $(this);
				$("h3", postbox).click(function(){
					$("div.inside", postbox).toggle();
				});
				$("div.handlediv", postbox).click(function(){
					$("div.inside", postbox).toggle();
				});
			});
		});
	</script>
</div>';
}

function activeHelper_liveHelp_agentsInfo()
{
	global $wpdb, $activeHelper_liveHelp;

	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

	$agent = $wpdb->get_row("
		SELECT username AS agent_username, firstname AS agent_firstname, lastname AS agent_lastname,
			email AS agent_email, department AS agent_department, privilege AS agent_privilege, 
			status AS agent_status, photo AS agent_picture
		FROM {$wpdb->prefix}livehelp_users
		WHERE id = '{$_REQUEST['id']}'
		LIMIT 1
	", ARRAY_A);
	$_POST = array_merge($_POST, $agent);

	$tabindex = 1;

	$parseUrl = parse_url(get_bloginfo('url'));
	$clientInfo_server = $parseUrl['scheme'] . '://' . $parseUrl['host'];
	$clientInfo_serverPath = str_replace($clientInfo_server, '', $activeHelper_liveHelp['serverUrl']) . '/';
	$clientInfo_login = $agent['agent_username'];
	$clientInfo_ssl = $parseUrl['scheme'] == 'http' ? 'OFF' : 'ON';

	echo '
<div class="wrap">
	<div id="icon-edit" class="icon32 icon32-posts-post"><br /></div>
	<h2>
		LiveHelp » ' . __('Agents', 'activehelper_livehelp') . (!empty($_REQUEST['id']) ? ' <span style="font-size: 70%;">(' . $_POST['agent_username'] . ')</span>' : '') . ' » ' . __('Client info', 'activehelper_livehelp') . '
	</h2>
	<form action="admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '&amp;action=info" method="post" accept-charset="utf-8" id="activeHelper_liveHelp_form" enctype="multipart/form-data">
		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<div class="inner-sidebar"><div class="meta-box-sortables ui-sortable">
				<div id="submitdiv" class="postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;"><span style="cursor: default;">
						' . __('Client info', 'activehelper_livehelp') . '</span></h3>
					<div class="inside"><div class="submitbox">
						<div id="major-publishing-actions" style="padding: 1ex;">
							<div id="delete-action">
								<a class="submitdelete deletion" href="admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '">' . __('Close', 'activehelper_livehelp') . '</a>
							</div>
							<div class="clear"></div>
						</div>
						<div class="clear"></div>
					</div></div>
				</div>
			</div></div>
			<div id="post-body"><div id="post-body-content"><div class="meta-box-sortables ui-sortable">
				<div class="stuffbox postbox"><div id="postcustomstuff" style="padding: .6ex 0;">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">
						' . __('Client info', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<table><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Server', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $clientInfo_server . '" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Server Path', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $clientInfo_serverPath . '" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Account', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="default" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Login', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $clientInfo_login . '" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('SSL', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $clientInfo_ssl . '" />
						</td></tr></tbody></table>
					</div>
				</div></div>    
			</div></div></div>
            
            <div id="post-body"><div id="post-body-content"><div class="meta-box-sortables ui-sortable">
				<div class="stuffbox postbox"><div id="postcustomstuff" style="padding: .6ex 0;">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">
						' . __('Downloads', 'activehelper_livehelp') . '</h3>
					<div class="inside">
					<table><tbody><tr><td class="first t">
								' . __('Support Panel Desktop for Windows : ', 'activehelper_livehelp') . '
							</td><td class="b">
								' . __('<a target="_blank" href="http://www.activehelper.com/downloads/client/windows/installer-supportpanel.exe">Download</a>', 'activehelper_livehelp') . '
							</td>
                            </td><td class="b">
								' . __('<a target="_blank" href="http://www.slideshare.net/activehelper/support-panel-console-3-user-guide">User Guide</a>', 'activehelper_livehelp') . '
							</td></tr></tbody></table>
                            <table><tbody><tr><td class="first t">
								' . __('Support Panel Desktop for MAC   : ', 'activehelper_livehelp') . '
							</td><td class="b">
								' . __('<a target="_blank" href="http://www.activehelper.com/downloads/client/mac/installer-supportpanel.dmg">Download</a>', 'activehelper_livehelp') . '
							</td>
                            </td><td class="b">
								' . __('<a target="_blank" href="http://www.slideshare.net/activehelper/support-panel-console-3-user-guide">User Guide</a>', 'activehelper_livehelp') . '
							</td></tr></tbody></table>
                              <table><tbody><tr><td class="first t">
								' . __('Support Panel Mobile for IOS   : ', 'activehelper_livehelp') . '
							</td><td class="b">
								' . __('<a target="_blank" href="https://itunes.apple.com/us/app/live-help/id515929709?mt=8">Download</a>', 'activehelper_livehelp') . '
							</td>
                            </td><td class="b">
								' . __('<a target="_blank" href="http://www.slideshare.net/activehelper/support-panel-mobile-user-guide-for-iphone-and-ipad-english">User Guide</a>', 'activehelper_livehelp') . '
							</td>
                            </tr></tbody></table>
                              <table><tbody><tr><td class="first t">
								' . __('Support Panel mobile for Android : ', 'activehelper_livehelp') . '
							</td><td class="b">
								' . __('<a target="_blank" href="https://play.google.com/store/apps/details?id=air.com.activehelper.supportpanel">Download</a>', 'activehelper_livehelp') . '
							</td></td><td class="b">
								' . __('<a target="_blank" href="http://www.slideshare.net/activehelper/support-panel-mobile-user-guide-for-android-english">User Guide</a>', 'activehelper_livehelp') . '
							</td></tr></tbody></table>
					</div>
				</div></div>
			</div></div></div>
            
			<br />
		</div>';

	if (!empty($_REQUEST['id']))
		echo '
		<input type="hidden" name="id" value="' . $_REQUEST['id'] . '" />';

	echo '
	</form>
	<script type="text/javascript">
		jQuery(document).ready(function($){
			$(".meta-box-sortables .postbox").each(function(){
				var postbox = $(this);
				$("h3", postbox).click(function(){
					$("div.inside", postbox).toggle();
				});
				$("div.handlediv", postbox).click(function(){
					$("div.inside", postbox).toggle();
				});
			});
		});
	</script>
</div>';
}

function activeHelper_liveHelp_agentsSettingsPost() {
	global $wpdb, $activeHelper_liveHelp;

	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
	$_REQUEST['lang'] = !empty($_REQUEST['lang']) ? (string) $_REQUEST['lang'] : 'en';

	$activeHelper_liveHelp['agent'] = $wpdb->get_row("
		SELECT username AS agent_username, firstname AS agent_firstname, lastname AS agent_lastname,
			email AS agent_email, department AS agent_department, privilege AS agent_privilege, 
			status AS agent_status, photo AS agent_picture, answers AS agent_answers
		FROM {$wpdb->prefix}livehelp_users
		WHERE id = '{$_REQUEST['id']}'
		LIMIT 1
	", ARRAY_A);

	$agent_dir = $activeHelper_liveHelp['agentsDir'] . '/' . $_REQUEST['id'] . '/i18n/' . $_REQUEST['lang'];
     

 if (isset($_POST['submit']))
	{
      include($activeHelper_liveHelp['importDir'] . '/constants.php');            
    }
    
	while (!empty($_FILES['online']['tmp_name'])) {
		activeHelper_liveHelp_imagesDelete($agent_dir, 'online.' .$status_indicator_img_type);

		activeHelper_liveHelp_imagesUpload($agent_dir, 'online', $_FILES['online'], '.' . $status_indicator_img_type);
		unset($_FILES['online']);
	}

	while (!empty($_FILES['offline']['tmp_name'])) {
		activeHelper_liveHelp_imagesDelete($agent_dir, 'offline.' .$status_indicator_img_type );

		activeHelper_liveHelp_imagesUpload($agent_dir, 'offline', $_FILES['offline'], '.' . $status_indicator_img_type);
		unset($_FILES['offline']);
	}
	
	while (!empty($_FILES['away']['tmp_name'])) {
		activeHelper_liveHelp_imagesDelete($agent_dir, 'away.' .$status_indicator_img_type );

		activeHelper_liveHelp_imagesUpload($agent_dir, 'away', $_FILES['away'], '.' . $status_indicator_img_type);
		unset($_FILES['away']);
	}

	while (!empty($_FILES['brb']['tmp_name'])) {
		activeHelper_liveHelp_imagesDelete($agent_dir, 'brb.' .$status_indicator_img_type);

		activeHelper_liveHelp_imagesUpload($agent_dir, 'brb', $_FILES['brb'], '.' . $status_indicator_img_type);
		unset($_FILES['brb']);
	}
        
   // update Time_schedule 
   
   	while (!empty($_POST['int_time']['end_time'])) {
    	$wpdb->query("
			UPDATE {$wpdb->prefix}livehelp_users
			SET schedule     = '{$_POST['schedule']}',
                initial_time = '{$_POST['int_time']}',
                final_time   = '{$_POST['end_time']}'                
	      WHERE id           = '{$_REQUEST['id']}'
		");
        
      unset($_POST['int_time']);               
  }
  
     
}

function activeHelper_liveHelp_agentsSettings()
{
	global $wpdb, $activeHelper_liveHelp;
    
          // status indicator file type
          
 if (!isset($_POST['submit']))
	{
      include($activeHelper_liveHelp['importDir'] . '/constants.php');
          
    $f_online   = "online." . $status_indicator_img_type;
    $f_offline  = "offline." . $status_indicator_img_type;
    $f_away     = "away." . $status_indicator_img_type;
    $f_brb      = "brb." . $status_indicator_img_type;    
    }
    
	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
	$_REQUEST['lang'] = !empty($_REQUEST['lang']) ? (string) $_REQUEST['lang'] : 'en';

	$agent_url = $activeHelper_liveHelp['serverUrl'] . '/agents/' . $_REQUEST['id'] . '/i18n/' . $_REQUEST['lang'] . '/';

	$agent_dir = $activeHelper_liveHelp['agentsDir'] . '/' . $_REQUEST['id'];
	$agent_imgs_paths = array_filter(glob($agent_dir . '/i18n/' . $_REQUEST['lang'] . '/*'), 'is_file');
	$agent_imgs = array();
	foreach ($agent_imgs_paths as $path) {
		$agent_imgs[basename($path, '.' .$status_indicator_img_type)] = basename($path, '.' .$status_indicator_img_type);
	}

	$tabindex = 1;
    
    

	$agent_schedule = $wpdb->get_row("
		SELECT schedule, initial_time as int_time, final_time as end_time
		FROM {$wpdb->prefix}livehelp_users
		WHERE id = '{$_REQUEST['id']}'
		LIMIT 1
	", ARRAY_A);
        
	echo '
<div class="wrap">
	<div id="icon-edit" class="icon32 icon32-posts-post"><br /></div>
	<h2>
		LiveHelp » ' . __('Agents', 'activehelper_livehelp') . (!empty($_REQUEST['id']) ? ' <span style="font-size: 70%;">(' . $activeHelper_liveHelp['agent']['agent_username'] . ')</span>' : '') . ' » ' . __('Settings', 'activehelper_livehelp') . '
	</h2>
	<form action="admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '&amp;action=settings" method="post" accept-charset="utf-8" id="activeHelper_liveHelp_form" enctype="multipart/form-data">
		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<div class="inner-sidebar"><div class="meta-box-sortables ui-sortable">
				<div id="submitdiv" class="postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;"><span style="cursor: default;">
						' . __('Settings', 'activehelper_livehelp') . '</span></h3>
					<div class="inside"><div class="submitbox">
						<div id="major-publishing-actions" style="padding: 1ex;">
							<div id="delete-action">
								<a class="submitdelete deletion" href="admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '">' . __('Close', 'activehelper_livehelp') . '</a>
							</div>
							<div id="publishing-action">
								<input name="submit" value="' . __('Update', 'activehelper_livehelp') . '" type="submit" accesskey="p" tabindex="999" class="button-primary">
							</div>
							<div class="clear"></div>
						</div>
						<div class="clear"></div>
					</div></div>
				</div>
			</div></div>
			<div id="post-body"><div id="post-body-content"><div class="meta-box-sortables ui-sortable">
				<div class="stuffbox postbox"><div id="postcustomstuff" style="padding: .6ex 0;">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">
						' . __('Settings', 'activehelper_livehelp') . '</h3>
					<div class="inside">


						<table><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Language', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<select size="1" id="agent_settings_language" style="width: 200px;" name="lang" tabindex="' . $tabindex++ . '">';

	$__text = array(
		'en' => __('English', 'activehelper_livehelp'),
		'sp' => __('Spanish', 'activehelper_livehelp'),
		'de' => __('Deutsch', 'activehelper_livehelp'),
		'pt' => __('Portuguese', 'activehelper_livehelp'),
		'it' => __('Italian', 'activehelper_livehelp'),
		'fr' => __('French', 'activehelper_livehelp'),
		'cz' => __('Czech', 'activehelper_livehelp'),
		'se' => __('Swedish', 'activehelper_livehelp'),
		'no' => __('Norwegian', 'activehelper_livehelp'),
		'tr' => __('Turkey', 'activehelper_livehelp'),
		'gr' => __('Greek', 'activehelper_livehelp'),
		'he' => __('Hebrew', 'activehelper_livehelp'),
		'fa' => __('Farsi', 'activehelper_livehelp'),
		'sr' => __('Serbian', 'activehelper_livehelp'),
		'ru' => __('Rusian', 'activehelper_livehelp'),
		'hu' => __('Hungarian', 'activehelper_livehelp'),
		'zh' => __('Traditional Chinese', 'activehelper_livehelp'),
		'ar' => __('Arab', 'activehelper_livehelp'),
		'nl' => __('Dutch', 'activehelper_livehelp'),
		'fi' => __('Finnish', 'activehelper_livehelp'),
		'dk' => __('Danish', 'activehelper_livehelp'),
		'pl' => __('Polish', 'activehelper_livehelp'),
		'cn' => __('Simplified Chinese', 'activehelper_livehelp'),
        'bg' => __('Bulgarian', 'activehelper_livehelp'),
        'sk' => __('Slovak', 'activehelper_livehelp'),
        'cr' => __('Croatian', 'activehelper_livehelp'),
        'id' => __('Indonesian', 'activehelper_livehelp'),
        'lt' => __('Lithuanian', 'activehelper_livehelp'),
        'ro' => __('Romanian', 'activehelper_livehelp'),
        'sl' => __('Slovenian', 'activehelper_livehelp'),
        'et' => __('Estonian', 'activehelper_livehelp'),
        'lv' => __('Latvian', 'activehelper_livehelp'),
        'ge' => __('Georgian', 'activehelper_livehelp'),        
	);
        


    
	foreach ($__text as $code => $language)
		echo '
								<option value="' . $code . '" ' . ($_REQUEST['lang'] == $code ? 'selected="selected"' : '') . '>
									' . $language . '</option>';

	echo '
							</select>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

                             <table><tbody><tr><td class="first t">
								' . __('', 'activehelper_livehelp') . '
							</td><td class="b">
								' . __('<a target="_blank" href="http://www.activehelper.com/Icons/icon-store.html#free">	Get more chat buttons here</a>', 'activehelper_livehelp') . '
							</td></tr></tbody>
						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Online image (' .$status_indicator_img_type .')', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">' . (!empty($agent_imgs['online']) ? '
							<div style="float: right; padding: .5ex 1ex .5ex 1ex;">
								<img style="margin: 4px 2px; border: 1px solid #ccc; background: #fff; padding: 2px;" src="' . $agent_url .$f_online .'" alt="" />
							</div>' : '') . '
							<input type="file" tabindex="' . $tabindex++ . '" style="width: auto;" size="35" name="online">
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Offline image (' . $status_indicator_img_type .')', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">' . (!empty($agent_imgs['offline']) ? '
							<div style="float: right; padding: .5ex 1ex .5ex 1ex;">
								<img style="margin: 4px 2px; border: 1px solid #ccc; background: #fff; padding: 2px;" src="' . $agent_url . $f_offline .'" alt="" />
							</div>' : '') . '
							<input type="file" tabindex="' . $tabindex++ . '" style="width: auto;" size="35" name="offline">
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Away image ('.$status_indicator_img_type.')', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">' . (!empty($agent_imgs['away']) ? '
							<div style="float: right; padding: .5ex 1ex .5ex 1ex;">
								<img style="margin: 4px 2px; border: 1px solid #ccc; background: #fff; padding: 2px;" src="' . $agent_url . $f_away .'" alt="" />
							</div>' : '') . '
							<input type="file" tabindex="' . $tabindex++ . '" style="width: auto;" size="35" name="away">
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('BRB image ('.$status_indicator_img_type.')', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">' . (!empty($agent_imgs['brb']) ? '
							<div style="float: right; padding: .5ex 1ex .5ex 1ex;">
								<img style="margin: 4px 2px; border: 1px solid #ccc; background: #fff; padding: 2px;" src="' . $agent_url . $f_brb .'" alt="" />
							</div>' : '') . '
							<input type="file" tabindex="' . $tabindex++ . '" style="width: auto;" size="35" name="brb">
						</td></tr></tbody></table>

					</div>
				</div></div>
			</div></div></div>
           
            <div id="post-body"><div id="post-body-content"><div class="meta-box-sortables ui-sortable">
				<div class="stuffbox postbox"><div id="postcustomstuff" style="padding: .6ex 0;">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">
						' . __('Time Schedule', 'activehelper_livehelp') . '</h3>
					<div class="inside">
                    
                    	<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Allow to sign in only for an specific time', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input style="float: left;  margin: 0 .5ex 0 0; width: auto;"" tabindex="' . $tabindex++ . '" type="radio" name="schedule" ' . (!empty($agent_schedule['schedule']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="display: block; margin: 0 .5ex 0 0; float: left; line-height: 15px;">
								<input style="float: left;  margin: 0 .5ex 0 0; width: auto;"" tabindex="' . $tabindex++ . '" type="radio" name="schedule" ' . (empty($agent_schedule['schedule']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

                  	<table><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="int_time">' . __('Inital Time ( 24H 00:00:00 )', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $agent_schedule['int_time'] . '" id="int_time" name="int_time" />' . (isset($errors['int_time']) ? '
							<p style="color: #f00;">' . __('Error', 'activehelper_livehelp') . ': <code style="background-color: #FAF0F0;">' . $errors['int_time'] . '</code></p>' : '') . '
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="end_time">' . __('End Time ( 24H 23:59:59)', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $agent_schedule['end_time'] . '" id="end_time" name="end_time" />' . (isset($errors['end_time']) ? '
							<p style="color: #f00;">' . __('Error', 'activehelper_livehelp') . ': <code style="background-color: #FAF0F0;">' . $errors['end_time'] . '</code></p>' : '') . '
						</td></tr></tbody></table>                                                                     
	       </div></div></div>
			<br />
		</div>';

	if (!empty($_REQUEST['id']))
		echo '
		<input type="hidden" name="id" value="' . $_REQUEST['id'] . '" />
		<input type="hidden" name="lang" value="' . $_REQUEST['lang'] . '" />';

	echo '
	</form>
	<script type="text/javascript">
		jQuery(document).ready(function($){
      $("#agent_settings_language").change(function(){
        window.location = window.location.href + "&lang=" +  $("#agent_settings_language").val();
      });
    
			$(".meta-box-sortables .postbox").each(function(){
				var postbox = $(this);
				$("h3", postbox).click(function(){
					$("div.inside", postbox).toggle();
				});
				$("div.handlediv", postbox).click(function(){
					$("div.inside", postbox).toggle();
				});
			});
		});
	</script>
</div>';

 if (isset($_POST['submit']))
	{
      wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_agents'));
		exit;
     }
}

