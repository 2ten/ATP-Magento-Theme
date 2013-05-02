// Attach some actions to Product.Config prototype to get the list
// of current available products based on attribute selection.
// Notify subscribers
if (Product && Product.Config) { (function(Config) {

var filterAllowedProducts = function() {
  var self = this, attributes, productsCache = {};

  function findProducts(attr, selection) {
    var cacheKey = attr + ':' + selection, cache = productsCache[cacheKey];
    if (cache) return cache;

    var products = [], options = (function() {
      var o;
      attributes.each(function(item) {
        if (item.value.id.toString().toLowerCase() == attr.toString().toLowerCase()) {
          o = item.value.options;
        }
      });
      return o;
    })();

    options.each(function(opt) {
      if (opt.id.toString().toLowerCase() == selection.toString().toLowerCase())
        products = opt.products.clone();
    });
    productsCache[cacheKey] = products;
    return products;
  }

  return function() {
    attributes = attributes || $H(self.config.attributes);
    var state = new Hash(), allowed = [];
    for (var o in self.state) {
      if (/[0-9]+/.test(o) && self.state.hasOwnProperty(o) && self.state[o])
        state.set(o.toString(), self.state[o]);
    }
    state.each(function(selection) {
      var products = findProducts(selection.key, selection.value),
        enabled = allowed.length ? allowed.intersect(products) : products;
      allowed = enabled;
    });

    return allowed;
  }
};


var init = Config.prototype.initialize;
Config.prototype.initialize = function(config) {
  init.call(this, config);
  this.getAllowedProducts = filterAllowedProducts.call(this);
}

var configElm = Config.prototype.configureElement;
Config.prototype.configureElement = function(element) {
  var self = this;
  configElm.call(this, element);
  this.settings.each(function(elm) {
    if (!elm.value) {
      self.state[elm.config.id] = false;
    }
  });
  setTimeout(this.notifyAllowedProducts.bind(this), 0);
}

Config.prototype.updateState = function() {
}

Config.prototype.notifyAllowedProducts = function() {
  var allowed = this.getAllowedProducts();
  Event.fire(document, 'products:allowed', allowed);
}

}) (Product.Config); }

