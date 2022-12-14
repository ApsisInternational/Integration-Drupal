/**
 * @file
 * Sets up the summary for APSIS One on vertical tabs of block forms.
 */

(function ($, Drupal) {

  function checkboxesSummary(context) {
    const conditionChecked = $(context).find(
      '[data-drupal-selector="edit-visibility-apsisone-segments-segments"] option:checked')
      .length;
    const negateChecked = $(context).find(
      '[data-drupal-selector="edit-visibility-apsisone-segments-negate"]:checked')
      .length;

    if (conditionChecked) {
      if (negateChecked) {
        return Drupal.t('Hidden for some segments');
      }
      return Drupal.t('Visible for some segments');
    }
    return Drupal.t('Not restricted');
  }

  /**
   * Provide the summary information for the block settings vertical tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block settings summaries.
   */
  Drupal.behaviors.blockSettingsSummaryApsisone = {
    attach: function () {
      if ($.fn.drupalSetSummary !== undefined) {
        // Set the summary on the vertical tab.
        $('[data-drupal-selector="edit-visibility-apsisone-segments"]')
          .drupalSetSummary(checkboxesSummary);
      }
    }
  };

}(jQuery, Drupal));
