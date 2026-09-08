<div class="span10">         

    <div class="row-fluid">
        
        <div class="well well-small">
            <h6><i class="icon-search"></i> Zoek opdracht: </h6>

            <table class="table" style="width: 100%">

                <tr>
         
                <td style="width: 25%">  
                        <strong>Zoek naar:</strong>
                    </td>

                    <td style="width: 25%">  
                        <select id="typeSelector">
                            <option value="sample" selected>Monster</option>
                            <option value="project">Project</option>
                     
                        </select>
                    </td>

                    <td style="width: 25%">  
                        <strong>Van Klant:</strong>
                    </td>

                    <td style="width: 25%">  
                    {client_name}
                    <input type="hidden" id="client_id"  /> 
                    </td>
                
                </tr>
            
            </table>

            <div id="sampleFilters">
                <h6><i class="icon-beaker"></i> Monster filters: </h6>

                <div class="label label-info pb-2 pt-2" style="white-space: normal;">Tip: in de meeste velden kan men een procent-teken gebruiken als wildcard. Bijv:  %Salade kan als resultaat "ei-salade" of "tonijn-salade" vinden. Of %onion% kan als resultaat zowel "onion rings" als "fried onion rings" vinden.  Zonder wild-cards zoekt het systeem op de exacte input in een veld.</div>

                <table class="table pt-2" style="width: 100%; margin-top: 5px;">

                    <tr>
                    
                        <td style="width: 25%">  
                            <div class="flex items-center">
                                <input type="checkbox" value="1" class="pm-0" id="filterOnDescription" />
                                <label for="filterOnDescription" class="pm-0 ml-4 text-normal"><strong>Omschrijving:</strong></label>
                            </div>
                        </td>

                        <td style="width: 25%">  
                            <input id="desriptionValue" type="text" class="input-block" placeholder="Zoekopdracht " value="">                        
                        </td>

                        <td style="width: 25%">  
                            <div class="flex items-center">
                                <input type="checkbox" value="1" class="pm-0" id="filterOnDetails" />
                                <label for="filterOnDetails"  class="pm-0 ml-4 text-normal"><strong>Details:</strong></label>
                            </div>
                        </td>

                        <td style="width: 25%">  
                            <input id="detailsValue" type="text" class="input-block" placeholder="Zoekopdracht" value="">
                        </td>
                
                    </tr>

                    <tr>
                    
                        <td style="width: 25%">  
                            <div class="flex items-center">
                                <input type="checkbox"  class="pm-0" value="1" id="filterOnClientDescription" />
                                <label for="filterOnClientDescription"  class="pm-0 ml-4 text-normal"><strong>Klant omschrijving:</strong></label>
                            </div>
                        </td>

                        <td style="width: 25%">  
                            <input id="clientDescriptionValue" type="text" class="input-block" placeholder="Zoekopdracht " value="">
                        
                        </td>

                        <td style="width: 25%">  
                            <div class="flex items-center">
                                <input type="checkbox"  class="pm-0" value="1" id="filterOnType" />
                                <label for="filterOnType"  class="pm-0 ml-4 text-normal"><strong>Monster type: </strong></label>
                            </div> 
                        </td>

                        <td style="width: 25%">  
                            <select id="sampleTypeValue">
                                <option value="S">Normaal monster</option>
                                <option value="L">Legionella</option>
                                <option value="R">Rodac</option>
                                <option value="T">Monster met THT-code</option>
                            <select>
                        </td>
                
                    </tr>


                    
                    <tr>
                    
                        <td style="width: 25%">  
                            <div class="flex items-center">
                                <input  class="pm-0" type="checkbox" value="1" id="filterOnProductGroups" />
                                <label for="filterOnProductGroups"  class="pm-0 ml-4 text-normal"><strong>Productgroup:</strong></label>
                            </div> 
                        </td>

                        <td style="width: 25%">  

                            <select id="productGroups" class="select2-input select2-default input-block-level" multiple>
                            </select>
                        
                        </td>

                        <td style="width: 25%">  
                        <div class="flex items-center">
                                <input class="pm-0" type="checkbox" value="1" id="filterOnSamplingMethod" />
                                <label for="filterOnSamplingMethod"  class="pm-0 ml-4 text-normal"><strong>Bemonster methode:</strong></label>
                            </div> 
                        </td>

                        <td style="width: 25%">  
                            <select id="samplingMethodValue">
                                {samplingOpts}
                            <select>
                        </td>
                
                    </tr>


                    <tr>
                    
                        <td style="width: 25%">  
                            <div class="flex items-center">
                                <input  class="pm-0" type="checkbox" id="filterOnAssay" value="1" />
                                <label for="filterOnAssay"  class="pm-0 ml-4 text-normal"><strong>Heeft analyses:</strong></label>
                            </div> 
                        </td>

                        <td style="width: 25%">  
                            <select id="assaySub" class="select2-input select2-default input-block-level" multiple>
                                {assayOpts}
                            <select>
                        
                        </td>

                        <td style="width: 25%">  
                            <div class="flex items-center">
                                <input class="pm-0" id="filterOnMetadata" value="1" type="checkbox" />
                                <label for="filterOnMetadata"  class="pm-0 ml-4 text-normal"><strong>Heeft metadata waarde:</strong></label>
                            </div>
                        </td>

                        <td style="width: 25%">  
                            <input id="metadataValue" type="text" class="input-block" placeholder="Zoekopdracht" value="">
                        </td>
            
                    </tr>


                    

                    <tr>
                    
                    <td style="width: 25%">  
                        <div class="flex items-center">
                            <input class="pm-0" type="checkbox" id="filterOnInnoc" value="1"  />
                            <label for="filterOnInnoc"  class="pm-0 ml-4 text-normal"><strong>Inzet datum:</strong></label>
                        </div> 
                    </td>

                    <td style="width: 25%">  
                        <input id="innoc_start" type="text" class="input-block" placeholder="Inzetdatum start" value="">
                        <input id="innoc_end" type="text" class="input-block" placeholder="Inzetdatum t/m" value="">
                    </td>

                    <td style="width: 25%">  
                      
                    </td>

                    <td style="width: 25%">  
                       
                    </td>
        
                    </tr>


                    <tr>
                    
                        <td style="width: 25%">  
                            <div class="flex items-center">
                                <input class="pm-0" type="checkbox" id="maxRecords" value="1" checked />
                                <label for="maxRecords"  class="pm-0 ml-4 text-normal"><strong>Maximum aantal rijen:</strong></label>
                            </div>
                        </td>

                        <td style="width: 25%">  
                            <input id="maxN" type="number" class="input-block" placeholder="Zoekopdracht" value="100">                        
                        </td>

                        <td style="width: 25%">  
                          
                        </td>

                        <td style="width: 25%">  
                           
                        </td>
            
                    </tr>


                </table>

                <button id="doSearchSample"> Zoeken </button> 
          
                <h6><i class="icon-search"></i> Resultaten: </h6>

                <table class="table" style="width: 100%">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Barcode</th>
                        <th>Inzet-datum</th>
                        <th>Omschrijving</th>
                        <th>Klant Omschrijving</th>                 
                        <th>Productgroep</th>
                        <th>Project referentie  &amp; naam</th>
                    </tr>
                </thead>

                <tbody id="resultTableBody">


                </tbody>

                </table>

            </div>

            <div  id="projectFilters"> 

                <h6><i class="icon-suitcase"></i> Project filters: </h6>

                <table class="table" style="width: 100%">

                <tr>
                    <td style="width: 25%">  
                            <div class="flex items-center">
                                <input type="checkbox" value="1" class="pm-0" id="filterOnProjectName" />
                                <label for="filterOnProjectName" class="pm-0 ml-4 text-normal"><strong>Project naam:</strong></label>
                            </div>
                    </td>

                    <td style="width: 25%">  
                        <input id="projectNameValue" type="text" class="input-block" placeholder="Zoekopdracht " value="">                        
                    </td>

                    <td style="width: 25%">  
                            <div class="flex items-center">
                                <input type="checkbox" value="1" class="pm-0" id="filterOnProjectNotes" />
                                <label for="filterOnProjectNotes" class="pm-0 ml-4 text-normal"><strong>Project notities:</strong></label>
                            </div>
                    </td>

                    <td style="width: 25%">  
                        <input id="projectNotesValue" type="text" class="input-block" placeholder="Zoekopdracht " value="">                        
                    </td>

                </tr>

                
                <tr>
                    <td style="width: 25%">  
                            <div class="flex items-center">
                                <input type="checkbox" value="1" class="pm-0" id="filterOnAuthStatus" />
                                <label for="filterOnAuthStatus" class="pm-0 ml-4 text-normal"><strong>Authorisatie status:</strong></label>
                            </div>
                    </td>

                    <td style="width: 25%">  
                        <select id="authStatusValue">
                            <option value="1">Geautoriseerd</option>
                            <option value="0">Niet geautoriseerd</option>
                        </select>                    
                    </td>

                    <td style="width: 25%">  
                            <div class="flex items-center">
                                <input type="checkbox" value="1" class="pm-0" id="filterOnAuthUser" />
                                <label for="filterOnAuthUser" class="pm-0 ml-4 text-normal"><strong>Geautoriseer door:</strong></label>
                            </div>
                    </td>

                    <td style="width: 25%">  
                        <select id="authUserValue">
                            {userOpts}
                        </select>                       
                    </td>

                </tr>

                <tr>
                    <td style="width: 25%">  
                            <div class="flex items-center">
                                <input type="checkbox" value="1" class="pm-0" id="filterOnSamplingDate" />
                                <label for="filterOnSamplingDate" class="pm-0 ml-4 text-normal"><strong>Bemonster datum:</strong></label>
                            </div>
                    </td>

                    <td style="width: 25%">  
                        <input id="project_sampling_start" type="text" class="input-block" placeholder="Bemonster-datum start" value="">
                        <input id="project_sampling_end" type="text" class="input-block" placeholder="Bemonster-datum  t/m" value="">
                    </td>

                    <td style="width: 25%">  
                            <div class="flex items-center">
                                <input type="checkbox" value="1" class="pm-0" id="filterOnArrivalDate" />
                                <label for="filterOnArrivalDate" class="pm-0 ml-4 text-normal"><strong>Ontvangst datum:</strong></label>
                            </div>
                    </td>

                    <td style="width: 25%">  
                        <input id="project_receive_start" type="text" class="input-block" placeholder="Bemonster-datum start" value="">
                        <input id="project_receive_end" type="text" class="input-block" placeholder="Bemonster-datum  t/m" value="">     
                    </td>

                </tr>

                <tr>
                    
                        <td style="width: 25%">  
                            <div class="flex items-center">
                                <input class="pm-0" type="checkbox" id="projectMaxRecords" value="1" checked />
                                <label for="projectMaxRecords"  class="pm-0 ml-4 text-normal"><strong>Maximum aantal rijen:</strong></label>
                            </div>
                        </td>

                        <td style="width: 25%">  
                            <input id="projectMaxN" type="number" class="input-block" placeholder="Zoekopdracht" value="100">                        
                        </td>

                        <td style="width: 25%">  
                          
                        </td>

                        <td style="width: 25%">  
                           
                        </td>
            
                    </tr>

                </table>

                
                <button id="doProjectSearch"> Zoeken </button> 

                <h6><i class="icon-search"></i> Project resultaten: </h6>

                <table class="table" style="width: 100%">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Project referentie</th>
                        <th>Project naam</th>
                        <th>Bemonster datum</th>
                        <th>Ontvangst datum</th>
                        <th>Geautoriseerd</th>
                        <th>Heeft rapportages</th>
                        
                    </tr>
                </thead>

                <tbody id="projectResultTableBody">


                </tbody>

                </table>


             

            </div>

           
        

        </div>

        </div>


    </div>

   
