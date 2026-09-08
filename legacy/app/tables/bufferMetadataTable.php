<table class="table table-condensed table-bordered" style="margin-top: 20px" id='bufferMetadataTable'>
    <thead>
        <tr>                        
            <th>Meta naam</th>
            <th>Waarde</th>            
        </tr>
    </thead>

    <tbody>

        <tr id='metarow_{id}'>                        
            <td>

                <span class="showspans"  id="show_{index}_name" >

                    {name} 
                    <span class="pull-right">
                        <button class="btn btn-mini revealClicker"  typereveal="name" indexvalue="{index}"><i class="icon icon-pencil"></i></button>
                    </span>

                </span>
                
                <span id="edit_{index}_name" class="hide hidespans">                    
                    <input id="input_{index}_name" class="input" value="{name}" orivalue="{name}" keyname="{name}" parentid="{id}" />
                    <button class="btn btn-primary saveClicker" save-by-index="{saveByIndex}" keyname="{name}" parentid="{id}" sourced="input_{index}_name" typereveal="name" indexvalue="{index}"><i class="icon icon-save"></i></button>
                </span>
            </td>

            <td>
                
                {brvalue} 
                <span class="pull-right">
                    <button class="btn btn-mini revealClicker" typereveal="value" indexvalue="{index}"><i class="icon icon-pencil"></i></button>
                </span>
                
                 <span id="edit_{index}_value" class="hide">                    
                    <textarea id="input_{index}_value" class="input" value="{value}" orivalue="{value}" keyname="{name}" parentid="{id}">{value}</textarea>

                    <button class="btn btn-primary saveClicker" save-by-index="{saveByIndex}" keyname="{name}" parentid="{id}" sourced="input_{index}_value" typereveal="value" indexvalue="{index}"><i class="icon icon-save"></i></button>
                </span>

            </td>
        </tr>

    </tbody>
</table>

