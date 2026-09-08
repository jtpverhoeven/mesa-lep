<div class="span10">
    <div class="row-fluid">

        <div class="span3">
            <div class="well well-small">
                <h6><i class="icon-search"></i> {MESA_UAD_ADDANDSEARCH} </h6>

                <div id="saveConfirmationDiv" class="hide">
                    <div class="alert">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>{MESA_UAD_SAVED}</strong> {MESA_UAD_SAVEDESCRIPTION}
                  </div>
                </div>

                <input id="unameSearch" class="input-block-level" type="text" style="margin-bottom: 5px;"/>
                <br />

                <div class="pull-right">
                    <button href="#addUserModal" type="button" tabindex="-1" class="btn btn-primary btn-mini" data-toggle="modal"> <i class="icon-plus"></i> {MESA_UAD_ADD} </button>
                    <button id="searchUser" type="button" class="btn btn-primary btn-mini"><i class="icon-search"></i> {MESA_UAD_SEARCH} </button>
                    <button id="listUsers" type="button" class="btn btn-primary btn-mini" onClick='loadUserList()'><i class="icon-list"></i> Lijst</button>
                </div>
                <br />
                <br />
            </div>

            <div id="searchResultWell" class="well well-small hide">
                <h6><i class="icon-search"></i> {MESA_UAD_SEARCHRESULTS} </h6>

                <div id="foundUsers">
                </div>


            </div>


        </div>

        <div class="span3">

            <div id="userSearchSpace" class="hide">


            </div>



        </div>

        <div class="span3">

            <div id="userGroupSpace" class="hide">

            <div class="well well-small">
                 <h6><i class="icon-group"></i> {MESA_UAD_PARTOFGROUPS} </h6>
                 <div id="userGroups">
                 </div>
            </div>
            </div>
        </div>

         <div class="span3">
            <div class="well well-small hide">
                 <h6><i class="icon-eye-open"></i> {MESA_UAD_RECENTACTIVITY}/h6>
            </div>
        </div>

    </div>

</div>

<div id="addUserModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addUserModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addUserModalTitle">{MESA_UAD_ADDUSER} </h3>
    </div>
    <div class="modal-body" id="addUserModalBody" >
            {new_user_form}
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_UAD_CANCEL}</button>
        <button id="saveUser" class="btn btn-primary" aria-hidden="true"><i class="icon-save"></i> {MESA_UAD_SAVE}</button>
    </div>
</div>

<div id="editUserModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="editUserModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="editUserModalTitle">{MESA_UAD_EDITUSER}</h3>
    </div>
    <div class="modal-body" id="editUserModalBody" >


    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_UAD_CANCEL}</button>
        <button id="editUser"  class="btn btn-primary" aria-hidden="true"><i class="icon-save"></i> {MESA_UAD_SAVE}</button>
    </div>
</div>


<div id="fullListModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="fullListModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="fullListModalTitle">{MESA_UAD_EDITUSER}</h3>
    </div>
    <div class="modal-body" id="fullListModalBody" >
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_UAD_CANCEL}</button>
    </div>
</div>


<script>

var loadedUser = false;

$(function(){

   $('#searchUser').on('click', function(){

        var searchTerm = $('#unameSearch').val();

        $.ajax({
            type: "POST",
            data: { searchTerm: searchTerm},
            url: "{LB}/profiles/getUserTable/useredit"
        }).done(function(msg) {
            $('#foundUsers').html(msg);
            $('#searchResultWell').fadeIn();
        });

   });
});

function selectFromDialog(id){
    $('#fullListModal').modal('hide');
    loadUser(id);
}


function loadUser(id){
     $.ajax({
            type: "POST",
            url: "{LB}/userAdmin/editUser/" + id
        }).done(function(info) {
            loadedUser = id;
            //$('#searchResultWell').fadeOut();
            $('#userSearchSpace').html(info);
            $('#userSearchSpace').fadeIn();
    });

    $.ajax({
        type: "POST",
        url: "{LB}/userAdmin/loadUserGroups/" + id
    }).done(function(groups) {
        $('#userGroups').html(groups);
        $('#userGroupSpace').fadeIn();
    });
}

function saveUser(){

     $.ajax({
            type: "POST",
            data: $("#addUserForm").serialize(),
            url: "{LB}/users/add"
    }).done(function(msg) {
            $("#addUserForm").trigger('reset');
            $('#addUserModal').modal('hide');
            $('#saveConfirmationDiv').fadeIn();
    });
}

function loadEditForm(id){

    $.ajax({
        type: "POST",
        url: "{LB}/userAdmin/editForm/" + id
    }).done(function(editForm) {
        $('#editUserModalBody').html(editForm);
        $('#editUserModal').modal('show');
    });

}

function saveUserEdit(){

    $.ajax({
            type: "POST",
            data: $("#editUserForm").serialize(),
            url: "{LB}/userAdmin/saveUserEdit/" + id
    }).done(function(editForm) {
        $('#editUserModal').modal('hide');
        $('#editUserModalBody').html('');
        loadUser(loadedUser);
    });

}

function lockAccount(id, lock){

    $.ajax({
        type: "POST",
        data: {'id': id, 'lock': lock},
        url: "{LB}/userAdmin/lock"
    }).done(function(editForm) {
        loadUser(loadedUser);
    });
}

function removeAvatar(){
    $.ajax({
        type: "POST",
        url: "{LB}/profiles/removeAvatar/" + loadedUser
    }).done(function() {
        loadUser(loadedUser);
    });
}

function changePassword(id){

    var newPassword = prompt('Voer een nieuw wachtwoord in');
    console.log(newPassword)
    if(newPassword==null){
      alert('passwoord wijzigen afgebroken');
    } else {
      $.ajax({
          data: {userId: id, newPassword: newPassword},
          type: "POST",
          url: "{LB}/users/changePassword"
      }).done(function() {
          alert('Wachtwoord gewijzigd');
      });
    }
}

function loadUserList(){

  $.ajax({
      type: "POST",
      url: "{LB}/userAdmin/fullOverview"
  }).done(function(msg) {
    $('#fullListModalBody').html(msg);
    $('#fullListModal').modal('show');
  });

}

</script>
