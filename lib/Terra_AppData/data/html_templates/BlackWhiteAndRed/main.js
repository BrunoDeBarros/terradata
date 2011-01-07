// Main JavaScript file for the default TD_Admin Template.

$(document).ready(function() {
    var show = 'Hide';
    if (jQuery.cookie('header_hidden')) {
        show = 'Show';
        $('#header').hide();
    }
    $('#header').after('<div class="container"><div id="toggle_header">'+show+'</div></div><div class="clear"></div>');
    $('#toggle_header').click(function() {
        if ($('#header:hidden').length == 0) {
            $('#header').slideUp();
            $('#toggle_header').html('Show');
            jQuery.cookie('header_hidden', true);
        } else {
            $('#header').slideDown();
            $('#toggle_header').html('Hide');
            jQuery.cookie('header_hidden', null);
        }
    });

    $('label').hover(function() {
        forattr = $(this).attr('for');
        if (forattr) {
            $('#'+forattr).trigger('mouseenter');
        }
    }, function() {
        forattr = $(this).attr('for');
        if (forattr) {
            $('#'+forattr).trigger('mouseleave');
        }
    });


    /**
     * @todo: Implement a better way of deleting, that shows deleted successfully message, gets next row, goes back if page is empty, etc.

    $('.delete-link').click(function() {
        var id = $(this).parent('td').parent('tr').attr('id');
        $.get($(this).attr('href'), function(data) {
            $('#'+id).fadeOut(function() {
                $(this).remove();
            });
        });

        $(this).parent('td').html('<span>Deleting...</span>');
        return false;
    });
    */
});