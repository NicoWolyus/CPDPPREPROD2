/**
* 2013-2019 2N Technologies
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to contact@2n-tech.com so we can send you a copy immediately.
*
* @author    2N Technologies <contact@2n-tech.com>
* @copyright 2013-2019 2N Technologies
* @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

var progressBackup;
var progress_restore;
var backup_warning          = '';
var refresh_sent            = 0;
var display_progress_only   = 0;
var ftp_account_id          = '#ftp_account';
var dropbox_account_id      = '#dropbox_account';
var owncloud_account_id     = '#owncloud_account';
var webdav_account_id       = '#webdav_account';
var googledrive_account_id  = '#googledrive_account';
var onedrive_account_id     = '#onedrive_account';
var hubic_account_id        = '#hubic_account';
var aws_account_id          = '#aws_account';
var ftp_save_result         = '';
var dropbox_save_result     = '';
var owncloud_save_result    = '';
var hubic_save_result       = '';
var webdav_save_result      = '';
var googledrive_save_result = '';
var onedrive_save_result    = '';
var aws_save_result         = '';

$(document).ready( function ()
{
	$('#nt_tab a').click(function()
	{
		ntTab($(this));
	});

	$('#nt_advanced_automation_tab li').click(function()
	{
		ntAutomationTab($(this));
	});

	$('#create_backup').click(function()
	{
		createBackup();
	});

	$('.backup_download').click(function(){
		downloadFile('backup', $(this).attr('nb'));
	});

	$('.delete_backup').click(function()
	{
		deleteBackup($(this).attr('nb'));
	});

	$('.save_comment_backup').click(function()
	{
		saveCommentBackup($(this).attr('nb'));
	});

	$('.backup_see').click(function()
	{
        seeBackup($(this).attr('nb'));
	});

	$('.send_backup').click(function()
	{
        sendBackup($(this).attr('nb'));
	});

	$('#backup_log_download').click(function(){
		downloadFile('log', 0);
	});

	$('#restore_download').click(function(){
		downloadFile('restore', 0);
	});

	$('#generate_url').click(function(){
		generateUrls();
	});

    $('#choose_type_backup_files').change(function() {
        var type_backup = $('#choose_type_backup_files').val();

        $('.list_backup_type').hide();
        $('#restore_backup_'+type_backup+'_files').show();
    });

    $('#restoration #start_restore').click(function() {
        if ($('.restore_backup:checked').length <= 0) {
            alert(no_backup_selected);
            return;
        }

        var backup = $('.restore_backup:checked').parent().find('.backup_name').text();
        var type_backup = $('#choose_type_backup_files').val();

        if (confirm(confirm_restore_backup) == true) {
            initRestoreBackup(backup, type_backup);
        }
    });

    $('#big_website_error .close').click(function(){
        $('#big_website_error').hide();

        $.post(
            admin_link_ntbr,
            'hide_big_site=1'
        );
    });

    $('#send_ftp').click(function(){
        $('#config_ftp_accounts').toggle();
    });

    $('.choose_ftp_account').click(function(){
        if (checkFormChanged(ftp_account_id)) {
            if (confirm(confirm_change_account) == true) {
                displayFtpAccount($(this).val());
            } else {
                selectFtpTab($('#id_ntbr_ftp').val());
            }
        } else {
            displayFtpAccount($(this).val());
        }
    });

    $('#save_ftp').click(function(){
        saveFtp();
    });

    $('#check_ftp').click(function(){
        checkConnectionFtp();
    });

    $('#delete_ftp').click(function(){
        deleteFtp();
    });

    $('#send_dropbox').click(function(){
        $('#config_dropbox_accounts').toggle();
    });

    $('.choose_dropbox_account').click(function(){
        if (checkFormChanged(dropbox_account_id)) {
            if (confirm(confirm_change_account) == true) {
                displayDropboxAccount($(this).val());
            } else {
                selectDropboxTab($('#id_ntbr_dropbox').val());
            }
        } else {
            displayDropboxAccount($(this).val());
        }
    });

    $('#save_dropbox').click(function(){
        saveDropbox();
    });

    $('#check_dropbox').click(function(){
        checkConnectionDropbox();
    });

    $('#delete_dropbox').click(function(){
        deleteDropbox();
    });

    $('#send_owncloud').click(function(){
        $('#config_owncloud_accounts').toggle();
    });

    $('.choose_owncloud_account').click(function(){
        if (checkFormChanged(owncloud_account_id)) {
            if (confirm(confirm_change_account) == true) {
                displayOwncloudAccount($(this).val());
            } else {
                selectOwncloudTab($('#id_ntbr_owncloud').val());
            }
        } else {
            displayOwncloudAccount($(this).val());
        }
    });

    $('#save_owncloud').click(function(){
        saveOwncloud();
    });

    $('#check_owncloud').click(function(){
        checkConnectionOwncloud();
    });

    $('#delete_owncloud').click(function(){
        deleteOwncloud();
    });

    $('#send_webdav').click(function(){
        $('#config_webdav_accounts').toggle();
    });

    $('.choose_webdav_account').click(function(){
        if (checkFormChanged(webdav_account_id)) {
            if (confirm(confirm_change_account) == true) {
                displayWebdavAccount($(this).val());
            } else {
                selectWebdavTab($('#id_ntbr_webdav').val());
            }
        } else {
            displayWebdavAccount($(this).val());
        }
    });

    $('#save_webdav').click(function(){
        saveWebdav();
    });

    $('#check_webdav').click(function(){
        checkConnectionWebdav();
    });

    $('#delete_webdav').click(function(){
        deleteWebdav();
    });

    $('#send_googledrive').click(function(){
        $('#config_googledrive_accounts').toggle();
    });

    $('.choose_googledrive_account').click(function(){
        if (checkFormChanged(googledrive_account_id)) {
            if (confirm(confirm_change_account) == true) {
                displayGoogledriveAccount($(this).val());
            } else {
                selectGoogledriveTab($('#id_ntbr_googledrive').val());
            }
        } else {
            displayGoogledriveAccount($(this).val());
        }
    });

    $('#save_googledrive').click(function(){
        saveGoogledrive();
    });

    $('#check_googledrive').click(function(){
        checkConnectionGoogledrive();
    });

    $('#delete_googledrive').click(function(){
        deleteGoogledrive();
    });

    $('#send_onedrive').click(function(){
        $('#config_onedrive_accounts').toggle();
    });

    $('.choose_onedrive_account').click(function(){
        if (checkFormChanged(onedrive_account_id)) {
            if (confirm(confirm_change_account) == true) {
                displayOnedriveAccount($(this).val());
            } else {
                selectOnedriveTab($('#id_ntbr_onedrive').val());
            }
        } else {
            displayOnedriveAccount($(this).val());
        }
    });

    $('#save_onedrive').click(function(){
        saveOnedrive();
    });

    $('#check_onedrive').click(function(){
        checkConnectionOnedrive();
    });

    $('#delete_onedrive').click(function(){
        deleteOnedrive();
    });

    $('#send_hubic').click(function(){
        $('#config_hubic_accounts').toggle();
    });

    $('.choose_hubic_account').click(function(){
        if (checkFormChanged(hubic_account_id)) {
            if (confirm(confirm_change_account) == true) {
                displayHubicAccount($(this).val());
            } else {
                selectHubicTab($('#id_ntbr_hubic').val());
            }
        } else {
            displayHubicAccount($(this).val());
        }
    });

    $('#save_hubic').click(function(){
        saveHubic();
    });

    $('#check_hubic').click(function(){
        checkConnectionHubic();
    });

    $('#delete_hubic').click(function(){
        deleteHubic();
    });

    $('#send_aws').click(function(){
        $('#config_aws_accounts').toggle();
    });

    $('.choose_aws_account').click(function(){
        if (checkFormChanged(aws_account_id)) {
            if (confirm(confirm_change_account) == true) {
                displayAwsAccount($(this).val());
            } else {
                selectAwsTab($('#id_ntbr_aws').val());
            }
        } else {
            displayAwsAccount($(this).val());
        }
    });

    $('#save_aws').click(function(){
        saveAws();
    });

    $('#check_aws').click(function(){
        checkConnectionAws();
    });

    $('#delete_aws').click(function(){
        deleteAws();
    });

	$(save_btn_id).click(function(){
        if(confirm(confirm_save_config))
        {
            saveAllConfiguration();
        }
        else
        {
            return;
        }

	});

	$('#nt_save_config_btn').click(function(){
		saveAllConfiguration();
	});

	$('#nt_save_automation_btn').click(function(){
		saveAutomation();
	});

	$('#display_onedrive_tree').click(function(){
		displayOnedriveTree();
	});

	$('#display_aws_tree').click(function(){
		displayAwsTree();
	});

	$('#display_googledrive_tree').click(function(){
		displayGoogledriveTree();
	});

	$('#backup_download').show();
	$('#restore_download').show();
	//$('#delete_backup').show();

    if($('#send_email_off').is(':checked'))
        $('#change_mail').hide();

    $('#send_email_off').click(function()
    {
        $('#change_mail').hide();
    });

    $('#send_email_on').click(function()
    {
        $('#change_mail').show();
    });

    if($('#send_sftp_off').is(':checked'))
    {
        var current_value = $('#ftp_port').val();
        if (!current_value || current_value == '') {
            $('#ftp_port').val('21');
        }

        $('.option_ftp_ssl').show();
        $('.option_ftp_pasv').show();
    }
    else
    {
        var current_value = $('#ftp_port').val();
        if (!current_value || current_value == '') {
            $('#ftp_port').val('22');
        }

        $('#ftp_port').val('22');

        $('#ftp_ssl_off').prop('checked', true);
        $('#ftp_ssl_off').attr('checked', 'checked');
        $('#ftp_pasv_off').prop('checked', true);
        $('#ftp_pasv_off').attr('checked', 'checked');

        $('.option_ftp_ssl').hide();
        $('.option_ftp_pasv').hide();
    }

    $('#send_sftp_off').click(function()
    {
        $('#ftp_port').val('21');
        $('.option_ftp_ssl').show();
        $('.option_ftp_pasv').show();
    });

    $('#send_sftp_on').click(function()
    {
        $('#ftp_port').val('22');

        $('#ftp_ssl_off').prop('checked', true);
        $('#ftp_ssl_off').attr('checked', 'checked');
        $('#ftp_pasv_off').prop('checked', true);
        $('#ftp_pasv_off').attr('checked', 'checked');

        $('.option_ftp_ssl').hide();
        $('.option_ftp_pasv').hide();
    });

    $('#nt_advanced_config').click(function()
    {
        $('#nt_advanced_config_diplay').toggle();
    });

    $('#nt_advanced_automation').click(function()
    {
        $('#nt_advanced_automation_diplay').toggle();
    });

    $('.deactivate').click(function(e)
    {
        e.preventDefault();
        return false;
    });

    $('.deactivate').find('select, button, input').each(function()
    {
        $(this).attr('disabled', 'disabled');
    });

    $('#display_progress').click(function(){
        display_progress_only = 1;
        displayProgress();
    });

    $('#stop_backup').click(function(){
        $.post(stop_backup, function(data)
            {

            },"json"
        );
    });

    if($('#increase_server_memory_off').is(':checked')) {
        $('#increase_server_memory_value').parent().parent().hide();
    }

    if($('#disable_refresh_on').is(':checked')) {
        $('#time_between_refresh').parent().parent().hide();
    }

    $('input[type=radio][name=increase_server_memory]').change(function(){
        if($('#increase_server_memory_off').is(':checked')) {
            $('#increase_server_memory_value').parent().parent().hide();
        } else {
            $('#increase_server_memory_value').parent().parent().show();
        }
    });

    $('input[type=radio][name=disable_refresh]').change(function(){
        if($('#disable_refresh_on').is(':checked')) {
            $('#time_between_refresh').parent().parent().hide();
        } else {
            $('#time_between_refresh').parent().parent().show();
        }
    });
});

function ntTab(tab)
{
	$('.tab').hide();
	$('#nt_tab a').removeClass('active');
	var tab_id = tab.attr('id');
	$('#'+tab_id+'_content').show();
	tab.addClass('active');
}

function ntAutomationTab(tab)
{
	$('.nt_aat').hide();
	$('#nt_advanced_automation_tab li').removeClass('active');
    var tab_id = tab.attr('id');
	$('#'+tab_id+'_content').show();
	tab.addClass('active');
}

function checkFormChanged(id_form)
{
    var has_changed = false;

    $(id_form + ' input').each(function(){
        var origin_value = $(this).attr('data-origin');
        var new_value = $(this).val();

        if ($(this).attr('type') == 'radio') {
            if (!$(this).is(':checked')) {
                origin_value = '';
                new_value = '';
            }
        }

        if ($(this).hasClass('name_account') && $(this).parent().parent().find('input[type="hidden"]').val() <= 0) {
            origin_value += ' ' + $(id_form).parent().find('.account_list button').length;
        }

        if (typeof origin_value !== 'undefined' && origin_value != new_value) {
            has_changed = true;

        }
    });

    return has_changed;
}

function initForm(id_form)
{
    $(id_form + ' input').each(function(){
        var default_value = $(this).attr('data-default');

        $(this).attr('data-origin', default_value);

        if ($(this).attr('type') == 'radio') {
            if ($(this).val() == default_value) {
                $(this).prop('checked', true);
                $(this).attr('checked', 'checked');
            }
        } else {
            if ($(this).hasClass('name_account') && $(this).parent().parent().find('input[type="hidden"]').val() <= 0) {
                default_value += ' ' + $(id_form).parent().find('.account_list button').length;
            }

            $(this).val(default_value).change();
        }
    });
}

function selectFtpTab(id_ftp_account)
{
    if (typeof id_ftp_account === 'undefined') {
        id_ftp_account = 0;
    }

    $('.choose_ftp_account.active').removeClass('active').addClass('inactive');
    $('#ftp_account_' + id_ftp_account).removeClass('inactive').addClass('active');
}

function selectDropboxTab(id_dropbox_account)
{
    if (typeof id_dropbox_account === 'undefined') {
        id_dropbox_account = 0;
    }

    $('.choose_dropbox_account.active').removeClass('active').addClass('inactive');
    $('#dropbox_account_' + id_dropbox_account).removeClass('inactive').addClass('active');
}

function selectOwncloudTab(id_owncloud_account)
{
    if (typeof id_owncloud_account === 'undefined') {
        id_owncloud_account = 0;
    }

    $('.choose_owncloud_account.active').removeClass('active').addClass('inactive');
    $('#owncloud_account_' + id_owncloud_account).removeClass('inactive').addClass('active');
}

function selectWebdavTab(id_webdav_account)
{
    if (typeof id_webdav_account === 'undefined') {
        id_webdav_account = 0;
    }

    $('.choose_webdav_account.active').removeClass('active').addClass('inactive');
    $('#webdav_account_' + id_webdav_account).removeClass('inactive').addClass('active');
}

function selectGoogledriveTab(id_googledrive_account)
{
    if (typeof id_googledrive_account === 'undefined') {
        id_googledrive_account = 0;
    }

    $('.choose_googledrive_account.active').removeClass('active').addClass('inactive');
    $('#googledrive_account_' + id_googledrive_account).removeClass('inactive').addClass('active');
}

function selectOnedriveTab(id_onedrive_account)
{
    if (typeof id_onedrive_account === 'undefined') {
        id_onedrive_account = 0;
    }

    $('.choose_onedrive_account.active').removeClass('active').addClass('inactive');
    $('#onedrive_account_' + id_onedrive_account).removeClass('inactive').addClass('active');
}

function selectHubicTab(id_hubic_account)
{
    if (typeof id_hubic_account === 'undefined') {
        id_hubic_account = 0;
    }

    $('.choose_hubic_account.active').removeClass('active').addClass('inactive');
    $('#hubic_account_' + id_hubic_account).removeClass('inactive').addClass('active');
}

function selectAwsTab(id_aws_account)
{
    if (typeof id_aws_account === 'undefined') {
        id_aws_account = 0;
    }

    $('.choose_aws_account.active').removeClass('active').addClass('inactive');
    $('#aws_account_' + id_aws_account).removeClass('inactive').addClass('active');
}

function initFtpAccount()
{
    initForm(ftp_account_id);

    $('.option_ftp_ssl').show();
    $('.option_ftp_pasv').show();
    $('#check_ftp').hide();
}

function initDropboxAccount()
{
    initForm(dropbox_account_id);

    $('#check_dropbox').hide();
}

function initOwncloudAccount()
{
    initForm(owncloud_account_id);

    $('#check_owncloud').hide();
}

function initWebdavAccount()
{
    initForm(webdav_account_id);
    $('#check_webdav').hide();
}

function initGoogledriveAccount()
{
    initForm(googledrive_account_id);

    $('#googledrive_tree').html('');
    $('#check_googledrive').hide();
}

function initOnedriveAccount()
{
    initForm(onedrive_account_id);

    $('#onedrive_tree').html('');
    $('#check_onedrive').hide();
}

function initHubicAccount()
{
    initForm(hubic_account_id);

    $('#check_hubic').hide();
}

function initAwsAccount()
{
    initForm(aws_account_id);

    $('#aws_tree').html('');
    $('#check_aws').hide();
}

function displayFtpAccount(id_ntbr_ftp)
{
    initFtpAccount();

    selectFtpTab(id_ntbr_ftp);

    if (parseInt(id_ntbr_ftp) === 0) {
        return true;
    }

	return $.post(
		admin_link_ntbr,
		'display_ftp_account=1'
        +'&id_ntbr_ftp='+encodeURIComponent(id_ntbr_ftp),
		function(data)
		{
			if(data.ftp_account && data.ftp_account.id_ntbr_ftp)
			{
                $('#id_ntbr_ftp').val(data.ftp_account.id_ntbr_ftp);
                $('#id_ntbr_ftp').attr('data-origin', data.ftp_account.id_ntbr_ftp);

                $('#ftp_name').val(data.ftp_account.name);
                $('#ftp_name').attr('data-origin', data.ftp_account.name);

                $('#nb_keep_backup_ftp').val(data.ftp_account.nb_backup);
                $('#nb_keep_backup_ftp').attr('data-origin', data.ftp_account.nb_backup);

                $('#nb_keep_backup_file_ftp').val(data.ftp_account.nb_backup_file);
                $('#nb_keep_backup_file_ftp').attr('data-origin', data.ftp_account.nb_backup_file);

                $('#nb_keep_backup_base_ftp').val(data.ftp_account.nb_backup_base);
                $('#nb_keep_backup_base_ftp').attr('data-origin', data.ftp_account.nb_backup_base);

                $('#ftp_server').val(data.ftp_account.server);
                $('#ftp_server').attr('data-origin', data.ftp_account.server);

                $('#ftp_login').val(data.ftp_account.login);
                $('#ftp_login').attr('data-origin', data.ftp_account.login);

                $('#ftp_pass').val(data.ftp_account.password_decrypt);
                $('#ftp_pass').attr('data-origin', data.ftp_account.password_decrypt);

                $('#ftp_port').val(data.ftp_account.port);
                $('#ftp_port').attr('data-origin', data.ftp_account.port);

                $('#ftp_dir').val(data.ftp_account.directory);
                $('#ftp_dir').attr('data-origin', data.ftp_account.directory);

                if (parseInt(data.ftp_account.active) === 1) {
                    $('#active_ftp_on').prop('checked', true);
                    $('#active_ftp_on').attr('checked', 'checked');
                    $('#active_ftp_on').attr('data-origin', '1');
                    $('#active_ftp_off').attr('data-origin', '1');
                }

                if (parseInt(data.ftp_account.sftp) === 1) {
                    $('#send_sftp_on').prop('checked', true);
                    $('#send_sftp_on').attr('checked', 'checked');
                    $('#send_sftp_on').attr('data-origin', '1');
                    $('#send_sftp_off').attr('data-origin', '1');

                    $('#ftp_ssl_off').prop('checked', true);
                    $('#ftp_ssl_off').attr('checked', 'checked');
                    $('#ftp_ssl_off').attr('data-origin', '0');
                    $('#ftp_ssl_on').attr('data-origin', '0');

                    $('#ftp_pasv_off').prop('checked', true);
                    $('#ftp_pasv_off').attr('checked', 'checked');
                    $('#ftp_pasv_off').attr('data-origin', '0');
                    $('#ftp_pasv_on').attr('data-origin', '0');

                    $('.option_ftp_ssl').hide();
                    $('.option_ftp_pasv').hide();
                } else {
                    if (parseInt(data.ftp_account.ssl) === 1) {
                        $('#ftp_ssl_on').prop('checked', true);
                        $('#ftp_ssl_on').attr('checked', 'checked');
                        $('#ftp_ssl_on').attr('data-origin', '1');
                        $('#ftp_ssl_off').attr('data-origin', '1');
                    }

                    if (parseInt(data.ftp_account.passive_mode) === 1) {
                        $('#ftp_pasv_on').prop('checked', true);
                        $('#ftp_pasv_on').attr('checked', 'checked');
                        $('#ftp_pasv_on').attr('data-origin', '1');
                        $('#ftp_pasv_off').attr('data-origin', '1');
                    }

                    $('.option_ftp_ssl').show();
                    $('.option_ftp_pasv').show();
                }

                $('#check_ftp').show();
			}

            return true;
		},"json"
	);
}

function displayDropboxAccount(id_ntbr_dropbox)
{
    initDropboxAccount();

    selectDropboxTab(id_ntbr_dropbox);

    if (parseInt(id_ntbr_dropbox) === 0) {
        return true;
    }

	return $.post(
		admin_link_ntbr,
		'display_dropbox_account=1'
        +'&id_ntbr_dropbox='+encodeURIComponent(id_ntbr_dropbox),
		function(data)
		{
			if(data.dropbox_account && data.dropbox_account.id_ntbr_dropbox)
			{
                $('#id_ntbr_dropbox').val(data.dropbox_account.id_ntbr_dropbox);
                $('#id_ntbr_dropbox').attr('data-origin', data.dropbox_account.id_ntbr_dropbox);

                $('#dropbox_name').val(data.dropbox_account.name);
                $('#dropbox_name').attr('data-origin', data.dropbox_account.name);

                $('#nb_keep_backup_dropbox').val(data.dropbox_account.nb_backup);
                $('#nb_keep_backup_dropbox').attr('data-origin', data.dropbox_account.nb_backup);

                $('#nb_keep_backup_file_dropbox').val(data.dropbox_account.nb_backup_file);
                $('#nb_keep_backup_file_dropbox').attr('data-origin', data.dropbox_account.nb_backup_file);

                $('#nb_keep_backup_base_dropbox').val(data.dropbox_account.nb_backup_base);
                $('#nb_keep_backup_base_dropbox').attr('data-origin', data.dropbox_account.nb_backup_base);

                $('#dropbox_dir').val(data.dropbox_account.directory);
                $('#dropbox_dir').attr('data-origin', data.dropbox_account.directory);

                if (parseInt(data.dropbox_account.active) === 1) {
                    $('#active_dropbox_on').prop('checked', true);
                    $('#active_dropbox_on').attr('checked', 'checked');
                    $('#active_dropbox_on').attr('data-origin', '1');
                    $('#active_dropbox_off').attr('data-origin', '1');
                }

                $('#check_dropbox').show();
			}

            return true;
		},"json"
	);
}

function displayOwncloudAccount(id_ntbr_owncloud)
{
    initOwncloudAccount();

    selectOwncloudTab(id_ntbr_owncloud);

    if (parseInt(id_ntbr_owncloud) === 0) {
        return true;
    }

	return $.post(
		admin_link_ntbr,
		'display_owncloud_account=1'
        +'&id_ntbr_owncloud='+encodeURIComponent(id_ntbr_owncloud),
		function(data)
		{
			if(data.owncloud_account && data.owncloud_account.id_ntbr_owncloud)
			{
                $('#id_ntbr_owncloud').val(data.owncloud_account.id_ntbr_owncloud);
                $('#id_ntbr_owncloud').attr('data-origin', data.owncloud_account.id_ntbr_owncloud);

                $('#owncloud_name').val(data.owncloud_account.name);
                $('#owncloud_name').attr('data-origin', data.owncloud_account.name);

                $('#nb_keep_backup_owncloud').val(data.owncloud_account.nb_backup);
                $('#nb_keep_backup_owncloud').attr('data-origin', data.owncloud_account.nb_backup);

                $('#nb_keep_backup_file_owncloud').val(data.owncloud_account.nb_backup_file);
                $('#nb_keep_backup_file_owncloud').attr('data-origin', data.owncloud_account.nb_backup_file);

                $('#nb_keep_backup_base_owncloud').val(data.owncloud_account.nb_backup_base);
                $('#nb_keep_backup_base_owncloud').attr('data-origin', data.owncloud_account.nb_backup_base);

                $('#owncloud_user').val(data.owncloud_account.login);
                $('#owncloud_user').attr('data-origin', data.owncloud_account.login);

                $('#owncloud_pass').val(data.owncloud_account.password_decrypt);
                $('#owncloud_pass').attr('data-origin', data.owncloud_account.password_decrypt);

                $('#owncloud_server').val(data.owncloud_account.server);
                $('#owncloud_server').attr('data-origin', data.owncloud_account.server);

                $('#owncloud_dir').val(data.owncloud_account.directory);
                $('#owncloud_dir').attr('data-origin', data.owncloud_account.directory);

                if (parseInt(data.owncloud_account.active) === 1) {
                    $('#active_owncloud_on').prop('checked', true);
                    $('#active_owncloud_on').attr('checked', 'checked');
                    $('#active_owncloud_on').attr('data-origin', '1');
                    $('#active_owncloud_off').attr('data-origin', '1');
                }

                $('#check_owncloud').show();
			}

            return true;
		},"json"
	);
}

function displayWebdavAccount(id_ntbr_webdav)
{
    initWebdavAccount();

    selectWebdavTab(id_ntbr_webdav);

    if (parseInt(id_ntbr_webdav) === 0) {
        return true;
    }

	return $.post(
		admin_link_ntbr,
		'display_webdav_account=1'
        +'&id_ntbr_webdav='+encodeURIComponent(id_ntbr_webdav),
		function(data)
		{
			if(data.webdav_account && data.webdav_account.id_ntbr_webdav)
			{
                $('#id_ntbr_webdav').val(data.webdav_account.id_ntbr_webdav);
                $('#id_ntbr_webdav').attr('data-origin', data.webdav_account.id_ntbr_webdav);

                $('#webdav_name').val(data.webdav_account.name);
                $('#webdav_name').attr('data-origin', data.webdav_account.name);

                $('#nb_keep_backup_webdav').val(data.webdav_account.nb_backup);
                $('#nb_keep_backup_webdav').attr('data-origin', data.webdav_account.nb_backup);

                $('#nb_keep_backup_file_webdav').val(data.webdav_account.nb_backup_file);
                $('#nb_keep_backup_file_webdav').attr('data-origin', data.webdav_account.nb_backup_file);

                $('#nb_keep_backup_base_webdav').val(data.webdav_account.nb_backup_base);
                $('#nb_keep_backup_base_webdav').attr('data-origin', data.webdav_account.nb_backup_base);

                $('#webdav_user').val(data.webdav_account.login);
                $('#webdav_user').attr('data-origin', data.webdav_account.login);

                $('#webdav_pass').val(data.webdav_account.password_decrypt);
                $('#webdav_pass').attr('data-origin', data.webdav_account.password_decrypt);

                $('#webdav_server').val(data.webdav_account.server);
                $('#webdav_server').attr('data-origin', data.webdav_account.server);

                $('#webdav_dir').val(data.webdav_account.directory);
                $('#webdav_dir').attr('data-origin', data.webdav_account.directory);

                if (parseInt(data.webdav_account.active) === 1) {
                    $('#active_webdav_on').prop('checked', true);
                    $('#active_webdav_on').attr('checked', 'checked');
                    $('#active_webdav_on').attr('data-origin', '1');
                    $('#active_webdav_off').attr('data-origin', '1');
                }

                $('#check_webdav').show();
			}

            return true;
		},"json"
	);
}

function displayGoogledriveAccount(id_ntbr_googledrive)
{
    initGoogledriveAccount();

    selectGoogledriveTab(id_ntbr_googledrive);

    if (parseInt(id_ntbr_googledrive) === 0) {
        return true;
    }

	return $.post(
		admin_link_ntbr,
		'display_googledrive_account=1'
        +'&id_ntbr_googledrive='+encodeURIComponent(id_ntbr_googledrive),
		function(data)
		{
			if(data.googledrive_account && data.googledrive_account.id_ntbr_googledrive)
			{
                $('#id_ntbr_googledrive').val(data.googledrive_account.id_ntbr_googledrive);
                $('#id_ntbr_googledrive').attr('data-origin', data.googledrive_account.id_ntbr_googledrive);

                $('#googledrive_name').val(data.googledrive_account.name);
                $('#googledrive_name').attr('data-origin', data.googledrive_account.name);

                $('#nb_keep_backup_googledrive').val(data.googledrive_account.nb_backup);
                $('#nb_keep_backup_googledrive').attr('data-origin', data.googledrive_account.nb_backup);

                $('#nb_keep_backup_file_googledrive').val(data.googledrive_account.nb_backup_file);
                $('#nb_keep_backup_file_googledrive').attr('data-origin', data.googledrive_account.nb_backup_file);

                $('#nb_keep_backup_base_googledrive').val(data.googledrive_account.nb_backup_base);
                $('#nb_keep_backup_base_googledrive').attr('data-origin', data.googledrive_account.nb_backup_base);

                $('#googledrive_dir_path').val(data.googledrive_account.directory_path);
                $('#googledrive_dir_path').attr('data-origin', data.googledrive_account.directory_path);

                $('#googledrive_dir').val(data.googledrive_account.directory_key);
                $('#googledrive_dir').attr('data-origin', data.googledrive_account.directory_key);

                if (parseInt(data.googledrive_account.active) === 1) {
                    $('#active_googledrive_on').prop('checked', true);
                    $('#active_googledrive_on').attr('checked', 'checked');
                    $('#active_googledrive_on').attr('data-origin', '1');
                    $('#active_googledrive_off').attr('data-origin', '1');
                }

                $('#check_googledrive').show();
			}

            return true;
		},"json"
	);
}

function displayOnedriveAccount(id_ntbr_onedrive)
{
    initOnedriveAccount();

    selectOnedriveTab(id_ntbr_onedrive);

    if (parseInt(id_ntbr_onedrive) === 0) {
        return true;
    }

	return $.post(
		admin_link_ntbr,
		'display_onedrive_account=1'
        +'&id_ntbr_onedrive='+encodeURIComponent(id_ntbr_onedrive),
		function(data)
		{
			if(data.onedrive_account && data.onedrive_account.id_ntbr_onedrive)
			{
                $('#id_ntbr_onedrive').val(data.onedrive_account.id_ntbr_onedrive);
                $('#id_ntbr_onedrive').attr('data-origin', data.onedrive_account.id_ntbr_onedrive);

                $('#onedrive_name').val(data.onedrive_account.name);
                $('#onedrive_name').attr('data-origin', data.onedrive_account.name);

                $('#nb_keep_backup_onedrive').val(data.onedrive_account.nb_backup);
                $('#nb_keep_backup_onedrive').attr('data-origin', data.onedrive_account.nb_backup);

                $('#nb_keep_backup_file_onedrive').val(data.onedrive_account.nb_backup_file);
                $('#nb_keep_backup_file_onedrive').attr('data-origin', data.onedrive_account.nb_backup_file);

                $('#nb_keep_backup_base_onedrive').val(data.onedrive_account.nb_backup_base);
                $('#nb_keep_backup_base_onedrive').attr('data-origin', data.onedrive_account.nb_backup_base);

                $('#onedrive_dir_path').val(data.onedrive_account.directory_path);
                $('#onedrive_dir_path').attr('data-origin', data.onedrive_account.directory_path);

                $('#onedrive_dir').val(data.onedrive_account.directory_key);
                $('#onedrive_dir').attr('data-origin', data.onedrive_account.directory_key);

                if (parseInt(data.onedrive_account.active) === 1) {
                    $('#active_onedrive_on').prop('checked', true);
                    $('#active_onedrive_on').attr('checked', 'checked');
                    $('#active_onedrive_on').attr('data-origin', '1');
                    $('#active_onedrive_off').attr('data-origin', '1');
                }

                $('#check_onedrive').show();
			}

            return true;
		},"json"
	);
}

function displayHubicAccount(id_ntbr_hubic)
{
    initHubicAccount();
    selectHubicTab(id_ntbr_hubic);

    if (parseInt(id_ntbr_hubic) === 0) {
        return true;
    }

	return $.post(
		admin_link_ntbr,
		'display_hubic_account=1'
        +'&id_ntbr_hubic='+encodeURIComponent(id_ntbr_hubic),
		function(data)
		{
			if(data.hubic_account && data.hubic_account.id_ntbr_hubic)
			{
                $('#id_ntbr_hubic').val(data.hubic_account.id_ntbr_hubic);
                $('#id_ntbr_hubic').attr('data-origin', data.hubic_account.id_ntbr_hubic);

                $('#hubic_name').val(data.hubic_account.name);
                $('#hubic_name').attr('data-origin', data.hubic_account.name);

                $('#nb_keep_backup_hubic').val(data.hubic_account.nb_backup);
                $('#nb_keep_backup_hubic').attr('data-origin', data.hubic_account.nb_backup);

                $('#nb_keep_backup_file_hubic').val(data.hubic_account.nb_backup_file);
                $('#nb_keep_backup_file_hubic').attr('data-origin', data.hubic_account.nb_backup_file);

                $('#nb_keep_backup_base_hubic').val(data.hubic_account.nb_backup_base);
                $('#nb_keep_backup_base_hubic').attr('data-origin', data.hubic_account.nb_backup_base);

                $('#hubic_dir').val(data.hubic_account.directory);
                $('#hubic_dir').attr('data-origin', data.hubic_account.directory);

                if (parseInt(data.hubic_account.active) === 1) {
                    $('#active_hubic_on').prop('checked', true);
                    $('#active_hubic_on').attr('checked', 'checked');
                    $('#active_hubic_on').attr('data-origin', '1');
                    $('#active_hubic_off').attr('data-origin', '1');
                }

                $('#check_hubic').show();
			}

            return true;
		},"json"
	);
}

function displayAwsAccount(id_ntbr_aws)
{
    initAwsAccount();

    selectAwsTab(id_ntbr_aws);

    if (parseInt(id_ntbr_aws) === 0) {
        return true;
    }

	return $.post(
		admin_link_ntbr,
		'display_aws_account=1'
        +'&id_ntbr_aws='+encodeURIComponent(id_ntbr_aws),
		function(data)
		{
			if(data.aws_account && data.aws_account.id_ntbr_aws)
			{
                $('#id_ntbr_aws').val(data.aws_account.id_ntbr_aws);
                $('#id_ntbr_aws').attr('data-origin', data.aws_account.id_ntbr_aws);

                $('#aws_name').val(data.aws_account.name);
                $('#aws_name').attr('data-origin', data.aws_account.name);

                $('#nb_keep_backup_aws').val(data.aws_account.nb_backup);
                $('#nb_keep_backup_aws').attr('data-origin', data.aws_account.nb_backup);

                $('#nb_keep_backup_file_aws').val(data.aws_account.nb_backup_file);
                $('#nb_keep_backup_file_aws').attr('data-origin', data.aws_account.nb_backup_file);

                $('#nb_keep_backup_base_aws').val(data.aws_account.nb_backup_base);
                $('#nb_keep_backup_base_aws').attr('data-origin', data.aws_account.nb_backup_base);

                $('#aws_directory_path').val(data.aws_account.directory_path);
                $('#aws_directory_path').attr('data-origin', data.aws_account.directory_path);

                $('#aws_directory_key').val(data.aws_account.directory_key);
                $('#aws_directory_key').attr('data-origin', data.aws_account.directory_key);

                /*$('#aws_access_key_id').val(data.aws_account.access_key_id);
                $('#aws_access_key_id').attr('data-origin', data.aws_account.access_key_id);

                $('#aws_secret_access_key').val(data.aws_account.secret_access_key);
                $('#aws_secret_access_key').attr('data-origin', data.aws_account.secret_access_key);*/

                $('#aws_region').val(data.aws_account.region);
                $('#aws_region').attr('data-origin', data.aws_account.region);

                $('#aws_bucket').val(data.aws_account.bucket);
                $('#aws_bucket').attr('data-origin', data.aws_account.bucket);

                if (parseInt(data.aws_account.active) === 1) {
                    $('#active_aws_on').prop('checked', true);
                    $('#active_aws_on').attr('checked', 'checked');
                    $('#active_aws_on').attr('data-origin', '1');
                    $('#active_aws_off').attr('data-origin', '1');
                }

                $('#check_aws').show();
			}

            return true;
		},"json"
	);
}

