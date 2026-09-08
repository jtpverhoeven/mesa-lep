<div class="well">
    <h5><i class="icon-rocket"></i> {MESA_SED_TITLE} </h5>
    
    <div class="tabbable"> 
        {formHeader}
                        <div class="tab-content">

                        <ul class="nav nav-tabs">
                            <li class="active"><a href="#resultScriptTab" data-toggle="tab" tabindex="-1" ><i class="icon-trophy"></i> {MESA_SED_TABRESULTSCRIPT}</a></li>
                            <li class=""><a href="#confirmationTab" data-toggle="tab" tabindex="-1" ><i class="icon-eye-open"></i> {MESA_SED_TABCONFSCRIPT}</a></li>    
                            <li class=""><a href="#helpTab" data-toggle="tab" tabindex="-1" ><i class="icon-question-sign"></i> {MESA_SED_HELP} </a></li>    
                        </ul>
                            
                            <div class="tab-pane  active" id="resultScriptTab">
                                
                            <table style="width: 100%;">
                                <tbody>
                                    <tr>
                                        <td style="width: 25%;">
                                            {staticVar}
                                        </td>
                                        
                                        <td style="width: 25%;">
                                            {assayFields}
                                        </td>
                                        <td style="width: 25%;">
                                            {confField}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                                
                          
                            <table style="width: 100%;">
                                    <tbody>
                                        <tr>
                                            <td style="width:70%" rowspan="2">
                                                 {resultsScript}
                                            </td>
                                            
                                            <td style="vertical-align: top; padding-left: 10px;">
                                                <h5><i class="icon-tags"></i> {MESA_SED_TESTVARIABLES} </i>
                                               {varWindow} 
                                               
                                               <div class="pull-right">
                                                <button id="structCreate" type="button" class="btn btn-mini btn-primary"><i class="icon-magic"></i> {MESA_SED_MOCKSTRUCTURE} </button>
                                               </div>
                                            </td>
                                        </tr>
                                        
                                        <tr>
                                                <td style="vertical-align: top; padding-left: 10px;">
                                                <h5><i class="icon-bug"></i> {MESA_SED_SCRIPTOUT} </i>
                                             {debugWindow}
                                            </td>
                                            
                                        </tr>
                                    </tbody>
                            </table> 
                                
                          
                                                                                          
                            </div>
                                                                                                
                            

                            <div class="tab-pane" id="confirmationTab">                                                               
                               {confirmationScript}
                            </div>
                            
                            <div class="tab-pane" id="helpTab">
                                <div class="panel">
                                    <div class="panel-heading">
                                        <i class="icon-question"></i> {MESA_SED_ABOUTMESASCRIPT}
                                    </div>
                                    
                                    <div class="panel-body">
                                        <p> {MESA_SED_ABOUTMSG} </p>
                                    </div>                               
                                </div>
                                
                                 <div class="panel">
                                    <div class="panel-heading">
                                        <i class="icon-exclamation-sign"></i> {MESA_SED_TIPS}
                                    </div>
                                    
                                    <div class="panel-body">
                                        <p>{MESA_SED_TIPSMSG} </p>
                                    </div>
                                
                                </div>
                                
                            </div>

                        </div>
                        
                    {validationScript}
        
                    </div>
    
    <div class="pull-left">       
        {templateSelector}
    </div>
   
    
    <div class="pull-right">
         <button style="margin-right: 5px;" type="button" class="btn btn-small btn-warning" id="tryScript"><i class="icon-bug"></i> {MESA_SED_RUNSCRIPT} </button>
         <button style="margin-right: 5px;" type="button" class="btn btn-small btn-success" id="saveScriptDirectly"><i class="icon-save"></i> {MESA_SED_SAVE} </button>
         <button style="margin-right: 5px;" type="button" class="btn btn-small btn-primary" id="closeEditor"><i class="icon-ok"></i> {MESA_SED_READY} </button>
    </div>
    <br />    
