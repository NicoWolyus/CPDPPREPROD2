/**
 * jQuery File Tree Plugin
 *
 * Version 1.01
 *
 * Cory S.N. LaViska
 * A Beautiful Site (http://abeautifulsite.net/)
 * 24 March 2008
 *
 * Visit http://abeautifulsite.net/notebook.php?article=58 for more information
 *
 * Usage: $('.fileTreeDemo').fileTree( options, callback )
 *
 * Options:  root           - root folder to display; default = /
 *           script         - location of the serverside AJAX file to use; default = jqueryFileTree.php
 *           folderEvent    - event to trigger expand/collapse; default = click
 *           expandSpeed    - default = 500 (ms); use -1 for no animation
 *           collapseSpeed  - default = 500 (ms); use -1 for no animation
 *           expandEasing   - easing function to use on expand (optional)
 *           collapseEasing - easing function to use on collapse (optional)
 *           multiFolder    - whether or not to limit the browser to one subfolder at a time
 *           loadMessage    - Message to display while initial tree loads (can be HTML)
 *
 * History:
 *
 * 1.01 - updated to work with foreign characters in directory/file names (12 April 2008)
 * 1.00 - released (24 March 2008)
 *
 * TERMS OF USE
 * 
 * This plugin is dual-licensed under the GNU General Public License and the MIT License and
 * is copyright 2008 A Beautiful Site, LLC. 
 */

if(jQuery) (function($){
	
	$.extend($.fn, {
		fileTree: function(o, h) {
			// Defaults
			if( !o ) var o = {};
			if( o.root == undefined ) o.root = '/';
			if( o.script == undefined ) o.script = 'jqueryFileTree.php';
			if( o.folderEvent == undefined ) o.folderEvent = 'click';
			if( o.expandSpeed == undefined ) o.expandSpeed= 500;
			if( o.collapseSpeed == undefined ) o.collapseSpeed= 500;
			if( o.expandEasing == undefined ) o.expandEasing = null;
			if( o.collapseEasing == undefined ) o.collapseEasing = null;
			if( o.multiFolder == undefined ) o.multiFolder = true;
			if( o.loadMessage == undefined ) o.loadMessage = 'Loading...';
			if( o.startFolder == undefined ) o.startFolder = o.root;
			o.element = $(this);
			
			$(this).each( function() {
				
				function showTree(c, t) {
					$(c).find('a:first').addClass('wait');
					$(".jqueryFileTree.start").remove();
					$.post(o.script, { dir: t }, function(data) {
						$(c).find('.start').html('');
						$(c).find('a:first').removeClass('wait');
						$(c).append(data);
						if( 
							(o.root == t)
							|| (startList.length > 0)
						) {
							$(c).find('ul:hidden').show(); 
						} else {
							$(c).find('ul:hidden').slideDown({ duration: o.expandSpeed, easing: o.expandEasing });
						}
						bindTree(c);
						if (startList.length > 0) {
							var folder = startList.shift();
							var $a;
							if ($a = o.element.find('li a[rel="'+ folder +'"]')) {
								o.element.scrollTop($a.offset().top - o.element.offset().top + o.element.scrollTop() - o.element.innerHeight()/2);
								$a.trigger(o.folderEvent);
							} else {
								startList = [];
							}
						}
					});
				}
				
				function bindTree(t) {
					$(t).find('li a').bind(o.folderEvent, function() {
						if( $(this).hasClass('directory') ) {
							if( $(this).hasClass('collapsed') ) {
								// Expand
								if( !o.multiFolder ) {
									if (startList.length > 0) {
										$(this).parent().parent().find('ul').show();
									} else {
										$(this).parent().parent().find('ul').slideUp({ duration: o.collapseSpeed, easing: o.collapseEasing });
									}
									$(this).parent().parent().find('a.directory').removeClass('expanded').addClass('collapsed');
								}
								$(this).parent().find('ul').remove(); // cleanup
								showTree( $(this).parent(), escape($(this).attr('rel').match( /.*\// )) );
								$(this).removeClass('collapsed').addClass('expanded');
							} else {
								// Collapse
								$(this).parent().find('ul').slideUp({ duration: o.collapseSpeed, easing: o.collapseEasing });
								$(this).removeClass('expanded').addClass('collapsed');
							}
						}
						o.element.find('li a').removeClass('selected');
						$(this).addClass('selected');
						h($(this).attr('rel'));
						return false;
					});
					// Prevent A from triggering the # on non-click events
					if( o.folderEvent.toLowerCase != 'click' ) $(t).find('li a').bind('click', function() { return false; });
				}

				// Loading message
				$(this).html('<div class="jqueryFileTree start"><a class="wait">' + o.loadMessage + '<a></div>');
				// Get the initial file list
				var startList = [];
				if (o.startFolder != o.root) {
					var folders = o.startFolder.split('/');
					var currentFolder = '';
					for (var i in folders) {
						if (
							(i == 0)
							|| (folders[i] != '')
						) {
							currentFolder += folders[i] + '/';
							if (currentFolder != o.root)
								startList.push(currentFolder);
						}
					}
				}
				showTree( $(this), escape(o.root) );
			});
		}
	});
	
})(jQuery);