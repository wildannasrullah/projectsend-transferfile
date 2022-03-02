<?php
/**
 * Options page and form.
 *
 * @package ProjectSend
 * @subpackage Options
 */
$load_scripts	= array(
						'jquery_tags_input',
						'spinedit',
					);

$allowed_levels = array(9);
require_once('sys.includes.php');

$section = ( !empty( $_GET['section'] ) ) ? $_GET['section'] : $_POST['section'];

switch ( $section ) {
	case 'general':
		$section_title	= __('General options','cftp_admin');
		$checkboxes		= array(
								'footer_custom_enable',
								'files_descriptions_use_ckeditor',
								'use_browser_lang',
							);
		break;
	case 'clients':
		$section_title	= __('Clients','cftp_admin');
		$checkboxes		= array(
								'clients_can_register',
								'clients_auto_approve',
								'clients_can_upload',
								'clients_can_delete_own_files',
								'clients_can_set_expiration_date',
							);
		break;
	case 'privacy':
		$section_title	= __('Privacy','cftp_admin');
		$checkboxes		= array(
								'privacy_noindex_site',
								'enable_landing_for_all_files',
								'public_listing_page_enable',
								'public_listing_logged_only',
								'public_listing_show_all_files',
								'public_listing_use_download_link',
							);
		break;
	case 'email':
		$section_title	= __('E-mail notifications','cftp_admin');
		$checkboxes		= array(
								'mail_copy_user_upload',
								'mail_copy_client_upload',
								'mail_copy_main_user',
							);
		break;
	case 'security':
		$section_title	= __('Security','cftp_admin');
		$checkboxes		= array(
								'pass_require_upper',
								'pass_require_lower',
								'pass_require_number',
								'pass_require_special',
								'recaptcha_enabled',
							);
		break;
	case 'thumbnails':
		$section_title	= __('Thumbnails','cftp_admin');
		$checkboxes		= array(
								'thumbnails_use_absolute',
							);
		break;
	case 'branding':
		$section_title	= __('Branding','cftp_admin');
		$checkboxes		= array(
							);
		break;
	case 'social_login':
		$section_title	= __('Social Login','cftp_admin');
		$checkboxes		= array(
							);
		break;
	default:
		$location = BASE_URI . 'options.php?section=general';
		header("Location: $location");
		die();
		break;
}

//$page_title = __('System options','cftp_admin');
$page_title = $section_title;

$active_nav = 'options';
include('header.php');

$logo_file_info = generate_logo_url();

/** Form sent */
if ($_POST) {
	/**
	 * Escape all the posted values on a single function.
	 * Defined on functions.php
	 */
	/** Values that can be empty */
	$allowed_empty_values	= array(
                                'footer_custom_content',
								'mail_copy_addresses',
								'mail_smtp_host',
								'mail_smtp_port',
								'mail_smtp_user',
								'mail_smtp_pass',
							);

	if ( empty( $_POST['google_signin_enabled'] ) ) {
		$allowed_empty_values[] = 'google_client_id';
		$allowed_empty_values[] = 'google_client_secret';
	}
	if ( empty( $_POST['recaptcha_enabled'] ) ) {
		$allowed_empty_values[] = 'recaptcha_site_key';
		$allowed_empty_values[] = 'recaptcha_secret_key';
	}

	foreach ($checkboxes as $checkbox) {
		$_POST[$checkbox] = (empty($_POST[$checkbox]) || !isset($_POST[$checkbox])) ? 0 : 1;
	}

	$keys = array_keys($_POST);

	$options_total = count($keys);
	$options_filled = 0;
	$query_state = '0';

	/**
	 * Check if all the options are filled.
	 */
	for ($i = 0; $i < $options_total; $i++) {
		if (!in_array($keys[$i], $allowed_empty_values)) {
			if (empty($_POST[$keys[$i]]) && $_POST[$keys[$i]] != '0') {
				$query_state = '3';
			}
			else {
				$options_filled++;
			}
		}
	}

	/** If every option is completed, continue */
	if ($query_state == '0') {
		$updated = 0;
		for ($j = 0; $j < $options_total; $j++) {
			$save = $dbh->prepare( "UPDATE " . TABLE_OPTIONS . " SET value=:value WHERE name=:name" );
			$save->bindParam(':value', $_POST[$keys[$j]]);
			$save->bindParam(':name', $keys[$j]);
			$save->execute();

			if ($save) {
				$updated++;
			}
		}
		if ($updated > 0){
			$query_state = '1';
		}
		else {
			$query_state = '2';
		}
	}

	/** If uploading a logo on the branding page */
	$file_logo = $_FILES['select_logo'];
	if ( !empty( $file_logo ) ) {
		$logo = option_file_upload( $file_logo, 'image', 'logo_filename', 29 );
		$file_status = $logo['status'];
	}

	/** Redirect so the options are reflected immediatly */
	while (ob_get_level()) ob_end_clean();
	$section_redirect = html_output($_POST['section']);
	$location = BASE_URI . 'options.php?section=' . $section_redirect;

	if ( !empty( $query_state ) ) {
		$location .= '&status=' . $query_state;
	}

	if ( !empty( $file_status ) ) {
		$location .= '&file_status=' . $file_status;
	}
	header("Location: $location");
	die();
}

