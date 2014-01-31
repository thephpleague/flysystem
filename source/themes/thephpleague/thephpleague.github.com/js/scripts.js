$(function()
{
    var container = $('.all_packages');
    var opened;

    function load_packages()
    {
        $.ajax({
            dataType: 'jsonp',
            jsonpCallback: 'phploep_jsonp_callback',
            url: 'http://thephpleague.com/js/packages.js',
            success: function (packages) {

                $.each(packages, function(index, package) {
                    container.find('ul').append('<li><a href="' + package.website + '">' + package.name + '</a> <span>' + package.description + '</span></li>');
                });

                show_packages();
            }
        });
    }

    function show_packages()
    {
        container
            .css({
                'margin-top': '-' + container.outerHeight() + 'px'
            })
            .show()
            .animate({
                'margin-top': 0
            });

        opened = true;
    }

    function hide_packages()
    {
        container.animate({
            'margin-top': '-' + container.outerHeight() + 'px'
        });

        opened = false;
    }

    $('header a.league').click(function(event)
    {
        event.preventDefault();

        if (opened == undefined) {
            load_packages();
        } else if (opened === false) {
            show_packages();
        } else if (opened === true) {
            hide_packages();
        }
    });
});