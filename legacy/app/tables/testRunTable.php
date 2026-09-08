
<table class="table table-bordered" style="width: 100%">
    <thead>
        <tr>
            <th>Test ID</th>
            <th>Naam</th>                    
            <th>Acties</th>            
            <th>Uitslag</th>
        </tr>
    </thead>

    <tbody>

        <tr >
            <td>{id}</td>
            <td>{name} [ <a href="{LB}/tests/runSingle/{id}/1"> Enkel deze test </a>] - [<a href="{LB}/tests/runSingle/{id}/0"> Zonder stickers </a>]</td>
            <td>{actionCount}</td>            
            <td>
                <p id="status_running_{id}" class="hidden" style="padding: 10px; font-size: 25px; background-color: yellow; color: black;"> In behandeling </p>    
                <p id="status_fail_{id}" class="hidden" style="padding: 10px; font-size: 25px; background-color: red; color: white;"> Mislukt </p>    
                <p id="status_pass_{id}" class="hidden" style="padding: 10px; font-size: 25px; background-color: green; color: black;"> Geslaagd </p>

            </td>
        </tr>

    
    </tbody>

</table>      