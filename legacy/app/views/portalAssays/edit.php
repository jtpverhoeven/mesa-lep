<div class="span8">
    <div class="well">
        <h3>LIMS analysis in deze Portal Analyse  </h3>
        <p>Portal analyse: {template_name}</p>

        <table class="table table-bordered table-condensed">
          {ticker_table}
        </table>


    </div>
</div>

<div class="span2">

        <ul class="nav nav-list well">
            <li class="nav-header">Acties </li>
            <li><a href="{LB}/portalAssays/edit/{common_id}/true"><i class="icon-eye-open"></i> Toon ook verborgen analyses </a> </li>
            <li><a href="{LB}/portalAssays/editname/{common_id}"><i class="icon-pencil"></i> Naam &amp; gegevens aanpassen </a> </li>
            <li><a href="{LB}/portalAssays/listing"><i class="icon-backward"></i> Terug naar overzicht </a> </li>


        </ul>

</div>

<script>
$(function(){

    $('.assocTicker').change(function(){
      var assocValue = 0;
      var assocId =  $(this).attr('assocId');
      var assayId =  $(this).attr('assayId');
      var assocOriginalId =  $(this).attr('originalAssayId');
      var assocTemplateId = '{common_id}';

      if ($(this).is(":checked")){
        assocValue = 1;
      }

      $.ajax({
          type: "POST",
          data: {},
          url: "{LB}/portalAssayContent/setAssoc/" + assocTemplateId + '/' + assayId + '/' + assocOriginalId + '/' + assocValue + '/' + assocId
      }).done(function(widget) {

      });

    });

  });

</script>
