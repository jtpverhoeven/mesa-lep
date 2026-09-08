<table class="table table-condensed table-bordered" style="margin-top: 20px" id="{table_id}">
    <thead>
    <tr>
        <th><i class="icon-check"></i></th>
        <th>ID</th>
        <th>Naam</th>
        <th>Korte naam</th>
        <th>Bevestigings media</th>
        <th>Soort</th>
        <th>Supplementen</th>
        <th>Heeft datum/positie?</th>

    </tr>
    </thead>

    <tbody>

    <tr id="tr_{id}">
        <td><input type="radio" name="selectedMedia" id="selectedMedia" value="{id}" /></td>
        <td>{id}</td>
        <td>{name}</td>
        <td>{short_name}</td>
        <td>{confirmation_media}</td>
        <td>{type}</td>
        <td>{supplements}</td>
        <td>{hasDate}</td>
    </tr>

    </tbody>
</table>
