
<div class="span10">

      <div id="sampleLockDiv" class="alert alert-block hide">
        <img id="usageAvatar" class='pull-left img-polaroid avatar-32' style='margin-right: 15px;' src='' />
        <h4>{MESA_SLU_SAMPLELOCKED_TITLE} </h4>
        {MESA_SLU_SAMPLELOCKED_DESCRIPTION} <strong><span id="usageName"></span></strong> {MESA_SLU_SAMPLELOCKED_DESCRIPTION2}
      </div>


    <div id="mainConfWindow" class="row-fluid hide">

      <div class="span12">
        <div class="well">
              <h5 style="padding-bottom: 5px;"> Bevestigingen <span class="pull-right"><a href="#" class="btn btn-small" onClick="closeConf()"><i class="icon-remove"></i> Sluiten</a></span></h5>

             <div id="confirmationModalBody" class="row-fluid">

            </div>
        </div>
      </div>

    </div>


    <div id="mainLookupWindow" class="row-fluid">

    <div class="span4">

        <div class="well well-small">
          <h6><i class="icon-barcode"></i> {MESA_SLU_LOOKUPTITLE} <span class="pull-right">

                      <button id="sampleBackward" class="btn btn-mini" title="Vorige" tabindex="-1" onClick="sampleBackward();"><i class="icon-backward"></i></button>
                      <button id="sampleForward" class="btn btn-mini" title="Volgende" tabindex="-1" onClick="sampleForward();"><i class="icon-forward"></i></button>

              </span></h6>
          <div class="control-group" id="barCG">
            <input class="input-block-level" type="text" placeholder="{MESA_SLU_SCANINFO}"  value="" id="barcodeEntry" />

           </div>
        </div>

        <div id="scannedWell" class="well well-small">
          <h6><i class="icon-search"></i> {MESA_SLU_SCANNEDSAMPLE}
              <span class="pull-right">
                    <auth:sampRevision>
                      <button id="sampleInfoRevisions" class="btn btn-mini" title="{MESA_SLU_SAMPLEREVISIONSTOOLTIP}" tabindex="-1"><i class="icon-rotate-left"></i></button>
                    </auth>

                    <span></span>

                    <auth:editSampleDetails>
                        <button id="editSampleButton" class="btn btn-mini" title="{MESA_SLU_EDITTOOLTIP}" tabindex="-1"><i class="icon-pencil"></i></button>
                    </auth>
              </span>
          </h6>

          <div class="tabbable tabs-below">
            <div class="tab-content">
              
              <div class="tab-pane active" id="sampleTab">
                <span id="sample_block">
                </span>
              </div>
              
              <div class="tab-pane" id="metaDataTab">
                <span id="metadata_block">
                </span>
                
                <p>
                    <auth:editSampleDetails>
                        <a  id="metaDataAddButton" class="btn btn-mini btn-primary" >Metadata toevoegen</a>
                    </auth>
                </p>

              </div>

              <div class="tab-pane" id="productGroupTab">
                <span id="productgroup_block">
                </span>
                
                <p>
                 
                </p>
                
                <auth:editSampleDetails>
                    <a id="changeProductGroupBtn" class="btn btn-mini btn-primary" >Productgroep wijzigen</a>
                    <br /><br />
                    <a id="removeThtFlag" class="btn btn-mini btn-warning" >THT status verwijderen</a>
                </auth>



              </div>

              <div class="tab-pane" id="documentsTab">

                <span id="documents_block">

                </span>                           
              </div>
            </div>
            
            <ul class="nav nav-tabs">
              <li class="active"><a href="#sampleTab" data-toggle="tab" tabindex="-1" ><i class="icon-beaker"></i> Algemeen</a></li>
              <li class=""><a href="#metaDataTab" data-toggle="tab" tabindex="-1" ><i class="icon-folder-open-alt"></i> Metadata</a></li>   
              <li class=""><a href="#productGroupTab" data-toggle="tab" tabindex="-1" ><i class="icon-dropbox"></i> Productgroep / THT</a></li>   
              <li class=""><a href="#documentsTab" data-toggle="tab" tabindex="-1" ><i class="icon-file"></i> Documenten</a></li>   
            </ul>
          </div>
          


          

        </div>

        <div id="projectWell" class="well well-small">
          <h6><i class="icon-suitcase"></i> {MESA_SLU_SAMPLEPROJECT}</h6>
          <span id="project_block">
          </span>

        </div>

    </div>

    <div class="span4">
        <div id="researchWell" class="well well-small">
            <h6><i class="icon-beaker"></i> {MESA_SLU_SAMPLERESEARCH}
                <span id="resModButtons"class="pull-right hide">
                    <auth:editResearchButtons>
                    <button title="Analyse volgorde wijzigen" id="orderResButton" type="button" class="btn btn-mini" tabindex="-1"><i class="icon-refresh"></i></button>
                    <button title="{MESA_SLU_TOOLTIPADDASSAY}" id="addResButton" type="button" class="btn btn-mini" tabindex="-1"><i class="icon-plus"></i></button>
                    <button title="{MESA_SLU_TOOTLIPREMASSAY}" id="delResButton" type="button" class="btn btn-mini" tabindex="-1"><i class="icon-minus"></i></button>
                    <button title="{MESA_SLU_TOOLTIP_CHANGEREF}" id="editRoamingRef" type="button" class="btn btn-mini" tabindex="-1"><i class="icon-star"></i></button>
                    </auth>
                </span>
            </h6>

            <span id="research_block">
            </span>
        </div>

        <div id="progressWell" class="well well-small">
            <h6> <i class="icon-time"></i> {MESA_SLU_ESTIMATEPROGRESS} </h6>
            <div class="progress progress-striped">
                <div class="bar" id="sampleProgress" ></div>
             </div>
        </div>
    </div>

    <div class="span4">
         <div class="well well-small" id='endResultsWell'>
            <h6><i class="icon-star"></i> Resultaat uitgedrukt in
            <span class="pull-right">
                <a href="#" id="peekButton">(?)</a>
            </span>
            </h6>
            <span id="analysisEndResults">
            </span>
         </div>

        <div id="assayContainer" class="well well-small">
            <h6><i class="icon-sort-by-order"></i> {MESA_SLU_LABORATORYRESULTS}

                <span class="pull-right">
                    <auth:assayRevision>
                      <button title="{MESA_SLU_TOOLTIPASSAYREVISIONS}" id="revisionButton" class="btn btn-mini"><i class="icon-rotate-left"></i> </button>
                    </auth>
                    <auth:dilChange>
                      <button title="{MESA_SLU_EDITDILLUTION}" id="editDillutionBtn" class="btn btn-mini"><i class="icon-plus-sign"></i> </button>
                    </auth>
                </span></h6>

            <span id="assay_results">
            </span>
        </div>

        <auth:notes>
            <div class="well well-small" id="sampleNoteWell">
            <h6><i class="icon-pencil"></i> Monster notities</h6>
                {sample_notes}
            </div>
        </auth> 
      </div>
    </div>
</div>

