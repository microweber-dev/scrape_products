<?php only_admin_access(); ?>

<?php require __DIR__ . '/admin_ui.php'; ?>

<script type="text/javascript">mw.require('options.js');</script>
<script type="text/javascript">
function reload_after_save(dontNotify) {
  mw.reload_module("#<?php echo $params['id']; ?>");
  mw.reload_module_parent("#<?php echo $params['id']; ?>");
  if(!dontNotify) mw.notification.success("<?php _e('Success'); ?>");
}

function dateString(S) {
  var H = Math.floor(S / 3600);
  S -= H * 3600;
  var M = Math.floor(S / 60);
  S = Math.floor(S - (M * 60));
  return (H<10 ? '0'+H : H)+':'+(M<10 ? '0'+M : M)+':'+(S<10 ? '0'+S : S);
}

var logFile = '', totalProducts = 0;

function checkLog(cb) {
  if(!logFile) return;
  $.get('<?php echo site_url(); ?>userfiles/modules/import_products/cache/'+logFile, function(data) {
    data = data.split('/');
    console.log(data)
    if(!data.length) return;
    logData = data;
    var p = (data[0] / (data[1]-1)) * 100;
    importProgress = p;
    totalProducts = data[1] - 1;
    p = p.toFixed(2);
    $('#progress .mw-ui-progress-bar').css('width', p+'%');
    $('#progress .mw-ui-progress-percent').text(p+'%');
    var d = new Date() - importStart;
    var etaText = '';
    if(d) etaText += dateString(d * 0.001);
    if(data.length > 3) etaText += (etaText.length?'':'?') +' / '+ dateString(data[3]);
    $('#eta').text(etaText);
    if(cb) cb(data);
  });
  $.get('<?php echo api_url(); ?>import_count_products', function(data) {
    $('#product-count').text(+data + (totalProducts > 0 ? ' / '+totalProducts : ''));
  });
}

function toggleResume(show) {
  $('#container-resume').toggle(show);
  if(show) toggleImport(false);
}

function toggleImport(show) {
  $('#btn-import').toggle(show);
  if(show) toggleResume(false);
}

function toggleSelected(show) {
  $('.on-selected').toggle(show);
  if(!show) {
    selectPlaceholder = getSelectedSource();
  }
}

function getStartUrl() {
  return '<?php echo api_url() ?>import_start?source=' + getSelectedSource();
}

function getSelectedSource() {
  return $('#import-source .mw-dropdown-value').text().trim().toLowerCase();
}

var importStart, importProgress, logData, selectPlaceholder;

$(document).ready(function() {
  $('#import-source').change(function() {
    var val = getSelectedSource();
    if(val == selectPlaceholder) return;
    toggleSelected(true);
    logFile = val + '.txt';
    checkLog(function(data) {
      console.log(data)
      toggleResume(importProgress > 0 && importProgress < 100);
      if(importProgress >= 100) {
        $.ajax('<?php echo api_url() ?>import_halt', function() {
          checkLog();
        });
      }
    });
  });
  $('#btn-halt').click(function() {
    clearInterval(interval);
    $.ajax({
      url: '<?php echo api_url() ?>import_halt',
      complete: function(xhr, data) {
        console.log(data);
        toggleImport(true);
      }
    });
  });
  $('#btn-import, #btn-resume').click(function() {
    $(this).attr('disabled', true);
    importStart = new Date();
  });
  $('#btn-resume').click(function() {
    $(this).attr('disabled', false);
    if(!logData) return;
    var btn = $(this);
    if(0 == logData[0]) {
      $.ajax({
        url: getStartUrl() + '&resume=' + logData[0],
        complete: function(xhr, data) {
          console.log(data);
        }
      });
    }
    console.log(logData)
  });
  $('#btn-import').click(function() {
    var btn = $(this);
    $.ajax({
      url: getStartUrl(),
      success: function(data) {
        btn.attr('disabled', false);
        data = $.parseJSON(data);
        if(!data) return;
        mw.notification.success(data.saved, data.total);
        clearInterval(interval);
      },
      error: function() {
        btn.attr('disabled', false);
        mw.notification.error("<?php _e('Error'); ?>");
      }
    });
  });


  var interval = setInterval(checkLog, 4200);
  toggleSelected(false);
  toggleResume(false);
  $('#import-source').change();
});
</script>
