<div class="span10">
    <div class="row-fluid">

        <form id="sampleForm" >
            <div class="span4">

                <div class="well well-small">
                    <h6><i class="icon-building"></i> {MESA_SAD_CLIENT} </h6>
                    
                    {client_name}
                    
                    {client}                    
                    {subclient}

                    <div id="clientInfoFetchDisplay" style="padding-bottom: 5px;"></div>

                    <div id="wishAlertDiv" class="alert alert-info alert-block hide" style="cursor:pointer" onClick="openClientWishes()"><span><i class="icon icon-smile"></i> Eisen / bijzonderheden gevonden!</span></div>


                    
                </div>

                <div class="well well-small">
                    <h6><i class="icon-suitcase"></i> {MESA_SAD_PROJINFORMATION} </h6>
                        
                        {project}

                        <input type="hidden" id="otfProjectName" /> 
                        
                        <p id="customProjectHint"><i class="icon icon-exclamation-sign"></i> Project naam ingesteld op: <span id="customProjectLabel"></span>.</p> 


                        <span id="project_field_span" class="hide">
                            {project_fields}
                        </span>

                        <span id="project_preloadfield_span" class="hide">
                            {project_preload_fields}
                        </span>

                          <!--  <button title="{MESA_SAD_TOOLTIPLOCKPROJECT}" id="lockProjectButton" type="button" tabindex="-1" class="btn btn-mini pull-right" ><i id="projectLockIcon" class="icon-unlock"></i> {MESA_SAD_PROJECT}</button>         -->
                        
                        <br />
                </div>

                <auth:reseachToSampleAdd>
                <div class="well well-small hidden">
                    <h6><i class="icon-time"></i> Inzet tijd/datum </h6>

                    <div class="control-group" id="innocDateCG">
                        <div class="controls">
                            <div class="input-prepend input-append input-block-level">
                                <span class="add-on">Inzet datum</span>
                                <input type="text" id="innocDate" name="innocDate" value="" class="input-block-level sample_innoc" placeholder="Inzet datum">
                            </div>
                        </div>
                    </div>

                    <!-- <script>
                        $(function(){

                            $('#innocDate').Zebra_DatePicker({
                                format: 'd-m-Y',
                                zero_pad: true,
                                show_icon: false,
                                offset: [10, 200],
                                readonly_element: false
                            });

                        });
                    </script> -->

                    <div class="control-group hidden" id="innocTimeCG">
                        <div class="controls">
                            <div class="input-prepend input-append input-block-level">
                                <span class="add-on">Inzet tijd</span>
                                <input type="text" id="innocTime" name="innocTime" value="" class="input-block-level sample_innoc" placeholder="Inzet tijd">
                            </div>
                        </div>
                    </div>

                </div>
                    </auth>
            </div>


            <div class="span4">

                <div class="well well-small">
                    <h6><i class="icon-tag"></i> {MESA_SAD_SAMPLEINFORMATION} </h6>
                    {barcode}
                    {samplen}
                    {description}
                    {register_as}
                    {tht_trigger_date}
                    {tht_storage}
                    {sampling_method}
                    {custom_fields}
                    {sample_note}
                </div>

                <auth:reseachToSampleAdd>
                <div class="well well-small">
                    <h6><i class="icon-paperclip"></i> {MESA_SAD_SELECTEDRESEARCH} </h6>
                    <div id="selectedResearch" class="list-group">
                    </div>

                    <button title="{MESA_SAD_TOOLTIPLOCKRESEARCH}" id="lockResearchButton" type="button" tabindex="-1" class="btn btn-mini pull-right"><i id="researchLockIcon" class="icon-unlock"></i> {MESA_SAD_RESEARCH}</button>
                    <br />
                </div>
              </auth>

            </div>

            <div class="span4">

                <auth:reseachToSampleAdd>
                <div class="well well-small">
                    <h6><i class="icon-beaker"></i> {MESA_SAD_RESEARCH} </h6>

                    <div class="tabbable tabs-below">
                        <div class="tab-content">

                            <div class="tab-pane active" id="profile">

                                <div id="profileDiv">

                                <div id="custProfileList" class="list-group">
                                    {cust_profile_list}
                                </div>

                                <div id="globalProfileList" class="list-group">
                                    {global_profile_list}
                                </div>

                                </div>
                            </div>

                            <div class="tab-pane" id="assay">

                              <div class="control-group" id="matrixSelectorCG">
                                <div class="controls">
                                  <div class="input-prepend input-append input-block-level">
                                    <span class="add-on">Selecteer Matrix</span>
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
                                
                                <div id="searchDiv" class="hide">
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
                                
                            </div>

                            <div class="tab-pane " id="quickAdd">


                                <div class="control-group" id="quickAddCustomerCG">
                                    <div class="controls">
                                        <div class="input-prepend input-append input-block-level">
                                            <span class="add-on">Klant profielen</span>
                                            <select id="quickAddProfileCustomer" name="quickAddProfileCustomer" class="input-block-level">

                                            </select>
                                            <span class="add-on"><a href="#" id="quickAddProfileCustomerBtn"><i class="icon-plus"></i></a></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="control-group" id="quickAddProfileGlobalCG">
                                    <div class="controls">
                                        <div class="input-prepend input-append input-block-level">
                                            <span class="add-on">Globale profielen</span>
                                            <select id="quickAddProfileGlobal" name="quickAddProfileGlobal" class="input-block-level">
                                                {global_profile_drop_list}
                                            </select>
                                            <span class="add-on"><a href="#" id="quickAddProfileGlobalBtn"><i class="icon-plus"></i></a></span>
                                        </div>
                                    </div>
                                </div>



                                <div class="control-group" id="quickAddAssayCG">
                                    <div class="controls">
                                        <div class="input-prepend input-append input-block-level">
                                            <span class="add-on">Analyses</span>
                                            <select id="quickAddAssay" name="quickAddAssay" class="input-block-level">
                                              {assay_drop_list}
                                            </select>
                                            <span class="add-on"><a href="#" id="quikAddAssayBtn" ><i class="icon-plus"></i></a></span>
                                        </div>
                                    </div>
                                </div>


                            </div>

                            </div>
                        <ul class="nav nav-tabs">
                            <li class="active"><a href="#profile" data-toggle="tab" tabindex="-1" ><i class="icon-sitemap"></i> {MESA_SAD_PROFILE}</a></li>
                            <li class=""><a href="#assay" data-toggle="tab" tabindex="-1" ><i class="icon-random"></i> {MESA_SAD_ASSAYS}</a></li>
                            <!-- <li class=""><a href="#quickAdd" data-toggle="tab" tabindex="-1" ><i class="icon-bolt"></i> </a></li> -->
                        
                            <li id="searchButtonAssays" class="hide"><a href="#search" data-toggle="tab" tabindex="-1"  ><i class="icon-search"></i> </a></li>
                        
                        </ul>
                    </div>
                </div>
                </auth>
            </div>
        </form>
    </div>

    <div class="row-fluid">
        <div class="span9" > </div>
        <div class="span2">

                <span id="nowSavingIcon" class="label label-info hide" style="padding: 10px;"><i class="icon-spinner icon-spin"></i> Bezig met opslaan . . . </em></span>
                <button id="saveSampleButton" class="btn btn-success btn-block"><i class="icon-save"></i> {MESA_SAD_SAVE} </button>

        </div>
    </div>
