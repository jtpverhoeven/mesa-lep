<div class="span2">

    <div class="well well-small">
        <h6> <i class="icon-barcode"></i> Monsternummer </h6>

        <div class="control-group" id="barCG">
          <input class="input-block-level" type="text" placeholder="Scan of typ een barcode" value="" id="barcodeEntry">
        </div>

        <div style="text-align: right;">
          <span id="nowLoading" style="display: none;"><i class="icon-spinner icon-spin"></i> <em> Bezig met laden </em></span>
          <button id="runTrail" class="btn btn-primary btn-mini" type="button"><i class="icon-gears"></i> AuditTrail  bekijken </button>
        </div>
    </div>

</div>

<div class="span8">

    <div class="well well-small">
        <h6> <i class="icon-shield"></i> AuditTrail output</h6>

    <div id="trailLoading" class="hide" style="text-align: center; padding: 10px;">
      <i class="icon-4x icon-spin icon-spinner"></i>
    </div>

    <div id="trailOutput">
    </div>

</div>

</div>
<script>

  $(function(){

    $('#runTrail').on('click', function(){
      var barcode = $('#barcodeEntry').val();
      runTrail(barcode);
    });

    $('#barcodeEntry').bind('keydown', 'return', function(){
      var barcode = $(this).val();
      runTrail(barcode);
    });

  });

  function runTrail(barcode){

    $('#trailOutput').html('');
    $('#trailLoading').show();

    $.ajax({
            type: "POST",
            data: { barcode: barcode},
            url: "{LB}/samples/sampleAuditTrail"
    }).done(function(trailOutput) {
      $('#trailOutput').html(trailOutput);
      $('#trailLoading').hide();
    });
  }



</script>
