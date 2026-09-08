<div class="span8">
    <div class="well">
        <h3> Matrices </h3>
            {matrix_table}
    </div>
</div>

<div class="span2">

        <ul class="nav nav-list well">
            <li class="nav-header">Matrix Actions </li>

            <li><a href="#addMatrixModal" role="button"  data-toggle="modal" id="addMatrix"><i class="icon-plus"></i> Toevoegen </a> </li>
            <li><a id="editClick"><i class="icon-edit"></i> Bewerken </a> </li>
            <li><a id="removeClick"><i class="icon-remove"></i> Verwijderen </a> </li>

        </ul>

</div>

<div id="addMatrixModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addMatrixModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addMatrixModalTitle">Analyse matrix toevoegen</h3>
    </div>
    <div class="modal-body" id="addMatrixModalBody" >
        {matrixForm}
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_ERP_CANCEL}</button>
        <button class="btn btn-primary" id="addMatrixSubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_ERP_SAVE}</button>
    </div>
</div>

<script>


$(function(){

    $('#editClick').click(function(){
       var selectedAnalysis = $('input[name=selectedAssay]:checked').val();
       if(selectedAnalysis !== undefined){
           window.location.href = '{LB}/matrix/edit/' + selectedAnalysis;
       } else {
           alert('Selecteer een matrix.');
       }

    });

    $('#removeClick').click(function(){
      var selectedAnalysis = $('input[name=selectedAssay]:checked').val();
      if(selectedAnalysis !== undefined){

      bootbox.confirm("<h3>Matrix verwijderen?</h3> <p>Weet u zeker dat u deze matrix wilt verwijderen?</p>", function(result) {
                if(result == true){
                     window.location.href = '{LB}/matrix/destroy/' + selectedAnalysis;
                }
        });

      } else {
          alert('{MESA_ASE_SELECTFIRST}');
      }
    });

});
</script>
