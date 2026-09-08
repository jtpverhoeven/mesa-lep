<div class="span8">    
    <div class="well">        
        <h3> Edit Packet <small>{name}</small> </h3> 
        
        <h6> Workflows in this packet </h6>
      
        {bound_table}
        
        <h6> Packet tags </h6>
        <i class="icon-tags"></i> <span id="packet_tags">{tags}</span>
                
    </div>

</div>

<div class="span2">

    <ul class="nav nav-list well">                                     
        <li class="nav-header">{MESA_PCK_RSB_ACTIONS} </li>
        <li><a href="#addTestModal" role="button"  data-toggle="modal"><i class="icon-plus"></i> Add Flow</a> </li>
        <li><a href="#" id="removeTest"><i class="icon-edit"></i> Remove selected flow</a> </li>
        <li><a href="#" id="changeTags"><i class="icon-tags"></i> Change tags</a> </li>
        
        <li class="nav-header">Other </li>
        <li><a href="{LB}/packets/listing/"><i class="icon-backward"></i> Back to List </a> </li>
    </ul>
    
</div>

<div id="addTestModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addTestModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addTestModalTitle">Add flow to packet</h3>
    </div>
    <div class="modal-body" id="addTestBody" >   
        {add_test_form}
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
        <button id="submitAddTest" class="btn btn-primary">Add</button>
    </div>
</div>

<script>
    
$(function(){
   
    $('#removeTest').click(function(){       
       var selectedTest = $('input[name=selectedTest]:checked').val();       
       if(selectedTest !== undefined){           
            $.ajax({
                type: "GET",            
                url: "{LB}/packetsTests/removeFromPack/" + selectedTest
            }).done(function(msg) {
                $('#pt_' + selectedTest).remove();
            });
                        
       } else {
           alert('Select an packet test from the table first.');
       }             
    });
    
    
    $('#changeTags').click(function(){
    
     bootbox.prompt("<h3>Change tags</h3> <p>Type new tags below</p>", 'Cancel', 'save',   function(result) {             
             
                
      
         $.ajax({
                type: "POST",            
                data:  { packet_id: '{packet_id}' , tags: result},
                url: "{LB}/packets/updateTags/" + result
            }).done(function(msg) {
                $('#packet_tags').html(result);
                $('#packet_tags').highLight();
            });
      
     });
      
      
    });
   
});    
</script>