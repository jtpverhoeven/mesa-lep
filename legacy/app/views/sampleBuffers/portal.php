<div class="span10">
    <div class="row-fluid">

        <div class="well" id="bufferTable">


            <div id="portalTabs" class="tabbable tabs-above">
                <ul class="nav nav-tabs">
                <li class="{normal_active}"><a href="#sampleTab" data-toggle="tab" tabindex="-1" ><i class="icon-beaker"></i> Algemeen <span id="buffer_size" class="badge badge-info">{count_samples}</span></a></li>
                <li class="{tht_active}"><a href="#thtTab" data-toggle="tab" tabindex="-1" ><i class="icon-time"></i> THT onderzoeken <span id="buffer_size_tht" class="badge badge-info">{count_tht}</span></a></li>
                <li class="{leg_active}"><a href="#legTab" data-toggle="tab" tabindex="-1" ><i class="icon-tint"></i> Legionella <span id="buffer_size_leg" class="badge badge-info">{count_leg}</span></a></li>
                <li class="{rodac_active}"><a href="#rodacTab" data-toggle="tab" tabindex="-1" ><i class="icon-th"></i> Rodac <span id="buffer_size_leg" class="badge badge-info">{count_rodac}</span></a></li>
                </ul>

                <div class="tab-content">

                    <div class="tab-pane {normal_active}" id="sampleTab">
                    <div class="follow-scroll" style="background: white; margin: 10px; padding: 10px; border: 1px solid;"  >
                        <table  >
                            <tbody>
                                <tr>
                                    <td>Met geselecteerd:</td>
                                    <td>
                                        <select id="actionSelect">
                                            <option value="NULL">-- Acties -- </option>
                                            <option value="commit">Goedkeuren &amp; Aanmelden</option>
                                            <option value="samplingDate">Bemonster datum wijzigen</option>
                                            <option value="samplingMethod">Bemonster procedure wijzigen</option>
                                            <option value="moveToTHT">Verplaatsen naar THT lijst</option>
                                            <option value="removeFromBuffer">Verwijderen</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary" type="button" id="runAction">Uitvoeren</button>
                                    </td>
                                    <td>
                                        Sorteer op:    
                                    </td>
                                    <td>
                                        <select class="sortCol">
                                            <option value="sampling_date" {sort_by_date}>Bemonster datum</option>
                                            <option value="client" {sort_by_client}>Klant</option>
                                            <option value="project_name" {sort_by_project_name}>Project naam</option>                             
                                            <option value="project" {sort_by_project}>Project-slug</option>                             
                                        </select>
                                        <select class="sortDir">
                                            <option value="ASC" {sort_asc}>Oplopend</option>                                
                                            <option value="DESC" {sort_desc}>Aflopend</option>                                
                                        </select>
                                    </td>

                                    <td>
                                        <button class="btn btn-primary runSort" type="button" >Uitvoeren</button>
                                    </td>

                                </tr>

                                <tr>
                                    <td> Zoeken:   </td>
                                    <td colspan="7"> <input type="text" class="input" id="bufferSearchInput" placeholder="Zoeken in tabel" /> </td>
                                </tr>

                                <tr>
                                    <td colspan="7">   <button onClick="expandProjectsSelection();" class="btn btn-xs btn-primary" type="button">Selectie uitbreiden naar project</button> </td>
                                </tr>

                            </tbody>
                            </table>
                        </div>
                    <div>
                        <table id="" class="table table-bordered table-condensed buffertbl">
                            <thead>
                                <tr>
                                    <td><a href="#" onClick="expandProjectsSelection();">[Project]</a></td>
                                    <td>Bron</td>
                                    <td>Klant</td>
                                    <td>Project</td>
                                    <td>Project naam</td>
                                    <td>Volgnummer</td>
                                    <td>Bemonster datum</td>
                                    <td>Bemonster procedure</td>
                                    <td>Omschrijving</td>
                                    <td>Monster details</td>
                                    <td>Meta data</td>
                                </tr>
                            </thead>

                            <tbody  id="bufferBody">
                                {rows}
                            </tbody>

                        </table>
                        <a onclick="selectAll();"><img src="{LP}/images/arrow_ltr.png" /> Selecteer alles</a>
                    </div>

                    </div>

                    <div class="tab-pane {tht_active}" id="thtTab">
                    <div class="follow-scroll" style="background: white; margin: 10px; padding: 10px; border: 1px solid;"  >
                    <table>
                    <tbody>
                        <tr>
                            <td>Met geselecteerd:</td>
                            <td>
                                <select id="actionSelectTht">
                                    <option value="NULL">-- Acties -- </option>
                                    <option value="commit">Goedkeuren &amp; THT lijst verplaatsen</option>
                                    <option value="samplingDate">Bemonster datum wijzigen</option>
                                    <option value="samplingMethod">Bemonster procedure wijzigen</option>
                                    <option value="changeInnocTarget">Inzetdatum THT wijzigen</option>
                                    <option value="removeFromBuffer">Verwijderen</option>
                                </select>
                            </td>
                            <td>
                                <button class="btn btn-primary" type="button" id="runActionTht">Uitvoeren</button>
                            </td>
                            <td>
                                Sorteer op:    
                            </td>
                            <td>
                                <select  class="sortCol">
                                    <option value="sampling_date" {sort_by_date}>Bemonster datum</option>
                                    <option value="client" {sort_by_client}>Klant</option>
                                    <option value="project_name" {sort_by_project_name}>Project naam</option>        
                                    <option value="project" {sort_by_project}>Project slug</option>                             
                                </select>
                                <select class="sortDir">
                                    <option value="ASC" {sort_asc}>Oplopend</option>                                
                                    <option value="DESC" {sort_desc}>Aflopend</option>                                
                                </select>
                            </td>

                            <td>
                                <button class="btn btn-primary runSort" type="button">Uitvoeren</button>
                            </td>

                        </tr>

                        <tr>
                            <td> Zoeken:   </td>
                            <td colspan="5"> <input type="text" class="input" id="thtSearchInput" placeholder="Zoeken in tabel" /> </td>
                        </tr>

                        <tr>
                                    <td colspan="7">   <button onClick="expandProjectsSelection();" class="btn btn-xs btn-primary" type="button">Selectie uitbreiden naar project</button> </td>
                                </tr>
                        
                    </tbody>
                    </table>
                    </div>


                        <table id="" class="table table-bordered table-condensed buffertbl" >
                            <thead>
                                <tr>
                                    <td><a href="#" onClick="expandProjectsSelection();">[Project]</a></td>
                                    <td>Bron</td>
                                    <td>Klant</td>
                                    <td>Project</td>
                                    <td>Project naam</td>
                                    <td>Volgnummer</td>
                                    <td>Bemonster datum</td>
                                    <td>Bemonster procedure</td>
                                    <td>Omschrijving</td>
                                    <td>Monster details</td>
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

                    <div class="tab-pane {leg_active}" id="legTab">
                        <div class="follow-scroll" style="background: white; margin: 10px; padding: 10px; border: 1px solid;"  >
                            <table  >
                                <tbody>
                                    <tr>
                                        <td>Met geselecteerd:</td>
                                        <td>
                                            <select id="actionSelectLeg">
                                                <option value="NULL">-- Acties -- </option>
                                                <option value="commit">Goedkeuren &amp; Aanmelden</option>
                                                <option value="samplingDate">Bemonster datum wijzigen</option>
                                                <option value="samplingMethod">Bemonster procedure wijzigen</option>
                                                <option value="adjustLegDetails">Water type (matrix) aanpassen</option>
                                                <option value="removeFromBuffer">Verwijderen</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary" type="button" id="runActionLeg">Uitvoeren</button>
                                        </td>
                                        <td>
                                            Sorteer op:    
                                        </td>
                                        <td>
                                            <select class="sortCol">
                                                <option value="sampling_date" {sort_by_date}>Bemonster datum</option>
                                                <option value="client" {sort_by_client}>Klant</option>
                                                <option value="project_name" {sort_by_project_name}>Project naam</option>                             
                                                <option value="project" {sort_by_project}>Project-slug</option>                             
                                            </select>
                                            <select class="sortDir">
                                                <option value="ASC" {sort_asc}>Oplopend</option>                                
                                                <option value="DESC" {sort_desc}>Aflopend</option>                                
                                            </select>
                                        </td>

                                        <td>
                                            <button class="btn btn-primary runSort" type="button">Uitvoeren</button>
                                        </td>

                                    </tr>

                                    <tr>
                                        <td> Zoeken:   </td>
                                        <td colspan="7"> <input type="text" class="input" id="legSearchInput" placeholder="Zoeken in tabel" /> </td>
                                    </tr>

                                    <tr>
                                        <td colspan="7">   <button onClick="expandProjectsSelection();" class="btn btn-xs btn-primary" type="button">Selectie uitbreiden naar project</button> </td>
                                    </tr>

                                </tbody>
                                </table>
                            </div>
                        <div>
                            <table id="" class="table table-bordered table-condensed buffertbl">
                                <thead>
                                    <tr>
                                        <td><a href="#" onClick="expandProjectsSelection();">[Project]</a></td>
                                        <td>Bron</td>
                                        <td>Klant</td>
                                        <td>Project</td>
                                        <td>Project naam</td>
                                        <td>Volgnummer</td>
                                        <td>Bemonster datum</td>
                                        <td>Bemonster procedure</td>
                                        <td>Omschrijving</td>
                                        <td>Monster details</td>
                                        <td>Watertype/Matrix</td>
                                        <td>Meta data</td>
                                    </tr>
                                </thead>

                                <tbody  id="legBufferBody">
                                    {leg_rows}
                                </tbody>

                            </table>
                            <a onclick="selectAll();"><img src="{LP}/images/arrow_ltr.png" /> Selecteer alles</a>
                        </div>
                    </div>

                    <div class="tab-pane {rodac_active}" id="rodacTab">
                        <div class="follow-scroll" style="background: white; margin: 10px; padding: 10px; border: 1px solid;"  >
                            <table  >
                                <tbody>
                                    <tr>
                                        <td>Met geselecteerd:</td>
                                        <td>
                                            <select id="actionSelectRodac">
                                                <option value="NULL">-- Acties -- </option>
                                                <option value="commit">Goedkeuren &amp; Aanmelden</option>
                                                <option value="samplingDate">Bemonster datum wijzigen</option>
                                                <option value="samplingMethod">Bemonster procedure wijzigen</option>                                                
                                                <option value="removeFromBuffer">Verwijderen</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary" type="button" id="runActionRodac">Uitvoeren</button>
                                        </td>
                                        <td>
                                            Sorteer op:    
                                        </td>
                                        <td>
                                            <select class="sortCol">
                                                <option value="sampling_date" {sort_by_date}>Bemonster datum</option>
                                                <option value="client" {sort_by_client}>Klant</option>
                                                <option value="project_name" {sort_by_project_name}>Project naam</option>                             
                                                <option value="project" {sort_by_project}>Project-slug</option>                             
                                            </select>
                                            <select class="sortDir">
                                                <option value="ASC" {sort_asc}>Oplopend</option>                                
                                                <option value="DESC" {sort_desc}>Aflopend</option>                                
                                            </select>
                                        </td>

                                        <td>
                                            <button class="btn btn-primary runSort" type="button">Uitvoeren</button>
                                        </td>

                                    </tr>

                                    <tr>
                                        <td> Zoeken:   </td>
                                        <td colspan="7"> <input type="text" class="input" id="rodacSearchInput" placeholder="Zoeken in tabel" /> </td>
                                    </tr>

                                    <tr>
                                        <td colspan="7">   <button onClick="expandProjectsSelection();" class="btn btn-xs btn-primary" type="button">Selectie uitbreiden naar project</button> </td>
                                    </tr>

                                </tbody>
                                </table>
                            </div>
                        <div>
                            <table id="" class="table table-bordered table-condensed buffertbl">
                                <thead>
                                    <tr>
                                        <td><a href="#" onClick="expandProjectsSelection();">[Project]</a></td>
                                        <td>Bron</td>
                                        <td>Klant</td>
                                        <td>Project</td>
                                        <td>Project naam</td>
                                        <td>Volgnummer</td>
                                        <td>Bemonster datum</td>
                                        <td>Bemonster procedure</td>
                                        <td>Omschrijving</td>
                                        <td>Ruimte</td>
                                        <td>Monster details </td>
                                        <td>Meta data</td>
                                    </tr>
                                </thead>

                                <tbody  id="rodacBufferBody">
                                    {rodac_rows}
                                </tbody>

                            </table>
                            <a onclick="selectAll();"><img src="{LP}/images/arrow_ltr.png" /> Selecteer alles</a>
                        </div>
                    </div>

                </div>

            </div>


        </div>
    </div>
