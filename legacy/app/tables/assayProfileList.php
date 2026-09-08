<table class="table table-condensed table-bordered" id="{table_id}" style="margin-top: 20px">
    <thead>
        <tr>
            <th><i class="icon-check"></i></th>
            <th>ID</th>
            <th>Assay</th>
            <th>Type </th>
            <th>Dillutions</th>
            <th>Replicates</th>
            <th>Reference value</th>
                               
        </tr>
    </thead>

    <tbody>

        <tr id="tr_{id}">
            <td><input type="radio" name="selectedAnalysis" id="selectedAnalysis" value="{id}" /></td>           
            <td>{id}</td>
            <td>{assay}</td>
            <td>{type}</td>
            <td>{dillutions}</td>
            <td>{replicates}</td>
            <td>{refscope}</td>
        </tr>

    </tbody>
</table>