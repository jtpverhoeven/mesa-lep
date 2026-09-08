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
                    <h6><i class="icon-tag"></i> {MESA_SAD_SAMPLEINFORMATION} </h6>
                    {number_of_samples}                    
                    {sampling_method}
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
                    <br />
                </div>

                <div class="hidden well well-small">
                    <h6><i class="icon-time"></i> Inzet tijd/datum </h6>

                    <div class="control-group" id="innocDateCG">
                        <div class="controls">
                            <div class="input-prepend input-append input-block-level">
                                <span class="add-on">Inzet datum</span>
                                <input type="text" id="innocDate" name="innocDate" value="" class="input-block-level sample_innoc" placeholder="Inzet datum">
                            </div>
                        </div>
                    </div>

                    <script>
                        $(function(){

                            $('#innocDate').Zebra_DatePicker({
                                format: 'd-m-Y',
                                zero_pad: true,
                                show_icon: false,
                                offset: [10, 200],
                                readonly_element: false
                            });

                        });
                    </script>

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


            <div class="span8">


                <div class="well well-small">
                    <h6><i class="icon-tag"></i> {MESA_SAD_SAMPLEINFORMATION} </h6>
                    <span id="nowLoadingIcon" class="label label-info hide" style="padding: 10px;"><i class="icon-spinner icon-spin"></i> Bezig met laden . . . </em></span>

                    {client_reference}

                    <table class="table table-condensed table-bordered">
                        <thead>
                        <tr>
                            <th width="50px;">Volgn</th>
                            <th width="100px;">Monsternummer (indicatief)</th>
                            <th>Ruimte</th>
                            <th>Omschrijving plaats van monsterneming</th>
                        </tr>
                        </thead>

                        <tbody id="sampleTableBody">

                        </tbody>
                    </table>
                </div>

                <div class="row-fluid">
                    <div class="span9" > <div class="well well-small"> {sample_note} </div> </div>
                    <div class="span2">

                        <span id="nowSavingIcon" class="label label-info hide" style="padding: 10px;"><i class="icon-spinner icon-spin"></i> Bezig met opslaan . . . </em></span>
                        <button id="saveSampleButton" type="button" class="btn btn-success btn-block"><i class="icon-save"></i> {MESA_SAD_SAVE} </button>

                    </div>
                </div>

            </div>

        </form>
        {validationScript}
    </div>
</div>

