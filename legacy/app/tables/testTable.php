<table class="table table-condensed table-bordered" style="margin-top: 20px" id="{table_id}">
    <thead>
        <tr>
            <th><i class="icon-check"></i></th>
            <th>ID</th>
            <th>Test naam</th>
            <th></th>

        </tr>
    </thead>

    <tbody>

        <tr id="tr_{id}">
            <td><input type="radio" name="selectedTest" id="selectedTest" value="{id}" /></td>
            <td>{id}</td>
            <td>{name}</td>
            <td><a href="{LB}/testSets/run/{id}"class="btn btn-success"><i class="icon icon-play"></i> Test-set uitvoeren</a></td>                              
        </tr>

    </tbody>
</table>