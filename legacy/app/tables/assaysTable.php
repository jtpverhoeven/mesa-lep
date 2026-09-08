<table class="table table-condensed table-bordered" style="margin-top: 20px" id="{table_id}">
    <thead>
        <tr>
            <th><i class="icon-check"></i></th>
            <th>ID</th>
            <th>Orgineel ID</th>
            <th>{MESA_ASE_ASSAYNAME}</th>
            <th>{MESA_ASE_ASSAYBASE}</th>
            <th>{MESA_ASE_ASSAYTYPEOF}</th>
            <th>{MESA_ASE_USESDILUTION}</th>
            <th>{MESA_ASE_USESREPLICATES}</th>
            <th>{MESA_ASE_USESCONFIRMATION}</th>

        </tr>
    </thead>

    <tbody>

        <tr id="tr_{id}">
            <td><input type="radio" name="selectedAssay" id="selectedAssay" value="{id}" /></td>
            <td>{id}</td>
            <td>{original_id}</td>
            <td>{name}</td>
            <td>{type_base}</td>
            <td>{type}</td>
            <td>{dillution}</td>
            <td>{replicates}</td>
            <td>{confirmation}</td>
        </tr>

    </tbody>
</table>
