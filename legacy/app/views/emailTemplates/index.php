<div class="span8">
    <div class="well">
        <h3>  Uitgaande emails </h3>
       {email_table}
    </div>
</div>


<div class="span2">
    <ul class="nav nav-list well">
        <li class="nav-header">{MESA_LBD_ACTIONS} </li>
        <li><a href="{LB}/emailTemplates/edit" id="addMail"><i class="icon-plus"></i> Email toevoegen </a> </li>
        <li><a href="#" id="editEmail"><i class="icon-edit"></i> {MESA_LBD_EDIT} </a> </li>
        <li><a href="#" id="removeEmail"><i class="icon-remove"></i> {MESA_LBD_REMOVE} </a> </li>
        <li><a href="#" id="setDefault"><i class="icon-check"></i> Standaard email </a> </li>
        
    </ul>
</div>



<div id="defaultMailModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="defaultMailModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="defaultMailModalTitle">Instellen als standaard email </h3>
    </div>
    <div class="modal-body" id="metadataModalBody" >

    
       <div class="control-group" id="metadataNameCG" style="display:block;">
           <label class="control-label" for="metadataName">Standaard voor</label>
            <div class="control">
                <select class="input-block-level" id="setDefaultFor">
                    <option value="reports">Rapportages</option>
                    <option value="reports_with_files">Rapportages met bestanden</option>
                    <option value="for_files">Bestand emails</option>
                </select>
           </div>
       </div>       

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CANCEL}</button>
        <button class="btn btn-primary" aria-hidden="true" onclick="saveDefault();"><i class="icon-save"></i>Opslaan</button>
    </div>
</div>


<script>

$(function(){

    

    $('#editEmail').on('click', function(){

       var selectedEmail = $('input[name=selectedEmail]:checked').val();
       if(selectedEmail !== undefined){
        window.location = "{LB}/emailTemplates/edit/" + selectedEmail;
       } else {
           alert('{MESA_LBD_SELECTFIRST}');
       }
    });


    $('#removeEmail').on('click', function(){
        var selectedEmail = $('input[name=selectedEmail]:checked').val();
        if(selectedEmail !== undefined){
                removeEmail(selectedEmail);
        } else {
            alert('Geen email geselecteerd');
        }
    });

    $('#setDefault').on('click', function(){

        var selectedEmail = $('input[name=selectedEmail]:checked').val();



        var selectedEmail = $('input[name=selectedEmail]:checked').val();
        if(selectedEmail !== undefined){
            $('#defaultMailModal').modal('show');
            //window.location = "{LB}/emailTemplates/setdefault/" + selectedEmail;
       } else {
           alert('Geen email geselecteerd');
       }
    });

   

});


function saveDefault(){
    
    var selectedEmail = $('input[name=selectedEmail]:checked').val();
    
    window.location = "{LB}/emailTemplates/setdefault/" + selectedEmail + "/"  + $('#setDefaultFor').val();

    
}

function removeEmail(selectedEmail){

    bootbox.confirm("<h3>Email template verwijderen?</h3> <p>Weet u het zeker?</p>", function(result) {
        if(result == true){
            window.location = "{LB}/emailTemplates/destroy/" + selectedEmail;
        }
   });

}


</script>
