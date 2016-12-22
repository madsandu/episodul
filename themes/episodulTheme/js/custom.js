// Autosubmit 'autosubmit' views exposed form.
jQuery("div.autosubmit").find("form.views-exposed-form").find("select").bind("change", function () {
  jQuery(this).closest("form").trigger("submit");
}).end().find("input[type='submit']").addClass("visually-hidden");