</div>

{validationScript}

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


<div id="showProfileModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="showProfileModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="showProfileModalTitle">{MESA_SAD_PROFILEDETAILS}</h3>
    </div>
    <div class="modal-body" id="showProfileModalBody" >
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CLOSE}</button>
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
        <button class="btn" aria-hidden="true" onClick="endAdd();"><i class="icon-remove"></i> {MESA_SAD_CANCEL}</button>
    </div>
</div>

<div id="clientWishesModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="clientWishesModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="clientWishesModalTitle">Klant bijzonderhden & bestanden</h3>
    </div>
    <div class="modal-body" id="clientWishesModalBody" >

        <h5><i class="icon icon-paper-clip"></i> Bijzonderheden / wensen</h5>
        <div id="clientWishesNote">
        </div>

        <h5><i class="icon icon-upload"></i> Bestanden </h5>
        <div id="clientWishesFileList">

        </div>

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CLOSE}</button>
    </div>
</div>

<script>

var clientLocked = true;
var projectLocked = true;
var researchLocked = false;

var stdVals = [];               //std values store
var researchCounter = 0;
var reqResearch = [];           //requested research
var roamingAssayValues = [];    //roaming analysis information store
var excResearch = [];

var roamingAssaySelected = null;

var selected_client = null;
var selected_subclient = null;
var seleced_project = null;
var create_project = false;

