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
    <i class="far fa-clock"></i>
    &nbsp;{l s='Automation' mod='ntbackupandrestore'}
</div>
<div {if !$activate_2nt_automation}class="deactivate"{/if}>
    {if !$activate_2nt_automation}
        <p class="error alert alert-danger">
            {l s='This option is not available for local websites.' mod='ntbackupandrestore'}
        </p>
    {/if}
    <p>
        <label for="automation_2nt" id="automation_2nt">{l s='Automation by 2n-tech.com at' mod='ntbackupandrestore'}</label>
        <select id="automation_2nt_hours" name="automation_2nt_hours">
            {for $i=0; $i<24; $i++}
                {if $i < 10}
                    {assign var='hours' value="0$i"}
                {else}
                    {assign var='hours' value=$i}
                {/if}
                <option {if $automation_2nt_hours == $i}selected="selected"{/if} value="{$i|intval}">{$hours|escape:'html':'UTF-8'}</option>
            {/for}
        </select>
        H
        <select id="automation_2nt_minutes" name="automation_2nt_minutes">
            {for $i=0; $i<60; $i++}
                {if $i < 10}
                    {assign var='minutes' value="0$i"}
                {else}
                    {assign var='minutes' value=$i}
                {/if}
                <option {if $automation_2nt_minutes == $i}selected="selected"{/if} value="{$i|intval}">{$minutes|escape:'html':'UTF-8'}</option>
            {/for}
        </select>
        <span class="switch prestashop-switch fixed-width-lg">
            <input type="radio" name="automation_2nt" id="automation_2nt_on" value="1" {if $automation_2nt}checked="checked"{/if}/>
            <label class="t" for="automation_2nt_on">
                {l s='Yes' mod='ntbackupandrestore'}
            </label>
            <input type="radio" name="automation_2nt" id="automation_2nt_off" value="0"  {if !$automation_2nt}checked="checked"{/if}/>
            <label class="t" for="automation_2nt_off">
                {l s='No' mod='ntbackupandrestore'}
            </label>
            <a class="slide-button btn"></a>
        </span>
    </p>
    <p class="alert alert-warning warn">
        {l s='The automation service by 2n-tech.com only start your backup automatically at the specified time. Your data is not sent to the 2n-tech.com server. It\'s always your server that runs your backup.' mod='ntbackupandrestore'}
    </p>
    <div class="panel-footer">
        <button id="nt_save_automation_btn" class="btn btn-default pull-right">
            <i class="far fa-save process_icon"></i> {l s='Save' mod='ntbackupandrestore'}
        </button>
    </div>
</div>
<p>
    <button type="button" class="btn btn-default" id="nt_advanced_automation" name="nt_advanced_automation">
        <i class="fas fa-sliders-h"></i>
        {l s='Advanced' mod='ntbackupandrestore'}
    </button>
