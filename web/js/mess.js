$(document).ready(function(){

    var login = $('div#login').text();

    var display_url = '../../../../../chat/web/user/profile/' + login + '/display';
    var chat_url = '../../../../../../chat/web/user/profile/' + login + '/chat';

    console.log('name: ' + login);

    var interval = 1000;

    setInterval(function(login){

        // console.log('name in interval: ' + login);
        // console.log('url in interval: ' + login);

        $.ajax({
            type : 'get',
            url : display_url,
            dataType : 'html',
            success : function(html, textStatus){
                $('#chatDiv').html(html);
            },
            error : function(XMLHttpRequest, textStatus, errorThrown) {
                $('#chatDiv').val('false');
            }
        });
        return false;
    }, interval);


    var form = $('#testForm');
    console.log(form);

    form.submit(function(login){

        // fetch the data for the form
        var data = $('#testForm').serializeArray();

        // setup the ajax request
        $.ajax({
            url: chat_url,
            data: data,
            success: function(data) {
                $("#content").html(data);
            }
        });
        return false;
    });
});
