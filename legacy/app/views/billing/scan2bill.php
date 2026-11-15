<div class="span2">

    <div class="well">

        <h6><i class="icon-building"></i> Selecteren op klant en periode </h6>

        <div class="control-group">
            <div class="controls">
                <input id="billing_client_name" type="text"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    class="select2-input select2-default input-block-level"
                    placeholder="Selecteer klant"
                />
                <input id="billing_client" name="billing_client" type="hidden" />
            </div>
        </div>

        <div class="control-group">
            <div class="controls">
                <div class="input-prepend input-append input-block-level">
                    <span class="add-on">Inzetdatum van</span>
                    <input type="text" id="billing_date_from" name="billing_date_from" value="" class="input-block-level" placeholder="Van" />
                </div>
            </div>
        </div>

        <div class="control-group">
            <div class="controls">
                <div class="input-prepend input-append input-block-level">
                    <span class="add-on">Inzetdatum t/m</span>
                    <input type="text" id="billing_date_to" name="billing_date_to" value="" class="input-block-level" placeholder="Tot" />
                </div>
            </div>
        </div>

        <div class="control-group">
            <div class="controls">
                <select id="range_billing_status" name="range_billing_status" class="input-block-level">
                    <option value="0" selected="selected">Nog te factureren</option>
                    <option value="1">Gefactureerd</option>
                    <option value="all">Alles</option>
                </select>
            </div>
        </div>

        <button style="margin-top: 10px; margin-bottom: 10px;" id="rangeSearchButton" type="button" class="btn btn-primary btn-mini"><i class="icon-search"></i> {MESA_PLU_SEARCH} </button>
    </div>

    <div class="well">

        <h6><i class="icon-search"></i> Scannen voor facturatie </h6>

        <div class="control-group" id="project_search_sample_cg">
            <div class="controls">
                <div class="input-prepend input-append input-block-level">
                    <span class="add-on"><i class="icon-barcode"></i></span>
                    <input type="text" id="scan_field" name="scan_field" value=""  class="input-block-level" placeholder="Scan of typ een project referentie" />
                </div>
            </div>
        </div>

        <div class="control-group">
            <div class="controls">
                <select id="scan_billing_status" name="scan_billing_status" class="input-block-level">
                    <option value="0" selected="selected">Nog te factureren</option>
                    <option value="1">Gefactureerd</option>
                    <option value="all">Alles</option>
                </select>
            </div>
        </div>

        <table width="100%">

            <tbody>
                <tr>
                    <td><button style="margin-top: 10px; margin-bottom: 10px;" id="resetButton" type="button" class="btn btn-primary btn-mini"><i class="icon-trash"></i> Opnieuw beginnen </button></td>

                    <td style="text-align: right;"><button style="margin-top: 10px; margin-bottom: 10px;" id="searchButton" type="button" class="btn btn-primary btn-mini"><i class="icon-search"></i> {MESA_PLU_SEARCH} </button></td>

                </tr>
            </tbody>

        </table>

    </div>




</div>

<div class="span8">
    <div class="well">
        <h6><i class="icon-eur"></i> Project gegevens voor facturatie </h6>

        <h6><i class="icon-building"></i> Klant </h6>        
        
        <div id="clientTable">
            
            <table style="width: 100%">
                <tbody>
                    <tr>                        
                        <td id="clientNameTd"></td>                      
                        <td id="debitNumberTd"></td>                      
                        <td id="clientCatTd"></td>
                    </tr>
                </tbody>
            </table>

        </div>

        <h6><i class="icon-sitemap"></i> Ingescande projecten </h6>
        <table class="table table-condensed hltable" id="">
            <thead>
                <tr>
                <th>Facturatie</th>
                <th>Status</th>
                <th>Referentie MAZ</th>
                <th>Revisie</th>
                <th>Referentie klant</th>
                <th>Aantal monsters</th>
                <th>Bemonsterdatum</th>
                <th>Ontvangstdatum</th>
                <th>Inzetdatum</th>           
                <th>Verwijder</th>                
            </tr>
            </thead>
            <tbody id="projectSearchTableBody">

            </tbody>
        </table>

        <div style="margin-bottom: 15px;">
            <img src="{LP}/images/arrow_ltr.png" alt="" />
            <button id="markProjectsBilledButton" type="button" class="btn btn-primary btn-mini">Instelling als gefactureerd</button>
            <button id="downloadBillingExportButton" type="button" class="btn btn-primary btn-mini">Download uitvoer</button>
        </div>

        <h6><i class="icon-beaker"></i> Monsters &amp; toeslagen </h6>
        
        <div id="billingTypeTable">

        </div>


        <h6><i class="icon-search"></i> Analyses </h6>

        <div id="billedArticlesTable">

        </div>





    </div>
</div>

