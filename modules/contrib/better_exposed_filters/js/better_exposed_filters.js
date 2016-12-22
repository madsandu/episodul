/**
 * @file better_exposed_filters.js
 *
 * Provides some client-side functionality for the Better Exposed Filters module
 */
(function ($) {
  /*
   * Helper functions
   */

  /**
   * Adds/Removes the highlight class from the form-item div as appropriate
   */
  function _bef_highlight(elem, context) {
    $elem = $(elem, context);
    $elem.attr('checked')
      ? $elem.closest('.form-item', context).addClass('highlight')
      : $elem.closest('.form-item', context).removeClass('highlight');
  }


}) (jQuery);
