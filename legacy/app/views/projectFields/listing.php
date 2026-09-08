<div class="span8">
    <div class="well">
        <h3> {MESA_PFD_LISTINGTITLE}</h3>
        {field_table}
    </div>
</div>


<div class="span2">
    <ul class="nav nav-list well">
        <li class="nav-header">{MESA_PFD_FIELDS} </li>
        <li><a href="#addFieldModal" role="button"  data-toggle="modal" id="addField"><i class="icon-plus"></i> {MESA_PFD_ADDFIELD}</a> </li>
        <li><a href="#" id="editField"><i class="icon-edit"></i> {MESA_PFD_EDITFIELD} </a> </li>
        <!-- <li><a href="#" id="removeField"><i class="icon-remove"></i> {MESA_PFD_REMOVEFIELD} </a> </li>--> 

    </ul>
</div>


<div id="addFieldModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addFieldModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addFieldModalTitle">{MESA_PFD_ADDFIELDTITLE} </h3>
    </div>
    <div class="modal-body" id="addFieldModalBody" >

        {addFieldForm}

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_PFD_ADDFIELDCANCEL}</button>
        <button class="btn btn-primary" id="addFieldSubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_PFD_ADDFIELDSAVE} </button>
    </div>
</div>


<script>

//editField

$(document).ready(function() {
    $('#projectFieldTable').DataTable({
        "paging":   false,
        "info":     false,
        "order": [[ 3, "asc" ]]
    });
} );


$('#removeField').click(function(){
       var selectedField = $('input[name=selectedField]:checked').val();
       if(selectedField !== undefined){
           bootbox.confirm("<h3>{MESA_PFD_DELETEFIELDTITLE}</h3> <p>{MESA_PFD_DELETEFIELDBODY}</p>", function(result) {
                    if(result == true){
                        $.ajax({
                            type: "POST",
                            data:  { field_id: selectedField },
                            url: "{LB}/projectFields/removeField/"
                        }).done(function(msg) {
                            $('#projectfield_' + selectedField).remove();
                            $('#projectFieldTable').highLight();
                        });
                    }
                });
       } else {
           alert('{MESA_PFD_DELETEEDITSELECTFIRST}');
       }
});


$('#editField').click(function(){

    var selectedField = $('input[name=selectedField]:checked').val();
       if(selectedField !== undefined){
           window.location.href = '{LB}/projectFields/edit/' + selectedField;
        }
       else {
           alert('{MESA_PFD_DELETEEDITSELECTFIRST}');
       }
});

</script>
