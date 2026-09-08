<table class="table table-condensed table-bordered" style="margin-top: 20px" id='{table_id}'>
    <thead>
        <tr>            
            <th width='25px'><i class="icon-check"></i></th>
            <th width='25px'>ID</th>
            <th>{MESA_SMP_NAME} </th>                     
        </tr>
    </thead>

    <tbody>

        <tr id='samplefield_{id}'>            
            <td><input type="radio" name="selectedProc" id="selectedProc" value="{id}" /></td>
            <td>{id}</td>
            <td>{name} <i class="{icon}" title="{MESA_SMP_HIDDEN}"></i> </td>        
        </tr>

    </tbody>
</table>