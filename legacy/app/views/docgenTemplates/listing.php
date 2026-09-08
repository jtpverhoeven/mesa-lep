<div class="span8">
    <div class="well">
        <h3> Rapport associaties bewerken  </h3>

        {template_table}


    </div>
</div>

<div class="span2">
    <ul class="nav nav-list well">
        <li class="nav-header">Associaties</li>
        <li><a href="#" id="editDocgenTemplate"><i class="icon-edit"></i> Bekijken / Bewerken </a> </li>

    </ul>
</div>




<script>
$(function(){

    $('#editDocgenTemplate').click(function(){
       var selectedAssay = $('input[name=selectedTemplate]:checked').val();
       if(selectedAssay !== undefined){
           window.location.href = '{LB}/docgenAssociations/view/' + selectedAssay;
       } else {
           alert('Selecteer eerst een template.');
       }
    });


});


</script>
