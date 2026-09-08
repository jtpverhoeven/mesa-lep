<table class="table table-condensed table-bordered" style="margin-top: 20px">
    <thead>
        <tr>
            <th>ID</th>
            <th>Naam</th>
            <th>Gebruikersnaam</th>
            <th>Actief</th>
            <th>Laatst gezien</th>
            <th>Acties</th>
        </tr>
    </thead>

    <tbody>

        <tr>
            <td>{id}</td>
            <td>{full_name}</td>
            <td>{username}</td>
            <td>{enabled}</td>
            <td>{last_seen}</td>
            <td><a class="btn btn-primary" onClick="selectFromDialog('{id}')">Bewerken</a></td>
        </tr>

    </tbody>
</table>