<div id="confirmationModal" class="modal modalWide hide fade" tabindex="-1" role="dialog" aria-labelledby="confirmationModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="confirmationModalTitle">{MESA_SLU_CONFIRMATIONDIALOG}</h3>
    </div>
    <div class="modal-body" id="confirmationModalBodyold" >


    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SLU_CLOSE}</button>
    </div>
</div>


<div id="orderModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="orderModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="orderModalTitle">Analyse volgorde</h3>
    </div>
    <div class="modal-body">
      <div style="padding-left: 20px;" id="orderModalBody">
      </div>

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Annuleren</button>
        <button class="btn btn-primary" id="saveOrderButton" onClick="saveOrder()" aria-hidden="true">Opslaan</button>
    </div>
</div>



<div id="addAssayModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addAssayModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addAssayModalTitle">{MESA_SLU_ADDASSAY}</h3>
    </div>
    <div class="modal-body" id="addAssayModalBody" >

      <p> {MESA_SLU_ADDASSAYEXPLANATION} </p>

                 <div class="tabbable tabs-below">
                        <div class="tab-content">

                            <div class="tab-pane active" id="profile">

                                <div id="profileDiv">

                                <div id="custProfileList" class="list-group">
                                    {cust_profile_list}
                                </div>

                              <!--   <h6><i class="icon-globe"></i> Global profiles </h6> -->

                                <div id="globalProfileList" class="list-group">
                                    {global_profile_list}
                                </div>

                                </div>
                            </div>

                            <div class="tab-pane" id="assay">

                              <div class="control-group" id="matrixSelectorCG">
                                <div class="controls">
                                  <div class="input-prepend input-append input-block-level">
                                    <span class="add-on">Matrix</span>
                                    <select id="matrixSelector" name="matrixSelector" class="input-block-level">{matrix_content}</select>
                                  </div>
                                </div>
                                </div>

                                <table style="width: 100%">
                                  <tr>
                                    <td>
                                      <label for="filterQ">
                                      <input value="q" type="checkbox" class="filterTick" id="filterQ" style="zoom: 1.1;" />
                                      Geaccrediteerd
                                      </label>
                                    </td>
                                      <td style="text-align: right;">
                                        <label for="filterNonQ">
                                        <input value="nq" type="checkbox" class="filterTick" id="filterNonQ" style="zoom: 1.1;" />
                                        Niet Geaccrediteerd
                                        </label>
                                      </td>
                                  </tr>
                                </table>

                                <div class="control-group" id="matrixSearcherCG">
                                <div class="controls">
                                  <div class="input-prepend input-append input-block-level">
                                    <span class="add-on">Zoeken in matrix</span>
                                    <input type="text" id="matrixSearcher" name="matrixSearcher" class="input-block-level" />
                                  </div>
                                </div>
                                </div>

                                <div id="assaysDiv">
                                    <div id="assaysList" class="list-group">
                                    </div>
                                </div>

                            </div>

                            <div class="tab-pane" id="search">
                                
                              <auth:searchAnalysisAdd>
                                <div id="searchDiv">
                                    <div class="well well-small">
                                        <table style="width: 100%">
                                            <tr>
                                                <td><input id="searchTerm" class="input-block-level" type="text" /></td>
                                                <td><button type="button" id="searchButton" class="btn btn-primary input-block-level"><i class="icon-search"></i> {MESA_SAD_SEARCH}</button></td>
                                                <td><select id="searchTermQ" class="input-block-level"><option value="q">Q</option><option value="nq">non-Q</option><option value="a">Alles</option></select></td>
                                            </tr>
                                        </table>


                                    </div>

                                <div id="searchList" class="list-group">
                                </div>


                                </div>
                                </auth>
                            </div>

                        </div>
                        <ul class="nav nav-tabs">
                            <li class="active"><a href="#profile" data-toggle="tab" tabindex="-1" ><i class="icon-sitemap"></i> {MESA_SLU_PROFILE}</a></li>
                            <li class=""><a href="#assay" data-toggle="tab" tabindex="-1" ><i class="icon-random"></i> {MESA_SLU_ASSAYS}</a></li>
                            <auth:searchAnalysisAdd>
                            <li class=""><a href="#search" data-toggle="tab" tabindex="-1" ><i class="icon-search"></i></a></li>
                            </auth>
                        </ul>
                    </div>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"> {MESA_SLU_CLOSE}</button>
    </div>
</div>

<div id="addRoamingModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addRoamingModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addRoamingModalTitle">{MESA_SAD_ADDSINGLE}</h3>
    </div>
    <div class="modal-body" id="addAssayModalBody" >

      <p> {MESA_SAD_ADDSINGLEEXPLANATION}</p>

      <span id="roamingFormSpan">
      </span>

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CANCEL}</button>
        <button class="btn btn-primary" id="saveRoamingButton"  aria-hidden="true"><i class="icon-plus"></i> {MESA_SAD_ADD}</button>
    </div>
</div>


<div id="editSampleModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="editSampleModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="editSampleModalTitle">{MESA_SAD_PROFILEDETAILS}</h3>
    </div>
    <div class="modal-body" id="editSampleModalBody" >



    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SLU_CLOSE}</button>
    </div>
</div>

<div id="revisionModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="revisionModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="revisionModalTitle">{MESA_SLU_REVISIONS}</h3>
    </div>
    <div class="modal-body" id="revisionModalBody" >



    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SLU_CLOSE}</button>
    </div>
</div>

<div id="changeRequestModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="changeRequestModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="changeRequestModalTitle">{MESA_SAD_DUPLICATEASSAYFOUND}</h3>
    </div>
    <div class="modal-body" id="changeRequestModalBody" >
        <p>
            {MESA_SAD_DUPLICATEASSAYEXPLANATION}
        </p>

        <p>
            {MESA_SAD_DUPLICATEASSAYCHOOSEORCANCEL}
        </p>


    </div>
    <div class="modal-footer">
        <button class="btn btn-primary" aria-hidden="true" onClick="addOffenders();"><i class="icon-plus"></i> {MESA_SAD_ADDOFFENDING}</button>
        <button class="btn btn-primary" aria-hidden="true" onClick="replaceOffenders();"><i class="icon-refresh"></i> {MESA_SAD_REPLACEOFFENDING}</button>
        <button class="btn" aria-hidden="true" onClick="cancelAdd();"><i class="icon-remove"></i> {MESA_SAD_CANCEL}</button>
    </div>
</div>

<div id="editDillutionsModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="editDillutionsModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="editDillutionsModalTitle">{MESA_SLU_EDITDILLUTION}</h3>
    </div>
    <div class="modal-body" id="editDillutionsModalBody" >

     <form id="dillutionEditForm">

         <div class="control-group" id="dillutionEditCG" style="display:block;">
             <label class="control-label" for="dillution_edit_list">Verdunningen voor monster</label>
             <div class="control">
                <textarea id="dillution_edit_list" class="input-block-level" placeholder="Voer verdunningen in" rows="5"></textarea>
             </div>
         </div>

     </form>


    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CANCEL}</button>
        <button class="btn btn-primary" id="saveDillutionEditBtn"  aria-hidden="true"><i class="icon-save"></i> {MESA_SLU_DILLUTIONEDITSAVE}</button>
    </div>
