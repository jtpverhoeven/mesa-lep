<div class="span8">    
    <div class="well">        
        <h3> Available Analysis </h3>
            {available_analysis_listing}
    </div>       
</div>

<div class="span2">
        
        <ul class="nav nav-list well">                                     
            <li class="nav-header">Analysis Actions </li>
            
            <li><a href="{LB}/analysis/add/"><i class="icon-plus"></i> Add Analysis </a> </li>
            <li><a id="editClick" href="#"><i class="icon-edit"></i> Edit selected </a> </li>
            <li><a href="{LB}/analyticals/listing/"><i class="icon-remove"></i> Remove Selected </a> </li>
            
        </ul>                 
                
</div>

<script>
    
    
$(function(){
   
    $('#editClick').click(function(){       
       var selectedAnalysis = $('input[name=selectedAnalysis]:checked').val();       
       if(selectedAnalysis !== undefined){
           window.location.href = '{LB}/analysis/edit/' + selectedAnalysis;                       
       } else {
           alert('Select an analysis from the table first.');
       }
             
    });
   
});
</script>