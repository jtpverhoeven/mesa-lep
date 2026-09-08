<table class="table table-condensed table-bordered" style="margin-top: 20px" id='{table_id}'>
    <thead>
        <tr>            
            <th width="25px"><i class="icon-check"></i></th>
            <th width="25px">ID</th>
            <th>{MESA_GAF_FIELDNAME}</th>                  
            <th>{MESA_GAF_FIELDSTDVAL}</th>     
            <th width="25px">{MESA_GAF_POSITION}</th>                    
        </tr>
    </thead>

    <tbody>

        <tr id='assayfield_{id}'>            
            <td><input type="radio" name="selectedField" id="selectedField" value="{id}" /></td>
            <td>{id}</td>
            <td>{name}</td>  
            <td>{standard_value}</td>  
            <td>{position}</td>
        </tr>

    </tbody>
</table>