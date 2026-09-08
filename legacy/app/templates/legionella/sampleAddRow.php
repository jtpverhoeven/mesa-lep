<tr id="sample_row_{i}" sampleI="{i}">
    <td>
        <input type="text" class="input-block-level" id="follow_number_{i}" value="{follow_number}" disabled="disabled"/>
    </td>
    
    <td>
        <select id="profile_select_{i}" class="input-block-level leg-sample-profile">
            {global_profile_drop_list}
        </select>    
    </td>


    <td>
        <select id="sampling_method_{i}" class="input-block-level leg-sample-sampling">
            {sample_methods}
        </select>        
    </td>

    <td>
        <input type="text" class="input-block-level disabled" id="barcode_{i}" value="{barcode}" disabled/>
    </td>
</tr>