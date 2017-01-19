// Autosubmit 'autosubmit' views exposed form.
//jQuery("div.autosubmit").find("form.views-exposed-form").find("select").bind("change", function () {
//  jQuery(this).closest("form").trigger("submit");
//}).end().find("input[type='submit']").addClass("visually-hidden");


//Submit form by clicking link
var form = document.getElementById("views-exposed-form-serie-teaser-search-serie");

document.getElementById("facet-search-submit").addEventListener("click", function () {
    form.submit();
});