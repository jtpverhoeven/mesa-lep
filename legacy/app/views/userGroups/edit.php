<div class="span8">   
    <div class="well">        
        <h3>{MESA_UGA_EDITTITLE} <small>{groupName}</small></h3>
        
        <h4> {MESA_UGA_GROUPLEADER} </h4>
        
       {group_leader}
        
        
        <h4> {MESA_UGA_GROUPUSERS} </h4>
        
        <table width="100%" class="table">
            
            <tbody>
                {group_users}
            </tbody>
            
        </table>
       
        <div class="pull-right">            
            <button href="#addUserModal" class="btn btn-mini btn-primary"  role="button" data-toggle="modal"><i class="icon icon-user"></i> {MESA_UGA_ADDTOGROUP} </button>
        </div>
        
        <br /><br />
        
        <h4> {MESA_UGA_PRIVILEGES} </h4>
        
          {privileges_table}

    </div>    
</div>

<div class="span2">
    <ul class="nav nav-list well">                                     
        <li class="nav-header">{MESA_UGA_ACTIONS} </li>
        <li><a href="{LB}/userGroups/listing"><i class="icon-backward"></i> {MESA_UGA_BACKTOLIST} </a> </li>             
    </ul>         
</div>



<div id="addUserModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addUserModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addUserModalTitle">{MESA_UGA_ADDTOGROUP}</h3>
    </div>
    <div class="modal-body" id="addUserModalBody" >   
        
        <div class="input-prepend input-append input-block-level">
                        <span class="add-on">{MESA_UGA_USERNAME}</span>
                        <input id="unameSearch" name="unameSearch" type="text" class="input-block-level" placeholder="{MESA_UGA_USERNAME}" value="">
                        <span class="add-on"><a  id="searchUser" > {MESA_UGA_SEARCH}</a></span>
        </div>
        
        <div id="foundUsers">
            
        </div>
        
    </div>
    <div class="modal-footer">        
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_UGA_CANCEL}</button>                
    </div>
</div>

<script>

var selectingLeader = false;

$(function(){
   
   $('#apointLeader').on('click', function(){      
       selectingLeader = true;
        $('#addUserModal').modal('show');
   });
   
   $('#searchUser').on('click', function(){
        
        var searchTerm = $('#unameSearch').val();

        $.ajax({
            type: "POST",
            data: { searchTerm: searchTerm},
            url: "{LB}/profiles/getUserTable"
        }).done(function(msg) {       
            $('#foundUsers').html(msg);
        });
                       
   });  
   
   $('#addUserModal').on('hidden', function(){
        selectingLeader = false;
   });
   
   $('#privTable').on('change', 'select', function(){
        var privId = $(this).attr('ruleId');
        var allowed = $(this).val(); 
        changePriv(privId, allowed);
   });
   
   
});

function addUserToGroup(id){                
   
   if(selectingLeader == false){
       $.ajax({
        type: "POST",
        data: { userAdd: id, addToGroup: '{id}'},
        url: "{LB}/groupUsers/addToGroup"
        }).done(function(msg) {                   
            location.reload();
        });
   } else{   
        promote(id);
   }        
}



function removeFromGroup(id){
        
        $.ajax({
            type: "POST",
            data: { userAdd: id, addToGroup: '{id}'},
            url: "{LB}/groupUsers/removeFromGroup"
        }).done(function(msg) {                   
            location.reload();
        });
}


function updateRule(){

        $.ajax({
            type: "POST",
            data: { userAdd: id, addToGroup: '{id}'},
            url: "{LB}/groupUsers/addToGroup"
        }).done(function(msg) {                   
            location.reload();
        });
}

function demote(){

        $.ajax({
            type: "POST",            
            url: "{LB}/userGroups/demoteLeader/{id}"
        }).done(function(msg) {                   
            location.reload();
        });

}

function promote(selectedUser){
    $.ajax({
        type: "POST",            
        url: "{LB}/userGroups/promoteLeader/{id}/" + selectedUser
    }).done(function(msg) {                   
        location.reload();
    });
}

function changePriv(privId, allowed){
        
    $.ajax({
        type: "POST",            
        data: { group: "{id}", privilege: privId, allowed: allowed },
        url: "{LB}/groupPrivileges/changePrivilege"
    }).done(function(msg) {                           
    });



}
    
</script>