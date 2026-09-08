<div class="span8">    
    <div class="well">        
        <h3> Available Workflows </h3>
        {available_flows}        
    </div>
    
    
</div>

<div class="span2">
        
        <ul class="nav nav-list well">                                     
            <li class="nav-header">Workflow Actions </li>
            
            <li><a href="{LB}/flows/add/"><i class="icon-plus"></i> Add Workflow </a> </li>
            <li><a id="editClick" href="#"><i class="icon-edit"></i> Edit selected </a> </li>
            <li><a id="removeClick" href="#" ><i class="icon-remove"></i> Remove Selected </a> </li>
            
        </ul>                 
                
</div>

<script>

$(function(){
   
    $('#editClick').click(function(){       
       var selectedFlow = $('input[name=selectedFlow]:checked').val();       
       if(selectedFlow !== undefined){
           window.location.href = '{LB}/flows/edit/' + selectedFlow;                       
       } else {
           alert('Select a workflow from the table first.');
       }
             
    });
   
});

</script>