/**
 * Replace | with , to use the tags system when showing
 * the allowed filetypes on the form. This value comes from
 * site.options.php
*/
$allowed_file_types = str_replace('|',',',$allowed_file_types);
/** Explode, sort, and implode the values to list them alphabetically */
$allowed_file_types = explode(',',$allowed_file_types);
sort($allowed_file_types);

/** If .php files are allowed, set the flag for the warning message */
if ( in_array( 'php', $allowed_file_types ) ) {
	$php_allowed_warning = true;
}

$allowed_file_types = implode(',',$allowed_file_types);

?>

<div class="col-xs-12 col-sm-12 col-lg-6">
	<?php
		if (isset($_GET['status'])) {
			switch ($_GET['status']) {
				case '1':
					$msg = __('Options updated succesfuly.','cftp_admin');
					echo system_message('ok',$msg);
					break;
				case '2':
					$msg = __('There was an error. Please try again.','cftp_admin');
					echo system_message('error',$msg);
					break;
				case '3':
					$msg = __('Some fields were not completed. Options could not be saved.','cftp_admin');
					echo system_message('error',$msg);
					$show_options_form = 1;
					break;
			}
		}

		/** Logo uploading status */
		if (isset($_GET['file_status'])) {
			switch ($_GET['file_status']) {
				case '1':
					break;
				case '2':
					$msg = __('The file could not be moved to the corresponding folder.','cftp_admin');
					$msg .= __("This is most likely a permissions issue. If that's the case, it can be corrected via FTP by setting the chmod value of the",'cftp_admin');
					$msg .= ' '.LOGO_FOLDER.' ';
					$msg .= __('directory to 755, or 777 as a last resource.','cftp_admin');
					$msg .= __("If this doesn't solve the issue, try giving the same values to the directories above that one until it works.",'cftp_admin');
					echo system_message('error',$msg);
					break;
				case '3':
					$msg = __('The file you selected is not an allowed format.','cftp_admin');
					echo system_message('error',$msg);
					break;
				case '4':
					$msg = __('There was an error uploading the file. Please try again.','cftp_admin');
					echo system_message('error',$msg);
					break;
			}
		}
	?>

	<div class="white-box">
		<div class="white-box-interior">

			<script type="text/javascript">
				$(document).ready(function() {
					$('#notifications_max_tries').spinedit({
						minimum: 1,
						maximum: 100,
						step: 1,
						value: <?php echo NOTIFICATIONS_MAX_TRIES; ?>,
						numberOfDecimals: 0
					});

					$('#notifications_max_days').spinedit({
						minimum: 0,
						maximum: 365,
						step: 1,
						value: <?php echo NOTIFICATIONS_MAX_DAYS; ?>,
						numberOfDecimals: 0
					});

					$('#allowed_file_types').tagsInput({
						'width'			: '95%',
						'height'		: 'auto',
						'defaultText'	: '',
					});

					$("form").submit(function() {
						clean_form(this);

						is_complete_all_options(this,'<?php _e('Please complete all the fields.','cftp_admin'); ?>');

						// show the errors or continue if everything is ok
						if (show_form_errors() == false) { alert('<?php _e('Please complete all the fields.','cftp_admin'); ?>'); return false; }
					});
				});
			</script>

			<form action="options.php" name="optionsform" method="post" enctype="multipart/form-data" class="form-horizontal">
				<input type="hidden" name="section" value="<?php echo $section; ?>">

					<?php
						switch ( $section ) {
							case 'general':
					?>
								<h3><?php _e('General','cftp_admin'); ?></h3>
								<p><?php _e('Basic information to be shown around the site. The time format and zones values affect how the clients see the dates on their files lists.','cftp_admin'); ?></p>

								<div class="form-group">
									<label for="this_install_title" class="col-sm-4 control-label"><?php _e('Site name','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<input type="text" name="this_install_title" id="this_install_title" class="form-control" value="<?php echo html_output(THIS_INSTALL_SET_TITLE); ?>" />
									</div>
								</div>

								<div class="form-group">
									<label for="timezone" class="col-sm-4 control-label"><?php _e('Timezone','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<?php
											/**
											 * Generates a select field.
											 * Code is stored on a separate file since it's pretty long.
											 */
											include_once('includes/timezones.php');
										?>
									</div>
								</div>

								<div class="form-group">
									<label for="timeformat" class="col-sm-4 control-label"><?php _e('Time format','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<input type="text" class="form-control" name="timeformat" id="timeformat" value="<?php echo TIMEFORMAT_USE; ?>" />
										<p class="field_note"><?php _e('For example, d/m/Y h:i:s will result in something like','cftp_admin'); ?> <strong><?php echo date('d/m/Y h:i:s'); ?></strong>.
										<?php _e('For the full list of available values, visit','cftp_admin'); ?> <a href="http://php.net/manual/en/function.date.php" target="_blank"><?php _e('this page','cftp_admin'); ?></a>.</p>
									</div>
								</div>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="footer_custom_enable">
											<input type="checkbox" value="1" name="footer_custom_enable" id="footer_custom_enable" class="checkbox_options" <?php echo (FOOTER_CUSTOM_ENABLE == 1) ? 'checked="checked"' : ''; ?> /> <?php _e("Use custom footer text",'cftp_admin'); ?>
										</label>
									</div>
								</div>

								<div class="form-group">
									<label for="footer_custom_content" class="col-sm-4 control-label"><?php _e('Footer content','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<input type="text" name="footer_custom_content" id="footer_custom_content" class="form-control empty" value="<?php echo html_output(FOOTER_CUSTOM_CONTENT); ?>" />
									</div>
								</div>

								<div class="options_divide"></div>

								<h3><?php _e('Editor','cftp_admin'); ?></h3>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="files_descriptions_use_ckeditor">
											<input type="checkbox" value="1" name="files_descriptions_use_ckeditor" id="files_descriptions_use_ckeditor" class="checkbox_options" <?php echo (DESCRIPTIONS_USE_CKEDITOR == 1) ? 'checked="checked"' : ''; ?> /> <?php _e("Use the visual editor on files descriptions",'cftp_admin'); ?>
										</label>
									</div>
								</div>

								<div class="options_divide"></div>

								<h3><?php _e('Language','cftp_admin'); ?></h3>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="use_browser_lang">
											<input type="checkbox" value="1" name="use_browser_lang" id="use_browser_lang" class="checkbox_options" <?php echo (USE_BROWSER_LANG == 1) ? 'checked="checked"' : ''; ?> /> <?php _e("Detect user browser language",'cftp_admin'); ?>
											<p class="field_note"><?php _e("If available, will override the default one from the system configuration file. Affects all users and clients.",'cftp_admin'); ?></p>
										</label>
									</div>
								</div>

								<div class="options_divide"></div>

								<h3><?php _e('System location','cftp_admin'); ?></h3>
								<p class="text-warning"><?php _e('These options are to be changed only if you are moving the system to another place. Changes here can cause ProjectSend to stop working.','cftp_admin'); ?></p>

								<div class="form-group">
									<label for="base_uri" class="col-sm-4 control-label"><?php _e('System URI','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<input type="text" class="form-control" name="base_uri" id="base_uri" value="<?php echo BASE_URI; ?>" />
									</div>
								</div>
					<?php
							break;
							case 'clients':
					?>
								<h3><?php _e('New registrations','cftp_admin'); ?></h3>
								<p><?php _e('Used only on self-registrations. These options will not apply to clients registered by system administrators.','cftp_admin'); ?></p>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="clients_can_register">
											<input type="checkbox" value="1" name="clients_can_register" id="clients_can_register" class="checkbox_options" <?php echo (CLIENTS_CAN_REGISTER == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('Clients can register themselves','cftp_admin'); ?>
										</label>
									</div>
								</div>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="clients_auto_approve">
											<input type="checkbox" value="1" name="clients_auto_approve" id="clients_auto_approve" class="checkbox_options" <?php echo (CLIENTS_AUTO_APPROVE == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('Auto approve new accounts','cftp_admin'); ?>
										</label>
									</div>
								</div>

								<div class="form-group">
									<label for="clients_auto_group" class="col-sm-4 control-label"><?php _e('Add clients to this group:','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<select class="form-control" name="clients_auto_group" id="clients_auto_group">
											<option value="0"><?php _e('None (does not enable this feature)','cftp_admin'); ?></option>
											<?php
												/** Fill the groups array that will be used on the form */
												$get_groups		= new GroupActions;
												$arguments		= array();
												$groups 		= $get_groups->get_groups($arguments);

												foreach ( $groups as $group ) {
											?>
														<option value="<?php echo filter_var($group["id"],FILTER_VALIDATE_INT); ?>"
															<?php
																if (CLIENTS_AUTO_GROUP == $group["id"]) {
																	echo 'selected="selected"';
																}
															?>
															><?php echo html_output($group["name"]); ?>
														</option>
													<?php
												}
											?>
										</select>
										<p class="field_note"><?php _e('New clients will automatically be assigned to the group you have selected.','cftp_admin'); ?></p>
									</div>
								</div>

								<div class="form-group">
									<label for="clients_can_select_group" class="col-sm-4 control-label"><?php _e('Groups for which clients can request membership to:','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<select class="form-control" name="clients_can_select_group" id="clients_can_select_group">
											<?php
												$pub_groups_options = array(
																			'none'		=> __("None",'cftp_admin'),
																			'public'	=> __("Public groups",'cftp_admin'),
																			'all'		=> __("All groups",'cftp_admin'),
																		);
												foreach ( $pub_groups_options as $value => $label ) {
											?>
													<option value="<?php echo $value; ?>" <?php if (CLIENTS_CAN_SELECT_GROUP == $value) { echo 'selected="selected"'; } ?>><?php echo $label; ?></option>
											<?php
												}
											?>
										</select>
										<p class="field_note"><?php _e('When a client registers a new account, an option will be presented to request becoming a member of a particular group.','cftp_admin'); ?></p>
									</div>
								</div>

								<div class="options_divide"></div>

								<h3><?php _e('Files','cftp_admin'); ?></h3>
								<?php
									/*<p><?php _e('Options related to the files that clients upload themselves.','cftp_admin'); ?></p>
									*/
								?>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="clients_can_upload">
											<input type="checkbox" value="1" name="clients_can_upload" id="clients_can_upload" class="checkbox_options" <?php echo (CLIENTS_CAN_UPLOAD == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('Clients can upload files','cftp_admin'); ?>
										</label>
									</div>
								</div>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="clients_can_delete_own_files">
											<input type="checkbox" value="1" name="clients_can_delete_own_files" id="clients_can_delete_own_files" class="checkbox_options" <?php echo (CLIENTS_CAN_DELETE_OWN_FILES == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('Clients can delete their own uploaded files','cftp_admin'); ?>
										</label>
									</div>
								</div>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="clients_can_set_expiration_date">
											<input type="checkbox" value="1" name="clients_can_set_expiration_date" id="clients_can_set_expiration_date" class="checkbox_options" <?php echo (CLIENTS_CAN_SET_EXPIRATION_DATE == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('Clients can set expiration Date','cftp_admin'); ?>
										</label>
									</div>
								</div>

								<div class="form-group">
									<label for="expired_files_hide" class="col-sm-4 control-label"><?php _e('When a file expires:','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<select class="form-control" name="expired_files_hide" id="expired_files_hide">
											<option value="1" <?php echo (EXPIRED_FILES_HIDE == '1') ? 'selected="selected"' : ''; ?>><?php _e("Don't show it on the files list",'cftp_admin'); ?></option>
											<option value="0" <?php echo (EXPIRED_FILES_HIDE == '0') ? 'selected="selected"' : ''; ?>><?php _e("Show it anyway, but prevent download.",'cftp_admin'); ?></option>
										</select>
										<p class="field_note"><?php _e('This only affects clients. On the admin side, you can still get the files.','cftp_admin'); ?></p>
									</div>
								</div>
					<?php
							break;
							case 'privacy':
					?>
								<h3><?php _e('Privacy','cftp_admin'); ?></h3>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="privacy_noindex_site">
											<input type="checkbox" value="1" name="privacy_noindex_site" id="privacy_noindex_site" class="checkbox_options" <?php echo (PRIVACY_NOINDEX_SITE == 1) ? 'checked="checked"' : ''; ?> /> <?php _e("Prevent search engines from indexing this site",'cftp_admin'); ?>
										</label>
									</div>
								</div>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="enable_landing_for_all_files">
											<input type="checkbox" value="1" name="enable_landing_for_all_files" id="enable_landing_for_all_files" class="checkbox_options" <?php echo (ENABLE_LANDING_FOR_ALL_FILES == 1) ? 'checked="checked"' : ''; ?> /> <?php _e("Enable information page for private files",'cftp_admin'); ?>
											<p class="field_note"><?php _e("If enabled, the file information landing page will be available even for files that are not marked as private. Downloading them will stay restricted.",'cftp_admin'); ?></p>
										</label>
									</div>
								</div>

								<div class="options_divide"></div>

								<h3><?php _e('Public groups and files listings page','cftp_admin'); ?></h3>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="public_listing_page_enable">
											<input type="checkbox" value="1" name="public_listing_page_enable" id="public_listing_page_enable" class="checkbox_options" <?php echo (PUBLIC_LISTING_ENABLE == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('Enable page','cftp_admin'); ?>
										</label>
										<p class="field_note"><?php _e('The url for the listings page is','cftp_admin'); ?><br>
										<a href="<?php echo PUBLIC_LANDING_URI; ?>" target="_blank">
											<?php echo PUBLIC_LANDING_URI; ?>
										</a></p>
									</div>
								</div>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="public_listing_logged_only">
											<input type="checkbox" value="1" name="public_listing_logged_only" id="public_listing_logged_only" class="checkbox_options" <?php echo (PUBLIC_LISTING_LOGGED_ONLY == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('Only for logged in clients','cftp_admin'); ?>
										</label>
									</div>
								</div>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="public_listing_show_all_files">
											<input type="checkbox" value="1" name="public_listing_show_all_files" id="public_listing_show_all_files" class="checkbox_options" <?php echo (PUBLIC_LISTING_SHOW_ALL_FILES == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('Inside groups show all files, including those that are not marked as public.','cftp_admin'); ?>
										</label>
									</div>
								</div>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="public_listing_use_download_link">
											<input type="checkbox" value="1" name="public_listing_use_download_link" id="public_listing_use_download_link" class="checkbox_options" <?php echo (PUBLIC_LISTING_USE_DOWNLOAD_LINK == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('On public files, show the download link.','cftp_admin'); ?>
										</label>
									</div>
								</div>

								<div class="options_divide"></div>

					<?php
							break;
							case 'email':
					?>
								<h3><?php _e('"From" information','cftp_admin'); ?></h3>

								<div class="form-group">
									<label for="admin_email_address" class="col-sm-4 control-label"><?php _e('E-mail address','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<input type="text" name="admin_email_address" id="admin_email_address" class="form-control" value="<?php echo html_output(ADMIN_EMAIL_ADDRESS); ?>" />
									</div>
								</div>

								<div class="form-group">
									<label for="mail_from_name" class="col-sm-4 control-label"><?php _e('Name','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<input type="text" name="mail_from_name" id="mail_from_name" class="form-control" value="<?php echo html_output(MAIL_FROM_NAME); ?>" />
									</div>
								</div>

								<div class="options_divide"></div>

								<h3><?php _e('Send copies','cftp_admin'); ?></h3>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="mail_copy_user_upload">
											<input type="checkbox" value="1" name="mail_copy_user_upload" id="mail_copy_user_upload" <?php echo (COPY_MAIL_ON_USER_UPLOADS == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('When a system user uploads files','cftp_admin'); ?>
										</label>
									</div>
								</div>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="mail_copy_client_upload">
											<input type="checkbox" value="1" name="mail_copy_client_upload" id="mail_copy_client_upload" <?php echo (COPY_MAIL_ON_CLIENT_UPLOADS == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('When a client uploads files','cftp_admin'); ?>
										</label>
									</div>
								</div>

								<div class="options_nested_note">
									<p><?php _e('Define here who will receive copies of this emails. These are sent as BCC so neither recipient will see the other addresses.','cftp_admin'); ?></p>
								</div>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="mail_copy_main_user">
											<input type="checkbox" value="1" name="mail_copy_main_user" class="mail_copy_main_user" <?php echo (COPY_MAIL_MAIN_USER == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('Address supplied above (on "From")','cftp_admin'); ?>
										</label>
									</div>
								</div>

								<div class="form-group">
									<label for="mail_copy_addresses" class="col-sm-4 control-label"><?php _e('Also to this addresses','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<input type="text" name="mail_copy_addresses" id="mail_copy_addresses" class="mail_data empty form-control" value="<?php echo html_output(COPY_MAIL_ADDRESSES); ?>" />
										<p class="field_note"><?php _e('Separate e-mail addresses with a comma.','cftp_admin'); ?></p>
									</div>
								</div>

								<div class="options_divide"></div>

								<h3><?php _e('Expiration','cftp_admin'); ?></h3>

								<div class="form-group">
									<label for="notifications_max_tries" class="col-sm-4 control-label"><?php _e('Maximum sending attemps','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<input type="text" name="notifications_max_tries" id="notifications_max_tries" class="form-control" value="<?php echo NOTIFICATIONS_MAX_TRIES; ?>" />
										<p class="field_note"><?php _e('Define how many times will the system attemp to send each notification.','cftp_admin'); ?></p>
									</div>
								</div>

								<div class="form-group">
									<label for="notifications_max_days" class="col-sm-4 control-label"><?php _e('Days before expiring','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<input type="text" name="notifications_max_days" id="notifications_max_days" class="form-control" value="<?php echo NOTIFICATIONS_MAX_DAYS; ?>" />
										<p class="field_note"><?php _e('Notifications older than this will not be sent.','cftp_admin'); ?><br /><strong><?php _e('Set to 0 to disable.','cftp_admin'); ?></strong></p>
									</div>
								</div>

								<div class="options_divide"></div>

								<h3><?php _e('E-mail sending options','cftp_admin'); ?></h3>
								<p><?php _e('Here you can select which mail system will be used when sending the notifications. If you have a valid e-mail account, SMTP is the recommended option.','cftp_admin'); ?></p>

								<div class="form-group">
									<label for="mail_system_use" class="col-sm-4 control-label"><?php _e('Mailer','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<select class="form-control" name="mail_system_use" id="mail_system_use">
											<option value="mail" <?php echo (MAIL_SYSTEM == 'mail') ? 'selected="selected"' : ''; ?>>PHP Mail (basic)</option>
											<option value="smtp" <?php echo (MAIL_SYSTEM == 'smtp') ? 'selected="selected"' : ''; ?>>SMTP</option>
											<option value="gmail" <?php echo (MAIL_SYSTEM == 'gmail') ? 'selected="selected"' : ''; ?>>Gmail</option>
											<option value="sendmail" <?php echo (MAIL_SYSTEM == 'sendmail') ? 'selected="selected"' : ''; ?>>Sendmail</option>
										</select>
									</div>
								</div>

								<div class="options_divide"></div>

								<h3><?php _e('SMTP & Gmail shared options','cftp_admin'); ?></h3>
								<p><?php _e('You need to include your username (usually your e-mail address) and password if you have selected either SMTP or Gmail as your mailer.','cftp_admin'); ?></p>

								<div class="form-group">
									<label for="mail_smtp_user" class="col-sm-4 control-label"><?php _e('Username','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<input type="text" name="mail_smtp_user" id="mail_smtp_user" class="mail_data empty form-control" value="<?php echo html_output(SMTP_USER); ?>" />
									</div>
								</div>

								<div class="form-group">
									<label for="mail_smtp_pass" class="col-sm-4 control-label"><?php _e('Password','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<input type="password" name="mail_smtp_pass" id="mail_smtp_pass" class="mail_data empty form-control" value="<?php echo html_output(SMTP_PASS); ?>" />
									</div>
								</div>

								<div class="options_divide"></div>

								<h3><?php _e('SMTP options','cftp_admin'); ?></h3>
								<p><?php _e('If you selected SMTP as your mailer, please complete these options.','cftp_admin'); ?></p>

								<div class="form-group">
									<label for="mail_smtp_host" class="col-sm-4 control-label"><?php _e('Host','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<input type="text" name="mail_smtp_host" id="mail_smtp_host" class="mail_data empty form-control" value="<?php echo html_output(SMTP_HOST); ?>" />
									</div>
								</div>

								<div class="form-group">
									<label for="mail_smtp_port" class="col-sm-4 control-label"><?php _e('Port','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<input type="text" name="mail_smtp_port" id="mail_smtp_port" class="mail_data empty form-control" value="<?php echo html_output(SMTP_PORT); ?>" />
									</div>
								</div>

								<div class="form-group">
									<label for="mail_smtp_auth" class="col-sm-4 control-label"><?php _e('Authentication','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<select class="form-control" name="mail_smtp_auth" id="mail_smtp_auth">
											<option value="none" <?php echo (SMTP_AUTH == 'none') ? 'selected="selected"' : ''; ?>><?php _e('None','cftp_admin'); ?></option>
											<option value="ssl" <?php echo (SMTP_AUTH == 'ssl') ? 'selected="selected"' : ''; ?>>SSL</option>
											<option value="tls" <?php echo (SMTP_AUTH == 'tls') ? 'selected="selected"' : ''; ?>>TLS</option>
										</select>
									</div>
								</div>
					<?php
							break;
							case 'security':
					?>
								<h3><?php _e('Allowed file extensions','cftp_admin'); ?></h3>
								<p><?php _e('Be careful when changing this options. They could affect not only the system but the whole server it is installed on.','cftp_admin'); ?><br />
								<strong><?php _e('Important','cftp_admin'); ?></strong>: <?php _e('Separate allowed file types with a comma.','cftp_admin'); ?></p>

							   <div class="form-group">
								   <label for="file_types_limit_to" class="col-sm-4 control-label"><?php _e('Limit file types uploading to','cftp_admin'); ?></label>
								   <div class="col-sm-8">
										<select class="form-control" name="file_types_limit_to" id="file_types_limit_to">
											<option value="noone" <?php echo (FILE_TYPES_LIMIT_TO == 'noone') ? 'selected="selected"' : ''; ?>><?php _e('No one','cftp_admin'); ?></option>
											<option value="all" <?php echo (FILE_TYPES_LIMIT_TO == 'all') ? 'selected="selected"' : ''; ?>><?php _e('Everyone','cftp_admin'); ?></option>
											<option value="clients" <?php echo (FILE_TYPES_LIMIT_TO == 'clients') ? 'selected="selected"' : ''; ?>><?php _e('Clients only','cftp_admin'); ?></option>
										</select>
								   </div>
								</div>

							   <div class="form-group">
									<input name="allowed_file_types" id="allowed_file_types" value="<?php echo $allowed_file_types; ?>" />
								</div>

								<?php
									if ( isset( $php_allowed_warning ) && $php_allowed_warning == true ) {
										$msg = __('Warning: php extension is allowed. This is a serious security problem. If you are not sure that you need it, please remove it from the list.','cftp_admin');
										echo system_message('error',$msg);
									}
								?>

								<div class="options_divide"></div>

								<h3><?php _e('Passwords','cftp_admin'); ?></h3>
								<p><?php _e('When setting up a password for an account, require at least:','cftp_admin'); ?></p>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="pass_require_upper">
											<input type="checkbox" value="1" name="pass_require_upper" id="pass_require_upper" class="checkbox_options" <?php echo (PASS_REQ_UPPER == 1) ? 'checked="checked"' : ''; ?> /> <?php echo $validation_req_upper; ?>
										</label>
									</div>
								</div>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="pass_require_lower">
											<input type="checkbox" value="1" name="pass_require_lower" id="pass_require_lower" class="checkbox_options" <?php echo (PASS_REQ_LOWER == 1) ? 'checked="checked"' : ''; ?> /> <?php echo $validation_req_lower; ?>
										</label>
									</div>
								</div>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="pass_require_number">
											<input type="checkbox" value="1" name="pass_require_number" id="pass_require_number" class="checkbox_options" <?php echo (PASS_REQ_NUMBER == 1) ? 'checked="checked"' : ''; ?> /> <?php echo $validation_req_number; ?>
										</label>
									</div>
								</div>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="pass_require_special">
											<input type="checkbox" value="1" name="pass_require_special" id="pass_require_special" class="checkbox_options" <?php echo (PASS_REQ_SPECIAL == 1) ? 'checked="checked"' : ''; ?> /> <?php echo $validation_req_special; ?>
										</label>
									</div>
								</div>

								<div class="options_divide"></div>

								<h3><?php _e('reCAPTCHA','cftp_admin'); ?></h3>
								<p><?php _e('Helps prevent SPAM on your registration form.','cftp_admin'); ?></p>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="recaptcha_enabled">
											<input type="checkbox" value="1" name="recaptcha_enabled" id="recaptcha_enabled" class="checkbox_options" <?php echo (RECAPTCHA_ENABLED == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('Use reCAPTCHA','cftp_admin'); ?>
										</label>
									</div>
								</div>

								<div class="form-group">
									<label for="recaptcha_site_key" class="col-sm-4 control-label"><?php _e('Site key','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<input type="text" name="recaptcha_site_key" id="recaptcha_site_key" class="form-control empty" value="<?php echo html_output(RECAPTCHA_SITE_KEY); ?>" />
									</div>
								</div>

								<div class="form-group">
									<label for="recaptcha_secret_key" class="col-sm-4 control-label"><?php _e('Secret key','cftp_admin'); ?></label>
									<div class="col-sm-8">
										<input type="text" name="recaptcha_secret_key" id="recaptcha_secret_key" class="form-control empty" value="<?php echo html_output(RECAPTCHA_SECRET_KEY); ?>" />
									</div>
								</div>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<a href="<?php echo LINK_DOC_RECAPTCHA; ?>" class="external_link" target="_blank"><?php _e('How do I obtain this credentials?','cftp_admin'); ?></a>
									</div>
								</div>
					<?php
							break;
							case 'thumbnails':
					?>
								<h3><?php _e('Thumbnails','cftp_admin'); ?></h3>
								<p><?php _e("Thumbnails are used on files lists. It is recommended to keep them small, unless you are using the system to upload only images, and will change the default client's template accordingly.",'cftp_admin'); ?></p>

								<div class="options_column">
									<div class="options_col_left">
										<div class="form-group">
											<label for="max_thumbnail_width" class="col-sm-6 control-label"><?php _e('Max width','cftp_admin'); ?></label>
											<div class="col-sm-6">
												<input type="text" name="max_thumbnail_width" id="max_thumbnail_width" class="form-control" value="<?php echo html_output(THUMBS_MAX_WIDTH); ?>" />
											</div>
										</div>

										<div class="form-group">
											<label for="max_thumbnail_height" class="col-sm-6 control-label"><?php _e('Max height','cftp_admin'); ?></label>
											<div class="col-sm-6">
												<input type="text" name="max_thumbnail_height" id="max_thumbnail_height" class="form-control" value="<?php echo html_output(THUMBS_MAX_HEIGHT); ?>" />
											</div>
										</div>
									</div>
									<div class="options_col_right">
										<div class="form-group">
											<label for="thumbnail_default_quality" class="col-sm-6 control-label"><?php _e('JPG Quality','cftp_admin'); ?></label>
											<div class="col-sm-6">
												<input type="text" name="thumbnail_default_quality" id="thumbnail_default_quality" class="form-control" value="<?php echo html_output(THUMBS_QUALITY); ?>" />
											</div>
										</div>
									</div>
								</div>

								<div class="options_divide"></div>

								<h3><?php _e("File's path", 'cftp_admin'); ?></h3>
								<p><?php _e("If thumbnails are not showing (your company logo and file's preview on the branding page and client's files lists) try setting this option ON. It they still don't work, a folders permission issue might be the cause.",'cftp_admin'); ?></p>

								<div class="form-group">
									<div class="col-sm-8 col-sm-offset-4">
										<label for="thumbnails_use_absolute">
											<input type="checkbox" value="1" name="thumbnails_use_absolute" id="thumbnails_use_absolute" <?php echo (THUMBS_USE_ABSOLUTE == 1) ? 'checked="checked"' : ''; ?> /> <?php _e("Use file's absolute path",'cftp_admin'); ?>
										</label>
									</div>
								</div>
					<?php
							break;
							case 'branding':
					?>
								<h3><?php _e('Current logo','cftp_admin'); ?></h3>
								<p><?php _e('Use this page to upload your company logo, or update the currently assigned one. This image will be shown to your clients when they access their file list.','cftp_admin'); ?></p>

								<input type="hidden" name="MAX_FILE_SIZE" value="1000000000">

								<div id="current_logo">
									<div id="current_logo_img">
										<?php
											if ($logo_file_info['exists'] === true) {
										?>
												<img src="<?php echo $logo_file_info['url']; ?>" alt="<?php _e('Logo Placeholder','cftp_admin'); ?>" />
										<?php
											}
										?>
									</div>
									<p class="preview_logo_note">
										<?php _e('This preview uses a maximum width of 300px.','cftp_admin'); ?>
									</p>
								</div>

								<div id="form_upload_logo">
									<div class="form-group">
										<label class="col-sm-4 control-label"><?php _e('Select image to upload','cftp_admin'); ?></label>
										<div class="col-sm-8">
											<input type="file" name="select_logo" class="empty" />
										</div>
									</div>
								</div>

								<div class="options_divide"></div>

								<h3><?php _e('Size settings','cftp_admin'); ?></h3>
								<p><?php _e("The file viewer template may use this setting when showing the image.",'cftp_admin'); ?></p>

								<div class="form-group">
									<label for="max_logo_width" class="col-sm-4 control-label"><?php _e('Max width','cftp_admin'); ?></label>
									<div class="col-sm-3">
										<div class="input-group">
											<input type="text" name="max_logo_width" id="max_logo_width" class="form-control" value="<?php echo html_output(LOGO_MAX_WIDTH); ?>" />
											<span class="input-group-addon">px</span>
										</div>
									</div>
								</div>

								<div class="form-group">
									<label for="max_logo_height" class="col-sm-4 control-label"><?php _e('Max height','cftp_admin'); ?></label>
									<div class="col-sm-3">
										<div class="input-group">
											<input type="text" name="max_logo_height" id="max_logo_height" class="form-control" value="<?php echo html_output(LOGO_MAX_HEIGHT); ?>" />
											<span class="input-group-addon">px</span>
										</div>
									</div>
								</div>
					<?php
							break;
							case 'social_login':
					?>
								<h3><?php _e('Google','cftp_admin'); ?></h3>

								<div class="options_column">
									<div class="form-group">
										<label for="google_signin_enabled" class="col-sm-4 control-label"><?php _e('Enabled','cftp_admin'); ?></label>
										<div class="col-sm-8">
											<select name="google_signin_enabled" id="google_signin_enabled" class="form-control">
												<option value="1" <?php echo (GOOGLE_SIGNIN_ENABLED == '1') ? 'selected="selected"' : ''; ?>><?php _e('Yes','cftp_admin'); ?></option>
												<option value="0" <?php echo (GOOGLE_SIGNIN_ENABLED == '0') ? 'selected="selected"' : ''; ?>><?php _e('No','cftp_admin'); ?></option>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label for="google_client_id" class="col-sm-4 control-label"><?php _e('Client ID','cftp_admin'); ?></label>
										<div class="col-sm-8">
											<input type="text" name="google_client_id" id="google_client_id" class="form-control empty" value="<?php echo html_output(GOOGLE_CLIENT_ID); ?>" />
										</div>
									</div>
									<div class="form-group">
										<label for="google_client_secret" class="col-sm-4 control-label"><?php _e('Client Secret','cftp_admin'); ?></label>
										<div class="col-sm-8">
											<input type="text" name="google_client_secret" id="google_client_secret" class="form-control empty" value="<?php echo html_output(GOOGLE_CLIENT_SECRET); ?>" />
										</div>
									</div>
									<div class="form-group">
										<div class="col-sm-8 col-sm-offset-4">
											<a href="<?php echo LINK_DOC_GOOGLE_SIGN_IN; ?>" class="external_link" target="_blank"><?php _e('How do I obtain this credentials?','cftp_admin'); ?></a>
										</div>
									</div>
									<div class="form-group">
										<div class="col-sm-4">
											<?php _e('Callback URI','cftp_admin'); ?>
										</div>
										<div class="col-sm-8">
											<span class="format_url"><?php echo BASE_URI . 'sociallogin/google/callback.php'; ?></span>
										</div>
									</div>
								</div>
					<?php
							break;
						}
					?>

				<div class="options_divide"></div>

				<div class="after_form_buttons">
					<button type="submit" class="btn btn-wide btn-primary empty"><?php _e('Save options','cftp_admin'); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
	include('footer.php');
