<table class="table table-condensed table-bordered" style="margin-top: 20px" id='sampleFieldTable'>
    <thead>
        <tr>            
            <th><i class="icon-check"></i></th>
            <th>ID</th>
            <th>{MESA_SMF_FIELDNAME}</th>
            <th>{MESA_SMF_FIELDALIAS}</th>
            <th>{MESA_SMF_FIELDTYPE}</th>
            <th>{MESA_SMF_FIELDSTDVALUE}</th>
            <th>{MESA_SMF_FIELDPOSITION}</th>                    
        </tr>
    </thead>

    <tbody>

        <tr id='samplefield_{id}'>            
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