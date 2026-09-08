<table class="table table-condensed table-bordered table-striped" id="{table_id}">
    <thead>
        <tr>            
            <th>Variabele</th>
            <th>Instelling</th>
            <th>Standaard waarde</th>                                           
        </tr>
    </thead>

    <tbody>

        <tr id="tr_{id}">          
            <td>{cvar} <br /> <i> {description} </i> </td>
            <td><input id="cvar_{id}" class="input-cvar" type="text" value='{value}' /><button onClick="saveCvar('{id}');" class="btn btn-mini btn-primary"><i class="icon-save"></i> Opslaan </button></td>
            <td>{default}</td>            
        </tr>

    </tbody>
</table>