<div class="span2">

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

            loadOverview();

            $('#scan_field').val('');

            $('#scan_field').focus();
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



    function scan(barcode)
    {

        console.log(barcode);

        loadOverview(barcode);

        $.playSound('{LP}/snd/scan.wav');

        $('#scan_field').focus();

    }

    function dropProject(projectId)
    {
        console.log('drop project ' + projectId );

        console.log(scannedProjects);

        var index = scannedProjects.indexOf(projectId);

        console.log(index);
        if(index > -1)
        {
            scannedProjects.splice(index, 1);
        }

        loadOverview();
    }


    function loadOverview(barcode)
    {
            
            var projects = scannedProjects.join(',');
    
            $.ajax({
                type: "POST",
                dataType: "json",
                url: "/billing/createBillingOverview",
                data: {
                    projects: projects,
                    barcode: typeof barcode !== 'undefined' ? barcode : null
                },
                success: function(data) {

                    if(data.status == 'client_mismatch')
                    {
                        $.playSound('{LP}/snd/scanDeny.wav');
                        alert('De geselecteerde projecten zijn niet van dezelfde klant');
                        return;
                    }

                    if(data.status == 'no_projects')
                    {                        
                        return;
                    }

                    if(data.status == 'barcode_not_found')
                    {
                        $.playSound('{LP}/snd/scanDeny.wav');                        
                        alert('Barcode niet gevonden');                     
                    }

                    //this return a key called projects, replace the current projects with the new ones
                    scannedProjects = data.projects;
                                    

                    $('#billingTypeTable').html(data.billingTypeTable);
                    $('#projectSearchTableBody').html(data.projectSearchTableBody); 
                    $('#billedArticlesTable').html(data.billedArticlesTable);
                    $('#clientNameTd').html(data.client_name);
                    $('#debitNumberTd').html(data.debit_number);
                    $('#clientCatTd').html(data.client_cats);

                }
            });
    }


</script>