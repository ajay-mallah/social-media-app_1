$(document).ready(function () {
    $(".posts").on ('click', '.comment', function(event) {
        $(this).parent().parent().siblings(".comment-input").toggleClass("grow");
        $(this).parent().parent().siblings(".comment-input").children(".comment-btn").children(".loader-btn").fadeOut("fast");
        $(this).parent().parent().siblings(".comment-input").children(".comment-btn").children(".text").fadeIn("fast");
    })

    $(".posts").on('click', '.comments-count',function() {
        var comment = $(this).parent().siblings(".user-comments--container").toggleClass("grow");
        console.log('count');
    });

    $("#logout").click(function() {
        $.post ('/logout', function(data,success) {
            window.location.href = "/login";
        });
    });

    // adding post 
    $('#post-submit-btn').click(function(event) {
        // grab data
        var data = {};
        data.text = $('[name="add-post"]').val();

        // make post request
        $.post(
            '/post/add',
            data,
            function (data, success) {
                if (!data.status) {
                    $('.posts').prepend(data);
                }
            }
        )
    });

    $(".posts").on('click', '.edit-dots',function() {
        var option = $(this).siblings('.edit-option');
        option.toggleClass('d-block');
    })

    // Handling edit comments action
    $(".posts").on('click', '.edit-comment',function() {
        var parent = $(this).parents('.edit-comment-container');
        var sibling = parent.siblings('.comment-body');
        sibling.children('.user-comment').toggleClass('grow');
        sibling.children('.edit-comment-input').toggleClass('grow');
    })

    // Handling edit comments action
    $(".posts").on('click', '.comment-save-btn',function() {
        var data = {};
        data.text = $(this).parent().children('textarea').val();
        data.comment_id = $(this).attr('comment-id');
        var parent = $(this).parents('.comment-body');

        $.post(
            '/comments/edit',
            data,
            function (data, success) {
                if (data.status == "success") {
                    parent.children('.user-comment').html(data.message);
                    parent.children('.user-comment').toggleClass('grow');
                    parent.children('.edit-comment-input').toggleClass('grow');
                }
            }
        )
    })

    // Handling add comments action
    $(".posts").on('click', '.comment-btn',function() {
        var data = {};
        data.text = $(this).parent().children('textarea').val();
        data.post_id = $(this).attr('post-id');
        var parent = $(this).parents('.comment-input');
        var commentContainer = $(this).parents('.post-container').children('.user-comments--container');
        var commentCounter = $(this).parents('.post-container').children('.post-info-container').children('.comments-count').children('span');

        $.post(
            '/comments/add',
            data,
            function (data, success) {
                console.log(data + "ADD");
                if (!data.status) {
                    parent.toggleClass('grow');
                    commentContainer.prepend(data);
                    var count = commentCounter.html();
                    commentCounter.html(parseInt(count) + 1);
                    $(this).children(".text").fadeToggle("fast");
                    $(this).children(".loader-btn").fadeToggle("slow");
                }
            }
        )

        console.log(commentContainer.html());

    })

    // Handling delete comments action
    $(".posts").on('click', '.delete-comment',function() {
        var postContainer = $(this).parents('.user-comment-box');
        var comment_id = $(this).parent().attr('comment-id');
        var comment_count = $(this).parents('.post-container').children('.post-info-container').children('.comments-count').children('span');

        $.post(
            '/comments/delete',
            {
                comment_id: comment_id,
            },
            function (data, success) {
                if (data.status == "success") {
                    postContainer.remove();
                    var count = comment_count.html();
                    comment_count.html(parseInt(count) - 1);
                }
            }
        )
    })

    // Handling delete comments action
    $(".posts").on('click', '.like',function() {
        var data = {};
        data.post_id = $(this).attr('post-id');
        data.like_id = $(this).attr('like-id') != 0 ? $(this).attr('like-id') : null;
        var likeCounter = $(this).siblings('.total-like').children('span');
        var count = parseInt(likeCounter.html());
        console.log(data);
        $.post(
            '/likes',
            data,
            function (data, success) {
                console.log(data);
                if (data.status === "error") {
                    alert(data.message);
                }
                else if (data.status === "added") {
                    $(this).attr('like-id', `${data.message.id}`);
                    console.log(data.message.id);
                    likeCounter.html(count + 1);
                }
                else if (data.message) {
                    likeCounter.html(count + 1);
                }
                else {
                    likeCounter.html(count - 1);
                }
            }
        )
    })

    // Handling delete post action
    $(".posts").on('click', '.delete-post',function() {
        var postContainer = $(this).parents('.post-container');
        var post_id = postContainer.attr('post-id');

        $.post(
            '/post/delete',
            {
                post_id: post_id,
            },
            function (data, success) {
                console.log(data);
                if (data.status == "success") {
                    postContainer.remove();
                }
            }
        )
    })

    // Handling edit post action
    $(".posts").on('click', '.edit-post',function() {
        var contentContainer = $(this).parents('.post-container').children('.content-container');
        contentContainer.children('.post-text').toggleClass('grow');
        contentContainer.children('.edit-post-input').toggleClass('grow');
    })

    // Handling edit post action
    $(".posts").on('click', '.post-edit-btn',function() {
        var contentContainer = $(this).parents('.post-container').children('.content-container');
        var parent = $(this).parents('.content-container');

        var data = {};
        data.text = $(this).parent().children('textarea').val();
        data.post_id = $(this).attr('post-id');

        $.post(
            '/post/edit',
            data,
            function (data, success) {
                if (data.status == "success") {
                    console.log(data);
                    contentContainer.children('.post-text').html(data.message);
                    contentContainer.children('.post-text').toggleClass('grow');
                    contentContainer.children('.edit-post-input').toggleClass('grow');
                }
            }
        )
    })

    // Add profile image button
    $('#add-profile-pic').click(function() {
        $('.profile-form-container').toggleClass("d-flex");
    })

    $('.cross-icon').click(function() {
        $(this).parents('.profile-form-container').toggleClass("d-flex");
    });


    // Handling file change
    $('[name="upload-profile-pic"]').change(function(){
        const file = this.files[0];
        if (file){
            let reader = new FileReader();
            reader.onload = function(event){
                $('#imgPreview').attr('src', event.target.result);
            }
            reader.readAsDataURL(file);
        }
    });


    // Handling upload image form 
    $('[name="upload-profile"]').click(function(event) {
        event.preventDefault();
        // var file = $('[name="upload-profile-pic"]').files[0];
        var file = $('[name="upload-profile-pic"]')[0].files[0];
        console.log(file);
    
        var data = new FormData();
        data.append('image', file);
        
        $.ajax({
            url: "/profile/upload",
            type: "POST",
            data: data,
            processData: false,
            contentType: false,
            cache: false,
            success: function(data) {
                if (data.status == "success") {
                    $('.profile-form-container').toggleClass("d-flex");
                    $('#add-profile-pic').attr('src', data.fileName);
                }
            }
        });
    })
});