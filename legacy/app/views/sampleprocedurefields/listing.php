<div class="span8">   
    <div class="well">        
        <h3> {MESA_SPF_LISTINGTITLE} </h3>
        {procfield_table}
    </div>    
</div>


<div class="span2">
    <ul class="nav nav-list well">                                     
        <li class="nav-header">{MESA_SPF_FIELD} </li>
        <li><a href="{LB}/sampleProcedureFields/edit"><i class="icon-plus"></i> {MESA_SPF_ADDFIELD}</a> </li>
        <li><a href="#" id="editProc"><i class="icon-edit"></i> {MESA_SPF_EDITFIELD} </a> </li>
        <li><a href="#" id="removeProc"><i class="icon-remove"></i> {MESA_SPF_REMOVEFIELD} </a> </li>                                  
    </ul>         
</div>

<script>

    $(document).ready(function() {
        $('#procfield_table').DataTable({
            "paging":   false,
            "info":     false,
            "order": [[ 2, "asc" ]]
        });
    } );

$('#removeProc').click(function(){       
       var selectedProcField = $('input[name=selectedProcField]:checked').val();       
       if(selectedProcField !== undefined){
           bootbox.confirm("<h3>{MESA_SPF_REMOVEFIELDTITLE}</h3> <p>{MESA_SPF_REMOVEFIELDMSG}</p>", function(result) {             
                    if(result == true){
                        $.ajax({
                            type: "POST",            
                            data:  { selected_proc: selectedProcField },
                            url: "{LB}/sampleProcedureFields/removeProcedureField/" + selectedProcField
                        }).done(function(msg) {                                   
                             window.location.reload();
                        });
                    }
                });                       
       } else {
           alert('{MESA_SPF_NOFIELDSELECTED}');
       }
       
});


$('#editProc').click(function(){
    var selectedProcField = $('input[name=selectedProcField]:checked').val();       
       if(selectedProcField !== undefined){
           window.location.href = '{LB}/sampleProcedureFields/edit/' + selectedProcField;
        }       
       else {
           
       }
});
</script>