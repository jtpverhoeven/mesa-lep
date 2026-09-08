<div class="span8">
    <div class="well">
        <h3> {MESA_ASE_LISTING} </h3>
        {assay_table}
    </div>
</div>


<div class="span2">
    <ul class="nav nav-list well follow-scroll">
        <li class="nav-header"> {MESA_ASE_ACTIONS} </li>
        <li><a href="#addAssayModal" role="button"  data-toggle="modal" id="addAssay"><i class="icon-plus"></i> {MESA_ASE_ADD}</a> </li>
        <li><a href="#" id="editClick"><i class="icon-edit"></i> {MESA_ASE_EDIT}</a> </li>
        <li><a href="#" id="removeClick"><i class="icon-remove"></i> {MESA_ASE_REMOVE}</a> </li>
        <li class="nav-header"> Rapportages </li>
        <li><a href="{LB}/docgenTemplates/listing" id=""><i class="icon-print"></i> Rapport associaties</a> </li>
    </ul>
</div>

<div id="addAssayModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addAssayModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addAssayModalTitle">{MESA_ASE_ADD}</h3>
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
        "ordering": true
    });

    $('#editClick').click(function(){
       var selectedAssay = $('input[name=selectedAssay]:checked').val();
       if(selectedAssay !== undefined){
           window.location.href = '{LB}/assays/edit/' + selectedAssay;
       } else {
           alert('{MESA_ASE_SELECTFIRST}');
       }
    });

    $('#removeClick').click(function(){
       var selectedAssay = $('input[name=selectedAssay]:checked').val();
       if(selectedAssay !== undefined){

       bootbox.confirm("<h3>Analyse verwijderen?</h3> <p>Weet u zeker dat u deze analyse wilt verwijderen (inactiveren)?</p>", function(result) {
                 if(result == true){
                    authPopup('assays', 'remove', removeAssayFunc, Array(selectedAssay) );
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
      url: "{LB}/assays/remove/" + selectedAssay
  }).done(function(confwindow) {
      $('#tr_' + selectedAssay).remove();
  });
}

</script>
