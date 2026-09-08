<div class="span8">    
    <div class="well">        
        <h3> {MESA_AST_LISTING} </h3>
        {available_assay_listing}        
    </div>        
</div>

<div class="span2">        
        <ul class="nav nav-list well">                                     
            <li class="nav-header"> {MESA_AST_ACTIONS} </li>
            
            <li><a href="{LB}/assayTypes/add/"><i class="icon-plus"></i> {MESA_AST_ADD}</a> </li>
            <li><a id="editClick" href="#"><i class="icon-edit"></i> {MESA_AST_EDIT} </a> </li>
            <li><a id="removeClick" ><i class="icon-remove"></i> {MESA_AST_REMOVE} </a> </li>            
        </ul>                                 
</div>

<script>

$(function(){

    $(document).ready(function() {
        $('#assayTypesTable').DataTable({
            "paging":   false,
            "info":     false,
            "order": [[ 2, "asc" ]]
        });
    } );

    $('#editClick').click(function(){       
       var selectedType = $('input[name=selectedAssayType]:checked').val();       
       if(selectedType !== undefined){
           window.location.href = '{LB}/assayTypes/edit/' + selectedType;                       
       } else {
           alert('{MESA_AST_SELECTFIRST}');
       }             
    });
    
    $('#removeClick').click(function(){ 
                
       var selectedType = $('input[name=selectedAssayType]:checked').val();       
       
       if(selectedType !== undefined){     
            bootbox.confirm("<h3>{MESA_AST_DELETETITLE}</h3> <p>{MESA_AST_DELETEMSG}</p>", function(result) {             
            if(result == true){
                $.ajax({
                    type: "POST",                                                
                    url: "{LB}/assayTypes/remove/" + selectedType
                }).done(function(msg) {                         
                    $('#tr_' + selectedType).remove();
                });
            }
        });
        } else {
           alert('{MESA_AST_SELECTFIRST}');
       }
             
    });
   
});

</script>