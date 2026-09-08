<div class="span8">
    <div class="well">
        <h3> {MESA_AST_EDITTITLE} <small id="nameHeader">{name}</small> </h3>
        {notice}

        {analyticalRowListing}
    </div>

</div>

<div class="span2">

    <ul class="nav nav-list well">
        <li class="nav-header">{MESA_AST_ACTIONS} </li>

        <li><a href="#" id="editName"><i class="icon-edit"></i> {MESA_AST_EDITNAME}</a> </li>




        <li class="nav-header">{MESA_AST_FIELDS} </li>

        <li><a href="#" id="editField"><i class="icon-edit"></i> Veld bewerken </a> </li>
        <li><a href="#addRowModal" role="button"  data-toggle="modal"><i class="icon-plus"></i> {MESA_AST_ADDFIELD} </a> </li>
        <li><a href="#" id="removeFieldButton" ><i class="icon-remove"></i> {MESA_AST_REMOVEFIELD} </a> </li>
        <!-- <li><a href="#bindingModal" role="button"  data-toggle="modal"><i class="icon-magnet"></i> {MESA_AST_INSPECTBINDINGS} </a> </li> -->

        <li class="nav-header">{MESA_AST_MISC} </li>
        <li><a href="{LB}/assayTypes/listing/"><i class="icon-backward"></i> {MESA_AST_BACKTOLIST} </a> </li>


    </ul>
</div>

<div id="addRowModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addRowModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addRowModalTitle">{MESA_AST_ADDFIELD}</h3>
    </div>
    <div class="modal-body">
        {addFieldForm}
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_AST_CANCEL}</button>
        <button id="submitAddField" class="btn btn-primary">{MESA_AST_SAVE}</button>
    </div>
</div>

<div id="bindingModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="bindingModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="bindingModalTitle">{MESA_AST_FIELDBINDINGS}</h3>
    </div>
    <div class="modal-body">
        <div class="tabbable"> <!-- Only required for left/right tabs -->

            <ul class="nav nav-tabs">
              <li class="active"><a href="#activeBindings" data-toggle="tab"><i class="icon-magnet"></i> {MESA_AST_SETBINDINGS}</a></li>
              <li><a href="#addBinding" data-toggle="tab"><i class="icon-plus"></i> {MESA_AST_NEWBINDING}</a></li>
            </ul>
            <div class="tab-content">
              <div class="tab-pane active" id="activeBindings">
               {current_bindings}
              </div>
              <div class="tab-pane" id="addBinding">
                {addBindingForm}

              </div>
            </div>
          </div>

    </div>
   <!--   <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
    </div> -->
</div>

<div id="editFieldModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="editFieldModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="editFieldModalTitle">Veld bewerken</h3>
    </div>
    <div class="modal-body" id="editFieldModalBody" >




    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CANCEL}</button>
        <button class="btn btn-primary" id="submitEditField"  aria-hidden="true"><i class="icon-save"></i> Opslaan</button>
    </div>
</div>


<script>

    var editingField = false;

    $(function() {


        $('#editField').click(function(){

            var selectedField = $('input[name=selectedField]:checked').val();
            if(selectedField === undefined){
                alert('{MESA_AST_SELECTFIELDFIRST}');
            } else{
              editingField = selectedField;
                $.ajax({
                    type: "GET",
                    url: "{LB}/assayTypeFields/edit/" + selectedField
                }).done(function(msg) {
                    $('#editFieldModalBody').html(msg);
                    $('#editFieldModal').modal('show');
                });

            }
    });



        $('#editName').click(function(){

           bootbox.prompt("<h3>{MESA_AST_EDITNAMETITLE}</h3> <p>{MESA_AST_EDITNAMEDESC}</p>", function(result) {

                if(result != '' && result != false){
                    $.ajax({
                        type: "POST",
                        data: { name: result , id: '{id}'} ,
                        url: "{LB}/assayTypes/changeName"
                    }).done(function(msg) {
                            $('#nameHeader').html(result);
                            $('#nameHeader').highLight();
                    });

                }
             });
        });

        $('#removeFieldButton').click(function(){

            var selectedField = $('input[name=selectedField]:checked').val();
            if(selectedField === undefined){
                alert('{MESA_AST_SELECTFIELDFIRST}');
            }
            else{
                bootbox.confirm("<h3> {MESA_AST_DELETEFIELD} </h3>{MESA_AST_DELETEFIELDDESC} " , function(result) {

                    if(result === true){
                     $.ajax({
                        type: "GET",
                        url: "{LB}/assayTypeFields/removeField/" + selectedField
                    }).done(function(msg) {
                        updateRows();
                    });
                    }
                });
            }
        });
    });

    // field addition

    function submitField() {

        $('#addFieldForm').hide();

        $.ajax({
            type: "POST",
            data: $("#addFieldForm").serialize(),
            url: "{LB}/assayTypeFields/addField/{id}"
        }).done(function(msg) {
            $('#addRowModal').modal('hide');
            $('#addFieldForm').trigger('reset');
            $('#addFieldForm').show();
            updateRows();
        });
    }

    function updateRows() {
        $.ajax({
            type: "GET",
            url: "{LB}/assayTypeFields/printFieldsTable/{id}"
        }).done(function(msg) {
            $('#analyticalRowListing').replaceWith(msg);
        });
    }

    function saveFieldEdit(){
      $.ajax({
          type: "POST",
          data: $("#editFieldForm").serialize(),
          url: "{LB}/assayTypeFields/save/" + editingField
      }).done(function(msg) {
        $('#editFieldModalBody').html('');
        $('#editFieldModal').modal('hide');
      });
    }




</script>
