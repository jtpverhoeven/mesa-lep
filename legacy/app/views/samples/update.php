<div class="span10" i>

    <div class="row-fluid" >

        <div class="span8">

            <div class="row-fluid hidden"  id="topRow">
                <div class="span6 ">
                    <div class="well well-small">
                        <h6><i class="icon-calendar"></i> Filter op inzet datum</h6>
                        <input class="input formDate" type="text" id="inzetSelector" name="inzetSelector" value="" /> <br />
                        Er zijn <em id="number_of_emptys"></em> monsters in de lijst
                    </div>
                </div>
                <div class="span6">
                    <div class="well well-small hidden">
                        <h6><i class="icon-download"></i> Inzet datum </h6>
                        <input class="input" type="text" id="innocDateSet" name="innocDateSet" value="" /> <br />
                        <label><input type="checkbox" id="alsoSetInnoc" name="alsoSetInnoc"/> Ook inzetdatum toekennen</label>
                    </div>
                </div>
            </div>

            <div class="row-fluid">
                <div class="span12">
            

                    <div class="well well-small" style="overflow: hidden;" >

                    


                    <div  id="tableWell">
                      
                        <table class="table table-bordered table-condensed" id="sampleTable">

                            <thead>

                            <tr>
                                <th style="width: 25px;"><i class="icon icon-check"></i> <a href="#" onClick="expandProjectsSelection();">[Project]</a></th>
                                <th style="width: 20px;"></th>
                                <th style="text-align: center">Labnummer</th>
                                <th style="text-align: center">Klant</th>
                                <th>Monsteromschrijving</th>
                                <th colspan="2">Ontvangst datum en tijd</th>
                                <th>Inzetdatum</th>
                            </tr>

                            </thead>

                            <tbody id="tableBody">


                            </tbody>


                        </table>

                    </div> 

                    <img src="{LP}/images/arrow_ltr.png" /> <button onClick="expandProjectsSelection();" class="btn btn-xs btn-primary" type="button">Selectie uitbreiden naar project</button> 

                    </div>
                </div>
            </div>

        </div>

        <div class="span4">


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
                            <auth:searchAnalysisAdd>
                                <li class=""><a href="#search" data-toggle="tab" tabindex="-1" ><i class="icon-search"></i> </a></li>
                            </auth>
                        </ul>
                    </div>
                </div>
                <div class="well well-small">
                    <h6><i class="icon-paperclip"></i> {MESA_SAD_SELECTEDRESEARCH} </h6>
                    <div id="selectedResearch" class="list-group">
                    </div>

                    <span id="nowSavingIcon" class="label label-info hide" style="padding: 10px;"><i class="icon-spinner icon-spin"></i> Bezig met opslaan . . . </em></span>
                    <button id="saveSampleButton" class="btn btn-primary btn-block"><i class="icon-plus"></i> Toevoegen </button>
                </div>


                </div>

        </div>


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

