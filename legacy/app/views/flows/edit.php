<div class="span3">    
    
    <div class="well well-small">
        
        <h5><i class="icon-search"></i> Inspector </h5>
        <div id='inspector_gadget' class="hide">
            
            <div class="tabbable tabs-below">
               <div class="tab-content">
                    <div class="tab-pane active" id="compInfo">
                        <div id='inspector_info'>
                       </div>
                    </div>
                   
                   <div class="tab-pane" id="compSend">
                       <div id='inspector_sending'>
                       </div>
                    </div>
                   
                   <div class="tab-pane" id="compLink">
                       <p>
                        <button id='linkerButton' type='button' class='btn btn-primary btn-block'> New Link </button><br/>
                        <button id='cancelLink' type='button' class='btn btn-warning hide btn-block'> Cancel link </button>
                       </p>
                    </div>
                   
                    <div class="tab-pane" id="compInstructions">
                        <div id="inspector_instructions">
                        </div>
                    </div>
               </div>

                <ul class="nav nav-tabs">
                    <li class="active"><a href="#compInfo" data-toggle="tab"><i class="icon-info"></i></a></li>                    
                    <li class=""><a href="#compSend" data-toggle="tab"><i class="icon-signal"></i></a></li>                    
                    <li class=""><a href="#compLink" data-toggle="tab"><i class="icon-link"></i></a></li>                    
                    <li class=""><a href="#compInstructions" data-toggle="tab"><i class="icon-cog"></i></a></li>                    
                </ul>
           </div>
            
        </div>
        
    </div>
    
    <div class="well well-small">                        
        <h5><i class="icon-plus"></i> Add Components </h5>
        
        <div class="tabbable tabs-below">
            <div class="tab-content">
                <div class="tab-pane active" id="flowComponents">
                    <div id="toolsDiv">
                     <div class="list-group" id="toolsList">
                        <a href="#" class="list-group-item" id="FLOW_START">
                            <h6 class="list-group-item-heading"><i class="icon-beaker"></i> Start point (sample)</h6>                
                        </a>

                        <a href="#" class="list-group-item" id="MATH">
                            <h6 class="list-group-item-heading"><i class="icon-superscript"></i> Formula</h6>                
                        </a>
                   
                         
                        <a href="#" class="list-group-item" id="FLOW_END" >
                            <h6 class="list-group-item-heading"><i class="icon-flag-checkered"></i> End point (result)</h6>                
                        </a>                        
                    </div>
                    </div>
                </div>                
                <div class="tab-pane" id="assayComponents">
                    <div id="assayDiv">
                        <div class="list-group" id="assayList">
                        {analyticals}
                        </div>
                    </div>
                </div>
                                 
                <div class="tab-pane" id="microComponents">
                <div id="bioDiv">
                     <div class="list-group" id="bioList">
                          <a href="#" class="list-group-item" id="ISOCOUNT">
                            <h6 class="list-group-item-heading"><i class="icon-file-text-alt"></i> NEN-EN-ISO-7218 Count</h6>                
                        </a>
                    </div>
                </div>
                </div>
            </div>
            
             <ul class="nav nav-tabs">
                <li class="active"><a href="#flowComponents" data-toggle="tab"><i class="icon-code-fork"></i> Flow </a></li>
                <li ><a href="#assayComponents" data-toggle="tab"><i class="icon-beaker"></i> Assays </a></li>
                <li ><a href="#microComponents" data-toggle="tab"><i class="icon-bug"></i> Micro bio.</a></li>
       
            </ul>
            </div>                
        </div>       
</div>

<div class="span7">    
    <div class="well well-small">
        
        <h5> Visual </h5>        
        <div id="svgContainer">
            <svg id="svgCanvas" heigth='800'>
                {svg_content}
            </svg>
        </div>
    </div>
</div>


<div id="addMathWindow" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="mathModalWindow">Add math instruction</h3>
  </div>
  <div class="modal-body">

      <form id="addMathForm" class="" action="" method="POST">
               
          <div class="control-group" id="nameCG">
              <label class="control-label" for="name">Available variables</label>              
              <div class="controls">             
                                   
                    <select class="input" id='mathAvailVar'>
                        <option value='test'> test </option>
                    </select>
                  
                  <button id='addToFormula' type='button' class='btn btn-default'> Add to formula</button>
              </div>
          </div>
       
          <div class="control-group" id="nameCG">
              <label class="control-label" for="name">Formula</label>              
              <div class="controls">                    
                  <textarea id='mathFormula' class='textarea-block-level'></textarea>
              </div>
          </div>
          
          <input type='hidden' id='thisMathComponent' value='' />
               
      </form>
      
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
    <button id='mathSaveButton' class="btn btn-primary">Save changes</button>
  </div>
</div>



<script>

var waitingForLink = false;

