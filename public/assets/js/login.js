$(document).ready(function () {

    $("[name='login']").click(function(element) {
        element.preventDefault();
        // Resets error messages.
        resetErrorMsg();
        // Enabling loader.
        toggleLoader($(this));
        // Form validation
        var formData = $(".login-verify").serializeArray();
        var isEmpty = false;
        var jsonData = {};
        formData.forEach(el => {
            if (!el.value) {
                isEmpty = true;
                return;
            }
            jsonData[el.name] = el.value;
        });
        if(isEmpty) {
            addResponseMessage("Please fill all the empty filed", "text-danger");
            toggleLoader($(this));
        }
        else {
            jsonData.title = $('[name="uid"]').attr('title');
            // Making post request to register user.
            $.post('/login', 
                jsonData,
                function (data, success) {
                    if (data.status && data.status == "danger") {
                        // Assigning error fields value.
                        data["message"].forEach(msg=> {
                            $(`#${msg[0]}`).html(msg[1]);
                        });
                        toggleLoader($("[name='login']"));
                    }
                    else if (data.status &&  data.status == "unverified") {
                        sendOTP(jsonData, "login", $("[name='login']"));

                    }
                    else if (data == "login") {
                        window.location.href = '/'; 
                        toggleLoader($("[name='login']"));
                    }
                }
            )
        }
    });

    $("[name='verify-otp']").click(function(element) {
        element.preventDefault();
        // Resets error messages.
        resetErrorMsg();
        // Enabling loader.
        toggleLoader($(this));
        
        // // Form validation
        var data = {};
        data.uid = $("[name='uid']").val();
        data.title = $('[name="uid"]').attr('title');
        data.otp = $("[name='otp']").val();
        if (!data.otp) {
            addResponseMessage("Please fill the otp filed.", "text-danger");
        }
        else {
            // make ajax post request
            $.post('/otp/verify',
                data,
                function (data, success) {
                    if (data.status == "success") {
                        window.location.href = '/';
                    }
                    else {
                        addResponseMessage(data.message, `text-${data.status}`);
                    }
                    toggleLoader($(this));
                }
            )
        }
    });

    $('#resend').click(function(event) {
        event.preventDefault();
        $(this).css('display', 'none');
        $('.resend-loading').css('display', 'inline');
        var data = {};
        data.title = $('[name="uid"]').attr('title');
        data.uid = $("[name='uid']").val();
        // post request
        sendOTP(data, "resend");
    });

    function sendOTP(data, operation, element) {
        $.post('/otp/send',
            data,
            function(data, success) {
                addResponseMessage(data.message, `text-${data.status}`);
                resendHtmlSetter(new Date);
                if(operation == "login") {
                    $(".login-verify").removeClass("enable");
                    $(".verify-otp").addClass("enable");
                }
                else {
                    $('.resend-loading').css('display', 'none');
                    toggleLoader(element);
                }
            }
        );
    }
 
    function resendHtmlSetter(start) {
        $("#resend-time").css("display", "inline");
        $("#resend").css("display", "none");
        $('#remain-time').html(`10s`);

        var refreshId = setInterval(function() {
            var total_seconds = (new Date - start) / 1000;   
            var seconds = Math.floor(total_seconds);
            $('#remain-time').html(`${10 - seconds}s`);
            if (seconds >= 10) {
                clearInterval(refreshId);
            }
        }, 1000);

        setTimeout(() => {
            $("#resend-time").css("display", "none");
            $("#resend").css("display", "inline");
        }, 10000);
    }

    // Resetting error messages.
    function resetErrorMsg() {
        $(".error").each(function(index, element){
            $(element).html("&nbsp");
        });
    }

    $('#email-btn').click(function() {
        if ($(this).hasClass('text-primary')) {
            $(this).toggleClass('text-primary');
            $(this).css('cursor', 'default');
            $('#username-btn').toggleClass('text-primary');
            $('#username-btn').css('cursor', 'pointer');
            $('[name="uid"]').attr('title', 'email');
            $('[name="uid"]').attr('placeholder', 'email ...');
        }
    });

    $('#username-btn').click(function() {
        if ($(this).hasClass('text-primary')) {
            $(this).toggleClass('text-primary');
            $(this).css('cursor', 'default');
            $('#email-btn').toggleClass('text-primary');
            $('#email-btn').css('cursor', 'pointer');
            $('[name="uid"]').attr('title', 'username');
            $('[name="uid"]').attr('placeholder', 'username ...');
        }
    });

    function toggleLoader(element) {
        element.children(".loader-btn").fadeToggle("fast");
        element.children(".text").fadeToggle("slow");
    }


    function addResponseMessage(message, cssClass) {
        $("#response-msg").html(message);
        $("#response-msg").removeClass("text-danger");
        $("#response-msg").removeClass("text-success");
        $("#response-msg").addClass(cssClass);
        if (cssClass == "text-success") {
            setTimeout(function() {
                $("#response-msg").html("");
            },5000);
        }
    }
});