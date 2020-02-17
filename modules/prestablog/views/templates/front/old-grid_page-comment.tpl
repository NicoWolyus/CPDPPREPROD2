{*
 * 2008 - 2019 (c) Prestablog
 *
 * MODULE PrestaBlog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2
 *}

<!-- Module Presta Blog -->
<div id="prestablog-comments">
<h3>{l s='Add a comment' mod='prestablog'}</h3>
{if ($prestablog_config.prestablog_comment_only_login && $isLogged) || !$prestablog_config.prestablog_comment_only_login}
	{if !$isSubmit}
		<form action="{$LinkReal|escape:'html':'UTF-8'}&id={$news->id|intval}" method="post" class="std">
			<fieldset id="prestablog-comment">
				{if sizeof($errors)}
				<p id="errors">{foreach from=$errors item=Ierror name=errors}{$Ierror|escape:'htmlall':'UTF-8'}<br />{/foreach}</p>
				{/if}
				<p class="text">
					<label for="name">{l s='Name' mod='prestablog'}</label>
					<input type="text" class="text{if sizeof($errors) && array_key_exists('name', $errors)} errors{/if}" name="name" id="name" value="{$content_form.name|escape:'htmlall':'UTF-8'}" />
				</p>
				<p class="text">
					<label for="url">{l s='Url' mod='prestablog'}</label>
					<input type="text" class="text{if sizeof($errors) && array_key_exists('url', $errors)} errors{/if}" name="url" id="url" value="{$content_form.url|escape:'html':'UTF-8'}" />&nbsp;<small id="with-http">({l s='with http://' mod='prestablog'})</small>
				</p>
				<p class="textarea">
					<label for="comment">{l s='Comment' mod='prestablog'}</label>
					<textarea name="comment" id="comment" cols="26" rows="2" {if sizeof($errors) && array_key_exists('comment', $errors)}class="errors"{/if}>{$content_form.comment|escape:'htmlall':'UTF-8'}</textarea>
				</p>
				{if isset($AntiSpam)}
					<p class="text">
						<label for="{$AntiSpam.checksum|escape:'htmlall':'UTF-8'}">{l s='Antispam protection' mod='prestablog'} : <strong>{$AntiSpam.question|escape:'htmlall':'UTF-8'}</strong></label>
						<input type="text" class="text{if sizeof($errors) && array_key_exists($AntiSpam.checksum, $errors)} errors{/if}" name="{$AntiSpam.checksum|escape:'htmlall':'UTF-8'}" id="{$AntiSpam.checksum|escape:'htmlall':'UTF-8'}" value="{$content_form.antispam_checksum|escape:'htmlall':'UTF-8'}" />
					</p>
				{/if}
				<p class="submit">
					<input type="submit" class="btn-primary" name="submitComment" id="submitComment" value="{l s='Submit comment' mod='prestablog'}" />
				</p>
			</fieldset>
		</form>
	{else}
		<form id="submitOk" class="std">
			<fieldset>
				<h3>{l s='Your comment has successfully sent' mod='prestablog'}</h3>
				{if $prestablog_config.prestablog_comment_auto_actif}
				<p>{l s='This comment is automatically published.' mod='prestablog'}</p>
				{else}
				<p>{l s='Before published, your comment must be approve by an administrator.' mod='prestablog'}</p>
				{/if}
			</fieldset>
		</form>
	{/if}
{else}
	<form class="std">
		<fieldset id="prestablog-comment-register">
			<p style="text-align:center;">
				<a href="{$link->getPageLink('authentication', true)|escape:'html':'UTF-8'}">{l s='You must be register' mod='prestablog'}<br />{l s='Clic here to registered' mod='prestablog'}</a>
			</p>
		</fieldset>
	</form>
{/if}
{if sizeof($comments)}
<h3>
	{count($comments)|intval} {l s='comments' mod='prestablog'}
	{if $prestablog_config.prestablog_comment_subscription}
		<div id="abo">
		{if $Is_Subscribe}
			<a href="{$LinkReal|escape:'html':'UTF-8'}&d={$news->id|intval}">{l s='Stop my subscription to comments' mod='prestablog'}</a>
		{else}
			<a href="{$LinkReal|escape:'html':'UTF-8'}&a={$news->id|intval}">{l s='Subscribe to comments' mod='prestablog'}</a>
		{/if}
		</div>
	{/if}
</h3>

<div id="comments">
{foreach from=$comments item=comment name=Comment}
	<div class="comment">
		<h4>
			{if $comment.url}
				<a href="{$comment.url|escape:'html':'UTF-8'}" {if $prestablog_config.prestablog_comment_nofollow}rel="nofollow"{/if}>{$comment.name|escape:'htmlall':'UTF-8'}</a>
			{else}
				{$comment.name|escape:'htmlall':'UTF-8'}
			{/if}
		</h4>
		<hr />
		<p class="date-comment">{dateFormat date=$comment.date full=1}</p>
		<p>{$comment.comment|escape:'htmlall':'UTF-8'}</p>
	</div>
{/foreach}
</div>

{/if}
</div>
<!-- /Module Presta Blog -->