function saveFtp(display_result)
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    if (typeof display_result === 'undefined') {
        display_result = 1;
    }

    ftp_save_result     = '';
    var id_ntbr_ftp     = parseInt($('#id_ntbr_ftp').val());
    var name            = $('#ftp_name').val();
    var active          = 0;
    var sftp            = 0;
    var ssl             = 0;
    var passive_mode    = 0;
    var nb_backup       = $('#nb_keep_backup_ftp').val();
    var nb_backup_file  = $('#nb_keep_backup_file_ftp').val();
    var nb_backup_base  = $('#nb_keep_backup_base_ftp').val();
    var server          = $('#ftp_server').val();
    var login           = $('#ftp_login').val();
    var password        = $('#ftp_pass').val();
    var port            = $('#ftp_port').val();
    var directory       = $('#ftp_dir').val();

    if($('#active_ftp_on').is(':checked')) {
		active = 1;
    }

    if($('#send_sftp_on').is(':checked')) {
		sftp = 1;
    }

    if($('#ftp_ssl_on').is(':checked')) {
		ssl = 1;
    }

    if($('#ftp_pasv_on').is(':checked')) {
		passive_mode = 1;
    }

	return $.post(
		admin_link_ntbr,
		'save_ftp=1'
        +'&id_ntbr_ftp='+encodeURIComponent(id_ntbr_ftp)
        +'&name='+encodeURIComponent(name)
        +'&active='+encodeURIComponent(active)
        +'&sftp='+encodeURIComponent(sftp)
        +'&ssl='+encodeURIComponent(ssl)
        +'&passive_mode='+encodeURIComponent(passive_mode)
        +'&nb_backup='+encodeURIComponent(nb_backup)
        +'&nb_backup_file='+encodeURIComponent(nb_backup_file)
        +'&nb_backup_base='+encodeURIComponent(nb_backup_base)
        +'&server='+encodeURIComponent(server)
        +'&login='+encodeURIComponent(login)
        +'&password='+encodeURIComponent(password)
        +'&port='+encodeURIComponent(port)
        +'&directory='+encodeURIComponent(directory),
		function(data)
		{
            if (data.result) {
                var result = data.result;

                if (result.success && parseInt(result.success) === 1 && data.id_ntbr_ftp) {
                    if (parseInt(display_result) === 1) {
                        $('#result .confirm.alert.alert-success').html('<p>' + save_account_success + '</p>').show();
                    }

                    if (parseInt(data.id_ntbr_ftp) !== id_ntbr_ftp) {
                        $('#id_ntbr_ftp').val(data.id_ntbr_ftp);
                        $('#ftp_tabs').append('<button type="button" class="btn btn-default choose_ftp_account" id="ftp_account_'+data.id_ntbr_ftp+'" value="'+data.id_ntbr_ftp+'">'+name+'</button>');

                        $('#ftp_account_'+data.id_ntbr_ftp).click(function(){
                            if (checkFormChanged(ftp_account_id)) {
                                if (confirm(confirm_change_account) == true) {
                                    displayFtpAccount($(this).val());
                                } else {
                                    selectFtpTab($('#id_ntbr_ftp').val());
                                }
                            } else {
                                displayFtpAccount($(this).val());
                            }
                        });
                    }

                    if (parseInt(active) === 1) {
                        $('#ftp_account_'+data.id_ntbr_ftp).removeClass('disable').addClass('enable');
                    } else {
                        $('#ftp_account_'+data.id_ntbr_ftp).removeClass('enable').addClass('disable');
                    }

                    displayFtpAccount(data.id_ntbr_ftp);
                } else {
                    var html_error = '';
                    html_error += '<p>' + save_account_error + '</p>';
                    if(result.errors)
                    {
                        html_error += '<ul>';
                        $.each(result.errors, function(key, error)
                        {
                            html_error += '<li>' + error + '</li>';
                        });
                        html_error += '</ul>';
                    }
                    if (parseInt(display_result) === 1) {
                        $('#result .error.alert.alert-danger').html(html_error).show();
                    }

                    ftp_save_result = html_error;
                }

                $('#loader_container').hide();

                $('html, body').animate({
                    scrollTop: 0
                }, 1000);
            }
		},
        'json',
	);
}

