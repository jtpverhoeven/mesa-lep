<div class="span10">
    <div class="row-fluid">

        <div class="well" id="bufferTable">

        <div class="follow-scroll" style="background: white; margin: 10px; padding: 10px; border: 1px solid;"  >
        	 <table id="stagedThtTable">
                <tbody>
                    <tr>
                        <td>Met geselecteerd:</td>
                        <td>
                            <select id="actionSelect">
                                <option value="NULL">-- Acties -- </option>
                                <option value="commit">Aanmelden</option>                                
                                <option value="removeFromBuffer">Verwijderen</option>
                                <option value="changeInnocTarget">Inzetdatum THT wijzigen</option>
                                <option value="printLabel">Print label (Standaard printer)</option>
                                <option value="changeTHTTemp">Bewaar temperatuur wijzigen</option>
                                <option value="changeReceiveDate">Ontvangst datum/tijd wijzigen</option>
                            </select>
                        </td>
                        <td>
                            <button class="btn btn-primary" type="button" id="runAction">Uitvoeren</button>
                        </td>
                        <td style="min-width: 50px;">
                            &nbsp;
                        </td>
                        <td>
                            Sorteer op:    
                        </td>
                        <td>
                            <select id="sortCol">
                                <option value="tht_date">Inzet datum</option>
                                <option value="tht_code">THT code</option>
                                <option value="client_name">Klant naam</option>
                                <option value="project">Project slug</option>
                                <option value="project">Project naam</option>
                            </select>
                            <select id="sortDir">
                                <option value="ASC">Oplopend</option>                                
                                <option value="DESC">Aflopend</option>                                
                            </select>
                        </td>

                        <td>
                            <button class="btn btn-primary" type="button" id="runSort">Uitvoeren</button>
                        </td>

                    </tr>

                    <tr>

                        <td > Zoeken:   </td>
                        <td colspan="6"> <input type="text" class="input" id="thtSearchInput" placeholder="Zoeken in tabel" /> </td>
                      

                    </tr>

                    <tr>
                                    <td colspan="7">   <button onClick="expandProjectsSelection();" class="btn btn-xs btn-primary" type="button">Selectie uitbreiden naar project</button> </td>
                                </tr>
                </tbody>    
                </table> 
                
                </div>
                
                    
                <table id="thtTable" class="table table-bordered table-condensed">
                        <thead>
                            <tr>
                                <td><a href="#" onClick="expandProjectsSelection();">[Project]</a></td>                                  
                                <td>Bron</td>  
                                <td>THT code</td>
                                <td>Klant</td>  
                                <td>Project</td>  
                                <td>Project naam</td>  
                                <td>Bemonster datum</td>  
                                <td>Bemonster procedure</td>  
                                <td>Omschrijving</td>  
                                <td>Monster details</td>  
                                <td>Ontvangst datum [tijd]</td>                                  
                                <td>Inzet datum</td>                                                                  
                                <td>Meta data</td>  
                            </tr>
                        </thead>        

                        <tbody id="thtBody">
                            {tht_rows}
                        </tbody>

                    </table>         

                    <a onclick="selectAll();"><img src="{LP}/images/arrow_ltr.png" /> Selecteer alles</a>
               
        </div>
    </div>
</div>

<div id="changeTempModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="changeTempModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="changeTempModalTitle">Temperatuur wijzigen </h3>
    </div>
    <div class="modal-body"  >
        
        <div class="controls">
            <div class="input-prepend input-append input-block-level">
                <span class="add-on">Bewaar temperatuur</span>
                <select id="tht_storage" name="tht_storage" class="input-block-level">
                    <option selected="SELECTED" value="4">+3°C</option>                    
                    <option value="1">+7°C</option>
                    <option value="2">-18°C</option>
                    <option value="3">Kamer temperatuur</option>
                </select>
            </div>
        </div>

    </div>
    
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Sluiten</button>        
        <button class="btn" onclick="changeStorageTemp()" >Wijzigen</button>        
    </div>
</div>



<div id="metadataModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="metadataModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="metadataModalTitle">Metadata bekijken / wijzigen </h3>
    </div>
    <div class="modal-body" id="metadataModalBody" >


    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Sluiten</button>        
    </div>