</div>

<div id="editRoamingModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="editRoamingModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="editRoamingModalTitle">{MESA_SLU_EDITROAMING}</h3>
    </div>
    <div class="modal-body" id="editRoamingModalBody" >


    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CANCEL}</button>
        <button class="btn btn-primary" id="saveRoamingReferenceBtn"  aria-hidden="true"><i class="icon-save"></i> {MESA_SLU_SAVEEDITROAMING}</button>
    </div>
</div>


<div id="metadataModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="metadataModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="metadataModalTitle">Metadata toevoegen</h3>
    </div>
    <div class="modal-body" id="metadataModalBody" >

       <div class="control-group" id="metadataNameCG" style="display:block;">
           <label class="control-label" for="metadataName">Metadata naam</label>
            <div class="control">
              <input type="text" id="metadataName" class="input-block-level" placeholder="Metadata naam" />
           </div>
       </div>

       <div class="control-group" id="metadataValueCG" style="display:block;">
           <label class="control-label" for="metadataValue">Metadata waarde</label>
            <div class="control">
              <input type="text" id="metadataValue" class="input-block-level" placeholder="Metadata waarde" />
           </div>
       </div>


    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CANCEL}</button>
        <button class="btn btn-primary" aria-hidden="true" onclick="saveMetadata();"><i class="icon-save"></i>Opslaan</button>
    </div>
</div>

<div id="productGroupModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="productGroupModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="productGroupModalTitle">Productgroep wijzigen</h3>
    </div>
    <div class="modal-body" id="productGroupModalBody" >

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CANCEL}</button>
        <button class="btn btn-primary" aria-hidden="true" onclick="updateProductGroup();"><i class="icon-save"></i>Opslaan</button>
    </div>
</div>


<a href="#" id="backToBar">&nbsp; </a>
<script src='{LP}/js/jquery-sortable.js'></script>

<script>


var barcodeEntry = '{entry_scan}';
var loadedSAID = null;
var project_auth = null;
var selected_client = null;
var selected_sample = null;
var previous_client = null;
var selectedBarcode = null;

var roamingAssaySelected = null;
var reqResearch = [];           //requested research
var roamingAssayValues = [];    //roaming analysis information store
var excResearch = [];
var thtflag = false; 

var nReqResearch = [];           //requested research
var nExcResearch = [];
var nRoamingAssayValues = [];
var nReplaceOffenders = false;


//for customization
var offendingAssays = false;
var addableAssays = false;
var pendingProfile = false;
var replacers = false;

//process running?
var transactionRunning = false;