function saveDropbox(display_result)
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    if (typeof display_result === 'undefined') {
        display_result = 1;
    }

    dropbox_save_result = '';
    var id_ntbr_dropbox = parseInt($('#id_ntbr_dropbox').val());
    var name            = $('#dropbox_name').val();
    var active          = 0;
    var nb_backup       = $('#nb_keep_backup_dropbox').val();
    var nb_backup_file  = $('#nb_keep_backup_file_dropbox').val();
    var nb_backup_base  = $('#nb_keep_backup_base_dropbox').val();
    var code            = $('#dropbox_code').val();
    var directory       = $('#dropbox_dir').val();

    if($('#active_dropbox_on').is(':checked')) {
		active = 1;
    }

	return $.post(
		admin_link_ntbr,
		'save_dropbox=1'
        +'&id_ntbr_dropbox='+encodeURIComponent(id_ntbr_dropbox)
        +'&name='+encodeURIComponent(name)
        +'&active='+encodeURIComponent(active)
        +'&nb_backup='+encodeURIComponent(nb_backup)
        +'&nb_backup_file='+encodeURIComponent(nb_backup_file)
        +'&nb_backup_base='+encodeURIComponent(nb_backup_base)
        +'&code='+encodeURIComponent(code)
        +'&directory='+encodeURIComponent(directory),
		function(data)
		{
			if (data.result) {
                var result = data.result;

                if (result.success && parseInt(result.success) === 1 && data.id_ntbr_dropbox) {
                    if (parseInt(display_result) === 1) {
                        $('#result .confirm.alert.alert-success').html('<p>' + save_account_success + '</p>').show();
                    }

                    $('#dropbox_code').val('');
                    $('#dropbox_code').attr('data-origin', '');

                    if (parseInt(data.id_ntbr_dropbox) !== id_ntbr_dropbox) {
                        $('#id_ntbr_dropbox').val(data.id_ntbr_dropbox);
                        $('#dropbox_tabs').append('<button type="button" class="btn btn-default choose_dropbox_account" id="dropbox_account_'+data.id_ntbr_dropbox+'" value="'+data.id_ntbr_dropbox+'">'+name+'</button>');

                        $('#dropbox_account_'+data.id_ntbr_dropbox).click(function(){
                            if (checkFormChanged(dropbox_account_id)) {
                                if (confirm(confirm_change_account) == true) {
                                    displayDropboxAccount($(this).val());
                                } else {
                                    selectDropboxTab($('#id_ntbr_dropbox').val());
                                }
                            } else {
                                displayDropboxAccount($(this).val());
                            }
                        });
                    }

                    if (parseInt(active) === 1) {
                        $('#dropbox_account_'+data.id_ntbr_dropbox).removeClass('disable').addClass('enable');
                    } else {
                        $('#dropbox_account_'+data.id_ntbr_dropbox).removeClass('enable').addClass('disable');
                    }

                    displayDropboxAccount(data.id_ntbr_dropbox);
                } else {
                    var html_error = '';
                    html_error += '<p>' + save_account_error + '</p>';
                    if(result.errors)
                    {
                        html_error += '<ul>';
                        $.each(result.errors, function(key, error)
                        {
                            html_error += '<li>' + error + '</li>';
                        });
                        html_error += '</ul>';
                    }
                    if (parseInt(display_result) === 1) {
                        $('#result .error.alert.alert-danger').html(html_error).show();
                    }
                    dropbox_save_result = html_error;
                }

                $('#loader_container').hide();

                $('html, body').animate({
                    scrollTop: 0
                }, 1000);
            }
		},"json"
	);
}

