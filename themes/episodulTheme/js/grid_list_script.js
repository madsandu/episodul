(function ($) {

    Drupal.behaviors.gridListView = {
        attach: function (context, settings) {
            
            //remember product list/grid choice using localStorage
            $('.list-grid .list:not(.a-processed)', context).each(function () {
                $(this).addClass('a-processed').on('click', function (e) {
                    $(this).closest('.serie-wrapper').addClass('list-view');
                    remove_grid_classes()
                    //you need to pass string values, your variables display & block was not defined
                    try {
                        localStorage.setItem('displayType', 'list');
                    } catch (error) {
                        return false;
                    }
                });
            });
            
            $('.list-grid .grid:not(.a-processed)', context).each(function () {
                $(this).addClass('a-processed').on('click', function (e) {
                    $(this).closest('.serie-wrapper').removeClass('list-view');
                    add_grid_classes();
                    //you need to pass string values, your variables display & block was not defined
                    try {
                        localStorage.setItem('displayType', '');
                    } catch (error) {
                        return false;
                    }
                });
            });
        }
    };
    
    Drupal.behaviors.ForceListForMobile = {
        attach: function (context, settings) {
            var $window = $(window),
                $html = $('.serie-wrapper');

            $window.resize(function resize(){
                if ($window.width() < 768) {
                    remove_grid_classes()
                    return $html.addClass('list-view');
                } else if ($window.width() >= 768 && !$html.hasClass('list-view')) {
                    return $html.removeClass('list-view');
                    add_grid_classes()
                }
            }).trigger('resize');
        }
    };
    
    $(document).ready(function(){
        block = localStorage.getItem('displayType');
        if (block == 'list' && !$('serie-wrapper').hasClass('list-view')) {
            $('.serie-wrapper').addClass('list-view');
            remove_grid_classes();
        }
    });
    
    
    function remove_grid_classes() {
        // All series
        $('.serie-container').removeClass('col-lg-3');
        $('.serie-container').removeClass('col-md-4');
        $('.serie-container').removeClass('col-sm-6');

        //Favorite Series
        $('.my-serie').removeClass('col-lg-5ths');
        $('.my-serie').removeClass('col-md-3');
        $('.my-serie').removeClass('col-sm-4');   
    }

    function add_grid_classes() {
        //All series
        $('.serie-container').addClass('col-lg-3');
        $('.serie-container').addClass('col-md-4');
        $('.serie-container').addClass('col-sm-6');

        //Favorite Series
        $('.my-serie').addClass('col-lg-5ths');
        $('.my-serie').addClass('col-md-3');
        $('.my-serie').addClass('col-sm-4'); 
    }
    
})(jQuery);
