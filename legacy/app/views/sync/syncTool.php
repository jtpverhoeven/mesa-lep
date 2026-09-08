<div class="span3">   
    <div class="well">        
        <h5><i class="icon-list"></i> {MESA_SYN_ACTIONS}</h5>
         
        
        <ul class="nav nav-list">                                                             
            <li><a id="syncButton" href="#"><i class="icon-exchange"></i> {MESA_SYN_SYNC} </a> </li>                                 
            <li><a id="dumpToAA" href="#"><i class="icon-cloud-upload"></i> {MESA_SYN_DUMPTOAA} </a> </li>                     
            <li><a id="cloneFromAA" href="#"><i class="icon-cloud-download"></i> {MESA_SYN_CLONEFROM} </a> </li>                     
        </ul>         
        
    </div>    
</div>

<div class="span7">   
    <div class="well">
        <h5><i class="icon-check"></i> {MESA_SYN_RESULT}</h5>
          
        <span id="logLoadingIndicator"><i class="icon-spinner icon-spin"></i> {MESA_SYN_WORKING} </span>
        
        <div id="conflictWindow">
            
        </div>
    </div>
</div>



<script>

$(function(){
   
   $('#logLoadingIndicator').hide();

   $('#syncButton').on('click', function(){
    
        $('#conflictWindow').html('');
        $('#logLoadingIndicator').show();
    
        $.ajax({
            type: "POST",                         
            url: "{LB}/sync/syncToolSync"
        }).done(function(msg) {   
            $('#logLoadingIndicator').hide();
            $('#conflictWindow').html(msg);
        });
        
   });
   
   $('body').on('change', '.syncSelector', function(){
      
      var mesa_id = $(this).attr('mesaId');
      var aa_id = $(this).attr('aaId');
      var sync_type = $(this).attr('syncType');
      var sync_direction = $(this).val();
      var parent_tr = $(this).attr('parentTr');
      
      console.log('Sync mesa_id: '  +mesa_id  + ' with aa_id: ' + aa_id + ' syncType: ' + sync_type + ' direction:' + sync_direction);      
      $(this).prop("disabled", true);      
      $('#tr_' + parent_tr).remove();
      
      $.ajax({
            type: "POST",             
            data: {aa_id: aa_id, mesa_id:mesa_id, sync_type: sync_type, sync_direction: sync_direction },
            url: "{LB}/sync/syncToolCommand"
        }).done(function(msg) {   
            console.log(msg);            
        });
   });
   
   
   $('#dumpToAA').on('click', function(){       
        bootbox.confirm("<h3>{MESA_SYN_CLONETOAATITLE}</h3> <p>{MESA_SYN_CLONETOAAMSG}</p>", function(result) {             
            if(result == true){
                $('#conflictWindow').html('');
                authPopup('sync', 'cloneToAA', cloneToAA, Array() );
            }
        }); 
               
   }); 
   
   $('#cloneFromAA').on('click', function(){       
       bootbox.confirm("<h3>{MESA_SYN_CLONEFROMAATITLE}</h3> <p>{MESA_SYN_CLONEFROMAAMSG}</p>", function(result) {             
            if(result == true){
                $('#conflictWindow').html('');
                authPopup('sync', 'cloneToMesa', cloneToMesa, Array() );
            }
        });
   }); 
   
});


function cloneToMesa(){
 
   $('#logLoadingIndicator').show();
    $.ajax({
          type: "POST",                     
          url: "{LB}/sync/cloneToMesa"
    }).done(function(msg) {   
          console.log(msg);            
          $('#logLoadingIndicator').hide();
          alert('{MESA_SYN_COMPLETED}');
    });     
}

function cloneToAA(){
 
    $('#logLoadingIndicator').show();
    $.ajax({
          type: "POST",                     
          url: "{LB}/sync/cloneToAA"
    }).done(function(msg) {   
          console.log(msg);            
          $('#logLoadingIndicator').hide();
          alert('{MESA_SYN_COMPLETED}');
    });    
}

</script>