//for customization
var offendingAssays = false;
var addableAssays = false;
var pendingProfile = false;
var replacers = false;


var clients = [];
var map = {};

var replicates = false;


$(function(){

   /* =============
    * UI COMPONENTS INITILIZATION
    * =============   */
    $('#profileDiv').slimScroll({
       height: '500px'
    });

    $('#assaysDiv').slimScroll({
       height: '500px'
    });

    $('#searchDiv').slimScroll({
       height: '500px'
    });

    $('#search').on('show', function(){
        //alert('saw it');
    });

    $('a[data-toggle="tab"]').on('shown', function (e) {
        thisTabTarget = $(e.target).attr('href');
        if(thisTabTarget == '#search'){
            $('#searchTerm').focus();
        }
    });

    /* ========
     * On window load
     * ========      */
      storeDefaultValues();
     // $('#client_name').focus();

    /* ==================
     * Client prediction
     * =================     */
    $("#client_name").select2({
            minimumInputLength: 2,
            placeholder: "{MESA_SAD_SELECTCLIENT}",

            ajax: {
            type: "POST",
            url: "{LB}/clients/predictNoSub",
            dataType: 'json',
            quietMillis: 350,
            data: function (term, page) {
                return {
                    term: term, //search term
                    page_limit: 10 // page size
                };
            },
            results: function (data, page) {
                return { results: data.results };
            }

            },
            initSelection: function(element, callback) {
                    //return $.getJSON("/ajax/select2_sample.php?id=" + (element.val()), null, function(data) {
                    //return callback(data);
                    //});
        },
        dropdownCssClass: "bigdrop"
       }).on('change', function(){

            resetResearch();

            //check if its empty
            if ($(this).val() == ''){
                $('#client').val('NULL');
                $("#subclient").val('NULL');
                selected_client = null;
                selected_subclient = null;
                return;
            }

            //breakup the tag and select client id + subclient id
            else{
                clSplit = $(this).val().split("||");
                selected_client = clSplit[0];
                selected_subclient = clSplit[1];
                $('#client').val(selected_client);
                $("#subclient").val(selected_subclient);
                checkClientWishes(selected_client);
                fetchClientInfo(); 
                loadClientProfiles(selected_client);
                loadClientProjects();
            }

       });

    /*=====================
     * OTF Project creation
     * ====================*/
    $('#project').bind('keydown', 'shift+n', function(){
        otfProject();
    });

    $('#newProjButton').click(function() {
        otfProject();
    });

    /*  ==========
     *  UI Triggers
     *  ==========  */

     $('#searchTerm').keypress(function(e) {
        if(e.which == 13) {
            $('#searchButton').trigger('click');
        }
    });

     $('#lockProjectButton').on('click', function(){
        if (projectLocked == true) {
            $('#projectLockIcon').removeClass('icon-lock');
            $('#projectLockIcon').addClass('icon-unlock');
            projectLocked = false;
        } else {
            $('#projectLockIcon').removeClass('icon-unlock');
            $('#projectLockIcon').addClass('icon-lock');
            projectLocked = true;
            $('#clientLockIcon').removeClass('icon-unlock');
            $('#clientLockIcon').addClass('icon-lock');
            clientLocked = true;
        }
     });

    $('#lockClientButton').on('click', function(){
        if (clientLocked == true) {
            //switch to not lockd
            $('#clientLockIcon').removeClass('icon-lock');
            $('#clientLockIcon').addClass('icon-unlock');
            //remove lock from project
            $('#projectLockIcon').removeClass('icon-lock');
            $('#projectLockIcon').addClass('icon-unlock');
            projectLocked = false;
            clientLocked = false;
        } else {
            $('#clientLockIcon').removeClass('icon-unlock');
            $('#clientLockIcon').addClass('icon-lock');
            clientLocked = true;
        }
     });

     $('#lockResearchButton').on('click', function(){

        if(researchLocked == true){
            $('#researchLockIcon').removeClass('icon-lock');
            $('#researchLockIcon').addClass('icon-unlock');
            researchLocked = false;
        } else{
            $('#researchLockIcon').addClass('icon-lock');
            $('#researchLockIcon').removeClass('icon-unlock');
            researchLocked = true;
        }

     });

    $('#quickAddProfileCustomerBtn').on('click', function(){

        if($('#quickAddProfileCustomer').val() !== null){
            requestProfile($('#quickAddProfileCustomer').val());
        }

    });

    $('#quickAddProfileGlobalBtn').on('click', function(){
        requestProfile($('#quickAddProfileGlobal').val());
    });

    $('#quikAddAssayBtn').on('click', function(){
        validateAssayRequest($('#quickAddAssay option:selected'));
    });



     $("#globalProfileList, #custProfileList").on('click', '.list-group-item', function(){
        requestProfile($(this).attr('profileId'));
        $(this).highLight();
     });

    $("#assaysList").on('click', '.list-group-item', function(){
        //requestRoamingAssay($(this));
        validateAssayRequest($(this));
        $(this).highLight();
    });

    $('#searchList').on('click', '.list-group-item', function(){

       if( $(this).attr('researchType') == 'profile'){
           requestProfile($(this).attr('researchId'));
       }

       if( $(this).attr('researchType') == 'assay'){
            //requestRoamingAssay($(this));
            validateAssayRequest($(this));
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

    $('#project').on('change', function(){
        //this fetches the project fields on field change
        selected_project = $(this).val();
        projectChange(selected_project);
    });

    $('.project_date_field').on('blur', function(){
        $(this).change();
    });

    $('.project_input_field').on('change', function(){
        var thisField = $(this).attr('project_field_name');
        var thisValue = $(this).val();

        $.ajax({
        type: "POST",        
        url: "{LB}/projects/updateProjectField/" + selected_project ,
        data: { field: thisField, value: thisValue}
        }).done(function(response) {
        });
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
    
    $('#tht_storageCG').hide();

    $('#tht_trigger_dateCG').hide();
    $('#register_as').on('change', function(){
        if($(this).val() == 'tht'){
            $('#tht_trigger_dateCG').show();
            $('#tht_storageCG').show();
        } else{
            $('#tht_trigger_dateCG').hide();
            $('#tht_storageCG').hide();
        }
    });

    $('#tht_trigger_date').Zebra_DatePicker({
        format: 'd-m-Y',
        zero_pad: true,
        show_icon: false,
        offset: [10, 200],
        readonly_element: false,           
    });

    $('#matrixSearcher').on("keyup", function() {
        var value = $(this).val();
        liveSearchTable(value, 'assaysList');
    });
    

    $("#roamingFormSpan").on('keydown', '.numerical-only-filter', function(e) {
        if (e.altKey || e.ctrlKey || e.which<28) return true;
            return "0123456789".indexOf(e.key) >= 0;     
    });

    // $(".numerical-only-filter").keypress( function(e) {
    //       if (e.altKey || e.ctrlKey || e.which<28) return true;
    //       return "0123456789".indexOf(String.fromCharCode(e.which)) >= 0;
    // });

    //hack for the search buton
    
    // <auth:searchAnalysisAdd>
    $('#searchButtonAssays').show();
    $('#searchDiv').show();
    // </auth>

    loadInMatrix();


    $('#customProjectHint').hide();
    $('#client_name').select2('open');
});


function storeDefaultValues(){
    //stores the values of the input fields as they are
    //when the page is loaded. This only affects custom_input_fields
    $("[class*='custom_input_field']").each(function() {
        var domName = $(this).attr('id');
        var domValue = $(this).val();
        stdVals.push({ domName: domName, domValue: domValue });
    });


}

function projectChange(project){

    selected_project = project;

    $('#otfProjectName').val('');   
    $('#customProjectHint').hide();
    $('#customProjectLabel').html('');

    if(project == 'NULL' || project == 'CREATE'){

        $("[class*='project_preload_field']").each(function() {
            var domName = $(this).attr('id');
            var domValue = $(this).data('defaultvalue');

            if($(this).data('lockedvalue') == '0'){
                $(this).val(domValue);
            }else{
                //alert('locked ' + domName);
            }
        });

        $('#project_field_span').hide();
        $('.project_input_field').val('');
        $('#project_preloadfield_span').show();

        //temporary solution 08-jan-2019
        //remove again because we now want the TIME in ALL samples, not just leg. 
        //$('#preload__project_bemonster_tijd_customproject_cg').hide();
        //$('#pj__project_bemonster_tijd_customproject_cg').hide();


    } else {
        $('#project_preloadfield_span').hide();
        $.ajax({
            type: "POST",
            dataType: "json",
            url: "{LB}/projects/loadProjectFields/" + project
        }).done(function(response) {
            $.each(response, function(index, value) {
                $('#pj_' + index).val(value);
            });
            $('#project_field_span').show();
            
            //temporary solution 08-jan-2019
            //$('#preload__project_bemonster_tijd_customproject_cg').hide();
            //$('#pj__project_bemonster_tijd_customproject_cg').hide();
        }).fail(function(msg){
            console.log('Got no json object.');
        });
    }

}



function otfProject(){
    if (selected_client === null || selected_subclient === null) {
        alert('{MESA_SAD_OTFCREATIONNOCLIENT}');
        return;
    }

    bootbox.prompt("{MESA_SAD_OTFCREATEPROJECT}", function(result) {
        if (result === null || result === '') {
        } else {
            
            $('#otfProjectName').val(result);   
            $('#customProjectHint').show();
            $('#customProjectLabel').html(result);

        }
    });
}

function requestProfile(requestedProfile){

    //check if already selected
    console.log(requestedProfile);
    validateProfileRequest(requestedProfile);
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


        if(response['valid'] == true){
           addProfile(requestedProfile);
        } else{
            pendingProfile = requestedProfile;
            addableAssays = response['addable'];
            offendingAssays = response['offending'];
            replacers = response['replacers'];
            $('#changeRequestModal').modal('show');

        }
    }).error(function(error){
        console.log(error);
    });

}

function addOffenders(){

    var thisResult;
    var thisFollow;

    thisFollow = researchCounter;
    thisResult = addProfile(pendingProfile);

    $.each(offendingAssays, function(inFollow, offending) {
        $.each(offending, function(index, assayId){
            excludeFromProfile(pendingProfile, assayId, thisFollow);
        });
    });


    endAdd();
}


function replaceOffenders(){

    var thisResult;
    thisResult = addProfile(pendingProfile);

    $.each(replacers, function(inFollow, offending) {
        $.each(offending, function(index, assayId){
           excludeFromProfile('0', assayId, inFollow);
        });
    });

    endAdd();
}

function endAdd(){
    $('#changeRequestModal').modal('hide');
    offendingAssays = false;
    addableAssays = false;
    replacers = false;
}

function addProfile(profileId){

    console.log('In add profile');

     $.ajax({
        type: "POST",
        url: "{LB}/researchProfiles/renderProfileRequest/" + profileId + "/" + researchCounter
    }).done(function(req) {
       $('#selectedResearch').append(req);
    });

    reqResearch[researchCounter] = {resType: 'profile', resId: profileId};
    excResearch[researchCounter] = [];
    researchCounter = researchCounter + 1;  //update the follow number

}

function removeProfile(followId){
    //remove from the reqResearch object
    //we can leave the research counter as it is, since its more of a pseudeo identifier for the UI
    delete reqResearch[followId];
    delete excResearch[followId];
    $('#req_' + followId).remove();
}

function removeAssay(followId){
    delete reqResearch[followId];
    delete roamingAssayValues[followId];
    $('#req_' + followId).remove();
}

function validateAssayRequest(selectedAssay){

    roamingAssaySelectedId = $(selectedAssay).attr('assayId');

    roamingAssaySelected = {
        'assayid' : $(selectedAssay).attr('assayId'),                               
        'assaytype' : $(selectedAssay).attr('assaytype'),
        'dillution' : $(selectedAssay).attr('dillution'),
        'replicates' :$(selectedAssay).attr('replicates')
    }

    console.log(roamingAssaySelected);

    console.warn(roamingAssaySelected['assaytype']);

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

           
        } else{
            alert('{MESA_SAD_ASSAYALREADYSELECTED}');
        }

    }).error(function(response){
        console.log(response);
    });

}

