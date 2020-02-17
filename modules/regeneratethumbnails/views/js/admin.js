/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://doc.prestashop.com/display/PS15/Overriding+default+behaviors
 * #Overridingdefaultbehaviors-Overridingamodule%27sbehavior for more information.
 *
 * @category Prestashop
 * @category Module
 * @author Samdha <contact@samdha.net>
 * @copyright Samdha
 * @license commercial license see license.txt
 */

;samdha_module.postInit = function () {
	"use strict";

	var $ = samdha_module.$;
	var config = samdha_module.config;
	var messages = samdha_module.messages;

	samdha_module.processLoop = function()
	{
		$('#tabRegenerate tr.image_format').each(function() {
			if ($(this).data('status') == 'waiting')
			{
				$('.format_continue', this).addClass('disable');
				$('.format_start', this).addClass('disable');
				if (($(this).data('current') > 0) && ($(this).data('current') < $(this).data('number')))
					$('.format_stop', this).removeClass('disable');
				else
					$('.format_stop', this).addClass('disable');
				$('.format_pause', this).removeClass('disable');
				$(this).trigger('regenerate', [0]);
			}
			else
			{
				if ($(this).data('status') == 'working')
					$('.format_pause', this).removeClass('disable');
				else
					$('.format_pause', this).addClass('disable');
				if (($(this).data('current') == 0) || ($(this).data('current') == $(this).data('number')))
				{
					$('.format_start', this).removeClass('disable');
					$('.format_stop', this).addClass('disable');
					$('.format_continue', this).addClass('disable');
				}
				else
				{
					$('.format_start', this).addClass('disable');
					$('.format_stop', this).removeClass('disable');
					if ($(this).data('status') == 'pause')
						$('.format_continue', this).removeClass('disable');
					else
						$('.format_continue', this).addClass('disable');
				}
			}

			if ($(this).data('status') == 'restart')
				$(this).trigger('regenerate', [1]);
		});

		$('#tabRegenerate .image_type').each(function() {
			var type = $(this).data('type');
			if ($('#tabRegenerate .image_type_' + type + ' .format_start:not(.disable)').length > 0)
				$('.type_start', this).removeClass('disable');
			else
				$('.type_start', this).addClass('disable');
			if ($('#tabRegenerate .image_type_' + type + ' .format_stop:not(.disable)').length > 0)
				$('.type_stop', this).removeClass('disable');
			else
				$('.type_stop', this).addClass('disable');
			if ($('#tabRegenerate .image_type_' + type + ' .format_continue:not(.disable)').length > 0)
				$('.type_continue', this).removeClass('disable');
			else
				$('.type_continue', this).addClass('disable');
			if ($('#tabRegenerate .image_type_' + type + ' .format_pause:not(.disable)').length > 0)
				$('.type_pause', this).removeClass('disable');
			else
				$('.type_pause', this).addClass('disable');

			var number = 0;
			var current = 0;
			var number_actif = 0;
			$('#tabRegenerate .image_type_' + type).each(function() {
				number = number + $(this).data('number');
				if (($(this).data('current') > 0) || ($(this).data('status') != 'pause'))
				{
					number_actif = number_actif + $(this).data('number');
					current = current + $(this).data('current');
				}
			});
			if (number_actif > 0)
			{
				$('.progress', this).progressbar('value', 100*current/number_actif);
				if (current < number_actif)
					$('.text', this).text(messages.image+' ' + current + ' ' + messages.of + ' '+ number_actif);
				else
					$('.text', this).text(messages.done+' (' + number_actif + ')');
			}
			else
			{
				$('.progress', this).progressbar('value', 0);
				$('.text', this).text(messages.image+' 0 '+messages.of+' '+ number);
			}
		});

		$('#tabRegenerate .image_global').each(function() {
			if ($('#tabRegenerate .type_start:not(.disable)').length > 0)
				$('.global_start', this).removeClass('disable');
			else
				$('.global_start', this).addClass('disable');
			if ($('#tabRegenerate .type_stop:not(.disable)').length > 0)
				$('.global_stop', this).removeClass('disable');
			else
				$('.global_stop', this).addClass('disable');
			if ($('#tabRegenerate .type_continue:not(.disable)').length > 0)
				$('.global_continue', this).removeClass('disable');
			else
				$('.global_continue', this).addClass('disable');
			if ($('#tabRegenerate .type_pause:not(.disable)').length > 0)
				$('.global_pause', this).removeClass('disable');
			else
				$('.global_pause', this).addClass('disable');

			var number = 0;
			var current = 0;
			var number_actif = 0;
			$('#tabRegenerate .image_format').each(function() {
				number = number + $(this).data('number');
				if (($(this).data('current') > 0) || ($(this).data('status') != 'pause'))
				{
					number_actif = number_actif + $(this).data('number');
					current = current + $(this).data('current');
				}
			});
			if (number_actif > 0)
			{
				$('.progress', this).progressbar('value', 100*current/number_actif);
				if (current < number_actif)
					$('.text', this).text(messages.image+' ' + current + ' ' + messages.of + ' '+ number_actif);
				else
					$('.text', this).text(messages.done+' (' + number_actif + ')');
			}
			else
			{
				$('.progress', this).progressbar('value', 0);
				$('.text', this).text(messages.image+' 0 '+messages.of+' '+ number);
			}
		});
	};

	samdha_module.regenerateFinish = function(event)
	{
		$(this).data('status', 'pause');
		$('.text', this).text(messages.done+' (' + $(this).data('number') + ')');
	};

	samdha_module.regenerateStart = function(event, restart)
	{
		$(this).data('status', 'working');
		if (restart)
		{
			$(this).data('current', 0);
			$('.progress', this).progressbar('value', 0);
			$('.text', this).text(messages.image+' 0 '+messages.of+' '+$(this).data('number'));
		}

		$.ajaxQueue(
			{
				type: 'GET',
				url: config.module_url,
				restart: restart,
				data: {
					'ajax': 1,
					'action': 'regenerate',
					'type': $(this).data('type'),
					'format': $(this).data('format'),
					'restart': restart
				},
				context: this,
				beforeSend: function(jqXHR, settings) {
					jqXHR.restart = settings.restart;
				},
				error: samdha_module.regenerateError,
				success: samdha_module.regenerateSuccess,
				dataType: 'json',
				cache: false
			},
			Math.floor(Math.random()*config.process_count))
			.done(samdha_module.regenerateDone)
			.fail(samdha_module.regenerateFail);
	};

	samdha_module.regenerateDone = function(data, textStatus, jqXHR)
	{
		$(this).data({'current': data.current, 'number': data.number});

		if (!data.error) {
			$('.text', this).text(messages.image+' '+data.current+' '+messages.of+' '+data.number);
		} else {
			$(this).next().show();
			$('div', $(this).next()).append(data.error+"<br/>");
		}
		$('.progress', this).progressbar('value', 100*data.current/data.number);

		if (data.finish)
			$(this).trigger('finish');
		else if (jqXHR.restart)
			$(this).data('status', 'pause');
		else if ($(this).data('status') == 'working')
			$(this).data('status', 'waiting');
	};

	samdha_module.regenerateFail = function(jqXHR, textStatus, errorThrown)
	{
		$(this).data('status', 'pause');

		var text = messages.image+' '+$(this).data('current')+' : ';
		if (jqXHR.status === 0) {
			text = text + '<br/>Not connect. Will Retry in 5 seconds.';
			window.setTimeout(function(element) {$(element).data('status', 'waiting');}, 5000, this);
		} else if (jqXHR.status == 404) {
			text = text + '<br/>Requested page not found. [404]';
		} else if (jqXHR.status == 500) {
			text = text + '<br/>Internal Server Error [500]. Will Retry in 5 seconds.';
			window.setTimeout(function(element) {$(element).data('status', 'waiting');}, 5000, this);
		} else if (textStatus === 'parsererror') {
			text = text + '<br/>Requested JSON parse failed.';
		} else if (textStatus === 'timeout') {
			text = text + '<br/>Time out error. Will Retry in 5 seconds.';
			window.setTimeout(function(element) {$(element).data('status', 'waiting');}, 5000, this);
		} else if (textStatus === 'abort') {
			text = text + '<br/>Ajax request aborted.';
		} else {
			text = text + '<br/>Uncaught Error.';
		}
		if (jqXHR.responseText)
			text = text + '<br/>' + jqXHR.responseText;
		$(this).next().show();
		$('div', $(this).next()).append(text+'<br/>');
	};

	samdha_module.formatContinue = function(event)
	{
		if (!$(this).hasClass('disable'))
			$(this).parent().parent().data('status', 'waiting');
	};

	samdha_module.formatPause = function(event)
	{
		if (!$(this).hasClass('disable'))
			$(this).parent().parent().data('status', 'pause');
	};

	samdha_module.formatStop = function(event)
	{
		if (!$(this).hasClass('disable'))
			$(this).parent().parent().data('status', 'restart');
	};

	samdha_module.typeContinue = function(event)
	{
		$('#tabRegenerate .image_type_'+ $(this).parent().parent().data('type')+' .format_continue:not(.disable)').trigger('click');
	};

	samdha_module.typePause = function(event)
	{
		$('#tabRegenerate .image_type_'+ $(this).parent().parent().data('type')+' .format_pause:not(.disable)').trigger('click');
	};

	samdha_module.typeStop = function(event)
	{
		$('#tabRegenerate .image_type_'+ $(this).parent().parent().data('type')+' .format_stop:not(.disable)').trigger('click');
	};

	samdha_module.typeStart = function(event)
	{
		$('#tabRegenerate .image_type_'+ $(this).parent().parent().data('type')+' .format_start:not(.disable)').trigger('click');
		$('#tabRegenerate .image_type_'+ $(this).parent().parent().data('type')+' .format_continue:not(.disable)').trigger('click');
	};

	samdha_module.globalContinue = function(event)
	{
		$('#tabRegenerate .format_continue:not(.disable)').trigger('click');
	};

	samdha_module.globalPause = function(event)
	{
		$('#tabRegenerate .format_pause:not(.disable)').trigger('click');
	};

	samdha_module.globalStop = function(event)
	{
		$('#tabRegenerate .format_stop:not(.disable)').trigger('click');
	};

	samdha_module.globalStart = function(event)
	{
		$('#tabRegenerate .format_start:not(.disable)').trigger('click');
		$('#tabRegenerate .format_continue:not(.disable)').trigger('click');
	};

	samdha_module.globalExpand = function(event)
	{
		if (!$(this).hasClass('not_expanded'))
			$('#tabRegenerate .type_expand:not(.not_expanded)').trigger('click');

		$(this).toggleClass('not_expanded');
		$('#tabRegenerate .image_type').toggle();
	};

	samdha_module.typeExpand = function(event)
	{
		if ($(this).hasClass('not_expanded'))
			$('#tabRegenerate .image_type_'+ $(this).parent().data('type')).show();
		else
			$('#tabRegenerate .image_type_'+ $(this).parent().data('type')).hide();
		$(this).toggleClass('not_expanded');
	};

	$('#tabRegenerate .global_continue').on('click', samdha_module.globalContinue);
	$('#tabRegenerate .global_pause').on('click', samdha_module.globalPause);
	$('#tabRegenerate .global_stop').on('click', samdha_module.globalStop);
	$('#tabRegenerate .global_start').on('click', samdha_module.globalStart);
	$('#tabRegenerate .type_continue').on('click', samdha_module.typeContinue);
	$('#tabRegenerate .type_pause').on('click', samdha_module.typePause);
	$('#tabRegenerate .type_stop').on('click', samdha_module.typeStop);
	$('#tabRegenerate .type_start').on('click', samdha_module.typeStart);
	$('#tabRegenerate .format_continue').on('click', samdha_module.formatContinue);
	$('#tabRegenerate .format_pause').on('click', samdha_module.formatPause);
	$('#tabRegenerate .format_stop').on('click', samdha_module.formatStop);
	$('#tabRegenerate .format_start').on('click', samdha_module.formatContinue);
	$('#tabRegenerate .global_expand').on('click', samdha_module.globalExpand);
	$('#tabRegenerate .type_expand').on('click', samdha_module.typeExpand);
	$('#tabRegenerate .image_format')
		.on('regenerate', samdha_module.regenerateStart)
		.on('finish', samdha_module.regenerateFinish);
	$('#tabRegenerate .image_global, #tabRegenerate .image_type, #tabRegenerate .image_format')
		.each(function() {
			var number = $(this).data('number');
			var current = $(this).data('current');
			if (number == 0)
				$('.progress', this).progressbar({'value':0});
			else
			{
				$('.progress', this).progressbar({'value':100*current/number});
				$('.text', this).text(messages.image+' '+current+' '+messages.of+' '+number);
			}
		})
	window.setInterval(function () {samdha_module.processLoop()}, 1000);

	// parameters
	// working directory
	$('#' + config.short_name + 'directory').prop('readonly', 'readonly');
	$("#jqueryFileTree_div").fileTree({
		root: "",
		startFolder: $('#' + config.short_name + 'directory').val(),
		script: config.module_url + "&ajax=1&action=getFileTree&dontsave=0"
	}, function(file) {
        $('#' + config.short_name + 'directory').val(file);
    });

};
