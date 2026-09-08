<div class="span8">
    <div class="well">
        <h3> Media &amp; Bevestigingen  &amp; Materiaal</h3>
        {media_table}
    </div>
</div>


<div class="span2">
    <ul class="nav nav-list well follow-scroll">
        <li class="nav-header"> {MESA_ASE_ACTIONS} </li>
        <li><a href="#addMediaModal" role="button"  data-toggle="modal" id="addMedia"><i class="icon-plus"></i> Media toevoegen</a> </li>
        <li><a href="#addMatModal" role="button"  data-toggle="modal" id="addMat"><i class="icon-plus"></i> Materiaal toevoegen</a> </li>
        <li><a href="#" id="editClick"><i class="icon-edit"></i> Bewerken </a> </li>
        <li><a href="#" id="removeClick"><i class="icon-remove"></i> Verwijderen</a> </li>
    </ul>
</div>

<div id="addMediaModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addMediaModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addMediaModalTitle">Media toevoegen</h3>
    </div>
    <div class="modal-body" id="addMediaModalBody" >

        {addMediaForm}

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_ASE_CANCEL}</button>
        <button class="btn btn-primary" id="addMediaModalSubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_ASE_SAVE} </button>
    </div>
</div>


<div id="addMatModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addMatModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addMatModalTitle">Materiaal toevoegen</h3>
    </div>
    <div class="modal-body" id="addMatModalBody" >

        {addMatForm}

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_ASE_CANCEL}</button>
        <button class="btn btn-primary" id="addMatModalSubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_ASE_SAVE} </button>
    </div>
</div>


<script>
    $(function(){

        $('#mediaTable').DataTable({
            "paging":   false,
            "info":     false,
            "order": [[ 2, "asc" ]]
        });

        $('#editClick').click(function(){
            var selectedMedia = $('input[name=selectedMedia]:checked').val();
            if(selectedMedia !== undefined){
                window.location.href = '{LB}/media/edit/' + selectedMedia;
            } else {
                alert('{MESA_ASE_SELECTFIRST}');
            }
        });

        $('#removeClick').click(function(){
            var selectedMedia = $('input[name=selectedMedia]:checked').val();
            if(selectedMedia !== undefined){

              bootbox.confirm("<h3> Media verwijderen? </h3>" , function(result) {

                  if(result === true){
                    $.ajax({
                        type: "POST",
                        url: "{LB}/media/remove/" + selectedMedia
                    }).done(function(confwindow) {
                        $('#tr_' + selectedMedia).remove();
                    });
                  }

                });


            } else {
                alert('{MESA_ASE_SELECTFIRST}');
            }
        });

    });
</script>
