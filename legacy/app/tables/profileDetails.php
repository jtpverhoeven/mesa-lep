<table class="table table-condensed table-bordered" style="margin-top: 20px" id="{table_id}">
    <thead>
        <tr>            
            <th>Assay</th>
            <th>Dillutions</th>
            <th>Replicates</th>
            <th>Reference Values</th>
            <th>Actions</th>                    
        </tr>
    </thead>

    <tbody>

        <tr id="assay_{id}">        
            <td>{assay_name}</td>
            <td>{dillutions_visual}</td>
            <td>{replicates}</td>
            <td>{reference_visual}</td>
            <td>
                <button id="assay_single_details_{assay_profile_id}" assayid="{assayId}" dillution="{dillution}" replicates="{replicates}" assaytype="{assaytype}" class="btn btn-mini btn-primary" onClick="editFromProfile('{profile}', '{id}', '{follow_no}', '{assay}');">Edit</button>
                <button class="btn btn-mini btn-warning" onClick="excludeFromProfile('{profile}', '{id}', '{follow_no}');">Remove</button>
            </td>
        </tr>

    </tbody>
</table>