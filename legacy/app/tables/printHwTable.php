<table class="table table-condensed table-bordered table-striped" id="{table_id}">
    <thead>
        <tr>            
            <th>ID</th>
            <th>Naam</th>
            <th>Type</th>
            <th>Netwerk Adres</th>                                           
            <th>Poort</th>     
            <th></th>
        </tr>
    </thead>

    <tbody>

        <tr id="tr_{id}">          
            <td>{id}</td>
            <td>{name}</td>
            <td>{type}</td>
            <td>{adres}</td>            
            <td>{port}</td>            
            <td>
                <button type="btn" class="btn btn-small" onClick="editPrinter('{id}');"><i class="icon-wrench" ></i></button> 
                <button type="btn" class="btn btn-small btn-warning" onClick="removePrinter('{id}');"><i class="icon-trash"></i></button> 
            </td>            
        </tr>

    </tbody>
</table>