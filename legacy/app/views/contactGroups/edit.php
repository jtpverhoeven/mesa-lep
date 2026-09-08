<div class="span8">
    <div class="well">
        <h4> Contactgroep: {group_name}  ({client_name}) </h4>
        
        {addGroepForm}

        <h5> Email adressen in contact groep </h5>

        {contactgroupmembers_table}


        <h5> Wijzigingen </h5>

        {contactgroupmembers_changes_table}
    </div>
</div>

<div class="span2">
    <ul class="nav nav-list well">
        <li class="nav-header"> Contactgroep </li>               
        <li><a href="#addEmailModal" role="button"  data-toggle="modal" id="addClick"><i class="icon-pencil"></i> Email toevoegen </a> </li>                
        <li><a href="#" id="removeClick"><i class="icon-trash"></i> Email verwijderen </a> </li>                
        <li><a href="{LB}/contactGroups/show/{client_id}"><i class="icon-plus"></i> Terug naar overzicht </a> </li>                
    </ul>
</div>


<div id="addEmailModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addEmailModalTile" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addEmailModalTile">Email toevoegen</h3>
    </div>
    <div class="modal-body">

        {emailForm}

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_ASE_CANCEL}</button>
        <button class="btn btn-primary" id="addEmailSubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_ASE_SAVE} </button>
    </div>
</div>

<script>
$(function(){

  $('#removeClick').click(function(){
       var selectedAssay = $('input[name=selectedContactGroupMember]:checked').val();
       if(selectedAssay !== undefined){
           window.location.href = '{LB}/contactgroupMembers/destroy/{group_id}/' + selectedAssay;
           //alert('{group_id}' + selectedAssay);
       } else {
           alert('Geen contact groep persoon geselecteerd');
       }
    });

});

</script>