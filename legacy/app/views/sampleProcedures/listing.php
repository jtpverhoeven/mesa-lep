<div class="span8">   
    <div class="well">        
        <h3> {MESA_SMP_LISTING}</h3>
        {procedure_table}
    </div>    
</div>


<div class="span2">
    <ul class="nav nav-list well">                                     
        <li class="nav-header">{MESA_SMP_PROCEDURES}  </li>
        <li><a href="{LB}/sampleProcedures/edit"><i class="icon-plus"></i> {MESA_SMP_ADD}</a> </li>
        <li><a href="#" id="editProc"><i class="icon-edit"></i> {MESA_SMP_EDIT} </a> </li>
        <li><a href="#" id="hideProc"><i class="icon-eye-close"></i> {MESA_SMP_HIDE} </a> </li>
        <li><a href="#" id="removeProc"><i class="icon-remove"></i> {MESA_SMP_REMOVE} </a> </li>
        <li><a href="{LB}/sampleProcedures/setDefaultProcedure" ><i class="icon-certificate"></i> {MESA_SMP_DEFAULT} </a> </li>

        <li class="nav-header">{MESA_SMP_PROCEDUREFIELDS} </li>
        <li><a href="{LB}/sampleProcedureFields/listing" ><i class="icon-cog"></i> {MESA_SMP_GOTOFIELDS} </a> </li>
        
    </ul>         
</div>

<script>

    $(document).ready(function() {
        $('#procedure_table').DataTable({
            "paging":   false,
            "info":     false,
            "order": [[ 2, "asc" ]]
        });
    } );


$('#removeProc').click(function(){       
       var selectedProc = $('input[name=selectedProc]:checked').val();       
       if(selectedProc !== undefined){
           bootbox.confirm("<h3>{MESA_SMP_DELETETITLE}</h3> <p>{MESA_SMP_DELETEMSG}</p>", function(result) {             
                    if(result == true){
                        $.ajax({
                            type: "POST",            
                            data:  { selected_proc: selectedProc },
                            url: "{LB}/sampleProcedures/removeProcedure/"
                        }).done(function(msg) {  
                            window.location.href = "{LB}/sampleProcedures/listing";
                        });
                    }
                });                       
       } else {
           alert('{MESA_SMP_SELECTFIRST}');
       }
       
});


$('#hideProc').click(function(){       
       var selectedProc = $('input[name=selectedProc]:checked').val();       
       if(selectedProc !== undefined){                            
            $.ajax({
                type: "POST",            
                data:  { selected_proc: selectedProc },
                url: "{LB}/sampleProcedures/hideProcedure/"
            }).done(function(msg) {         
               window.location.href = "{LB}/sampleProcedures/listing";
            });
       } else {
           alert('{MESA_SMP_SELECTFIRST}');
       }
});


$('#editProc').click(function(){

    var selectedProc = $('input[name=selectedProc]:checked').val();       
       if(selectedProc !== undefined){
           window.location.href = '{LB}/sampleProcedures/edit/' + selectedProc;
        }
       
       else {
           alert('{MESA_SMP_SELECTFIRST}');
       }
});

</script>