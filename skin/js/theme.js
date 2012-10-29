/**
 * Scripts for acrossthepuddle.com
 * by maurousuga.me
 */

var atp = atp || { 'options': {}, 'behaviors': {} };

(function($, ns, options) {

  var options = $.extend({
  }, options, {});

  ns.attachBehaviors = function (context, options) {
    context = context || document;
    options = options || ns.options;
    $.each(ns.behaviors, function () {
      if ($.isFunction(this.attach)) {
	this.attach(context, options);
      }
    });
  };

  ns.detachBehaviors = function (context, options, trigger) {
    context = context || document;
    options = options || ns.options;
    trigger = trigger || 'unload';
    $.each(ns.behaviors, function () {
      if ($.isFunction(this.detach)) {
	this.detach(context, options, trigger);
      }
    });
  };

  // start behaviors
  $(function () {
    ns.attachBehaviors(document, ns.options);
  });

  // Class indicating that JS is enabled; used for styling purpose.
  $(function() {
    $('body').removeClass('no-js').addClass('js');
  });

  // 'js enabled' cookie.
  document.cookie = 'has_js=1; path=/';

  ns.freezeHeight = function () {
    ns.unfreezeHeight();
    $('<div id="freeze-height"></div>').css({
      position: 'absolute',
      top: '0px',
      left: '0px',
      width: '1px',
      height: $('body').css('height')
    }).appendTo('body');
  };

  ns.unfreezeHeight = function () {
    $('#freeze-height').remove();
  };

}) (jQuery, atp, window.theme_settings || {});

jQuery(document).ready(function(){
  jQuery('.block-layered-nav .currently ol li:last').addClass('last')
})
