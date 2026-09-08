        <span class="span4">

            <div class="well">

                <h6><i class="icon-tags"></i> {MESA_ERP_GENERALINFO}     </h6>
                {profile_form}

            </div>

            <div class="well">
                <div id="searchDiv" class="">
                                            
                    <table style="width: 100%">
                        <tr>
                            <td><input id="searchTerm" class="input-block-level" type="text" /></td>
                            <td><button type="button" id="searchButton" class="btn btn-primary input-block-level"><i class="icon-search"></i> {MESA_SAD_SEARCH}</button></td>
                            <td><select id="searchTermQ" class="input-block-level"><option value="q">Q</option><option value="nq">non-Q</option><option value="a">Alles</option></select></td>
                        </tr>
                    </table>


                    <div id="searchList" class="list-group">
                    </div>
                </div>
            </div>

            <div class="well">
                <h6><i class="icon-beaker"></i> {MESA_ERP_AVAILABLEASSAYS} </h6>
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



        </span>

        <span class="span6">
            <div class="well" id="">
                <h6><i class="icon-plus"></i> {MESA_ERP_SELECTEDASSAYS} </h6>

                <table width="100%" class="table">
                    <thead>
                        <tr>
                            <th>Volg.</th>
                            <th>{MESA_ERP_ASSAY}</th>
                            <th>{MESA_ERP_DILUTIONS}</th>
                            <th>{MESA_ERP_REPLICATES}</th>
                            <th>{MESA_ERP_REFERENCEVALUE}</th>
                            <th>Ref. bron </th>
                            <th>Bevestig boven</th>
                            <th width="40px"> </th>
                        </tr>
                    </thead>
                    <tbody id="assayBody">
                    </tody>
                </table>

                <button onclick="loadAssays(true)" class="btn btn-mini">Herlaad met ID's </button>

            </div>

            <div class="well">
                <h6><i class="icon-save"></i> {MESA_ERP_SAVEANDREVISE}  </h6>
                <div class="pull-left">
                    <select id="revisionSelector" class="input-block-level">{revision_dropdown}</select>
                </div>

                <div class="pull-right">
                    <!-- <button id="saveProfileChangesButton" type="button" class="btn btn-small btn-danger"><i class="icon-warning-sign"></i> {MESA_ERP_FORCECHANGES} </button> -->
                    <button id="reviseProfileChangesButton" type="button" class="btn btn-small btn-success"><i class="icon-save"></i> {MESA_ERP_REVISE} </button>
                </div>
                <br />
            </div>




            <div class="well" id="copyform">
                    <h6><i class="icon-copy"></i> Kopieeren van ander profiel  </h6>

                    <form id="copyAssaysForm" method="POST" action="{LB}/researchProfiles/doCopy"> 
                        <input placeholder="Voer een ID in om te kopieren naar dit profiel" type="input" name="copy_form_origin" id="copy_form_origin" class="input" /> 
                        <input type="hidden" value="{id}" id="copy_form_destination" name="copy_form_destination" /> 
                        <button id="doCopy" type="button">Kopieren</button>     
                    </form> 
                
            </div>

        </span>
        
    </div>



    <div id="addAssayModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addAssayModalTitle" aria-hidden="true">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="addAssayModalTitle">{MESA_ERP_ADDTORESPROF}</h3>
        </div>
        <div class="modal-body" id="addAssayModalBody" >
            {add_assay_form}
        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_ERP_CANCEL}</button>
            <button type="button" class="btn btn-primary" id="addAssaySubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_ERP_SAVE}</button>
            <button type="button" class="btn btn-primary" id="editAssaySubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_ERP_EDIT}</button>
        </div>
    </div>


  <script>

    var openAssay = false;
    var selectedAssays = [];

    $('#forceChanges').val('0');
    revision_istip = '{revision_istip}';

    $(function() {

        loadAssays(false);

        if(revision_istip == '0'){
           $('#forceChanges').val('1');
           $('#reviseProfileChangesButton').hide();
           $('#copyform').hide();


       }

       $('#doCopy').on('click', function(){
        
        var dest = $('#copy_form_destination').val();
        var origin = $('#copy_form_origin').val();

        var doCopy = confirm("Analyses uit  " + origin + " kopieren naar dit profiel: " + dest + "?");
        
        if (doCopy == true) {
            $('#copyAssaysForm').submit();
        }

       });

        $('#assayBody').on('click', '.up,.down', function(){

           var row = $(this).parents("tr:first");
           if ($(this).is(".up")) {
               row.insertBefore(row.prev());
           } else {
               row.insertAfter(row.next());
           }

           recheckOrder();
        });


        //catch for assay click
        $('#assaysList, #searchList').on('click', 'a', function(){

            $('#editAssaySubmit').hide();
            $('#addAssaySubmit').show();

            openAssay = $(this).attr('assayid');
            if(openAssay in selectedAssays){
                alert('{MESA_ERP_ALREADYREQ}');
                return;
            }


            $.ajax({
                type: "POST",
                url: "{LB}/researchProfiles/updateAssayForm/{id}/" + $(this).attr('assayid') + "/" + $('#client').val()
            }).done(function(msg) {

                $('#addAssayModalBody').html(msg);
                $('#addAssayModal').modal('show');
            });
        });

        $('#addAssaySubmit').on('click', function(){

         

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

            $('#addAssayModal').modal('hide');
            var setObj = {};

            $('#addAssayForm *').filter(':input').each(function () {
              setObj[$(this).attr('name')] = $(this).val();
            });

            console.log(setObj);

            addAssay(openAssay, setObj);
        });

        $('#editAssaySubmit').on('click',function(){

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


            var hadOrder = selectedAssays[openAssay]['order'];
            delete selectedAssays[openAssay];
            $('#assay_' + openAssay).remove();

            var setObj = {};

            $('#addAssayForm *').filter(':input').each(function () {
              setObj[$(this).attr('name')] = $(this).val();
            });


            //copy the previous order
            setObj['order'] = hadOrder;

            addAssay(openAssay, setObj);

            $('#addAssayModal').modal('hide');

        });


        //check if i should hide
        if($('#global').val() == '1'){
            $('#client_nameCG').hide();
        } else{
            $("#client_name").select2("val", "{client}");
            $('#client').val('{client}');            
        }

        $('#global').on('change', function(){

            if($(this).val() == '1'){
                //global
                $('#client_nameCG').hide();
                $('#client_name').val('');
                $('#client').val('');
            }

            if($(this).val() == '0'){
                //client specific
                $('#client_nameCG').show();
                            
            }
         });

           $("#client_name").select2({
            minimumInputLength: 2,
            placeholder: "Select a client",

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
                    return $.getJSON("{LB}/clients/predictInit/" + (element.val()), null, function(data) {
                    return callback(data);
                });
            },
            dropdownCssClass: "bigdrop"
        }).on('change', function() {
            $('#client').val($(this).val());
        });


        $('#saveProfileChangesButton').on('click', function(){
              $('#forceChanges').val('1');
              save();
        });

        $('#reviseProfileChangesButton').on('click', function(){
              $('#forceChanges').val('0');
              save();
        });

        $('#revisionSelector').on('change', function(){
            selectedRevision = $(this).val();
            window.location.href = '{LB}/researchProfiles/edit/' + selectedRevision;
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

        $('#matrixSearcher').on("keyup", function() {
            var value = $(this).val();
            liveSearchTable(value, 'assaysList');
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
                data: { search: searchTerm , searchTermQ: searchTermQ, customer_id: false },
                url: "{LB}/samples/searchAvailResearch/true"
            }).done(function(response) {
                $('#searchList').html(response);
            });
        });

        loadInMatrix();
    });

    function addAssay(assayId, settings){

        selectedAssays[assayId] = settings;

        var currentNumberOfAssays =  $('#assayBody tr').length;
        var thisNewOrder =  selectedAssays[assayId]['order'];

        if (typeof thisNewOrder == 'undefined') {
          selectedAssays[assayId]['order'] = currentNumberOfAssays+1;
        }

        $.ajax({
            type: "POST",
            data: {data: selectedAssays},
            url: "{LB}/assayProfiles/renderAssayRequest"
        }).done(function(msg) {
            $('#assayBody').html(msg);
            recheckOrder();
        });

    }

    function removeAssay(id){
        delete selectedAssays[id];
        $('#assay_' + id).remove();
        recheckOrder();
    }

    function save(){

        var forceChanges = $('#forceChanges').val();
        var name = $('#pname').val();
        var original_id = $('#original_id').val();
        var global = $('#global').val();
        var client = $('#client_name').val();
        var portalVisible = $('#portal_visible').val();
        var limsVisible = $('#lims_visible').val();

        $.ajax({
            type: "POST",
            data: { 
                forceChanges: forceChanges, 
                name: name, 
                original_id: original_id, 
                global: global, 
                client: client, 
                data: selectedAssays, 
                lims_visible : limsVisible,
                portal_visible: portalVisible},
            url: "{LB}/researchProfiles/updateProfile/{id}"
        }).done(function(msg) {
           // window.location.href = '{LB}/researchProfiles/edit/' + msg;
            window.location.href = '{LB}/researchProfiles/listing';
        });

    }

    function loadAssays(showid){
        $.ajax({
            type: "POST",
            dataType: "json",
            data: {showid: showid},
            url: "{LB}/assayProfiles/renderAllAssays/{id}"
        }).done(function(obj) {
            console.log(obj);
            $('#assayBody').html(obj['html']);
            selectedAssays =  $.parseJSON(obj['javascript']);
        });
    }

    function editAssay(id){

        openAssay = id;
        $('#editAssaySubmit').show();
        $('#addAssaySubmit').hide();


        $.ajax({
                type: "POST",
                url: "{LB}/researchProfiles/updateAssayForm/{id}/" + id +  "/" + $('#client').val()
            }).done(function(msg) {

                $('#addAssayModalBody').html(msg);                

                for (var keyname in selectedAssays[id]) {
                    $('#' + keyname).val(selectedAssays[id][keyname]);
                }

                $('#addAssayModal').modal('show');
            });
    }

    function recheckOrder(){

      var oderValue = 1;
      $('#assayBody tr').each(function(){
        var assayId = $(this).attr('assayId');
        selectedAssays[assayId]['order'] = oderValue;
        //var assayName = selectedAssays[assayId]['name'];
        //console.log('Assay id ' + assayId + ' name: ' + assayName + ', should now have order:' + oderValue);
        oderValue = oderValue + 1;
      });
    }

    // function up(id){
    //
    //   $.ajax({
    //         type: "POST",
    //         url: "{LB}/assayProfiles/moveUp/{id}/" + id
    //   }).done(function(msg) {
    //     loadAssays();
    //   });
    //
    //
    // }
    //
    // function down(id){
    //   $.ajax({
    //         type: "POST",
    //         url: "{LB}/assayProfiles/moveDown/{id}/" + id
    //   }).done(function(msg) {
    //     loadAssays();
    //   });
    // }

    </script>