function requestRoamingAssay(selectedAssay, editId, autoapply){
    //add an assay to the requested research list
    //we need to get ifnormation from the user on
    //dillution, replicates and reference / reference scope
    //store this in roamingAssayValues

    console.log('requestRoamingAssay')
    console.log(selectedAssay);        
    console.log('edit id: ' + editId);


    //roamingAssaySelected = $(selectedAssay).attr('assayid');
    roamingAssaySelected = selectedAssay.assayid;
    
    //assayType = $(selectedAssay).attr('assaytype');
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
             $('#roam_dillutionCG').show();
             $('#roamingDillutionForm').show();             
        }  else{            
            $('#ref_kve').removeClass('numerical-only-filter');
            $('#roam_dillution').val('0=1');
        }
        

        if(selectedAssay.replicates == '1'){
             $('#roamingReplicatesForm').show();
        }

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
}

function saveRoamingAssay(){

    //check source selection
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

    setObj['assay_id'] = roamingAssaySelected
    setObj['reference_source'] =  $('#reference_source').val(); 

    reqResearch[researchCounter] = {resType: 'assay', assayId: roamingAssaySelected};
    roamingAssayValues[researchCounter] = setObj;

    $('#addRoamingModal').modal('hide');

    $.ajax({
        type: "POST",
        url: "{LB}/assays/renderAssayRequest/" + roamingAssaySelected + "/" + researchCounter
    }).done(function(req) {
       $('#selectedResearch').append(req);
    });

    //moved it here
    researchCounter = researchCounter + 1;  //update the follow number

}

