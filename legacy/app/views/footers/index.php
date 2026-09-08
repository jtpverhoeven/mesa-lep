<div class="span8">
    <div class="well">
        <h3> Voettekst rapportages </h3>
        {footer_table}
    </div>
</div>


<div class="span2">
    <ul class="nav nav-list well follow-scroll">
        <li class="nav-header"> Acties </li>        
        <li><a href="#" id="editClick"><i class="icon-edit"></i> {MESA_ASE_EDIT}</a> </li>
        
    </ul>
</div>



<script>
$(function(){
    

    $('#editClick').click(function(){
       var selectedFooter = $('input[name=selectedFooter]:checked').val();
       if(selectedFooter !== undefined){
           window.location.href = '{LB}/footers/edit/' + selectedFooter;
       } else {
           alert('Geen voettekst geselecteerd');
       }
    });

})

</script> 