<div class="span8">
    <div class="well">
        <h3> Voettekst rapportages </h3>

        {editFooterForm}

        <h5> Voorbeeld </h5>

        <div id="render">{current_footer}</div>

                
    </div>
</div>


<div class="span2">
    <ul class="nav nav-list well follow-scroll">
        <li class="nav-header"> Acties </li>        
        <li><a href="#" id="saveFooterSubmit"><i class="icon-edit"></i> Opslaan</a> </li>
        
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

    $('#data').on('keyup', function(){
        $('#render').html($('#data').val());
    })

})

</script> 