<div id="noteModal" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3>Monster notitie</h3>
    </div>
    <div class="modal-body" id="noteModalBody" >


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

    var scrollLocation = 0;
    var clientLocked = true;
    var projectLocked = true;
    var researchLocked = false;

    var stdVals = [];               //std values store
    var researchCounter = 0;
    var reqResearch = [];           //requested research
    var roamingAssayValues = [];    //roaming analysis information store
    var excResearch = [];
    var projectList = [];

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


    $(function() {


        $('#searchTerm').keypress(function(e) {
            if(e.which == 13) {
                $('#searchButton').trigger('click');
            }
        });

        $('#saveSampleButton').show();
        $('#nowSavingIcon').hide();

        $('#profileDiv').slimScroll({
            height: '360px'
        });

        $('#assaysDiv').slimScroll({
            height: '360px'
        });

        $('#searchDiv').slimScroll({
            height: '36 0px'
        });

        height = listResize();
        $('#tableWell').slimScroll({
            height: height,
            railVisible: false,
            railOpacity: 0
        });

        $('#inzetSelector').Zebra_DatePicker({
            format: 'd-m-Y',
            zero_pad: true,
            show_icon: false,
            offset: [10, 200],
            readonly_element: false,
            onSelect: function() {
                loadList(0);
            }, onClear: function(){
                loadList(0);
            }
        });

        $('#tableWell').slimScroll().bind('slimscroll', function(e, pos){
            if(pos == 'bottom'){
                lastId = $( "#tableBody tr:last").attr('sampleId');
                loadList(lastId);
            }
        });

        $('#inzetSelector').keypress(function(event) { return event.keyCode != 13; });
        $('#inzetSelector').bind('keydown', 'return', function(){
            loadList(0);
        });
        $('#emptySelector').change(function() {
            alert('Waarschuwing: er wordt geen controle uitgevoerd of niet-lege monsters een bepaalde analyse al bezitten. Dit kan leiden tot dubbele analyses. Gebruik deze functie zorgvuldig.')
            loadList(0);
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

        $('#saveSampleButton').on('click', function(){
            save();
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


        $('#tableBody').on('click', 'input', function(){
            if (this.checked) {
                checkClientBox($(this).attr('id'));
            }
 
            checkClientStillSelected();
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

        $('a[data-toggle="tab"]').on('shown', function (e) {
            thisTabTarget = $(e.target).attr('href');
            if(thisTabTarget == '#search'){
                $('#searchTerm').focus();
            }
        });

        $('#matrixSearcher').on("keyup", function() {
            var value = $(this).val();
            liveSearchTable(value, 'assaysList');
        });

        $("#roamingFormSpan").on('keydown', '.numerical-only-filter', function(e) {                      
            if (e.altKey || e.ctrlKey || e.which<28) return true;
            return "0123456789".indexOf(e.key) >= 0;        
        });

        loadInMatrix();

        loadList(0);
    });


    function listResize(){
        //height = $(window).height() - $('#topRow').height() - 100;
        height = $(window).height() - 125;
        return height;
    }

    function scrollDown(){
        scrollLocation = scrollLocation + {scroll_speed};
        $('#tableWell').slimScroll({ scrollTo: scrollLocation + 'px' });
    }

    function scrollUp(){
        scrollLocation = scrollLocation - {scroll_speed};
        if(scrollLocation < 0){
            scrollLocation = 0;
        }
        $('#tableWell').slimScroll({ scrollTo: scrollLocation + 'px' });
    }

    function resetScroll(){
        scrollLocation = 0;
        $('#tableWell').slimScroll({ scrollTo: scrollLocation + 'px' });
    }


    function loadList(loadFrom){

        if(loadFrom == 0){
            $('#tableBody').html('');
            reqResearch = [];           //requested research
            roamingAssayValues = [];    //roaming analysis information store
            roamingAssaySelected = null;
            $('#selectedResearch').html('');
        }

        var emptyOnly = 1;


        var inzetDatum = $('#inzetSelector').val();
        $.ajax({
            type: "POST",
            dataType: "json",
            url: "{LB}/samples/loadUpdateList/" + loadFrom + "/" + emptyOnly + '/' + inzetDatum
        }).done(function(msg) {
            var count = $('#tableBody').children('tr').length + msg['number_of_emptys'];
            $('#number_of_emptys').html(count);
            $('#tableBody').append(msg['html']);
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

        $.ajax({
            type: "POST",
            data: {requestedId: roamingAssaySelectedId, requestedType: 'assay', alreadyAssigned: reqResearch, excluded: excResearch},
            dataType: 'json',
            url: "{LB}/assayProfiles/validateAssayRequest"
        }).done(function(response) {
            console.log(response);

            if(response['valid'] == true){

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
            } 
                        
            else{
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
            console.log($(this).attr('id'));
            setObj[$(this).attr('id')] = $(this).val();
        });

        setObj['assay_id'] = roamingAssaySelected;
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

    function checkClientBox(id){

        console.log(id)

        if(selected_client == null){
            console.log('no client was selected, setting now');
            selected_client = $('#' + id).attr('client');
            loadClientProfiles(selected_client);
        }
    }

    function expandProjectsSelection(){
      $('#tableBody input[type=checkbox]').each(function () {
          var projectId = $(this).attr('project');
          var arrIndex = $.inArray(projectId, projectList);
          if(arrIndex > -1){
            $(this).prop( "checked", true );
          }
      });
    }

    function checkClientStillSelected(){

        var countedTickedOnes = 0;
        projectList = []

        $('#tableBody input[type=checkbox]').each(function () {

            if(this.checked){
                countedTickedOnes = countedTickedOnes + 1;
                projectList.push($(this).attr('project'));
            }
        });

        console.log('found ' + countedTickedOnes + ' ticked boxes');

        if(countedTickedOnes == 0){
            console.log('No more tick boxes available!');
            selected_client = null;
            enableAllClientBoxes();
        } else{
            disableAllNonClientBoxes();
        }

        //count for selectred zero?
        //no more boxes selected? Means we deselected the client
        //open up the other client boxes again
        //unload custom profiles
        //set selected_client to null
        //remove all

    }

    function disableAllNonClientBoxes(){
        $('#tableBody input[type=checkbox]').each(function () {
            if($(this).attr('client') != selected_client){
                $(this).attr('disabled', true);
            } else{
                $(this).removeAttr("disabled");
            }
        });
    }

    function enableAllClientBoxes(){

        $('#tableBody input[type=checkbox]').each(function () {
            $(this).removeAttr("disabled");
        });

        //and remove custom profiles
        $("#custProfileList").empty();
        $("#quickAddProfileCustomer").empty();
        $('#selectedResearch').html('');
        reqResearch = [];           //requested research
        roamingAssayValues = [];    //roaming analysis information store
        roamingAssaySelected = null;
    }


    function save() {
        $('#saveSampleButton').hide();
        $('#nowSavingIcon').show();

        var samplesToUpdate = [];
        var alsoSetInnoc = false;
        var innocSetDate = false;

        $('#tableBody input[type=checkbox]').each(function () {
            if(this.checked){
                var fieldValue = $(this).attr('sampleId');
                samplesToUpdate.push({sample: fieldValue});
            }
        });

        if(document.getElementById('alsoSetInnoc').checked) {
            alsoSetInnoc = true;
            innocSetDate = $('#innocDateSet').val();
        }

        $.ajax({
            type: "POST",
            dataType: "json",
            data: { samplesToUpdate: samplesToUpdate, alsoSetInnoc: false, innocSetDate: false, addRequested: reqResearch, addRoaming: roamingAssayValues, addExcluded: excResearch},
            url: "{LB}/samples/sampleUpdateWrapper"
        }).done(function(response) {

            if(response['warning'] == true) //some samples were updated already 
            {
                alert('Let op! Er zijn voor dit monster al analyses toegevoegd door een andere gebruiker. Dit gebeurde mogelijk gelijktijdig. De door u geselecteerde analyses zijn niet aan het monster toegevoegd. Controleer het monster handmatig. Deze melding is van toepassing op de volgende monster(s): ' + response['warning_samples']);
            }            

            $('#saveSampleButton').show();
            $('#nowSavingIcon').hide();
            afterSave();
        });

    }

    function afterSave(){

        //need to remove the selected rows now
        $("#custProfileList").empty();
        $("#quickAddProfileCustomer").empty();
        $('#selectedResearch').html('');
        reqResearch = [];           //requested research
        roamingAssayValues = [];    //roaming analysis information store
        roamingAssaySelected = null;

        $('#tableBody input[type=checkbox]').each(function () {

            if(this.checked){
                //$(this).parents('tr').remove();
                $(this).parents('tr').each(function () {
                    let thisId = $(this).attr('sampleid');
                    $('#sample_' + thisId).remove();
                    $('#sampleinstructions_' + thisId).remove();
                });
            }
        });
        checkClientStillSelected();
        runBadgeCounters();
    }

    function noteDisplay(sample){
        $.ajax({
            type: "POST",
            dataType: "json",
            data: { sampleId: sample},
            url: "{LB}/samples/fetchNote"
        }).done(function(response) {
            $('#noteModalBody').html(response['note']);
            $('#noteModal').modal('show');
        });
    }

    function openClientWishes(selected_client){

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



</script>
