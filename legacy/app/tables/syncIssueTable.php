<table class="table table-condensed table-bordered table-striped" id="{table_id}">
    <thead>
        <tr>            
            <th>mesaLIMS</th>
            <th>Action</th>
            <th>AuditAssist</th>                                           
        </tr>
    </thead>

    <tbody>

        <tr id="tr_{id}">          
            <td>{a} </td>
            <td>
                <select id="syncDropper_{id}" class="syncSelector input-block-level" parentTr="{id}" mesaId="{mesa_id}" aaId="{aa_id}" syncType="{type}">
                    <option value="NULL"> Select sync </option>
                    <option value="toMesa"> &lt;===</option>
                    <option value="toAA"> ===&gt; </option>                    
                </select>
            </td>
            <td>{b}</td>            
        </tr>

    </tbody>
</table>