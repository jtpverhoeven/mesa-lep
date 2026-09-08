<div class="span8">
    <div class="well">
        <h3> Werklijsten </h3>
        {worklists_table}
    </div>
</div>



<div class="span2">
    <ul class="nav nav-list well">
        <li class="nav-header"> Werklijst acties </li>
        <li><a href="#addWorklistModal" role="button"  data-toggle="modal" id="addAssay"><i class="icon-plus"></i> Toevoegen</a> </li>
        <li><a href="#" id="editClick"><i class="icon-edit"></i> Bewerken</a> </li>
        <li><a href="#" id="removeClick"><i class="icon-remove"></i> Verwijderen</a> </li>
    </ul>
</div>


<div id="addWorklistModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addWorklistModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addWorklistModalTitle">Werklijst toevoegen</h3>
    </div>
    <div class="modal-body" id="addWorklistModalBody" >

        {addworklistform}

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_ASE_CANCEL}</button>
        <button class="btn btn-primary" id="addWorklistSubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_ASE_SAVE} </button>
    </div>
</div>

<script>

$(function() {



    $('#editClick').click(function () {
        var selectedWorklist = $('input[name=selectedWorklist]:checked').val();
        if (selectedWorklist !== undefined) {
            window.location.href = '{LB}/workLists/edit/' + selectedWorklist;
        } else {
            alert('Geen werklijst geselecteerd');
        }
    });

    $('#removeClick').click(function(){
        var selectedWorklist = $('input[name=selectedWorklist]:checked').val();

        if (selectedWorklist !== undefined) {
            bootbox.confirm("<h3>Werklijst sjabloon verwijderen?</h3> <p>Weet u zeker dat u het sjabloon voor deze werklijst wilt verwijderen?</p>", function(result) {
                 if(result == true){
                    authPopup('confirmationTables', 'remove', worklistRemove, Array(selectedWorklist) );
                   }
        });
       
        } else {
           alert('Geen werklijst geseleteerd');
       }

    });
    

});

function worklistRemove(selectedWorklist){
      $.ajax({
        type: "POST",
        url: "{LB}/workLists/remove/" + selectedWorklist
    }).done(function () {
        $('#tr_' + selectedWorklist).remove();
    });
}

</script>