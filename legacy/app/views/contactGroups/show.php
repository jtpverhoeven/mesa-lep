<div class="span8">
    <div class="well">
        <h4> Contactgroepen voor:  {client_name} </h4>
        {contactgroup_table}
    </div>
</div>

<div class="span2">
    <ul class="nav nav-list well">
        <li class="nav-header"> Contactgroep </li>        
        <li><a href="{LB}/contactGroups/add/{client_id}"><i class="icon-plus"></i> Toevoegen </a> </li>                
        <li><a href="#" id="editClick"><i class="icon-pencil"></i> Bewerken </a> </li>                
        <li><a href="#" id="removeClick"><i class="icon-trash"></i> Verwijderen </a> </li>                
    </ul>
</div>


<script>
$(function(){

 

    $('#editClick').click(function(){
       var selectedAssay = $('input[name=selectedContactGroup]:checked').val();
       if(selectedAssay !== undefined){
           window.location.href = '{LB}/contactGroups/edit/' + selectedAssay;
       } else {
           alert('Geen contactgroep geselecteerd');
       }
    });

    $('#removeClick').click(function(){
       var selectedAssay = $('input[name=selectedContactGroup]:checked').val();
       if(selectedAssay !== undefined){

       bootbox.confirm("<h3>Contactgroep verwijderen?</h3> <p>Weet u zeker dat u deze contact groep wilt verwijderen ?</p>", function(result) {
            if(result == true){
                window.location.href = '{LB}/contactGroups/destroy/{client_id}/' + selectedAssay;
            }
         });

       } else {
           alert('Geen contactgroep geselecteerd');
       }
    });

});

</script>