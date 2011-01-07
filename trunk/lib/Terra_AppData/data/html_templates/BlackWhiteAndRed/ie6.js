$(document).ready(function() {
    
    $('tr, button, #toggle_header, input, select').hover(function() {
        $(this).addClass('hover');
    }, function() {
        $(this).removeClass('hover');
    });
});