$(function(){

    /* =============
    * UI COMPONENTS INITILIZATION
    * =============   */
    $('#profileDiv').slimScroll({
       height: '175px'
    });

    $('#assaysDiv').slimScroll({
       height: '175px'
    });

    //on entry focus on barcode
    $('#barcodeEntry').focus();

    //check for entry scan
    if(barcodeEntry != 'null'){
        $('#barcodeEntry').focus();
        $('#barcodeEntry').val('{entry_scan}');
        scan(barcodeEntry);
    }

    //bind backToBar to well, go back to the barcode
    $('#backToBar').on('focus', function(){
        $('#barcodeEntry').focus();
    });

    //on click select all the text
    $('#barcodeEntry').on('click', function(){
        this.select();
    });

    $('#barcodeEntry').on('focus', function(){
        this.select();
    });

    //bind enter key to scan bar
    //$('#barcodeEntry').bind('keydown', 'return', function(){
    $('#barcodeEntry').bind('keydown', 'return', function(){
            scan($(this).val());
    });

    //shortcut to go back to scan
    $('*').bind('keydown', 'alt+q', function(){
        $('#barcodeEntry').focus();
    });

    $('#assayContainer').on('keydown', 'input', function(evt){
        if (evt.which == 13) {
             $('#barcodeEntry').focus();
        }
    });

    $(document).keydown(function(event) {
        if (event.altKey && event.which === 88)
        {
            window.location.href = '{LB}/samples/developer/' + selected_sample
            event.preventDefault();
        }
    });

    $('a[data-toggle="tab"]').on('shown', function (e) {
        thisTabTarget = $(e.target).attr('href');
        if(thisTabTarget == '#search'){
            $('#searchTerm').focus();
        }
    });


    //bind the research listing to load assay
    $('#research_block').on('click', '.list-group-item', function(){

      if(checkTransaction()){
        alert('Een moment, er loopt nog een transactie');
        return;
      }

       loadAssay($(this).attr('said'));
        $("#research_block").find('.list-group-item').removeClass('active');
        $(this).addClass('active');
    });



    $('#endResultsWell').on('click', '#confButton', function(){
        //this is the GLOBAL confirmation window
        confirmation($(this).attr('said'), '0', '0', '1' );
    });

    $('#endResultsWell').on('click', '#cancelConfButton', function(){
        //this is the GLOBAL confirmation window
        setConfRequestStatus($(this).attr('said'), 2);
    });
    
    <auth:resetConfirmation>
    $('#endResultsWell').on('click', '#resetConfButton', function(){
        //this is the GLOBAL confirmation window
        setConfRequestStatus($(this).attr('said'), 'reset');
    });    
    </auth>

    $('#endResultsWell').on('click', '#enableConfButton', function(){
        //this is the GLOBAL confirmation window
        setConfRequestStatus($(this).attr('said'), 1);
    });

        $('#confirmationModalBody')
            .off('change.confirmationSave', '.confirmation-input')
            .on('change.confirmationSave', '.confirmation-input', function(){
        //confirmationSave($(this).attr('said'), $(this).attr('fieldname'), $(this).val() );


        var elementId = $(this).attr('id');
        var elementValue = $(this).val();

        isExtendoDate = $(this).hasClass('formDate');
        if (isExtendoDate) {
            var currentYear = new Date().getFullYear()
            if (elementValue.length < 6 && elementValue.length !== 0 ) {
                elementValue = elementValue + '-' + currentYear;
                $(this).val(elementValue);
            }
        }


        // //add check for bounds here
        // if($(this).attr('disposition') == 'n' ){
        //   console.log('in here');
        //   var maxBound = $(this).attr('maxbound');
        //   var thisValNow = $(this).val()
        //
        //   if(parseInt(thisValNow) > parseInt(maxBound)){
        //     alert('Fout: Aantal getest kan niet hoger zijn als aantal kolonies geobserveerd');
        //     $(this).val('');
        //   }
        //
        // }

        if($(this).attr('disposition') == 'pos' ){
          var chainN =  $(this).attr('chainN');
          var mediaN = $(this).attr('mediaid');
          var testedValNow = $('#conf_' + mediaN + '_n').val()
          var thisValNow = $(this).val()

          if(parseInt(thisValNow) > parseInt(testedValNow)){
            alert('Fout: Aantal positief kan niet hoger zijn als aantal getest');
            $(this).val('');
          }

        }

                var saveValue = $(this).val();
                if($(this).data('confirmationQueuedValue') === saveValue){
                    return;
                }
                $(this).data('confirmationQueuedValue', saveValue);

        confirmationSave($(this).attr('said'), $(this).attr('dF'), $(this).attr('rep'), $(this).attr('globalConf'), $(this).attr('fieldname'), $(this).attr('chainN'), saveValue, $(this).attr('mediaid'),
         $(this).attr('placeholder'), $(this).attr('contender'), $(this).attr('disposition'), $(this) );


    });

    $('#confirmationModal').on('hidden', function () {
         loadEndResults(loadedSAID);
    });

    $('#confirmationModal').on('shown', function () {
          $('#confirmationModalBody input:first').focus();
    });


    $('#addResButton').on('click', function(){
        $('#addAssayModal').modal('show');
    });

    $('#orderResButton').on('click', function(){
          openOrderModal();
    });



    $('#editRoamingRef').on('click', function(){

        //HERE
        $.ajax({
            type: "POST",
            url: "{LB}/roamingAnalysis/changeRoamingSettings/" + loadedSAID
        }).done(function(msg) {
            $('#editRoamingModalBody').html(msg);
            $('#editRoamingModal').modal('show');
        });
    });

    $('#delResButton').on('click', function(){

       saidDel = null;
       $("#research_block").find('.list-group-item').each(function(){
           if($(this).hasClass('active')){
               saidDel = $(this).attr('said');
           }
       });

       if(saidDel == null){
           alert('{MESA_SLU_DELASSAYNOTSELECTED}');
       } else {
               bootbox.confirm("<h3> {MESA_SLU_REMOVEASSAYTITLE} </h3> <p> {MESA_SLU_REMOVEAREYOUSURE} </p> <p> {MESA_SLU_REMOVERESULTSWARNING}", function(result) {

                    if(result === true){
                     $.ajax({
                        type: "POST",
                        url: "{LB}/samples/removeAnalysis/" + saidDel + '/' + selected_sample
                    }).done(function(msg) {
                        scan(selectedBarcode);
                    });
                    }
                });
       }
    });

    $("#globalProfileList, #custProfileList").on('click', '.list-group-item', function(){
        validateProfileRequest($(this).attr('profileId'));
    });

    $("#assaysList").on('click', '.list-group-item', function(){
        validateAssayRequest($(this));
    });

    $('#searchList').on('click', '.list-group-item', function(){

       if( $(this).attr('researchType') == 'profile'){
           validateProfileRequest($(this).attr('researchId'));
       }

       if( $(this).attr('researchType') == 'assay'){
            validateAssayRequest($(this));
       }

    });


     $('#searchTerm').keypress(function(e) {
        if(e.which == 13) {
            $('#searchButton').trigger('click');
        }
    });

    $('#searchButton').on('click', function(){
      var searchTerm = $('#searchTerm').val();
      var searchTermQ = $('#searchTermQ').val();

      $.ajax({
          type: "POST",
          data: { search: searchTerm , searchTermQ: searchTermQ, customer_id: selected_client },
          url: "{LB}/samples/searchAvailResearch"
      }).done(function(response) {
          $('#searchList').html(response);
      });
    });


    $('#editSampleButton').on('click', function(){
        loadSampleEditForm();
    });

    $('#revisionButton').on('click', function(){
        $.ajax({
            type: "POST",
            url: "{LB}/changeTracker/changesForAnalysis/" +  selected_sample + '/' + loadedSAID
        }).done(function(msg) {
            $('#revisionModalBody').html(msg);
            $('#revisionModal').modal('show');
        });
    });

    //note update hook
    $('#sampleNoteWell').on('change', 'textarea', function(){
      var value = $(this).val();
      var field = 'sample_notes';
      $.ajax({
        type: "POST",
        data: { id: selected_sample, field: field, value: value},
        url: "{LB}/samples/updateSampleNoteField"
      });
    });


    $('#removeThtFlag').on('click', function(){
        
        $.ajax({
            type: "GET",
            url: "{LB}/samples/drop_tht_flag/" +  selected_sample,            
        }).done(function(msg) {
            scan(selectedBarcode);
        });

    });

    $('#metaDataAddButton').on('click', function(){
      $('#metadataModal').modal('show');
    });

    $('#changeProductGroupBtn').on('click', function(){

        $.ajax({
            type: "POST",
            url: "{LB}/productGroups/updateSamplePG",
            data: { sample_id: selected_sample }
        }).done(function(msg) {
            $('#productGroupModalBody').html(msg);
        });

      $('#productGroupModal').modal('show');
    });




    $('#sampleInfoRevisions').on('click', function(){

        $.ajax({
            type: "POST",
            url: "{LB}/changeTracker/changesForSample/" +  selected_sample
        }).done(function(msg) {
            $('#revisionModalBody').html(msg);
            $('#revisionModal').modal('show');
        });

    });

    $('#editDillutionBtn').on('click', function(){
        editDillutions(loadedSAID);
    });

    $('#saveDillutionEditBtn').on('click', function(){
        saveNewDillutions(loadedSAID);
    });

    //free selected sample
    $(window).on('beforeunload', function() {
          //$.ajax({
          //      type: 'POST',
          //      url: "{LB}/keyrings/removeOwnLock/SAMPLE/" +  selected_sample ,
          //      async:false,
          //  });

        navigator.sendBeacon("{LB}/keyrings/removeOwnLock/SAMPLE/" +  selected_sample);
    });

    $('#matrixSelector').on('change', function(){
        clearTableFilter();
        loadInMatrix();
    });

    $('.filterTick').click(function() {
        clearTableFilter();  
        if ($(this).is(':checked')) {
          disableFilters();
          applyMatrixFilter($(this).val());
        } else{
          disableFilters();
        }


    });

    $('#peekButton').on('click', function(){
        window.location.href = '{LB}/results/peek/' + loadedSAID
    });

    loadInMatrix();

     $('#matrixSearcher').on("keyup", function() {
        var value = $(this).val();
        liveSearchTable(value, 'assaysList');
    });

    
    $("#roamingFormSpan").on('keydown', '.numerical-only-filter', function(e) {                      
        if (e.altKey || e.ctrlKey || e.which<28) return true;
        return "0123456789".indexOf(e.key) >= 0;        
    });

    //initial hide
    hideAll();


});