function resetResearch(){
  reqResearch = [];           //requested research
  roamingAssayValues = [];    //roaming analysis information store
  roamingAssaySelected = null;
  $('#selectedResearch').html('');
}

function resetWindow(){

    //reset standards
    $.each(stdVals, function(index, value) {
        $('#' + value['domName']).val(value['domValue']);
    });

    //reset
    if(researchLocked == false){
      resetResearch();
    }

    $('#sample_notes').val('');
    $('#description').val('');
    $('#samplen').val('1');
    $('#saveSampleButton').show();
    $('#nowSavingIcon').hide();
    $('#register_as').val('standard').trigger('change');
    $('#tht_trigger_date').val('');
    $('#tht_storage').val('0');
    $('#description').focus();
    $('#otfProjectName').val('');

}

function loadSubClients(client) {
    //loads subclients for selected client
    $.ajax({
        type: "POST",
        url: "{LB}/subClients/printSubClientSelect/" + client
    }).done(function(options) {
        $("#subclient").empty().append(options);
    });
}

function loadClientProfiles(client){
    //loads client research profiles
    $.ajax({
        type: "POST",
        dataType: "json",
        url: "{LB}/researchProfiles/fetchCustomerProfiles/" + client + "/false/true"
    }).done(function(options) {
        $("#custProfileList").empty().append(options['html']);
        $("#quickAddProfileCustomer").empty().append(options['options']);

    });
}