<script>

    var scannedProjects = [];
    var activeBillingStatus = '0';

    $(document).ready(function() {

        $('#scan_field').focus();

        $('#scan_field').keypress(function(e) {
            if(e.which == 13) {
                var barcode = $(this).val();
                scan(barcode);
               // $(this).val('');
            }
        });

        $('#searchButton').click(function() {
            var barcode = $('#scan_field').val();
        
            scan(barcode);
        
        });
        
        $("#resetButton").click(function() {
            
            scannedProjects = [];
            activeBillingStatus = '0';

            $('#scan_field').val('');
            $('#billing_client_name').select2('val', '');
            $('#billing_client').val('');
            $('#billing_date_from').val('');
            $('#billing_date_to').val('');
            $('#scan_billing_status').val('0');
            $('#range_billing_status').val('0');

            loadOverview();

            $('#scan_field').focus();
        }); 

        $("#rangeSearchButton").click(function() {

            var client = $('#billing_client').val();
            var dateFrom = $('#billing_date_from').val();
            var dateTo = $('#billing_date_to').val();

            if(client == '' || dateFrom == '' || dateTo == '')
            {
                alert('Selecteer een klant en een datum van en tot');
                return;
            }

            loadOverview(undefined, client, dateFrom, dateTo, $('#range_billing_status').val());
        });

        $("#markProjectsBilledButton").click(function() {

            if(scannedProjects.length == 0)
            {
                alert('Geen projecten geselecteerd');
                return;
            }

            $.ajax({
                type: "POST",
                dataType: "json",
                url: "/billing/markProjectsBilled",
                data: {
                    projects: scannedProjects.join(',')
                },
                success: function(data) {

                    if(data.status == 'ok')
                    {
                        $('#projectSearchTableBody .project-billed-status').each(function() {
                            updateBilledStatus(this, '1');
                        });
                        return;
                    }

                    if(data.status == 'no_projects')
                    {
                        alert('Geen projecten geselecteerd');
                        return;
                    }

                    alert('Projecten konden niet als gefactureerd worden ingesteld');
                },
                error: function() {
                    alert('Projecten konden niet als gefactureerd worden ingesteld');
                }
            });
        });

        $("#downloadBillingExportButton").click(function() {

            if(scannedProjects.length == 0)
            {
                alert('Geen projecten geselecteerd');
                return;
            }

            var exportForm = $('<form>', {
                method: 'POST',
                action: '/billing/exportBillingCSV'
            });
            exportForm.append($('<input>', {
                type: 'hidden',
                name: 'projects',
                value: scannedProjects.join(',')
            }));
            exportForm.append($('<input>', {
                type: 'hidden',
                name: 'separator',
                value: getBillingExportSeparator()
            }));
            $('body').append(exportForm);
            exportForm.submit();
            exportForm.remove();
        });

        $("#billing_client_name").select2({
            minimumInputLength: 2,
            placeholder: "Selecteer klant",

            ajax: {
                type: "POST",
                url: "{LB}/clients/predictNoSub",
                dataType: 'json',
                quietMillis: 350,
                data: function (term, page) {
                    return {
                        term: term,
                        page_limit: 10
                    };
                },
                results: function (data, page) {
                    return { results: data.results };
                }
            },
            dropdownCssClass: "bigdrop"
        }).on('change', function(){

            var clientValue = $(this).val();
            var clientId = clientValue == '' ? '' : clientValue.split("||")[0];
            var clientChanged = $('#billing_client').val() != clientId;

            $('#billing_client').val(clientId);

            if(clientChanged)
            {
                clearProjectSelection();
            }
        });

        $('#billing_date_from, #billing_date_to').Zebra_DatePicker({
            format: 'd-m-Y',
            zero_pad: true,
            show_icon: false,
            offset: [10, 200],
            readonly_element: false
        });

        loadOverview();

        
        //on click select all the text
        $('#scan_field').on('click', function(){
            this.select();
        });

        $('#scan_field').on('focus', function(){
            this.select();
        });
    });

    function toggleProjectBilled(statusElement)
    {
        var currentlyBilled = $(statusElement).attr('data-billed') == '1';
        var newBilled = currentlyBilled ? '0' : '1';
        var confirmationMessage = currentlyBilled
            ? 'Terug instellen op "Niet gefactureerd"?'
            : 'Instellen als gefactureerd?';

        if(!confirm(confirmationMessage))
        {
            return;
        }

        $.ajax({
            type: "POST",
            dataType: "json",
            url: "/billing/setProjectBilled",
            data: {
                project: $(statusElement).attr('data-project-id'),
                billed: newBilled
            },
            success: function(data) {

                if(data.status == 'ok')
                {
                    updateBilledStatus(statusElement, data.billed);
                    return;
                }

                alert('De facturatiestatus kon niet worden aangepast');
            },
            error: function() {
                alert('De facturatiestatus kon niet worden aangepast');
            }
        });
    }

    function updateBilledStatus(statusElement, billed)
    {
        var isBilled = billed == '1' || billed === 1;
        $(statusElement)
            .attr('data-billed', isBilled ? '1' : '0')
            .text(isBilled ? 'Gefactureerd' : 'Nog te factureren')
            .removeClass(isBilled ? 'label-warning' : 'label-success')
            .addClass(isBilled ? 'label-success' : 'label-warning');
    }

    function clearProjectSelection()
    {
        scannedProjects = [];
        loadOverview();
    }

    function getBillingExportSeparator()
    {
        var locale = navigator.language || (navigator.languages && navigator.languages[0]) || '';

        if(window.Intl && locale)
        {
            try
            {
                return new Intl.NumberFormat(locale).format(1.1).indexOf(',') !== -1 ? ';' : ',';
            }
            catch(error)
            {
                // Fall through to the locale prefix fallback.
            }
        }

        return /^(nl|de|fr|es|it|pt|da|sv|no|fi|pl|cs|sk|hu|ro|tr)(-|$)/i.test(locale) ? ';' : ',';
    }



    function scan(barcode)
    {

        console.log(barcode);

        loadOverview(barcode, undefined, undefined, undefined, $('#scan_billing_status').val());

        $.playSound('{LP}/snd/scan.wav');

        $('#scan_field').focus();

    }

    function dropProject(projectId)
    {
        console.log('drop project ' + projectId );

        console.log(scannedProjects);

        var index = scannedProjects.indexOf(parseInt(projectId, 10));

        console.log(index);
        if(index > -1)
        {
            scannedProjects.splice(index, 1);
        }

        loadOverview();
    }


    function loadOverview(barcode, client, dateFrom, dateTo, billingStatus)
    {
            if(typeof billingStatus === 'undefined' || billingStatus === null)
            {
                billingStatus = activeBillingStatus;
            }
            else
            {
                activeBillingStatus = billingStatus;
            }

            var projects = scannedProjects.join(',');
    
            $.ajax({
                type: "POST",
                dataType: "json",
                url: "/billing/createBillingOverview",
                data: {
                    projects: projects,
                    barcode: typeof barcode !== 'undefined' ? barcode : null,
                    client: typeof client !== 'undefined' ? client : null,
                    date_from: typeof dateFrom !== 'undefined' ? dateFrom : null,
                    date_to: typeof dateTo !== 'undefined' ? dateTo : null,
                    billing_status: billingStatus
                },
                success: function(data) {

                    if(data.status == 'client_mismatch')
                    {
                        if(typeof barcode !== 'undefined' && barcode !== null && barcode !== '')
                        {
                            var startNewSelection = confirm('De gescande barcode hoort bij een andere klant. Wilt u de huidige selectie wissen en deze barcode als nieuwe selectie starten?');

                            if(startNewSelection)
                            {
                                scannedProjects = [];
                                loadOverview(barcode, undefined, undefined, undefined, billingStatus);
                            }
                        }
                        else
                        {
                            $.playSound('{LP}/snd/scanDeny.wav');
                            alert('De geselecteerde projecten zijn niet van dezelfde klant');
                        }

                        return;
                    }

                    if(data.status == 'no_projects')
                    {
                        if(typeof client !== 'undefined')
                        {
                            alert('Geen projecten gevonden voor deze klant en periode');
                        }

                        scannedProjects = data.projects || [];
                        $('#billingTypeTable').html(data.billingTypeTable || '');
                        $('#projectSearchTableBody').html(data.projectSearchTableBody || '');
                        $('#billedArticlesTable').html(data.billedArticlesTable || '');
                        $('#clientNameTd').html(data.client_name || '');
                        $('#debitNumberTd').html(data.debit_number || '');
                        $('#clientCatTd').html(data.client_cats || '');
                        return;
                    }

                    if(data.status == 'invalid_project_range')
                    {
                        alert('Selecteer een geldige datum van en tot');
                        return;
                    }

                    if(data.status == 'barcode_not_found')
                    {
                        $.playSound('{LP}/snd/scanDeny.wav');                        
                        alert('Barcode niet gevonden');                     
                    }

                    if(data.status == 'barcode_filtered')
                    {
                        alert('Project valt buiten de gekozen facturatiestatus');
                    }

                    //this return a key called projects, replace the current projects with the new ones
                    scannedProjects = data.projects;
                                    

                    $('#billingTypeTable').html(data.billingTypeTable);
                    $('#projectSearchTableBody').html(data.projectSearchTableBody); 
                    $('#billedArticlesTable').html(data.billedArticlesTable);
                    $('#clientNameTd').html(data.client_name);
                    $('#debitNumberTd').html(data.debit_number);
                    $('#clientCatTd').html(data.client_cats);

                    if(typeof barcode !== 'undefined' && barcode !== null && barcode !== '' && data.client_id)
                    {
                        $('#billing_client').val(data.client_id);
                        $('#billing_client_name').select2('data', {
                            id: String(data.client_id),
                            text: data.client_name
                        });
                    }

                }
            });
    }


</script>