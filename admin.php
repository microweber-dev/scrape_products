<?php only_admin_access(); ?>

<?php require __DIR__ . '/admin_ui.php'; ?>

<script type="text/javascript">mw.require('options.js');</script>
<script type="text/javascript">
function reload_after_save(dontNotify) {
  mw.reload_module("#<?php echo $params['id']; ?>");
  mw.reload_module_parent("#<?php echo $params['id']; ?>");
  if(!dontNotify) mw.notification.success("<?php _e('Settings are saved!'); ?>");
}
function isValidURL(str) {
  var a  = document.createElement('a');
  a.href = str;
  return (a.host && a.host != window.location.host);
}

$(document).ready(function() {
  mw.options.form('#<?php echo $params['id']; ?>', function() {
   mw.notification.success("<?php _e("Settings are saved!"); ?>");
  });

  $('#category-selector li a').click(function() {
    $('#category-selector li a').removeClass('active');
    $(this).addClass('active');
    $('#new-category-name').val('');
  });

  $('#new-uri').on('change keyup blur', function() {
    var uri = $(this).val();
    if(!isValidURL(uri)) return;
    $.get('<?php echo api_url() ?>scrape_products_source_title?uri=' + encodeURIComponent(uri), function(data) {
      if(!data) return;
      if($('#new-name').val().length) return;
      $('#new-name').val(data.trim());
    });
  });

  $('#new-category-name').on('change keyup blur', function() {
    $('#category-selector a.active').removeClass('active'); 
  });

  $('#add-source').click(function() {
    var catId = $('#category-selector').find('.active');
    if(catId.length) catId = catId.attr('id').substr(catId.attr('id').indexOf('-') + 1);
    catId = parseInt(catId);
    var newCat = $('#new-category-name').val();
    var data = {
      'name': $('#new-name').val(),
      'uri': $('#new-uri').val(),
      'category_id': catId,
      'new_category': newCat
    };
    var errors = new Array();
    if(!data.category_id && !data.new_category) errors.push('Select a category');
    if(!data.name) errors.push('Source name is required');
    if(!data.uri) errors.push('Source URL is required');
    if(errors.length) {
      mw.notification.error(errors.join('. '));
      return;
    }
    var me = $(this);
    var domsel = $('#modal-domselector');
    me.addClass('disabled');

    domsel.find('.loading').show();
    domsel.find('.loaded').hide();
    domsel.fadeIn();

    domsel.find('.cancel').one('click', function() {
      domsel.fadeOut();
      me.removeClass('disabled');
    });

    $.get('<?php echo api_url() ?>scrape_products_proxy_page?url=' + encodeURIComponent(data.uri), function(response) {
      var src = 'data:text/html;charset=utf-8,' + escape(response);
      var iframe = domsel.find('iframe');
      iframe.attr('src', src);
      iframe.one('load', function() {
        domsel.find('.loading').hide();
        domsel.find('.loaded').show();
      });
      domsel.find('.ok').one('click', function() {
        domsel.fadeOut();
        add_source(data);
        me.removeClass('disabled');
      });
    });
  });

  function add_source(data) {
    $.post('<?php echo api_url() ?>scrape_products_source_add', data, function(response) {
      if(response.error) {
        mw.notification.error(response.error);
        return;
      }
      mw.notification.success("<?php _e('Source has been added'); ?>");
      reload_after_save(true);
    });
  }

  $('.remove-source').click(function() {
    if(!confirm("<?php _e('Do you really want to delete this source?'); ?>")) return;
    var sid = $(this).data('source');
    $.get('<?php echo api_url() ?>scrape_products_source_remove?id=' + sid, function(data) {
      mw.notification.success("<?php _e('Source has been deleted'); ?>");
      reload_after_save(true);
    });
  });

  $('.refresh-source').click(function(event) {
    event.stopPropagation();
    event.preventDefault();
    $(this).addClass('disabled');
    mw.notification.success("<?php _e('Refreshing source products...'); ?>");
    var sid = $(this).data('source');
    $.get('<?php echo api_url() ?>scrape_products_source_refresh?id=' + sid, function(data) {
      mw.notification.success("<?php _e('Source has been refreshed'); ?>. " + data.length + " <?php _e('products found'); ?>");
      reload_after_save(true);
    });
    return false;
  });

  $('.products-box > .mw-ui-box-header').click(function() {
    mw.accordion('#' + $(this).parent().attr('id'));
  });

  $('[data-sources-page]').click(function() {
    mw.notification.success("<?php _e('Loading page...'); ?>");
    var page = $(this).data('sources-page');
    $.get('<?php echo api_url() ?>scrape_products_sources_set_page?page=' + page, function() {
      reload_after_save(true);
    });
  });

  $('#sources-filter .sources-filter').click(filter_sources);

  $('#sources-filter .sources-clear').click(function() {
    $('#sources-filter input[type=text]').val('');
    filter_sources();
  });

  function filter_sources() {
    mw.notification.success("<?php _e('Filtering results...'); ?>");
    var q = $('#sources-filter input[type=text]').val();
    $.get('<?php echo api_url() ?>scrape_products_sources_set_filter?q=' + q, function() {
      reload_after_save(true);
    });
  }
});
</script>
