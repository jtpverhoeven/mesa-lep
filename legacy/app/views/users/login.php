<div class="container-narrow">
    <div class="row-fluid ">


        <div class="span12 ">

           <div class="alert alert-info {SHOW_LOGIN_REASON}">
            <strong><i class="icon-key"></i> {ALPC_LOGIN_SCREEN_REASON} </strong>
            </div>


            {formHeader}

            <span id="devNoticeShow" class="hide">
              <div class="panel panel-warning">

                    <div class="panel-heading">
                       <i class="icon-wrench"></i> {ALPC_LOGIN_DEV_TITLE}
                    </div>

                    <div class="panel-body">
                        <p> {ALPC_LOGIN_DEV_MSG} </p>
                        <p><i id="userCheckSpinner" class="icon-spinner icon-spin"></i> </p>
                        <p id="userCountP"> Kan niet inloggen in development modus, er zijn nog <strong id="userCount"></strong> gebruiker(s) actief in de productie omgeving  <a href="#" class="btn btn-mini" onClick="checkUsersActive();"> Opnieuw controleren </a>  <a href="#" class="btn btn-mini btn-danger" onClick="unlockLogin();"> Negeren </a> </p>

                    </div>

                </div>
            </span>

            <div id="loginPanel">
            {ALPC_LOGIN_USERNAME}
            {ALPC_LOGIN_PASSWORD}
            {ALPC_DEFAULT_PRINTER}
            
            {ALPC_LOGIN_DEVELOPMENT}
            {ALPC_LOGIN_DEVELOPMENT_UPDATE}


            <div class="pull-left">

                <div class="dropdown">

                    <button tabindex="-1" type="button" class="btn btn-mini btn-link dropdown-toggle" data-toggle="dropdown" ><i class="icon-plus-sign"></i> {ALPC_LOGIN_OPTIONS} </button>
                     <ul class="dropdown-menu">
                                <li id="devSwitch"><a href="#" onClick="toggleDatabase();" ><i class="icon-wrench"></i> {ALPC_LOGIN_DEV} </a></li>
                                <li id="prodSwitch" class="hide"><a href="#" onClick="toggleDatabase();" ><i class="icon-building"></i> {ALPC_LOGIN_PROD} </a></li>
                            </ul>
                </div>


            </div>



            <div class="pull-right">
                {loginButton}
            </div>

             </div>

            </form>
             {validationScript}
        </div>

    </div>
</div>

<script>

    window.isLoginPage = true;
    var dbSelectDevelop = false;
    var dbResyncDevelop = false;


    $(function(){
        $('#ALPC_LOGIN_USERNAME').focus();
        
        $('#ALPC_LOGIN_PASSWORD').bind('keydown', 'return', function(){            
            $('#loginForm').trigger('submit');
        });
        
        $('#ALPC_LOGIN_USERNAME').on('change', function(){
            checkUserPrinter();
        });

        $("body").toasty({
            image: '{LP}/img/toasty.png',
            sound: '{LP}/snd/toasty.mp3',
        });

  
    });

    $('#loginForm').on('submit', function(){

        if(dbSelectDevelop == true){

            if(dbResyncDevelop == true){
                 alert('{ALPC_DEV_UPDATING}');
            }

            $('#loginPanel').fadeOut();
            $('#ALPC_LOGIN_PASSWORD').unbind('keydown');
        }
    });

    function toggleDatabase(){

        $('#userCountP').hide();
        $('#userCheckSpinner').hide();

        if(dbSelectDevelop == false){

            checkUsersActive();
            $('#ALPC_LOGIN_DEVELOPMENT_UPDATE').val('1');
            dbSelectDevelop = true;
            dbResyncDevelop = true;
            $('#devNoticeShow').fadeIn();
            $('#devSwitch').hide();
            $('#prodSwitch').show();
            $('#ALPC_LOGIN_DEVELOPMENT').val('1');

        } else{
            dbSelectDevelop = false;
            dbResyncDevelop = false;
            $('#devNoticeShow').fadeOut();
            $('#devSwitch').show();
            $('#prodSwitch').hide();
            $('#ALPC_LOGIN_DEVELOPMENT').val('0');
            $('#ALPC_LOGIN_DEVELOPMENT_UPDATE').val('0');
        }

    }

    function checkUsersActive(){

      $('#userCheckSpinner').show();
      $.ajax({
         type: "POST",
         data: {},
         dataType: 'json',
         url: "{LB}/users/countActiveSessions"
     }).done(function(response) {

       $('#userCheckSpinner').hide();
       $('#userCount').html(response['count']);

       if(response['count'] > 0){
          $('#userCountP').show();
          $('#ALPC_LOGIN_USERNAME').prop('disabled', true);
          $('#ALPC_LOGIN_PASSWORD').prop('disabled', true);
       } else{
         $('#userCountP').hide();
         $('#ALPC_LOGIN_USERNAME').prop('disabled', false);
         $('#ALPC_LOGIN_PASSWORD').prop('disabled', false);
       }

     });
    }

    function checkUserPrinter()
    {
        var userName = $('#ALPC_LOGIN_USERNAME').val();
        $.ajax({
            type: "POST",
            data: { username : userName },
            dataType: 'json',
            url: "{LB}/users/checkUserHasPrinter"
        }).done(function(response) {
            console.log(response);
            $('#ALPC_DEFAULT_PRINTER').val(response['printer']);
        });

    }

    function unlockLogin(){
      $('#ALPC_LOGIN_USERNAME').prop('disabled', false);
      $('#ALPC_LOGIN_PASSWORD').prop('disabled', false);
    }

</script>
