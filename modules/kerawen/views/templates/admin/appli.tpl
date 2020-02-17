{*
 * 2014 KerAwen
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@kerawen.com so we can send you a copy immediately.
 *
 *  @author    KerAwen <contact@kerawen.com>
 *  @copyright 2014 KerAwen
 *  @license   http://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 *}

<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<title>{$title|escape}</title>
		<meta name="mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="google" value="notranslate">
		<meta name="viewport" content="width=1280, initial-scale=1.0">
		<link href="{$server|escape}/img/logo.png" type="image/png" rel="icon">
		<script type="text/javascript" src="{$server|escape}/../res/jquery-2.1.3.min.js"></script>
		<script type="text/javascript">
			jQuery.noConflict();
			jQuery(window).load(function() {
				jQuery.ajax({
					url: "{$server|escape}/start.php",
					method: "POST",
					data: {
						shop: "{$shop|escape}",
						key: "{$key|escape}",
						version: "{$version|escape}",
						psver: "{$psver|escape}",
						appli: "{$appli|escape}",
						lang: "{$lang|escape}",
						ws: "{$ws}",
						css: "{$css|escape}",
						printer : "{$printer}",
					},
					success: function(response) {
						jQuery("body").empty().append(response);
					},
				});
			});
		</script>
	</head>
	<body>
		<div style="text-align:center">
			<p>
				{if $lang eq "fr"}
					Chargement de l'application, veuillez patienter...
				{else}
					Please wait while application is loading...
				{/if}
			</p>
		</div>
	</body>
</html>
