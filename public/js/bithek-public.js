(function ($) {
    'use strict';

    $(function () {
        $('.bithek-result').each(function () {
            var isbn = $(this).data('isbn');
            if (isbn) {
                var url = 'https://www.googleapis.com/books/v1/volumes?q=isbn: ' + isbn + '&key=AIzaSyCWFiQPpRYkbdJR2rDaORQa7nAb15w2Jck';
                $.ajax({
                    context: this,
                    url: url,
                    success: function (data) {
                    if (data.totalItems > 0 && typeof data.items[0].volumeInfo.imageLinks != 'undefined') {
                        var $image = $('<img />').attr('src', data.items[0].volumeInfo.imageLinks.smallThumbnail);
                        $(this).find('td.image').append($image);
                    }
                }});

            }

        });
    });

})(jQuery);