</div>


<div id="changeReceiveModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="changeReceiveModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="changeReceiveModalTitle">Ontvangst datum/tijd wijzigen</h3>
    </div>
    
    <div class="modal-body">

        <div class="control-group" >
            <div class="controls">
                <div class="input-prepend input-append input-block-level">
                    <span class="add-on">Ontvangst datum</span>
                    <input id="receive_date" name="receive_date" type= "text" class="input-block-level" placeholder="Ontvangst datum voor geselecteerde monsters" value="" />
                </div>
            </div>
        </div>

        <div class="control-group" >
            <div class="controls">
                <div class="input-prepend input-append input-block-level">
                    <span class="add-on">Ontvangst tijd</span>
                    <input id="receive_time" name="receive_time" type= "text" class="input-block-level" placeholder="Ontvangst tijd voor geselecteerde monsters" value="" />
                </div>
            </div>
        </div>


    </div>
    
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Sluiten</button>        
        <button class="btn btn-primary" id="saveReceiveDate"  aria-hidden="true" onclick="changeReceiveDate();"><i class="icon-plus"></i> OK</button>
    </div>
</div>


<form id="commitForm" action="" method="POST">
    <input type="hidden" id="commit" name="commit" />
    <input type="hidden" id="date" name="date" />
    <input type="hidden" id="sampling" name="sampling" />
    <input type="hidden" id="temp" name="temp" />
    <input type="hidden" id="thtFlag" name="thtFlag" value="yes" />
    <input type="hidden" id="commit_receive_date" name="commit_receive_date" />
    <input type="hidden" id="commit_receive_time" name="commit_receive_time" />    
</form>