</div>

<form id="commitForm" action="" method="POST">
    <input type="hidden" id="commit" name="commit" />

    <input type="hidden" id="commit_receive_date" name="commit_receive_date" />
    <input type="hidden" id="commit_receive_time" name="commit_receive_time" />
    
    <input type="hidden" id="date" name="date" />
    <input type="hidden" id="sampling" name="sampling" />
    <input type="hidden" id="storage" name="storage" />
    <input type="hidden" id="waterType" name="watertype" />
    <input type="hidden" id="matrixType" name="matrixtype" />
    <input type="hidden" id="matrixBaseProfile" name="matrixBaseProfile" />
    

</form>


<div id="changeSamplingModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="changeSamplingModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="changeSamplingModalTitle">Bemonster procedure wijzigen</h3>
    </div>
    <div class="modal-body" id="changeSamlingModalBody" >
        <select id="updateSamplingDropper" name="updateSamplingDropper">
            {procedures}
        </select>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CANCEL}</button>
        <button class="btn btn-primary" id="saveSampling"  aria-hidden="true" onclick="commitUpdateSamplingMethod();"><i class="icon-plus"></i> Wijzigen</button>
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




<div id="moveToTHTModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="moveToTHTModalT" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="moveToTHTModalT">Monster(s) veranderen in THT onderzoek </h3>
    </div>
    <div class="modal-body" id="thtModalBody" >

    <form id="moveToTHTform">

        <div class="control-group" id="dillutionEditCG" style="display:block;">
            <label class="control-label" for="dillution_edit_list">THT inzet datum</label>
            <div class="control">
               <input id="moveToTHTdate" class="input-block-level" placeholder="" />
            </div>
        </div>

        <div class="control-group" id="dillutionEditCG" style="display:block;">
            <label class="control-label" for="dillution_edit_list">Opslag temperatuur</label>
            <div class="control">
               <select id="moveToTHTstorage" class="input-block-level" placeholder="">
                    <option value="4">Opslag: 3°C</option>     
                    <option value="0">Opslag: 4°C</option>                    
                    <option value="1">Opslag: 7°C</option>
                    <option value="2">Opslag: -18°C</option>
                    <option value="3">Opslag: Kamertemperatuur</option>
                </select> 
            </div>
        </div>


    </form>

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Sluiten</button>
        <button class="btn btn-primary" onClick="moveToTHT()">Verplaatsen</button>
    </div>
