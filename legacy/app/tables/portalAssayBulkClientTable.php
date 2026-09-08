<table class="table table-condensed table-bordered" style="margin-top: 20px" id="{table_id}">
    <thead>
        <tr>        
            <th><i class="icon-check"></i></th>
            <th>ID</th>
            <th>Naam</th>
            <th>Klant categorie</th>
            <th><i class="icon-check"></i> Aantal analyses beschikbaar </th>
        </tr>
    </thead>

    <tbody>

        <tr id="tr_{id}">
            <td class="text-center"><input type="checkbox" id="client_{id}" name="selectedClient" {assay_ticker}/></td>
            <td>{id}</td>
            <td><label for="client_{id}">{name}</label></td>
            <td>{categories}</td>
            <td>{count}</td>
        </tr>
    </tbody>
</table>
