<div class="span10">
    <div class="well">
        <h5><i class="icon-cogs"></i> Uitgaande email instellingen </h5>

        <form action="{LB}/portal/saveEmailSettings" method="POST">

        <div class="control-group" id="cgBCC">
          <label class="control-label" for="portalBCC">BCC</label>
          <div class="controls">
            <input id="portalBCC" name="portalBCC" type="text" class="input-block-level" placeholder="BCC email" value="{portalBCC}">
          </div>
        </div>

        
        <div class="control-group" id="cgMessage">
          <label class="control-label" for="portalMessage">Standaard email</label>
          <div class="controls">
            <textarea id="portalMessage" name="portalMessage" class="textarea-block-level" placeholder="Portal standard email" rows="10">{portalMessage}</textarea>
          </div>
        </div>

        <div class="pull-right">
            <button id="saveSettings" type="submit" class="btn btn-small btn-success"><i class="icon-save"></i> Opslaan </button>
        </div>
        
        
        </form>


    </div>
</div>