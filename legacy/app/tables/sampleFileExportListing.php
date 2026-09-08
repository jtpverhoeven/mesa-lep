<table class="table table-condensed table-bordered" style="margin-top: 20px" id='{table_id}'>
    <thead>
        <tr>            
            
           
            <th>Barcode</th>
            <th>Bestandsnaam</th>
            <th>Status</th>
            <th>Upload datum</th>
            <th>Meesturen <span id="fileOverViewToggleSwitch">[wisselen]</span></th>
            
        </tr>
    </thead>

    <tbody>

        <tr id='samplefile_{id}'>                        
            <td>{barcode}</td>
            <td>{original_name}</td>
            <td>{sent_label} <button type="button" onclick="openHistory({id})" class="btn btn-mini">see</button></td>
            <td>{created_at}</td>            
            <td><input class="fileCheckBox" type="checkbox" onclick="toggleFile('{hash_name}', this)" style="zoom: 1.5;"></td>
        </tr>

    </tbody>
</table>

