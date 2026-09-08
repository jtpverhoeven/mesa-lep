<table class="table table-condensed table-bordered" style="margin-top: 20px" id='{table_id}'>
    <thead>
        <tr>            
            <th width='25px'><i class="icon-check"></i></th>
            <th width='25px'>ID</th>
            <th>{MESA_SPF_FIELDNAME}</th>                     
            <th>{MESA_SPF_FIELDALIAS}</th>                     
        </tr>
    </thead>

    <tbody>

        <tr id='samplefield_{id}'>            
            <td><input type="radio" name="selectedProcField" id="selectedProcField" value="{id}" /></td>
            <td>{id}</td>
            <td>{name}</td>        
            <td>{alias}</td>        
        </tr>

    </tbody>
</table>