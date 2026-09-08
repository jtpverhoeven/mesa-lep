<div class="span8">
    <div class="well">
        <h3>Associaties  </h3>
        <p>Rapport sjabloon: {template_name}</p>

        <table class="table table-bordered table-condensed">
          {ticker_table}
        </table>


    </div>
</div>

<div class="span2">

        <ul class="nav nav-list well">
            <li class="nav-header">Acties </li>
            <li><a href="{LB}/docgenAssociations/view/{template_id}/true"><i class="icon-eye-open"></i> Toon ook verborgen analyses </a> </li>            
            <li><a href="#copyModal" role="button"  data-toggle="modal" id="copyModalbtn"><i class="icon-copy"></i> Kopieer van ander rapport </a> </li>
            <li><a href="{LB}/docgenTemplates/listing"><i class="icon-backward"></i> Terug naar overzicht </a> </li>
        </ul>

</div>


<div id="copyModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addAssayModalTitle" aria-hidden="true">
  <form action="{LB}/docgenAssociations/cloneFromOther" method="POST">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addAssayModalTitle">Kopieer van ander rapport</h3>
    </div>
    <div class="modal-body" id="addAssayModalBody" >
        
    
    <div class="control-group" id="register_asCG">
      <div class="controls">
        <div class="input-prepend input-append input-block-level">
            <span class="add-on">Kopieer van</span>

            <select id="copy_from" name="copy_from" class="input-block-level">
              {template_select}
            </select>

            <input type="hidden" name="target" value="{id}" />
        </div>
      </div>
    </div>
    

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_ASE_CANCEL}</button>
        <button class="btn btn-primary" type="submit" aria-hidden="true"><i class="icon-copy"></i> Kopieren </button>
    </div>
    
    </form>
</div>

<script>
$(function(){


    $('.assocTicker').change(function(){
      var assocValue = 0;
      var assocId =  $(this).attr('assocId');
      var assayId =  $(this).attr('assayId');
      var assocOriginalId =  $(this).attr('originalAssayId');
      var assocTemplateId = '{template_id}';

      if ($(this).is(":checked")){
        assocValue = 1;
      }

      $.ajax({
          type: "POST",
          data: {},
          url: "{LB}/docgenAssociations/setAssoc/" + assocTemplateId + '/' + assayId + '/' + assocOriginalId + '/' + assocValue + '/' + assocId
      }).done(function(widget) {

      });

    });

  });


  function showHidden(){

  }
</script>
