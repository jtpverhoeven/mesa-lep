<table class="table table-condensed table-bordered table-striped" id="{table_id}">
    <thead>
        <tr>            
            <th width='25px'><i class="icon-check"></i></th>
            <th>ID</th>
            <th>{MESA_LBD_NAME}</th>
            <th width='100px'>{MESA_LBD_DEFAULTSAMPLETBL}</th>
            <th width='100px'>{MESA_LBD_DEFAULTANALYSISTBL}</th>
        </tr>
    </thead>

    <tbody>

        <tr id="tr_{id}">          
            <td><input type="radio" name="selectedLabelDesign" id="selectedLabelDesign" value="{id}" /></td>
            <td>{id}</td>
            <td>{name}</td>            
            <td>{std_sample}</td>                             
            <td>{std_analysis}</td>                             
        </tr>

    </tbody>
</table>