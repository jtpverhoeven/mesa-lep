<div class="span8">    
    <div class="well">        
        <h3> Edit analysis <small id="nameHeader">{name}</small> </h3> 
        
        <h5> Tests  </h5>
        {availableTestsTable}
        
        <h5> Result fields </h5>                
        {resultfields_list}
    </div>

</div>

<div class="span2">

    <ul class="nav nav-list well">                                     
        <li class="nav-header">Analysis </li>

        <li><a href="#" id="nameChangeButton"><i class="icon-edit"></i> Edit name</a> </li>
        <li><a href="#"><i class="icon-trash"></i> Remove / hide analysis</a> </li>

        <li class="nav-header">Tests </li>

        <li><a href="#addTestModal" role="button"  data-toggle="modal"><i class="icon-plus"></i> Add Test </a> </li>
        <li><a href="#" id="editClick"><i class="icon-edit-sign"></i> Edit selected </a> </li>

        
        <li class="nav-header"> Result generation</li>
        <li><a href="{LB}/resultFields/edit/{id}"><i class="icon-filter"></i> Add results field </a> </li>        
        <li><a href="#"><i class="icon-bar-chart"></i> Set limits </a> </li>
                
        <li class="nav-header">Other </li>
        <li><a href="{LB}/analysis/listing/"><i class="icon-backward"></i> Back to List </a> </li>


    </ul>                                 
</div>

<div id="addTestModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addTestModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addTestModalTitle">Add Test</h3>
    </div>
    <div class="modal-body" id="addTestBody" >   
        {addTestForm}
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
        <button id="submitAddTest" class="btn btn-primary">Add Test</button>
    </div>
</div>

<script>
    
$(function(){
    
        $('#addTestBody').slimScroll({
            height: '400px',
            railVisible: true,
            railOpacity: 0.1,
            alwaysVisible: true           
        });

        $('#editClick').click(function(){       
               var selectedTest = $('input[name=selectedTest]:checked').val();       
               if(selectedTest !== undefined){
                   window.location.href = '{LB}/analysisTests/edit/' + selectedTest;                       
               } else {
                   alert('Select a test from the table first.');
               }
        });

           
        $('#nameChangeButton').click(function(){
            bootbox.prompt("<h3>{MESA_ALS_EDITNAME_TITLE}</h3> <p>{MESA_ALS_EDITNAME_MSG}</p>", function(result) {                         
                $.ajax({
                    type: "POST",                            
                    data: { name: result , id: '{id}'} ,
                    url: "{LB}/analysis/changeName/" 
                }).done(function(msg) {            
                    $('#nameHeader').html(result);
                    $('#nameHeader').highLight();
                });                 
            });                
        });
        
        
});  
    
</script>