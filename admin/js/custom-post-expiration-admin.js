(function( $ ) {
    'use strict';

    $(function() {
        // Update current time every second
        setInterval(function() {
            var now = new Date();
            var dateString = now.getFullYear() + '-' + 
                             ('0' + (now.getMonth()+1)).slice(-2) + '-' + 
                             ('0' + now.getDate()).slice(-2);
            var timeString = ('0' + now.getHours()).slice(-2) + ':' + 
                             ('0' + now.getMinutes()).slice(-2) + ':' + 
                             ('0' + now.getSeconds()).slice(-2);
            $('#cpen_current_datetime').text(dateString + ' ' + timeString);
        }, 1000);
    });

})( jQuery );