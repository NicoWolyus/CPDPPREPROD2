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

<div class="alert alert-warning warn">
    <h4>{l s='Disclaimer before creating a new backup' mod='ntbackupandrestore'}</h4>
    <ol>
        <li>{l s='2N Technologies is not responsible for your database, files, backups or restores.' mod='ntbackupandrestore'}</li>
        <li>{l s='You are using NT Backup And Restore at your own risk under the license agreement.' mod='ntbackupandrestore'}</li>
        <li>{l s='Your existing databases and files will be deleted if you restore a backup.' mod='ntbackupandrestore'}</li>
        <li>{l s='Always verify that your backup files are complete, up-to-date and valid, even if a success message appeared during the backup process.' mod='ntbackupandrestore'}</li>
    </ol>
    <p>
        <button type="button" name="create_backup" id="create_backup" class="btn btn-default">
            <i class="far fa-save fa-lg"></i>
            {l s='I have read the disclaimer. Please create a new backup.' mod='ntbackupandrestore'}
        </button>
    </p>
</div>

<div id="backup_progress_panel" class="panel">
    <div class="panel-heading">
        <i class="fas fa-road"></i>
        &nbsp;{l s='Progress' mod='ntbackupandrestore'}
    </div>
    <div id="backup_progress"></div>
</div>

<div class="panel">
    <div class="panel-heading">
        <i class="fas fa-archive"></i>
        &nbsp;{l s='Backup files' mod='ntbackupandrestore'}
    </div>
    <div id="backup_files">
        {foreach from=$backup_files key=nb item=backup}
            <p id="backup{$nb|intval}">
                <span class="backup_list_content_left">
                    {if $backup.part|@count > 1}
                        <button type="button" title="{l s='See' mod='ntbackupandrestore'}" nb="{$nb|intval}" name="backup_see" class="backup_see btn btn-default">
                            <i class="fas fa-eye"></i>
                        </button>
                    {else}
                        <button type="button" title="{l s='Download' mod='ntbackupandrestore'}" nb="{$nb|intval}" name="backup_download" class="backup_download btn btn-default">
                            <i class="fas fa-download"></i>
                        </button>
                        {*<button type="button" title="{l s='Send away' mod='ntbackupandrestore'}" nb="{$nb|intval}" name="send_backup" class="send_backup btn btn-default">
                            <i class="fas fa-share"></i>
                        </button>*}
                    {/if}

                    {if !$light}
                        <button type="button" title="{l s='Send away' mod='ntbackupandrestore'}" nb="{$nb|intval}" name="send_backup" class="send_backup btn btn-default">
                            <i class="fas fa-share"></i>
                        </button>
                    {/if}
                    <button type="button" title="{l s='Delete' mod='ntbackupandrestore'}" nb="{$nb|intval}" name="delete_backup" class="delete_backup btn btn-default">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                    <span>{$backup.date|escape:'htmlall':'UTF-8'} : </span><span class="backup_name">{$backup.name|escape:'htmlall':'UTF-8'}</span> <span class="backup_size">({$backup.size|escape:'htmlall':'UTF-8'})</span>
                </span>
                <span class="backup_list_content_right">
                    {assign var=backup_name value=$backup.name}
                    <button type="button" title="{l s='Save' mod='ntbackupandrestore'}" nb="{$nb|intval}" name="save_comment_backup" class="save_comment_backup btn btn-default">
                        <i class="far fa-save fa-lg"></i>
                    </button>
                    <input class="backup_comment" type="text" placeholder="{l s='Comment' mod='ntbackupandrestore'}" title="{l s='Comment' mod='ntbackupandrestore'}" name="comment_backup[{$nb|intval}]" id="comment_backup_{$nb|intval}" value="{if isset($list_comments.$backup_name.comment)}{$list_comments.$backup_name.comment|escape:'html':'UTF-8'}{/if}"/>
                </span>
                <span class="clear"></span>
            </p>
            {if $backup.part|@count > 1}
                <ul id="sub_backups{$nb|intval}" class="sub_backup">
                    {foreach from=$backup.part key=nb_part item=part}
                        <li class="{$nb_part|escape:'htmlall':'UTF-8'}">
                            <button type="button" title="{l s='Download' mod='ntbackupandrestore'}" nb="{$nb_part|escape:'htmlall':'UTF-8'}" name="backup_download" class="backup_download btn btn-default">
                                <i class="fas fa-download"></i>
                            </button>
                            {if !$light}
                                <button type="button" title="{l s='Send away' mod='ntbackupandrestore'}" nb="{$nb_part|escape:'htmlall':'UTF-8'}" name="send_backup" class="send_backup btn btn-default">
                                    <i class="fas fa-share"></i>
                                </button>
                            {/if}
                            <button type="button" title="{l s='Delete' mod='ntbackupandrestore'}" nb="{$nb_part|escape:'htmlall':'UTF-8'}" name="delete_backup" class="delete_backup btn btn-default">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                            {$part.name|escape:'htmlall':'UTF-8'} ({$part.size|escape:'htmlall':'UTF-8'})
                        </li>
                    {/foreach}
                </ul>
            {/if}
        {/foreach}
    </div>
</div>

<div class="panel" >
    <div class="panel-heading">
        <i class="fas fa-rocket"></i>
        &nbsp;{l s='Restoration script' mod='ntbackupandrestore'}
    </div>
    <div class="alert alert-info hint">
        <h4>{l s='The restore script allows you to restore your shop in the following cases:' mod='ntbackupandrestore'}</h4>
        <ul>
            <li>{l s='You no longer have access to your backoffice or module (crash, hack ...).' mod='ntbackupandrestore'}</li>
            <li>{l s='You are changing your server.' mod='ntbackupandrestore'}</li>
            <li>{l s='You are changing your domain name.' mod='ntbackupandrestore'}</li>
            <li>{l s='You are changing your database.' mod='ntbackupandrestore'}</li>
            <li>{l s='You are a developer or an advanced user.' mod='ntbackupandrestore'}</li>
            <li>{l s='In any other cases.' mod='ntbackupandrestore'}</li>
        </ul>
    </div>
    <p>
        <button type="button" name="restore_download" id="restore_download" class="btn btn-default">
            <i class="fas fa-download"></i>
            {l s='Download the restoration script' mod='ntbackupandrestore'}
        </button>
    </p>
</div>
<div class="panel" id="log_button" {if !$activate_log}style="display:none;"{/if}>
    <div class="panel-heading">
        <i class="far fa-file-alt"></i>
        &nbsp;{l s='Log file' mod='ntbackupandrestore'}
    </div>
    <p>
        <button type="button" name="backup_log_download" id="backup_log_download" class="btn btn-default">
            <i class="fas fa-download"></i>
            {l s='Download the log' mod='ntbackupandrestore'}
        </button>
    </p>
</div>