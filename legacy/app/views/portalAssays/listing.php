<div class="span8">
    <div class="well">
        <h3> Algemene portal analyses </h3>
        {portal_assay_table}
    </div>
</div>

<div class="span2">
    <ul class="nav nav-list well follow-scroll">
        <li class="nav-header"> {MESA_ASE_ACTIONS} </li>
        <li><a href="#addAssayModal" role="button"  data-toggle="modal" id="addAssay"><i class="icon-plus"></i> Toevoegen</a> </li>
        <li><a href="#" id="editClick"><i class="icon-edit"></i> {MESA_ASE_EDIT}</a> </li>
        <li><a href="#" id="removeClick"><i class="icon-remove"></i> Verbergen </a> </li>
        <li><a href="#" id="availabilityClick" role="button" ><i class="icon-building"></i> Klant beschikbaarheid</a> </li>

        <li><a href="/portalAssays/bulkClientCopy"  role="button" ><i class="icon-random"></i> Bulk wijzigen beschikbaarheid</a> </li>

    </ul>
</div>

<div id="addAssayModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addAssayModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addAssayModalTitle">Portal analyse toevoegen</h3>
    </div>
    <div class="modal-body" id="addAssayModalBody" >
        {addAssayForm} 
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_ASE_CANCEL}</button>
        <button class="btn btn-primary" id="addAssaySubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_ASE_SAVE} </button>
    </div>
</div>

<script>
$(function(){

    $('#assaysTable').DataTable({
        "paging":   false,
        "info":     false,
        "order": [[ 2, "asc" ]]
    });

    $('#editClick').click(function(){
       var selectedAssay = $('input[name=selectedAssay]:checked').val();
       if(selectedAssay !== undefined){
           window.location.href = '{LB}/portalAssays/edit/' + selectedAssay;
       } else {
           alert('{MESA_ASE_SELECTFIRST}');
       }
    });

    $('#availabilityClick').click(function(){

        var selectedAssay = $('input[name=selectedAssay]:checked').val();
       if(selectedAssay !== undefined){
           window.location.href = '{LB}/portalAssays/clientAvailability/' + selectedAssay;
       } else {
           alert('{MESA_ASE_SELECTFIRST}');
       }

    });

    $('#removeClick').click(function(){
       var selectedAssay = $('input[name=selectedAssay]:checked').val();
       if(selectedAssay !== undefined){

       bootbox.confirm("<h3>Portal analyse verwijderen?</h3> <p>Weet u zeker dat u deze analyse wilt verwijderen (inactiveren)?</p>", function(result) {
                 if(result == true){
                    authPopup('portalAssays', 'remove', removeAssayFunc, Array(selectedAssay) );
         	       }
         });

       } else {
           alert('{MESA_ASE_SELECTFIRST}');
       }
    });
});

function removeAssayFunc(selectedAssay){
  $.ajax({
      type: "POST",
      url: "{LB}/portalAssays/remove/" + selectedAssay
  }).done(function(confwindow) {
      $('#tr_' + selectedAssay).remove();
  });
}

</script>
