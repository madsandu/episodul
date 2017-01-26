(function ($) {

    Drupal.behaviors.gridListView = {
        attach: function (context, settings) {

            //remember product list/grid choice using localStorage
            $('.list-grid .list:not(.a-processed)', context).each(function () {
                $(this).addClass('a-processed').on('click', function (e) {
                    $(this).closest('.list-allowed').addClass('list-view');
                    remove_grid_classes()
                });
            });
            
            $('.list-grid .grid:not(.a-processed)', context).each(function () {
                $(this).addClass('a-processed').on('click', function (e) {
                    $(this).closest('.list-allowed').removeClass('list-view');
                    add_grid_classes();//you need to pass string values, your variables display & block was not defined
                });
            });
        }
    };
    
    Drupal.behaviors.ForceListForMobile = {
        attach: function (context, settings) {
            var $window = $(window),
                $html = $('.lsit-allowed');

            $window.resize(function resize(){
                if ($window.width() < 768) {
                    remove_grid_classes();
                    return $html.addClass('list-view');
                    
                } else if ($window.width() >= 768 && !$html.hasClass('list-view')) {
                    return $html.removeClass('list-view');
                    add_grid_classes();
                }
            }).trigger('resize');
        }
    };
    
    $(document).ready(function(){
        block = localStorage.getItem('displayType');
        if (block == 'list' && !$('list-allowed').hasClass('list-view')) {
            $('.list-allowed').addClass('list-view');
            remove_grid_classes();
        }
    });
    
    // Remove grid classes at ajax complete
    $(document).ajaxComplete(function() {
        if (block == 'list') {
            remove_grid_classes();
        }
    });
    
    
    function remove_grid_classes() {
        //save display type to 'list'
        try {
            localStorage.setItem('displayType', 'list');
        } catch (error) {
            return false;
        }
        // All series
        $('.serie-container').removeClass('col-lg-3');
        $('.serie-container').removeClass('col-md-3');
        $('.serie-container').removeClass('col-sm-4');

        //Favorite Series
        $('.my-serie').removeClass('col-lg-5ths');
        $('.my-serie').removeClass('col-md-3');
        $('.my-serie').removeClass('col-sm-4');   
    }

    function add_grid_classes() {
        //save display type as 'grid'
        try {
            localStorage.setItem('displayType', '');
        } catch (error) {
            return false;
        }
        //All series
        $('.serie-container').addClass('col-lg-3');
        $('.serie-container').addClass('col-md-3');
        $('.serie-container').addClass('col-sm-4');

        //Favorite Series
        $('.my-serie').addClass('col-lg-5ths');
        $('.my-serie').addClass('col-md-3');
        $('.my-serie').addClass('col-sm-4'); 
    }
    
})(jQuery);



