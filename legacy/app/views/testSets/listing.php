<div class="span8">

    <div class="well">
        <h3> Test sets </h3>

        <div class="alert alert-block ">
            <h6 style="color: black;">Opgelet</h6>
            <strong>Data</strong> Deze test-functie kan automatisch monsters aanmelden, resultaten invoeren, en projecten autoriseren. Deze informatie komt in dezelfde database te staan als de werkelijke productie data. 
            Indien dit niet wenselijk is, kan deze functie eventueel in schaduw-lims uitgevoerd worden, zodat test-monsters de werkelijke productie data niet vervuilen. <strong>Stickers</strong> De test-functie meld monsters aan 
            via hetzelfde kanaal als wanneer een gebruiker dit doet, d.w.z. dat ook stickers worden afgedrukt op de plekken waar dit normaal ook gebeurt. Indien dit niet wenselijk is,kan men tijdens het draaien van test-sets er voor kiezen
            om de 'sticker print' uit te schakelen, let wel op: deze wijziging is systeem-wijd (als andere gebruikers dus bezig zijn met andere activiteiten, worden deze stickers ook niet geprint, mocht dit op hetzelfde moment zijn wanneer een set draait.).  


        </div>

        {test_table}
    </div>
</div>




<div class="span2">
    <ul class="nav nav-list well follow-scroll">
        <li class="nav-header"> Testset </li>
        <li><a href="#addAssayModal" role="button"  data-toggle="modal" id="addAssay"><i class="icon-plus"></i> Aanmaken </a> </li>
        <li><a href="#" id="editClick"><i class="icon-edit"></i> Bewerken </a> </li>
        <li><a href="#" id="removeClick"><i class="icon-remove"></i> Verwijderen </a> </li>        
    </ul>
</div>

<div id="addAssayModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addAssayModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addAssayModalTitle">Test Set toevoegen </h3>
    </div>
    <div class="modal-body" id="addAssayModalBody" >

        {addTestForm}

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_ASE_CANCEL}</button>
        <button class="btn btn-primary" id="addTestSubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_ASE_SAVE} </button>
    </div>
</div>


<script>
$(function(){


    $('#editClick').click(function(){
       var selectedAssay = $('input[name=selectedTest]:checked').val();
       if(selectedAssay !== undefined){
           window.location.href = '{LB}/testSets/edit/' + selectedAssay;
       } else {
           alert('Selecteer eerst een test set');
       }
    });

    $('#removeClick').click(function(){
       var selectedAssay = $('input[name=selectedTest]:checked').val();
       if(selectedAssay !== undefined){

       bootbox.confirm("<h3>Testset verwijderen?</h3> <p>Weet u zeker dat u deze test wilt verwijderen </p>", function(result) {
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
      url: "{LB}/testSets/destroy/" + selectedAssay
  }).done(function(confwindow) {
      $('#tr_' + selectedAssay).remove();
  });
}

</script>
