(function ($) {
    //Change arrow down to arrow up on facet title collapsed
    Drupal.behaviors.facetArrow = {
        attach: function (context, settings) {
            $('.collapse').on('shown.bs.collapse', function(){
                $(this).parent().find(".glyphicon-menu-down").removeClass("glyphicon-menu-down").addClass("glyphicon-menu-up");
            }).on('hidden.bs.collapse', function(){
                $(this).parent().find(".glyphicon-menu-up").removeClass("glyphicon-menu-up").addClass("glyphicon-menu-down");
            });
        }
    };
    
    //Submit form by clicking icon
    Drupal.behaviors.searchSubmit = {
        attach: function (context, settings) {
            var form = document.getElementById("views-exposed-form-serie-teaser-search-serie");
            document.getElementById("facet-search-submit").addEventListener("click", function () {
                form.submit();
            });
        }
    };
    
    //IMDB Rating
//    Drupal.behaviors.IMDBrating = {
//        attach: function (d,s,id) {
//            var js,stags=d.getElementsByTagName(s)[0];
//            if(d.getElementById(id)){
//                return;
//            }
//            js=d.createElement(s);
//            js.id=id;
//            js.src="http://g-ec2.images-amazon.com/images/G/01/imdb/plugins/rating/js/rating.min.js";
//            stags.parentNode.insertBefore(js,stags);
//        }
//    };
//    (document,"script","imdb-rating-api");
})(jQuery);






