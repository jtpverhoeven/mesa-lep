<div id="metadataModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="metadataModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="metadataModalTitle">Metadata bekijken / wijzigen </h3>
    </div>
    <div class="modal-body" id="metadataModalBody" >

       <div class="control-group" id="metadataNameCG" style="display:block;">
           <label class="control-label" for="metadataName">Metadata naam</label>
            <div class="control">
              <input type="text" id="metadataName" class="input-block-level" placeholder="Metadata naam" />
           </div>
       </div>

       <div class="control-group" id="metadataValueCG" style="display:block;">
           <label class="control-label" for="metadataValue">Metadata waarde</label>
            <div class="control">
              <input type="text" id="metadataValue" class="input-block-level" placeholder="Metadata waarde" />
           </div>
       </div>


    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CANCEL}</button>
        <button class="btn btn-primary" aria-hidden="true" onclick="saveMetadata();"><i class="icon-save"></i>Opslaan</button>
    </div>
</div>