function saveOwncloud(display_result)
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    if (typeof display_result === 'undefined') {
        display_result = 1;
    }

    owncloud_save_result    = '';
    var id_ntbr_owncloud    = parseInt($('#id_ntbr_owncloud').val());
    var name                = $('#owncloud_name').val();
    var active              = 0;
    var nb_backup           = $('#nb_keep_backup_owncloud').val();
    var nb_backup_file      = $('#nb_keep_backup_file_owncloud').val();
    var nb_backup_base      = $('#nb_keep_backup_base_owncloud').val();
    var login               = $('#owncloud_user').val();
    var password            = $('#owncloud_pass').val();
    var server              = $('#owncloud_server').val();
    var directory           = $('#owncloud_dir').val();

    if($('#active_owncloud_on').is(':checked')) {
		active = 1;
    }

	return $.post(
		admin_link_ntbr,
		'save_owncloud=1'
        +'&id_ntbr_owncloud='+encodeURIComponent(id_ntbr_owncloud)
        +'&name='+encodeURIComponent(name)
        +'&active='+encodeURIComponent(active)
        +'&nb_backup='+encodeURIComponent(nb_backup)
        +'&nb_backup_file='+encodeURIComponent(nb_backup_file)
        +'&nb_backup_base='+encodeURIComponent(nb_backup_base)
        +'&login='+encodeURIComponent(login)
        +'&password='+encodeURIComponent(password)
        +'&server='+encodeURIComponent(server)
        +'&directory='+encodeURIComponent(directory),
		function(data)
		{
			if (data.result) {
                var result = data.result;

                if (result.success && parseInt(result.success) === 1 && data.id_ntbr_owncloud) {
                    if (parseInt(display_result) === 1) {
                        $('#result .confirm.alert.alert-success').html('<p>' + save_account_success + '</p>').show();
                    }

                    if (parseInt(data.id_ntbr_owncloud) !== id_ntbr_owncloud) {
                        $('#id_ntbr_owncloud').val(data.id_ntbr_owncloud);
                        $('#owncloud_tabs').append('<button type="button" class="btn btn-default choose_owncloud_account" id="owncloud_account_'+data.id_ntbr_owncloud+'" value="'+data.id_ntbr_owncloud+'">'+name+'</button>');

                        $('#owncloud_account_'+data.id_ntbr_owncloud).click(function(){
                            if (checkFormChanged(owncloud_account_id)) {
                                if (confirm(confirm_change_account) == true) {
                                    displayOwncloudAccount($(this).val());
                                } else {
                                    selectOwncloudTab($('#id_ntbr_owncloud').val());
                                }
                            } else {
                                displayOwncloudAccount($(this).val());
                            }
                        });
                    }

                    if (parseInt(active) === 1) {
                        $('#owncloud_account_'+data.id_ntbr_owncloud).removeClass('disable').addClass('enable');
                    } else {
                        $('#owncloud_account_'+data.id_ntbr_owncloud).removeClass('enable').addClass('disable');
                    }

                    displayOwncloudAccount(data.id_ntbr_owncloud);
                } else {
                    var html_error = '';
                    html_error += '<p>' + save_account_error + '</p>';
                    if(result.errors)
                    {
                        html_error += '<ul>';
                        $.each(result.errors, function(key, error)
                        {
                            html_error += '<li>' + error + '</li>';
                        });
                        html_error += '</ul>';
                    }
                    if (parseInt(display_result) === 1) {
                        $('#result .error.alert.alert-danger').html(html_error).show();
                    }

                    owncloud_save_result = html_error;
                }

                $('#loader_container').hide();

                $('html, body').animate({
                    scrollTop: 0
                }, 1000);
            }
		},"json"
	);
}

function saveWebdav(display_result)
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    if (typeof display_result === 'undefined') {
        display_result = 1;
    }

    webdav_save_result    = '';
    var id_ntbr_webdav    = parseInt($('#id_ntbr_webdav').val());
    var name                = $('#webdav_name').val();
    var active              = 0;
    var nb_backup           = $('#nb_keep_backup_webdav').val();
    var nb_backup_file      = $('#nb_keep_backup_file_webdav').val();
    var nb_backup_base      = $('#nb_keep_backup_base_webdav').val();
    var login               = $('#webdav_user').val();
    var password            = $('#webdav_pass').val();
    var server              = $('#webdav_server').val();
    var directory           = $('#webdav_dir').val();

    if($('#active_webdav_on').is(':checked')) {
		active = 1;
    }

	return $.post(
		admin_link_ntbr,
		'save_webdav=1'
        +'&id_ntbr_webdav='+encodeURIComponent(id_ntbr_webdav)
        +'&name='+encodeURIComponent(name)
        +'&active='+encodeURIComponent(active)
        +'&nb_backup='+encodeURIComponent(nb_backup)
        +'&nb_backup_file='+encodeURIComponent(nb_backup_file)
        +'&nb_backup_base='+encodeURIComponent(nb_backup_base)
        +'&login='+encodeURIComponent(login)
        +'&password='+encodeURIComponent(password)
        +'&server='+encodeURIComponent(server)
        +'&directory='+encodeURIComponent(directory),
		function(data)
		{
			if (data.result) {
                var result = data.result;

                if (result.success && parseInt(result.success) === 1 && data.id_ntbr_webdav) {
                    if (parseInt(display_result) === 1) {
                        $('#result .confirm.alert.alert-success').html('<p>' + save_account_success + '</p>').show();
                    }

                    if (parseInt(data.id_ntbr_webdav) !== id_ntbr_webdav) {
                        $('#id_ntbr_webdav').val(data.id_ntbr_webdav);
                        $('#webdav_tabs').append('<button type="button" class="btn btn-default choose_webdav_account" id="webdav_account_'+data.id_ntbr_webdav+'" value="'+data.id_ntbr_webdav+'">'+name+'</button>');

                        $('#webdav_account_'+data.id_ntbr_webdav).click(function(){
                            if (checkFormChanged(webdav_account_id)) {
                                if (confirm(confirm_change_account) == true) {
                                    displayWebdavAccount($(this).val());
                                } else {
                                    selectWebdavTab($('#id_ntbr_webdav').val());
                                }
                            } else {
                                displayWebdavAccount($(this).val());
                            }
                        });
                    }

                    if (parseInt(active) === 1) {
                        $('#webdav_account_'+data.id_ntbr_webdav).removeClass('disable').addClass('enable');
                    } else {
                        $('#webdav_account_'+data.id_ntbr_webdav).removeClass('enable').addClass('disable');
                    }

                    displayWebdavAccount(data.id_ntbr_webdav);
                } else {
                    var html_error = '';
                    html_error += '<p>' + save_account_error + '</p>';
                    if(result.errors)
                    {
                        html_error += '<ul>';
                        $.each(result.errors, function(key, error)
                        {
                            html_error += '<li>' + error + '</li>';
                        });
                        html_error += '</ul>';
                    }
                    if (parseInt(display_result) === 1) {
                        $('#result .error.alert.alert-danger').html(html_error).show();
                    }

                    webdav_save_result = html_error;
                }

                $('#loader_container').hide();

                $('html, body').animate({
                    scrollTop: 0
                }, 1000);
            }
		},"json"
	);
}

