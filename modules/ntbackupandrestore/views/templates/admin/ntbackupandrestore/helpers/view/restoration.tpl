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
    <i class="fas fa-history"></i>
    &nbsp;{l s='Restoration' mod='ntbackupandrestore'}
</div>
<div>
    {if $light}
        <div class="light_version_error alert alert-info hint">
            <p>
                {l s='Your backup can be restored with a script or directly from the module. The restore script allows restoration in all cases, whether on the current domain, another domain or even offline. Direct restore from the module is a feature available only in the' mod='ntbackupandrestore'}
                <a href="{$link_full_version|escape:'htmlall':'UTF-8'}">{l s='full version of the module' mod='ntbackupandrestore'}</a>.
            </p>
        </div>
        <br/>
    {/if}
    <div class="alert alert-info hint">
        <p>{l s='WARNING! Direct restoration on your site is not a trivial operation, do not use this feature lightly.' mod='ntbackupandrestore'}</p>
        <p>{l s='The shop will be restored on the date of your backup. As a result you will lose all data between the date of your backup and now.' mod='ntbackupandrestore'}</p>
        <p>{l s='Before restoring your shop, we strongly advise you to download the backup file to your computer, just in case.' mod='ntbackupandrestore'}</p>
    </div>
</div>
<div id="restoration" class="panel {if $light}deactivate light_version{/if}">
    <div class="panel-heading">
        <i class="fas fa-archive"></i>
        &nbsp;{l s='Backup files to restore' mod='ntbackupandrestore'}
    </div>
    <p>
        <label for="choose_type_backup_files">{l s='Restore' mod='ntbackupandrestore'}</label>
        <select name="choose_type_backup_files" id="choose_type_backup_files">
            <option value="{$backup_type_complete|escape:'htmlall':'UTF-8'}">{l s='Complete' mod='ntbackupandrestore'}</option>
            <option value="{$backup_type_file|escape:'htmlall':'UTF-8'}">{l s='Files' mod='ntbackupandrestore'}</option>
            <option value="{$backup_type_base|escape:'htmlall':'UTF-8'}">{l s='Database' mod='ntbackupandrestore'}</option>
        </select>
    </p>
    <div id="restore_backup_{$backup_type_complete|escape:'htmlall':'UTF-8'}_files">
        {foreach from=$restore_backup_files_complete key=nb item=backup_complete}
            <p>
                <input type="radio" title="{l s='Choose' mod='ntbackupandrestore'}" name="restore_backup" id="restore_backup_complete_{$nb|intval}" class="restore_backup btn btn-default"/>
                <label for="restore_backup_complete_{$nb|intval}">{$backup_complete.date|escape:'htmlall':'UTF-8'} - <span class="backup_name">{$backup_complete.name|escape:'htmlall':'UTF-8'}</span> <span class="backup_size">({$backup_complete.size|escape:'htmlall':'UTF-8'})</span></label>


                {assign var=backup_complete_name value=$backup_complete.name}
                {if isset($list_comments.$backup_complete_name.comment)}{l s='Comment:' mod='ntbackupandrestore'} {$list_comments.$backup_complete_name.comment|escape:'html':'UTF-8'}{/if}
            </p>
        {/foreach}
    </div>
    <div class="list_backup_type" id="restore_backup_{$backup_type_file|escape:'htmlall':'UTF-8'}_files">
        {foreach from=$restore_backup_files_file key=nb item=backup_file}
            <p>
                <input type="radio" title="{l s='Choose' mod='ntbackupandrestore'}" name="restore_backup" id="restore_backup_file_{$nb|intval}" class="restore_backup btn btn-default"/>
                <label for="restore_backup_file_{$nb|intval}">{$backup_file.date|escape:'htmlall':'UTF-8'} - <span class="backup_name">{$backup_file.name|escape:'htmlall':'UTF-8'}</span> <span class="backup_size">({$backup_file.size|escape:'htmlall':'UTF-8'})</span></label>

                {assign var=backup_file_name value=$backup_file.name}
                {if isset($list_comments.$backup_file_name.comment)}{l s='Comment:' mod='ntbackupandrestore'} {$list_comments.$backup_file_name.comment|escape:'html':'UTF-8'}{/if}
            </p>
        {/foreach}
    </div>
    <div class="list_backup_type" id="restore_backup_{$backup_type_base|escape:'htmlall':'UTF-8'}_files">
        {foreach from=$restore_backup_files_base key=nb item=backup_base}
            <p>
                <input type="radio" title="{l s='Choose' mod='ntbackupandrestore'}" name="restore_backup" id="restore_backup_base_{$nb|intval}" class="restore_backup btn btn-default"/>
                <label for="restore_backup_base_{$nb|intval}">{$backup_base.date|escape:'htmlall':'UTF-8'} - <span class="backup_name">{$backup_base.name|escape:'htmlall':'UTF-8'}</span> <span class="backup_size">({$backup_base.size|escape:'htmlall':'UTF-8'})</span></label>

                {assign var=backup_base_name value=$backup_base.name}
                {if isset($list_comments.$backup_base_name.comment)}{l s='Comment:' mod='ntbackupandrestore'} {$list_comments.$backup_base_name.comment|escape:'html':'UTF-8'}{/if}
            </p>
        {/foreach}
    </div>
    <div class="alert alert-warning warn">
        <h4>{l s='Disclaimer before restoring a backup' mod='ntbackupandrestore'}</h4>
        <ol>
            <li>{l s='2N Technologies is not responsible for your database, files, backups or restores.' mod='ntbackupandrestore'}</li>
            <li>{l s='You are using NT Backup And Restore at your own risk under the license agreement.' mod='ntbackupandrestore'}</li>
            <li>{l s='Your existing databases and files will be deleted if you restore a backup.' mod='ntbackupandrestore'}</li>
            <li>{l s='You must have checked that your backup is correct before restoring it.' mod='ntbackupandrestore'}</li>
        </ol>
        <p>
            <button type="button" name="start_restore" id="start_restore" class="btn btn-default">
                <i class="far fa-save fa-lg"></i>
                {l s='I have read the disclaimer. Restore the backup.' mod='ntbackupandrestore'}
            </button>
        </p>
    </div>
</div>