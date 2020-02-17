{*
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
*}

<script type="text/javascript">
    var light = {$light|intval};
    var admin_link_ntbr = "{$link->getAdminLink('AdminNtbackupandrestore')|escape:'javascript':'UTF-8'}";
	var create_backup_ajax = "{$create_backup_ajax|escape:'html':'UTF-8'}";
	var refresh_backup_ajax = "{$refresh_backup_ajax|escape:'html':'UTF-8'}";
	var delete_backup = "{$delete_backup|escape:'html':'UTF-8'}";
	var save_config = "{$save_config|escape:'html':'UTF-8'}";
	var save_automation = "{$save_automation|escape:'html':'UTF-8'}";
	var download_file = "{$download_file|escape:'html':'UTF-8'}";
	var backup_progress = "{$backup_progress|escape:'html':'UTF-8'}";
	var generate_urls = "{$generate_urls|escape:'html':'UTF-8'}";
    var stop_backup = "{$stop_backup|escape:'html':'UTF-8'}";
	var ajax_loader = "{$ajax_loader|escape:'html':'UTF-8'}";
	var link_restore_file = "{$link_restore_file|escape:'html':'UTF-8'}";
	var restore_lastlog = "{$restore_lastlog|escape:'html':'UTF-8'}";
    var FINISH5 = "{$restore_backup_finish|escape:'html':'UTF-8'}";
    var ERROR5 = "{$restore_backup_error|escape:'html':'UTF-8'}";
	var nt_btn_save = "{l s='Save' mod='ntbackupandrestore'}";
	var backup_link_text = "{l s='Download the backup' mod='ntbackupandrestore'}";
	var create_success = "{l s='Success! The backup seems to have works correctly, however you should download the backup file and check its validity.' mod='ntbackupandrestore'}";
	var delete_success = "{l s='Your files are deleted' mod='ntbackupandrestore'}";
	var delete_error = "{l s='Error during the deletion of your files' mod='ntbackupandrestore'}";
	var add_comment_backup_success = "{l s='Your comment is updated' mod='ntbackupandrestore'}";
	var add_comment_backup_error = "{l s='Error, your comment was not updated' mod='ntbackupandrestore'}";
	var restore_backup_error = "{l s='Error, your backup was not restored' mod='ntbackupandrestore'}";
	var restore_backup_success = "{l s='Your backup is restored' mod='ntbackupandrestore'}";
	var save_config_success = "{l s='Your configuration is saved' mod='ntbackupandrestore'}";
	var confirm_change_account = "{l s='Be carefull. All update not saved will be lost for this account. Are you sure you want to leave?' mod='ntbackupandrestore'}";
	var confirm_delete_account = "{l s='Are you sure you want to delete this account?' mod='ntbackupandrestore'}";
	var confirm_save_config = "{l s='Do you want to save your configuration?' mod='ntbackupandrestore'}";
	var confirm_restore_backup = "{l s='Are you sure you want to restore your site from your backup file? If you click Yes, the restoration will start and SHOULD NOT be stopped until it is complete or your site will be rendered unusable.' mod='ntbackupandrestore'}";
	var save_automation_success = "{l s='Your automation is saved' mod='ntbackupandrestore'}";
	var save_account_success = "{l s='Your account is saved' mod='ntbackupandrestore'}";
	var save_account_error = "{l s='Error, your account was not saved' mod='ntbackupandrestore'}";
	var delete_account_success = "{l s='Your account is deleted' mod='ntbackupandrestore'}";
	var delete_account_error = "{l s='Error, your account was not deleted' mod='ntbackupandrestore'}";
	var check_connection_success = "{l s='Your connection is working' mod='ntbackupandrestore'}";
	var check_connection_error = "{l s='Error, your connection is not working' mod='ntbackupandrestore'}";
	var tree_loading_error = "{l s='An error occured while trying to load the tree' mod='ntbackupandrestore'}";
	var prompt_name_account = "{l s='Please give a name to your new account' mod='ntbackupandrestore'}";
	var no_backup_selected = "{l s='You do not have any backup selected' mod='ntbackupandrestore'}";
	var list_backups_see = "{l s='See' mod='ntbackupandrestore'}";
	var list_backups_download = "{l s='Download' mod='ntbackupandrestore'}";
	var list_backups_delete = "{l s='Delete' mod='ntbackupandrestore'}";
	var list_backups_colons = "{l s=':' mod='ntbackupandrestore'}";
	var list_backups_send_away = "{l s='Send away' mod='ntbackupandrestore'}";
	var list_backups_comment = "{l s='Comment' mod='ntbackupandrestore'}";
	var warning_while_restoring = "{l s='WARNING! The restoration of the backup is in progress. Do not touch anything and wait. Restoration MUST NOT be interrupted otherwise your site will be completely unusable!' mod='ntbackupandrestore'}";
	var id_shop_group = {$id_shop_group|intval};
	var id_shop = {$id_shop|intval};
	var ftp_port_default = {$ftp_port_default|intval};
	var ftp_directory_default = "{$ftp_directory_default|escape:'html':'UTF-8'}";
    var list_comments = new Array();
    var time_between_progress_refresh = {$time_between_progress_refresh|intval} * 1000;

    {foreach from=$list_comments item=comment}
        if (!list_comments["{$comment.backup_name|escape:'html':'UTF-8'}"]) {
            list_comments["{$comment.backup_name|escape:'html':'UTF-8'}"] = new Array();
        }

        list_comments["{$comment.backup_name|escape:'html':'UTF-8'}"]['id_ntbr_comments'] = "{$comment.id_ntbr_comments|intval}";
        list_comments["{$comment.backup_name|escape:'html':'UTF-8'}"]['backup_name'] = "{$comment.backup_name|escape:'html':'UTF-8'}";
        list_comments["{$comment.backup_name|escape:'html':'UTF-8'}"]['comment'] = "{$comment.comment|escape:'html':'UTF-8'}";
    {/foreach}
