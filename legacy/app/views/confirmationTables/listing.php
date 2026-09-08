<div class="span8">
    <div class="well">
        <h3> Bevestigings tabellen </h3>
        {tables}
    </div>
</div>


<div class="span2">
    <ul class="nav nav-list well follow-scroll">
        <li class="nav-header"> {MESA_ASE_ACTIONS} </li>
        <li><a href="#addTableModal" role="button"  data-toggle="modal" id="addAssay"><i class="icon-plus"></i> Toevoegen </a> </li>
        <li><a href="#" id="editClick"><i class="icon-edit"></i> {MESA_ASE_EDIT}</a> </li>
        <li><a href="#" id="removeClick"><i class="icon-remove"></i> {MESA_ASE_REMOVE}</a> </li>
    </ul>
</div>


<div id="addTableModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addTableModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addTableModalTitle">{MESA_ASE_ADD}</h3>
    </div>
    <div class="modal-body" id="addTableModalBody" >

        {addTableForm}

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_ASE_CANCEL}</button>
        <button class="btn btn-primary" id="addTableSubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_ASE_SAVE} </button>
    </div>
</div>

<script>


$(function(){

    $('#confListTable').DataTable({
        "paging":   false,
        "info":     false,
        "order": [[ 2, "asc" ]]
    });

    $('#editClick').click(function(){
       var selectedAssay = $('input[name=selectedList]:checked').val();
       if(selectedAssay !== undefined){
           window.location.href = '{LB}/confirmationTables/edit/' + selectedAssay;
       } else {
           alert('Geen tabel geselecteerd');
       }
    });

    $('#removeClick').click(function(){
       var selectedAssay = $('input[name=selectedList]:checked').val();
       if(selectedAssay !== undefined){

       bootbox.confirm("<h3>Bevestigings tabel verwijderen?</h3> <p>Weet u zeker dat u deze analyse wilt verwijderen?</p>", function(result) {
                 if(result == true){
                    authPopup('confirmationTables', 'remove', removeAssayFunc, Array(selectedAssay) );
         	       }
         });
       } else {
           alert('Geen tabel geseleteerd');
       }
    });

});


function removeAssayFunc(selectedAssay){
  $.ajax({
      type: "POST",
      url: "{LB}/confirmationTables/remove/" + selectedAssay
  }).done(function(confwindow) {
      $('#tr_' + selectedAssay).remove();
  });
}



</script>