</div>

<script>
$(function(){   
    
    openEditor();
    
    $('#staticVar').on('change', function(){        
        $('#resultsScript').insertAtCaret($(this).val());
        $("#staticVar").val("NULL");
    });
   
    $('#assayFields').on('change', function(){        
        $('#resultsScript').insertAtCaret($(this).val());
        $("#assayFields").val("NULL");
    });   
    
    $('#confField').on('change', function(){        
        $('#resultsScript').insertAtCaret($(this).val());
        $("#confField").val("NULL");
    });   
    
    $('#confirmationScript').on('change', function(){    
        redoConf();
     });
     
     $('#structCreate').on('click', function(){
         
         var typeBase = window.opener.$('#type_base').val();
         var dilUse = window.opener.$("#dillution").val();
         var repUse = window.opener.$("#replicates").val();
         var conUse = window.opener.$("#confirmation").val();
         var conType = window.opener.$("#confirmation_type").val();
         var conFields = $('#confirmationScript').val();
         var minCount = window.opener.$('#min_count').val();
         var maxCount = window.opener.$('#max_count').val();
         
                 
         
         $.ajax({
            type: "POST",                             
            data: {typeBase: typeBase, dilUse: dilUse, repUse: repUse, conUse: conUse, conType:conType, conFields: conFields, minCount:minCount , maxCount:maxCount },
            url: "{LB}/scriptEditor/generateStructure/"
        }).done(function(response) {                    
            $('#varWindow').val(response);
        }); 
     });
     
     $('#tryScript').on('click', function(){
        runDebug();
     });
     
     $('#saveScriptDirectly').on('click', function(){
        saveScript(); 
     });
     
     $('#templateSelector').on('change', function(){         
         
         var selectedScript = $(this).val();
         
         if(selectedScript == 'NULL'){
             return;
         }
         else{             
                $('#templateSelector option[value=NULL]').attr('selected', 'selected');
                $.ajax({
                    type: "POST",                             
                    url: "{LB}/scriptEditor/loadTemplate/"  + selectedScript
                }).done(function(response) {                    
                    $('#resultsScript').insertAtCaret(response);
                }); 
            
         }         
     });
});

//function pasteStruct(){
//$('#varWindow').val("$results = array(); \n$confirmation_data = array(); \n$countable_maximum = NULL; \n$countable_minimum = NULL; ");
//}

function runDebug(){
    
    var fullScript = $('#varWindow').val() + $('#resultsScript').val();
    
      $.ajax({
            type: "POST",           
            data: {script: fullScript },
            url: "{LB}/results/debugMs" 
        }).done(function(response) {                    
            $('#debugWindow').val(response);
        }); 
}

function openEditor(){          

    $('#resultsScript').val( window.opener.$("#script").val());
    $('#confirmationScript').val(  window.opener.$("#confirmation_script").val() );
    redoConf();
    
}

function closeEditor(){
    
    editorObj = {};
    editorObj['resultScript'] = $('#resultsScript').val();
    editorObj['confScript'] = $('#confirmationScript').val();
    
     try {
         window.opener.receiveEditor(editorObj);
     }
     catch (err) {}
     window.close();
     return false;    
}

function redoConf(){
     var newConfFields = $('#confirmationScript').val();
        $.ajax({
            type: "POST",           
            data: {newFields: newConfFields },
            url: "{LB}/scriptEditor/redoConfList" 
        }).done(function(response) {                    
            $('#confField').html(response);
        }); 
}


function saveScript(){
    
    var script = $('#resultsScript').val();
    var confirmation = $('#confirmationScript').val();
    
     $.ajax({
            type: "POST",           
            data: {script: script, confirmation: confirmation},
            url: "{LB}/scriptEditor/saveScript/{id}" 
        }).done(function(response) {                    
            $('#confField').html(response);
        }); 
}
</script>

