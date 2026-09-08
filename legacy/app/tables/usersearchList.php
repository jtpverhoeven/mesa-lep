<table class="table table-condensed table-bordered" id="{table_id}" style="margin-top: 20px">
    <thead>
        <tr>                        
            <th>{MESA_UGA_UNAME}</th>
            <th>{MESA_UGA_NAME}</th>  
            <th></th>
        </tr>
    </thead>

    <tbody>

        <tr>            
            <td>{username}</td>
            <td>{title} {first_name} {last_name}</td>
            <td><button onClick="addUserToGroup('{id}');" class="btn btn-mini"><i class="icon icon-plus"></i> {MESA_UGA_ADDTOGROUP} </a> </td>
        </tr>

    </tbody>
</table>