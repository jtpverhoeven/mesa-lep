<div class="span8">
    <div class="well">
        <h3> Print profielen </h3>
        {table}
    </div>
</div>


<div class="span2">
    <ul class="nav nav-list well">
        <li class="nav-header">PrintProfiel opties </li>
        <li><a href="#" id="addProfile"><i class="icon-plus"></i> Toevoegen</a> </li>
        <li><a href="#" id="editProfile"><i class="icon-edit"></i> Bewerken </a> </li>
        <li><a href="#" id="removeProfile"><i class="icon-remove"></i> Verwijderen </a> </li>

        <li class="nav-header">PrintProfiel opties </li>
        <li><a href="#" id="testProfile"><i class="icon-gears"></i> Test </a> </li>

    </ul>
</div>



<div id="addProfileModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addProfileModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addProfileModalTitle">Print profiel toevoegen</h3>
    </div>
    <div class="modal-body" id="addProfileModalBody" >
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"> Annuleren</button>
        <button class="btn btn-primary" id="addProfileSubmit"  aria-hidden="true"><i class="icon-save"></i> Opslaan </button>
    </div>
</div>


<script>

    $(function(){

        $('#addProfile').on('click', function(){
            addProfile();
        });

        $('#testProfile').on('click', function(){
            var selectedProfile = $('input[name=selectedProfile]:checked').val();
            if(selectedProfile !== undefined){
                window.location = "{LB}/printing/test/" + selectedProfile;
            } else {
                alert('Selecteer eerst een profiel');
            }
        });

        $('#editProfile').on('click', function(){
            var selectedProfile = $('input[name=selectedProfile]:checked').val();
            if(selectedProfile !== undefined){
                editProfile(selectedProfile);
            } else {
                alert('Selecteer eerst een profiel');
            }
        });

        $('#removeProfile').on('click', function(){
            var selectedProfile = $('input[name=selectedProfile]:checked').val();
            if(selectedProfile !== undefined){
                removeProfile(selectedProfile)
            } else {
                alert('Selecteer eerst een profiel');
            }
        });
    });

    function addProfile(){
        $.ajax({
            type: "POST",
            url: "{LB}/printing/profileForm/NULL"
        }).done(function(response) {
            $('#addProfileModalBody').html(response);
            $('#addProfileModal').modal('show');
        });
    }

    function editProfile(labelId){
        $.ajax({
            type: "POST",
            url: "{LB}/printing/profileForm/" + labelId
        }).done(function(response) {
            $('#addProfileModalBody').html(response);
            $('#addProfileModal').modal('show');
        });
    }

    function removeProfile(labelId){
        bootbox.confirm("<h3>Print profiel verwijderen</h3> <p>Geselecteerd print profiel verwijderen?</p>", function(result) {
            if(result == true){
                window.location = "{LB}/printing/removeProfile/" + labelId;
            }
        });
    }

</script>