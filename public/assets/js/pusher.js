
var pusher = new Pusher('d345442ff53937d09791', {
    cluster: 'ap2'
});

var channel = pusher.subscribe('chirptalk-development');
channel.bind('active-users', function(data) {
    $.post(
        "/Update/userList",
        {},
        function(data, success) {
            $('.users-container').html("");
            $('.users-container').append(data);
        }
    )
});
