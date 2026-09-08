<div class="span8">   
    <div class="well">        
        <h3>{MESA_PCK_TITLE}</h3>
        
        {available_packets_listing}        
    </div>
    
</div>
    
    
<div class="span2">
    <ul class="nav nav-list well">                                     
        <li class="nav-header">{MESA_PCK_RSB_ACTIONS}</li>
        <li><a href="{LB}/packets/add" id="addPacketButton"><i class="icon-plus"></i> Add packet</a> </li>
        <li><a href="#" id="editPacketButton"><i class="icon-edit"></i> Edit Selected </a> </li>                        
        <li><a href="#" id="remPacketButton"><i class="icon-trash"></i> Remove packet</a> </li>       
    </ul>         
</div>

<script>
    
    
$(function(){
   
    $('#editPacketButton').click(function(){       
       var selectedPacket = $('input[name=selectedPacket]:checked').val();       
       if(selectedPacket !== undefined){
           window.location.href = '{LB}/packets/edit/' + selectedPacket;                       
       } else {
           alert('Select a packet from the table first.');
       }
             
    });
    
    $('#remPacketButton').click(function(){
       
       var selectedPacket = $('input[name=selectedPacket]:checked').val();       
       if(selectedPacket !== undefined){           
               bootbox.confirm("<h3>{MESA_PCK_REM_TITLE}</h3> <p>{MESA_PCK_REM_BODY}</p>", function(result) {             
                    if(result == true){
                        $.ajax({
                            type: "POST",            
                            data:  { packet_id: selectedPacket },
                            url: "{LB}/packets/removePacket/"
                        }).done(function(msg) {
                            $('#packet_' + selectedPacket).remove();
                            $('#packTable').highLight();
                        });
                    }
                });                       
       } else {
           alert('Select a packet from the table first.');
       }
       
    });
   
});
</script>