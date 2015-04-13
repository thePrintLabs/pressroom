jQuery(function(){
  var $this = jQuery('#_pr_adps_override');
  $this.click(function(e) {
    overrideSettings($this, jQuery('#adps_settings_metabox .form-table'));
  });
  if($this.length) {
    overrideSettings($this, jQuery('#adps_settings_metabox .form-table'));
  }

  function overrideSettings(e, m) {
    if (e.is(':checked')) {
      m.find('input, select, button').removeAttr('disabled');
      m.find('h3, label').css('color','#222');
      m.find('p.description').css('color','#666');
    } else {
      m.find('input, select, button').attr('disabled','disabled');
      m.find('h3, label, p.description').css('color','#ddd');
      e.removeAttr('disabled');
      e.parent().parent().find('label').css('color','#222');
      e.parent().parent().find('p.description').css('color','#666');
    }
  }
});
