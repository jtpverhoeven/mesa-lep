<div class="span8">   
    <div class="well">      
        
        <h3> {action}</h3>

        <form id="createNewClientForm" class=" form-horizontal" action="{LB}/clients/saveNewClient" method="POST">

        <h5>Basis gegevens</h5>
        
        {cname} 

        <div class="control-group" id="nameExistsWarning">
            <label class="control-label">
                <i class="icon-exclamation"></i>
            </label>

            <div class="controls">
                <p style="font-weight: bold; color: red;">
                    Deze naam lijkt mogelijk al te bestaan, is deze naam hetzelfde als: 
                    <span id="nameExistsWarningLabel"></span>?
                </p>
            </div>
        </div>


        {street_name}

        {street_number}

        {postal_code}

        
        <div class="control-group" id="adresExistsWarning">
            <label class="control-label">
                <i class="icon-exclamation"></i>
            </label>

            <div class="controls">
                <p style="font-weight: bold; color: red;">
                    Dit adres is al in gebruik, is deze klant een duplicaat van:
                    <span id="adresExistsWarningLabel"></span>?
                </p>
            </div>
        </div>


        {place}

        {country}

        {telephone}

        {cellphone}

        {email}

        {title}

        {fname}

        {mname}

        {lname}

        {nvwa_number}

        {debit_number}
        
        <h5>Klant categorie</h5>
        
        {cat_selector}

        <h5>Email adressen in contact groep</h5>

        <table class="table table-condensed table-bordered" style="margin-top: 20px" id="cgSelectTable">
            <thead>
                <tr>                    
                    <th>Adresering</th>
                    <th>Email</th>
                         
                </tr>
            </thead>

            <tbody id="emailtbody">
                
                <tr id="tr_1">
                    <td><input type="text" id="cg_name_1" name="cg_name_1" /></td>
                    <td><input type="text" id="cg_email_1" name="cg_email_1" /></td>
                </tr>

         
            
            </tbody>
        
        </table>
        
        <button class="btn btn-mini btn-default" type="button" id="addEmailButton">Toevoegen</button>

        
        <button type="button" id="save" name="save" class="btn btn-primary"><i id="saveIcon" class="icon-save"></i> Opslaan</button>

        <input type="hidden" name="categories_selected" id="categories_selected" value="" />

        </form>
     
    </div>
</div>

<div class="span2">
    <ul class="nav nav-list well">                                     
        
        <li class="nav-header">{MESA_CLI_CLIENTS} </li>
        <li><a href="{LB}/clients/show/{id}"><i class="icon-eye-open"></i> {MESA_CLI_BACKTOCLIENT}</a> </li>
        
        <li class="nav-header">Other </li>
        <li><a href="{LB}/clients/dashboard/"><i class="icon-backward"></i> {MESA_CLI_BACKTODASH} </a> </li>
        <li><a href="{LB}/clients/listing/"><i class="icon-list"></i> {MESA_CLI_BACKTOLIST} </a> </li>

    </ul>         
</div>

<script>
    
    var categories_selected = []; 
    var emails_set = [];

    $(function() {
        
        $('#nameExistsWarning').hide(); 
        $('#adresExistsWarning').hide();

        $('#cname').on('change', function(){

            checkClientExists();            
        }); 

        $('#street_name').on('change', function(){

            checkClientAdresExists();            
        });

        $('#street_number').on('change', function(){

            checkClientAdresExists();            
        });

        $('#postal_code').on('change', function(){

            checkClientAdresExists();            
        });



        $('#addEmailButton').click(function() {
            add_email_row();
        });

        $('#save').click(function(){
            checkAndSubmitForm();
        });

        //when selecting or deselecting a categorie, (checkbox with class catSelector) add or remove from categories_selected
        $('.catSelector').click(function() {
            var cat_id = $(this).val();
            if($(this).is(':checked')) {
                categories_selected.push(cat_id);
            } else {
                categories_selected = categories_selected.filter(function(value) {
                    return value != cat_id;
                });
            }

            console.log(categories_selected);
        });


        
    });

    function add_email_row()
    {

        var row_id = document.getElementById('emailtbody').rows.length;
        var row_id = row_id + 1;
        var row = '<tr id="tr_' + row_id + '">';
        row += '<td><input type="text" name="cg_name_' + row_id + '" /></td>';
        row += '<td><input type="text" name="cg_email_' + row_id + '" /></td>';
        row += '</tr>';

        $('#cgSelectTable tbody').append(row);

        emails_set.push(row_id);

    }

    function checkAndSubmitForm()
    {



        //required fields        
        //cname, street_name, street_number, postal_code, place, country, debit_number
        let warnings = {
            'cname': 'Bedrijfsnaam',
            'street_name': 'Straatnaam',
            'street_number': 'Huisnummer',
            'postal_code': 'Postcode',
            'place': 'Plaats',
            'country': 'Land'
            //'debit_number': 'Debiteurnummer'
        };

        //simpel error check, don't submit form and show an alert with the textual warning if mising
        for (let key in warnings) {
            if($('#' + key).val() == '') {
                alert(warnings[key] + ' is verplicht');
                return;
            }
        }
                
        //minimum category selected = 1
        var no_cats_selected = categories_selected.length;

        if (no_cats_selected < 1) {
            alert('Selecteer minimaal 1 categorie');
            return;
        }

        //minimum email filled in = 1
        cg_name = $('#cg_name_1').val();
        cg_email = $('#cg_email_1').val();

        if (cg_name == '' || cg_email == '') {
            alert('Vul minimaal 1 email adres voor contactlijst in');
            return;
        }

        //encode categories_selected into hidden field
        $('#categories_selected').val(JSON.stringify(categories_selected));
        
        //submit form
        $('#createNewClientForm').submit();       

        

    }

    function checkClientExists()
    {

        var cname = $('#cname').val();

        $.ajax({
            type: "POST",
            data: {name: cname},
            dataType: 'json',
            url: '{LB}/clients/checkClientAlreadyExists'
        }).done(function(req) {
        
            if(req['exists']== true) {
                
                 $('#nameExistsWarning').show();
                 $('#nameExistsWarningLabel').text(req['candidate']['text']);
            } 
            
            else 
            {
                $('#nameExistsWarning').hide();
            }
            
        });
        
    }

    function checkClientAdresExists()
    {

        var street_name = $('#street_name').val();
        var street_number = $('#street_number').val();
        var postal_code = $('#postal_code').val();        

        $.ajax({
            type: "POST",
            data: {street_name: street_name, street_number: street_number, postal_code: postal_code},
            dataType: 'json',
            url: '{LB}/clients/checkClientAdresAlreadyExists'
        }).done(function(req) {
        
            if(req['exists']== true) {
                
                 $('#adresExistsWarning').show();
                 $('#adresExistsWarningLabel').text(req['candidate']['text']);
            } 
            
            else 
            {
                $('#adresExistsWarning').hide();
            }
            
        });

    }

</script>