function saveGoogledrive(display_result)
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    if (typeof display_result === 'undefined') {
        display_result = 1;
    }

    googledrive_save_result = '';
    var id_ntbr_googledrive = parseInt($('#id_ntbr_googledrive').val());
    var name                = $('#googledrive_name').val();
    var active              = 0;
    var nb_backup           = $('#nb_keep_backup_googledrive').val();
    var nb_backup_file      = $('#nb_keep_backup_file_googledrive').val();
    var nb_backup_base      = $('#nb_keep_backup_base_googledrive').val();
    var code                = $('#googledrive_code').val();
    var directory_path      = $('#googledrive_dir_path').val();
    var directory_key       = $('#googledrive_dir').val();

    if($('#active_googledrive_on').is(':checked')) {
		active = 1;
    }

	return $.post(
		admin_link_ntbr,
		'save_googledrive=1'
        +'&id_ntbr_googledrive='+encodeURIComponent(id_ntbr_googledrive)
        +'&name='+encodeURIComponent(name)
        +'&active='+encodeURIComponent(active)
        +'&nb_backup='+encodeURIComponent(nb_backup)
        +'&nb_backup_file='+encodeURIComponent(nb_backup_file)
        +'&nb_backup_base='+encodeURIComponent(nb_backup_base)
        +'&code='+encodeURIComponent(code)
        +'&directory_path='+encodeURIComponent(directory_path)
        +'&directory_key='+encodeURIComponent(directory_key),
		function(data)
		{
			if (data.result) {
                var result = data.result;

                if (result.success && parseInt(result.success) === 1 && data.id_ntbr_googledrive) {
                    if (parseInt(display_result) === 1) {
                        $('#result .confirm.alert.alert-success').html('<p>' + save_account_success + '</p>').show();
                    }

                    $('#googledrive_code').val('');
                    $('#googledrive_code').attr('data-origin', '');

                    if (parseInt(data.id_ntbr_googledrive) !== id_ntbr_googledrive) {
                        $('#id_ntbr_googledrive').val(data.id_ntbr_googledrive);
                        $('#googledrive_tabs').append('<button type="button" class="btn btn-default choose_googledrive_account" id="googledrive_account_'+data.id_ntbr_googledrive+'" value="'+data.id_ntbr_googledrive+'">'+name+'</button>');

                        $('#googledrive_account_'+data.id_ntbr_googledrive).click(function(){
                            if (checkFormChanged(googledrive_account_id)) {
                                if (confirm(confirm_change_account) == true) {
                                    displayGoogledriveAccount($(this).val());
                                } else {
                                    selectGoogledriveTab($('#id_ntbr_googledrive').val());
                                }
                            } else {
                                displayGoogledriveAccount($(this).val());
                            }
                        });
                    }

                    if (parseInt(active) === 1) {
                        $('#googledrive_account_'+data.id_ntbr_googledrive).removeClass('disable').addClass('enable');
                    } else {
                        $('#googledrive_account_'+data.id_ntbr_googledrive).removeClass('enable').addClass('disable');
                    }

                    displayGoogledriveAccount(data.id_ntbr_googledrive);
                } else {
                    var html_error = '';
                    html_error += '<p>' + save_account_error + '</p>';
                    if(result.errors)
                    {
                        html_error += '<ul>';
                        $.each(result.errors, function(key, error)
                        {
                            html_error += '<li>' + error + '</li>';
                        });
                        html_error += '</ul>';
                    }
                    if (parseInt(display_result) === 1) {
                        $('#result .error.alert.alert-danger').html(html_error).show();
                    }
                    googledrive_save_result = html_error;
                }

                $('#loader_container').hide();

                $('html, body').animate({
                    scrollTop: 0
                }, 1000);
            }
		},"json"
	);
}

function saveOnedrive(display_result)
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    if (typeof display_result === 'undefined') {
        display_result = 1;
    }

    onedrive_save_result    = '';
    var id_ntbr_onedrive    = parseInt($('#id_ntbr_onedrive').val());
    var name                = $('#onedrive_name').val();
    var active              = 0;
    var nb_backup           = $('#nb_keep_backup_onedrive').val();
    var nb_backup_file      = $('#nb_keep_backup_file_onedrive').val();
    var nb_backup_base      = $('#nb_keep_backup_base_onedrive').val();
    var code                = $('#onedrive_code').val();
    var directory_path      = $('#onedrive_dir_path').val();
    var directory_key       = $('#onedrive_dir').val();

    if($('#active_onedrive_on').is(':checked')) {
		active = 1;
    }

	return $.post(
		admin_link_ntbr,
		'save_onedrive=1'
        +'&id_ntbr_onedrive='+encodeURIComponent(id_ntbr_onedrive)
        +'&name='+encodeURIComponent(name)
        +'&active='+encodeURIComponent(active)
        +'&nb_backup='+encodeURIComponent(nb_backup)
        +'&nb_backup_file='+encodeURIComponent(nb_backup_file)
        +'&nb_backup_base='+encodeURIComponent(nb_backup_base)
        +'&code='+encodeURIComponent(code)
        +'&directory_path='+encodeURIComponent(directory_path)
        +'&directory_key='+encodeURIComponent(directory_key),
		function(data)
		{
			if (data.result) {
                var result = data.result;

                if (result.success && parseInt(result.success) === 1 && data.id_ntbr_onedrive) {
                    if (parseInt(display_result) === 1) {
                        $('#result .confirm.alert.alert-success').html('<p>' + save_account_success + '</p>').show();
                    }

                    $('#onedrive_code').val('');
                    $('#onedrive_code').attr('data-origin', '');

                    if (parseInt(data.id_ntbr_onedrive) !== id_ntbr_onedrive) {
                        $('#id_ntbr_onedrive').val(data.id_ntbr_onedrive);
                        $('#onedrive_tabs').append('<button type="button" class="btn btn-default choose_onedrive_account" id="onedrive_account_'+data.id_ntbr_onedrive+'" value="'+data.id_ntbr_onedrive+'">'+name+'</button>');

                        $('#onedrive_account_'+data.id_ntbr_onedrive).click(function(){
                            if (checkFormChanged(onedrive_account_id)) {
                                if (confirm(confirm_change_account) == true) {
                                    displayOnedriveAccount($(this).val());
                                } else {
                                    selectOnedriveTab($('#id_ntbr_onedrive').val());
                                }
                            } else {
                                displayOnedriveAccount($(this).val());
                            }
                        });
                    }

                    if (parseInt(active) === 1) {
                        $('#onedrive_account_'+data.id_ntbr_onedrive).removeClass('disable').addClass('enable');
                    } else {
                        $('#onedrive_account_'+data.id_ntbr_onedrive).removeClass('enable').addClass('disable');
                    }

                    displayOnedriveAccount(data.id_ntbr_onedrive);
                } else {
                    var html_error = '';
                    html_error += '<p>' + save_account_error + '</p>';
                    if(result.errors)
                    {
                        html_error += '<ul>';
                        $.each(result.errors, function(key, error)
                        {
                            html_error += '<li>' + error + '</li>';
                        });
                        html_error += '</ul>';
                    }
                    if (parseInt(display_result) === 1) {
                        $('#result .error.alert.alert-danger').html(html_error).show();
                    }
                    onedrive_save_result = html_error;
                }

                $('#loader_container').hide();

                $('html, body').animate({
                    scrollTop: 0
                }, 1000);
            }
		},"json"
	);
}

function saveHubic(display_result)
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    if (typeof display_result === 'undefined') {
        display_result = 1;
    }

    hubic_save_result   = '';
    var id_ntbr_hubic   = parseInt($('#id_ntbr_hubic').val());
    var name            = $('#hubic_name').val();
    var active          = 0;
    var nb_backup       = $('#nb_keep_backup_hubic').val();
    var nb_backup_file  = $('#nb_keep_backup_file_hubic').val();
    var nb_backup_base  = $('#nb_keep_backup_base_hubic').val();
    var code            = $('#hubic_code').val();
    var directory       = $('#hubic_dir').val();

    if($('#active_hubic_on').is(':checked')) {
		active = 1;
    }

	return $.post(
		admin_link_ntbr,
		'save_hubic=1'
        +'&id_ntbr_hubic='+encodeURIComponent(id_ntbr_hubic)
        +'&name='+encodeURIComponent(name)
        +'&active='+encodeURIComponent(active)
        +'&nb_backup='+encodeURIComponent(nb_backup)
        +'&nb_backup_file='+encodeURIComponent(nb_backup_file)
        +'&nb_backup_base='+encodeURIComponent(nb_backup_base)
        +'&code='+encodeURIComponent(code)
        +'&directory='+encodeURIComponent(directory),
		function(data)
		{
			if (data.result) {
                var result = data.result;

                if (result.success && parseInt(result.success) === 1 && data.id_ntbr_hubic) {
                    if (parseInt(display_result) === 1) {
                        $('#result .confirm.alert.alert-success').html('<p>' + save_account_success + '</p>').show();
                    }

                    $('#hubic_code').val('');
                    $('#hubic_code').attr('data-origin', '');

                    if (parseInt(data.id_ntbr_hubic) !== id_ntbr_hubic) {
                        $('#id_ntbr_hubic').val(data.id_ntbr_hubic);
                        $('#hubic_tabs').append('<button type="button" class="btn btn-default choose_hubic_account" id="hubic_account_'+data.id_ntbr_hubic+'" value="'+data.id_ntbr_hubic+'">'+name+'</button>');

                        $('#hubic_account_'+data.id_ntbr_hubic).click(function(){
                            if (checkFormChanged(hubic_account_id)) {
                                if (confirm(confirm_change_account) == true) {
                                    displayHubicAccount($(this).val());
                                } else {
                                    selectHubicTab($('#id_ntbr_hubic').val());
                                }
                            } else {
                                displayHubicAccount($(this).val());
                            }
                        });
                    }

                    if (parseInt(active) === 1) {
                        $('#hubic_account_'+data.id_ntbr_hubic).removeClass('disable').addClass('enable');
                    } else {
                        $('#hubic_account_'+data.id_ntbr_hubic).removeClass('enable').addClass('disable');
                    }

                    displayHubicAccount(data.id_ntbr_hubic);
                } else {
                    var html_error = '';
                    html_error += '<p>' + save_account_error + '</p>';
                    if(result.errors)
                    {
                        html_error += '<ul>';
                        $.each(result.errors, function(key, error)
                        {
                            html_error += '<li>' + error + '</li>';
                        });
                        html_error += '</ul>';
                    }
                    if (parseInt(display_result) === 1) {
                        $('#result .error.alert.alert-danger').html(html_error).show();
                    }
                    hubic_save_result = html_error;
                }

                $('#loader_container').hide();

                $('html, body').animate({
                    scrollTop: 0
                }, 1000);
            }
		},"json"
	);
}

function saveAws(display_result)
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    if (typeof display_result === 'undefined') {
        display_result = 1;
    }

    aws_save_result         = '';
    var id_ntbr_aws         = parseInt($('#id_ntbr_aws').val());
    var name                = $('#aws_name').val();
    var active              = 0;
    var nb_backup           = $('#nb_keep_backup_aws').val();
    var nb_backup_file      = $('#nb_keep_backup_file_aws').val();
    var nb_backup_base      = $('#nb_keep_backup_base_aws').val();
    var directory_key       = $('#aws_directory_key').val();
    var directory_path      = $('#aws_directory_path').val();
    var access_key_id       = $('#aws_access_key_id').val();
    var secret_access_key   = $('#aws_secret_access_key').val();
    var region              = $('#aws_region').val();
    var bucket              = $('#aws_bucket').val();

    if($('#active_aws_on').is(':checked')) {
		active = 1;
    }

	return $.post(
		admin_link_ntbr,
		'save_aws=1'
        +'&id_ntbr_aws='+encodeURIComponent(id_ntbr_aws)
        +'&name='+encodeURIComponent(name)
        +'&active='+encodeURIComponent(active)
        +'&nb_backup='+encodeURIComponent(nb_backup)
        +'&nb_backup_file='+encodeURIComponent(nb_backup_file)
        +'&nb_backup_base='+encodeURIComponent(nb_backup_base)
        +'&directory_path='+encodeURIComponent(directory_path)
        +'&directory_key='+encodeURIComponent(directory_key)
        +'&access_key_id='+encodeURIComponent(access_key_id)
        +'&secret_access_key='+encodeURIComponent(secret_access_key)
        +'&region='+encodeURIComponent(region)
        +'&bucket='+encodeURIComponent(bucket),
		function(data)
		{
			if (data.result) {
                var result = data.result;

                if (result.success && parseInt(result.success) === 1 && data.id_ntbr_aws) {
                    if (parseInt(display_result) === 1) {
                        $('#result .confirm.alert.alert-success').html('<p>' + save_account_success + '</p>').show();
                    }

                    $('#aws_access_key_id').val('');
                    $('#aws_access_key_id').attr('data-origin', '');
                    $('#aws_secret_access_key').val('');
                    $('#aws_secret_access_key').attr('data-origin', '');

                    if (parseInt(data.id_ntbr_aws) !== id_ntbr_aws) {
                        $('#id_ntbr_aws').val(data.id_ntbr_aws);
                        $('#aws_tabs').append('<button type="button" class="btn btn-default choose_aws_account" id="aws_account_'+data.id_ntbr_aws+'" value="'+data.id_ntbr_aws+'">'+name+'</button>');

                        $('#aws_account_'+data.id_ntbr_aws).click(function(){
                            if (checkFormChanged(aws_account_id)) {
                                if (confirm(confirm_change_account) == true) {
                                    displayAwsAccount($(this).val());
                                } else {
                                    selectAwsTab($('#id_ntbr_aws').val());
                                }
                            } else {
                                displayAwsAccount($(this).val());
                            }
                        });
                    }

                    if (parseInt(active) === 1) {
                        $('#aws_account_'+data.id_ntbr_aws).removeClass('disable').addClass('enable');
                    } else {
                        $('#aws_account_'+data.id_ntbr_aws).removeClass('enable').addClass('disable');
                    }

                    displayAwsAccount(data.id_ntbr_aws);
                } else {
                    var html_error = '';
                    html_error += '<p>' + save_account_error + '</p>';
                    if(result.errors)
                    {
                        html_error += '<ul>';
                        $.each(result.errors, function(key, error)
                        {
                            html_error += '<li>' + error + '</li>';
                        });
                        html_error += '</ul>';
                    }
                    if (parseInt(display_result) === 1) {
                        $('#result .error.alert.alert-danger').html(html_error).show();
                    }
                    aws_save_result = html_error;
                }

                $('#loader_container').hide();

                $('html, body').animate({
                    scrollTop: 0
                }, 1000);
            }
		},"json"
	);
}

