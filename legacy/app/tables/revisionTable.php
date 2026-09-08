<table  class="table table-bordered">

    <thead>
        <tr>
            <th> <span id="revTableClicky"> {MESA_SLU_REVTIME} </span></th>
            <th>{MESA_SLU_REVUSER}</th>
            <th>{MESA_SLU_REVCHANGE}</th>
            <th>{MESA_SLU_REVFROM}</th>
            <th>{MESA_SLU_REVTO}</th>
            <th class="revisionAdmin hidden">Beheer</th>
        </tr>
    </thead>

    <tbody>
        <tr  id="rev_row_{id}">
            <td> {time} </td>
            <td> <a href="{LB}/profiles/view/{user}">{user}</a> </td>
            <td> {changed} - {dillution_visual} - {replicate} </td>
            <td> {from} </td>
            <td> {to} </td>
            <td class="revisionAdmin hidden"> <button type="button" class="btn btn-warning" onclick="remRev('{id}');">Verwijderen</button></td>
        </tr>
    </tbody>

</table>
