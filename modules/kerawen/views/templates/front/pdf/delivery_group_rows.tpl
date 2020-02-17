{$l="border-left:1px solid #000"}
{$r="border-right:1px solid #000"}
{$t="border-top:1px solid #000"}
{$b="border-bottom:1px solid #000"}
{$tb="{$t};{$b}"}
{$tl="{$t};{$l}"}
{$tr="{$t};{$r}"}
{$bl="{$b};{$l}"}
{$br="{$b};{$r}"}
{$tbl="{$tb};{$l}"}
{$tbr="{$tb};{$r}"}
{$tblr="border:1px solid #000"}

{$gray="background-color:#AAA"}
{$bold="font-weight:bold"}
{$header="{$gray};{$bold}"}
{$center="text-align:center"}
{$right="text-align:right"}
<!--br/><br/><br/><br/-->
<div style="font-size:8pt">
	<div >
		<table style="width:100%" cellpadding="2">
			<tr style="{$bold};{$gray}">
				<th style="width:15%;{$tbl}">
					RÃ©fÃ©rence
				</th>
				<th style="width:30%;{$tb}">
					DÃ©signation
				</th>
				<th style="width:10%;{$right};{$tb}">
					Nombre
				</th>
				<th style="width:15%;{$right};{$tb}">
					QuantitÃ©
				</th>
				<th style="width:15%;{$right};{$tb}">
					P.U. HT
				</th>
				<th style="width:15%;{$right};{$tbr}">
					Montant HT
				</th>
			</tr>
			<tr style="line-height:0px">
				<td style="{$l}"></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td style="{$r}"></td>
			</tr>
			{foreach $kerawen.invoice_details as $detail}
				{cycle values='#FFF,#DDD' assign=bgcolor}
				<tr style="background-color:{$bgcolor}">
					<td style="{$l}">
						{$detail.reference}
					</td>
					<td style="{$bold}">
						{$detail.name}
					</td>
					<td style="{$right}">
						{$detail.quantity}
					</td>
					<td style="{$right}">
						{if $detail.measure}
							{$detail.measure|string_format:"%.{$detail.precision}f"}
							{$detail.unit}
						{else}
							{$detail.quantity}
						{/if}
					</td>
					<td style="{$right}">
						{$detail.unit_price_te|string_format:"%.2f"}
					</td>
					<td style="{$right};{$r}">
						{$detail.total_price_te|string_format:"%.2f"}
					</td>
				</tr>
			{/foreach}
			<tr style="line-height:0px">
				<td style="{$b};{$l}"></td>
				<td style="{$b}"></td>
				<td style="{$b}"></td>
				<td style="{$b}"></td>
				<td style="{$b}"></td>
				<td style="{$b};{$r}"></td>
			</tr>
		</table>
	</div>
	<br/>
	<div>
		<table style="width:100%">
			<td style="width:60%">
				<div>
					<table style="width:100%;{$center}">
						<td style="width:40%">
							<table style="width:90%" cellpadding="5">
								<tr>
									<th style="{$tblr};{$header}">
										Termenn / EchÃ©ance
									</th>
								</tr>
								<tr>
									<td style="{$tblr}">
										{$kerawen.payment_date}
									</td>
								</tr>
							</table>
						</td>
						<td style="width:60%">
							<table style="width:90%" cellpadding="5">
								<tr>
									<th style="{$tblr};{$header}">
										Doare PaeaÃ± / Mode de paiement
									</th>
								</tr>
								<tr>
									<td style="{$tblr}">
										ChÃ¨que Ã  rÃ©ception
									</td>
								</tr>
							</table>
						</td>
					</table>
				</div>
				<div>
					<table style="width:95%" cellpadding="2">
						<tr style="{$bold};{$gray}">
							<th style="width:20%;{$tbl}">
								Code
							</th>
							<th style="width:26%;{$right};{$tb}">
								Base HT
							</th>
							<th style="width:26%;{$right};{$tb}">
								Taux TVA
							</th>
							<th style="width:28%;{$right};{$tbr}">
								Montant TVA
							</th>
						</tr>
						<tr style="line-height:0px">
							<td style="{$l}"></td>
							<td></td>
							<td></td>
							<td style="{$r}"></td>
						</tr>
						{foreach $kerawen.invoice_taxes as $tax}
							{cycle values='#FFF,#DDD' assign=bgcolor}
							<tr style="background-color:{$bgcolor}">
								<td style="{$l}">
									{$tax.code}
								</td>
								<td style="{$right}">
									{$tax.base|string_format:"%.2f"}
								</td>
								<td style="{$right}">
									{$tax.rate|string_format:"%.2f"}
								</td>
								<td style="{$right};{$r}">
									{$tax.amount|string_format:"%.2f"}
								</td>
							</tr>
						{/foreach}
						<tr style="line-height:0px">
							<td style="{$b};{$l}"></td>
							<td style="{$b}"></td>
							<td style="{$b}"></td>
							<td style="{$b};{$r}"></td>
						</tr>
					</table>
				</div>
			</td>
			<td style="width:40%">
				<table style="width:100%" cellpadding="2">
					<tr>
						<td style="width:70%;{$tl}">
							Total HT
						</td>
						<td style="width:30%;{$tr};{$right}">
						</td>
					</tr>
					<tr style="{$header}">
						<td style="{$l}">
							Net HT
						</td>
						<td style="{$right};{$r}">
							{$kerawen.invoice->total_paid_tax_excl|string_format:"%.2f"}
						</td>
					</tr>
					<tr>
						<td style="{$l}">
							Total TVA
						</td>
						<td style="{$right};{$r}">
							{($kerawen.invoice->total_paid_tax_incl - $kerawen.invoice->total_paid_tax_excl)|string_format:"%.2f"}
						</td>
					</tr>
					<tr>
						<td style="{$l}">
							Total TTC
						</td>
						<td style="{$right};{$r}">
							{$kerawen.invoice->total_paid_tax_incl|string_format:"%.2f"}
						</td>
					</tr>
					<tr style="{$header}">
						<td style="{$bl}">
							NET A PAYER
						</td>
						<td style="{$br};font-size:12pt">
							{$kerawen.invoice->total_paid_tax_incl|string_format:"%.2f"}
						</td>
					</tr>
				</table>
			</td>
		</table>
	</div>
</div>