function checkConnectionFtp()
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    var id_ntbr_ftp = parseInt($('#id_ntbr_ftp').val());

	$.post(
		admin_link_ntbr,
		'check_connection_ftp=1'
        +'&id_ntbr_ftp='+encodeURIComponent(id_ntbr_ftp),
		function(data)
		{
			if (data.success && parseInt(data.success) === 1) {
                $('#result .confirm.alert.alert-success').html('<p>' + check_connection_success + '</p>').show();
            } else {
                $('#result .error.alert.alert-danger').html('<p>' + check_connection_error + '</p>').show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
		},"json"
	);
}

function checkConnectionDropbox()
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    var id_ntbr_dropbox = parseInt($('#id_ntbr_dropbox').val());

	$.post(
		admin_link_ntbr,
		'check_connection_dropbox=1'
        +'&id_ntbr_dropbox='+encodeURIComponent(id_ntbr_dropbox),
		function(data)
		{
			if (data.success && parseInt(data.success) === 1) {
                $('#result .confirm.alert.alert-success').html('<p>' + check_connection_success + '</p>').show();
            } else {
                $('#result .error.alert.alert-danger').html('<p>' + check_connection_error + '</p>').show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
		},"json"
	);
}

function checkConnectionOwncloud()
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    var id_ntbr_owncloud = parseInt($('#id_ntbr_owncloud').val());

	$.post(
		admin_link_ntbr,
		'check_connection_owncloud=1'
        +'&id_ntbr_owncloud='+encodeURIComponent(id_ntbr_owncloud),
		function(data)
		{
			if (data.success && parseInt(data.success) === 1) {
                $('#result .confirm.alert.alert-success').html('<p>' + check_connection_success + '</p>').show();
            } else {
                $('#result .error.alert.alert-danger').html('<p>' + check_connection_error + '</p>').show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
		},"json"
	);
}

function checkConnectionWebdav()
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    var id_ntbr_webdav = parseInt($('#id_ntbr_webdav').val());

	$.post(
		admin_link_ntbr,
		'check_connection_webdav=1'
        +'&id_ntbr_webdav='+encodeURIComponent(id_ntbr_webdav),
		function(data)
		{
			if (data.success && parseInt(data.success) === 1) {
                $('#result .confirm.alert.alert-success').html('<p>' + check_connection_success + '</p>').show();
            } else {
                $('#result .error.alert.alert-danger').html('<p>' + check_connection_error + '</p>').show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
		},"json"
	);
}

function checkConnectionGoogledrive()
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    var id_ntbr_googledrive = parseInt($('#id_ntbr_googledrive').val());

	$.post(
		admin_link_ntbr,
		'check_connection_googledrive=1'
        +'&id_ntbr_googledrive='+encodeURIComponent(id_ntbr_googledrive),
		function(data)
		{
			if (data.success && parseInt(data.success) === 1) {
                $('#result .confirm.alert.alert-success').html('<p>' + check_connection_success + '</p>').show();
            } else {
                $('#result .error.alert.alert-danger').html('<p>' + check_connection_error + '</p>').show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
		},"json"
	);
}

function checkConnectionOnedrive()
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    var id_ntbr_onedrive = parseInt($('#id_ntbr_onedrive').val());

	$.post(
		admin_link_ntbr,
		'check_connection_onedrive=1'
        +'&id_ntbr_onedrive='+encodeURIComponent(id_ntbr_onedrive),
		function(data)
		{
			if (data.success && parseInt(data.success) === 1) {
                $('#result .confirm.alert.alert-success').html('<p>' + check_connection_success + '</p>').show();
            } else {
                $('#result .error.alert.alert-danger').html('<p>' + check_connection_error + '</p>').show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
		},"json"
	);
}

function checkConnectionHubic()
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    var id_ntbr_hubic = parseInt($('#id_ntbr_hubic').val());

	$.post(
		admin_link_ntbr,
		'check_connection_hubic=1'
        +'&id_ntbr_hubic='+encodeURIComponent(id_ntbr_hubic),
		function(data)
		{
			if (data.success && parseInt(data.success) === 1) {
                $('#result .confirm.alert.alert-success').html('<p>' + check_connection_success + '</p>').show();
            } else {
                $('#result .error.alert.alert-danger').html('<p>' + check_connection_error + '</p>').show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
		},"json"
	);
}

function checkConnectionAws()
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    var id_ntbr_aws = parseInt($('#id_ntbr_aws').val());

	$.post(
		admin_link_ntbr,
		'check_connection_aws=1'
        +'&id_ntbr_aws='+encodeURIComponent(id_ntbr_aws),
		function(data)
		{
			if (data.success && parseInt(data.success) === 1) {
                $('#result .confirm.alert.alert-success').html('<p>' + check_connection_success + '</p>').show();
            } else {
                $('#result .error.alert.alert-danger').html('<p>' + check_connection_error + '</p>').show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
		},"json"
	);
}