function validateAssayRequest(selectedAssay){

    roamingAssaySelectedId = $(selectedAssay).attr('assayId');

    roamingAssaySelected = {
        'assayid' : $(selectedAssay).attr('assayId'),                               
        'assaytype' : $(selectedAssay).attr('assaytype'),
        'dillution' : $(selectedAssay).attr('dillution'),
        'replicates' :$(selectedAssay).attr('replicates')
    }


    //roamingAssaySelected = $(selectedAssay).attr('assayId');

     $.ajax({
        type: "POST",
       data: {requestedId: roamingAssaySelectedId, requestedType: 'assay', alreadyAssigned: reqResearch, excluded: excResearch},
        dataType: 'json',
        url: "{LB}/assayProfiles/validateAssayRequest"
    }).done(function(response) {

        if(response['valid'] == true ){

            if(roamingAssaySelected['assaytype'] == '2')
            {
                requestRoamingAssay(roamingAssaySelected,undefined,true);
                return;
            }

            requestRoamingAssay(roamingAssaySelected);

           
        }
        
        else{
            alert('{MESA_SAD_ASSAYALREADYSELECTED}');
        }

    }).error(function(response){

    });

}


function validateProfileRequest(requestedProfile){

     //check if profile exists already
     var profileAlreadySelected = false;

     $.each(reqResearch, function(i, request) {
         if(request !== undefined ){
            if(request['resType'] == 'profile' && request['resId'] == requestedProfile){
                profileAlreadySelected = true;
            }
         }
     });

     //if so abort the request and give a warning
     if(profileAlreadySelected == true){
        alert('{MESA_SAD_PROFILEALREADYSELECTED}');
        return false;
     }

     //profile doesnt exist yet, so lets check if it conflicts with anything else
    $.ajax({
        type: "POST",
        data: {requestedId: requestedProfile, requestedType: 'profile', alreadyAssigned: reqResearch, excluded: excResearch},
        dataType: 'json',
        url: "{LB}/assayProfiles/validateAssayRequest"
    }).done(function(response) {

        addProfile(requestedProfile);

        if(response['valid'] == true){
            endAdd();
        } else{
            pendingProfile = requestedProfile;
            addableAssays = response['addable'];
            offendingAssays = response['offending'];
            replacers = response['replacers'];

            $('#addAssayModal').modal('hide');
            $('#changeRequestModal').modal('show');

        }
    });
}

function requestRoamingAssay(selectedAssay, editId, autoapply){
    //add an assay to the requested research list
    //we need to get ifnormation from the user on
    //dillution, replicates and reference / reference scope
    //store this in roamingAssayValues
    console.log(selectedAssay);        
    console.log('edit id: ' + editId);

    roamingAssaySelected = selectedAssay.assayid;
    assayType = selectedAssay.assaytype; 

    //check if the user wanted to edit a profile assay, if so
    //load in the standard value for that research profile setting
    var loadInDefaults
    if(editId !== undefined ){
        loadInDefaults = editId;
    } else{
        loadInDefaults = false;
    }



     $.ajax({
        type: "POST",
        data: {loadInDefaults: loadInDefaults},
        url: "{LB}/samples/generateRoamingForm/" + roamingAssaySelected + '/' + selected_client
     }).done(function(msg) {
         $('#roamingFormSpan').html(msg);
         

         $('#roamingDillutionForm').hide();
         $('#roamingReplicatesForm').hide();
        
         if(selectedAssay.dillution == '1'){
             //$('#roam_dillutionCG').show();
             $('#roamingDillutionForm').show();
         }  else{
             $('#roam_dillution').val('0=1');
         }

         if(selectedAssay.replicates == '1'){             
             $('#roamingReplicatesForm').show();

         }

        $('#addAssayModal').modal('hide');
        if(autoapply == true)
        {            
            saveRoamingAssay();
            return;
        }

        else
        {         
            $('#addRoamingModal').modal('show');
        }

    });

    //prepare the roaming dialog based on the settings for this assay

}

function saveRoamingAssay(){

    var selectedSource = $('#reference_source').val();
        var setReference = $('#ref_kve').val(); 
        var referenceIsRestricted = $('#ref_kve').hasClass('numerical-only-filter');
        
        if(selectedSource == '' && setReference != '' )
        {

         
            alert('Een referentie bron is verplicht');
            return;
         
            
        }

        if(selectedSource != '' && setReference == '')
        {

            if(referenceIsRestricted == true)
            {
                alert('Een bron is bij een referentie-loos onderzoek niet nodig');
                return; 
            }
                
            
        }

    var setObj = {};
    $('#roamingForm *').filter(':input').each(function () {
        setObj[$(this).attr('id')] = $(this).val();
    });

    setObj['assay_id'] = roamingAssaySelected;    
    setObj['reference_source'] =  $('#reference_source').val(); 

    nReqResearch = [];           //requested research
    nRoamingAssayValues = [];    //roaming analysis information store

    nReqResearch[0] = {resType: 'assay', assayId: roamingAssaySelected};
    nRoamingAssayValues[0] = setObj;

    $('#addRoamingModal').modal('hide');

    $.ajax({
        type: "POST",
         data: {requested: nReqResearch, roaming: nRoamingAssayValues , sample:selected_sample },
        url: "{LB}/samples/addNewResearch"
    }).done(function(req) {
        scan(selectedBarcode);
    });
}


function scan(barcode){

    if(checkTransaction()){
      alert('Een moment, er loopt nog een transactie');
      return;
    }

    //for sample lock release
    var previous_sample = selected_sample;
    var previous_client = previous_client;
    cls();
    transaction(true);

    $.ajax({
        type: "POST",
        dataType: 'json',
        data: {previous_sample: previous_sample},
        url: "{LB}/samples/doLookup/" + barcode
    }).done(function(msg) {

        if(msg['error'] == 'BAR_WRONG'){
          selected_barcode = null;
          selected_sample = null;
          hideAll();
          $('#resModButtons').hide();
          $('#barCG').addClass('error');
          $.playSound('{LP}/snd/scanDeny.wav');
          transaction(false);
          return;
        }

        //set blocks
        showAll();
        $.playSound('{LP}/snd/scan.wav');
        $('#barCG').removeClass('error');
        $('#sample_block').html(msg['sample_block']);
        $('#metadata_block').html(msg['metadata_block']);
        $('#productgroup_block').html(msg['productgroup_block']);
        $('#documents_block').html(msg['documents_block']);
        $('#project_block').html(msg['project_block']);
        $('#research_block').html(msg['research_block']);
        $('#sample_notes').val(msg['sample_note']);

        $('#portal_notes').html(msg['portal_notes']);
        
        //if msg['portal_notes'] is empty, hide portal notes
        if(msg['portal_notes'] == '' || msg['portal_notes'] == null)
        {
            $('#portal_notes_div').hide();
        }
        else
        {
            $('#portal_notes_div').show();
        }

        if(msg['was_tht'] == true)
        {
            $('#barcodeEntry').val(msg['barcode']);
        }
        

        $('#sampleProgress').css('width', msg['progress'] +'%');
        $('#resModButtons').show();
        selected_client = msg['selected_client'];
        selected_sample = msg['selected_sample'];
        project_auth = msg['project_auth'];
        selectedBarcode = barcode;

        //set research + exc research
        reqResearch = msg['reqResearch'];
        excResearch = msg['excResearch'];

        //check for tht
        if(msg['was_tht'] == true){
            $('#removeThtFlag').show();
        } else{
            $('#removeThtFlag').hide();
        }

        var focalElement = false;

        if(msg['selected_follow'] != false){
            focalElement = msg['selected_follow'];
        }

        if(msg['selected_analysis'] != false){
            $("#research_block").find('.list-group-item').removeClass('active');
            $('#research_' + msg['selected_analysis']).addClass('active');
            loadAssay($('#research_' + msg['selected_analysis']).attr('said'), focalElement);
        }

        if(msg['lock_object']['locked'] == true){
            sampleLockCall(msg['lock_object']['locked_by_id'], msg['lock_object']['locked_by_name'],  msg['lock_object']['locked_by_avatar']);
        } else{
            $('#sampleLockDiv').hide();

            if(project_auth == true){
                $('#resModButtons').hide();
                $('#editSampleButton').hide();
            } else{
                $('#resModButtons').show();
                $('#editSampleButton').show();
            }
        }

        updateAddAssayWin();
        transaction(false);
        checkClientIsPortalUser(selected_client);
        
        

    }).fail(function(msg){

        selected_barcode = null;
        selected_sample = null;
        hideAll();
        $('#resModButtons').hide();
        var mesaError = msg.responseText;
        if(mesaError == 'BAR_WRONG'){
          $('#barCG').addClass('error');
          $.playSound('{LP}/snd/scanDeny.wav');
        }
    });
}

