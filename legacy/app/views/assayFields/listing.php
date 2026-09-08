<div class="span8">   
    <div class="well">        
        <h3> {MESA_GAF_LISTING}</h3>
        {field_table}
    </div>    
</div> 


<div class="span2">
    <ul class="nav nav-list well">                                     
        <li class="nav-header">{MESA_GAF_FIELDS} </li>
        <li><a href="#addFieldModal" role="button"  data-toggle="modal" id="addField"><i class="icon-plus"></i> {MESA_GAF_ADDFIELD}</a> </li>
        <li><a href="#" id="editField"><i class="icon-edit"></i> {MESA_GAF_EDIT} </a> </li>
        <li><a href="#" id="removeField"><i class="icon-remove"></i> {MESA_GAF_DELETE} </a> </li>
                
    </ul>         
</div>


<div id="addFieldModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addFieldModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addFieldModalTitle">{MESA_GAF_ADDGLOBALFIELD}</h3>
    </div>
    <div class="modal-body" id="addFieldModalBody" >           
        {addFieldForm}       
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_GAF_CANCEL}</button>        
        <button class="btn btn-primary" id="addFieldSubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_GAF_SAVE}</button>        
    </div>
</div>


<script>

//editField

$(document).ready(function() {
    $('#assayFieldTable').DataTable({
        "paging":   false,
        "info":     false,
        "order": [[ 2, "asc" ]]
    });
} );

$('#removeField').click(function(){       
       var selectedField = $('input[name=selectedField]:checked').val();       
       if(selectedField !== undefined){
           bootbox.confirm("<h3>{MESA_GAF_REMFIELDTITLE}</h3> <p>{MESA_GAF_REMFIELDMSG}</p>", function(result) {             
                    if(result == true){
                        $.ajax({
                            type: "POST",            
                            data:  { field_id: selectedField },
                            url: "{LB}/assayFields/removeField/"
                        }).done(function(msg) {
                            $('#assayfield_' + selectedField).remove();
                            $('#assayFieldTable').highLight();
                        });
                    }
                });                       
       } else {
           alert('{MESA_GAF_SELECTFIELDFIRST}');
       }
       
});


$('#editField').click(function(){

    var selectedField = $('input[name=selectedField]:checked').val();       
       if(selectedField !== undefined){
           window.location.href = '{LB}/assayFields/edit/' + selectedField;
       }      
       else {
           alert('{MESA_GAF_SELECTFIELDFIRST}');
       }
});

</script>