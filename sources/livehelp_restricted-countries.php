<?php
/**
 * @package ActiveHelper Live Help
 */

if (!defined('ACTIVEHELPER_LIVEHELP'))
	die('Hi there! I\'m just a plugin, not much I can do when called directly.');

function activeHelper_liveHelp_restrictedCountries()
{
	global $wpdb, $activeHelper_liveHelp;

	$actions = array(
		'list' => 'activeHelper_liveHelp_restrictedCountriesList',
		'register' => 'activeHelper_liveHelp_restrictedCountriesRegister',
	);
	if (!empty($_REQUEST['action']) && isset($actions[$_REQUEST['action']]))
		return $actions[$_REQUEST['action']]();

	return $actions['list']();
}

function activeHelper_liveHelp_restrictedCountriesPost()
{
	$actions = array(
		'delete' => 'activeHelper_liveHelp_restrictedCountriesDeletePost',
		'register' => 'activeHelper_liveHelp_restrictedCountriesRegisterPost'
	);

	if (!empty($_REQUEST['action']) && isset($actions[$_REQUEST['action']]))
		return $actions[$_REQUEST['action']]();
}

function activeHelper_liveHelp_restrictedCountriesDeletePost()
{
	global $wpdb, $activeHelper_liveHelp;

	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

	if (empty($_REQUEST['id']))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_restrictedCountries') . '&miss');
		exit;
	}

	$wpdb->query("
		DELETE FROM  {$wpdb->prefix}livehelp_not_allowed_countries
		WHERE id = {$_REQUEST['id']}
		LIMIT 1
	");

	wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_restrictedCountries') . '&delete');
	exit;
}