function updateAddAssayWin(){
    //loads client research profiles
    $.ajax({
        type: "POST",
        url: "{LB}/researchProfiles/fetchCustomerProfiles/" + selected_client
    }).done(function(options) {
        $("#custProfileList").empty().append(options);
    });

}


function loadAssay(said, focalElement){


    loadedSAID = said;

    $.ajax({
        dataType: "json",
        type: "POST",
        url: "{LB}/results/buildAssayPanel/" + said
    }).done(function(msg) {

        //set block
        loadEndResults(said);
        $('#endResultsWell').fadeIn();
        $('#assayContainer').fadeIn();
        $('#assay_results').html(msg['panel']);

        //check if add dillution is needed
        //

        if((msg['uses_dillution'] == true || msg['uses_replicates'] == true ) && msg['is_authorized'] == false){
            $('#editDillutionBtn').show ();
        } else{
            $('#editDillutionBtn').hide();
        }

        if(msg['is_roaming'] == true && msg['is_authorized'] == false){
            $('#editRoamingRef').show ();
        } else{
            $('#editRoamingRef').hide();
        }

        $('#assayContainer').highLight();

        //focus on element if set
        if(focalElement != false || focalElement != undefined){
            $("*[dbrid='" + focalElement +"']").eq(0).focus();
        }

    }).fail(function(msg){
        cls();
    });
}

function revealHide(reveal, hide){
  

  if(reveal != false){

    $('#' + reveal).show();
    $('#' + reveal).removeClass('hidden');
  }

  if(hide != false){
    $('#' + hide).hide();
  }
}

function resultHandler(fieldId){

    transaction(true);

    var dbRid = $('#' + fieldId).attr('dbrid');
    var fieldName = $('#' +fieldId).attr('name');
    var fieldValue = $('#' + fieldId).val();

    $.ajax({
            type: "POST",
            dataType: 'json',
            data: { dbRid: dbRid, fieldName: fieldName, fieldValue: fieldValue},
            url: "{LB}/results/resultsHandler/"
    }).done(function(resultUpdate) {

        transaction(false);

        //confirmation question
        if(resultUpdate['askForConf'] == true){
          console.log('asking for conf');
          //var r = confirm("Bevestiging test wordt ingezet?");
          bootbox.confirm("<h3> Bevestiging inzetten? </h3> Wilt u de bevestiging methodes starten voor deze analyse? " , function(result) {
            setConfFlag(result, resultUpdate['said'], resultUpdate['ajaxUpdateBlob'], resultUpdate['reveal'], resultUpdate['hide'] );
            $('#barcodeEntry').focus();
          });


        } 

        else if(resultUpdate['autoConfApplied'] == true){
            console.log('auto conf applied');            
            loadAssay(loadedSAID, false);
        }

        else{
            console.log('no ask for conf, parsing end result');
            revealHide(resultUpdate['reveal'], resultUpdate['hide']);
            parseEndResult(resultUpdate['result'], loadedSAID);
        }

    });
}

function setConfFlag(flag, said, ajaxUpdateBlob, reveal = false, hide = false ){

    $.ajax({
        type: "POST",
        dataType: 'json',
        data: { said: said, flag: flag},
        url: "{LB}/sampleAnalysis/setConfFlag/" + flag + '/' + said
    }).done(function(resultUpdate) {

        loadAssay(loadedSAID, false);
        //revealHide(reveal, hide);
        
        //if this conf flag request was a result of a setConfFlag prompt
        //as opposed to a button, also process the ajaxUpdateBlob
        // if(ajaxUpdateBlob == false){
        //     updateBinds(ajaxUpdateBlob);
        // } else{
        //     //loadEndResults(loadedSAID);
           
        // }



    });
}

function updateBinds(bindObj){

    // $.each(bindObj, function(fieldId, fieldValue) {
    //     var currFieldValue = $('#' + fieldId).val();
    //     if(currFieldValue != fieldValue){
    //         $('#' + fieldId).val(fieldValue);
    //         $('#' + fieldId).highLight();
    //     }
    // });

    loadEndResults(loadedSAID);
}

function parseEndResult(msg, said)
{
    $('#analysisEndResults').html(msg['panel']);
    $('#endResultsWell').highLight();

    if(msg['askForMetaParentConfirmation'] == true){
        //var r = confirm("Bevestiging test inzetten voor overkoepelende meta analyse?");
        bootbox.confirm("<h3> Bevestiging test inzetten voor overkoepelende meta analyse? </h3> Deze analyse is gekoppeld aan een meta-analyse, en de huidige resultaten geven aan dat een bevestigings nodig is. Inzetten? " , function(result) {
            setConfRequestStatus(msg['metaParent'],result);
        });

    }

    if(msg['askForMetaConfirmation'] == true){
        //var r = confirm("Bevestiging test wordt ingezet?");
        bootbox.confirm("<h3> Bevestiging test wordt ingezet? </h3>  Wilt u de bevestiging methodes starten voor deze analyse?  " , function(result) {
            setConfRequestStatus(said,result);
        });
    } else{

        $.each(msg['messageBag'], function(bagId, bagMsg) {
            alert(bagMsg);
        });

        //update is ready overview icons
        if(msg['isReady'] == true){  $('#readyicon_' + said).show(); }
        else{ $('#readyicon_' + said).hide(); }

        //update conf requested overview icons
        if(msg['confRequested'] == true){
            $('#confdenyicon_' + said).hide();
            $('#conficon_' + said).show();
        }
        else{
            $('#conficon_' + said).hide();
            $('#confdenyicon_' + said).hide();

            if(msg['confDenied'] == true){
                $('#confdenyicon_' + said).show();
            }
        }
    }

    transaction(false);
}