</script>
{include file="toolbar.tpl" toolbar_btn=$toolbar_btn toolbar_scroll=$toolbar_scroll title=$title}
<div id="ntbackupandrestore">
    {if $module_address_use != $module_address_config}
        <div id="shop_error" class="error alert alert-danger">
            <p>
                {l s='Warning!' mod='ntbackupandrestore'}
                <br/>
                {l s='You are not connected on the primary address of your shop!' mod='ntbackupandrestore'}
                <br/>
                <span>
                    {l s='So that the module works correctly, use' mod='ntbackupandrestore'}
                    <a href="{$module_address_config|escape:'html':'UTF-8'}">{$module_address_config|escape:'html':'UTF-8'}</a>
                    {l s='instead of' mod='ntbackupandrestore'}
                    <a href="{$module_address_use|escape:'html':'UTF-8'}">{$module_address_use|escape:'html':'UTF-8'}</a>
                </span>
            </p>
        </div>
    {/if}
    {if !$update_owncloud_pass}
        <div id="update_owncloud_pass_error" class="error alert alert-danger">
            <p>
                {l s='Warning!' mod='ntbackupandrestore'}
                <br/>
                {l s='You ownCloud password cannot be updated for the new version. Please save it again.' mod='ntbackupandrestore'}
            </p>
        </div>
    {/if}
    {if $big_website}
        <div id="big_website_error" class="error alert alert-danger">
            <span class="close">X</span>
            <p>
                {l s='Warning!' mod='ntbackupandrestore'}
                <br/>
                {l s='Your backup has a large size (> 2 GB). Compressing your backup may be faster if you:' mod='ntbackupandrestore'}
            </p>
            <ol>
                <li>{l s='Increase the maximum timeout of your server and in the advanced options of the module, increase the duration of the intermediate renewal to the maximum timeout of your server.' mod='ntbackupandrestore'}</li>
                <li>{l s='Or increase the maximum timeout of your server and disable intermediate renewal in the advanced options of the module.' mod='ntbackupandrestore'}</li>
                <li>{l s='Or create fragmented backups by specifying a maximum size of 2000 MB in the advanced options of the module.' mod='ntbackupandrestore'}</li>
                <li>{l s='Or disable compression in the advanced options of the module.' mod='ntbackupandrestore'}</li>
            </ol>
            <p>
                {l s='Do not hesitate to contact us if necessary!' mod='ntbackupandrestore'}
            </p>
        </div>
    {/if}
    <div id="result">
        <div class="error alert alert-danger"></div>
        <div class="confirm alert alert-success"></div>
    </div>
	<div class="clear"></div>
	<div class="sidebar navigation col-md-2">
		<nav id="nt_tab" class="list-group">
			<a id="nt_tab0" class="list-group-item active"><i class="fas fa-archive"></i>&nbsp;{l s='Backup' mod='ntbackupandrestore'}</a>
			<a id="nt_tab1" class="list-group-item"><i class="fas fa-cogs"></i>&nbsp;{l s='Configuration' mod='ntbackupandrestore'}</a>
			<a id="nt_tab2" class="list-group-item"><i class="far fa-clock"></i>&nbsp;{l s='Automation' mod='ntbackupandrestore'}</a>
			<a id="nt_tab5" class="list-group-item"><i class="fas fa-history"></i>&nbsp;{l s='Restoration' mod='ntbackupandrestore'}</a>
			<a id="nt_tab3" class="list-group-item"><i class="fas fa-book"></i>&nbsp;{l s='Documentation' mod='ntbackupandrestore'}</a>
			<a id="nt_tab4" class="list-group-item"><i class="fas fa-envelope"></i>&nbsp;{l s='Contact' mod='ntbackupandrestore'}</a>
            {if $display_translate_tab}
			<a id="nt_tab6" class="list-group-item"><i class="fas fa-globe"></i>&nbsp;Help us translate into your language</a>
            {/if}
		</nav>
		<nav class="list-group">
            <a id="nt_request" class="list-group-item" href="{$link_contact|escape:'html':'UTF-8'}" target="_blank">
                <i class="far fa-lightbulb"></i>&nbsp;{l s='Request feature' mod='ntbackupandrestore'}
            </a>
			<a id="nt_version" class="list-group-item">
                <i class="fas fa-info"></i>&nbsp;{l s='Version' mod='ntbackupandrestore'} {$version|escape:'html':'UTF-8'}
                {if $available_version > $version}({$available_version|escape:'html':'UTF-8'} avail){/if}
            </a>
		</nav>
	</div>
	<div>
		<div id="nt_tab0_content" class="tab col-md-10">
			{include file="./backup.tpl"}
		</div>
		<div id="nt_tab1_content" class="tab panel col-md-10">
			{include file="./configuration.tpl"}
		</div>
		<div id="nt_tab2_content" class="panel tab col-md-10">
			{include file="./automation.tpl"}
		</div>
		<div id="nt_tab3_content" class="panel tab col-md-10">
			{include file="./documentation.tpl"}
		</div>
		<div id="nt_tab4_content" class="panel tab col-md-10">
			{include file="./contact.tpl"}
		</div>
		<div id="nt_tab5_content" class="panel tab col-md-10">
			{include file="./restoration.tpl"}
		</div>
		<div id="nt_tab6_content" class="panel tab col-md-10">
			{include file="./translate.tpl"}
		</div>
		<div class="clear"></div>
	</div>
    <div class="clear"></div>
    <div id="loader_container">
        <div id="grey_background"></div>
        <img id="loader" src="{$ajax_loader|escape:'html':'UTF-8'}"/>
    </div>
</div>