function checkClientWishes(client){
    //checkForWishesAndFiles
    $.ajax({
        type: "POST",
        dataType: "json",
        url: "{LB}/clients/checkForWishesAndFiles/" + client
    }).done(function(wishMsg) {
            if(wishMsg['wishes'] == true){
                $('#wishAlertDiv').show();
            } else{
                $('#wishAlertDiv').hide();
            }
    });
}

function openClientWishes(){


    $.ajax({
        type: "POST",
        dataType: "json",
        url: "{LB}/clients/renderWishesAndFilesDialog/" + selected_client
    }).done(function(wishMsg) {
        $('#clientWishesNote').html(wishMsg['wishes']);
        $('#clientWishesFileList').html(wishMsg['files']);
        $('#clientWishesModal').modal('show');
    });
}

function loadClientProjects(preselect){
    $.ajax({
        type: "POST",
        url: "{LB}/projects/getProjectSelect/" + selected_client + "/" + selected_subclient
    }).done(function(options) {
        $("#project").empty().append(options);

        //console.log($('#project').val());

        if(preselect !== undefined ){
            $('#project option[value=' + preselect +']').attr('selected', 'selected');
            projectChange(preselect);
        }  else{
            projectChange($('#project').val());
        }
    });
}


function checkProjectFields(){

    // var projectCreation = $('#project').val();

    // if(projectCreation == 'CREATE'){
        
        
    //     var creationDate = $('#preload_project_bemonster_datum').val();

    //     if(creationDate == 'Onbekend' || creationDate == '')
    //     {
    //             return true; 
    //     }
        

    //     var creationTime = $('#preload_project_bemonster_tijd').val();
                
    //     if(creationTime === ''){
    //         $('#preload__project_bemonster_tijd_customproject_cg').addClass('error');                
    //         alert('Bemonster tijd niet ingevuld!');
    //         return false;
    //     }

    // }

    // $('#preload__project_monster_tijd_customproject_cg').removeClass('error');
    
    return true;
}