<script>
    var selected_project = null;
    var receive_date = null; 
    var receive_time = null;

    $(function() {

    $('#receive_date').Zebra_DatePicker({
        format: 'd-m-Y',
        zero_pad: true,
        show_icon: false,
        offset: [10, 200],
        readonly_element: false,           
    });

    $("#thtSearchInput").keyup(function () {
        var data = this.value.split(" ");
        
        //create a jquery object of the rows
        var allData = $("#thtBody").find("tr");

        var thtRows = $("#thtBody").find(".datarow");
        if (this.value == "") { 

            allData.show();
            return;
        }
        
        //hide all the rows
        thtRows.hide();

        //Recusively filter the jquery object to get results.
        thtRows.filter(function (i, v) {
            var $t = $(this);
            
            for (var d = 0; d < data.length; ++d) {
                if ($t.is(":contains('" + data[d] + "')")) {
                    $t.next().show();
                    return true;
                }
            }   

            $t.next().hide();
        
            return false;
        }).show();
        
    });

    $('#runAction').on('click', function(){
        let action = $('#actionSelect').val();                      
        if(action == 'NULL'){ return; }
        if(action == 'commit'){ commitToBuffer(); }
        if(action == 'removeFromBuffer'){ removeFromBuffer(); }
        if(action == 'changeInnocTarget'){ changeInnocTarget(); }
        if(action == 'printLabel'){  printLabel(); }
        if(action =='changeTHTTemp') { $('#changeTempModal').modal('show'); }
        if(action =='changeReceiveDate') {  $('#changeReceiveModal').modal('show'); }
    });

    $('#runSort').on('click', function(){
        let sortcol = $('#sortCol').val();                      
        let dir = $('#sortDir').val();                      
        window.location.href = "{LB}/sampleBuffers/stagedTht/" + sortcol + "/" + dir;        
    });
           

    $('#metadataModal').on('click', '.revealClicker', function(){
        let targetReveal = 'edit_' + $(this).attr('indexvalue') + '_' + $(this).attr('typereveal');
        let originalHide =  'show_' + $(this).attr('indexvalue') + '_' + $(this).attr('typereveal');       
        $('#' + targetReveal).show();
        $('#' + originalHide).hide();
        $('.revealClicker').hide();
    });


    $('#metadataModal').on('click', '.saveClicker', function(){
        
        let newValueSource = 'input_' + $(this).attr('indexvalue') + '_' + $(this).attr('typereveal');        
        let id = $('#' + newValueSource).attr('parentid');
        let originalValue = $('#' + newValueSource).attr('orivalue');
        let newValue =  $('#' + newValueSource).val();
        let changeType =  $(this).attr('typereveal'); 
        let changeKey =   $(this).attr('keyname'); 
    
        $.ajax({
            type: "POST",
            data: { 'id' : id, 'originalValue': originalValue, 'newValue' : newValue, 'changeType' : changeType, 'changeKey' : changeKey},
            url: "{LB}/sampleBuffers/changeBufferedMeta"
        }).done(function(msg){
            seeMeta($('#' + newValueSource).attr('parentid'));
        });        
    });

    $('#thtTable').on('click', 'input', function(){            

            if (this.checked) {
                checkProjecBox($(this).attr('id'));
                
            }

            checkClientStillSelected();
    });
    


    
 

    });

    function changeReceiveDate()
    {
        var commitIDs = $("#bufferTable input:checkbox:checked:visible").map(function(){
          return $(this).val();
        }).get(); 

        receive_date = $('#receive_date').val();
        receive_time = $('#receive_time').val();

        if(moment(receive_date, 'DD-MM-YYYY',true).isValid() != true)
        {
            alert('Datum niet ingevuld of incorrect, gebruik DD-MM-YYYY');
            return; 
        }

        $('#commit_receive_date').val(receive_date);
        $('#commit_receive_time').val(receive_time);

        var commitString = JSON.stringify(commitIDs);
        $('#commit').val(commitString);
        $('#temp').val($('#tht_storage').val());
        $('#commitForm').attr('action', '{LB}/sampleBuffers/changeReceiveDate');
        $('#commitForm').submit();   
    }


    function changeStorageTemp()
    {
        var commitIDs = $("#bufferTable input:checkbox:checked:visible").map(function(){
          return $(this).val();
        }).get(); 

        var commitString = JSON.stringify(commitIDs);
        $('#commit').val(commitString);
        $('#temp').val($('#tht_storage').val());
        $('#commitForm').attr('action', '{LB}/sampleBuffers/changeTHTTemp');
        $('#commitForm').submit();   
    }

    function printLabel(){
        
        var commitIDs = $("#bufferTable input:checkbox:checked:visible").map(function(){
          return $(this).val();
        }).get(); 

        var commitString = JSON.stringify(commitIDs);
        $('#commit').val(commitString);
        $('#commitForm').attr('action', '{LB}/sampleBuffers/rerunTHTPrint');
        $('#commitForm').submit();   

    }

    function commitToBuffer(){

        var showWarning = false;
        var commitIDs = $("#bufferTable input:checkbox:checked:visible").map(function(){
          return $(this).val();
        }).get(); 

        $('#bufferTable input[type=checkbox]:visible').each(function () {
            if(this.checked){
                //var date = moment(, "DD-MM-YYYY")                
                //var now = moment();
                
                var innoctarget = moment($(this).attr('innocTarget'), "DD-MM-YYYY");                
                var currentTime = moment();

                if (currentTime > innoctarget) {                   
                   //console.log(innoctarget + 'was in the past');
                } else {
                    showWarning = true;                                                    
                }
            }
        });

        var commitString = JSON.stringify(commitIDs);
        $('#commit').val(commitString);
        $('#commitForm').attr('action', '{LB}/sampleBuffers/commitFromBuffer');

        if(showWarning == false){
            $('#commitForm').submit();
        } else{
            bootbox.confirm("<h3> Inzetdatum in toekomst </h3> Een, of meerdere, inzet datums vallen in de toekomst, alle monsters toch aanmelden?" , function(result) {
                if(result === true){
                    $('#commitForm').submit();                    
                }
            });
        }
       
        
    }

    function removeFromBuffer(){
    	        
            bootbox.prompt("<h3>Verwijderen uit THT lijst?</h3> <p>Weet u het zeker, u verwijderd hiermee ook de monsters uit de portal van de klant. De gegevens gaan ook voor de klant verloren! Bevestigings code:<span class='label label-warning'>verwijder</span>", function(result) {
            if (result != null) {
                if (result == 'verwijder') {
                                
                    var commitIDs = $("#bufferTable input:checkbox:checked:visible").map(function(){
                    return $(this).val();
                    }).get(); 

                    var commitString = JSON.stringify(commitIDs);
                    $('#commit').val(commitString);
                    $('#commitForm').attr('action', '{LB}/sampleBuffers/destroy');
                    $('#commitForm').submit();   
                } else {
                    bootbox.alert("<h3>{MESA_CLI_REMOVEERRORTITLE}</h3> <p> Niet verwijderd, bevestigings code correct? </p> ");
                }
            }
            });
    }   

    function changeInnocTarget(){
        bootbox.prompt("<h3>Inzetdatum wijzigen</h3> <p>Voor nieuwe datum (DD-MM-JJJJ) in voor de gewenste inzetdatum van dit THT  monster(s)</p>", function(result) {
            if(result != '' && result != false){                   

                if(moment(result, 'DD-MM-YYYY',true).isValid() == true){
                    var commitIDs = $("#bufferTable input:checkbox:checked:visible").map(function(){
                      return $(this).val();
                    }).get(); 

                    var commitString = JSON.stringify(commitIDs);
                    $('#commit').val(commitString);
                    $('#commitForm').attr('action', '{LB}/sampleBuffers/changeInnocTarget');
                    $('#date').val(result);
                    $('#commitForm').submit();
                } else{
                    alert('Ongeldige datum ingevuld. ');
                    return;
                }

            } else{
                return;
            }            
        });
    }

    function seeMeta(id){
          $.ajax({
            type: "POST",
            data: { 'id' : id},
            url: "{LB}/sampleBuffers/editBufferedMetadata"
          }).done(function(msg){
            $('#metadataModalBody').html(msg);         
            $('#metadataModal').modal('show');            
          });
    }

    // function expandProjectsSelection(){
    //     var projectList = [];

    //     $('#bufferTable input[type=checkbox]:visible').each(function () {
    //         if(this.checked){
    //             let projectSlug = $(this).attr('assayid') +  $(this).attr('client') + $(this).attr('innoctarget');
    //             projectList.push(projectSlug);
    //             //projectList.push($(this).attr('assayid'));
    //         }
    //     });

    //     console.log(projectList);

    //     $('#bufferTable input[type=checkbox]:visible').each(function () {
    //       //var projectId = $(this).attr('assayid');
    //         var projectId = $(this).attr('assayid') +  $(this).attr('client') + $(this).attr('innoctarget');
    //       var arrIndex = $.inArray(projectId, projectList);
    //       if(arrIndex > -1){
    //         $(this).prop( "checked", true );
    //       }
    //   });
    // }

    //  function selectAll(){
    //   $('#bufferTable input[type=checkbox]:visible').each(function () {          
    //     $(this).prop( "checked", true );          
    //   });
    // }

     function expandProjectsSelection(){

        $('#bufferTable input[type=checkbox]:visible').each(function () {
          var projectId = $(this).attr('project');
          //var arrIndex = $.inArray(projectId, projectList);
          if(projectId == selected_project){
            $(this).prop( "checked", true );
          }
      });
    }

    function selectAll(){
    
        expandProjectsSelection();
    }


    function checkProjecBox(id){

        if(selected_project == null){        
            selected_project = $('#' + id).attr('project');            
        }


    }
        

    function checkClientStillSelected(){

        var countedTickedOnes = 0;
        projectList = []

        $('#thtTable input[type=checkbox]').each(function () {

            if(this.checked){
                countedTickedOnes = countedTickedOnes + 1;
                projectList.push($(this).attr('project'));
            }
        });        

        if(countedTickedOnes == 0){
            selected_project = null;
            enableAllClientBoxes();
        } else{
            disableAllNonClientBoxes();
        }

    }

    function disableAllNonClientBoxes(){
    
    $('#thtTable input[type=checkbox]').each(function () {
        if($(this).attr('project') != selected_project){
            $(this).attr('disabled', true);
        } else{
            $(this).removeAttr("disabled");
        }
    });
    }

    function enableAllClientBoxes(){

        $('#thtTable input[type=checkbox]').each(function () {
            $(this).removeAttr("disabled");
        });

    }
</script>