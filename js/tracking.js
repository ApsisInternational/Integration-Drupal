(function (Drupal) {
  Drupal.behaviors.apsisoneTracking = {
    attach: function attach(context, settings) {
      once('apsisoneTrackingCode', 'html', context).forEach(function (element) {
        if (settings.apsisone && settings.apsisone.trackingcode && settings.apsisone.trackingcode != '') {
          eval(settings.apsisone.trackingcode);
        }
      })
    }
  };
})(Drupal);