$(function(){

        
        
    var selectedComp = false;
    
    $('#toolsDiv').slimScroll({
        height: '175px'
    });

    $('#assayDiv').slimScroll({
        height: '175px'
    });
    
    $('#bioDiv').slimScroll({
        height: '175px'
    });
    
           
    $('#linkerButton').on('click', function(){
        
        if(selectedComp != false){
            waitingForLink = true;            
            $('#linkerButton').addClass('disabled');
            $('#cancelLink').show();
        }        
    });
    
           
    $('#cancelLink').on('click', function(){
            window.waitingForLink = false;
            $('#linkerButton').removeClass('disabled');
            $('#cancelLink').hide();
    })

    $('#assayList').on('click', '.list-group-item', function(){
       
        var anaId = $(this).attr('analytical');
        var baseName = $(this).attr('basename');
        var addToLevel = 0;

        var addToLevel = prompt("Add component to the following level",""); 
        var compName = prompt("Name for this analysis?", baseName);
       
           $.ajax({
                type: "POST",                            
                data: { flow: '{flow_id}',
                        component: anaId,
                        level: addToLevel,
                        name: compName},
                url: "{LB}/flowComponents/addAssayComponent/" 
            }).done(function(msg) {            
                reloadSVG();
            });         
    });
        
    $("#toolsList").on('click', '.list-group-item', function(){
            
            var compType = $(this).attr('id');
            var addToLevel = 0;
            
            if( compType !== 'FLOW_START' && compType !== 'FLOW_END'){
                var addToLevel = prompt("Add component to the following level","");
            }
            
            var compName = prompt("Name for analysis?","");
            
            $.ajax({
                type: "POST",                            
                data: { flow: '{flow_id}',
                        component: compType,
                        level: addToLevel,
                        name: compName},
                url: "{LB}/flowComponents/addFlowComponent/" 
            }).done(function(msg) {            
                reloadSVG();
            }); 
                    
     });
     
     $("#bioList").on('click', '.list-group-item', function(){
        
            var compType = $(this).attr('id');
            var addToLevel = 0;            
            var addToLevel = prompt("Add component to the following level","");
            var compName = prompt("Name for analysis?","");
            
         
            $.ajax({
                type: "POST",                            
                data: { flow: '{flow_id}',
                        component: compType,
                        level: addToLevel,
                        name: compName},
                url: "{LB}/flowComponents/addFlowComponent/" 
            }).done(function(msg) {            
                reloadSVG();
            }); 
     });
        
    $("#svgContainer").on('click', 'rect', function(){
       
        var thisSVGelement = $(this).attr('id');
        $('#inspector_gadget').show();
               
        if( window.waitingForLink == true){
            var linkTarget = thisSVGelement.substr(3);                        
            instigateLink(selectedComp, linkTarget );
            return;
        }
                
        var componentId = $(this).attr('id');        
        selectedComp = componentId.substr(3)
        componentLoad(componentId);                       
     });
    
    $('#addToFormula').on('click', function(){        
        var selectedVariable = $('#mathAvailVar').val();
        $('#mathFormula').insertAtCaret(selectedVariable);
    });
    
    $('#mathSaveButton').on('click', function(){
       //get values              
       var selectedComponent = $('#thisMathComponent').val();
       var formulaEntered = $('#mathFormula').val();
       
       $.ajax({
            type: "POST",                            
            data: { componentId: selectedComponent, flow: '{flow_id}', formula: formulaEntered} ,
            url: "{LB}/flowComponents/saveMath/" 
        }).done(function(msg) {                       
            
            if(msg == 'True'){
                $('#mathAvailVar').empty();
                $('#mathFormula').val(''); 
                $('#thisMathComponent').val('');
                $('#addMathWindow').modal('hide');
            } else {
                alert('Error, please check formula');
            }                        
        });                     
       
    });
    
});

function instigateLink(origin, target){
    
    var originLevel = $('#fc_' + origin).attr('level');
    var targetLevel = $('#fc_' + target).attr('level');
    
    var originText = $('#fc_' + origin).attr('name');
    var targetText = $('#fc_' + target).attr('name');
        
    if(originLevel >= targetLevel){
        alert('Can not link to a lower or equal level');
    }
    
    else{
      
        var linkConfirmed = confirm("Link component" + originText + " to " + targetText + "?");       
        if(linkConfirmed){
            waitingForLink = false;
            $('#linkerButton').removeClass('disabled');
            $('#cancelLink').hide();
            
            $.ajax({
            type: "POST",              
            url: "{LB}/flowConnections/addConnection/{flow_id}/" +  origin + "/" + target
        }).done(function(msg) {                                   
            reloadSVG();
        });
                    
        } else {
            alert('Link canceled');
            waitingForLink = false;
            $('#linkerButton').removeClass('disabled');
            $('#cancelLink').hide();
        }                
    }        
}

function componentLoad(componentId){
        
        $.ajax({
            type: "POST",  
            dataType: 'json',
            data: { flow: '{flow_id}',
                    compId: componentId } ,
            url: "{LB}/flowComponents/inspector/" + componentId 
        }).done(function(msg) {                                   
            $('#inspector_sending').html(msg['outputs_window']);            
            $('#inspector_info').html(msg['info_window']);
            $('#inspector_instructions').html(msg['instruct_window']);                        
        });                      
}

function reloadSVG(){    
       $.ajax({
            type: "POST",                            
            data: { flow: '{flow_id}'} ,
            url: "{LB}/flows/reloadSVG/" 
        }).done(function(msg) {                       
            $('#svgCanvas').empty().append(msg);            
            $('#svgContainer').html($('#svgContainer').html());
        });                     
}

function addMath(componentId){

    //load available variables for this component
    //and set up the form to save to the correct     
    $.ajax({
        type: "POST",                            
        dataType: 'json',
        data: { componentId: componentId, flow: '{flow_id}'} ,
        url: "{LB}/flowComponents/loadMathVar/" 
    }).done(function(msg) {                       
            $('#mathAvailVar').empty().append(msg['variables']);
            $('#thisMathComponent').attr('value', componentId);
            $('#addMathWindow').modal('show');            
    });                     


        
}

function saveMath(){
    
}

function updateStd(name, flowComp){
    
    fieldValue = $('#' + name).val();
        
    $.ajax({
        type: "POST",                                  
        data: { componentId: flowComp, fieldName: name, newValue: fieldValue} ,
        url: "{LB}/flowComponents/saveStdValue/" 
    }).done(function(msg) {                       
         $('#' + name).highLight();
    });        
}


    
</script>