</div>


<div id="setReceiveDate" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="setReceiveDateTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="setReceiveDateTitle">Monster onvangst</h3>
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
                <div style="margin-left: 200px;">
                    <button class="btn" onClick="setToday()">Vandaag</button>
                    <button class="btn" onClick="setTommorow()">Morgen</button>
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
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CANCEL}</button>
        <button class="btn btn-primary" id="saveSampling"  aria-hidden="true" onclick="continueAction();"><i class="icon-plus"></i> OK</button>
    </div>
</div> 


    
<div id="legDetailsModal" class="modal  fade" tabindex="-1" role="dialog" aria-labelledby="setLegDetailsTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="setLegDetailsTitle">Water-type aanpassen</h3>
    </div>
    <div class="modal-body">
    
        
    <div class="control-group" >
            <div class="controls">
                <div class="input-prepend input-append input-block-level">
                    <span class="add-on">Water type</span>
                    <select id="updateWaterTypeSelect" name="updateWaterTypeSelect"class="input-block-level" >
                        {legTypeOptions}
                    </select>
                </div>
            </div>
        </div>
      

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CANCEL}</button>
        <button class="btn btn-primary" id="saveSampling"  aria-hidden="true" onclick="updateLegDetails();"><i class="icon-plus"></i> OK</button>
    </div>
