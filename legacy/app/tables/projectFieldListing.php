<table class="table table-condensed table-bordered" style="margin-top: 20px" id='{table_id}'>
    <thead>
        <tr>            
            <th><i class="icon-check"></i></th>
            <th>ID</th>
            <th>{MESA_PFD_FIELDNAME}</th>
            <th>{MESA_PFD_FIELDALIAS}</th>
            <th>{MESA_PFD_FIELDTYPE}</th>
            <th>{MESA_PFD_STDVALUE}</th>
            <th>{MESA_PFD_POSITION}</th>                    
        </tr>
    </thead>

    <tbody>

        <tr id='projectfield_{id}'>            
            <td><input type="radio" name="selectedField" id="selectedField" value="{id}" /></td>
            <td>{id}</td>
            <td>{name}</td>
            <td>{alias}</td>
            <td>{type}</td>
            <td>{std_value}</td>
            <td>{position}</td>
        </tr>

    </tbody>
</table>