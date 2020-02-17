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

<div class="panel-heading">
    <i class="fas fa-cogs"></i>
    &nbsp;{l s='Configuration' mod='ntbackupandrestore'}
</div>

{if !$curl_exists}
    <div class="curl_warning alert alert-warning warn">
        <p>
            {l s='PHP curl not loaded. Curl is required to increase performance if you have backup files larger than 2 GB. Please enable it in your hosting management if this is the case.' mod='ntbackupandrestore'}
        </p>
    </div>
{/if}
<div class="panel">
    <div class="panel-heading">
        <i class="far fa-hdd"></i>
        &nbsp;{l s='Backups to keep in local.' mod='ntbackupandrestore'}
    </div>
    <p>
        <label for="nb_keep_backup">{l s='Complete backup to keep in local. 0 to never delete old complete backups' mod='ntbackupandrestore'}</label>
        <span><input type="text" title="{l s='Delete old backups in local. 0 to never delete old backups' mod='ntbackupandrestore'}" name="nb_keep_backup" id="nb_keep_backup" value="{$nb_keep_backup|intval}"/></span>
    </p>
    <p>
        <label for="nb_keep_backup_file">{l s='Only files backup to keep in local. 0 to never delete old files backups' mod='ntbackupandrestore'}</label>
        <span><input type="text" title="{l s='Delete old files backups in local. 0 to never delete old files backups' mod='ntbackupandrestore'}" name="nb_keep_backup_file" id="nb_keep_backup_file" value="{$nb_keep_backup_file|intval}"/></span>
    </p>
    <p>
        <label for="nb_keep_backup_base">{l s='Only database backup to keep in local. 0 to never delete old database backups' mod='ntbackupandrestore'}</label>
        <span><input type="text" title="{l s='Delete old database backups in local. 0 to never delete old database backups' mod='ntbackupandrestore'}" name="nb_keep_backup_base" id="nb_keep_backup_base" value="{$nb_keep_backup_base|intval}"/></span>
    </p>
</div>
<div class="panel">
    <div class="panel-heading">
        <i class="fas fa-envelope"></i>
        &nbsp;<span>{l s='Send an email with the date and hour of the beginning and end of the backup and the result message.' mod='ntbackupandrestore'}</span>
    </div>
    <p>
        <span class="switch prestashop-switch fixed-width-lg">
            <input type="radio" name="send_email" id="send_email_on" value="1" {if $send_email}checked="checked"{/if}/>
            <label class="t" for="send_email_on">
                {l s='Yes' mod='ntbackupandrestore'}
            </label>
            <input type="radio" name="send_email" id="send_email_off" value="0" {if !$send_email}checked="checked"{/if}/>
            <label class="t" for="send_email_off">
                {l s='No' mod='ntbackupandrestore'}
            </label>
            <a class="slide-button btn"></a>
        </span>
    </p>
    <div id="change_mail" class="panel" >
        <div class="panel-heading">
            <i class="fas fa-cog"></i>
            &nbsp;{l s='Send an email with the date and hour of the beginning and end of the backup and the result message.' mod='ntbackupandrestore'}
        </div>
        <p>
            <label for="email_only_error" id="email_only_error">{l s='Send an email only if there is an error' mod='ntbackupandrestore'}</label>
            <span class="switch prestashop-switch fixed-width-lg">
                <input type="radio" name="email_only_error" id="email_only_error_on" value="1" {if $email_only_error}checked="checked"{/if}/>
                <label class="t" for="email_only_error_on">
                    {l s='Yes' mod='ntbackupandrestore'}
                </label>
                <input type="radio" name="email_only_error" id="email_only_error_off" value="0" {if !$email_only_error}checked="checked"{/if}/>
                <label class="t" for="email_only_error_off">
                    {l s='No' mod='ntbackupandrestore'}
                </label>
                <a class="slide-button btn"></a>
            </span>
        </p>
        <p>
            <label for="mail_backup">{l s='Email you want to use to receive message from this module' mod='ntbackupandrestore'}</label>
            <span><input type="text" title="{l s='You will receive your notification on this email' mod='ntbackupandrestore'}" name="mail_backup" id="mail_backup" value="{$mail_backup|escape:'html':'UTF-8'}"/></span>
        </p>
    </div>
