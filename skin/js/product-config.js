(function() {


function insert_thumbs(list, thumbs) {
  thumbs.each(function(item) {
    var li = new Element('li', {class: 'magic', 'data-product-id': item.readAttribute('data-product-id')});
    var anchor = new Element('a', {rel: 'product-zoom', title: item.readAttribute('data-title'), href: item.readAttribute('data-zoom'), rev: item.readAttribute('data-src')});
    var thumb = new Element('img', {src: item.readAttribute('data-thumb'), width: 56, height: 77});
    anchor.insert(thumb);
    li.insert(anchor);
    list.insert(li);
  });
}

var zoom, list, last_ids;
document.observe('products:allowed', function(event, allowed) {
  if (!event.memo || !event.memo.length)  return;

  var ids = event.memo.sort(), memo_ids = ids.join(','),
    proccess = !(last_ids && (last_ids == memo_ids || last_ids.indexOf(memo_ids) > -1));

  var metas = [];
  if (proccess) {
    last_ids = memo_ids;
    ids.each(function(id) {
      var query = $$('meta[data-product-id='+ id + ']');
      query.length && metas.push(query);
    });

    list = list || $$('.more-views > ul')[0];
    var previous = list.childElements();
    previous.each(function(el) {
     el.hasClassName('magic') && el.remove();
    });

    if (metas) {
      metas = metas.flatten();
      insert_thumbs(list, metas);
    }
  }

  setTimeout(function() {
    metas && MagicTouch.refresh();
    var meta = ids[0], el = $$('.more-views li[data-product-id="' + meta + '"] > a');
    if (el) {
     zoom = zoom || document.getElementById('product-zoom');
      MagicTouch.update(zoom, el[0].href, el[0].rev);
    }
   }, 0);
});

}) ();
