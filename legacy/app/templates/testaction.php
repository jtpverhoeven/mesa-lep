<div class="well well-small" style="min-height: 0px; padding: 2px; margin-bottom: 2px; ">
    <table style="width: 100%">
        <tbody>
            <tr>
                <td style="width:30px">{id}</td> 
                <td>action 
                    <input value="{action}" class="actionfield" data-action="{id}" data-field="action"/>
                    <!-- <select>
                        <option value="create_sample">create_sample</option>                    
                        <option value="add_assay">add_assay</option>                    
                        <option value="register_innoculation">register_innoculation</option>                    
                        <option value="set_result">set_result</option>                    
                        <option value="set_confirmation">set_confirmation</option>                    
                        <option value="set_confirmation_data">set_confirmation_data</option>                    
                        <option value="assert">assert</option>                    
                    </select> -->
                </td>
                <td>data<textarea style="width: 500px;" data-action="{id}" class="actionfield" data-field="data" >{data}</textarea></td>
                <td >order <input style="width: 30px" data-action="{id}" class="input actionfield" value="{order}" data-field="order" /></td>
                <td><a href="{LB}/testActions/destroy/{id}/{testset_id}" class="btn btn-danger btn-small">Actie verwijderen</a></td>

            </tr>
        </tbody>
    </table>
</div>