</div>

<script>

    var selected_project = null;
    var commit_type = '';
    var is_loading = false; 
    
    var receive_date = null; 
    var receive_time = null;

    $(function() {

        jQuery.expr[':'].icontains = function(a, i, m) {
            return jQuery(a).text().toUpperCase()
                .indexOf(m[3].toUpperCase()) >= 0;
        };


        $('#receive_date').Zebra_DatePicker({
            format: 'd-m-Y',
            zero_pad: true,
            show_icon: false,
            offset: [10, 200],
            readonly_element: false,           
        });
        
        $("#bufferSearchInput").keyup(function () {
            var data = this.value.split(" ");
            
            //create a jquery object of the rows
            var allData = $("#bufferBody").find("tr");

            var thtRows = $("#bufferBody").find(".datarow");
            if (this.value == "") { 

                allData.show();
                return;
            }
            
            //hide all the rows
            thtRows.hide();

            //Recusively filter the jquery object to get results.
            thtRows.filter(function (i, v) {
                var $t = $(this);

                var hits = [];

                for (var d = 0; d < data.length; ++d) {
                    if ($t.is(":icontains('" + data[d] + "')")) {
                        //push true onto hits array
                        hits.push(true);                        
                    }
                }   

                if (hits.length == data.length) {
                    $t.next().show();
                    return true;
                }
                
                $t.next().hide();            
                return false;
            }).show();
        
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

                var hits = [];
                
                for (var d = 0; d < data.length; ++d) {
                    if ($t.is(":icontains('" + data[d] + "')")) {
                        //$t.next().show();
                        //return true;
                        hits.push(true);           
                    }
                }   

                if (hits.length == data.length) {
                    $t.next().show();
                    return true;
                }
                
                $t.next().hide();            
                return false;
            }).show();
        
        });

        $('#rodacSearchInput').keyup(function () {
            var data = this.value.split(" ");
            
            //create a jquery object of the rows
            var allData = $("#rodacBufferBody").find("tr");

            var thtRows = $("#rodacBufferBody").find(".datarow");
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

        $("#legSearchInput").keyup(function () {
            var data = this.value.split(" ");
            
            //create a jquery object of the rows
            var allData = $("#legBufferBody").find("tr");

            var thtRows = $("#legBufferBody").find(".datarow");
            if (this.value == "") { 

                allData.show();
                return;
            }
            
            //hide all the rows
            thtRows.hide();

            //Recusively filter the jquery object to get results.
            thtRows.filter(function (i, v) {
                var $t = $(this);

                var hits = [];
                
                for (var d = 0; d < data.length; ++d) {
                    if ($t.is(":icontains('" + data[d] + "')")) {                       
                        hits.push(true);     
                    }
                }   

                if (hits.length == data.length) {
                    $t.next().show();
                    return true;
                }
                
                $t.next().hide();            
                return false;
            }).show();
        
        });

        $('#runAction').on('click', function(){
            let action = $('#actionSelect').val();
            if(action == 'NULL'){ return; }
            
            if(action == 'commit'){ 
                commit_type = 'normal';
                promptReceiveDate(); 
            }

            if(action == 'removeFromBuffer'){ removeFromBuffer(); }
            if(action == 'samplingDate'){ updateSamplingDate(); }
            if(action == 'samplingMethod'){ updateSamplingMethod(); }
            if(action == 'moveToTHT'){ $('#moveToTHTModal').modal('show'); }
        });

        $('#runActionTht').on('click', function(){
            let action = $('#actionSelectTht').val();
            if(action == 'NULL'){ return; }
            
            if(action == 'commit'){ 
                //authorizeTht(); 
                commit_type = 'tht';
                promptReceiveDate(); 
            }
            if(action == 'changeInnocTarget'){ changeInnocTarget(); }
            if(action == 'removeFromBuffer'){ removeFromBuffer(); }
            if(action == 'samplingDate'){ updateSamplingDate(); }
            if(action == 'samplingMethod'){ updateSamplingMethod(); }
        });

        $('#runActionLeg').on('click', function(){
            let action = $('#actionSelectLeg').val();
            if(action == 'NULL'){ return; }
            
            if(action == 'commit'){             
                commit_type = 'leg';
                promptReceiveDate(); 
            }
            if(action == 'changeInnocTarget'){ changeInnocTarget(); }
            if(action == 'removeFromBuffer'){ removeFromBuffer(); }
            if(action == 'samplingDate'){ updateSamplingDate(); }
            if(action == 'samplingMethod'){ updateSamplingMethod(); }
            if(action == 'adjustLegDetails'){ adjustLegDetails(); }

        });

        $('#runActionRodac').on('click', function(){
            let action = $('#actionSelectRodac').val();
            if(action == 'NULL'){ return; }
            
            if(action == 'commit'){             
                commit_type = 'rodac';
                promptReceiveDate(); 
            }
            if(action == 'changeInnocTarget'){ changeInnocTarget(); }
            if(action == 'removeFromBuffer'){ removeFromBuffer(); }
            if(action == 'samplingDate'){ updateSamplingDate(); }
            if(action == 'samplingMethod'){ updateSamplingMethod(); }
        });




    $('#metadataModal').on('click', '.revealClicker', function(){
        let targetReveal = 'edit_' + $(this).attr('indexvalue') + '_' + $(this).attr('typereveal');
        let originalHide =  'show_' + $(this).attr('indexvalue') + '_' + $(this).attr('typereveal');
        $('#' + targetReveal).show();
        $('#' + originalHide).hide();
        $('.revealClicker').hide();
    });

    $('.runSort').on('click', function(){               
        let sortcol = $('.sortCol:visible').val();         
        let dir = $('.sortDir:visible').val();                      
        window.location.href = "{LB}/sampleBuffers/portal/" + sortcol + "/" + dir;                
    });
           


    $('#metadataModal').on('click', '.saveClicker', function(){

        let newValueSource = 'input_' + $(this).attr('indexvalue') + '_' + $(this).attr('typereveal');
        let id = $('#' + newValueSource).attr('parentid');
        let originalValue = $('#' + newValueSource).attr('orivalue');
        let newValue =  $('#' + newValueSource).val();
        let changeType =  $(this).attr('typereveal');
        let changeKey =   $(this).attr('keyname');
        let saveByIndex = $(this).attr('save-by-index');
        let index =  $(this).attr('indexvalue')

        $.ajax({
            type: "POST",
            data: { 'id' : id, 'originalValue': originalValue, 'newValue' : newValue, 'changeType' : changeType, 'changeKey' : changeKey, 'index': index, 'saveByIndex' : saveByIndex},
            url: "{LB}/sampleBuffers/changeBufferedMeta"
        }).done(function(msg){
            seeMeta($('#' + newValueSource).attr('parentid'));
        });
    });

    


    $('.buffertbl').on('click', 'input', function(){            

            if (this.checked) {
                checkProjecBox($(this).attr('id'));
                
            }

            checkClientStillSelected();
        });
  });

  function continueAction(){
    
    receive_date = $('#receive_date').val();
    receive_time = $('#receive_time').val();
    
    if(moment(receive_date, 'DD-MM-YYYY',true).isValid() != true)
    {
        alert('Datum niet ingevuld of incorrect, gebruik DD-MM-YYYY');
        return; 
    }

    if(is_loading){        
        console.log('click blocked, already loading');
        return;
    }

    if(commit_type == 'tht'){
        is_loading = true; 
        authorizeTht();
    }

    else{
        is_loading = true; 
        commitToBuffer(); 
    }  
    
  }

  function setToday(){
    $('#receive_date').val('{today}');
  }

  function setTommorow(){
    $('#receive_date').val('{tommorow}');
  }

  function moveToTHT(){
        
        var inzetDatum = $('#moveToTHTdate').val();
        var temperature = $('#moveToTHTstorage').val(); 
        
        if(moment(inzetDatum, 'DD-MM-YYYY',true).isValid() != true)
        {
            alert('Ongeldige datum ingevuld. Gebruik DD-MM-JJJJ ');
            return False; 
        }

        else
        {
            var commitIDs = $("#bufferTable input:checkbox:checked:visible").map(function(){
                      return $(this).val();
                    }).get();

                    var commitString = JSON.stringify(commitIDs);
                    $('#commit').val(commitString);
                    $('#commitForm').attr('action', '{LB}/sampleBuffers/changeToTHT');
                    $('#date').val(inzetDatum);
                    $('#storage').val(temperature);
                    $('#commitForm').submit();
        }

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
                }

            } else{
                return;
            }
        });
    }

    function authorizeTht(){
        var commitIDs = $("#bufferTable input:checkbox:checked:visible").map(function(){
          return $(this).val();
        }).get();



        $('#commit_receive_date').val(receive_date);
        $('#commit_receive_time').val(receive_time);

        var commitString = JSON.stringify(commitIDs);                
        
        $('#commit').val(commitString);
        $('#commitForm').attr('action', '{LB}/sampleBuffers/setAuth');
        $('#commitForm').submit();
    }

    function promptReceiveDate(){
        $('#setReceiveDate').modal('show');
    }

    function commitToBuffer(){
        var commitIDs = $("#bufferTable input:checkbox:checked:visible").map(function(){
          return $(this).val();
        }).get();

        $('#commit_receive_date').val(receive_date);
        $('#commit_receive_time').val(receive_time);

        var commitString = JSON.stringify(commitIDs);
        $('#commit').val(commitString);
        $('#commitForm').attr('action', '{LB}/sampleBuffers/commitFromBuffer');
        $('#commitForm').submit();
    }
    

    function removeFromBuffer(){

        bootbox.prompt("<h3>Verwijderen uit voorportaal</h3> <p>Weet u het zeker, u verwijderd hiermee ook de monsters uit de portal van de klant. De gegevens gaan ook voor de klant verloren! Bevestigings code:<span class='label label-warning'>verwijder</span>", function(result) {
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

    function updateSamplingDate(){

        var commitIDs = $("#bufferTable input:checkbox:checked:visible").map(function(){
          return $(this).val();
        }).get();

        var commitString = JSON.stringify(commitIDs);

        $('#commitForm').attr('action', '{LB}/sampleBuffers/updateSamplingDate');
        $('#commit').val(commitString);

            bootbox.prompt("<p>Nieuwe bemonster datum: dd-mm-YYYY</p>", function(result) {
                if (result != null) {
                    $('#date').val(result);
                    $('#commitForm').submit();
                }
            });
    }

    function updateSamplingMethod(){
        $('#changeSamplingModal').modal('show');
    }

    function commitUpdateSamplingMethod(){

        $('#changeSamplingModal').modal('hide');
        let newSamplingMethod = $('#updateSamplingDropper').val();

        var commitIDs = $("#bufferTable input:checkbox:checked:visible").map(function(){
          return $(this).val();
        }).get();

        var commitString = JSON.stringify(commitIDs);

        $('#commitForm').attr('action', '{LB}/sampleBuffers/updateSamplingMethod');
        $('#commit').val(commitString);
        $('#sampling').val(newSamplingMethod);
        $('#commitForm').submit();
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

        $('.buffertbl input[type=checkbox]').each(function () {

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
    
    $('.buffertbl input[type=checkbox]').each(function () {
        if($(this).attr('project') != selected_project){
            $(this).attr('disabled', true);
        } else{
            $(this).removeAttr("disabled");
        }
    });
    }

    function enableAllClientBoxes(){

        $('.buffertbl input[type=checkbox]').each(function () {
            $(this).removeAttr("disabled");
        });

    }

    function adjustLegDetails()
    {
        $('#legDetailsModal').modal('show');
    }

    function updateLegDetails()
    {
        $('#legDetailsModal').modal('hide');
        
        let matrixBaseProfile = $('#updateWaterTypeSelect').val();

        let waterType = $('#updateWaterTypeSelect').find(':selected').text();

        var matrixType = $('#updateWaterTypeSelect').find(':selected').data('matrix-type');      
        

        var commitIDs = $("#bufferTable input:checkbox:checked:visible").map(function(){
          return $(this).val();
        }).get();

        var commitString = JSON.stringify(commitIDs);

        $('#commitForm').attr('action', '{LB}/sampleBuffers/updateWaterType');
        $('#commit').val(commitString);
        
        $('#waterType').val(waterType);
        $('#matrixBaseProfile').val(matrixBaseProfile)
        $('#matrixType').val(matrixType)

        $('#commitForm').submit();
    }


</script>