</div>
<div class="panel">
    <div class="panel-heading">
        <i class="fas fa-share"></i>
        &nbsp;<span>{l s='Send away.' mod='ntbackupandrestore'}</span>
    </div>
    {if $light}
        <div class="light_version_error alert alert-info hint">
            <p>
                {l s='Remote sending allows you to secure your backup by sending it automatically to another physical location. If your server is unavailable (crash, hack, fire ...), you can restore your shop with your backup located elsewhere. This feature is only available in the' mod='ntbackupandrestore'}
                <a href="{$link_full_version|escape:'htmlall':'UTF-8'}">{l s='full version of the module' mod='ntbackupandrestore'}</a>.
                {l s='You can send your backups to FTP, FTPS, SFTP, Dropbox, Owncloud, WebDav, Google Drive, Google Drive G Suite, Microsoft Onedrive, OVH Hubic, Amazon AWS.' mod='ntbackupandrestore'}
            </p>
        </div>
        <br/>
    {/if}
    <div class="{if $light}light_version{/if}">
        <div class="panel">
            <div class="panel-heading">
                <i class="icon_send_away send_ftp"></i>
                &nbsp;<span>{l s='Send the backup on a FTP or SFTP server.' mod='ntbackupandrestore'}</span>
            </div>
            <div>
                <p {if $light}class="deactivate"{/if}>
                    <button type="button" class="btn btn-default" id="send_ftp" name="send_ftp">
                        <i class="fas fa-cog"></i>
                        {l s='Accounts configuration' mod='ntbackupandrestore'}
                    </button>
                </p>
            </div>
            <div id="config_ftp_accounts" class="panel config_send_away_account" >
                <div class="panel-heading">
                    <i class="fas fa-cog"></i>
                    &nbsp;{l s='Send the backup on a FTP or SFTP server.' mod='ntbackupandrestore'}
                </div>
                <div>
                    <p class="account_list" id="ftp_tabs">
                        <label>{l s='Account' mod='ntbackupandrestore'}</label>
                        <button type="button" class="btn btn-default choose_ftp_account active" id="ftp_account_0" value="0">
                            <i class="fas fa-plus"></i>
                        </button>
                        {foreach $ftp_accounts as $ftp_account}
                            <button type="button" class="btn btn-default choose_ftp_account inactive {if $ftp_account.active == 1}enable{else}disable{/if}" id="ftp_account_{$ftp_account.id_ntbr_ftp|intval}" value="{$ftp_account.id_ntbr_ftp|intval}">
                                {$ftp_account.name|escape:'html':'UTF-8'}
                            </button>
                        {/foreach}
                    </p>
                    <div class="ftp_account" id="ftp_account">
                        <p>
                            <input type="hidden" id="id_ntbr_ftp" name="id_ntbr_ftp" value="{$ftp_default.id_ntbr_ftp|intval}" data-origin="{$ftp_default.id_ntbr_ftp|intval}" data-default="{$ftp_default.id_ntbr_ftp|intval}"/>
                            <label for="ftp_name">{l s='Account name' mod='ntbackupandrestore'}</label>
                            <span><input class="name_account" type="text" name="ftp_name" id="ftp_name" value="{$ftp_default.name|escape:'html':'UTF-8'} {$ftp_default.nb_account|intval}" data-origin="{$ftp_default.name|escape:'html':'UTF-8'}" data-default="{$ftp_default.name|escape:'html':'UTF-8'}"/></span>
                        </p>
                        <p>
                            <label for="active_ftp" id="active_ftp">{l s='Enabled' mod='ntbackupandrestore'}</label>
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="active_ftp" id="active_ftp_on" value="1" {if $ftp_default.active}checked="checked"{/if} data-origin="{$ftp_default.active|intval}" data-default="{$ftp_default.active|intval}"/>
                                <label class="t" for="active_ftp_on">
                                    {l s='Yes' mod='ntbackupandrestore'}
                                </label>
                                <input type="radio" name="active_ftp" id="active_ftp_off" value="0"  {if !$ftp_default.active}checked="checked"{/if} data-origin="{$ftp_default.active|intval}" data-default="{$ftp_default.active|intval}"/>
                                <label class="t" for="active_ftp_off">
                                    {l s='No' mod='ntbackupandrestore'}
                                </label>
                                <a class="slide-button btn"></a>
                            </span>
                        </p>
                        {if !$fct_crypt_exists}
                            <div class="fct_crypt_error error alert alert-danger">
                                <p>
                                    {l s='SFTP cannot work with your current configuration. Please check the following requirements:' mod='ntbackupandrestore'}
                                </p>
                                <ul>
                                    <li>{l s='You have at least PHP 5.6. Please check your PHP version in your hosting management.' mod='ntbackupandrestore'}</li>
                                    <li>{l s='PHP openssl is loaded. Please enable it in your hosting management to use SFTP.' mod='ntbackupandrestore'}</li>
                                </ul>
                            </div>
                        {/if}
                        <p {if !$fct_crypt_exists}class="deactivate"{/if}>
                            <label for="send_sftp" id="send_sftp">{l s='SFTP (SSH File Transfer Protocol). It is different from FTP or FTPS (File Transfer Protocol Secure). If you are not sure, it means it is not SFTP.' mod='ntbackupandrestore'}</label>
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="send_sftp" id="send_sftp_on" value="1" {if $ftp_default.sftp}checked="checked"{/if} data-origin="{$ftp_default.sftp|intval}" data-default="{$ftp_default.sftp|intval}"/>
                                <label class="t" for="send_sftp_on">
                                    {l s='Yes' mod='ntbackupandrestore'}
                                </label>
                                <input type="radio" name="send_sftp" id="send_sftp_off" value="0"  {if !$ftp_default.sftp}checked="checked"{/if} data-origin="{$ftp_default.sftp|intval}" data-default="{$ftp_default.sftp|intval}"/>
                                <label class="t" for="send_sftp_off">
                                    {l s='No' mod='ntbackupandrestore'}
                                </label>
                                <a class="slide-button btn"></a>
                            </span>
                        </p>
                        <p class="option_ftp_ssl {if !$fct_crypt_exists || $os_windows}deactivate{/if}">
                            <label for="ftp_ssl" id="ftp_ssl">{l s='SSL' mod='ntbackupandrestore'}</label>
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="ftp_ssl" id="ftp_ssl_on" value="1" {if $ftp_default.ssl}checked="checked"{/if} data-origin="{$ftp_default.ssl|intval}" data-default="{$ftp_default.ssl|intval}"/>
                                <label class="t" for="ftp_ssl_on">
                                    {l s='Yes' mod='ntbackupandrestore'}
                                </label>
                                <input type="radio" name="ftp_ssl" id="ftp_ssl_off" value="0"  {if !$ftp_default.ssl}checked="checked"{/if} data-origin="{$ftp_default.ssl|intval}" data-default="{$ftp_default.ssl|intval}"/>
                                <label class="t" for="ftp_ssl_off">
                                    {l s='No' mod='ntbackupandrestore'}
                                </label>
                                <a class="slide-button btn"></a>
                            </span>
                        </p>
                        <p class="option_ftp_pasv">
                            <label for="ftp_pasv" id="ftp_pasv">{l s='Passive mode' mod='ntbackupandrestore'}</label>
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="ftp_pasv" id="ftp_pasv_on" value="1" {if $ftp_default.passive_mode}checked="checked"{/if} data-origin="{$ftp_default.ssl|intval}" data-default="{$ftp_default.ssl|intval}"/>
                                <label class="t" for="ftp_pasv_on">
                                    {l s='Yes' mod='ntbackupandrestore'}
                                </label>
                                <input type="radio" name="ftp_pasv" id="ftp_pasv_off" value="0"  {if !$ftp_default.passive_mode}checked="checked"{/if} data-origin="{$ftp_default.ssl|intval}" data-default="{$ftp_default.ssl|intval}"/>
                                <label class="t" for="ftp_pasv_off">
                                    {l s='No' mod='ntbackupandrestore'}
                                </label>
                                <a class="slide-button btn"></a>
                            </span>
                        </p>
                        <p>
                            <label for="nb_keep_backup_ftp">{l s='Complete backup to keep. 0 to never delete old complete backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old backups. 0 to never delete old backups' mod='ntbackupandrestore'}" name="nb_keep_backup_ftp" id="nb_keep_backup_ftp" value="{$ftp_default.nb_backup|intval}" data-origin="{$ftp_default.nb_backup|intval}" data-default="{$ftp_default.nb_backup|intval}"/></span>

                            <label for="nb_keep_backup_file_ftp">{l s='Only files backup to keep. 0 to never delete old files backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old files backups. 0 to never delete old files backups' mod='ntbackupandrestore'}" name="nb_keep_backup_file_ftp" id="nb_keep_backup_file_ftp" value="{$ftp_default.nb_backup_file|intval}" data-origin="{$ftp_default.nb_backup_file|intval}" data-default="{$ftp_default.nb_backup_file|intval}"/></span>

                            <label for="nb_keep_backup_base_ftp">{l s='Only database backup to keep. 0 to never delete old database backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old database backups. 0 to never delete old database backups' mod='ntbackupandrestore'}" name="nb_keep_backup_base_ftp" id="nb_keep_backup_base_ftp" value="{$ftp_default.nb_backup_base|intval}" data-origin="{$ftp_default.nb_backup_base|intval}" data-default="{$ftp_default.nb_backup_base|intval}"/></span>

                            <label for="ftp_server">{l s='Server' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="ftp_server" id="ftp_server" value="{$ftp_default.server|escape:'html':'UTF-8'}" data-origin="{$ftp_default.server|escape:'html':'UTF-8'}" data-default="{$ftp_default.server|escape:'html':'UTF-8'}"/></span>

                            <label for="ftp_login">{l s='Login' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="ftp_login" id="ftp_login" value="{$ftp_default.login|escape:'html':'UTF-8'}" data-origin="{$ftp_default.login|escape:'html':'UTF-8'}" data-default="{$ftp_default.login|escape:'html':'UTF-8'}"/></span>

                            <label for="ftp_pass">{l s='Password' mod='ntbackupandrestore'}</label>
                            <input type="password" class="decoy" value=""/>
                            <span><input autocomplete="new-password" type="password" name="ftp_pass" id="ftp_pass" value="{$ftp_default.password|escape:'html':'UTF-8'}" data-origin="{$ftp_default.password|escape:'html':'UTF-8'}" data-default="{$ftp_default.password|escape:'html':'UTF-8'}"/></span>

                            <label for="ftp_port">{l s='Port' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="ftp_port" id="ftp_port" value="{$ftp_default.port|intval}" data-origin="{$ftp_default.port|intval}" data-default="{$ftp_default.port|intval}"/></span>

                            <label for="ftp_dir">{l s='Directory' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="ftp_dir" id="ftp_dir" value="{$ftp_default.directory|escape:'html':'UTF-8'}" data-origin="{$ftp_default.directory|escape:'html':'UTF-8'}" data-default="{$ftp_default.directory|escape:'html':'UTF-8'}"/></span>
                        </p>
                    </div>
                </div>
                <div class="panel-footer">
                    <button type="button" class="btn btn-default" id="save_ftp" name="save_ftp">
                        <i class="far fa-save process_icon"></i>
                        {l s='Save' mod='ntbackupandrestore'}
                    </button>
                    <button type="button" class="btn btn-default" id="check_ftp" name="check_ftp">
                        <i class="fas fa-sync-alt process_icon"></i>
                        {l s='Check connection' mod='ntbackupandrestore'}
                    </button>
                    <button type="button" class="btn btn-default" id="delete_ftp" name="delete_ftp">
                        <i class="fas fa-trash-alt process_icon"></i>
                        {l s='Delete' mod='ntbackupandrestore'}
                    </button>
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-heading">
                <i class="icon_send_away send_dropbox"></i>
                &nbsp;<span>{l s='Send the backup on a Dropbox account.' mod='ntbackupandrestore'}</span>
            </div>
            <div>
                <p {if $light}class="deactivate"{/if}>
                    <button type="button" class="btn btn-default" id="send_dropbox" name="send_dropbox">
                        <i class="fas fa-cog"></i>
                        {l s='Accounts configuration' mod='ntbackupandrestore'}
                    </button>
                </p>
            </div>
            <div id="config_dropbox_accounts" class="panel config_send_away_account" >
                <div class="panel-heading">
                    <i class="fas fa-cog"></i>
                    &nbsp;{l s='Send the backup on a Dropbox account.' mod='ntbackupandrestore'}
                </div>
                <div>
                    <p class="account_list" id="dropbox_tabs">
                        <label>{l s='Account' mod='ntbackupandrestore'}</label>
                        <button type="button" class="btn btn-default choose_dropbox_account active" id="dropbox_account_0" value="0">
                            <i class="fas fa-plus"></i>
                        </button>
                        {foreach $dropbox_accounts as $dropbox_account}
                            <button type="button" class="btn btn-default choose_dropbox_account inactive {if $dropbox_account.active == 1}enable{else}disable{/if}" id="dropbox_account_{$dropbox_account.id_ntbr_dropbox|intval}" value="{$dropbox_account.id_ntbr_dropbox|intval}">
                                {$dropbox_account.name|escape:'html':'UTF-8'}
                            </button>
                        {/foreach}
                    </p>
                    <div class="dropbox_account" id="dropbox_account">
                        <p>
                            <input type="hidden" id="id_ntbr_dropbox" name="id_ntbr_dropbox" value="{$dropbox_default.id_ntbr_dropbox|intval}" data-origin="{$dropbox_default.id_ntbr_dropbox|intval}" data-default="{$dropbox_default.id_ntbr_dropbox|intval}"/>
                            <label for="dropbox_name">{l s='Account name' mod='ntbackupandrestore'}</label>
                            <span><input class="name_account" type="text" name="dropbox_name" id="dropbox_name" value="{$dropbox_default.name|escape:'html':'UTF-8'} {$dropbox_default.nb_account|intval}" data-origin="{$dropbox_default.name|escape:'html':'UTF-8'}" data-default="{$dropbox_default.name|escape:'html':'UTF-8'}"/></span>
                        </p>
                        <p>
                            <label for="active_dropbox" id="active_dropbox">{l s='Enabled' mod='ntbackupandrestore'}</label>
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="active_dropbox" id="active_dropbox_on" value="1" {if $dropbox_default.active}checked="checked"{/if} data-origin="{$dropbox_default.active|intval}" data-default="{$dropbox_default.active|intval}"/>
                                <label class="t" for="active_dropbox_on">
                                    {l s='Yes' mod='ntbackupandrestore'}
                                </label>
                                <input type="radio" name="active_dropbox" id="active_dropbox_off" value="0"  {if !$dropbox_default.active}checked="checked"{/if} data-origin="{$dropbox_default.active|intval}" data-default="{$dropbox_default.active|intval}"/>
                                <label class="t" for="active_dropbox_off">
                                    {l s='No' mod='ntbackupandrestore'}
                                </label>
                                <a class="slide-button btn"></a>
                            </span>
                        </p>
                        <p>
                            <label for="nb_keep_backup_dropbox">{l s='Complete backup to keep. 0 to never delete old complete backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old backups. 0 to never delete old backups' mod='ntbackupandrestore'}" name="nb_keep_backup_dropbox" id="nb_keep_backup_dropbox" value="{$dropbox_default.nb_backup|intval}" data-origin="{$dropbox_default.nb_backup|intval}" data-default="{$dropbox_default.nb_backup|intval}"/></span>

                            <label for="nb_keep_backup_file_dropbox">{l s='Only files backup to keep. 0 to never delete old files backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old files backups. 0 to never delete old files backups' mod='ntbackupandrestore'}" name="nb_keep_backup_file_dropbox" id="nb_keep_backup_file_dropbox" value="{$dropbox_default.nb_backup_file|intval}" data-origin="{$dropbox_default.nb_backup_file|intval}" data-default="{$dropbox_default.nb_backup_file|intval}"/></span>

                            <label for="nb_keep_backup_base_dropbox">{l s='Only database backup to keep. 0 to never delete old database backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old database backups. 0 to never delete old database backups' mod='ntbackupandrestore'}" name="nb_keep_backup_base_dropbox" id="nb_keep_backup_base_dropbox" value="{$dropbox_default.nb_backup_base|intval}" data-origin="{$dropbox_default.nb_backup_base|intval}" data-default="{$dropbox_default.nb_backup_base|intval}"/></span>

                            <label>{l s='1.' mod='ntbackupandrestore'}</label>
                            <button type="button" name="authentification_dropbox" id="authentification_dropbox" class="btn btn-default" onclick="window.open('{$dropbox_authorizeUrl|escape:'html':'UTF-8'}');">
                                <i class="fab fa-dropbox"></i> - {l s='Authentification' mod='ntbackupandrestore'}
                            </button>
                            <br/>
                            <label>{l s='2. Click "Allow" (you might have to log in first)' mod='ntbackupandrestore'}</label>
                            <br/>
                            <label for="dropbox_code">{l s='3. Copy the authorization code' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="dropbox_code" id="dropbox_code" value="" data-origin="" data-default=""/></span>

                            <label for="dropbox_dir">{l s='Directory (ex: "/backups")' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="dropbox_dir" id="dropbox_dir" value="{$dropbox_default.directory|escape:'html':'UTF-8'}" data-origin="{$dropbox_default.directory|escape:'html':'UTF-8'}" data-default="{$dropbox_default.directory|escape:'html':'UTF-8'}"/></span>
                        </p>
                    </div>
                </div>
                <div class="panel-footer">
                    <button type="button" class="btn btn-default" id="save_dropbox" name="save_dropbox">
                        <i class="far fa-save process_icon"></i>
                        {l s='Save' mod='ntbackupandrestore'}
                    </button>
                    <button type="button" class="btn btn-default" id="check_dropbox" name="check_dropbox">
                        <i class="fas fa-sync-alt process_icon"></i>
                        {l s='Check connection' mod='ntbackupandrestore'}
                    </button>
                    <button type="button" class="btn btn-default" id="delete_dropbox" name="delete_dropbox">
                        <i class="fas fa-trash-alt process_icon"></i>
                        {l s='Delete' mod='ntbackupandrestore'}
                    </button>
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-heading">
                <i class="icon_send_away send_owncloud"></i>
                &nbsp;<span>{l s='Send the backup on a ownCloud account.' mod='ntbackupandrestore'}</span>
            </div>
            <div>
                {if !$fct_crypt_exists}
                    <div class="fct_crypt_error error alert alert-danger">
                        <p>
                            {l s='ownCloud cannot work with your current configuration. Please check the following requirements:' mod='ntbackupandrestore'}
                        </p>
                        <ul>
                            <li>{l s='You have at least PHP 5.6. Please check your PHP version in your hosting management.' mod='ntbackupandrestore'}</li>
                            <li>{l s='PHP openssl is loaded. Please enable it in your hosting management to use ownCloud.' mod='ntbackupandrestore'}</li>
                        </ul>
                    </div>
                    <br/>
                {/if}

                <p {if !$fct_crypt_exists || $light}class="deactivate"{/if}>
                    <button type="button" class="btn btn-default" id="send_owncloud" name="send_owncloud">
                        <i class="fas fa-cog"></i>
                        {l s='Accounts configuration' mod='ntbackupandrestore'}
                    </button>
                </p>
            </div>
            <div id="config_owncloud_accounts" class="panel config_send_away_account" >
                <div class="panel-heading">
                    <i class="fas fa-cog"></i>
                    &nbsp;{l s='Send the backup on a ownCloud account.' mod='ntbackupandrestore'}
                </div>
                <div>
                    <p class="account_list" id="owncloud_tabs">
                        <label>{l s='Account' mod='ntbackupandrestore'}</label>
                        <button type="button" class="btn btn-default choose_owncloud_account active" id="owncloud_account_0" value="0">
                            <i class="fas fa-plus"></i>
                        </button>
                        {foreach $owncloud_accounts as $owncloud_account}
                            <button type="button" class="btn btn-default choose_owncloud_account inactive {if $owncloud_account.active == 1}enable{else}disable{/if}" id="owncloud_account_{$owncloud_account.id_ntbr_owncloud|intval}" value="{$owncloud_account.id_ntbr_owncloud|intval}">
                                {$owncloud_account.name|escape:'html':'UTF-8'}
                            </button>
                        {/foreach}
                    </p>
                    <div class="owncloud_account" id="owncloud_account">
                        <p>
                            <input type="hidden" id="id_ntbr_owncloud" name="id_ntbr_owncloud" value="{$owncloud_default.id_ntbr_owncloud|intval}" data-origin="{$owncloud_default.id_ntbr_owncloud|intval}" data-default="{$owncloud_default.id_ntbr_owncloud|intval}"/>
                            <label for="owncloud_name">{l s='Account name' mod='ntbackupandrestore'}</label>
                            <span><input class="name_account" type="text" name="owncloud_name" id="owncloud_name" value="{$owncloud_default.name|escape:'html':'UTF-8'} {$owncloud_default.nb_account|intval}" data-origin="{$owncloud_default.name|escape:'html':'UTF-8'}" data-default="{$owncloud_default.name|escape:'html':'UTF-8'}"/></span>
                        </p>
                        <p>
                            <label for="active_owncloud" id="active_owncloud">{l s='Enabled' mod='ntbackupandrestore'}</label>
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="active_owncloud" id="active_owncloud_on" value="1" {if $owncloud_default.active}checked="checked"{/if} data-origin="{$owncloud_default.active|intval}" data-default="{$owncloud_default.active|intval}"/>
                                <label class="t" for="active_owncloud_on">
                                    {l s='Yes' mod='ntbackupandrestore'}
                                </label>
                                <input type="radio" name="active_owncloud" id="active_owncloud_off" value="0"  {if !$owncloud_default.active}checked="checked"{/if} data-origin="{$owncloud_default.active|intval}" data-default="{$owncloud_default.active|intval}"/>
                                <label class="t" for="active_owncloud_off">
                                    {l s='No' mod='ntbackupandrestore'}
                                </label>
                                <a class="slide-button btn"></a>
                            </span>
                        </p>
                        <p>
                            <label for="nb_keep_backup_owncloud">{l s='Complete backup to keep. 0 to never delete old complete backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old backups. 0 to never delete old backups' mod='ntbackupandrestore'}" name="nb_keep_backup_owncloud" id="nb_keep_backup_owncloud" value="{$owncloud_default.nb_backup|intval}" data-origin="{$owncloud_default.nb_backup|intval}" data-default="{$owncloud_default.nb_backup|intval}"/></span>

                            <label for="nb_keep_backup_file_owncloud">{l s='Only files backup to keep. 0 to never delete old files backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old files backups. 0 to never delete old files backups' mod='ntbackupandrestore'}" name="nb_keep_backup_file_owncloud" id="nb_keep_backup_file_owncloud" value="{$owncloud_default.nb_backup_file|intval}" data-origin="{$owncloud_default.nb_backup_file|intval}" data-default="{$owncloud_default.nb_backup_file|intval}"/></span>

                            <label for="nb_keep_backup_base_owncloud">{l s='Only database backup to keep. 0 to never delete old database backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old database backups. 0 to never delete old database backups' mod='ntbackupandrestore'}" name="nb_keep_backup_base_owncloud" id="nb_keep_backup_base_owncloud" value="{$owncloud_default.nb_backup_base|intval}" data-origin="{$owncloud_default.nb_backup_base|intval}" data-default="{$owncloud_default.nb_backup_base|intval}"/></span>

                            <label for="owncloud_user">{l s='User:' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="owncloud_user" id="owncloud_user" value="{$owncloud_default.login|escape:'html':'UTF-8'}" data-origin="{$owncloud_default.login|escape:'html':'UTF-8'}" data-default="{$owncloud_default.login|escape:'html':'UTF-8'}"/></span>

                            <label for="owncloud_pass">{l s='Pass:' mod='ntbackupandrestore'}</label>
                            <input type="password" class="decoy"/>
                            <span><input autocomplete="new-password" type="password" name="owncloud_pass" id="owncloud_pass" value="{$owncloud_default.password|escape:'html':'UTF-8'}" data-origin="{$owncloud_default.password|escape:'html':'UTF-8'}" data-default="{$owncloud_default.password|escape:'html':'UTF-8'}"/></span>

                            <label for="owncloud_server">{l s='Server:' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="owncloud_server" id="owncloud_server" value="{$owncloud_default.server|escape:'html':'UTF-8'}" data-origin="{$owncloud_default.server|escape:'html':'UTF-8'}" data-default="{$owncloud_default.server|escape:'html':'UTF-8'}"/></span>

                            <label for="owncloud_dir">{l s='Directory (ex: "backups")' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="owncloud_dir" id="owncloud_dir" value="{$owncloud_default.directory|escape:'html':'UTF-8'}" data-origin="{$owncloud_default.directory|escape:'html':'UTF-8'}" data-default="{$owncloud_default.directory|escape:'html':'UTF-8'}"/></span>
                        </p>
                    </div>
                </div>
                <div class="panel-footer">
                    <button type="button" class="btn btn-default" id="save_owncloud" name="save_owncloud">
                        <i class="far fa-save process_icon"></i>
                        {l s='Save' mod='ntbackupandrestore'}
                    </button>
                    <button type="button" class="btn btn-default" id="check_owncloud" name="check_owncloud">
                        <i class="fas fa-sync-alt process_icon"></i>
                        {l s='Check connection' mod='ntbackupandrestore'}
                    </button>
                    <button type="button" class="btn btn-default" id="delete_owncloud" name="delete_owncloud">
                        <i class="fas fa-trash-alt process_icon"></i>
                        {l s='Delete' mod='ntbackupandrestore'}
                    </button>
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-heading">
                <i class="icon_send_away send_webdav"></i>
                &nbsp;<span>{l s='Send the backup on a WebDAV account.' mod='ntbackupandrestore'}</span>
            </div>
            <div>
                {if !$fct_crypt_exists}
                    <div class="fct_crypt_error error alert alert-danger">
                        <p>
                            {l s='WebDAV cannot work with your current configuration. Please check the following requirements:' mod='ntbackupandrestore'}
                        </p>
                        <ul>
                            <li>{l s='You have at least PHP 5.6. Please check your PHP version in your hosting management.' mod='ntbackupandrestore'}</li>
                            <li>{l s='PHP openssl is loaded. Please enable it in your hosting management to use WebDAV.' mod='ntbackupandrestore'}</li>
                        </ul>
                    </div>
                    <br/>
                {/if}

                <p {if !$fct_crypt_exists || $light}class="deactivate"{/if}>
                    <button type="button" class="btn btn-default" id="send_webdav" name="send_webdav">
                        <i class="fas fa-cog"></i>
                        {l s='Accounts configuration' mod='ntbackupandrestore'}
                    </button>
                </p>
            </div>
            <div id="config_webdav_accounts" class="panel config_send_away_account" >
                <div class="panel-heading">
                    <i class="fas fa-cog"></i>
                    &nbsp;{l s='Send the backup on a WebDAV account.' mod='ntbackupandrestore'}
                </div>
                <div>
                    <p class="account_list" id="webdav_tabs">
                        <label>{l s='Account' mod='ntbackupandrestore'}</label>
                        <button type="button" class="btn btn-default choose_webdav_account active" id="webdav_account_0" value="0">
                            <i class="fas fa-plus"></i>
                        </button>
                        {foreach $webdav_accounts as $webdav_account}
                            <button type="button" class="btn btn-default choose_webdav_account inactive {if $webdav_account.active == 1}enable{else}disable{/if}" id="webdav_account_{$webdav_account.id_ntbr_webdav|intval}" value="{$webdav_account.id_ntbr_webdav|intval}">
                                {$webdav_account.name|escape:'html':'UTF-8'}
                            </button>
                        {/foreach}
                    </p>
                    <div class="webdav_account" id="webdav_account">
                        <p>
                            <input type="hidden" id="id_ntbr_webdav" name="id_ntbr_webdav" value="{$webdav_default.nb_backup|intval}" data-origin="{$webdav_default.id_ntbr_webdav|intval}" data-default="{$webdav_default.id_ntbr_webdav|intval}"/>
                            <label for="webdav_name">{l s='Account name' mod='ntbackupandrestore'}</label>
                            <span><input class="name_account" type="text" name="webdav_name" id="webdav_name" value="{$webdav_default.name|escape:'html':'UTF-8'} {$webdav_default.nb_account|intval}" data-origin="{$webdav_default.name|escape:'html':'UTF-8'}" data-default="{$webdav_default.name|escape:'html':'UTF-8'}"/></span>
                        </p>
                        <p>
                            <label for="active_webdav" id="active_webdav">{l s='Enabled' mod='ntbackupandrestore'}</label>
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="active_webdav" id="active_webdav_on" value="1" {if $webdav_default.active}checked="checked"{/if} data-origin="{$webdav_default.active|intval}" data-default="{$webdav_default.active|intval}"/>
                                <label class="t" for="active_webdav_on">
                                    {l s='Yes' mod='ntbackupandrestore'}
                                </label>
                                <input type="radio" name="active_webdav" id="active_webdav_off" value="0" {if !$webdav_default.active}checked="checked"{/if} data-origin="{$webdav_default.active|intval}" data-default="{$webdav_default.active|intval}"/>
                                <label class="t" for="active_webdav_off">
                                    {l s='No' mod='ntbackupandrestore'}
                                </label>
                                <a class="slide-button btn"></a>
                            </span>
                        </p>
                        <p>
                            <label for="nb_keep_backup_webdav">{l s='Complete backup to keep. 0 to never delete old complete backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old backups. 0 to never delete old backups' mod='ntbackupandrestore'}" name="nb_keep_backup_webdav" id="nb_keep_backup_webdav" value="{$webdav_default.nb_backup|intval}" data-origin="{$webdav_default.nb_backup|intval}" data-default="{$webdav_default.nb_backup|intval}"/></span>

                            <label for="nb_keep_backup_file_webdav">{l s='Only files backup to keep. 0 to never delete old files backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old files backups. 0 to never delete old files backups' mod='ntbackupandrestore'}" name="nb_keep_backup_file_webdav" id="nb_keep_backup_file_webdav" value="{$webdav_default.nb_backup_file|intval}" data-origin="{$webdav_default.nb_backup_file|intval}" data-default="{$webdav_default.nb_backup_file|intval}"/></span>

                            <label for="nb_keep_backup_base_webdav">{l s='Only database backup to keep. 0 to never delete old database backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old database backups. 0 to never delete old database backups' mod='ntbackupandrestore'}" name="nb_keep_backup_base_webdav" id="nb_keep_backup_base_webdav" value="{$webdav_default.nb_backup_base|intval}" data-origin="{$webdav_default.nb_backup_base|intval}" data-default="{$webdav_default.nb_backup_base|intval}"/></span>

                            <label for="webdav_user">{l s='User:' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="webdav_user" id="webdav_user" value="{$webdav_default.login|escape:'html':'UTF-8'}" data-origin="{$webdav_default.login|escape:'html':'UTF-8'}" data-default="{$webdav_default.login|escape:'html':'UTF-8'}"/></span>

                            <label for="webdav_pass">{l s='Pass:' mod='ntbackupandrestore'}</label>
                            <input type="password" class="decoy"/>
                            <span><input autocomplete="new-password" type="password" name="webdav_pass" id="webdav_pass" value="{$webdav_default.password|escape:'html':'UTF-8'}" data-origin="{$webdav_default.password|escape:'html':'UTF-8'}" data-default="{$webdav_default.password|escape:'html':'UTF-8'}"/></span>

                            <label for="webdav_server">{l s='Url (ex: "http://localhost/webdav/"):' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="webdav_server" id="webdav_server" value="{$webdav_default.server|escape:'html':'UTF-8'}" data-origin="{$webdav_default.server|escape:'html':'UTF-8'}" data-default="{$webdav_default.server|escape:'html':'UTF-8'}"/></span>

                            <label for="webdav_dir">{l s='Directory (ex: "backups"):' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="webdav_dir" id="webdav_dir" value="{$webdav_default.directory|escape:'html':'UTF-8'}" data-origin="{$webdav_default.directory|escape:'html':'UTF-8'}" data-default="{$webdav_default.directory|escape:'html':'UTF-8'}"/></span>
                        </p>
                    </div>
                </div>
                <div class="panel-footer">
                    <button type="button" class="btn btn-default" id="save_webdav" name="save_webdav">
                        <i class="far fa-save process_icon"></i>
                        {l s='Save' mod='ntbackupandrestore'}
                    </button>
                    <button type="button" class="btn btn-default" id="check_webdav" name="check_webdav">
                        <i class="fas fa-sync-alt process_icon"></i>
                        {l s='Check connection' mod='ntbackupandrestore'}
                    </button>
                    <button type="button" class="btn btn-default" id="delete_webdav" name="delete_webdav">
                        <i class="fas fa-trash-alt process_icon"></i>
                        {l s='Delete' mod='ntbackupandrestore'}
                    </button>
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-heading">
                <i class="icon_send_away send_googledrive"></i>
                &nbsp;<span>{l s='Send the backup on a Google Drive account.' mod='ntbackupandrestore'}</span>
            </div>
            <div>
                <p {if $light}class="deactivate"{/if}>
                    <button type="button" class="btn btn-default" id="send_googledrive" name="send_googledrive">
                        <i class="fas fa-cog"></i>
                        {l s='Accounts configuration' mod='ntbackupandrestore'}
                    </button>
                </p>
            </div>
            <div id="config_googledrive_accounts" class="panel config_send_away_account" >
                <div class="panel-heading">
                    <i class="fas fa-cog"></i>
                    &nbsp;{l s='Send the backup on a Google Drive account.' mod='ntbackupandrestore'}
                </div>
                <div>
                    <p class="account_list" id="googledrive_tabs">
                        <label>{l s='Account' mod='ntbackupandrestore'}</label>
                        <button type="button" class="btn btn-default choose_googledrive_account active" id="googledrive_account_0" value="0">
                            <i class="fas fa-plus"></i>
                        </button>
                        {foreach $googledrive_accounts as $googledrive_account}
                            <button type="button" class="btn btn-default choose_googledrive_account inactive {if $googledrive_account.active == 1}enable{else}disable{/if}" id="googledrive_account_{$googledrive_account.id_ntbr_googledrive|intval}" value="{$googledrive_account.id_ntbr_googledrive|intval}">
                                {$googledrive_account.name|escape:'html':'UTF-8'}
                            </button>
                        {/foreach}
                    </p>
                    <div class="googledrive_account" id="googledrive_account">
                        <p>
                            <input type="hidden" id="id_ntbr_googledrive" name="id_ntbr_googledrive" value="{$googledrive_default.id_ntbr_googledrive|intval}" data-origin="{$googledrive_default.id_ntbr_googledrive|intval}" data-default="{$googledrive_default.id_ntbr_googledrive|intval}"/>
                            <label for="googledrive_name">{l s='Account name' mod='ntbackupandrestore'}</label>
                            <span><input class="name_account" type="text" name="googledrive_name" id="googledrive_name" value="{$googledrive_default.name|escape:'html':'UTF-8'} {$googledrive_default.nb_account|intval}" data-origin="{$googledrive_default.name|escape:'html':'UTF-8'}" data-default="{$googledrive_default.name|escape:'html':'UTF-8'}"/></span>
                        </p>
                        <p>
                            <label for="active_googledrive" id="active_googledrive">{l s='Enabled' mod='ntbackupandrestore'}</label>
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="active_googledrive" id="active_googledrive_on" value="1" {if $googledrive_default.active}checked="checked"{/if} data-origin="{$googledrive_default.active|intval}" data-default="{$googledrive_default.active|intval}"/>
                                <label class="t" for="active_googledrive_on">
                                    {l s='Yes' mod='ntbackupandrestore'}
                                </label>
                                <input type="radio" name="active_googledrive" id="active_googledrive_off" value="0"  {if !$googledrive_default.active}checked="checked"{/if} data-origin="{$googledrive_default.active|intval}" data-default="{$googledrive_default.active|intval}"/>
                                <label class="t" for="active_googledrive_off">
                                    {l s='No' mod='ntbackupandrestore'}
                                </label>
                                <a class="slide-button btn"></a>
                            </span>
                        </p>
                        <p>
                            <label for="nb_keep_backup_googledrive">{l s='Complete backup to keep. 0 to never delete old complete backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old backups. 0 to never delete old backups' mod='ntbackupandrestore'}" name="nb_keep_backup_googledrive" id="nb_keep_backup_googledrive" value="{$googledrive_default.nb_backup|intval}" data-origin="{$googledrive_default.nb_backup|intval}" data-default="{$googledrive_default.nb_backup|intval}"/></span>

                            <label for="nb_keep_backup_file_googledrive">{l s='Only files backup to keep. 0 to never delete old files backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old files backups. 0 to never delete old files backups' mod='ntbackupandrestore'}" name="nb_keep_backup_file_googledrive" id="nb_keep_backup_file_googledrive" value="{$googledrive_default.nb_backup_file|intval}" data-origin="{$googledrive_default.nb_backup_file|intval}" data-default="{$googledrive_default.nb_backup_file|intval}"/></span>

                            <label for="nb_keep_backup_base_googledrive">{l s='Only database backup to keep. 0 to never delete old database backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old database backups. 0 to never delete old database backups' mod='ntbackupandrestore'}" name="nb_keep_backup_base_googledrive" id="nb_keep_backup_base_googledrive" value="{$googledrive_default.nb_backup_base|intval}" data-origin="{$googledrive_default.nb_backup_base|intval}" data-default="{$googledrive_default.nb_backup_base|intval}"/></span>

                            <label>{l s='1.' mod='ntbackupandrestore'}</label>
                            <button type="button" name="authentification_googledrive" id="authentification_googledrive" class="btn btn-default" onclick="window.open('{$googledrive_authorizeUrl|escape:'html':'UTF-8'}');">
                                <i class="fab fa-google-drive"></i> - {l s='Authentification' mod='ntbackupandrestore'}
                            </button>
                            <br/>
                            <label>{l s='2. Click "Allow" (you might have to log in first)' mod='ntbackupandrestore'}</label>
                            <br/>
                            <label for="googledrive_code">{l s='3. Copy the authorization code' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="googledrive_code" id="googledrive_code" value="" data-origin="" data-default=""/></span>

                            <label for="googledrive_dir">{l s='Directory' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="googledrive_dir_path" id="googledrive_dir_path" readonly="readonly" value="{$googledrive_default.directory_path|escape:'html':'UTF-8'}" data-origin="{$googledrive_default.directory_path|escape:'html':'UTF-8'}" data-default="{$googledrive_default.directory_path|escape:'html':'UTF-8'}"/></span>
                            <br/>
                            <span>
                                <button type="button" class="btn btn-default" id="display_googledrive_tree" name="display_googledrive_tree">
                                    <i class="fas fa-sitemap"></i>
                                    {l s='Display list of directories' mod='ntbackupandrestore'}
                                </button>
                                <input type="hidden" name="googledrive_dir" id="googledrive_dir" value="{$googledrive_default.directory_key|escape:'html':'UTF-8'}" data-origin="{$googledrive_default.directory_key|escape:'html':'UTF-8'}" data-default="{$googledrive_default.directory_key|escape:'html':'UTF-8'}"/>
                            </span>
                        </p>
                        <p id="googledrive_tree">

                        </p>
                    </div>
                </div>
                <div class="panel-footer">
                    <button type="button" class="btn btn-default" id="save_googledrive" name="save_googledrive">
                        <i class="far fa-save process_icon"></i>
                        {l s='Save' mod='ntbackupandrestore'}
                    </button>
                    <button type="button" class="btn btn-default" id="check_googledrive" name="check_googledrive">
                        <i class="fas fa-sync-alt process_icon"></i>
                        {l s='Check connection' mod='ntbackupandrestore'}
                    </button>
                    <button type="button" class="btn btn-default" id="delete_googledrive" name="delete_googledrive">
                        <i class="fas fa-trash-alt process_icon"></i>
                        {l s='Delete' mod='ntbackupandrestore'}
                    </button>
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-heading">
                <i class="icon_send_away send_onedrive"></i>
                &nbsp;<span>{l s='Send the backup on a OneDrive account.' mod='ntbackupandrestore'}</span>
            </div>
            <div>
                <p {if $light}class="deactivate"{/if}>
                    <button type="button" class="btn btn-default" id="send_onedrive" name="send_onedrive">
                        <i class="fas fa-cog"></i>
                        {l s='Accounts configuration' mod='ntbackupandrestore'}
                    </button>
                </p>
            </div>
            <div id="config_onedrive_accounts" class="panel config_send_away_account" >
                <div class="panel-heading">
                    <i class="fas fa-cog"></i>
                    &nbsp;{l s='Send the backup on a OneDrive account.' mod='ntbackupandrestore'}
                </div>
                <div>
                    <p class="account_list" id="onedrive_tabs">
                        <label>{l s='Account' mod='ntbackupandrestore'}</label>
                        <button type="button" class="btn btn-default choose_onedrive_account active" id="onedrive_account_0" value="0">
                            <i class="fas fa-plus"></i>
                        </button>
                        {foreach $onedrive_accounts as $onedrive_account}
                            <button type="button" class="btn btn-default choose_onedrive_account inactive {if $onedrive_account.active == 1}enable{else}disable{/if}" id="onedrive_account_{$onedrive_account.id_ntbr_onedrive|intval}" value="{$onedrive_account.id_ntbr_onedrive|intval}">
                                {$onedrive_account.name|escape:'html':'UTF-8'}
                            </button>
                        {/foreach}
                    </p>
                    <div class="onedrive_account" id="onedrive_account">
                        <p>
                            <input type="hidden" id="id_ntbr_onedrive" name="id_ntbr_onedrive" value="{$onedrive_default.id_ntbr_onedrive|intval}" data-origin="{$onedrive_default.id_ntbr_onedrive|intval}" data-default="{$onedrive_default.id_ntbr_onedrive|intval}"/>
                            <label for="onedrive_name">{l s='Account name' mod='ntbackupandrestore'}</label>
                            <span><input class="name_account" type="text" name="onedrive_name" id="onedrive_name" value="{$onedrive_default.name|escape:'html':'UTF-8'} {$onedrive_default.nb_account|intval}" data-origin="{$onedrive_default.name|escape:'html':'UTF-8'}" data-default="{$onedrive_default.name|escape:'html':'UTF-8'}"/></span>
                        </p>
                        <p>
                            <label for="active_onedrive" id="active_onedrive">{l s='Enabled' mod='ntbackupandrestore'}</label>
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="active_onedrive" id="active_onedrive_on" value="1" {if $onedrive_default.active}checked="checked"{/if} data-origin="{$onedrive_default.active|intval}" data-default="{$onedrive_default.active|intval}"/>
                                <label class="t" for="active_onedrive_on">
                                    {l s='Yes' mod='ntbackupandrestore'}
                                </label>
                                <input type="radio" name="active_onedrive" id="active_onedrive_off" value="0"  {if !$onedrive_default.active}checked="checked"{/if} data-origin="{$onedrive_default.active|intval}" data-default="{$onedrive_default.active|intval}"/>
                                <label class="t" for="active_onedrive_off">
                                    {l s='No' mod='ntbackupandrestore'}
                                </label>
                                <a class="slide-button btn"></a>
                            </span>
                        </p>
                        <p>
                            <label for="nb_keep_backup_onedrive">{l s='Complete backup to keep. 0 to never delete old complete backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old backups. 0 to never delete old backups' mod='ntbackupandrestore'}" name="nb_keep_backup_onedrive" id="nb_keep_backup_onedrive" value="{$onedrive_default.nb_backup|intval}" data-origin="{$onedrive_default.nb_backup|intval}" data-default="{$onedrive_default.nb_backup|intval}"/></span>

                            <label for="nb_keep_backup_file_onedrive">{l s='Only files backup to keep. 0 to never delete old files backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old files backups. 0 to never delete old files backups' mod='ntbackupandrestore'}" name="nb_keep_backup_file_onedrive" id="nb_keep_backup_file_onedrive" value="{$onedrive_default.nb_backup_file|intval}" data-origin="{$onedrive_default.nb_backup_file|intval}" data-default="{$onedrive_default.nb_backup_file|intval}"/></span>

                            <label for="nb_keep_backup_base_onedrive">{l s='Only database backup to keep. 0 to never delete old database backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old database backups. 0 to never delete old database backups' mod='ntbackupandrestore'}" name="nb_keep_backup_base_onedrive" id="nb_keep_backup_base_onedrive" value="{$onedrive_default.nb_backup_base|intval}" data-origin="{$onedrive_default.nb_backup_base|intval}" data-default="{$onedrive_default.nb_backup_base|intval}"/></span>

                            <label>{l s='1.' mod='ntbackupandrestore'}</label>
                            <button type="button" name="authentification_onedrive" id="authentification_onedrive" class="btn btn-default" onclick="window.open('{$onedrive_authorizeUrl|escape:'html':'UTF-8'}');">
                                <i class="fas fa-cloud"></i> - {l s='Authentification' mod='ntbackupandrestore'}
                            </button>
                            <br/>
                            <label>{l s='2. Click "Allow" (you might have to log in first)' mod='ntbackupandrestore'}</label>
                            <br/>
                            <label for="onedrive_code">{l s='3. Copy the authorization code' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="onedrive_code" id="onedrive_code" value="" data-origin="" data-default=""/></span>

                            <label for="onedrive_dir_path">{l s='Directory' mod='ntbackupandrestore'}</label>
                            <br/>
                            <span><input type="text" name="onedrive_dir_path" id="onedrive_dir_path" readonly="readonly" value="{$onedrive_default.directory_path|escape:'html':'UTF-8'}" data-origin="{$onedrive_default.directory_path|escape:'html':'UTF-8'}" data-default="{$onedrive_default.directory_path|escape:'html':'UTF-8'}"/></span>
                            <br/>
                            <span>
                                <button type="button" class="btn btn-default" id="display_onedrive_tree" name="display_onedrive_tree">
                                    <i class="fas fa-sitemap"></i>
                                    {l s='Display list of directories' mod='ntbackupandrestore'}
                                </button>
                                <input type="hidden" name="onedrive_dir" id="onedrive_dir" value="{$onedrive_default.directory_key|escape:'html':'UTF-8'}" data-origin="{$onedrive_default.directory_key|escape:'html':'UTF-8'}" data-default="{$onedrive_default.directory_key|escape:'html':'UTF-8'}"/>
                            </span>
                        </p>
                        <p id="onedrive_tree">

                        </p>
                    </div>
                </div>
                <div class="panel-footer">
                    <button type="button" class="btn btn-default" id="save_onedrive" name="save_onedrive">
                        <i class="far fa-save process_icon"></i>
                        {l s='Save' mod='ntbackupandrestore'}
                    </button>
                    <button type="button" class="btn btn-default" id="check_onedrive" name="check_onedrive">
                        <i class="fas fa-sync-alt process_icon"></i>
                        {l s='Check connection' mod='ntbackupandrestore'}
                    </button>
                    <button type="button" class="btn btn-default" id="delete_onedrive" name="delete_onedrive">
                        <i class="fas fa-trash-alt process_icon"></i>
                        {l s='Delete' mod='ntbackupandrestore'}
                    </button>
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-heading">
                <i class="icon_send_away send_hubic"></i>
                &nbsp;<span>{l s='Send the backup on a Hubic account.' mod='ntbackupandrestore'}</span>
            </div>
            <div>
                <p {if $light}class="deactivate"{/if}>
                    <button type="button" class="btn btn-default" id="send_hubic" name="send_hubic">
                        <i class="fas fa-cog"></i>
                        {l s='Accounts configuration' mod='ntbackupandrestore'}
                    </button>
                </p>
            </div>
            <div id="config_hubic_accounts" class="panel config_send_away_account" >
                <div class="panel-heading">
                    <i class="fas fa-cog"></i>
                    &nbsp;{l s='Send the backup on a Hubic account.' mod='ntbackupandrestore'}
                </div>
                <div>
                    <p class="account_list" id="hubic_tabs">
                        <label>{l s='Account' mod='ntbackupandrestore'}</label>
                        <button type="button" class="btn btn-default choose_hubic_account active" id="hubic_account_0" value="0">
                            <i class="fas fa-plus"></i>
                        </button>
                        {foreach $hubic_accounts as $hubic_account}
                            <button type="button" class="btn btn-default choose_hubic_account inactive {if $hubic_account.active == 1}enable{else}disable{/if}" id="hubic_account_{$hubic_account.id_ntbr_hubic|intval}" value="{$hubic_account.id_ntbr_hubic|intval}">
                                {$hubic_account.name|escape:'html':'UTF-8'}
                            </button>
                        {/foreach}
                    </p>
                    <div class="hubic_account" id="hubic_account">
                        <p>
                            <input type="hidden" id="id_ntbr_hubic" name="id_ntbr_hubic" value="{$hubic_default.id_ntbr_hubic|intval}" data-origin="{$hubic_default.id_ntbr_hubic|intval}" data-default="{$hubic_default.id_ntbr_hubic|intval}"/>
                            <label for="hubic_name">{l s='Account name' mod='ntbackupandrestore'}</label>
                            <span><input class="name_account" type="text" name="hubic_name" id="hubic_name" value="{$hubic_default.name|escape:'html':'UTF-8'} {$hubic_default.nb_account|intval}" data-origin="{$hubic_default.name|escape:'html':'UTF-8'}" data-default="{$hubic_default.name|escape:'html':'UTF-8'}"/></span>
                        </p>
                        <p>
                            <label for="active_hubic" id="active_hubic">{l s='Enabled' mod='ntbackupandrestore'}</label>
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="active_hubic" id="active_hubic_on" value="1" {if $hubic_default.active}checked="checked"{/if} data-origin="{$hubic_default.active|intval}" data-default="{$hubic_default.active|intval}"/>
                                <label class="t" for="active_hubic_on">
                                    {l s='Yes' mod='ntbackupandrestore'}
                                </label>
                                <input type="radio" name="active_hubic" id="active_hubic_off" value="0"  {if !$hubic_default.active}checked="checked"{/if} data-origin="{$hubic_default.active|intval}" data-default="{$hubic_default.active|intval}"/>
                                <label class="t" for="active_hubic_off">
                                    {l s='No' mod='ntbackupandrestore'}
                                </label>
                                <a class="slide-button btn"></a>
                            </span>
                        </p>
                        <p>
                            <label for="nb_keep_backup_hubic">{l s='Complete backup to keep. 0 to never delete old complete backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old backups. 0 to never delete old backups' mod='ntbackupandrestore'}" name="nb_keep_backup_hubic" id="nb_keep_backup_hubic" value="{$hubic_default.nb_backup|intval}" data-origin="{$hubic_default.nb_backup|intval}" data-default="{$hubic_default.nb_backup|intval}"/></span>

                            <label for="nb_keep_backup_file_hubic">{l s='Only files backup to keep. 0 to never delete old files backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old files backups. 0 to never delete old files backups' mod='ntbackupandrestore'}" name="nb_keep_backup_file_hubic" id="nb_keep_backup_file_hubic" value="{$hubic_default.nb_backup_file|intval}" data-origin="{$hubic_default.nb_backup_file|intval}" data-default="{$hubic_default.nb_backup_file|intval}"/></span>

                            <label for="nb_keep_backup_base_hubic">{l s='Only database backup to keep. 0 to never delete old database backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old database backups. 0 to never delete old database backups' mod='ntbackupandrestore'}" name="nb_keep_backup_base_hubic" id="nb_keep_backup_base_hubic" value="{$hubic_default.nb_backup_base|intval}" data-origin="{$hubic_default.nb_backup_base|intval}" data-default="{$hubic_default.nb_backup_base|intval}"/></span>

                            <label>{l s='1.' mod='ntbackupandrestore'}</label>
                            <button type="button" name="authentification_hubic" id="authentification_hubic" class="btn btn-default" onclick="window.open('{$hubic_authorizeUrl|escape:'html':'UTF-8'}');">
                                {l s='Authentification' mod='ntbackupandrestore'}
                            </button>
                            <br/>
                            <label>{l s='2. Click "Allow" (you might have to log in first)' mod='ntbackupandrestore'}</label>
                            <br/>
                            <label for="hubic_code">{l s='3. Copy the authorization code' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="hubic_code" id="hubic_code" value="" data-origin="" data-default=""/></span>

                            <label for="hubic_dir">{l s='Directory (ex: "backups")' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="hubic_dir" id="hubic_dir" value="{$hubic_default.directory|escape:'html':'UTF-8'}" data-origin="{$hubic_default.directory|escape:'html':'UTF-8'}" data-default="{$hubic_default.directory|escape:'html':'UTF-8'}"/></span>
                        </p>
                    </div>
                </div>
                <div class="panel-footer">
                    <button type="button" class="btn btn-default" id="save_hubic" name="save_hubic">
                        <i class="far fa-save process_icon"></i>
                        {l s='Save' mod='ntbackupandrestore'}
                    </button>
                    <button type="button" class="btn btn-default" id="check_hubic" name="check_hubic">
                        <i class="fas fa-sync-alt process_icon"></i>
                        {l s='Check connection' mod='ntbackupandrestore'}
                    </button>
                    <button type="button" class="btn btn-default" id="delete_hubic" name="delete_hubic">
                        <i class="fas fa-trash-alt process_icon"></i>
                        {l s='Delete' mod='ntbackupandrestore'}
                    </button>
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-heading">
                <i class="icon_send_away send_aws"></i>
                &nbsp;<span>{l s='Send the backup on a AWS account.' mod='ntbackupandrestore'}</span>
            </div>
            <div>
                <p {if $light}class="deactivate"{/if}>
                    <button type="button" class="btn btn-default" id="send_aws" name="send_aws">
                        <i class="fas fa-cog"></i>
                        {l s='Accounts configuration' mod='ntbackupandrestore'}
                    </button>
                </p>
            </div>
            <div id="config_aws_accounts" class="panel config_send_away_account" >
                <div class="panel-heading">
                    <i class="fas fa-cog"></i>
                    &nbsp;{l s='Send the backup on a AWS account.' mod='ntbackupandrestore'}
                </div>
                <div>
                    <p class="account_list" id="aws_tabs">
                        <label>{l s='Account' mod='ntbackupandrestore'}</label>
                        <button type="button" class="btn btn-default choose_aws_account active" id="aws_account_0" value="0">
                            <i class="fas fa-plus"></i>
                        </button>
                        {foreach $aws_accounts as $aws_account}
                            <button type="button" class="btn btn-default choose_aws_account inactive {if $aws_account.active == 1}enable{else}disable{/if}" id="aws_account_{$aws_account.id_ntbr_aws|intval}" value="{$aws_account.id_ntbr_aws|intval}">
                                {$aws_account.name|escape:'html':'UTF-8'}
                            </button>
                        {/foreach}
                    </p>
                    <div class="aws_account" id="aws_account">
                        <p>
                            <input type="hidden" id="id_ntbr_aws" name="id_ntbr_aws" value="{$aws_default.id_ntbr_aws|intval}" data-origin="{$aws_default.id_ntbr_aws|intval}" data-default="{$aws_default.id_ntbr_aws|intval}"/>
                            <label for="aws_name">{l s='Account name' mod='ntbackupandrestore'}</label>
                            <span><input class="name_account" type="text" name="aws_name" id="aws_name" value="{$aws_default.name|escape:'html':'UTF-8'} {$aws_default.nb_account|intval}" data-origin="{$aws_default.name|escape:'html':'UTF-8'}" data-default="{$aws_default.name|escape:'html':'UTF-8'}"/></span>
                        </p>
                        <p>
                            <label for="active_aws" id="active_aws">{l s='Enabled' mod='ntbackupandrestore'}</label>
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="active_aws" id="active_aws_on" value="1" {if $aws_default.active}checked="checked"{/if} data-origin="{$aws_default.active|intval}" data-default="{$aws_default.active|intval}"/>
                                <label class="t" for="active_aws_on">
                                    {l s='Yes' mod='ntbackupandrestore'}
                                </label>
                                <input type="radio" name="active_aws" id="active_aws_off" value="0"  {if !$aws_default.active}checked="checked"{/if} data-origin="{$aws_default.active|intval}" data-default="{$aws_default.active|intval}"/>
                                <label class="t" for="active_aws_off">
                                    {l s='No' mod='ntbackupandrestore'}
                                </label>
                                <a class="slide-button btn"></a>
                            </span>
                        </p>
                        <p>
                            <label for="nb_keep_backup_aws">{l s='Complete backup to keep. 0 to never delete old complete backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old backups. 0 to never delete old backups' mod='ntbackupandrestore'}" name="nb_keep_backup_aws" id="nb_keep_backup_aws" value="{$aws_default.nb_backup|intval}" data-origin="{$aws_default.nb_backup|intval}" data-default="{$aws_default.nb_backup|intval}"/></span>

                            <label for="nb_keep_backup_file_aws">{l s='Only files backup to keep. 0 to never delete old files backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old files backups. 0 to never delete old files backups' mod='ntbackupandrestore'}" name="nb_keep_backup_file_aws" id="nb_keep_backup_file_aws" value="{$aws_default.nb_backup_file|intval}" data-origin="{$aws_default.nb_backup_file|intval}" data-default="{$aws_default.nb_backup_file|intval}"/></span>

                            <label for="nb_keep_backup_base_aws">{l s='Only database backup to keep. 0 to never delete old database backups' mod='ntbackupandrestore'}</label>
                            <span><input type="text" title="{l s='Delete old database backups. 0 to never delete old database backups' mod='ntbackupandrestore'}" name="nb_keep_backup_base_aws" id="nb_keep_backup_base_aws" value="{$aws_default.nb_backup_base|intval}" data-origin="{$aws_default.nb_backup_base|intval}" data-default="{$aws_default.nb_backup_base|intval}"/></span>

                            <label for="aws_access_key_id">{l s='Access key ID' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="aws_access_key_id" id="aws_access_key_id" value="" data-origin="" data-default=""/></span>

                            <label for="aws_secret_access_key">{l s='Secret access key' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="aws_secret_access_key" id="aws_secret_access_key" value="" data-origin="" data-default=""/></span>

                            <label for="aws_region">{l s='Region' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="aws_region" id="aws_region" value="" data-origin="" data-default=""/></span>

                            <label for="aws_bucket">{l s='Bucket' mod='ntbackupandrestore'}</label>
                            <span><input type="text" name="aws_bucket" id="aws_bucket" value="" data-origin="" data-default=""/></span>

                            <label for="aws_directory_path">{l s='Directory' mod='ntbackupandrestore'}</label>
                            <br/>
                            <span><input type="text" name="aws_directory_path" id="aws_directory_path" readonly="readonly" value="{$aws_default.directory_path|escape:'html':'UTF-8'}" data-origin="{$aws_default.directory_path|escape:'html':'UTF-8'}" data-default="{$aws_default.directory_path|escape:'html':'UTF-8'}"/></span>
                            <br/>
                            <span>
                                <button type="button" class="btn btn-default" id="display_aws_tree" name="display_aws_tree">
                                    <i class="fas fa-sitemap"></i>
                                    {l s='Display list of directories' mod='ntbackupandrestore'}
                                </button>
                                <input type="hidden" name="aws_directory_key" id="aws_directory_key" value="{$aws_default.directory_key|escape:'html':'UTF-8'}" data-origin="{$aws_default.directory_key|escape:'html':'UTF-8'}" data-default="{$aws_default.directory_key|escape:'html':'UTF-8'}"/>
                            </span>
                        </p>
                        <p id="aws_tree">

                        </p>
                    </div>
                </div>
                <div class="panel-footer">
                    <button type="button" class="btn btn-default" id="save_aws" name="save_aws">
                        <i class="far fa-save process_icon"></i>
                        {l s='Save' mod='ntbackupandrestore'}
                    </button>
                    <button type="button" class="btn btn-default" id="check_aws" name="check_aws">
                        <i class="fas fa-sync-alt process_icon"></i>
                        {l s='Check connection' mod='ntbackupandrestore'}
                    </button>
                    <button type="button" class="btn btn-default" id="delete_aws" name="delete_aws">
                        <i class="fas fa-trash-alt process_icon"></i>
                        {l s='Delete' mod='ntbackupandrestore'}
                    </button>
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-heading">
                <i class="fas fa-history"></i>
                &nbsp;{l s='Send the restore file too.' mod='ntbackupandrestore'}
            </div>
            <p class="send_restore {if $light}deactivate{/if}">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="send_restore" id="send_restore_on" value="1" {if $send_restore}checked="checked"{/if}/>
                    <label class="t" for="send_restore_on">
                        {l s='Yes' mod='ntbackupandrestore'}
                    </label>
                    <input type="radio" name="send_restore" id="send_restore_off" value="0"  {if !$send_restore}checked="checked"{/if}/>
                    <label class="t" for="send_restore_off">
                        {l s='No' mod='ntbackupandrestore'}
                    </label>
                    <a class="slide-button btn"></a>
                </span>
            </p>
        </div>
    </div>
</div>
<p>
    <button type="button" class="btn btn-default" id="nt_advanced_config" name="nt_advanced_config">
        <i class="fas fa-sliders-h"></i>
        {l s='Advanced' mod='ntbackupandrestore'}
    </button>
</p>
<div id="nt_advanced_config_diplay">
    <p>
        <label for="activate_log" id="activate_log">{l s='Enable debug log. Write a file with all messages of the module. Only for debug.' mod='ntbackupandrestore'}</label>
        <span class="switch prestashop-switch fixed-width-lg">
            <input type="radio" name="activate_log" id="activate_log_on" value="1" {if $activate_log}checked="checked"{/if} />
            <label class="t" for="activate_log_on">
                {l s='Yes' mod='ntbackupandrestore'}
            </label>
            <input type="radio" name="activate_log" id="activate_log_off" value="0"  {if !$activate_log}checked="checked"{/if} />
            <label class="t" for="activate_log_off">
                {l s='No' mod='ntbackupandrestore'}
            </label>
            <a class="slide-button btn"></a>
        </span>
    </p>
    <p>
        <label for="part_size">
            {l s='Size max (in MB) for your backup files. 0 if you want to have only one file for all your backup, whatever the size' mod='ntbackupandrestore'}
        </label>
        <span>
            <input
                type="text"
                title="{l s='Cut your backup so each parts has a size inferior to the one set. 0 if you do not want to use this functionality' mod='ntbackupandrestore'}"
                name="part_size"
                id="part_size"
                value="{$part_size|intval}"/>
        </span>
    </p>
    <p>
        <label for="max_file_to_backup">
            {l s='Size max (in MB) of the files to add to the backup. 0 if you want to backup all your files, whatever the size' mod='ntbackupandrestore'}
        </label>
        <span>
            <input
                type="text"
                title="{l s='Ignore files with a size equal or larger than this value. 0 if you do not want to use this functionality' mod='ntbackupandrestore'}"
                name="max_file_to_backup"
                id="max_file_to_backup"
                value="{$max_file_to_backup|intval}"/>
        </span>
    </p>
    <p>
        <label for="disable_refresh" id="disable_refresh">
            {l s='Disable intermediate renewal. The backup will be performed without interruption but the server timeout must be large enough.' mod='ntbackupandrestore'}
        </label>
        <span class="switch prestashop-switch fixed-width-lg">
            <input type="radio" name="disable_refresh" id="disable_refresh_on" value="1" {if $disable_refresh}checked="checked"{/if} />
            <label class="t" for="disable_refresh_on">
                {l s='Yes' mod='ntbackupandrestore'}
            </label>
            <input type="radio" name="disable_refresh" id="disable_refresh_off" value="0"  {if !$disable_refresh}checked="checked"{/if} />
            <label class="t" for="disable_refresh_off">
                {l s='No' mod='ntbackupandrestore'}
            </label>
            <a class="slide-button btn"></a>
        </span>
    </p>
    <p>
        <label for="time_between_refresh">
            {l s='Duration of intermediate renewal (default %1$d seconds).' sprintf=$max_time_before_refresh mod='ntbackupandrestore'}
        </label>
        <span>
            <input
                type="text"
                name="time_between_refresh"
                id="time_between_refresh"
                value="{$time_between_refresh|intval}"/>
        </span>
    </p>
    <p>
        <label for="time_pause_between_refresh">
            {l s='Duration of the pause between two intermediate renewal.' mod='ntbackupandrestore'}
        </label>
        <span>
            <input
                type="text"
                name="time_pause_between_refresh"
                id="time_pause_between_refresh"
                value="{$time_pause_between_refresh|intval}"/>
        </span>
    </p>
    <p>
        <label for="time_between_progress_refresh">
            {l s='Duration between progress refresh (default %1$d second). Useful for small servers, it saves some resources but progress may be less reactive.' sprintf=$max_time_before_progress_refresh mod='ntbackupandrestore'}
        </label>
        <span>
            <input
                type="text"
                name="time_between_progress_refresh"
                id="time_between_progress_refresh"
                value="{$time_between_progress_refresh|intval}"/>
        </span>
    </p>
    <p>
        <label for="disable_server_timeout" id="disable_server_timeout">
            {l s='Attempt to disable server timeout (Currently, your server max execution time is %1$d seconds.)' sprintf=$max_execution_time mod='ntbackupandrestore'}
        </label>
        <span class="switch prestashop-switch fixed-width-lg">
            <input type="radio" name="disable_server_timeout" id="disable_server_timeout_on" value="1" {if $disable_server_timeout}checked="checked"{/if} />
            <label class="t" for="disable_server_timeout_on">
                {l s='Yes' mod='ntbackupandrestore'}
            </label>
            <input type="radio" name="disable_server_timeout" id="disable_server_timeout_off" value="0"  {if !$disable_server_timeout}checked="checked"{/if} />
            <label class="t" for="disable_server_timeout_off">
                {l s='No' mod='ntbackupandrestore'}
            </label>
            <a class="slide-button btn"></a>
        </span>
    </p>
    <p>
        <label for="increase_server_memory" id="increase_server_memory">
            {l s='Attempt to increase the memory limit to the maximum usually required (%1$dMB). Currently, the memory limit of your server is' sprintf=$min_memory_limit mod='ntbackupandrestore'} {$memory_limit|intval}{l s='MB' mod='ntbackupandrestore'}
        </label>
        <span class="switch prestashop-switch fixed-width-lg">
            <input type="radio" name="increase_server_memory" id="increase_server_memory_on" value="1" {if $increase_server_memory}checked="checked"{/if} />
            <label class="t" for="increase_server_memory_on">
                {l s='Yes' mod='ntbackupandrestore'}
            </label>
            <input type="radio" name="increase_server_memory" id="increase_server_memory_off" value="0"  {if !$increase_server_memory}checked="checked"{/if} />
            <label class="t" for="increase_server_memory_off">
                {l s='No' mod='ntbackupandrestore'}
            </label>
            <a class="slide-button btn"></a>
        </span>
    </p>
    <p>
        <label for="increase_server_memory_value">
            {l s='New memory limit.' mod='ntbackupandrestore'}
        </label>
        <span>
            <input
                type="text"
                name="increase_server_memory_value"
                id="increase_server_memory_value"
                value="{$increase_server_memory_value|intval}"/>
        </span>
    </p>
    <p>
        <label for="dump_low_interest_table" id="dump_low_interest_table">
            {l s='Dump low interest table. For efficiency, the module do not backup some tables (statistics tables) which may be very big and not very useful. If you want to backup them, enable this option. The backup may take much more time and have a bigger size.' mod='ntbackupandrestore'}
            {l s='Tables ignored : connections, connections_page, connections_source, statssearch, guest.' mod='ntbackupandrestore'}
        </label>
        <span class="switch prestashop-switch fixed-width-lg">
            <input type="radio" name="dump_low_interest_table" id="dump_low_interest_table_on" value="1" {if $dump_low_interest_table}checked="checked"{/if} />
            <label class="t" for="dump_low_interest_table_on">
                {l s='Yes' mod='ntbackupandrestore'}
            </label>
            <input type="radio" name="dump_low_interest_table" id="dump_low_interest_table_off" value="0"  {if !$dump_low_interest_table}checked="checked"{/if} />
            <label class="t" for="dump_low_interest_table_off">
                {l s='No' mod='ntbackupandrestore'}
            </label>
            <a class="slide-button btn"></a>
        </span>
    </p>
    <p>
        <label for="maintenance" id="maintenance">{l s='Put your shop in maintenance while creating your backup. Attention, your shop will be unusable for the duration of the backup.' mod='ntbackupandrestore'}</label>
        <span class="switch prestashop-switch fixed-width-lg">
            <input type="radio" name="maintenance" id="maintenance_on" value="1" {if $maintenance}checked="checked"{/if}/>
            <label class="t" for="maintenance_on">
                {l s='Yes' mod='ntbackupandrestore'}
            </label>
            <input type="radio" name="maintenance" id="maintenance_off" value="0"  {if !$maintenance}checked="checked"{/if}/>
            <label class="t" for="maintenance_off">
                {l s='No' mod='ntbackupandrestore'}
            </label>
            <a class="slide-button btn"></a>
        </span>
    </p>
    {*<p>
        <label for="encrypt_backup" id="encrypt_backup">{l s='Crypt you backup.' mod='ntbackupandrestore'}</label>
        <span class="switch prestashop-switch fixed-width-lg">
            <input type="radio" name="encrypt_backup" id="encrypt_backup_on" value="1" {if $encrypt_backup}checked="checked"{/if}/>
            <label class="t" for="encrypt_backup_on">
                {l s='Yes' mod='ntbackupandrestore'}
            </label>
            <input type="radio" name="encrypt_backup" id="encrypt_backup_off" value="0"  {if !$encrypt_backup}checked="checked"{/if}/>
            <label class="t" for="encrypt_backup_off">
                {l s='No' mod='ntbackupandrestore'}
            </label>
            <a class="slide-button btn"></a>
        </span>
    </p>*}
    <p>
        <label for="time_between_backups">
            {l s='Security duration between backups (default %1$d seconds). Prevents simultaneous launch of backups.' sprintf=$min_time_new_backup mod='ntbackupandrestore'}
        </label>
        <span>
            <input
                type="text"
                name="time_between_backups"
                id="time_between_backups"
                value="{$time_between_backups|intval}"/>
        </span>
    </p>
    <p>
        <button type="button" class="btn btn-default" name="display_progress" id="display_progress">{l s='Enable progress display for the running backup.' mod='ntbackupandrestore'}</button>
    </p>
    <p>
        <button type="button" class="btn btn-default" name="stop_backup" id="stop_backup">{l s='Stop the running backup.' mod='ntbackupandrestore'}</button>
    </p>
    <div {if $light}class="panel"{/if}>
        {if $light}
            <div class="light_version_error alert alert-info hint">
                <p>
                    {l s='These advanced options are only available in the' mod='ntbackupandrestore'}
                    <a href="{$link_full_version|escape:'htmlall':'UTF-8'}">{l s='full version of the module' mod='ntbackupandrestore'}</a>.
                    {l s='Compatibility with XSendFile allows the server to save resources while downloading your backup. Ignoring the products images make a much lighter backup faster. This is particularly useful for developers to test a production version locally. The ability to not compress the backup make a faster but heavier backup.' mod='ntbackupandrestore'}
                </p>
            </div>
            <br/>
        {/if}
        <div {if $light}class="light_version"{/if}>
            <p>
                <label for="activate_xsendfile" id="activate_xsendfile">
                    {l s='Enable XSendfile. XSendFile enables fast file download with very low use of processor and memory.' mod='ntbackupandrestore'}
                    {if !$xsendfile_detected}<br/><i>{l s='XSendFile not detected' mod='ntbackupandrestore'}</i>{/if}
                </label>
                <br/>
                <span class="switch prestashop-switch fixed-width-lg {if $light}deactivate{/if}">
                    <input type="radio" name="activate_xsendfile" id="activate_xsendfile_on" value="1" {if $activate_xsendfile}checked="checked"{/if} {if !$xsendfile_detected} disabled="disabled"{/if}/>
                    <label class="t" for="activate_xsendfile_on">
                        {l s='Yes' mod='ntbackupandrestore'}
                    </label>
                    <input type="radio" name="activate_xsendfile" id="activate_xsendfile_off" value="0"  {if !$activate_xsendfile}checked="checked"{/if} {if !$xsendfile_detected} disabled="disabled"{/if}/>
                    <label class="t" for="activate_xsendfile_off">
                        {l s='No' mod='ntbackupandrestore'}
                    </label>
                    <a class="slide-button btn"></a>
                </span>
            </p>
            <p>
                <label for="ignore_product_image" id="ignore_product_image">{l s='Ignore product image: Will not backup products images.' mod='ntbackupandrestore'}</label>
                <span class="switch prestashop-switch fixed-width-lg {if $light}deactivate{/if}">
                    <input type="radio" name="ignore_product_image" id="ignore_product_image_on" value="1" {if $ignore_product_image}checked="checked"{/if}/>
                    <label class="t" for="ignore_product_image_on">
                        {l s='Yes' mod='ntbackupandrestore'}
                    </label>
                    <input type="radio" name="ignore_product_image" id="ignore_product_image_off" value="0"  {if !$ignore_product_image}checked="checked"{/if}/>
                    <label class="t" for="ignore_product_image_off">
                        {l s='No' mod='ntbackupandrestore'}
                    </label>
                    <a class="slide-button btn"></a>
                </span>
            </p>
            <p>
                <label for="ignore_compression" id="ignore_compression">{l s='Do not compress backup. Useful for small servers, it saves some resources but backup file may be twice bigger.' mod='ntbackupandrestore'}</label>
                <span class="switch prestashop-switch fixed-width-lg {if $light}deactivate{/if}">
                    <input type="radio" name="ignore_compression" id="ignore_compression_on" value="1" {if $ignore_compression}checked="checked"{/if}/>
                    <label class="t" for="ignore_compression_on">
                        {l s='Yes' mod='ntbackupandrestore'}
                    </label>
                    <input type="radio" name="ignore_compression" id="ignore_compression_off" value="0"  {if !$ignore_compression}checked="checked"{/if}/>
                    <label class="t" for="ignore_compression_off">
                        {l s='No' mod='ntbackupandrestore'}
                    </label>
                    <a class="slide-button btn"></a>
                </span>
            </p>
            <p>
                <label for="delete_local_backup" id="delete_local_backup">{l s='Delete your local backup file if the backup is sent elsewhere.' mod='ntbackupandrestore'}</label>
                <span class="switch prestashop-switch fixed-width-lg {if $light}deactivate{/if}">
                    <input type="radio" name="delete_local_backup" id="delete_local_backup_on" value="1" {if $delete_local_backup}checked="checked"{/if}/>
                    <label class="t" for="delete_local_backup_on">
                        {l s='Yes' mod='ntbackupandrestore'}
                    </label>
                    <input type="radio" name="delete_local_backup" id="delete_local_backup_off" value="0"  {if !$delete_local_backup}checked="checked"{/if}/>
                    <label class="t" for="delete_local_backup_off">
                        {l s='No' mod='ntbackupandrestore'}
                    </label>
                    <a class="slide-button btn"></a>
                </span>
            </p>
        </div>
    </div>
    <div id="not_backup" class="panel">
        <div class="panel-heading">
            <i class="fas fa-cog"></i>
            &nbsp;{l s='Ignore some directories, files and tables.' mod='ntbackupandrestore'}
        </div>
        {if $light}
            <div class="light_version_error alert alert-info hint">
                <p>
                    {l s='This feature is only available in the' mod='ntbackupandrestore'}
                    <a href="{$link_full_version|escape:'htmlall':'UTF-8'}">{l s='full version of the module' mod='ntbackupandrestore'}</a>
                    {l s='which makes it possible to not save the files that are not useful to you or that are too big to be saved every time. It can be pdf files, video files or any other type of file. You can also skip entire directories.' mod='ntbackupandrestore'}
                </p>
            </div>
            <br/>
        {/if}
        <div {if $light}class="deactivate light_version"{/if}>
            <p class="alert alert-warning warn">
                {l s='Be careful, use these advanced options only if you know exactly what you are doing. Deleting folders, files or tables required to run prestashop will make your backup unusable! ' mod='ntbackupandrestore'}
            </p>
            <p>
                <label for="ignore_directories">{l s='Do not save the following directories. Separe all the values by ",". Ex: "themes/old_theme, themes/very_old_theme"' mod='ntbackupandrestore'}</label>
                <span><input type="text" title="{l s='Do not save the following directories.' mod='ntbackupandrestore'}" name="ignore_directories" id="ignore_directories" value="{$ignore_directories|escape:'html':'UTF-8'}"/></span>
            </p>
            <p>
                <label for="ignore_files_types">{l s='Do not save the following types of files. Separe all the values by ",". Ex: ".mp4, .pdf"' mod='ntbackupandrestore'}</label>
                <span><input type="text" title="{l s='Do not save the following types of files.' mod='ntbackupandrestore'}" name="ignore_files_types" id="ignore_files_types" value="{$ignore_files_types|escape:'html':'UTF-8'}"/></span>
            </p>
            <p>
                <label for="ignore_tables">{l s='Do not save the following tables. Separe all the values by ",". Ex: "ps_log, ps_mail"' mod='ntbackupandrestore'}</label>
                <span><input type="text" title="{l s='Do not save the following tables.' mod='ntbackupandrestore'}" name="ignore_tables" id="ignore_tables" value="{$ignore_tables|escape:'html':'UTF-8'}"/></span>
            </p>
        </div>
    </div>
</div>
<div class="panel-footer">
    <button id="nt_save_config_btn" class="btn btn-default pull-right">
        <i class="far fa-save process_icon"></i> {l s='Save' mod='ntbackupandrestore'}
    </button>
</div>