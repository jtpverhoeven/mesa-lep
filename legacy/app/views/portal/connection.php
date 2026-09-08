<div class="span10">
    <div class="well">
        <h5><i class="icon-cogs"></i> Portal verbinding </h5>

        <p> Instellingen voor verbindingen van Looking Glass local API naar Client portal </p>

        <form action="{LB}/portal/saveConnectionSettings" method="POST">

        <div class="control-group" id="urlCG">
          <label class="control-label" for="portalUrl">Client portal API Url</label>
          <div class="controls">
            <input id="portalUrl" name="portalUrl" type="text" class="input-block-level" placeholder="URL voor portal API" value="{portalUrl}">
          </div>
        </div>

        <div class="control-group" id="acceptCG">
          <label class="control-label" for="acceptType">Accept type</label>
          <div class="controls">
            <input id="acceptType" name="acceptType" type="text" class="input-block-level" placeholder="Expected fiel format from API" value="{acceptType}">
          </div>
        </div>

        <div class="control-group" id="key">
          <label class="control-label" for="bearer">Authorization token</label>
          <div class="controls">
            <textarea id="bearer" name="bearer" class="textarea-block-level" placeholder="API authorization bearer" rows="10">{bearer}</textarea>
          </div>
        </div>

        <div class="pull-left">
             <button id="checkConnection" type="button" class="btn btn-small btn-primary"><i class="icon-rocket"></i> Test verbinding</button>
        </div>

        <div class="pull-right">
            <button id="saveSettings" type="submit" class="btn btn-small btn-success"><i class="icon-save"></i> Opslaan </button>
        </div>

        </form>

        <br />
        <br />
    </div>
</div>

<script>

$(function(){

$('#checkConnection').on('click', function(){

  $.ajax({
      type: "POST",
      dataType: "json",
      url: "{LB}/portal/connectionTest"
  }).done(function(msg) {
    alert(msg['message']);
  });

});


});

</script>
