<div class="span8">
    <div class="well">
        <h3> Referentie bronnen </h3>

        {table}
     
    </div>
</div>


<div class="span2">
    <ul class="nav nav-list well">
        <li class="nav-header">Acties </li>
        <li><a href="{LB}/referenceSources/edit"><i class="icon-plus"></i> Toevoegen</a> </li>
        <li><a href="#" id="editSource"><i class="icon-edit"></i> Bewerken </a> </li>
        <li><a href="#" id="removeSource"><i class="icon-remove"></i> Verwijderen </a> </li>        
    </ul>
</div>

<script>

$(function(){

    

    $('#editSource').on('click', function(){
        var selectedSource = $('input[name=selectedSource]:checked').val();
       
        if(selectedSource !== undefined)
        {
            window.location = "{LB}/referenceSources/edit/" + selectedSource;
        } 
        
        else 
        {
            alert('{MESA_LBD_SELECTFIRST}');
        }
    });


    $('#removeSource').click(function(){
       var selectedSource = $('input[name=selectedSource]:checked').val();
       
       if(selectedSource !== undefined){
           bootbox.confirm("<h3>Bron verwijderen?</h3> <p>Bron wordt ook verwijderd van gekoppelde onderzoeksprofielen</p>", function(result) {
                    if(result == true){
                        window.location = "{LB}/referenceSources/destroy/" + selectedSource;
                    }
                });
       } else {
           alert('Geen bron geselecteerd');
       }
});


});

</script>

