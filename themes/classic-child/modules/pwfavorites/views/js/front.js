/**
 * 2007-2019 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

prestashop.pwFavorites = {
  http: {},
  alert: {},
  slider: {}
};

prestashop.pwFavorites.http.toggleProduct = function (id_product, productName, silent) {
  prestashop.emit('pwFavoritesUpdateProduct', { id_product: id_product });

  return $.ajax({
    type: 'PUT',
    url: pwfavorites.urls.ajax + '&id_product=' + id_product
  }).done(function (active) {
    if (!silent && pwfavorites.show_confirmation) {
      prestashop.pwFavorites.alert.show(id_product, productName, active);
    }

    prestashop.emit('pwFavoritesUpdatedProduct', { id_product: id_product, active: active });
  });
};

prestashop.pwFavorites.alert.init = function () {
  prestashop.pwFavorites.alert.fixPosition();

  $(window).scroll(function () {
    prestashop.pwFavorites.alert.fixPosition();
  });
};

prestashop.pwFavorites.alert.fixPosition = function () {
  var $alerts = $('.pw-fav-alerts');
  var scroll = $(window).scrollTop();

  if (scroll < 150) {
    $alerts.css('top', 150 - scroll);
  } else {
    $alerts.css('top', 0);
  }
};

prestashop.pwFavorites.alert.animate = function ($alert) {
  setTimeout(function () {
    $alert.find('.pw-fav-alert-progress-value').addClass('translate');
    setTimeout(function () {
      $alert.fadeOut(function () {
        $alert.remove();
      });
    }, 2750);
  }, 50);
};

prestashop.pwFavorites.alert.render = function (id_product, productName, added) {
  var message = prestashop.pwFavorites.trans(added ? 'favorite_added' : 'favorite_removed')
    .replace('%1$s', productName)
    .replace('%2$s', '<a href="' + pwfavorites.urls.favorites + '">')
    .replace('%3$s', '</a>');

  return $(
    '<div class="pw-fav-alert" id="pw_fav_alert_' + id_product + '">' +
      '<span>' + message + '</span>' +
      '<button class="pw-fav-alert-close">' +
        '<span>&times;</span>' +
      '</button>' +
      '<div class="pw-fav-alert-progress">' +
        '<div class="pw-fav-alert-progress-value"></div>' +
      '</div>' +
    '</div>'
  );
};

prestashop.pwFavorites.alert.show = function (id_product, productName, added) {
  var $alert = prestashop.pwFavorites.alert.render(id_product, productName, added);

  $alert.children('button').click(function () {
    $alert.remove();
  });

  var $existingAlert = $('#pw_fav_alert_' + id_product);

  if ($existingAlert.length) {
    $existingAlert.remove();
    $('.pw-fav-alerts').prepend($alert);
    prestashop.pwFavorites.alert.animate($alert);
  } else {
    $('.pw-fav-alerts').prepend($alert);
    prestashop.pwFavorites.alert.animate($alert);
  }
};

prestashop.pwFavorites.slider.getConfiguration = function ($container) {
  var width = prestashop.responsive.current_width;
  var slideMargin = 15;

  var maxSlides = pwfavorites.slider.max_slides_xs;
  if (width > pwfavorites.slider.width_lg) {
    maxSlides = pwfavorites.slider.max_slides_lg;
  } else if (width > pwfavorites.slider.width_md) {
    maxSlides = pwfavorites.slider.max_slides_md;
  } else if (width > pwfavorites.slider.width_sm) {
    maxSlides = pwfavorites.slider.max_slides_sm;
  }

  return {
    hideControlOnEnd: true,
    infiniteLoop: !!pwfavorites.slider.infinite_loop,
    maxSlides: maxSlides,
    minSlides: 1,
    moveSlides: maxSlides,
    nextText: '<i class="material-icons">&#xE5CC;</i>',
    pager: false,
    prevText: '<i class="material-icons">&#xE5CB;</i>',
    slideWidth: ($container.width() / maxSlides) - slideMargin,
    slideMargin: slideMargin
  };
};

prestashop.pwFavorites.slider.init = function () {
  var $slider = $('.pw-fav-slider');
  if ($slider.length) {
    var $container = $slider.parent();
    var slider = $slider.bxSlider(prestashop.pwFavorites.slider.getConfiguration($container));
    $slider.css('visibility', 'visible');

    $(window).bind('resize', function () {
      slider.reloadSlider(prestashop.pwFavorites.slider.getConfiguration($container));
      $slider.css('visibility', 'visible');
    });
  }
};

prestashop.pwFavorites.setButtonState = function (element, active) {
  var $button = element;
  if (parseInt(element)) {
    $button = $('.pw-fav-toggle[data-id-product="' + element + '"]');
  }

  if (active) {
    $button.addClass('active');
  } else {
    $button.removeClass('active');
  }
};

prestashop.pwFavorites.moveButtons = function () {
  if (!pwfavorites.move_button || !pwfavorites.product_miniature_selector || !pwfavorites.product_thumbnail_selector) {
    return;
  }

  if (prestashop.page.page_name === 'product') {
    return;
  }

  $('.pw-fav-toggle').each(function () {
    var $container = $(this).parents(pwfavorites.product_miniature_selector).find(pwfavorites.product_thumbnail_selector);
    $(this).detach().appendTo($container);
  });
};

prestashop.pwFavorites.handleButtons = function () {
  prestashop.pwFavorites.moveButtons();

  if (!prestashop.customer.is_logged) {
    return;
  }

  $('.pw-fav-toggle').off().click(function (e) {
    e.preventDefault();

    var $button = $(this);
    var id_product = $button.data('id-product');

    prestashop.pwFavorites.http.toggleProduct(id_product, $button.data('product-name')).done(function (active) {
      prestashop.pwFavorites.setButtonState(id_product, active);

      if (!active && prestashop.pwFavorites.isFavoritesPage() && pwfavorites.product_miniature_selector) {
        $button.parents(pwfavorites.product_miniature_selector + '[data-id-product="' + id_product + '"]').remove();
        if (!$(pwfavorites.product_miniature_selector).length) {
          $('#js-product-list-top').remove();
          $('#js-product-list').remove();
          $('#page-content').show();
        }
      }
    });
  });
};

prestashop.pwFavorites.isFavoritesPage = function () {
  return prestashop.page.page_name === 'module-pwfavorites-favorites';
};

prestashop.pwFavorites.trans = function (key) {
  if (typeof pwfavorites.translations !== 'undefined' && key in pwfavorites.translations) {
    return pwfavorites.translations[key];
  }

  return '';
};

prestashop.pwFavorites.init = function () {
  prestashop.pwFavorites.alert.init();
  prestashop.pwFavorites.slider.init();
  prestashop.pwFavorites.handleButtons();
};

prestashop.on('updateProductList', function () {
  prestashop.pwFavorites.handleButtons();
});

$(function () {
  prestashop.pwFavorites.init();
});