<div id="clientWishesModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="clientWishesModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="clientWishesModalTitle">Klant bijzonderhden &amp; bestanden</h3>
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


    $(function() {

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
                    return {results: data.results};
                }

            },
            initSelection: function (element, callback) {
                //return $.getJSON("/ajax/select2_sample.php?id=" + (element.val()), null, function(data) {
                //return callback(data);
                //});
            },
            dropdownCssClass: "bigdrop"
        }).on('change', function () {

            //check if its empty
            if ($(this).val() == '') {
                $('#client').val('NULL');
                $("#subclient").val('NULL');
                selected_client = null;
                selected_subclient = null;
                return;
            }

            //breakup the tag and select client id + subclient id
            else {
                clSplit = $(this).val().split("||");
                selected_client = clSplit[0];
                selected_subclient = clSplit[1];
                $('#client').val(selected_client);
                $("#subclient").val(selected_subclient);
                fetchClientInfo(); 
                checkClientWishes(selected_client);
                loadClientProjects();
            }

        });

        $('#project').bind('keydown', 'shift+n', function () {
            otfProject();
        });

        $('#newProjButton').click(function () {
            otfProject();
        });

        /*  ==========
         *  UI Triggers
         *  ==========  */
        $('#number_of_samples').on('change', function(){
            var numberOfSamplesToPrepare = $(this).val();
            loadSamplesForm(numberOfSamplesToPrepare);
        });


        $('#lockProjectButton').on('click', function () {
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

        $('#lockClientButton').on('click', function () {
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

        $('#lockResearchButton').on('click', function () {

            if (researchLocked == true) {
                $('#researchLockIcon').removeClass('icon-lock');
                $('#researchLockIcon').addClass('icon-unlock');
                researchLocked = false;
            } else {
                $('#researchLockIcon').addClass('icon-lock');
                $('#researchLockIcon').removeClass('icon-unlock');
                researchLocked = true;
            }

        });


        $('#project').on('change', function () {
            //this fetches the project fields on field change
            selected_project = $(this).val();
            projectChange(selected_project);            
        });

        $('.project_date_field').on('blur', function () {
            $(this).change();
        });

        $('.project_input_field').on('change', function () {
            var thisField = $(this).attr('project_field_name');
            var thisValue = $(this).val();

            $.ajax({
                type: "POST",                
                url: "{LB}/projects/updateProjectField/" + selected_project ,
                data: { field: thisField, value: thisValue}
            }).done(function (response) {
            });
        });


        loadSamplesForm(0);
        
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

            //temporary solution 08-jan-2019
            //remove again because we now want the TIME in ALL samples, not just leg.             
            //$('#preload__project_bemonster_tijd_customproject_cg').hide();
            //$('#pj__project_bemonster_tijd_customproject_cg').hide();
            //end
            $('#project_field_span').hide();
            $('.project_input_field').val('');
            $('#project_preloadfield_span').show();
            $('#number_of_samples').trigger('change');            
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

                $('#number_of_samples').trigger('change');
                //temporary solution 08-jan-2019
                //$('#preload__project_bemonster_tijd_customproject_cg').hide();
                //$('#pj__project_bemonster_tijd_customproject_cg').hide();
                //end
                $('#project_field_span').show();
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

                // $.ajax({
                //     type: "POST",
                //     data: {client: selected_client, subclient: selected_subclient, project_name: result},
                //     url: "{LB}/projects/otfProjectCreation"
                // }).done(function(msg) {
                //     //loadClientProjects(selected_client, selected_subclient, msg);
                //     loadClientProjects(msg);
                //     projectChange(msg);
                // });
            }
        });
    }

    function resetWindow(){

        //reset standards
        $.each(stdVals, function(index, value) {
            $('#' + value['domName']).val(value['domValue']);
        });


        if(researchLocked == false){
            reqResearch = [];           //requested research
            roamingAssayValues = [];    //roaming analysis information store
            roamingAssaySelected = null;
            $('#selectedResearch').html('');
        }


        $('#number_of_samples').val(0);
        loadSamplesForm(0);


        $('#sampling_method').val('{sampling_method_standard}');
        $('#sample_notes').val('');
        $('#client_reference').val('');


        $('#saveSampleButton').show();
        $('#nowSavingIcon').hide();

        selected_client = null;
        selected_subclient = null;
        seleced_project = null;
        $('#client_name').val('');
        $('#subclient').val('');
        $('#project_field_span').hide();
        $('.project_input_field').val('');
        $('#wishAlertDiv').hide();

        //$('#project').empty();
        $('#project option[value="CREATE"]').attr('selected', 'selected');
        $('#otfProjectName').val('');
        $('#client_name').select2('open');


        /*
         if(clientLocked == false){
         //can remove both client, and project data, hide project window
         selected_client = null;
         selected_subclient = null;
         seleced_project = null;
         $('#client_name').val('');
         $('#subclient').val('');
         $('#project').empty();
         $('#project_field_span').hide();
         $('.project_input_field').val('');
         $('#client_name').focus();
         }

         if(clientLocked == true){

         if(projectLocked == false){
         seleced_project = null;
         $('#project option[value=NULL]').attr('selected', 'selected');
         $('#project_field_span').hide();
         $('.project_input_field').val('');
         $('#project').focus();
         }

         if(projectLocked == true){
         $('#description').focus();
         }
         }

         */



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
            url: "{LB}/projects/getProjectSelect/" + selected_client + "/" + selected_subclient + "/2"
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


    function saveSample() {
        //var sampleFormData = $("#sampleForm").serialize();
        //console.log(sampleFormData);

        $('#saveSampleButton').hide();
        $('#nowSavingIcon').show();

        var samplePackage = [];
        var customFields = [];
        var sampleFields = [];
        var clientId = $('#client').val();
        var projectPreloadFields = [];
        var samplen = $('#samplen').val();

        var innocDate = $('#innocDate').val();
        var innocTime = $('#innocTime').val();

        var samplingMethod = $('#sampling_method').val();
        var clientReference = $('#client_reference').val();
        var sampleNote = $('#sample_notes').val();
        var customProjectName =  $('#otfProjectName').val();
        
        //var numberOfBlanks = $('#number_of_blanks').val();

        var numberOfBlanks = 0;

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
        });

        $('#sampleTableBody tr').each(function(){
            var sampleI = $(this).attr('sampleI');
            var thisFollow = $('#follow_number_' + sampleI).val();
            var thisDescription = $('#description_' + sampleI).val();
            var thisLocation = $('#location_' + sampleI).val();
            samplePackage.push({ thisFollow: thisFollow, thisDescription: thisDescription, thisLocation:thisLocation });
        });

        var selected_project_id; //we need this for leg and roadc
        if($('#project').val() == 'CREATE'){
            create_project = 'True';
        } else{
            create_project = 'False';
            selected_project_id = $('#project').val();
        }

        $.ajax({
            type: "POST",
            dataType: "json",
            data: { 
                number_of_blanks: numberOfBlanks, 
                selected_project_id: selected_project_id, 
                client: clientId, 
                sample_note: sampleNote, 
                samplePackage:samplePackage, 
                createProject: create_project, 
                customProjectName : customProjectName,
                projectPreload: projectPreloadFields, 
                innocDate: innocDate, 
                innocTime: innocTime, 
                samplingMethod: samplingMethod, 
                clientReference: clientReference},
            url: "{LB}/samples/saveRodac"
        }).done(function(response) {
            if(create_project == 'True' ){
                //should load the selected project into the dropdown box
                loadClientProjects(response['project']);
                create_project = false;
            }
            $('#barcode').val(response['nextBar']);
            resetWindow();
        });
    }

    function loadSamplesForm(numberOfSamples){

        var selectedProject = $('#project').val();

        $('#nowLoadingIcon').show();
        $.ajax({
            type: "POST",
            dataType: "json",
            url: "{LB}/samples/renderAddRodacSamples/" + numberOfSamples + "/" + selectedProject
        }).done(function(msg) {
            $('#nowLoadingIcon').hide();
            $('#sampleTableBody').html(msg['table']);
            $('#client_reference').val(msg['first_up']);
        });
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
