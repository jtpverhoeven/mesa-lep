<table class="table table-condensed table-bordered" style="margin-top: 20px" id="{table_id}">
    <thead>
        <tr>
            <th><i class="icon-check"></i></th>
            <th>ID</th>
            <th>Standaard rapport email</th>
            <th>Standaard rapport email (met bestanden)</th>
            <th>Standaard email voor bestanden</th>
            <th>Naam</th>            
        </tr>
    </thead>

    <tbody>

        <tr id="tr_{id}">
            <td><input type="radio" name="selectedEmail" id="selectedEmail{id}" value="{id}" /></td>
            <td>{id}</td>
            <td>{defaultmail}</td>            
            <td>{defaultmail_with_files}</td>            
            <td>{defaultmail_for_files}</td>            
            

            <td>{name}</td>    
        </tr>

    </tbody>
</table>

