jQuery(function($) {
    var $formLogin = $('#login-form');
    var $formLost = $('#lost-form');
    var $formRegister = $('#register-form');
    var $divForms = $('#div-forms');
    var $modalAnimateTime = 300;
    var $msgAnimateTime = 150;
    
    $('#login_register_btn').click( function () { modalAnimate($formLogin, $formRegister); });
    $('#register_login_btn').click( function () { modalAnimate($formRegister, $formLogin); });
    $('#login_lost_btn').click( function () { modalAnimate($formLogin, $formLost); });
    $('#lost_login_btn').click( function () { modalAnimate($formLost, $formLogin); });
    $('#lost_register_btn').click( function () { modalAnimate($formLost, $formRegister); });
    $('#register_lost_btn').click( function () { modalAnimate($formRegister, $formLost); });
    
    function modalAnimate ($oldForm, $newForm) {
        var $oldH = $oldForm.height();
        var $newH = $newForm.height();
        $divForms.css("height",$oldH);
        $oldForm.fadeToggle($modalAnimateTime, function(){
            $divForms.animate({height: $newH}, $modalAnimateTime, function(){
                $newForm.fadeToggle($modalAnimateTime);
            });
        });
    }
    
    $('#register-form').submit( function(e) {
        if (e.preventDefault) {
            e.preventDefault();
        } else {
            e.returnValue = false;
        }
        $('#mensajes-register').html('');
        $('#mensajes-register').addClass('loading');
        
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: ed_login_vars.ajax_url,
            data: {
                'action': 'new_user',
                'nonce': $('#new_user_nonce').val(),
                'user_name': $('#new_user_name').val(),
                'user_mail': $('#new_user_mail').val(),
            },
            success: function(response) {
                $('#mensajes-register').removeClass('loading');
                $('#mensajes-register').html( response.mensaje);
                if( response.result === 0 ) {
                    document.location.href = ed_login_vars.redirecturl;
                }
            }
        });
    });
    $('#login-form').submit( function(e) {
        if (e.preventDefault) {
            e.preventDefault();
        } else {
            e.returnValue = false;
        }
        $('#mensajes-login').html('');
        $('#mensajes-login').addClass('loading');
        
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: ed_login_vars.ajax_url,
            data: {
                'action': 'login_user',
                'nonce': $('#login_user_nonce').val(),
                'login_user_name': $('#login_user_name').val(),
                'login_user_pass': $('#login_user_pass').val(),
            },
            success: function(response) {
                $('#mensajes-login').removeClass('loading');
                $('#mensajes-login').html( response.mensaje);
                if( response.result === 0 ) {
                    document.location.href = ed_login_vars.redirecturl;
                }
            }
        });
    });
    $('#lost-form').submit( function(e) {
        if (e.preventDefault) {
            e.preventDefault();
        } else {
            e.returnValue = false;
        }
        $('#mensajes-lost').html('');
        $('#mensajes-lost').addClass('loading');
        
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: ed_login_vars.ajax_url,
            data: {
                'action': 'lost_password',
                'nonce': $('#lost_password_nonce').val(),
                'user_mail': $('#user_mail').val(),
            },
            success: function(response) {
                $('#mensajes-lost').removeClass('loading');
                $('#mensajes-lost').html( response.mensaje);
                if( response.result === 0 ) {
                    document.location.href = ed_login_vars.redirecturl;
                }
            }
        });
    });
});