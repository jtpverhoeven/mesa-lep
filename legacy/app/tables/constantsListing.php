<table id='constantsTable' class="table table-condensed table-bordered" style="margin-top: 20px">
    <thead>
        <tr>                        
            <th>ID</th>
            <th>Name</th>
            <th>Type</th>
            <th>Poll column</th>
            <th>Operation</th>                    
            <th>Restrictor</th>                    
            <th>Restrictor added</th>                    
            <th>Action</th>                    
        </tr>
    </thead>

    <tbody>

        <tr id='ct_{id}'>                        
            <td>{id}</td>
            <td>{const_name}</td>
            <td>{const_type_text}</td>
            <td>{poll_col}</td>
            <td>{operation}</td>
            <td>{restrictor}</td>
            <td>{restrictor_add}</td>
            <td>
                <button type='button' class='btn btn-primary btn-mini'><i class='icon-paste'></i></button>
                <button type='button' class='btn btn-primary btn-mini' onClick='removeConstant("{id}");'><i class='icon-trash'></i></button>
            </td>
        </tr>

    </tbody>
</table>