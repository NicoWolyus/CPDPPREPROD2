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
 
<style>
#kerawen {
	padding: 0 4em;
}
#kerawen section {
	/*clear: both;*/
	/*padding-top: 2em;*/
}
#kerawen .header {
	font-size: 1.2em;
	margin-top: 1em;
}
#kerawen .logo {
	float: left;
	padding-right: 2em;
}
#kerawen .logo img {
	height: 6em;
}
#kerawen h2 {
	margin-top: 2em;
}
#kerawen p {
	text-align: justify;
}
#kerawen .center {
	text-align: center;
}
#kerawen form {
	margin: 2em 0;
}
#kerawen .action {
	margin-top: 2em;
	text-align: center;
}
#kerawen .action button {
	padding: 0 1em 0.25em 1em;
	background: #E97A04;
	color: #FFFFFF;
	border: 2px solid #FF9B1A;
	font-size: 1.5em;
	font-weight: bold;
	font-variant: small-caps;
}
/* PrestaShop 1.6.0.6 workaround */
#content.bootstrap .panel {
	display: none;
}
</style>

<div id="kerawen">
	<section class="col-lg-12 header">
		<a class="logo" href="http://www.kerawen.com" target="_blank">
			<img src="../modules/kerawen/img/kerawen.png"></img>
		</a>
		<p>
			{if $lang eq "fr"}
				Le module KerAwen POS, certifié par Prestashop,
				permet un pilotage centralisé de votre activité: magasin, web et mobile. 
				Chacune des opérations sur les produits, les stocks, les commandes, les clients, les avoirs, les coupons, le programme de fidélité
				se fait indifféremment depuis la caisse KerAwen, le front-office ou le back-office PrestaShop
				avec un effet immédiat sur les trois supports.
			{else}
				The KerAwen POS module, which is certified by Prestashop,
				allows you to centalize the management of your physical store, web site and mobile app.
				Each operation on products, stocks, orders, customers, credits, vouchers, loyalty program
				takes place indifferently from the KerAwen cash register application, PrestaShop front-office or back-office
				with an immediate effect on the three supports.
			{/if}
		</p>
	</section>
	<section class="col-lg-12">
		<h2>
			{if $lang eq "fr"}
				Comment démarrer la caisse&nbsp;?
			{else}
				How to start the cash register&nbsp;?
			{/if}
		</h2>
	</section>
	<section class="col-lg-6">
		{if $key}
			<p>
				{if $lang eq "fr"}
					Pour accéder à la caisse plus rapidement:
				{else}
					To start the cash register more quickly:
				{/if}
			</p>
			<ul>
				<li>
					{if $lang eq "fr"}
						Depuis le menu back-office PrestaShop, onglet "KERAWEN", entrée "Caisse"
					{else}
						From the PrestaShop back-office menu, "KERAWEN" tab, "Cash register" item
					{/if}
				</li>
				<li>
					{if $lang eq "fr"}
						Ou bien par le menu "Accès rapide", entrée "Caisse KerAwen"
					{else}
						Or from the "Quick Access" menu, "KerAwen cash register" item
					{/if}
				</li>
			</ul>
			<div class="action">
				<button class="start">
					{if $lang eq "fr"}
						Démarrer la caisse
					{else}
						Start cash register
					{/if}
				</button>
			</div>
		{else}
			<p>
				{if $lang eq "fr"}
					Pour démarrer la caisse et l'utiliser <b>gratuitement et sans engagement pendant 15 jours</b>,
					il vous suffit de vous enregistrer ci-dessous.
				{else}
					You can start the cash register immediatly without any subscription,
					we offer you a 15 days free trial.
				{/if}
			</p>
			<p>
				{if $lang eq "fr"}
					Par la suite, vous pourrez accéder à la caisse plus rapidement:
				{else}
					Later on, you can start the cash register more quickly:
				{/if}
			</p>
			<ul>
				<li>
					{if $lang eq "fr"}
						Depuis le menu back-office PrestaShop, onglet "KERAWEN", entrée "Caisse"
					{else}
						From the PrestaShop back-office menu, "KERAWEN" tab, "Cash register" item
					{/if}
				</li>
				<li>
					{if $lang eq "fr"}
						Ou bien par le menu "Accès rapide", entrée "Caisse KerAwen"
					{else}
						Or from the "Quick Access" menu, "KerAwen cash register" item
					{/if}
				</li>
			</ul>
			<form action="{$server|escape}/../subscribe.php" method="POST">
				<input type="hidden" name="version" value="{$version|escape}">
				<input type="hidden" name="psver" value="{$psver|escape}">
				<div class="form-group">
					<label for="shop">
						Boutique
					</label>
					<input name="shop" type="text"
						placeholder="URL de ma boutique"
						readonly="readonly"
						value="{$shop|escape}"> 
				</div>
				<div class="form-group">
					<label for="email">
						Email
					</label>
					<input name="email" type="text"
						placeholder="Mon addresse email"
						required="required"
						value="{$email|escape}"> 
				</div>
				<div class="form-group">
					<label for="name">
						Nom
					</label>
					<input name="name" type="text"
						placeholder="Mon nom"
						required="required"
						value="{$name|escape}"> 
				</div>
				<div class="form-group">
					<label for="phone">
						Téléphone
					</label>
					<input name="phone" type="text"
						placeholder="Mon numéro de téléphone pour un meilleur support"
						required="required"
						value=""> 
				</div>
				<div class="checkbox">
					<label>
						<input name="conditions" type="checkbox" required="required">
						J'accepte les
						<a href="http://www.kerawen.com/mentions-legales" target="_blank">conditions d'utilisation</a>
					</label>
				</div>
				<div class="action">
					<button type="submit">
						M'enregistrer et démarrer la caisse
					</button>
				</div>
			</form>
		{/if}
	</section>
	<section class="col-lg-6 center">
		<img src="../modules/kerawen/img/bo-menu.png"></img>
		<img src="../modules/kerawen/img/qa-menu.png"></img>
	</section>
	<section class="col-lg-12">
		<h2>
			{if $lang eq "fr"}
				Faire sa première vente en magasin
			{else}
				First sale
			{/if}
		</h2>
		<p>
			<iframe src="http://www.kerawen.com/videos/premiere-vente" frameborder="0" style="height:320px;width:500px"></iframe>
		</p>
	</section>
	<section class="col-lg-12">
		<h2>
			{if $lang eq "fr"}
				Besoin d’aide&nbsp;?
			{else}
				Need help&nbsp;?
			{/if}
		</h2>
		<p>
			{if $lang eq "fr"}
				Visitez notre <a href="http://www.kerawen.com/support" target="_blank">page support</a> pour:
			{else}
				Visit our <a href="http://www.kerawen.com/support" target="_blank">support page</a> and:
			{/if}
	</p>
		<ul>
			<li>
				{if $lang eq "fr"}
					Vous inscrire à nos prochains webinars "live"
				{else}
					Register for our coming live webinars sessions
				{/if}
			</li>
			<li>
				{if $lang eq "fr"}
					Visionner nos tutoriaux vidéo
				{else}
					Watch our video tutorials
				{/if}
			</li>
		</ul>
		<p>
			{if $lang eq "fr"}
				Pour contacter nos équipes techniques:
			{else}
				To contact our technical team:
			{/if}
		</p>
		<ul>
			<li>
				{if $lang eq "fr"}
					Posez votre question via le <a href="http://www.kerawen.com/contact" target="_blank">formulaire de contact</a>
				{else}
					Ask your question using the <a href="http://www.kerawen.com/contact" target="_blank">contact form</a>
				{/if}
			</li>
			<li>
				{if $lang eq "fr"}
					Envoyez un mail à <a href="mailto:support@kerawen.com">support@kerawen.com</a>
				{else}
					Send a mail to <a href="mailto:support@kerawen.com">support@kerawen.com</a>
				{/if}
			</li>
			<li>
				{if $lang eq "fr"}
					Appelez nos équipes techniques au +33 (0)2 57 52 02 61 ou +33 (0)6 31 12 31 65
				{else}
					Give a call at +33 (0)2 57 52 02 61 or +33 (0)6 31 12 31 65
				{/if}
			</li>
		</ul>
		<p>
			{if $lang eq "fr"}
				Comment souscrire à notre offre&nbsp;?
			{else}
				How to subscribe to our offer&nbsp;?
			{/if}
		</p>
		<ul>
			<li>
				{if $lang eq "fr"}
					Demandez à être rappelé en remplissant le <a href="http://www.kerawen.com/contact" target="_blank">formulaire de contact</a>
				{else}
					Request to be called back using the <a href="http://www.kerawen.com/contact" target="_blank">contact form</a>
				{/if}
			</li>
			<li>
				{if $lang eq "fr"}
					Appelez-nous au +33 (0)2 57 52 02 61 ou +33 (0)6 64 13 24 33
				{else}
					Give a call at +33 (0)2 57 52 02 61 or +33 (0)6 64 13 24 33
				{/if}
			</li>
		</ul>
	</section>
</div>

<script type="text/javascript">
$("#kerawen form").submit(function(e) {
	var form = $(this);
	$.ajax({ 
		url: form.attr("action"),
		type: form.attr("method"),
		data: form.serialize(),
		success: function(res) {
			window.location = "{$register}&key=" + res;
		},
	});
	return false;
});
$("#kerawen .start").click(function(e) {
	window.location = "{$register}";
});
</script>