</p>
<div id="nt_advanced_automation_diplay">
    <p>
        {l s='If you want to backup your site automatically yourself, you can create a CRON on your server.' mod='ntbackupandrestore'} <br/>
        {l s='The way to do this depends on your hosting.' mod='ntbackupandrestore'} <br/>
        {l s='To simplify the task, you will find below several usual techniques.' mod='ntbackupandrestore'} <br/>
    </p>

    <div id="cron_block">
        <ul id="nt_advanced_automation_tab">
            <li id="nt_aat_0" class="active">{l s='URL' mod='ntbackupandrestore'}</li>
            <li id="nt_aat_1">{l s='WGet' mod='ntbackupandrestore'}</li>
            <li id="nt_aat_2">{l s='cURL' mod='ntbackupandrestore'}</li>
            <li id="nt_aat_3">{l s='PHP Script' mod='ntbackupandrestore'}</li>
        </ul>
        <div class="clear"></div>

        <div class="nt_aat" id="nt_aat_0_content">
            <p>{l s='Direct URL to start the backup. Useful for services sites of Web Cron.' mod='ntbackupandrestore'}</p>
            <p>
                {l s='To start the full backup:' mod='ntbackupandrestore'} <br/>
                <a class="cron" href="{$create_backup_cron|escape:'html':'UTF-8'}">{$create_backup_cron|escape:'html':'UTF-8'}</a>
            </p>
            <p>
                {l s='To start backing up files only (not database):' mod='ntbackupandrestore'} <br/>
                <a class="cron" href="{$create_fileonly_backup|escape:'html':'UTF-8'}">{$create_fileonly_backup|escape:'html':'UTF-8'}</a>
            </p>
            <p>
                {l s='To start backing up database only (not files):' mod='ntbackupandrestore'} <br/>
                <a class="cron" href="{$create_databaseonly_backup|escape:'html':'UTF-8'}">{$create_databaseonly_backup|escape:'html':'UTF-8'}</a>
            </p>
        </div>
        <div class="nt_aat" id="nt_aat_1_content">
            <p>{l s='WGet works with most web hosts.' mod='ntbackupandrestore'}</p>
            <p>
                {l s='To start the full backup:' mod='ntbackupandrestore'} <br/>
                <span class="cron">wget -O - -q -t 1 --max-redirect=10000 "{$create_backup_cron|escape:'html':'UTF-8'}" >/dev/null 2>&1</span>
            </p>
            <p>
                {l s='To start backing up files only (not database):' mod='ntbackupandrestore'} <br/>
                <span class="cron">wget -O - -q -t 1 --max-redirect=10000 "{$create_fileonly_backup|escape:'html':'UTF-8'}" >/dev/null 2>&1</span>
            </p>
            <p>
                {l s='To start backing up database only (not files):' mod='ntbackupandrestore'} <br/>
                <span class="cron">wget -O - -q -t 1 --max-redirect=10000 "{$create_databaseonly_backup|escape:'html':'UTF-8'}" >/dev/null 2>&1</span>
            </p>
        </div>
        <div class="nt_aat" id="nt_aat_2_content">
            <p>{l s='CURL works with some web hosts.' mod='ntbackupandrestore'}</p>
            <p>
                {l s='To start the full backup:' mod='ntbackupandrestore'} <br/>
                <span class="cron">curl -L --max-redirs 10000 -s "{$create_backup_cron|escape:'html':'UTF-8'}" >/dev/null 2>&1</span>
            </p>
            <p>
                {l s='To start backing up files only (not database):' mod='ntbackupandrestore'} <br/>
                <span class="cron">curl -L --max-redirs 10000 -s "{$create_fileonly_backup|escape:'html':'UTF-8'}" >/dev/null 2>&1</span>
            </p>
            <p>
                {l s='To start backing up database only (not files):' mod='ntbackupandrestore'} <br/>
                <span class="cron">curl -L --max-redirs 10000 -s "{$create_databaseonly_backup|escape:'html':'UTF-8'}" >/dev/null 2>&1</span>
            </p>
        </div>
        <div class="nt_aat" id="nt_aat_3_content">
            <p>{l s='You can directly integrate into your PHP scripts the backup startup' mod='ntbackupandrestore'}</p>
            <p>{l s='To start the full backup:' mod='ntbackupandrestore'}</p>
            <pre class="cron">
        $curl_handle=curl_init();
        curl_setopt($curl_handle,CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle,CURLOPT_MAXREDIRS, 10000);
        curl_setopt($curl_handle, CURLOPT_URL, '{$create_backup_cron|escape:'html':'UTF-8'}');
        $result = curl_exec($curl_handle);
        curl_close($curl_handle);
        if (empty($result))
            echo '{l s='An error occured during backup' mod='ntbackupandrestore'}';
        else
            echo $result;
            </pre>
            <p>{l s='To start backing up files only (not database):' mod='ntbackupandrestore'}</p>
            <pre class="cron">
        $curl_handle=curl_init();
        curl_setopt($curl_handle,CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle,CURLOPT_MAXREDIRS, 10000);
        curl_setopt($curl_handle, CURLOPT_URL, '{$create_fileonly_backup|escape:'html':'UTF-8'}');
        $result = curl_exec($curl_handle);
        curl_close($curl_handle);
        if (empty($result))
            echo '{l s='An error occured during backup' mod='ntbackupandrestore'}';
        else
            echo $result;
            </pre>
            <p>{l s='To start backing up database only (not files):' mod='ntbackupandrestore'}</p>
            <pre class="cron">
        $curl_handle=curl_init();
        curl_setopt($curl_handle,CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle,CURLOPT_MAXREDIRS, 10000);
        curl_setopt($curl_handle, CURLOPT_URL, '{$create_databaseonly_backup|escape:'html':'UTF-8'}');
        $result = curl_exec($curl_handle);
        curl_close($curl_handle);
        if (empty($result))
            echo '{l s='An error occured during backup' mod='ntbackupandrestore'}';
        else
            echo $result;
            </pre>
        </div>
    </div>
    <div>
        <p>{l s='You can also automate your backup download with a secure link. Please click on the button below to generate this secure link.' mod='ntbackupandrestore'}</p>
        <p>
            <button type="button" name="generate_url" id="generate_url" class="btn btn-default">
                <i class="fas fa-link"></i>
                {l s='Generate secure download link' mod='ntbackupandrestore'}
            </button>
        </p>
        <div id="download_links">
            <p>{l s='You can download the backup with this URL:' mod='ntbackupandrestore'}</p>
            <p class="backup_link"></p>
            {if $activate_log}
                <p>{l s='You can download the log with this URL:' mod='ntbackupandrestore'}</p>
                <p class="backup_log"></p>
            {/if}
        </div>
    </div>
</div>