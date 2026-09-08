<div class="span10">         

    <div class="row-fluid">
        
        <div class="span4">
            <div class="well well-small">
                <h6><i class="icon-barcode"></i> Sample lookup</h6>
                <div class="control-group" id="barCG">
                <input class="input-block-level" type="text" placeholder="Scan or type barcode"  value="{entry_scan}" id="barcodeEntry" />
            </div>
            </div>                        
            
            <div id='temp' class="well well-small">
                <h6><i class="icon-info"></i> Sample information </h6>
                <table class="table table-condensed table-striped">
                    <tbody>
                    <tr>
                        <td width="125px">Barcode:</td>
                        <td><span id="barcode"></span></td>
                    </tr>
                    <tr>
                        <td>Sample:</td>
                        <td><span id="description"></span></td>
                    </tr>
                    <tr>
                        <td >Client:</td>
                        <td><span id="client_by_name"></span></td>
                    </tr>
                    <tr>
                        <td >Sub client:</td>
                        <td><span id="subclient_by_name"></span></td>
                    </tr>
                    <tr>
                        <td >Project:</td>
                        <td><span id="project_by_name"></span><span class="pull-right">
                                
                        <a href="#" id="project_go_button"><i class="icon-share"></i></a> </td>
                    </tr>
                    
                    {custom_fields_rows}
                    
                    </tbody>
                </table>
            </div>
        </div>        
        
        <div class="span3">
            <div class="well well-small">
                <h6><i class="icon-beaker"></i> Attached analysis 
                    
                    
                    <span class="pull-right">
                                                
                    <div class="dropdown">
                    <button data-toggle="dropdown"  type="button" class="btn btn-mini btn-default dropdown-toggle"><i class="icon-gears"></i></button>
                    <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                        <li><a role="menuitem" tabindex="-1" id="addAnalysis" >Add analysis<span class="pull-right"><i class="icon-plus"></i></span></a></li>                        
                        <li><a role="menuitem" tabindex="-1" id="deleteAnalysis">Remove selected<span class="pull-right "><i class="icon-trash"></i></span></a></li>            
                    </ul>
                  </div>
                        
         </span></h6>
                                                                
                <div id="attached_analysis" class="list-group">
                    
                </div>
                
            </div>                            
            <div class="well well-small">
                <h6><i class="icon-comments"></i> Comments</h6>
                <div id="sample_comment_block">
                    
                </div>
            
                <button class="btn-link btn-small" type="button"><i class="icon-comment"></i> Post comment</button>
                <button class="btn-link btn-small" type="button"><i class="icon-chevron-down"></i> Load more</button>
                
            </div>            
        </div>    
        
        <div class="span4">
            <div id='editAnalysisDiv' class="well well-small">
                <h6><i class="icon-edit"></i> Analysis </h6>

                <div id='baseScanIcon' class="alert alert-info hide">
                   <i class='icon-info'></i> Select an analysis to edit.
                </div>
                
                <span id='editPanel'></span>
                           
                
            </div>                            
        </div>    
        
    </div>            
</div>


<div id="addAnaWindow" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addAnaWindowTitle" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="addAnaWindowTitle">Add analysis</h3>
  </div>
  <div class="modal-body">

    <div id="flowDiv">
        <div id="workflowListGroup" class="list-group">
            {workflow_list}                                
        </div>
    </div>                                                                                 
    
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>   
  </div>
</div>




<script>
    
    var selectedSample = false;
    
$(function() {
    
    selectedAnalysis = false;
    $('#barcodeEntry').focus();   
    entryScan();
   
    //for analysis panel
        $('#flowDiv').slimScroll({
            height: '300px'
        });
        
        $('#packetsDiv').slimScroll({
            height: '300px'
        });
        
        $('#searchDiv').slimScroll({
            height: '300px'
        });
        
        
    $('#searchButton').click(function(){
        var searchTerm = $('#searchTerm').val();
        $.ajax({
            type: "POST",
            data: {search: searchTerm },
            url: "{LB}/samples/analysisSearch/" 
        }).done(function(searchResults) {
            $('#searchList').html(searchResults);
        });    
    });
 

         
   $("#barcodeEntry").bind('keydown', 'return', function(){
       selectedAnalysis = false; 
       barcodeSubmit();
   });
   
   $("#attached_analysis").on('click', '.list-group-item', function(){        
       $("#attached_analysis").find('.list-group-item').removeClass('active');  
       $(this).addClass('active');
       
        selectedAnalysis = $(this).attr('sampleAnalysis');
        readAnalysis($(this).attr('barcode'));
   });       
   
   $('#deleteAnalysis').on('click', function(){
      
        if(selectedAnalysis == false){
            alert('no analysis selected');
            return;
        }
       
        else{
             bootbox.confirm("<h3>{MESA_REMOVE_ANALYSIS_FROM_SAMPLE_TITLE}</h3> <p>{MESA_REMOVE_ANALYSIS_FROM_SAMPLE_MSG}</p>", function(result) {             
                if(result == true){
                     $.ajax({
                        type: "POST",                            
                        url: "{LB}/samples/removeAnalysis/" + selectedAnalysis
                    }).done(function(msg) {            
                        selectedAnalysis = false;
                        barcodeSubmit();
                    }); 
                }
              });      
        }                       
   });
   
   $('#addAnalysis').on('click', function(){
   
        if(window.selectedSample == false){
            alert('no sample selected');
        } else {
            $('#addAnaWindow').modal('show');
        }
   
   });
           
});