function loadEndResults(said){

    transaction(true);

    $.ajax({
        type: "POST",
        dataType: "json",
        url: "{LB}/results/renderEndResults/" + said
    }).done(function(msg) {
        parseEndResult(msg, said);
    });
}

function confirmation(said, dill, rep, globalConf, refresh = false){

    console.log('Launching confirmation for said: ' + said + ' dillution:' + dill + ' and replicate:' + rep);
    if(globalConf == '1'){
      dill = 'global';
    }

    return $.ajax({
        type: "POST",
        data: {dF: dill, rep: rep, globalConf: globalConf},
        url: "{LB}/confirmations/confirmationViewer/" + said + '/' + dill + '/' + rep,
    }).done(function(confwindow) {
        $('#confirmationModalBody').html(confwindow);
        $('#mainLookupWindow').hide();
        $('#mainConfWindow').show();

        if(refresh == false){
          $('#confirmationModalBody input:first').focus();
        }

                else{
                    $('.confirmation-input').eq(refresh).focus();
                }

        });
}

function findConfirmationThtFieldForSave(chainN, mediaId){
    var thtField = null;

    document.querySelectorAll('input[disposition="tht"]').forEach(function(field){
        if(thtField === null && field.getAttribute('mediaid') === String(mediaId) && field.getAttribute('chainN') === String(chainN)){
            thtField = field;
        }
    });

    return thtField;
}

function updateConfirmationFieldDependencies(request){
    if(request.disposition === 'tht' && typeof updateAssuranceForm === 'function'){
        return updateAssuranceForm(request.said, request.value, request.chainN, request.media);
    }

    if(request.disposition === 'inzet' && typeof updateOutOfDateHere === 'function'){
        var thtField = findConfirmationThtFieldForSave(request.chainN, request.media);
        if(thtField !== null){
            return updateOutOfDateHere(thtField, request.value);
        }
    }

    return $.when();
}

var confirmationFieldSaveQueue = [];
var confirmationFieldSaveInProgress = false;

function finishConfirmationFieldSave(){
    confirmationFieldSaveInProgress = false;
    processConfirmationFieldSaveQueue();
}

function clearConfirmationQueuedValue(request){
    if(request.sourceElement){
        $(request.sourceElement).removeData('confirmationQueuedValue');
    }
}

function processConfirmationFieldSaveQueue(){
    if(confirmationFieldSaveInProgress || confirmationFieldSaveQueue.length === 0){
        return;
    }

    var request = confirmationFieldSaveQueue.shift();
    confirmationFieldSaveInProgress = true;
    var dependencyRequest = updateConfirmationFieldDependencies(request);

    $.ajax({
        type: "POST",
        data: { said: request.said, dF:request.dF, rep:request.rep, globalConf:request.globalConf, field: request.field, chainN: request.chainN, value: request.value, placeholder: request.placeholder, mediaId: request.media, contender: request.contender, disposition: request.disposition},
        url: "{LB}/confirmations/saveField"
    }).done(function() {
        $.when(dependencyRequest).always(function(){
            var renderRequest = confirmation(request.said, request.dF, request.rep, request.globalConf, request.nextFieldIndex);
            if(renderRequest && typeof renderRequest.always === 'function'){
                renderRequest.fail(function(){
                    clearConfirmationQueuedValue(request);
                });
                renderRequest.always(finishConfirmationFieldSave);
            } else {
                finishConfirmationFieldSave();
            }
        });
    }).fail(function(){
        clearConfirmationQueuedValue(request);
        $.when(dependencyRequest).always(finishConfirmationFieldSave);
    });
}

function confirmationSave(said, dF, rep, globalConf, field, chainN, value, media, placeholder, contender, disposition, currentElement = null){
    var nextFieldIndex = currentElement ? $('.confirmation-input').index(currentElement) + 1 : 0;

    confirmationFieldSaveQueue.push({
        said: said,
        dF: dF,
        rep: rep,
        globalConf: globalConf,
        field: field,
        chainN: chainN,
        value: value,
        media: media,
        placeholder: placeholder,
        contender: contender,
        disposition: disposition,
        sourceElement: currentElement && currentElement.length ? currentElement[0] : currentElement,
        nextFieldIndex: nextFieldIndex
    });

    processConfirmationFieldSaveQueue();
}

function removeContender (said, df, rep, contender, globalConf){

  $.ajax({
      type: "POST",
      url: "{LB}/confirmations/removeContender/" + said + '/' + contender + '/' + df + '/' + rep
  }).done(function(msg) {
      confirmation(said, df, rep, globalConf);
  });
}

function addContender(said, df, rep, globalConf)
{
    $.ajax({
      type: "POST",
      url: "{LB}/confirmations/addContender/" + said + '/' + df + '/' + rep
  }).done(function(msg) {
      confirmation(said, df, rep, globalConf);
  });
}

function loadSampleEditForm(){

    if(selected_sample == null){
        alert('{MESA_SLU_NOSAMPLELOADED}');
        return;
    }

    $.ajax({
            type: "POST",
            url: "{LB}/samples/renderEditForm/" + selected_sample
    }).done(function(editWindow) {
        $('#editSampleModalBody').html(editWindow);

        $("#editSampleModalBody").find('input').unbind('change');
        $("#editSampleModalBody").find('input, textarea, select').on('change', function(){

            var id = selected_sample;
            var field = $(this).attr('id');
            var value = $(this).val();
            var isExtra = false;

            if( $(this).hasClass('sample-extra-field')){
                isExtra = true;
            }

        $.ajax({
                type: "POST",
                data: { id: id , field: field , value:  value, extra: isExtra},
                url: "{LB}/samples/updateSampleField"
        }).done(function(confwindow) {
            scan(selectedBarcode);
        });


        });
        $('#editSampleModal').modal('show');
    });

}

function sampleLockCall(user_id, user, user_avatar){
    $.playSound('{LP}/snd/scanLocked.wav');
    $('#usageName').html(user);
    $('#usageAvatar').attr('src',user_avatar );
    $('#sampleLockDiv').show();

    $('#resModButtons').hide();
    $('#editSampleButton').hide();

}


function hideAll(){
    $('#editRoamingRef').hide();
    $('#scannedWell').hide();
    $('#projectWell').hide();
    $('#researchWell').hide();
    $('#progressWell').hide();
    $('#endResultsWell').hide();
    $('#assayContainer').hide();
    $('#sampleNoteWell').hide();

    cls();
}

function showAll(){
    $('#scannedWell').show();
    $('#projectWell').show();
    $('#researchWell').show();
    $('#progressWell').show();
    $('#sampleNoteWell').show();    
}


function cls(){

    roamingAssaySelected = null;
    reqResearch = [];           //requested research
    roamingAssayValues = [];    //roaming analysis information store
    excResearch = [];

    nReqResearch = [];
    nExcResearch = [];
    nRoamingAssayValues = [];
    nReplaceOffenders = false;

    $('#sample_block').html('');
    $('#project_block').html('');
    $('#research_block').html('');
    $('#assay_results').html('');
    $('#analysisEndResults').html('');
}

