// Load jQuery library using plain JavaScript
// http://www.jquery4u.com/javascript/dynamically-load-jquery-library-javascript/
(function () {

    function loadScript(url, callback) {
        if (typeof(jQuery) == 'undefined') {
            var script = document.createElement("script")
            script.type = "text/javascript";

            if (script.readyState) { //IE
                script.onreadystatechange = function () {
                    if (script.readyState == "loaded" || script.readyState == "complete") {
                        script.onreadystatechange = null;
                        callback();
                    }
                };
            } else { //Others
                script.onload = function () {
                    callback();
                };
            }

            script.src = url;
            document.getElementsByTagName("head")[0].appendChild(script);
        } else {
            callback();
        }
    }

    loadScript("http://code.jquery.com/jquery-latest.min.js", function () {

        //jQuery loaded
        $(function() {
            $('.mars div > div:first-child').click(function () {
                $(this).next('div').slideToggle('fast').css('margin-left', '1em');
            });

            $('.tool a').click(function() {
                var method = $(this).parent('p').parent('div').prev('div').children('strong').text();
                $('.sandbox form legend').text(method);
                $('.sandbox').fadeToggle('fast');
            });

            $('.sandbox button').click(function() {
                var url = $('.sandbox form legend').text() + '?' + $('.sandbox form input').val();
                var accept = $('.sandbox form select option').filter(':selected').text();
                // accepts setting does not work
                $.ajax({
                    cache: false,
                    beforeSend: function(jqXHR) {
                        jqXHR.setRequestHeader('Accept', accept);
                    },
                    complete: function(jqXHR) {
                        $('.sandbox .request').text("Request url\n===========\n\n" + url).fadeIn('fast');
                        $('.sandbox .accept').text("Request accept\n==============\n\n" + accept).fadeIn('fast');
                        $('.sandbox .headers').text("Response headers\n================\n\n" + jqXHR.getAllResponseHeaders()).fadeIn('fast');
                        $('.sandbox .body').text("Response body\n=============\n\n" + jqXHR.responseText).fadeIn('fast');
                    },
                    timeout: 3000,
                    type: 'GET',
                    url: url
                });

                return false;
            });
        });
    });

})();