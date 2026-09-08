<div class="span8">

  <div class="well">
    <form method="POST" id="dataminingForm" action="{LB}/dataMining/doExport" target="_blank">
    <h4>Resultaten uitvoeren  </h4>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th colspan="2">Scope van export</th>
        </tr>
      </thead>
      <tbody>
      <tr>
        <td>Klant</td>
        <td>{client_name}</td>
      </tr>

      <tr >
        <td>Scope</td>
        <td>{scope_selector}</td>
      </tr>

      <tr id="dateRow1">
        <td>Start datum</td>
        <td>{date_start}</td>
      </tr>
      <tr  id="dateRow2">
        <td>Eind datum</td>
        <td>{date_end}</td>
      </tr>

      <tr  id="projectRow">
        <td>Selecteer project</td>
        <td>{project_selector}</td>
      </tr>


      <tr id="exportTypeRow">
        <td>Type export</td>
        <td>{export_selector}</td>
      </tr>

      <tr id="assaySelectorRow">
        <td>Analyses uit te voeren</td>
        <td>{assay_selector}</td>
      </tr>

      </tbody>
    </table>

     <table class="table table-bordered">
      <thead>
        <tr>
          <th colspan="2">Opties</th>
        </tr>
      </thead>
      <tbody>
      <tr class="hidden">
        <td>Formaat</td>
        <td>{export_format}</td>
      </tr>

      <tr>
        <td>Verborgen analyses ook uitvoeren?</td>
        <td>{show_hidden}</td>
      </tr>

      <tr>
        <td>Teken voor CSV cell-delineatie</td>
        <td>{seperator}</td>
      </tr>

      <tr>
        <td>Export versie</td>
        <td>{version_selector}</td>
      </tr>


      <tr>
        <td>Uitvoeren</td>
        <td><button id="exportButton" type="button" class="btn btn-success"><i class="icon-save"></i> Exporteren </button></td>
      </tr>

      </tbody>
    </table>
    </form>
</div>
</div>

<div class="span2">

  <p>
    <strong>Wat te doen met een CSV bestand?</strong> <br />

    Het bestand type CSV wordt gebruikt om data op te slaan.
    Open dit met Microsoft Excel om direct alle data in een kolom/rij formaat
    te vekrijgen. <br />

    <a href="{LP}/img/export.gif" ><img src="{LP}/img/export.gif" /></a>

  </p>


</div>


<script>

var selectedClient = false;

var old_clients = {old_version_clients};

$(function(){
  
  
  

  $('#assaySelectorRow').hide();
  $('#projectRow').hide();

  $('#exportButton').on('click', function(){

    if(!$('#client_name').val()){
      alert('Geen klant geselecteerd!');
    } else{
      $('#dataminingForm').submit();
    }

  });

  $('#export_selector').on('change', function(){

    if($(this).val() == 'selection'){
      $('#assaySelectorRow').show();
    } else{
      $('#assaySelectorRow').hide();
    }

  });

  $('#scope_selector').on('change', function(){

    if($(this).val() == 'project'){
      $('#dateRow1').hide();
      $('#dateRow2').hide();
      $('#exportTypeRow').hide();
      $('#export_selector').val('all');
      $('#projectRow').show();
    } else{
      $('#dateRow1').show();
      $('#dateRow2').show();
      $('#exportTypeRow').show();
      $('#projectRow').hide();
    }

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
                        return $.getJSON("{LB}/clients/predictInit/" + (element.val()), null, function(data) {
                            return callback(data);
                        //return $.getJSON("/ajax/select2_sample.php?id=" + (element.val()), null, function(data) {
                        //return callback(data);
                        });
            },
      dropdownCssClass: "bigdrop"
     }).on('change', function(){
        
        selectedClient = $(this).val();
       
        //update the version selector based on the client selected
        versionUpdater(selectedClient);

        // if(old_clients.indexOf(parseInt(selectedClient)) > -1){

        //   //set to "v1"
        //   $('#version_selector').val('v1');
        //   $('#version_selector').trigger('change');

        // }

        // else{
        //   //set to "v2"
        //   $('#version_selector').val('v2');
        //   $('#version_selector').trigger('change');
        // }

          
        




     });

  $("#project_selector").select2({
          minimumInputLength: 2,
          placeholder: "{MESA_SAD_SELECTCLIENT}",
          ajax: {
          type: "POST",
          url: "{LB}/projects/predict",
          dataType: 'json',
          quietMillis: 350,
          data: function (term, page) {
              return {
                  term: term, //search term,
                  client: selectedClient,
                  page_limit: 10 // page size
              };
          },
          results: function (data, page) {
              return { results: data.results };
          }
          },
          initSelection: function(element, callback) {
                        return $.getJSON("{LB}/projects/predictInit/" + (element.val()), null, function(data) {
                            return callback(data);
                        //return $.getJSON("/ajax/select2_sample.php?id=" + (element.val()), null, function(data) {
                        //return callback(data);
                        });
            },
      dropdownCssClass: "bigdrop"
     });

    $('#date_start').Zebra_DatePicker({
        format: 'd-m-Y',
        zero_pad: true,
        show_icon: false,
        offset: [10, 200],
        readonly_element: false,
    });


    $('#date_end').Zebra_DatePicker({
        format: 'd-m-Y',
        zero_pad: true,
        show_icon: false,
        offset: [10, 200],
        readonly_element: false,
    });

    $("#assay_selector").select2({
          minimumInputLength: 2,
          placeholder: "{MESA_ASE_SELECTASSAYS}",
          multiple: true,

          ajax: {
          type: "POST",
          url: "{LB}/assays/predict/0/yes",
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
                return $.getJSON("{LB}/assays/predictInit/" + (element.val()), null, function(data) {
                return callback(data);
            });
      },
      dropdownCssClass: "bigdrop"
     });

    $('#scope_selector').trigger('change');

    versionUpdater($('#client_name').val());

});

function versionUpdater(client_selected)
{

  console.log('versionUpdater called with: ' + client_selected);

  if(old_clients.indexOf(parseInt(client_selected)) > -1){
    //set to "v1"

    console.log('setting version to v1');

    $('#version_selector').val('v1');
    $('#version_selector').trigger('change');
  } else {
    //set to "v2"
    $('#version_selector').val('v2');
    $('#version_selector').trigger('change');
  }



}

</script>