function deleteFtp()
{
    if(!confirm(confirm_delete_account)) {
        return;
    }

    $('#loader_container').show();
    $('#result div').html('').hide();

    var id_ntbr_ftp = parseInt($('#id_ntbr_ftp').val());

	$.post(
		admin_link_ntbr,
		'delete_ftp=1'
        +'&id_ntbr_ftp='+encodeURIComponent(id_ntbr_ftp),
		function(data)
		{
			if (data.success && parseInt(data.success) === 1) {
                $('#result .confirm.alert.alert-success').html('<p>' + delete_account_success + '</p>').show();
                $('#ftp_account_'+id_ntbr_ftp).remove();
                selectFtpTab(0);
                initFtpAccount();
            } else {
                $('#result .error.alert.alert-danger').html('<p>' + delete_account_error + '</p>').show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
		},"json"
	);
}

function deleteDropbox()
{
    if(!confirm(confirm_delete_account)) {
        return;
    }

    $('#loader_container').show();
    $('#result div').html('').hide();

    var id_ntbr_dropbox = parseInt($('#id_ntbr_dropbox').val());

	$.post(
		admin_link_ntbr,
		'delete_dropbox=1'
        +'&id_ntbr_dropbox='+encodeURIComponent(id_ntbr_dropbox),
		function(data)
		{
			if (data.success && parseInt(data.success) === 1) {
                $('#result .confirm.alert.alert-success').html('<p>' + delete_account_success + '</p>').show();
                $('#dropbox_account_'+id_ntbr_dropbox).remove();
                selectDropboxTab(0);
                initDropboxAccount();
            } else {
                $('#result .error.alert.alert-danger').html('<p>' + delete_account_error + '</p>').show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
		},"json"
	);
}

function deleteOwncloud()
{
    if(!confirm(confirm_delete_account)) {
        return;
    }

    $('#loader_container').show();
    $('#result div').html('').hide();

    var id_ntbr_owncloud = parseInt($('#id_ntbr_owncloud').val());

	$.post(
		admin_link_ntbr,
		'delete_owncloud=1'
        +'&id_ntbr_owncloud='+encodeURIComponent(id_ntbr_owncloud),
		function(data)
		{
			if (data.success && parseInt(data.success) === 1) {
                $('#result .confirm.alert.alert-success').html('<p>' + delete_account_success + '</p>').show();
                $('#owncloud_account_'+id_ntbr_owncloud).remove();
                selectOwncloudTab(0);
                initOwncloudAccount();
            } else {
                $('#result .error.alert.alert-danger').html('<p>' + delete_account_error + '</p>').show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
		},"json"
	);
}

function deleteWebdav()
{
    if(!confirm(confirm_delete_account)) {
        return;
    }

    $('#loader_container').show();
    $('#result div').html('').hide();

    var id_ntbr_webdav = parseInt($('#id_ntbr_webdav').val());

	$.post(
		admin_link_ntbr,
		'delete_webdav=1'
        +'&id_ntbr_webdav='+encodeURIComponent(id_ntbr_webdav),
		function(data)
		{
			if (data.success && parseInt(data.success) === 1) {
                $('#result .confirm.alert.alert-success').html('<p>' + delete_account_success + '</p>').show();
                $('#webdav_account_'+id_ntbr_webdav).remove();
                selectWebdavTab(0);
                initWebdavAccount();

            } else {
                $('#result .error.alert.alert-danger').html('<p>' + delete_account_error + '</p>').show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
		},"json"
	);
}

function deleteGoogledrive()
{
    if(!confirm(confirm_delete_account)) {
        return;
    }

    $('#loader_container').show();
    $('#result div').html('').hide();

    var id_ntbr_googledrive = parseInt($('#id_ntbr_googledrive').val());

	$.post(
		admin_link_ntbr,
		'delete_googledrive=1'
        +'&id_ntbr_googledrive='+encodeURIComponent(id_ntbr_googledrive),
		function(data)
		{
			if (data.success && parseInt(data.success) === 1) {
                $('#result .confirm.alert.alert-success').html('<p>' + delete_account_success + '</p>').show();
                $('#googledrive_account_'+id_ntbr_googledrive).remove();
                selectGoogledriveTab(0);
                initGoogledriveAccount();
            } else {
                $('#result .error.alert.alert-danger').html('<p>' + delete_account_error + '</p>').show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
		},"json"
	);
}

function deleteOnedrive()
{
    if(!confirm(confirm_delete_account)) {
        return;
    }

    $('#loader_container').show();
    $('#result div').html('').hide();

    var id_ntbr_onedrive = parseInt($('#id_ntbr_onedrive').val());

	$.post(
		admin_link_ntbr,
		'delete_onedrive=1'
        +'&id_ntbr_onedrive='+encodeURIComponent(id_ntbr_onedrive),
		function(data)
		{
			if (data.success && parseInt(data.success) === 1) {
                $('#result .confirm.alert.alert-success').html('<p>' + delete_account_success + '</p>').show();
                $('#onedrive_account_'+id_ntbr_onedrive).remove();
                selectOnedriveTab(0);
                initOnedriveAccount();
            } else {
                $('#result .error.alert.alert-danger').html('<p>' + delete_account_error + '</p>').show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
		},"json"
	);
}

function deleteHubic()
{
    if(!confirm(confirm_delete_account)) {
        return;
    }

    $('#loader_container').show();
    $('#result div').html('').hide();

    var id_ntbr_hubic = parseInt($('#id_ntbr_hubic').val());

	$.post(
		admin_link_ntbr,
		'delete_hubic=1'
        +'&id_ntbr_hubic='+encodeURIComponent(id_ntbr_hubic),
		function(data)
		{
			if (data.success && parseInt(data.success) === 1) {
                $('#result .confirm.alert.alert-success').html('<p>' + delete_account_success + '</p>').show();
                $('#hubic_account_'+id_ntbr_hubic).remove();
                selectHubicTab(0);
                initHubicAccount();
            } else {
                $('#result .error.alert.alert-danger').html('<p>' + delete_account_error + '</p>').show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
		},"json"
	);
}

function deleteAws()
{
    if(!confirm(confirm_delete_account)) {
        return;
    }

    $('#loader_container').show();
    $('#result div').html('').hide();

    var id_ntbr_aws = parseInt($('#id_ntbr_aws').val());

	$.post(
		admin_link_ntbr,
		'delete_aws=1'
        +'&id_ntbr_aws='+encodeURIComponent(id_ntbr_aws),
		function(data)
		{
			if (data.success && parseInt(data.success) === 1) {
                $('#result .confirm.alert.alert-success').html('<p>' + delete_account_success + '</p>').show();
                $('#aws_account_'+id_ntbr_aws).remove();
                selectAwsTab(0);
                initAwsAccount();
            } else {
                $('#result .error.alert.alert-danger').html('<p>' + delete_account_error + '</p>').show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
		},"json"
	);
}

function generateUrls()
{
	$('#download_links .backup_link').html('');
	$('#download_links .backup_log').html('');
	$('#download_links').hide();

	$.post(
		generate_urls,
		'id_shop_group='+id_shop_group+'&id_shop='+id_shop,
		function(data)
		{
			if(data.urls)
			{
				var backup_download_link = data.urls.backup;
				var log_download_link = data.urls.log;

				$('#download_links .backup_link').append('<a href="' + backup_download_link + '">' + backup_download_link + '</a>');
				$('#download_links .backup_log').append('<a href="' + log_download_link + '">' + log_download_link + '</a>');

				$('#download_links').show();
			}
		},"json"
	);
}

function createBackup()
{
	$.post(create_backup_ajax, function(data)
		{
			resultCreateBackup(data);
		},"json"
	);

	displayProgress();
}

function displayProgress()
{
    backup_warning = '';
	$('#backup_progress_panel').show();
	$('#create_backup').hide();
	$('#backup_progress').removeClass('error_progress');
	$('#backup_progress').removeClass('success_progress');
	$('#backup_progress').text('');

    /* Call the function every x seconde*/
	progressBackup = setInterval("displayProgressBackup()", time_between_progress_refresh);
}

function refreshBackup()
{
	$.post(refresh_backup_ajax, function(data)
		{
            resultCreateBackup(data);
		},"json"
	);
}

function resultCreateBackup(data)
{
    if (data) {
        if(typeof data.backuplist !== 'undefined')
        {
            var backups_list = displayBackupsList(data.backuplist);
            //$('#backup_files').html(data.backuplist);
            $('#backup_files').html(backups_list);
        }
        if(typeof data.warnings !== 'undefined' && data.warnings)
        {
            backup_warning += '<ul class="error_progress">';

            $.each(data.warnings, function(key, warning)
            {
                backup_warning += '<li>' + warning + '</li>';
            });

            backup_warning += '</ul>';
        }
    }
}

function displayProgressBackup()
{
	$('#result div').html('').hide();

	$.post(backup_progress, function( data )
	{
        if (data) {
			data = data.trim();
            //console.log(data);
            //console.log(refresh_sent);
            if (data === 'RESUME') {
                refresh_sent = 0;
                return;
            }

            if (data === 'REFRESH') {
                if (parseInt(refresh_sent) == 0 && parseInt(display_progress_only) == 0) {
                    refresh_sent = 1;
                    refreshBackup();
                }
            } else {
                refresh_sent = 0;
                var three_first_letters = data.substring(0,3);
                if(three_first_letters === 'ERR' || three_first_letters === 'END')
                {
                    clearInterval(progressBackup);

                    if(three_first_letters === 'ERR') {
                        $('#backup_progress').addClass('error_progress');
                        data = data.replace(three_first_letters, '');
                    }
                    else if(three_first_letters === 'END') {
                        $('#backup_progress').addClass('success_progress');
                        data = create_success;
                    }

                    data = '<p>' + data + '</p>';

                    //$('#delete_backup').show();
                    $('#create_backup').show();
                } else if(three_first_letters === 'WAR') {
                    data = data.replace(three_first_letters, '');
                }
                $('#backup_progress').html(data + backup_warning);
            }
        }
	});
}

function seeBackup(nb)
{
    $('#sub_backups'+nb).toggle();
    var icon_button = $('#backup' + nb + ' .backup_see i');
    if (icon_button.hasClass('fa-eye')) {
        icon_button.removeClass('fa-eye');
        icon_button.addClass('fa-eye-slash');
    } else {
        icon_button.removeClass('fa-eye-slash');
        icon_button.addClass('fa-eye');
    }
}

function sendBackup(nb)
{
	$.post(
		admin_link_ntbr,
		'send_backup=1'
		+ '&nb=' + nb,
		function(data)
		{
            resultCreateBackup(data);
		},"json"
	);

    displayProgress();
}

function saveCommentBackup(nb)
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    var backup_comment = $('#comment_backup_'+nb).val();
    var backup_name = $('#comment_backup_'+nb).parent().parent().find('.backup_name').text();

	$.post(
		admin_link_ntbr,
		'add_comment_backup=1'
		+'&backup_name=' + backup_name
		+'&backup_comment=' + backup_comment,
		function( data )
		{
			if(data.result !== 1 && data.result !== '1') {
				$('#result .error.alert.alert-danger').html('<p>' + add_comment_backup_error + '</p>').show();
			} else {
				$('#result .confirm.alert.alert-success').html('<p>' + add_comment_backup_success + '</p>').show();
			}

            $('#loader_container').hide();
		},"json"
	);
}

function deleteBackup(nb)
{
    $('#loader_container').show();
    $('#result div').html('').hide();
	/*$('#backup' + nb).hide();*/
	$.post(
		delete_backup,
		'nb=' + nb,
		function( data )
		{
			if(data.result.success !== 1 && data.result.success !== '1') {
				$('#result .error.alert.alert-danger').html('<p>' + delete_error + '</p>').show();
			} else {
				$('#result .confirm.alert.alert-success').html('<p>' + delete_success + '</p>').show();
			}

            // We update the files list
            if(data.result.update_list !== '-') {
                var backups_list = displayBackupsList(data.result.update_list);
                //$('#backup_files').html(data.result.update_list);
                $('#backup_files').html(backups_list);
            }

			$('#create_backup').show();
            $('#loader_container').hide();
		},"json"
	);
}

function displayBackupsList(backups)
{
    var backups_list  = '';

    $.each(backups, function(nb, backup) {
        backups_list += '<p id="backup'+nb+'">';
            backups_list += '<span class="backup_list_content_left">';

            if (backup.nb_part > 1) {
                backups_list += '<button type="button" title="'+list_backups_see+'" nb="'+nb+'" onclick="seeBackup(\''+nb+'\');" name="backup_see" class="backup_see btn btn-default">';
                    backups_list += '<i class="fas fa-eye"></i>';
                backups_list += '</button>';
            } else {
                backups_list += '<button type="button" title="'+list_backups_download+'" nb="'+nb+'" onclick="downloadFile(\'backup\', \''+nb+'\');" name="backup_download" class="backup_download btn btn-default">';
                    backups_list += '<i class="fas fa-download"></i>';
                backups_list += '</button>';
            }

            if (parseInt(light) == 0) {
                backups_list += '<button type="button" title="'+list_backups_send_away+'" nb="'+nb+'" onclick="sendBackup(\''+nb+'\');" name="send_backup" class="send_backup btn btn-default">';
                    backups_list += '<i class="fas fa-share"></i>';
                backups_list += '</button>';
            }

            backups_list += '<button type="button" title="'+list_backups_delete+'" nb="'+nb+'" onclick="deleteBackup(\''+nb+'\');" name="delete_backup" class="delete_backup btn btn-default">';
                backups_list += '<i class="fas fa-trash-alt"></i>';
            backups_list += '</button>';

            backups_list += '<span>'+backup.date+list_backups_colons+'</span> <span class="backup_name">'+backup.name+'</span> <span class="backup_size">('+backup.size+')</span>';

        backups_list += '</span>';
        backups_list += '<span class="backup_list_content_right">';

            var comment = '';

            if (list_comments[backup.name] && list_comments[backup.name]['comment']) {
                comment = list_comments[backup.name]['comment'];
            }

            backups_list += '<button type="button" title="'+nt_btn_save+'" nb="'+nb+'" name="save_comment_backup" onclick="saveCommentBackup(\''+nb+'\');" class="save_comment_backup btn btn-default">';
                backups_list += '<i class="far fa-save fa-lg"></i>';
            backups_list += '</button>';
            backups_list += '<input class="backup_comment" type="text" placeholder="'+list_backups_comment+'" title="'+list_backups_comment+'" name="comment_backup['+nb+']" id="comment_backup_'+nb+'" value="'+comment+'"/>';

        backups_list += '</span>';

        backups_list += '<span class="clear"></span>';
        backups_list += '</p>';

        if (backup.nb_part > 1) {
            backups_list += '<ul id="sub_backups'+nb+'" class="sub_backup">';

            $.each(backup.part, function(nb_part, part) {
                backups_list += '<li class="'+nb_part+'">';
                    backups_list += '<button type="button" title="'+list_backups_download+'" nb="'+nb_part+'" onclick="downloadFile(\'backup\', \''+nb_part+'\');" name="backup_download" class="backup_download btn btn-default">';
                        backups_list += '<i class="fas fa-download"></i>';
                    backups_list += '</button>';

                    if (parseInt(light) == 0) {
                        backups_list += '<button type="button" title="'+list_backups_send_away+'" nb="'+nb_part+'" onclick="sendBackup(\''+nb_part+'\');" name="send_backup" class="send_backup btn btn-default">';
                            backups_list += '<i class="fas fa-share"></i>';
                        backups_list += '</button>';
                    }

                    backups_list += '<button type="button" title="'+list_backups_delete+'" nb="'+nb_part+'" onclick="deleteBackup(\''+nb_part+'\');" name="delete_backup" class="delete_backup btn btn-default">';
                        backups_list += '<i class="fas fa-trash-alt"></i>';
                    backups_list += '</button>';
                    backups_list += part.name+' ('+part.size+')';
                backups_list += '</li>';
            });

            backups_list += '</ul>';
        }
    });

    return backups_list;
}

function displayOnedriveTree()
{
    $('#onedrive_tree').html('<img src="'+ajax_loader+'"/>');
    var id_ntbr_onedrive    = parseInt($('#id_ntbr_onedrive').val());

    $.post(
		admin_link_ntbr,
        'display_onedrive_tree=1'
        + '&id_ntbr_onedrive='+id_ntbr_onedrive,
		function(data)
		{
            if (data.tree) {
                $('#onedrive_tree').html(data.tree);

                $('input[name=onedrive_dir]').click(function()
                {
                    $('#onedrive_dir').val($(this).parent().find('.onedrive_dir').val());
                    $('#onedrive_dir_path').val($(this).parent().find('input[name=onedrive_path]').val());
                });
            } else {
                $('#onedrive_tree').html(tree_loading_error);
            }
		},"json"
	);
}

function getOnedriveTreeChildren(id_parent, onedrive_dir, level, path, target)
{
    $('#onedrive_tree').append('<img class="loader" src="'+ajax_loader+'"/>');
    var id_ntbr_onedrive    = parseInt($('#id_ntbr_onedrive').val());

    $.post(
		admin_link_ntbr,
        'display_onedrive_tree_child=1'
        + '&id_ntbr_onedrive='+id_ntbr_onedrive
        + '&id_parent=' + id_parent
        + '&onedrive_dir=' + onedrive_dir
        + '&level=' + level
        + '&path=' + path,
		function(data)
		{
			$(target).parent().parent().append(data.tree);
            $(target).remove();
            $('#onedrive_tree .loader').remove();

            $('input[name=onedrive_dir]').click(function()
            {
                $('#onedrive_dir').val($(this).parent().find('.onedrive_dir').val());
                $('#onedrive_dir_path').val($(this).parent().find('input[name=onedrive_path]').val());
            });
		},"json"
	);
}

function displayAwsTree()
{
    $('#aws_tree').html('<img src="'+ajax_loader+'"/>');
    var id_ntbr_aws    = parseInt($('#id_ntbr_aws').val());

    $.post(
		admin_link_ntbr,
        'display_aws_tree=1'
        + '&id_ntbr_aws='+id_ntbr_aws,
		function(data)
		{
            if (data.tree) {
                $('#aws_tree').html(data.tree);

                // Select a directory in the tree
                $('input[name=aws_dir_key]').click(function()
                {
                    // Add the value in the input text
                    $('#aws_directory_key').val($(this).parent().find('.aws_dir_key').val());
                    $('#aws_directory_path').val($(this).parent().find('input[name=aws_dir_path]').val());
                });
            } else {
                $('#aws_tree').html(tree_loading_error);
            }
		},"json"
	);
}

function getAwsTreeChildren(directory_key, level, directory_path, target)
{
    $('#aws_tree').append('<img class="loader" src="'+ajax_loader+'"/>');
    var id_ntbr_aws    = parseInt($('#id_ntbr_aws').val());

    $.post(
		admin_link_ntbr,
        'display_aws_tree_child=1'
        + '&id_ntbr_aws='+id_ntbr_aws
        + '&directory_key=' + directory_key
        + '&directory_path=' + directory_path
        + '&level=' + level,
		function(data)
		{
			$(target).parent().parent().append(data.tree);
            $(target).remove();
            $('#aws_tree .loader').remove();

            // Select a directory in the tree
            $('input[name=aws_dir_key]').click(function()
            {
                // Display the value in the input text
                $('#aws_directory_key').val($(this).parent().find('.aws_dir_key').val());
                $('#aws_directory_path').val($(this).parent().find('input[name=aws_dir_path]').val());
            });
		},"json"
	);
}

function displayGoogledriveTree()
{
    $('#googledrive_tree').html('<img src="'+ajax_loader+'"/>');
    var id_ntbr_googledrive = parseInt($('#id_ntbr_googledrive').val());

    $.post(
		admin_link_ntbr,
        'display_googledrive_tree=1'
        + '&id_ntbr_googledrive='+id_ntbr_googledrive,
		function(data)
		{
            if (data.tree) {
                $('#googledrive_tree').html(data.tree);

                $('input[name=googledrive_dir]').click(function()
                {
                    $('#googledrive_dir').val($(this).parent().find('.googledrive_dir').val());
                    $('#googledrive_dir_path').val($(this).parent().find('input[name=googledrive_path]').val());
                });
            } else {
                $('#googledrive_tree').html(tree_loading_error);
            }
		},"json"
	);
}

function getGoogledriveTreeChildren(id_parent, googledrive_dir, level, path, target)
{
    $('#googledrive_tree').append('<img class="loader" src="'+ajax_loader+'"/>');
    var id_ntbr_googledrive = parseInt($('#id_ntbr_googledrive').val());

    $.post(
		admin_link_ntbr,
        'display_googledrive_tree_child=1'
        + '&id_ntbr_googledrive='+id_ntbr_googledrive
        + '&id_parent=' + id_parent
        + '&googledrive_dir=' + googledrive_dir
        + '&level=' + level
        + '&path=' + path,
		function(data)
		{
			$(target).parent().parent().append(data.tree);
            $(target).remove();
            $('#googledrive_tree .loader').remove();

            $('input[name=googledrive_dir]').click(function()
            {
                $('#googledrive_dir').val($(this).parent().find('.googledrive_dir').val());
                $('#googledrive_dir_path').val($(this).parent().find('input[name=googledrive_path]').val());
            });
		},"json"
	);
}

function saveAllConfiguration()
{
    saveSendAwayAccounts().then(function(){
        if (
                ftp_save_result === ''
                && dropbox_save_result === ''
                && owncloud_save_result === ''
                && webdav_save_result === ''
                && googledrive_save_result === ''
                && onedrive_save_result === ''
                && hubic_save_result === ''
                && aws_save_result === ''
        ) {
            saveConfiguration();
        } else {
            $('#result .error.alert.alert-danger').html(ftp_save_result + dropbox_save_result + owncloud_save_result + webdav_save_result + googledrive_save_result + onedrive_save_result + hubic_save_result + aws_save_result).show();
        }
    });
}

function saveSendAwayAccounts()
{
    var save_ftp;
    var save_dropbox;
    var save_owncloud;
    var save_webdav;
    var save_googledrive;
    var save_onedrive;
    var save_hubic;
    var save_aws;

    // Try to save current FTP if needed
    if (checkFormChanged(ftp_account_id)) {
        save_ftp = saveFtp(0);
    } else {
        ftp_save_result = '';
    }

    // Try to save current Dropbox if needed
    if (checkFormChanged(dropbox_account_id)) {
        save_dropbox = saveDropbox(0);
    } else {
        dropbox_save_result = '';
    }

    // Try to save current Hubic if needed
    if (checkFormChanged(hubic_account_id)) {
        save_hubic = saveHubic(0);
    } else {
        hubic_save_result = '';
    }

    // Try to save current ownCloud if needed
    if (checkFormChanged(owncloud_account_id)) {
        save_owncloud = saveOwncloud(0);
    } else {
        owncloud_save_result = '';
    }

    // Try to save current WebDAV if needed
    if (checkFormChanged(webdav_account_id)) {
        save_webdav = saveWebdav(0);
    } else {
        webdav_save_result = '';
    }

    // Try to save current Google Drive if needed
    if (checkFormChanged(googledrive_account_id)) {
        save_googledrive = saveGoogledrive(0);
    } else {
        googledrive_save_result = '';
    }

    // Try to save current OneDrive if needed
    if (checkFormChanged(onedrive_account_id)) {
        save_onedrive = saveOnedrive(0);
    } else {
        onedrive_save_result = '';
    }

    // Try to save current AWS if needed
    if (checkFormChanged(aws_account_id)) {
        save_aws = saveAws(0);
    } else {
        aws_save_result = '';
    }

    return $.when(save_ftp, saveDropbox, save_owncloud, save_webdav, saveGoogledrive, save_onedrive, save_hubic, save_aws);
}

function saveConfiguration()
{
    $('#loader_container').show();
    $('#result div').html('').hide();

    var save_config_error               =   '';
    var send_restore                    = 0;
    //var send_hubic                      = 0;
    //var nb_keep_backup_hubic            = $('#nb_keep_backup_hubic').val();
    //var hubic_code                      = $('#hubic_code').val();
    //var hubic_dir                       = $('#hubic_dir').val();
    var activate_log                    = 0;
    var nb_keep_backup                  = $('#nb_keep_backup').val();
    var nb_keep_backup_file             = $('#nb_keep_backup_file').val();
    var nb_keep_backup_base             = $('#nb_keep_backup_base').val();
    var ignore_directories              = $('#ignore_directories').val();
    var ignore_files_types              = $('#ignore_files_types').val();
    var ignore_tables                   = $('#ignore_tables').val();
    var mail_backup                     = $('#mail_backup').val();
    var part_size                       = $('#part_size').val();
    var max_file_to_backup              = $('#max_file_to_backup').val();
    var time_between_backups            = $('#time_between_backups').val();
    var time_between_refresh            = $('#time_between_refresh').val();
    var time_pause_between_refresh      = $('#time_pause_between_refresh').val();
    var time_between_progress_refresh   = $('#time_between_progress_refresh').val();
    var dump_low_interest_table         = 0;
    var disable_refresh                 = 0;
    var disable_server_timeout          = 0;
    var increase_server_memory          = 0;
    var increase_server_memory_value    = $('#increase_server_memory_value').val();
    var activate_xsendfile              = 0;
    var send_email                      = 0;
    var email_only_error                = 0;
    var ignore_product_image            = 0;
    var ignore_files_count              = 0;
    var ignore_compression              = 0;
    var maintenance                     = 0;
    var delete_local_backup             = 0;
    var encrypt_backup                  = 0;

    if($('#activate_log_on').is(':checked'))
        activate_log = 1;

    if($('#dump_low_interest_table_on').is(':checked'))
        dump_low_interest_table = 1;

    if($('#disable_refresh_on').is(':checked'))
        disable_refresh = 1;

    if($('#disable_server_timeout_on').is(':checked'))
        disable_server_timeout = 1;

    if($('#increase_server_memory_on').is(':checked'))
        increase_server_memory = 1;

    if($('#activate_xsendfile_on').is(':checked'))
        activate_xsendfile = 1;

    if($('#send_email_on').is(':checked'))
        send_email = 1;

    if($('#email_only_error_on').is(':checked'))
        email_only_error = 1;

    /*if($('#send_hubic_on').is(':checked'))
        send_hubic = 1;*/

    if($('#send_restore_on').is(':checked'))
        send_restore = 1;

    if($('#ignore_product_image_on').is(':checked'))
        ignore_product_image = 1;

    if($('#ignore_files_count_on').is(':checked'))
        ignore_files_count = 1;

    if($('#ignore_compression_on').is(':checked'))
        ignore_compression = 1;

    if($('#maintenance_on').is(':checked'))
        maintenance = 1;

    if($('#delete_local_backup_on').is(':checked'))
        delete_local_backup = 1;

    if($('#encrypt_backup_on').is(':checked'))
        encrypt_backup = 1;

    $.post(
        save_config,
        'activate_log=' + encodeURIComponent(activate_log)
        + '&nb_keep_backup=' + encodeURIComponent(nb_keep_backup)
        + '&nb_keep_backup_file=' + encodeURIComponent(nb_keep_backup_file)
        + '&nb_keep_backup_base=' + encodeURIComponent(nb_keep_backup_base)
        + '&send_restore=' + encodeURIComponent(send_restore)
        //+ '&send_hubic=' + encodeURIComponent(send_hubic)
        //+ '&nb_keep_backup_hubic=' + encodeURIComponent(nb_keep_backup_hubic)
        //+ '&hubic_code=' + encodeURIComponent(hubic_code)
        //+ '&hubic_dir=' + encodeURIComponent(hubic_dir)
        + '&ignore_directories=' + encodeURIComponent(ignore_directories)
        + '&ignore_files_types=' + encodeURIComponent(ignore_files_types)
        + '&ignore_tables=' + encodeURIComponent(ignore_tables)
        + '&mail_backup=' + encodeURIComponent(mail_backup)
        + '&dump_low_interest_table=' + encodeURIComponent(dump_low_interest_table)
        + '&disable_refresh=' + encodeURIComponent(disable_refresh)
        + '&disable_server_timeout=' + encodeURIComponent(disable_server_timeout)
        + '&increase_server_memory=' + encodeURIComponent(increase_server_memory)
        + '&increase_server_memory_value=' + encodeURIComponent(increase_server_memory_value)
        + '&activate_xsendfile=' + encodeURIComponent(activate_xsendfile)
        + '&send_email=' + encodeURIComponent(send_email)
        + '&email_only_error=' + encodeURIComponent(email_only_error)
        + '&ignore_product_image=' + encodeURIComponent(ignore_product_image)
        + '&ignore_files_count=' + encodeURIComponent(ignore_files_count)
        + '&ignore_compression=' + encodeURIComponent(ignore_compression)
        + '&maintenance=' + encodeURIComponent(maintenance)
        + '&delete_local_backup=' + encodeURIComponent(delete_local_backup)
        + '&encrypt_backup=' + encodeURIComponent(encrypt_backup)
        + '&part_size=' + encodeURIComponent(part_size)
        + '&max_file_to_backup=' + encodeURIComponent(max_file_to_backup)
        + '&time_between_backups=' + encodeURIComponent(time_between_backups)
        + '&time_between_refresh=' + encodeURIComponent(time_between_refresh)
        + '&time_pause_between_refresh=' + encodeURIComponent(time_pause_between_refresh)
        + '&time_between_progress_refresh=' + encodeURIComponent(time_between_progress_refresh)
        + '&id_shop_group=' + encodeURIComponent(id_shop_group)
        + '&id_shop=' + encodeURIComponent(id_shop),
        function( data )
        {
            if (data.result === true) {
                $('#result .confirm.alert.alert-success').html('<p>' + save_config_success + '</p>').show();

                if(activate_log)
                    $('#log_button').show();
                else
                    $('#log_button').hide();

                //$('#hubic_code').val('');
            } else {
                if(data.errors)
                {
                    save_config_error += '<ul>';
                    $.each(data.errors, function(key, error)
                    {
                        save_config_error += '<li>' + error + '</li>';
                    });
                    save_config_error += '</ul>';
                }
                $('#result .error.alert.alert-danger').html(save_config_error).show();
            }

            $('#loader_container').hide();

            $('html, body').animate({
                scrollTop: 0
            }, 1000);
        },"json"
    );
}

function saveAutomation()
{
    $('#loader_container').show();
	$('#result div').html('').hide();

    var save_automation_error = '';
	var automation_2nt = 0;
	var automation_2nt_hours = $('#automation_2nt_hours').val();
	var automation_2nt_minutes = $('#automation_2nt_minutes').val();

	if($('#automation_2nt_on').is(':checked'))
		automation_2nt = 1;

	$.post(
		save_automation,
        'automation_2nt=' + automation_2nt
        + '&automation_2nt_hours=' + automation_2nt_hours
        + '&automation_2nt_minutes=' + automation_2nt_minutes
        + '&id_shop_group=' + id_shop_group
        + '&id_shop=' + id_shop,
		function( data )
		{
			if (data.result === true) {
				$('#result .confirm.alert.alert-success').html('<p>' + save_automation_success + '</p>').show();

				if(activate_log)
					$('#log_button').show();
				else
					$('#log_button').hide();
			} else {
                if (data.errors) {
                    save_automation_error += '<ul>';

                    $.each(data.errors, function(key, error)
                    {
                        save_automation_error += '<li>' + error + '</li>';
                    });

                    save_automation_error += '</ul>';
                }
				$('#result .error.alert.alert-danger').html(save_automation_error).show();
			}

            $('#loader_container').hide();
		},"json"
	);
}

function downloadFile(type, nb)
{
	window.open(download_file+'&'+type+'&id_shop_group='+id_shop_group+'&id_shop='+id_shop+'&nb='+nb);
}

function initRestoreBackup(backup, type_backup)
{
    $('#loader_container').show();
    $('#result div').html('').hide();

	$.post(
		admin_link_ntbr,
		'restore_backup=1'
		+'&backup=' + backup
		+'&type_backup=' + type_backup,
		function( data )
		{
			if((data.result !== 1 && data.result !== '1') || !data.options || data.options == '') {
				$('#result .error.alert.alert-danger').html('<p>' + restore_backup_error + '</p>').show();
			} else {
                restoreBackup(data.options);
			}
		},"json"
	);
}

function restoreBackup(options_restore)
{
    $('#loader_container').append('<p id="warning_restoration_running" class="alert error alert-danger">Do not touch anything while the restoration is running</p>');
    $('#loader_container').append('<p id="restoration_progress" class="alert alert-warning warn"></p>');
    $('#loader_container').show();
    $('#result div').html('').hide();
	$('#restore_progress').text('');

	$.get(
		link_restore_file,
		options_restore,
		function( data )
		{
            if (data) {
                clearInterval(progress_restore);
                if((data.result !== 1 && data.result !== '1')) {
                    $('#result .alert.error.alert-danger').html('<p>' + restore_backup_error + '</p>').show();
                } else {
                    $('#result .alert.confirm.alert-success').html('<p>' + restore_backup_success + '</p>').show();
                }

                $('#loader_container').hide();
                $('#warning_restoration_running').remove();
                $('#restoration_progress').remove();
            }
		},"json"
	);

    /* Call the function every 1s*/
	progress_restore = setInterval("displayProgressRestore()", 1000);
}

function displayProgressRestore()
{
    $.get(restore_lastlog).done(function(data) {
        if (data) {
            data = data.trim();

            var first = data.substr(0, 5);

            if(first === ERROR5)
            {
                $('#restoration_progress').removeClass('alert-warning warn').addClass('error alert-danger');
            }
            if(first === FINISH5) {
                $('#restoration_progress').removeClass('alert-warning warn').addClass('confirm alert-success');
            }


            $('#restoration_progress').html(data);
        }
    });
}