function saveSample(){

    var canSubmit = checkProjectFields();

    if(canSubmit === false){
        return;
    }

    var printLimit = {MAX_LABEL_PRINT};
    var customFields = [];
    var sampleFields = [];
    var projectPreloadFields = [];
    var samplen = $('#samplen').val();
    var bufferSamplingTrack = '';
    var customProjectName =  $('#otfProjectName').val();

    console.warn('custom proj name' + customProjectName);

    var innocDate = $('#innocDate').val();
    var innocTime = $('#innocTime').val();
    var sampleNotes = $('#sample_notes').val();
    var registerType = $('#register_as').val();
    var thtTriggerDate = $('#tht_trigger_date').val();
    var thtStorage = $('#tht_storage').val();

    var printBool = true;
    if(samplen > printLimit ){
      printBool = confirm(samplen + " monsters worden aangemeld, labels afdrukken?");
    }


    $('#saveSampleButton').hide();
    $('#nowSavingIcon').show();

    $('input, select, textarea').each(function(){
            var fieldId = $(this).attr('id');
            var fieldValue = $(this).val();

            if($(this).hasClass('custom_input_field')){
                customFields.push({ customFieldId: fieldId, customFieldVal: fieldValue});
            } else if ($(this).hasClass('project_preload_field')){
                projectPreloadFields.push({preloadFieldId: fieldId, preloadFieldVal: fieldValue});
                //$(this).val($(this).data('defaultvalue'));
            } else if ($(this).hasClass('sample_innoc')){
                //skip
            }
            else{
                sampleFields.push({ sampleField: fieldId, sampleFieldValue: fieldValue});
            }
    });

    //check is server side project creation needs to be executed
    if($('#project').val() == 'CREATE'){
        create_project = 'True';
        bufferSamplingTrack = $('#preload_project_monster').val();
        bufferReceiveDateTrack = $('#preload_project_ontvangst').val();
        bufferReceiveTimeTrack = $('#preload_project_tijd_ontvangst').val();


    } else{
        create_project = 'False';
        bufferSamplingTrack = $('#pj_project_monster').val();
        bufferReceiveDateTrack = $('#pj_project_ontvangst').val();
        bufferReceiveTimeTrack = $('#pj_project_tijd_ontvangst').val();
    }


    return $.ajax({
        type: "POST",
        dataType: "json",
        data: { 
                requested: reqResearch, 
                roaming: roamingAssayValues, 
                printBool: printBool, 
                excluded: excResearch,       
                samplen: samplen,
                sampleFields: sampleFields, 
                customFields: customFields, 
                createProject: create_project, 
                customProjectName : customProjectName,
                projectPreload: projectPreloadFields, 
                sampleNotes: sampleNotes,  
                innocDate: innocDate,
                innocTime: innocTime, 
                registerType: registerType, 
                bufferSamplingTrack: bufferSamplingTrack, 
                thtTriggerDate: thtTriggerDate, 
                thtStorage: thtStorage,
                bufferReceiveDateTrack: bufferReceiveDateTrack, 
                bufferReceiveTimeTrack: bufferReceiveTimeTrack
            },
        url: "{LB}/samples/save/true"
    }).done(function(response) {

        //update if first sample was buffer commited 
        if(create_project == 'True' && response['bufferCommit'] == false ){
            //should load the selected project into the dropdown box
            loadClientProjects(response['project']);
            create_project = false;
        }

        $('#barcode').val(response['nextBar']);
        resetWindow();
    });
}




