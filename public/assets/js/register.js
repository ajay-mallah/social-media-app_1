$(document).ready(function () {

    $("[name='register']").click(function(element) {
        element.preventDefault();
        // Resets error messages.
        resetErrorMsg();
        // Enabling loader.
        toggleLoader($(this));

        // Form validation
        var formData = $(".register-form").serializeArray();
        var isEmpty = false;
        var data = {};
        formData.forEach(el => {
            if (!el.value) {
                isEmpty = true;
                return;
            }
            data[el.name] = el.value;
        });
        if(isEmpty) {
            addResponseMessage("Please fill all the empty filed", "text-danger");
            toggleLoader($(this));
        }
        else if (validateForm()) {
            // Making post request to register user.
            $.post('/register', 
                data,
                function (data, success) {
                    if (data.status == "invalid") {
                        // Assigning error fields value.
                        data["message"].forEach(msg=> {
                            $(`#${msg[0]}`).html(msg[1]);
                        });
                        toggleLoader($("[name='register']"));
                    }
                    else if (data.status == "valid") {
                        sendOTP({'uid' : data.uid, 'title' : 'id'}, "register", $("[name='register']"))
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
        $(this).children(".text").fadeToggle("fast");
        $(this).children(".loader-btn").fadeToggle("slow");
        
        // // Form validation
        var data = {};
        data.uid = $("[name='email']").val();
        data.title = "email";
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
                }
            )
        }
        $(this).children(".loader-btn").fadeToggle("fast");
        $(this).children(".text").fadeToggle("slow");
    });

    $('#resend').click(function(event) {
        event.preventDefault();
        $(this).css('display', 'none');
        $('.resend-loading').css('display', 'inline');
        var data = {};
        data.title = "email";
        data.uid = $("[name='email']").val();
        // post request
        sendOTP(data, "resend");
    });

    function sendOTP(data, operation, element) {
        $.post('/otp/send',
            data,
            function(data, success) {
                addResponseMessage(data.message, `text-${data.status}`);
                resendHtmlSetter(new Date);
                if(operation == "register") {
                    $(".register-form").removeClass("enable");
                    $(".verify-otp").addClass("enable");                    
                    toggleLoader(element);
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
            $(element).html("");
        });
    }

    function validateForm() {
        var isValid = true;
        if (!validNameSyntax($("[name='fullName']").val())) {
            $("#error-fullName").html("Only alphabets and white spaces are allowed.");
            isValid &= false;
        }
        if (!validEmailSyntax($("[name='email']").val())) {
            $("#error-email").html("Please enter email in valid email format.");
            isValid &= false;
        }
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

    function validNameSyntax(value) {
        var regex = /^[a-zA-Z]+[ ][a-zA-Z]+$/;
        var regex2 = /^[a-zA-Z]+$/;
        return regex.test(value) || regex2.test(value);
    }

    function validEmailSyntax(email) {
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email);
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

    function toggleLoader(element) {
        element.children(".loader-btn").fadeToggle("fast");
        element.children(".text").fadeToggle("slow");
    }
});