function addProfile(profileId){
    nReqResearch[1] = {resType: 'profile', resId: profileId};
    nExcResearch[1] = [];
}

function addOffenders(){
    //var thisResult;
    var thisFollow;
    thisFollow = 1;
    //thisResult = addProfile(pendingProfile);

    $.each(offendingAssays, function(inFollow, offending) {
        $.each(offending, function(index, assayId){
            excludeFromProfile(pendingProfile, assayId, thisFollow);
        });
    });


    endAdd();
}

function excludeFromProfile(profileId, assayId, follow_no){
    nExcResearch[1].push(assayId);
}

function replaceOffenders(){

    nReplaceOffenders = true;
    endAdd();
}

function cancelAdd(){

    $('#addAssayModal').modal('hide');
    $('#changeRequestModal').modal('hide');
    scan(selectedBarcode);
}

function endAdd(){
    $('#addAssayModal').modal('hide');
    $('#changeRequestModal').modal('hide');

    offendingAssays = false;
    addableAssays = false;
    replacers = false;

    $.ajax({
        type: "POST",
         data: {requested: nReqResearch, roaming: nRoamingAssayValues, excluded: nExcResearch, sample:selected_sample, replace: nReplaceOffenders },
        url: "{LB}/samples/addNewResearch"
    }).done(function(req) {
        scan(selectedBarcode);
    });

}

function editDillutions(said){

    $.ajax({
        type: "POST",
        dataType: "json",
        url: "{LB}/results/getCurrentDillutions/" + said + '/JSON/DESC'
    }).done(function(msg) {
        var dillutionFix = msg['dillutions'].replace(/&#13;/g,"\n");
        $('textarea#dillution_edit_list').val(dillutionFix);
        $('#editDillutionsModal').modal('show');
    });
}

function saveNewDillutions(said){

    var newDillutions = $('#dillution_edit_list').val();
    $('#editDillutionsModal').modal('hide');

    $.ajax({
        type: "POST",
        data: {dillutions: newDillutions},
        url: "{LB}/results/saveNewDillutions/" + said
    }).done(function() {
        loadAssay(loadedSAID, false);
    });

}

function saveRoamingRefChange(){

        
    var selectedSource = $('#reference_source').val();
    var setReference = $('#ref_kve').val(); 
    var referenceIsRestricted = $('#ref_kve').hasClass('numerical-only-filter');
    
    if(selectedSource == '' && setReference != '' )
    {

        
        alert('Een referentie bron is verplicht');
        return;
        
        
    }

    if(selectedSource != '' && setReference == '')
    {

        if(referenceIsRestricted == true)
        {
            alert('Een bron is bij een referentie-loos onderzoek niet nodig');
            return; 
        }
            
        
    }
    
    var refData = $('#roamingEditForm').serialize();



    $.ajax({
        type: "POST",
        data: refData,
        url: "{LB}/roamingAnalysis/saveRoamingChange/" + loadedSAID
    }).done(function(msg) {
        $('#editRoamingModal').modal('hide');
    });

}

function setConfRequestStatus(said, confFlag){

    $.ajax({
        type: "POST",
        url: "{LB}/sampleAnalysis/setConfFlag/" + confFlag + "/" + said
    }).done(function(msg) {
        loadAssay(loadedSAID, false);
    });
}

function transaction(status){
    transactionRunning = status;
}

function checkTransaction(){
    return transactionRunning;
}

function closeConf(){
  $('#mainConfWindow').hide();
  $('#confirmationModalBody').html('');
  $('#mainLookupWindow').show();
  loadEndResults(loadedSAID);
}

function openOrderModal(){

  $("#assaySorter").sortable("destroy");

  $.ajax({
      type: "POST",
      url: "{LB}/sampleAnalysis/analysisOrderList/" + selected_sample
  }).done(function(msg) {
      $('#orderModalBody').html(msg);
      $("ol.sorterList").sortable({  group: 'serialization'});
      $('#orderModal').modal('show');
  });
}

function saveOrder(){
  var sortData = $('.sorterList').sortable("serialize").get();
  var sendData = JSON.stringify(sortData, null, ' ')
  $.ajax({
      url: "{LB}/sampleAnalysis/saveAnalysisOrder/" + selected_sample,
      type: "POST",
      data: {sortArray: sendData},

  }).done(function(msg) {
      $('#orderModal').modal('hide');
      scan(selectedBarcode);
  });
}

function sampleBackward(){
  $.ajax({
      url: "{LB}/samples/sampleBackward/" + selected_sample,
      type: "POST",
      dataType: "json",
  }).done(function(msg) {

        if(msg['prev_barcode'] == false){
          alert('Geen vorige monsters beschikbaar.');
        } else{
          $('#barcodeEntry').val(msg['prev_barcode']);
          scan(msg['prev_barcode']);
        }
  });
}

function sampleForward(){
  $.ajax({
      url: "{LB}/samples/sampleForward/" + selected_sample,
      type: "POST",
      dataType: "json",
  }).done(function(msg) {

        if(msg['next_barcode'] == false){
          alert('Geen verdere monsters beschikbaar.');
        } else{
          $('#barcodeEntry').val(msg['next_barcode']);
          scan(msg['next_barcode']);
        }
  });
}

function updateProductGroup(){

    var newPG = $('#update_portal_product_group_id').val();

    console.warn(newPG);

    $.ajax({        
        url: "{LB}/samples/updateSampleProductGroup" ,
        data: {sample_id: selected_sample, product_group_id: newPG},
        type: "POST",      
    }).done(function(msg) {
        $('#productGroupModal').modal('hide');
        scan(selectedBarcode);
    });

}

function saveMetadata(){

  var metaName = $('#metadataName').val();
  var metaValue = $('#metadataValue').val(); 

  $.ajax({
      url: "{LB}/metadata/postSave" ,
      data: {sample: selected_sample, name: metaName, value: metaValue},
      type: "POST",      
  }).done(function(msg) {
    $('#metadataModal').modal('hide');
    $('#metadataName').val('');
    $('#metadataValue').val(''); 
    scan(selectedBarcode);
  });
}


function removeMetaLine(id){
    bootbox.confirm("<h3>Metadata rij verwijderen</h3> <p>Weet u zeker dat u deze metadata rij wilt verwijderen?</p>", function(result) {
        if(result != '' && result != false){
            $.ajax({
                type: "POST",
                data: {},
                url: "{LB}/metadata/delete/" + id
            }).done(function(msg) {         
              scan(selectedBarcode);           
            });
        }
     });
}

function checkClientIsPortalUser(clientId){
  $.ajax({
    type: "POST",
    dataType: "json",
    url: "{LB}/portal/checkClientIsPortalUser/" + clientId
  }).done(function(msg) {
    
    if(msg.users > 0)
    {
      $('#portalClientIcon').addClass('icon-dark-green');
      $('#portalClientIcon').removeClass('icon-red');
    }

    else
    {
      $('#portalClientIcon').addClass('icon-red');
      $('#portalClientIcon').removeClass('icon-dark-green');
    }
    
  });
}

</script>