function showProfileDetails(follow_no){

    var profileId =  reqResearch[follow_no]['resId'];
    console.log('request profile details:' + profileId);

    $.ajax({
        type: "POST",
        url: "{LB}/researchProfiles/profileInfo/" + profileId + "/" + follow_no
    }).done(function(response) {
        $('#showProfileModalBody').html(response);

        //remove the ones we previously mentioned to be excluded
        $.each(excResearch[follow_no], function(follow, follow_id) {
            $('#assay_' + follow_id).remove();
        });

        $('#showProfileModal').modal('show');
    });

}



function excludeFromProfile(profileId, assayId, follow_no){
    console.log('exclude' + assayId + 'from profile:' + profileId + ' follow number:' + follow_no);
    //remove from table
     $('#showProfileModalBody').find('#assay_' + assayId).remove();
    //set in array
    excResearch[follow_no].push(assayId);
}

function editFromProfile(profileId, assayId, follow_no, assayOrigin){
     //assayId is the profile_assay_id here 
     console.log('Edit from profile' + profileId, assayId, follow_no, assayOrigin );
                
    var marker = '#assay_single_details_' + assayId;

    console.log(marker);

    assayObject = {
        'assayid' :  $(marker).attr('assayid'),
        'assaytype' : $(marker).attr('assaytype'),
        'dillution' : $(marker).attr('dillution'),
        'replicates' :$(marker).attr('replicates'),
    }


    excludeFromProfile(profileId, assayId, follow_no);        

    // //close edit modal
    $('#showProfileModal').modal('hide');

    requestRoamingAssay(assayObject, assayId);

}

function fetchClientInfo()
{

    $.ajax({
            type: "POST",    
            data: {cid: selected_client, scid: selected_subclient},
            url: "{LB}/applets/clientInfoFetch" 
        }).done(function(response) {                                   
            $('#clientInfoFetchDisplay').html(response);
        });

}

</script>
