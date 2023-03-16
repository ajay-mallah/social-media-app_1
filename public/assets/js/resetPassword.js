$(document).ready(function () {

    $("[name='verify']").click(function(element) {
        element.preventDefault();
        // Resets error messages.
        resetErrorMsg();
        // Enabling loader.
        toggleLoader($(this));
        // Form validation
        var jsonData = {};
        jsonData.email = $('[name="email"]').val();
        if(!jsonData.email) {
            addResponseMessage("Please fill all the empty filed", "text-danger");
        }
        else {
            // Making post request to register user.
            sendResetKey(jsonData, "verify_email", $(this));
        }
        toggleLoader($(this));
    });

    $("[name='reset']").click(function(element) {
        element.preventDefault();
        // Resets error messages.
        resetErrorMsg();
        // Enabling loader.
        toggleLoader($(this));
        
        // // Form validation
        var data = {};
        data.key = $("[name='reset-key']").val();
        data.email = $("[name='email']").val();
        data.confPassword = $("[name='confPassword']").val();
        data.password = $("[name='password']").val();
        if (!data.key || !data.confPassword || !data.password) {
            addResponseMessage("Please fill the otp filed.", "text-danger");
        }
        else if (validateForm()) {
            // make ajax post request
            $.post('/reset/reset_password',
                data,
                function (data, success) {
                    if (data.status == "success") {
                        window.location.href = '/login';
                    }
                    else {
                        addResponseMessage(data.message, 'text-danger');
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
        data.email = $('[name="email"]').val();
        // post request
        sendResetKey(data, "resend");
    });

    function sendResetKey(data, operation, element) {
        $.post('/reset_password/send',
            data,
            function(data, success) {
                console.log(data);
                if (data.status == "danger") {
                    addResponseMessage(data.message, "text-danger");
                }
                else if (data.status == "success") {
                    addResponseMessage("reset password key has been send", "text-success");
                    resendHtmlSetter(new Date);
                    if(operation == "verify_email") {
                        $(".verify-email").removeClass("enable");
                        $(".reset").addClass("enable");
                        toggleLoader(element);
                    }
                    else {
                        $('.resend-loading').css('display', 'none');
                    }
                }
            }
        );
    }
 
    function resendHtmlSetter(start) {
        $("#resend-time").css("display", "inline");
        $("#resend").css("display", "none");
        $('#remain-time').html(`30s`);

        var refreshId = setInterval(function() {
            var total_seconds = (new Date - start) / 1000;   
            var seconds = Math.floor(total_seconds);
            $('#remain-time').html(`${30 - seconds}s`);
            if (seconds >= 30) {
                clearInterval(refreshId);
            }
        }, 1000);

        setTimeout(() => {
            $("#resend-time").css("display", "none");
            $("#resend").css("display", "inline");
        }, 30000);
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
        }
    });

    $('#username-btn').click(function() {
        if ($(this).hasClass('text-primary')) {
            $(this).toggleClass('text-primary');
            $(this).css('cursor', 'default');
            $('#email-btn').toggleClass('text-primary');
            $('#email-btn').css('cursor', 'pointer');
            $('[name="uid"]').attr('title', 'username');
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
        setTimeout(function() {
            $("#response-msg").html("");
        },5000);
    }

    function validateForm() {
        var isValid = true;
        if ($("[name='password']").val().length < 8) {
            $("#error-password").html("minimum password length should be 8");
            isValid &= false;
        }
        else if ($("[name='password']").val() !== $("[name='confPassword']").val()) {
            $("#error-confPassword").html("Password and confirmed passwords are not same.");
            isValid &= false;
        }
        return isValid;
    }
});