
<div id="accountSettingsModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="accountSettingsModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="accountSettingsModalTitle">{MESA_ACS_TITLE}</h3>
    </div>
    <div class="modal-body" id="accountSettingsModalBody" >    
       
        
        
        <div class="tabbable tabs-left">
                        
                        <ul id="acSetTabs" class="nav nav-tabs">
                            <li class="active"><a href="#acSetInfo" data-toggle="tab" tabindex="-1" load="details" ><i class="icon-user"></i> {MESA_ACS_DETAILS}</a></li>
                            <li><a href="#acSetAlias" data-toggle="tab" tabindex="-1" load="alias"><i class="icon-group"></i> {MESA_ACS_CHANGEALIAS}</a></li>
                            <li><a href="#acSetAvatar" data-toggle="tab" tabindex="-1" load="avatar"><i class="icon-camera"></i> {MESA_ACS_AVATAR}</a></li>
                            <li><a href="#acSetSignature" data-toggle="tab" tabindex="-1" load="signature"><i class="icon-edit"></i> {MESA_ACS_SIG}</a></li>
                            <li><a href="#acSetPassword" data-toggle="tab" tabindex="-1" load="password"><i class="icon-key"></i> {MESA_ACS_PASS}</a></li>
                            <li><a href="#acSetLang" data-toggle="tab" tabindex="-1" load="language"><i class="icon-flag"></i> {MESA_ACS_LANG}</a></li>                                                        
                        </ul>
            
            
                    <div class="tab-content">

                      
                            <div class="tab-pane active" id="acSetInfo">      
                      
                            </div>

                            <div class="tab-pane" id="acSetAlias">

                            </div>
                        
                            <div class="tab-pane" id="acSetAvatar">   
                             
                            </div>
                        
                            <div class="tab-pane" id="acSetSignature"> 
                              
                            </div>
                        
                            <div class="tab-pane" id="acSetPassword">   
                              
                            </div>
                        
                            <div class="tab-pane" id="acSetLang">  
                             
                            </div>
                        

                           

                        </div>
                        
                    </div>
        
              
        
        
    </div>
   <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CLOSE}</button>        
    </div>
</div>

<script>
    
$(function(){
    
   /*  $('#profileOptions').on('click', 'li', function(){

          //set the pointer
          $("#profileOptions").find('.active').removeClass('active');  
          $(this).addClass('active');   
          
    }); */
      
    $('#acSetTabs a[data-toggle="tab"]').on('shown', function (e) {        
        editLoader($(e.target).attr('load'), $(e.target).attr('href'));  
        
    });
  
});  


function editLoader(loader, target){
 
    //load the goods
    $.ajax({
        type: "POST",
        data: {},
        url: "{LB}/profiles/profileEditLoader/" + loader
    }).done(function(response) {
        $(target).html(response);        
    });   

}

function saveProfileEdit(){
    
    $.ajax({
        type: "POST",                                 
        data: $("#editMyProfileForm").serialize(),
        url: "{LB}/profiles/saveMyProfile/" 
    }).done(function() {               
        editLoader('details', '#acSetInfo');
        alert('{MESA_ACS_SAVED}');        
    });
}

function changePassword(){
    
    if($('#newPassRepeat').val() != $('#newPass').val()){        
        alert('Wrong repeated password, please retype to ensure that you made no typing mistakes');
        $('#newPassRepeat').val('');
        $('#newPass').val('')
    } else{                 
        
        $.ajax({
            type: "POST",                                 
            data: $("#changePasswordForm").serialize(),
            url: "{LB}/users/changeMyPassword/" 
        }).done(function(msg) {               
            if(msg == 'true'){
                alert('{MESA_ACS_PASSSAVED}');
                $('#oldPass').val('');
                $('#newPassRepeat').val('');
                $('#newPass').val('')
            }
            if(msg == 'false'){
                alert('{MESA_ACS_PASSERROR}');
        }
        });
    }        
}

function changeLanguage(){
    var newLang = $('#accSetLang').val();
    
    $.ajax({
        type: "POST",                                 
        data: {lang: newLang},
        url: "{LB}/users/setLang" 
    }).done(function() {                       
        alert('{MESA_PRO_LANGWASSET}');             
    });

}

function changeAlias(){
    var newAlias = $('#alias').val();

    $.ajax({
        type: "POST",
        data: {alias: newAlias},
        url: "{LB}/users/saveAlias"
    }).done(function() {
        alert('{MESA_ACS_ALIAS_SAVED}');
    });
}
    
editLoader('details', '#acSetInfo' );    
    
</script> 
