<div class="span8">   
    <div class="well">        
        <h3>{MESA_UGA_LISTING}</h3>
        {user_groups}

    </div>    
</div>

<div class="span2">
    <ul class="nav nav-list well">                                     
        <li class="nav-header">{MESA_UGA_ACTIONS} </li>
        <li><a href="#addGroupModal" id="add" role="button"  data-toggle="modal"><i class="icon-plus"></i> {MESA_UGA_ADD} </a> </li>        
        <li><a href="#" id="editClick" ><i class="icon-edit"></i> {MESA_UGA_EDIT} </a> </li>        
        <li><a href="#" id="removeClick" ><i class="icon-remove"></i> {MESA_UGA_REMOVE} </a> </li>                     
    </ul>         
</div>


<div id="addGroupModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addGroupModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addGroupModalTitle">{MESA_UGA_ADDTITLE}</h3>
    </div>
    <div class="modal-body" id="addGroupModalBody" >   
        
        {addGroupForm}
        
    </div>
    <div class="modal-footer">        
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_UGA_CANCEL}</button>        
        <button class="btn btn-primary" id="addGroupSubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_UGA_SAVE}</button>        
    </div>
</div>

<script>
$(function(){


    $('#userGroupsTable').DataTable({
        "paging":   false,
        "info":     false,
        "order": [[ 2, "asc" ]]
    });

    $('#editClick').click(function(){       
       var selectedGroup = $('input[name=selectedGroup]:checked').val();       
       if(selectedGroup !== undefined){
           window.location.href = '{LB}/userGroups/edit/' + selectedGroup;                       
       } else {
           alert('{MESA_UGA_SELECTFIRST}');
       }             
    });  

    $('#removeClick').click(function(){ 
                
     var selectedGroup = $('input[name=selectedGroup]:checked').val();      
     console.log(selectedGroup);
       
       if(selectedGroup !== undefined){     
            bootbox.confirm("<h3>{MESA_UGA_REMOVEUSERGROUP}</h3> <p>{MESA_UGA_REMOVEUSERGROUPEXP}</p>", function(result) {             
            if(result == true){
                authPopup('userGroups', 'removeGroup', removeGroup, Array(selectedGroup) );
            }
        });
        } else {
           alert('{MESA_UGA_SELECTFIRST}');
       }
             
    });
});

function removeGroup(selectedGroup){     
    
    $.ajax({
        type: "POST",                                                
        url: "{LB}/userGroups/removeGroup/" + selectedGroup
    }).done(function(msg) {                         
        $('#tr_' + selectedGroup).remove();
    });  
}

</script>