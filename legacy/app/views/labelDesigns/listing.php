<div class="span8">
    <div class="well">
        <h3> {MESA_LBD_LISTING} </h3>
       {label_table}
    </div>
</div>


<div class="span2">
    <ul class="nav nav-list well">
        <li class="nav-header">{MESA_LBD_ACTIONS} </li>
        <li><a href="#" id="addLabel"><i class="icon-plus"></i> {MESA_LBD_ADD}</a> </li>
        <li><a href="#" id="editLabel"><i class="icon-edit"></i> {MESA_LBD_EDIT} </a> </li>
        <li><a href="#" id="removeLabel"><i class="icon-remove"></i> {MESA_LBD_REMOVE} </a> </li>

        <li class="nav-header">{MESA_LBD_PRINTACTIONS} </li>
        <li><a href="#" id="printLabel"><i class="icon-print"></i> {MESA_LBD_PRINT} </a> </li>
    </ul>
</div>



<div id="addLabelModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addLabelModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addLabelModalTitle">{MESA_LBD_ADD}</h3>
    </div>
    <div class="modal-body" id="addLabelModalBody" >
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_LBD_CANCEL}</button>
        <button class="btn btn-primary" id="addLabelSubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_LBD_SAVE} </button>
    </div>
</div>


<script>

$(function(){

    $('#addLabel').on('click', function(){
        addLabel();
    });

    $('#editLabel').on('click', function(){

       var selectedLabelDesign = $('input[name=selectedLabelDesign]:checked').val();
       if(selectedLabelDesign !== undefined){
          editLabel(selectedLabelDesign);
       } else {
           alert('{MESA_LBD_SELECTFIRST}');
       }
    });


    $('#removeLabel').on('click', function(){
        var selectedLabelDesign = $('input[name=selectedLabelDesign]:checked').val();
       if(selectedLabelDesign !== undefined){
          removeLabel(selectedLabelDesign);
       } else {
           alert('{MESA_LBD_SELECTFIRST}');
       }
    });

    $('#printLabel').on('click', function(){
        var selectedLabelDesign = $('input[name=selectedLabelDesign]:checked').val();
       if(selectedLabelDesign !== undefined){
              window.location = "{LB}/labelDesigns/testLabel/" + selectedLabelDesign;
       } else {
           alert('{MESA_LBD_SELECTFIRST}');
       }
    });

});




function addLabel(){

    $.ajax({
        type: "POST",
        url: "{LB}/labelDesigns/labelForm/NULL"
    }).done(function(response) {
        $('#addLabelModalBody').html(response);
        $('#addLabelModal').modal('show');
    });
}

function editLabel(labelId){

    $.ajax({
        type: "POST",
        url: "{LB}/labelDesigns/labelForm/" + labelId
    }).done(function(response) {
        $('#addLabelModalBody').html(response);
        $('#addLabelModal').modal('show');
    });
}


function removeLabel(labelId){

    bootbox.confirm("<h3>{MESA_LBD_DELETELABEL}</h3> <p>{MESA_LBD_DELETELABELMSG}</p>", function(result) {
        if(result == true){
                window.location = "{LB}/labelDesigns/removeLabel/" + labelId;
        }
   });


}


</script>
