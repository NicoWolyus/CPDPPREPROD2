/**
 * 2008 - 2019 (c) Prestablog
 *
 * MODULE PrestaBlog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2

 */

$(document).ready(function(){
	$('ul.prestablogtree.dhtml').hide();

	//to do not execute this script as much as it's called...
	if(!$('ul.prestablogtree.dhtml').hasClass('dynamized'))
	{
		//add growers to each ul.prestablogtree elements
		$('ul.prestablogtree.dhtml ul').prev().before("<span class='grower OPEN'> </span>");

		//dynamically add the '.last' class on each last item of a branch
		$('ul.prestablogtree.dhtml ul li:last-child, ul.prestablogtree.dhtml li:last-child').addClass('last');

		//collapse every expanded branch
		$('ul.prestablogtree.dhtml span.grower.OPEN').addClass('CLOSE').removeClass('OPEN').parent().find('ul:first').hide();
		$('ul.prestablogtree.dhtml').show();

		//open the prestablogtree for the selected branch
			$('ul.prestablogtree.dhtml .selected').parents().each( function() {
				if ($(this).is('ul'))
					toggleBranch($(this).prev().prev(), true);
			});
			toggleBranch( $('ul.prestablogtree.dhtml .selected').prev(), true);

		//add a fonction on clicks on growers
		$('ul.prestablogtree.dhtml span.grower').click(function(){
			toggleBranch($(this));
		});
		//mark this 'ul.prestablogtree' elements as already 'dynamized'
		$('ul.prestablogtree.dhtml').addClass('dynamized');

		$('ul.prestablogtree.dhtml').removeClass('dhtml');
	}
});

//animate the opening of the branch (span.grower jQueryElement)
function openBranch(jQueryElement, noAnimation)
{
	jQueryElement.addClass('OPEN').removeClass('CLOSE');
	if(noAnimation)
		jQueryElement.parent().find('ul:first').show();
	else
		jQueryElement.parent().find('ul:first').slideDown();

	// jQueryElement.parents('ul').find('li').each(function() {
	// 	if($(this).find('span.grower') != jQueryElement)
	// 		$(this).find('a img.catblog_img, a.catblog_desc').fadeOut();
	// });
}
//animate the closing of the branch (span.grower jQueryElement)
function closeBranch(jQueryElement, noAnimation)
{
	jQueryElement.addClass('CLOSE').removeClass('OPEN');
	if(noAnimation)
		jQueryElement.parent().find('ul:first').hide();
	else
		jQueryElement.parent().find('ul:first').slideUp();

	// jQueryElement.parents('ul').find('li').each(function() {
	// 	if($(this).find('span.grower') != jQueryElement)
	// 		$(this).find('a img.catblog_img, a.catblog_desc').fadeIn();
	// });
}

//animate the closing or opening of the branch (ul jQueryElement)
function toggleBranch(jQueryElement, noAnimation)
{
	if(jQueryElement.hasClass('OPEN'))
		closeBranch(jQueryElement, noAnimation);
	else
		openBranch(jQueryElement, noAnimation);
}
