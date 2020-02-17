{*
 * 2016 KerAwen
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

<div class="bootstrap">

<div class="panel col-lg-12">
  
  <div class="panel-heading">{l s='Employees' mod='kerawen'}</div>
  
  <div class="table-responsive-row clearfix">
		<table class="table employee">
			<thead>
				<th class="nodrag nodrop"><span class="title_box active">ID</span></th>
				<th class=""><span class="title_box">Prénom</span></th>
				<th class=""><span class="title_box">Nom</span></th>
				<th class=""><span class="title_box">Profil</span></th>
				<th class=""></th>
			</thead>

			<tbody>
			
				{foreach $employees as $employee}
				<tr class="{cycle values="odd,even"}">
					<td class="row-selector">{$employee.id_employee}</td>			
					<td>{$employee.firstname}</td>
					<td>{$employee.lastname}</td>
					<td>{$employee.profile}</td>
					<td class="btn-group pull-right">
						<a href="{$employee.link}" title="Modifier" class="edit btn btn-default">
						<i class="icon-pencil"></i> Modifier</a>
					</td>
				</tr>
				{/foreach}
				
			</tbody>
		</table>
	</div>  
  
</div>
 
 
</div> 
 
employees.tpl





	<pre>
	{$employees|@print_r} 
	</pre>
