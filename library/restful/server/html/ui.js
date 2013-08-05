// Load jQuery library using plain JavaScript
// http://www.jquery4u.com/javascript/dynamically-load-jquery-library-javascript/
(function (baseUrl) {
    
    function main() {
        //jQuery loaded
        $(function() {
            
            // Intro text control
            $('.intro').prepend('<div class="control">-</div>');
            $('.control').click(function() {
                $(this).parent().toggleClass('collapsed');
                if ($(this).parent().hasClass('collapsed'))
                    $(this).text('+');
                else
                    $(this).text('-');
            });
            
            // Open / close left side sections (resources, methods, etc.)
            $('.discovery div > div:first-child').click(function () {
                $(this).next('div').slideToggle('fast').css('margin-left', '1em');
            });
            
            $('.sandbox input[type=submit]').click(function() {
                var form$ = $(this).parent();
                var url = form$.children('label.qs').text() + form$.children('input.qs').val();
                var accept = form$.children('select').children('option').filter(':selected').text();
                $.ajax({
                    cache: false,
                    beforeSend: function(jqXHR) {
                        jqXHR.setRequestHeader('Accept', accept);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        if ('200' == jqXHR.status) { // Not an HTTP error, maybe a JQuery error?
                            form$.siblings('.status').removeClass('error').text(jqXHR.status + ' ' + jqXHR.statusText);
                            console.log('JQuery error status: ' + textStatus);
                            console.log('JQuery error thrown: ' + errorThrown);
                            //form$.siblings('.message').text('JQuery reported errors, see the console to read more.');
                        } else {
                            form$.siblings('.status').addClass('error').text(jqXHR.status + ' ' + jqXHR.statusText);
                            //form$.siblings('.message').text(jqXHR.responseText);
                        }
                    },
                    success: function(data, textStatus, jqXHR) {
                        form$.siblings('.status').removeClass('error').text(jqXHR.status + ' ' + jqXHR.statusText);
                        //form$.siblings('.message').text('');
                    },
                    complete: function(jqXHR) {
                        output  = "Request url\n===========\n" + url + "\n\n";
                        output += "Request accept\n==============\n" + accept + "\n\n";
                        output += "Response headers\n================\n" + jqXHR.getAllResponseHeaders() + "\n\n";
                        output += "Response body\n=============\n" + jqXHR.responseText + "\n\n";
                        form$.siblings('.dialog').text(output);
                    },
                    jsonpCallback: 'parseResponse',
                    timeout: 3000,
                    type: 'GET',
                    url: url
                });
                                                   
                return false;
            });
        });
    }
    
    if (typeof(jQuery) == 'undefined') {
        var script = document.createElement("script")
        script.type = "text/javascript";

        if (script.readyState) { //IE
            script.onreadystatechange = function () {
                if (script.readyState == "loaded" || script.readyState == "complete") {
                    script.onreadystatechange = null;
                    main();
                }
            };
        } else { //Others
            script.onload = function () {
                main();
            };
        }

        script.src = "http://code.jquery.com/jquery-latest.min.js";
        document.getElementsByTagName("head")[0].appendChild(script);
    } else {
        main();
    }
})('http://services9.istat.it/');