function addSingle(flowId) {      
           
        $.ajax({
            type: "POST",
            data: {flow: flowId, barcode: window.selectedSample },
            url: "{LB}/sampleAnalysis/addByBarcode/" 
        }).done(function(searchResults) {
            $('#addAnaWindow').modal('hide');
            $('#barcodeEntry').val(window.selectedSample);
            barcodeSubmit();
        });  
    
}


function barcodeSubmit(){
    
    var barcode = $('#barcodeEntry').val();
       
    $.ajax({
        type: "POST",
        dataType: 'json',
        url: "{LB}/samples/doLookup/" + barcode
    }).done(function(msg) {            
        updateWindowSample(msg);             
    }).fail(function(msg){
        var mesaError = msg.responseText;
        if(mesaError == 'BAR_WRONG'){
          $('#barCG').addClass('error');
          $.playSound('{LP}/snd/scanDeny.wav');                
        }   
        
        selectedSample = false;
    });
  
}

function updateWindowSample(sampleInfo){
    
    var focusElement;
    
    $('#barCG').removeClass('error');
    $.playSound('{LP}/snd/scan.wav'); 
    
    $.each(sampleInfo, function(index, value) {                
        
        if ($("#" + index ).length > 0){
            $('#' + index).html(value);                                    
        }         
        
        if(index == 'barcode'){
             selectedSample = value;
        }
        
        if(index == 'project_link'){
            $('#project_go_button').attr('href', value);
        }
        
        if(index == 'selected_analysis'){
            if(value == 'NULL'){
                $('#baseScanIcon').show();
                $('#editPanel').html('');
            } else{
                $('#baseScanIcon').hide();                
            }
        } 
        
        if(index == 'entry_panel'){
            $('#editPanel').html(value);
        }
        
        if(index == 'focus_on_element'){            
           focusElement = value;
        }                
    });
    
    focusInput(focusElement);
}

function focusInput(element){
    $('#' + element).focus();
}

function readAnalysis(bar){
    $.ajax({
            type: "POST",
            dataType: 'json',
            url: "{LB}/samples/doLookup/" + bar
        }).done(function(sampleInfo) {
            
            $('#baseScanIcon').hide();                
            
            $.each(sampleInfo, function(index, value) {                
                if(index == 'entry_panel'){
                    $('#editPanel').html(value);
                    //$.playSound('{LP}/snd/scan.wav'); 
                    $('#editAnalysisDiv').highLight();
                }        
            });
   });
}


function resultHandler(fieldId){

    console.log(fieldId);
    var dbRid = $('#' + fieldId).attr('dbrid');
    var fieldName = $('#' +fieldId).attr('name');
    var fieldValue = $('#' + fieldId).val();

    $.ajax({
            type: "POST", 
            dataType: 'json',
            data: { dbRid: dbRid, fieldName: fieldName, fieldValue: fieldValue},
            url: "{LB}/results/resultsHandler/"
    }).done(function(resultUpdate) {
          updateBinds(resultUpdate);
    });

}

function updateBinds(bindObj){

    $.each(bindObj, function(fieldId, fieldValue) {  
                
        var currFieldValue = $('#' + fieldId).val();
        if(currFieldValue != fieldValue){
            $('#' + fieldId).val(fieldValue);
            $('#' + fieldId).highLight();             
        }        
    });
}


function saveField(fieldId){
    
      var dbRid = $('#' + fieldId).attr('dbrid');
      var fieldName = $('#' +fieldId).attr('name');
      var fieldValue = $('#' + fieldId).val();
      
      $.ajax({
            type: "POST",   
            data: { dbRid: dbRid, fieldName: fieldName, fieldValue: fieldValue},
            url: "{LB}/results/saveField/"
        }).done(function(sampleInfo) {
            
        });    
}

function entryScan(){
    if($('#barcodeEntry').val() != ''){
       barcodeSubmit();
    }
}


function authoriseAnalysis(analysisId, auth, barcode){
    if(auth == '-1'){
        bootbox.alert("<h3>{MESA_AUTH_ERROR_LOCKED_SMP_TITLE}</h3> <p> {MESA_AUTH_ERROR_LOCKED_SMP_MSG} </p> ");
        return false;
    }
      
     if(auth == '0'){
        var titleBox = '{MESA_CONFIRM_DEAUTH_TITLE}';
        var messageBox = '{MESA_CONFIRM_DEAUTH_MSG}';
        var futureSetting = '1';
     }
     
     if(auth == '1'){
        var titleBox = '{MESA_CONFIRM_AUTH_TITLE}';
        var messageBox = '{MESA_CONFIRM_AUTH_MSG}';
        var futureSetting = '0';
     }
                             
      bootbox.confirm("<h3>" + titleBox + "</h3>" + messageBox , function(result) {    
         
         if(result == true){
            $.ajax({
                type: "POST",   
                data: { analysisId: analysisId, authSetting: auth},
                url: "{LB}/sampleAnalysis/authoriseAnalysis/"
            }).done(function(authResponse) {

                if(auth == 0){
                    $('#icon_' + analysisId).removeClass('icon-star');
                    $('#icon_' + analysisId).addClass('icon-star-empty');                                    
                }           

                if(auth == 1){
                    $('#icon_' + analysisId).removeClass('icon-star-empty');
                    $('#icon_' + analysisId).addClass('icon-star');                
                }
                
                var newTarget = 'authoriseAnalysis("' +analysisId +'","' + futureSetting +'", "' + barcode +'" );'
                $('#' + analysisId + '_badge').attr('onClick', newTarget);     
                readAnalysis(barcode);
            });              
         }                  
      });               
}

</script>