</div>

<script>



$(function(){
    
    var searchType = 'sample';

    $('#sampleFilters').show();
    $('#projectFilters').hide();

    $('#innoc_start').Zebra_DatePicker({
        format: 'd-m-Y',
        zero_pad: true,
        show_icon: false,
        offset: [10, 200],
        readonly_element: false,           
    });

    $('#innoc_end').Zebra_DatePicker({
        format: 'd-m-Y',
        zero_pad: true,
        show_icon: false,
        offset: [10, 200],
        readonly_element: false,           
    });

    $('#project_sampling_start').Zebra_DatePicker({
        format: 'd-m-Y',
        zero_pad: true,
        show_icon: false,
        offset: [10, 200],
        readonly_element: false,           
    });

    $('#project_sampling_end').Zebra_DatePicker({
        format: 'd-m-Y',
        zero_pad: true,
        show_icon: false,
        offset: [10, 200],
        readonly_element: false,           
    });

    $('#project_receive_start').Zebra_DatePicker({
        format: 'd-m-Y',
        zero_pad: true,
        show_icon: false,
        offset: [10, 200],
        readonly_element: false,           
    });

    $('#project_receive_end').Zebra_DatePicker({
        format: 'd-m-Y',
        zero_pad: true,
        show_icon: false,
        offset: [10, 200],
        readonly_element: false,           
    });


    




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
        },
        dropdownCssClass: "bigdrop"
       }).on('change', function(){

           if($(this).val() != '')
           {
                $('#client_id').val($(this).val());
                loadProductGroups($(this).val());
           }
           else
           {
                $('#client_id').val('');
           }
          
            
       });

       $('#assaySub').select2({}).on('change', function(){

            console.log($(this).val());

            if($(this).val() != null)
            {
                $('#filterOnAssay').prop('checked', true);
            } 

            if($(this).val() == null)
            {
                $('#filterOnAssay').prop('checked', false);
            }

       });

       $('#doProjectSearch').click(function(){

            if( $('#client_id').val() == '' ||  $('#client_id').val() == null)
            {
                alert('Selecteer een klant');
                return;
            }

            
            $.ajax({
                type: "POST",
                data: {
                    client_id: $('#client_id').val(),                           
                    max_records : ($('#projectMaxRecords').prop('checked') == '1') ? '1' : '0',
                    max_n : $('#projectMaxN').val(),                                         
                    searchType: $('#typeSelector').val(),
                    filter_project_name : ($('#filterOnProjectName').prop('checked') == '1') ? '1' : '0',
                    project_name_value : $('#projectNameValue').val(),
                    filter_project_notes : ($('#filterOnProjectNotes').prop('checked') == '1') ? '1' : '0',
                    project_notes_value : $('#projectNotesValue').val(),
                    filter_auth_status : ($('#filterOnAuthStatus').prop('checked') == '1') ? '1' : '0',
                    auth_status_value : $('#authStatusValue').val(),
                    filter_auth_user : ($('#filterOnAuthUser').prop('checked') == '1') ? '1' : '0',
                    auth_user_value : $('#authUserValue').val(),
                    filter_sampling_date : ($('#filterOnSamplingDate').prop('checked') == '1') ? '1' : '0',
                    sampling_date_start : $('#project_sampling_start').val(),
                    sampling_date_end : $('#project_sampling_end').val(),
                    filter_arrival_date : ($('#filterOnArrivalDate').prop('checked') == '1') ? '1' : '0',
                    arrival_date_start : $('#project_receive_start').val(),
                    arrival_date_end : $('#project_receive_end').val(),


                    

                },
                url: "{LB}/search/doSearch"
            }).done(function(searchResults) {
                
                $('#projectResultTableBody').html(searchResults);
               

            });            

       });

       $('#doSearchSample').click(function(){


        if( $('#client_id').val() == '' ||  $('#client_id').val() == null)
        {
            alert('Selecteer een klant');
            return;
        }

        $.ajax({
            type: "POST",
            data: { 
                client_id: $('#client_id').val(),
                searchType: $('#typeSelector').val(),
                filter_analysis : ($('#filterOnAssay').prop('checked') == '1') ? '1' : '0',
                filter_assays : $('#assaySub').val(),
                filter_description : ($('#filterOnDescription').prop('checked') == '1') ? '1' : '0',
                description_value : $('#desriptionValue').val(),
                filter_metadata : ($('#filterOnMetadata').prop('checked') == '1') ? '1' : '0',
                metadata_value : $('#metadataValue').val(),
                filter_details : ($('#filterOnDetails').prop('checked') == '1') ? '1' : '0',
                details_value : $('#detailsValue').val(),                
                filter_innoc : ($('#filterOnInnoc').prop('checked') == '1') ? '1' : '0',
                innoc_start : $('#innoc_start').val(),
                innoc_end : $('#innoc_end').val(),
                filter_client_description : ($('#filterOnClientDescription').prop('checked') == '1') ? '1' : '0',
                client_description_value : $('#clientDescriptionValue').val(),      
                filter_sample_type : ($('#filterOnType').prop('checked') == '1') ? '1' : '0',
                sample_type_value : $('#sampleTypeValue').val(),
                filter_on_sampling_method : ($('#filterOnSamplingMethod').prop('checked') == '1') ? '1' : '0',
                sampling_method_value : $('#samplingMethodValue').val(),

                filter_on_product_groups : ($('#filterOnProductGroups').prop('checked') == '1') ? '1' : '0',
                product_groups_value : $('#productGroups').val(),

                

                

                max_records : ($('#maxRecords').prop('checked') == '1') ? '1' : '0',
                max_n : $('#maxN').val()
                
            },
            url: "{LB}/search/doSearch"
        }).done(function(searchResults) {
        
            $('#resultTableBody').html(searchResults);

        });
        

       });

       $('#typeSelector').change(function(){
            
            if($(this).val() == 'sample')
            {
                $('#sampleFilters').show();
                $('#projectFilters').hide();
            }
            
            if($(this).val() == 'project')
            {
                $('#sampleFilters').hide();
                $('#projectFilters').show();
            }
       });


});

function loadProductGroups(clientId)
{

    $.ajax({
        type: "POST",
        data : {},        
        url: "{LB}/productGroups/productGroupDropper/" + clientId
    }).done(function(productGroups) {
    
        $('#productGroups').select("destroy").html(productGroups).select2();        

    });

}


</script>