function activeHelper_liveHelp_restrictedCountriesList()
{
	global $wpdb, $activeHelper_liveHelp;

	$restrictionsList = $wpdb->get_results("
		SELECT jlnac.id AS restriction_id, jld.id_domain AS domain_id, jld.name AS domain_name, jlc.name AS country
		FROM  {$wpdb->prefix}livehelp_not_allowed_countries AS jlnac, {$wpdb->prefix}livehelp_countries AS jlc, {$wpdb->prefix}livehelp_domains AS jld
		WHERE jlnac.id_domain = jld.id_domain AND jlnac.code = jlc.code
		GROUP BY 1 DESC
		ORDER BY jlnac.id
	", ARRAY_A);

	echo '
<div class="wrap">
	<h2 style="padding-right: 0;">
		LiveHelp » ' . __('Not allowed countries', 'activehelper_livehelp') . '
		<a class="button add-new-h2" href="admin.php?page=' . strtolower('activeHelper_liveHelp_restrictedCountries') . '&amp;action=register">' . __('add new', 'activehelper_livehelp') . '</a>
	</h2>';

	if (isset($_GET['register']))
		echo '
	<div class="updated below-h2" id="message">
		<p>' . sprintf(__('The %s was successfully registered.', 'activehelper_livehelp'), __('restriction', 'activehelper_livehelp')) . '</p>
	</div>';

	if (isset($_GET['delete']))
		echo '
	<div class="error below-h2" id="message">
		<p>' . sprintf(__('The %s was deleted permanently.', 'activehelper_livehelp'), __('restriction', 'activehelper_livehelp')) . '</p>
	</div>';

	if (isset($_GET['miss']))
		echo '
	<div class="error below-h2" id="message">
		<p>' . sprintf(__('The %s was not found.', 'activehelper_livehelp'), __('restriction', 'activehelper_livehelp')) . '</p>
	</div>';

	echo '
	<div class="metabox-holder" style="padding-bottom: 10px;">
		<table cellspacing="0" class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th class="manage-column" scope="col">
						' . __('Restriction ID', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Domain ID', 'activehelper_livehelp') . '</th>
					<th  class="manage-column" scope="col">
						' . __('Domain name', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Country', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Delete', 'activehelper_livehelp') . '</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th class="manage-column" scope="col">
						' . __('Restriction ID', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Domain ID', 'activehelper_livehelp') . '</th>
					<th  class="manage-column" scope="col">
						' . __('Domain name', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Country', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Delete', 'activehelper_livehelp') . '</th>
				</tr>
			</tfoot>
			<tbody id="the-list">';

			if (empty($restrictionsList))
				echo '
				<tr valign="top" class="format-default">
					<td class="colspanchange" colspan="5"><p style="margin: 0; padding: .8ex; color: #888;">
						' . sprintf(__('No %s found.', 'activehelper_livehelp'), __('restrictions', 'activehelper_livehelp')) . '
					</p></td>
				</tr>';
			else
			{
				$alternate = false;
				foreach ($restrictionsList as $restriction)
				{
					echo '
				<tr valign="top" class="' . ($alternate ? 'alternate' : '') . ' format-default">
					<td>
						' . $restriction['restriction_id'] . '
					</td>
					<td>
						' . $restriction['domain_id'] . '
					</td>
					<td>
						' . $restriction['domain_name'] . '
					</td>
					<td>
						' . $restriction['country'] . '
					</td>
					<td>
						<a href="admin.php?page=' . strtolower('activeHelper_liveHelp_restrictedCountries') . '&amp;action=delete&amp;id=' . $restriction['restriction_id'] . '" class="submitdelete" onclick="return window.confirm(\'' . __('Are you sure you want to delete this item permanently?', 'activehelper_livehelp') . '\');">
							' . __('Delete', 'activehelper_livehelp') . '</a>
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

function activeHelper_liveHelp_restrictedCountriesRegisterPost()
{
	global $wpdb, $activeHelper_liveHelp;

	$_POST['domain'] = !empty($_POST['domain']) ? (int) $_POST['domain'] : '';
	$_POST['country'] = !empty($_POST['country']) ? (string) $_POST['country'] : '';

	$errors = array();
	$activeHelper_liveHelp['errors'] = &$errors;

	while (isset($_POST['submit']))
	{
		unset($_POST['submit']);

		// errors ...
		if (!empty($errors))
			break;

		$wpdb->query("
			INSERT INTO {$wpdb->prefix}livehelp_not_allowed_countries
				( id_domain, code )
			VALUES
				( '{$_POST['domain']}', '{$_POST['country']}' )
		");

		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_restrictedCountries') . '&register');
		exit;
	}
}

function activeHelper_liveHelp_restrictedCountriesRegister()
{
	global $wpdb, $activeHelper_liveHelp;

	if (!empty($activeHelper_liveHelp['errors'] ) )
		$errors = $activeHelper_liveHelp['errors'];

	$domainsList = $wpdb->get_results("
		SELECT id_domain, name
		FROM {$wpdb->prefix}livehelp_domains
		ORDER BY id_domain
	", ARRAY_A);
	$countriesList = $wpdb->get_results("
		SELECT code, name
		FROM {$wpdb->prefix}livehelp_countries
		ORDER BY name
	", ARRAY_A);

	$tabindex = 1;

	echo '
<div class="wrap">
	<div id="icon-edit" class="icon32 icon32-posts-post"><br /></div>
	<h2>
		LiveHelp » ' . __('Restrictions', 'activehelper_livehelp') . ' » ' . __('Add new', 'activehelper_livehelp') . '
	</h2>
	<form action="admin.php?page=' . strtolower('activeHelper_liveHelp_restrictedCountries') . '&amp;action=' . 'register' . '" method="post" accept-charset="utf-8" id="activeHelper_liveHelp_form" enctype="multipart/form-data">
		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<div class="inner-sidebar"><div class="meta-box-sortables ui-sortable">
				<div id="submitdiv" class="postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;"><span style="cursor: default;">
						' . __('Add new', 'activehelper_livehelp') . '</span></h3>
					<div class="inside"><div class="submitbox">
						<div id="major-publishing-actions" style="padding: 1ex;">
							<div id="delete-action">
								<a class="submitdelete deletion" href="admin.php?page=' . strtolower('activeHelper_liveHelp_restrictedCountries') . '">' . __('Cancel', 'activehelper_livehelp') . '</a>
							</div>
							<div id="publishing-action">
								<input name="submit" value="' . __( 'Add new', 'activehelper_livehelp' )  . '" type="submit" accesskey="p" tabindex="999" class="button-primary">
							</div>
							<div class="clear"></div>
						</div>
						<div class="clear"></div>
					</div></div>
				</div>
			</div></div>
			<div id="post-body"><div id="post-body-content"><div class="meta-box-sortables ui-sortable">
				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __( 'Click to toggle', 'activehelper_livehelp' ) . '"><br /></div>
					<h3 style="cursor: default;">
						' . __('Domain', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<select name="domain" style="width: 280px;">';
						
						foreach ( $domainsList as $domain ) {
							echo '
							<option value="' . $domain['id_domain'] . '">' . $domain['name'] . '</option>';
						}
						
					echo '
						</select>
					</div>
				</div>
				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">
						' . __('Country', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<select name="country" style="width: 350px;">';
						
						foreach ( $countriesList as $country ) {
							echo '
							<option value="' . $country['code'] . '">' . $country['name'] . '</option>';
						}
						
					echo '
						</select>
					</div>
				</div>
			</div></div></div>
			<br />
		</div>';

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

