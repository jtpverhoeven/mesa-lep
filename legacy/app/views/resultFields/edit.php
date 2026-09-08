<div class="span8">    
    <div class="well">        
        <h3> Result field designer  </h3>         
        {add_resultfield_form}                                
        
        <h3> Available constants </h3>
                 
        {available_constants}
        
    </div>

</div>


<div class="span2">

    <ul class="nav nav-list well">                  
                
        <li class="nav-header">Result field </li>
        <li><a href="#" id="saveResultFieldButton"><i class="icon-save"></i> Save changes </a> </li>
        <li><a href="#addConstantModal" role="button"  data-toggle="modal"><i class="icon-subscript"></i> Add constant   </a> </li>                
               
        <li class="nav-header">Other </li>
        <li><a href="{LB}/analysis/edit/{analysis_id}"><i class="icon-backward"></i> Back to analysis </a> </li>
        
    </ul>
</div>

<div id="addConstantModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addConstantModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addConstantModalTitle">New Constant</h3>
    </div>
    <div class="modal-body" id="addConstandBody" >           
        {add_constant_form}                               
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
        <button id="submitAddConstant" class="btn btn-primary">Add Constant</button>
    </div>
</div>

<script>

function submitConstant(){
    
    $('#addConstantModal').modal('hide');
        
     $.ajax({
            type: "POST",
            data: $("#addConstantForm").serialize(),
            url: "{LB}/fieldConstants/addConstant"
        }).done(function(msg) {                        
            $('#addConstantForm').trigger('reset');            
            updateConstants();          
        });            
}

function updateConstants(){
    $.ajax({
        type: "POST",        
        url: "{LB}/fieldConstants/fetchConstantsAjax/{analysis_id}"
    }).done(function(msg) {            
        $('#constantsTable').replaceWith(msg);
        $('#constantsTable').highLight();
    });            
}

function removeConstant(id){

     $.ajax({
            type: "POST",
            data: { id: id},
            url: "{LB}/fieldConstants/removeConstant"
        }).done(function(msg) {            
            $('#ct_' + id).remove();
            $('#constantsTable').highLight();
        });            

}

</script>