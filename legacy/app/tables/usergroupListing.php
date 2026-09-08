<table class="table table-condensed table-bordered" style="margin-top: 20px" id="{table_id}">
    <thead>
        <tr>
            <th><i class="icon-check"></i></th>
            <th>{MESA_UGA_GROEPID}</th>
            <th>{MESA_UGA_GROUPNAME}</th>
            <th>{MESA_UGA_GROUPLEADER}</th>
            <th>{MESA_UGA_NOUSERS}</th>            
        </tr>
    </thead>

    <tbody>

        <tr id="tr_{id}">
            <td><input type="radio" name="selectedGroup" id="selectedGroup" value="{id}" /></td>
            <td>{id}</td>
            <td>{groupName} {superGroupIcon}</td>      
            <td>{groupLeader}</td>
            <td>{userNumber}</td>
        </tr>

    </tbody>
</table>